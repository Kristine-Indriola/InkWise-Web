document.addEventListener('DOMContentLoaded', () => {
    const inputs = document.querySelectorAll('[data-preview-target]');
    const cardBg = document.querySelector('.preview-card-bg');
    const thumbButtons = document.querySelectorAll('[data-card-thumb]');
    const viewButtons = document.querySelectorAll('[data-card-view]');
    const extraPreviewContainer = document.getElementById('extraPreviewContainer');
    const textFieldList = document.getElementById('textFieldList');
    const addFieldBtn = document.querySelector('[data-add-text-field]');
    const navButtons = document.querySelectorAll('.sidenav-btn');
    let customIndex = 1;

    const modals = {
        text: document.getElementById('text-modal'),
        uploads: document.getElementById('uploads-modal'),
        graphics: document.getElementById('graphics-modal'),
        template: document.getElementById('template-modal'),
        color: document.getElementById('color-modal'),
        qr: document.getElementById('qr-modal'),
        background: document.getElementById('background-modal'),
        product: document.getElementById('product-modal'),
        tables: document.getElementById('tables-modal')
    };

    const closeButtons = document.querySelectorAll('[data-modal-close]');

    const hideModal = (modal) => {
        if (!modal) {
            return;
        }
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        const section = modal.dataset.section;
        if (section) {
            const navBtn = document.querySelector(`.sidenav-btn[data-nav="${section}"]`);
            navBtn?.classList.remove('active');
        }
    };

    const hideAllModals = () => {
        Object.values(modals).forEach((modal) => hideModal(modal));
    };

    const showModal = (section) => {
        const modal = modals[section];
        if (!modal) {
            return;
        }
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        const focusTarget = modal.querySelector('input:not([type="hidden"])');
        focusTarget?.focus({ preventScroll: true });
    };

    if (navButtons.length) {
        navButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const section = button.dataset.nav || 'text';
                const targetModal = modals[section];
                const isOpen = targetModal?.classList.contains('is-open');

                hideAllModals();
                navButtons.forEach((btn) => btn.classList.remove('active'));

                if (!isOpen) {
                    showModal(section);
                    button.classList.add('active');
                }
            });
        });
    }

    closeButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal');
            hideModal(modal);
        });
    });

    window.addEventListener('click', (event) => {
        if (event.target.classList.contains('modal') && event.target.classList.contains('is-open')) {
            hideModal(event.target);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            const openModal = document.querySelector('.modal.is-open');
            if (openModal) {
                hideModal(openModal);
            }
        }
    });

    hideAllModals();
    showModal('text');
    const defaultButton = document.querySelector('.sidenav-btn[data-nav="text"]');
    defaultButton?.classList.add('active');

    const initInput = (input) => {
        const targetName = input.dataset.previewTarget;
        if (!targetName) {
            return;
        }
        const previewNode = document.querySelector(`[data-preview-node="${targetName}"]`);
        const defaultText = previewNode ? previewNode.dataset.defaultText || previewNode.textContent : '';

        const applyValue = () => {
            if (!previewNode) {
                return;
            }
            const trimmed = input.value.trim();
            previewNode.textContent = trimmed || defaultText || '';
        };

        input.addEventListener('input', applyValue);
        applyValue();
    };

    inputs.forEach(initInput);

    // Create a custom text field (reusable when binding SVG text that has no form field)
    const createCustomField = (previewKey, value = '') => {
        const wrapper = document.createElement('div');
        wrapper.className = 'text-field-item';

        const label = document.createElement('span');
        label.className = 'text-field-label';
        label.textContent = 'Custom';
        wrapper.appendChild(label);

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'text-field-input';
        input.placeholder = 'Add your text';
        input.dataset.previewTarget = previewKey;
        input.value = value;
        wrapper.appendChild(input);

        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.className = 'text-field-delete';
        delBtn.setAttribute('aria-label', 'Delete field');
        delBtn.innerHTML = '<i class="fa-regular fa-trash-can"></i>';
        wrapper.appendChild(delBtn);

        textFieldList.appendChild(wrapper);

        const pill = document.createElement('div');
        pill.className = 'pill';
        pill.dataset.previewNode = previewKey;
        pill.dataset.defaultText = value || 'CUSTOM TEXT';
        pill.textContent = value || 'CUSTOM TEXT';
        extraPreviewContainer.appendChild(pill);

        initInput(input);
        attachDeleteHandler(wrapper);
        return input;
    };

    // Load an SVG file and bind any text/image elements (with data-preview-node) into the UI and make them editable
    const loadSVGAndBind = async (url, side = 'front') => {
        const svgId = side === 'back' ? 'preview-svg-back' : 'preview-svg-front';
        const svgElement = document.getElementById(svgId);
        
        if (!url) {
            if (svgElement) svgElement.innerHTML = '';
            return;
        }

        try {
            let svgText = '';
            if (url.startsWith('data:image/svg+xml')) {
                svgText = decodeURIComponent(url.split(',')[1] || '');
            } else {
                const res = await fetch(url, { cache: 'no-store' });
                if (!res.ok) {
                    console.warn('Failed to fetch SVG:', res.status);
                    return;
                }
                svgText = await res.text();
            }

            if (svgElement) {
                svgElement.innerHTML = svgText;

                // Parse viewBox to adjust canvas size
                const tempSvg = new DOMParser().parseFromString(svgText, 'image/svg+xml').documentElement;
                const viewBox = tempSvg.getAttribute('viewBox');
                if (viewBox) {
                    const [, , vbWidth, vbHeight] = viewBox.split(' ').map(Number);
                    const scale = 1.5; // Scale factor to match desired pixel sizes
                    const pixelWidth = Math.round(vbWidth * scale);
                    const pixelHeight = Math.round(vbHeight * scale);

                    const wrapper = svgElement.closest('.preview-canvas-wrapper');
                    if (wrapper) {
                        wrapper.style.width = pixelWidth + 'px';
                        wrapper.style.height = pixelHeight + 'px';

                        // Update measure values (only for the first canvas to avoid conflicts)
                        if (side === 'front') {
                            const widthIn = (vbWidth / 100).toFixed(2);
                            const heightIn = (vbHeight / 100).toFixed(2);
                            document.querySelector('.canvas-measure-vertical .measure-value').textContent = heightIn + 'in';
                            document.querySelector('.canvas-measure-horizontal .measure-value').textContent = widthIn + 'in';
                        }
                    }

                    // Adjust stage width
                    const stage = document.querySelector('.canvas-stage');
                    if (stage && side === 'front') {
                        stage.style.width = (pixelWidth + 112) + 'px';
                    }
                }
            }

            // Find all elements with data-preview-node
            const nodes = svgElement.querySelectorAll('[data-preview-node]');

            // Rebuild the form controls for text fields based on SVG nodes.
            // If there are no text nodes, show the default placeholder fields.
            if (textFieldList && side === 'front') { // Only rebuild form controls for front side
                textFieldList.innerHTML = '';

                const createFieldItem = (previewKey, value = '', labelText = '') => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'text-field-item';

                    const label = document.createElement('span');
                    label.className = 'text-field-label';
                    label.textContent = labelText || previewKey;
                    wrapper.appendChild(label);

                    const input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'text-field-input';
                    input.dataset.previewTarget = previewKey;
                    input.value = value || '';
                    wrapper.appendChild(input);

                    const delBtn = document.createElement('button');
                    delBtn.type = 'button';
                    delBtn.className = 'text-field-delete';
                    delBtn.setAttribute('aria-label', 'Delete field');
                    delBtn.innerHTML = '<i class="fa-regular fa-trash-can"></i>';
                    wrapper.appendChild(delBtn);

                    textFieldList.appendChild(wrapper);
                    initInput(input);
                    attachDeleteHandler(wrapper);
                    return input;
                };

                if (nodes.length > 0) {
                    nodes.forEach((node) => {
                        const key = node.getAttribute('data-preview-node');
                        if (!key) return;

                        if (node.tagName.toLowerCase() === 'text') {
                            createFieldItem(key, node.textContent || '', key);
                        }
                    });
                } else {
                    // No SVG-bound text fields — show default editable placeholders
                    const defaults = [
                        ['date', 'Date'],
                        ['heading', 'Heading'],
                        ['accent', 'Accent'],
                        ['subheading', 'Subheading'],
                        ['names', 'Names'],
                        ['location', 'Location']
                    ];
                    defaults.forEach(([k, label]) => createFieldItem(k, '', label));
                }
            }

            nodes.forEach((node) => {
                const key = node.getAttribute('data-preview-node');
                if (!key) return;

                if (node.tagName.toLowerCase() === 'text') {
                    // Make text editable
                    node.setAttribute('contenteditable', 'true');
                    node.style.cursor = 'text';
                    node.addEventListener('input', () => {
                        const input = document.querySelector(`#textFieldList input[data-preview-target="${key}"]`);
                        if (input) {
                            input.value = node.textContent;
                        }
                    });
                    node.addEventListener('click', () => {
                        openTextModalAndFocus(key);
                    });
                } else if (node.tagName.toLowerCase() === 'image') {
                    // Make image replaceable
                    node.style.cursor = 'pointer';
                    node.addEventListener('click', () => {
                        // Open uploads modal or file input
                        const input = document.createElement('input');
                        input.type = 'file';
                        input.accept = 'image/*';
                        input.onchange = (e) => {
                            const file = e.target.files[0];
                            if (file) {
                                const reader = new FileReader();
                                reader.onload = (ev) => {
                                    node.setAttribute('href', ev.target.result);
                                };
                                reader.readAsDataURL(file);
                            }
                        };
                        input.click();
                    });
                }

                // Sync from input to SVG
                const input = document.querySelector(`#textFieldList input[data-preview-target="${key}"]`);
                if (input && node.tagName.toLowerCase() === 'text') {
                    // avoid adding duplicate listeners
                    input.addEventListener('input', () => {
                        node.textContent = input.value;
                    });
                    // initialize SVG text from input if present
                    if (input.value && input.value.trim() !== '') {
                        node.textContent = input.value;
                    } else {
                        // otherwise keep existing node text (or blank)
                        input.value = node.textContent || '';
                    }
                }
            });
        } catch (e) {
            console.warn('Could not load SVG for binding:', e);
        }
    };

    // Attach delete handlers to existing text-field-items (initial fields)
    const attachDeleteHandler = (wrapper) => {
        if (!wrapper) return;
        const delBtn = wrapper.querySelector('.text-field-delete');
        const input = wrapper.querySelector('input[data-preview-target]');
        if (!delBtn || !input) return;

        // If the delete button is disabled (built-in field), do nothing
        if (delBtn.disabled || delBtn.getAttribute('aria-disabled') === 'true') {
            return;
        }

        // Clear any previous handlers to avoid duplicates
        delBtn.replaceWith(delBtn.cloneNode(true));
        const newBtn = wrapper.querySelector('.text-field-delete');

        newBtn.addEventListener('click', () => {
            const previewKey = input.dataset.previewTarget;

            // Remove the form control
            wrapper.remove();

            // Remove the corresponding preview pill if present
            if (extraPreviewContainer) {
                const pill = extraPreviewContainer.querySelector(`[data-preview-node="${previewKey}"]`);
                pill?.remove();
            }
        });
    };

    // Initialize delete handlers for any existing items (Blade-rendered)
    const existingItems = document.querySelectorAll('#textFieldList .text-field-item');
    existingItems.forEach((w) => attachDeleteHandler(w));

    // Helper: open text modal and focus a specific input by preview name
    const openTextModalAndFocus = (previewName) => {
        if (!previewName) return;

        // Open the text modal and mark nav active
        hideAllModals();
        showModal('text');
        navButtons.forEach((btn) => btn.classList.remove('active'));
    const textNav = document.querySelector('.sidenav-btn[data-nav="text"]');
        textNav?.classList.add('active');

        // Focus matching input inside the modal (if present)
        setTimeout(() => {
            const targetInput = document.querySelector(`#textFieldList input[data-preview-target="${previewName}"]`);
            if (targetInput) {
                try {
                    targetInput.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                } catch (e) {
                    const parent = document.getElementById('textFieldList');
                    if (parent) parent.scrollTop = targetInput.offsetTop - 12;
                }
                targetInput.focus({ preventScroll: false });
                return;
            }

            // If no matching input exists, focus the add button so user can create a binding
            const addBtn = document.querySelector('[data-add-text-field]');
            addBtn?.focus({ preventScroll: false });
        }, 60);
    };

    const setActiveCard = (side) => {
        currentSide = side;
        
        // Find the appropriate card background for this side
        const cardBgElement = document.querySelector(`.preview-card-bg[data-side="${side}"]`);
        if (!cardBgElement) {
            return;
        }
        
        let image = '';
        let isUploaded = false;
        let svgCandidate = '';
        if (side === 'front' && uploadedFrontImage) {
            image = uploadedFrontImage;
            svgCandidate = uploadedFrontImage;
            isUploaded = true;
        } else if (side === 'back' && uploadedBackImage) {
            image = uploadedBackImage;
            svgCandidate = uploadedBackImage;
            isUploaded = true;
        } else {
            image = cardBgElement.dataset[`${side}Image`] || '';
            svgCandidate = cardBgElement.dataset[`${side}Svg`] || '';
            isUploaded = false;
        }

        // Prefer explicit SVG dataset when present, otherwise fall back to image
        const isSvgSourceCandidate = (val) => typeof val === 'string' && (val.startsWith('data:image/svg+xml') || /\.svg($|\?)/i.test(val));

        if (svgCandidate && isSvgSourceCandidate(svgCandidate)) {
            loadSVGAndBind(svgCandidate, side);
            cardBgElement.style.backgroundImage = 'none';
            const svgElement = document.getElementById(side === 'back' ? 'preview-svg-back' : 'preview-svg-front');
            if (svgElement) svgElement.style.display = 'block';
        } else if (image && isSvgSourceCandidate(image)) {
            loadSVGAndBind(image, side);
            cardBgElement.style.backgroundImage = 'none';
            const svgElement = document.getElementById(side === 'back' ? 'preview-svg-back' : 'preview-svg-front');
            if (svgElement) svgElement.style.display = 'block';
        } else {
            // Show as background image
            cardBgElement.style.backgroundImage = image ? `url('${image}')` : '';
            const svgElement = document.getElementById(side === 'back' ? 'preview-svg-back' : 'preview-svg-front');
            if (svgElement) {
                svgElement.innerHTML = '';
                svgElement.style.display = 'none';
            }
        }
        cardBgElement.style.backgroundSize = isUploaded && !isSVG ? 'auto' : 'cover';

        viewButtons.forEach((btn) => {
            btn.classList.toggle('active', btn.dataset.cardView === side);
        });
        thumbButtons.forEach((thumb) => {
            const isActiveThumb = thumb.dataset.cardThumb === side;
            thumb.classList.toggle('active', isActiveThumb);
            thumb.setAttribute('aria-pressed', isActiveThumb ? 'true' : 'false');
        });
    };

    viewButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            setActiveCard(btn.dataset.cardView);
        });
    });

    thumbButtons.forEach((thumb) => {
        thumb.addEventListener('click', () => {
            setActiveCard(thumb.dataset.cardThumb);
        });
    });

    // Initialize both front and back canvases
    setActiveCard('front');
    setActiveCard('back');
  
    
    // Delegate clicks inside the preview area: clicking any element with
    // data-preview-node will open the Text modal and focus the corresponding field.
    const previewArea = document.querySelector('.preview-overlay');
    if (previewArea) {
        previewArea.addEventListener('click', (ev) => {
            const node = ev.target.closest('[data-preview-node]');
            if (node) {
                ev.stopPropagation();
                const name = node.dataset.previewNode;
                if (name) openTextModalAndFocus(name);
            }
        });
    }

    if (addFieldBtn && textFieldList && extraPreviewContainer) {
        addFieldBtn.addEventListener('click', () => {
            const previewKey = `custom_${Date.now()}_${customIndex}`;
            customIndex += 1;

            const wrapper = document.createElement('div');
            wrapper.className = 'text-field-item';

            const label = document.createElement('span');
            label.className = 'text-field-label';
            label.textContent = 'Custom';
            wrapper.appendChild(label);

            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'text-field-input';
            input.placeholder = 'Add your text';
            input.dataset.previewTarget = previewKey;
            wrapper.appendChild(input);

            // create delete button for the new custom field (enabled)
            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'text-field-delete';
            delBtn.setAttribute('aria-label', 'Delete field');
            delBtn.innerHTML = '<i class="fa-regular fa-trash-can"></i>';
            wrapper.appendChild(delBtn);

            textFieldList.appendChild(wrapper);

            const pill = document.createElement('div');
            pill.className = 'pill';
            pill.dataset.previewNode = previewKey;
            pill.dataset.defaultText = 'CUSTOM TEXT';
            pill.textContent = 'CUSTOM TEXT';
            extraPreviewContainer.appendChild(pill);

            initInput(input);

            // attach delete handler to the newly created wrapper
            attachDeleteHandler(wrapper);

            // Give the browser a moment to render and layout the new item, then
            // if the field list is overflowing, scroll it to show the new field.
            setTimeout(() => {
                try {
                    if (textFieldList.scrollHeight > textFieldList.clientHeight) {
                        textFieldList.scrollTo({ top: textFieldList.scrollHeight, behavior: 'smooth' });
                    }
                } catch (e) {
                    // ignore scroll errors on older browsers
                    textFieldList.scrollTop = textFieldList.scrollHeight;
                }

                // focus the new input and ensure it is visible; preventScroll:false
                input.focus({ preventScroll: false });
            }, 60);
        });
    }

    let uploadedFrontImage = null;
    let uploadedBackImage = null;
    let currentSide = 'front';

    // Image dragging functionality removed for inline SVG editing

    // Handle image uploads
    const imageInput = document.getElementById('image-upload');
    const uploadButton = document.getElementById('upload-button');
    const quickUploadButton = document.getElementById('quick-upload-button');
    const recentUploadsGrid = document.getElementById('recentUploadsGrid');
    const quickRecentUploads = document.getElementById('quickRecentUploads');

    // Recently uploaded images storage
    const RECENT_UPLOADS_KEY = 'inkwise_recent_uploads';
    const MAX_RECENT_UPLOADS = 12;

    const getRecentUploads = () => {
        try {
            const stored = localStorage.getItem(RECENT_UPLOADS_KEY);
            return stored ? JSON.parse(stored) : [];
        } catch (e) {
            return [];
        }
    };

    const saveRecentUpload = (dataUrl, filename, side) => {
        const uploads = getRecentUploads();
        const newUpload = {
            id: Date.now(),
            dataUrl,
            filename,
            side,
            timestamp: new Date().toISOString()
        };

        // Remove duplicates based on filename
        const filtered = uploads.filter(upload => upload.filename !== filename);

        // Add new upload to the beginning
        filtered.unshift(newUpload);

        // Keep only the most recent uploads
        const limited = filtered.slice(0, MAX_RECENT_UPLOADS);

        try {
            localStorage.setItem(RECENT_UPLOADS_KEY, JSON.stringify(limited));
        } catch (e) {
            // If storage is full, clear old items and try again
            const cleared = limited.slice(0, Math.floor(MAX_RECENT_UPLOADS / 2));
            localStorage.setItem(RECENT_UPLOADS_KEY, JSON.stringify(cleared));
        }

        renderRecentUploads();
        renderQuickRecentUploads();
    };

    const renderRecentUploads = () => {
        if (!recentUploadsGrid) return;

        const uploads = getRecentUploads();

        if (uploads.length === 0) {
            recentUploadsGrid.innerHTML = `
                <div class="no-recent-uploads">
                    <p>No recent uploads found. Upload some images above to see them here.</p>
                </div>
            `;
            return;
        }

        const uploadItems = uploads.map(upload => `
            <div class="recent-upload-item" data-upload-id="${upload.id}" data-image-url="${upload.dataUrl}" data-side="${upload.side}">
                <img src="${upload.dataUrl}" alt="${upload.filename}" loading="lazy">
                <div class="upload-info">
                    <span>${upload.filename}</span>
                </div>
            </div>
        `).join('');

        recentUploadsGrid.innerHTML = uploadItems;

        // Add click handlers for recent upload items
        const uploadItemsElements = recentUploadsGrid.querySelectorAll('.recent-upload-item');
        uploadItemsElements.forEach(item => {
            item.addEventListener('click', () => {
                const imageUrl = item.dataset.imageUrl;
                const side = item.dataset.side;

                // Remove selected class from all items
                uploadItemsElements.forEach(el => el.classList.remove('selected'));
                // Add selected class to clicked item
                item.classList.add('selected');

                // Apply the image to the appropriate side
                if (side === 'front') {
                    uploadedFrontImage = imageUrl;
                } else {
                    uploadedBackImage = imageUrl;
                }

                if (currentSide === side) {
                    setActiveCard(side);
                }

                // Show a brief success indication
                item.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    item.style.transform = '';
                }, 150);
            });
        });
    };

    const renderQuickRecentUploads = () => {
        if (!quickRecentUploads) return;

        const uploads = getRecentUploads();
        const recentUploads = uploads.slice(0, 5); // Show only the 5 most recent

        if (recentUploads.length === 0) {
            quickRecentUploads.innerHTML = '';
            return;
        }

        const quickUploadItems = recentUploads.map(upload => `
            <div class="quick-recent-upload-item" data-upload-id="${upload.id}" data-image-url="${upload.dataUrl}" data-side="${upload.side}" title="${upload.filename}">
                <img src="${upload.dataUrl}" alt="${upload.filename}" loading="lazy">
            </div>
        `).join('');

        quickRecentUploads.innerHTML = quickUploadItems;

        // Add click handlers for quick recent upload items
        const quickUploadItemsElements = quickRecentUploads.querySelectorAll('.quick-recent-upload-item');
        quickUploadItemsElements.forEach(item => {
            item.addEventListener('click', () => {
                const imageUrl = item.dataset.imageUrl;
                const side = item.dataset.side;

                // Remove selected class from all items
                quickUploadItemsElements.forEach(el => el.classList.remove('selected'));
                // Add selected class to clicked item
                item.classList.add('selected');

                // Apply the image to the appropriate side
                if (side === 'front') {
                    uploadedFrontImage = imageUrl;
                } else {
                    uploadedBackImage = imageUrl;
                }

                if (currentSide === side) {
                    setActiveCard(side);
                }

                // Show a brief success indication
                item.style.transform = 'scale(0.9)';
                setTimeout(() => {
                    item.style.transform = '';
                }, 150);
            });
        });
    };

    const handleImageUpload = (input) => {
        const file = input.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (e) => {
            const dataUrl = e.target.result;

            // Save to recent uploads (default to front side for single upload)
            saveRecentUpload(dataUrl, file.name, 'front');

            // Apply to front side by default
            uploadedFrontImage = dataUrl;
            if (currentSide === 'front') {
                setActiveCard('front');
            }

            // Don't automatically open the uploads modal for quick uploads
            // Users can click the uploads nav button if they want to see the full list
        };
        reader.readAsDataURL(file);
    };

    if (imageInput) {
        imageInput.addEventListener('change', () => handleImageUpload(imageInput));
    }

    if (uploadButton) {
        uploadButton.addEventListener('click', () => {
            imageInput.click();
        });
    }

    if (quickUploadButton) {
        quickUploadButton.addEventListener('click', () => {
            imageInput.click();
        });
    }

    // Initialize recent uploads display
    renderRecentUploads();
    renderQuickRecentUploads();

    const tooltipPairs = document.querySelectorAll('.pill-with-tooltip');
    const previewCanvas = document.querySelector('.preview-canvas-wrapper');
    const previewGuides = document.querySelector('.preview-guides');

    const updateHighlight = (buttonId, isActive) => {
        if (buttonId === 'safety-pill') {
            previewCanvas?.classList.toggle('highlight-safety', isActive);
        }

        if (buttonId === 'bleed-pill') {
            previewGuides?.classList.toggle('highlight-bleed', isActive);
        }
    };

    const closeTooltipPair = (pair) => {
        const btn = pair?.querySelector('.canvas-pill');
        const tooltip = pair?.querySelector('.tooltip');
        if (!btn || !tooltip) {
            return;
        }
        pair.classList.remove('is-active');
        btn.setAttribute('aria-expanded', 'false');
        tooltip.setAttribute('aria-hidden', 'true');
        updateHighlight(btn.id, false);
    };

    tooltipPairs.forEach((pair) => {
        const button = pair.querySelector('.canvas-pill');
        const tooltip = pair.querySelector('.tooltip');

        if (!button || !tooltip) {
            return;
        }

        button.addEventListener('click', (event) => {
            event.preventDefault();
            const shouldActivate = !pair.classList.contains('is-active');

            tooltipPairs.forEach((otherPair) => {
                if (otherPair !== pair) {
                    closeTooltipPair(otherPair);
                }
            });

            if (shouldActivate) {
                pair.classList.add('is-active');
                button.setAttribute('aria-expanded', 'true');
                tooltip.setAttribute('aria-hidden', 'false');
                updateHighlight(button.id, true);
            } else {
                closeTooltipPair(pair);
            }
        });

        button.addEventListener('mouseenter', () => {
            tooltip.setAttribute('aria-hidden', 'false');
        });

        button.addEventListener('focus', () => {
            tooltip.setAttribute('aria-hidden', 'false');
        });

        pair.addEventListener('mouseleave', () => {
            if (!pair.classList.contains('is-active')) {
                tooltip.setAttribute('aria-hidden', 'true');
            }
        });

        button.addEventListener('blur', () => {
            if (!pair.contains(document.activeElement) && !pair.classList.contains('is-active')) {
                tooltip.setAttribute('aria-hidden', 'true');
            }
        });

        button.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeTooltipPair(pair);
                button.blur();
            }
        });
    });

    document.addEventListener('click', (event) => {
        const pair = event.target.closest('.pill-with-tooltip');
        if (!pair) {
            tooltipPairs.forEach((item) => closeTooltipPair(item));
        }
    });

    // Zoom functionality
    const zoomSelect = document.getElementById('canvas-zoom-select');
    const zoomDisplay = document.getElementById('canvas-zoom-display');
    const zoomButtons = document.querySelectorAll('[data-zoom-step]');
    const canvasWrapper = document.querySelector('.preview-canvas-wrapper');
    const canvasStage = document.querySelector('.canvas-stage');

    const zoomLevels = [0.25, 0.5, 0.75, 1, 1.5, 2, 3];

    if (zoomSelect && canvasWrapper && canvasStage) {
        const applyZoom = (scale) => {
            canvasStage.style.setProperty('--canvas-scale', scale.toString());
            // ensure wrapper remains unscaled so hit zones stay aligned
            canvasWrapper.style.removeProperty('transform');
            zoomSelect.value = scale.toString();
            if (zoomDisplay) {
                zoomDisplay.textContent = `${Math.round(scale * 100)}%`;
            }
        };

        const stepZoom = (direction) => {
            const currentScale = parseFloat(zoomSelect.value) || 1;
            const currentIndex = zoomLevels.indexOf(currentScale);
            if (currentIndex === -1) {
                return;
            }

            let targetIndex = currentIndex;
            if (direction === 'up' && currentIndex < zoomLevels.length - 1) {
                targetIndex = currentIndex + 1;
            }
            if (direction === 'down' && currentIndex > 0) {
                targetIndex = currentIndex - 1;
            }

            if (targetIndex !== currentIndex) {
                applyZoom(zoomLevels[targetIndex]);
            }
        };

        zoomSelect.addEventListener('change', (event) => {
            const scale = parseFloat(event.target.value) || 1;
            applyZoom(scale);
        });

        zoomButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const direction = btn.dataset.zoomStep;
                if (!direction) {
                    return;
                }
                stepZoom(direction === 'up' ? 'up' : 'down');
            });
        });

        // Add mouse wheel and touchpad zoom
        if (canvasStage) {
            canvasStage.addEventListener('wheel', (event) => {
                event.preventDefault();
                const currentScale = parseFloat(zoomSelect.value) || 1;
                let newScale = currentScale;

                if (event.deltaY > 0) {
                    const currentIndex = zoomLevels.indexOf(currentScale);
                    if (currentIndex > 0) {
                        newScale = zoomLevels[currentIndex - 1];
                    }
                } else {
                    const currentIndex = zoomLevels.indexOf(currentScale);
                    if (currentIndex < zoomLevels.length - 1) {
                        newScale = zoomLevels[currentIndex + 1];
                    }
                }

                if (newScale !== currentScale) {
                    applyZoom(newScale);
                }
            }, { passive: false });
        }

        applyZoom(parseFloat(zoomSelect.value) || 1);
    }
});
