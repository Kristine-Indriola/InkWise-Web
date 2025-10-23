document.addEventListener('DOMContentLoaded', () => {
    const materialSource = window.materialsData || [];
    const rawMaterials = Array.isArray(materialSource) ? materialSource : Object.values(materialSource);
    const templates = Array.isArray(window.templatesData) ? window.templatesData : [];
    const materials = rawMaterials
        .map(item => ({
            id: String(item.material_id ?? item.id ?? ''),
            name: item.material_name ?? item.name ?? '',
            type: item.material_type ?? item.type ?? '',
            unitCost: (() => {
                const value = item.unit_cost ?? item.unitPrice ?? item.unit_price ?? item.price;
                const parsed = value !== undefined && value !== null ? parseFloat(value) : null;
                return Number.isFinite(parsed) ? parsed : null;
            })(),
        }))
        .filter(item => item.id || item.name);
    const assetBase = ((window.assetUrl || '').replace(/\/$/, '')) || '';

    const materialById = new Map(materials.map(mat => [mat.id, mat]));
    const materialByName = new Map(materials.map(mat => [mat.name.trim().toLowerCase(), mat]));

    function findMaterialById(id) {
        if (!id) return null;
        return materialById.get(String(id)) || null;
    }

    function findMaterialByName(name) {
        if (!name) return null;
        return materialByName.get(String(name).trim().toLowerCase()) || null;
    }

    function normalize(value) {
        return value === undefined || value === null ? '' : String(value).trim().toLowerCase();
    }

    // Generic add/remove handlers for other repeatable rows (paper stocks, addons, colors, bulk orders)
    document.querySelectorAll('.paper-stock-rows, .addon-rows, .color-rows, .bulk-order-rows').forEach(container => {
        container.addEventListener('click', event => {
            if (event.target.classList.contains('add-row')) {
                event.preventDefault();
                const rowClass = container.classList.contains('paper-stock-rows') ? '.paper-stock-row' :
                                container.classList.contains('addon-rows') ? '.addon-row' :
                                container.classList.contains('color-rows') ? '.color-row' :
                                container.classList.contains('bulk-order-rows') ? '.bulk-order-row' : 'div';
                const rows = container.querySelectorAll(rowClass);
                if (!rows.length) return;
                const lastRow = rows[rows.length - 1];
                const newRow = lastRow.cloneNode(true);
                const newIndex = rows.length;

                // Update names and ids
                newRow.querySelectorAll('[name]').forEach(element => {
                    element.name = element.name.replace(/\[\d+\]/, `[${newIndex}]`);
                });
                newRow.querySelectorAll('[id]').forEach(element => {
                    element.id = element.id.replace(/_(\d+)(?=_[^_]+$)/, `_${newIndex}`);
                    element.id = element.id.replace(/_(\d+)$/, `_${newIndex}`);
                });

                // Clear input values
                newRow.querySelectorAll('input, textarea, select').forEach(input => {
                    if (input.type === 'file') {
                        // Clear file inputs by replacing element
                        const clone = input.cloneNode();
                        clone.value = '';
                        input.parentNode.replaceChild(clone, input);
                    } else if (input.type === 'checkbox' || input.type === 'radio') {
                        input.checked = false;
                    } else if (input.tagName === 'SELECT') {
                        input.selectedIndex = 0;
                    } else {
                        input.value = '';
                    }
                    if (input.dataset) delete input.dataset.autofill;
                });

                newRow.querySelectorAll('.existing-file').forEach(el => el.remove());

                container.appendChild(newRow);

                if (container.classList.contains('paper-stock-rows')) {
                    const select = newRow.querySelector('.paper-stock-name-select');
                    if (select) {
                        select.value = '';
                    }
                    const hidden = newRow.querySelector('.paper-stock-material-id');
                    if (hidden) hidden.value = '';
                    syncPaperStockRow(newRow);
                }

                if (container.classList.contains('addon-rows')) {
                    const hidden = newRow.querySelector('.addon-material-id');
                    if (hidden) hidden.value = '';
                    toggleAddonInputs(newRow);
                    syncAddonRow(newRow);
                }
            }

            if (event.target.classList.contains('remove-row')) {
                event.preventDefault();
                const rowClass = container.classList.contains('paper-stock-rows') ? '.paper-stock-row' :
                                container.classList.contains('addon-rows') ? '.addon-row' :
                                container.classList.contains('color-rows') ? '.color-row' :
                                container.classList.contains('bulk-order-rows') ? '.bulk-order-row' : 'div';
                const rows = container.querySelectorAll(rowClass);
                if (rows.length <= 1) return;
                const row = event.target.closest(rowClass);
                if (row) row.remove();
            }
        });
    });

    const Navigation = (() => {
        const pages = Array.from(document.querySelectorAll('.page'));
        const steps = Array.from(document.querySelectorAll('.breadcrumb-step'));
        const progressFill = document.getElementById('progress-fill');
        const pageTitle = document.getElementById('page-title');
        const titles = ['Templates', 'Basic Info', 'Production'];
        let currentIndex = 0;

        function showPage(index) {
            if (!pages.length || index < 0 || index >= pages.length) return;
            pages.forEach((page, idx) => {
                const active = idx === index;
                page.style.display = active ? 'block' : 'none';
                page.setAttribute('aria-hidden', active ? 'false' : 'true');
                page.classList.toggle('active-page', active);
            });
            steps.forEach((step, idx) => step.classList.toggle('active', idx === index));
            if (pageTitle) pageTitle.textContent = titles[index] || pageTitle.textContent;
            if (progressFill) progressFill.style.width = `${((index + 1) / pages.length) * 100}%`;
            currentIndex = index;
        }

        function showNextPage() {
            if (currentIndex < pages.length - 1) {
                showPage(currentIndex + 1);
            }
        }

        steps.forEach((button, idx) => {
            button.addEventListener('click', () => showPage(idx));
        });

        function validatePage(pageIndex) {
            if (pageIndex === 1) {
                const errors = [];
                const invitationName = document.getElementById('invitationName');
                if (!invitationName || !invitationName.value.trim()) {
                    errors.push({ field: 'invitationName', message: 'Invitation Name is required.' });
                }
                const eventType = document.getElementById('eventType');
                if (!eventType || !eventType.value) {
                    errors.push({ field: 'eventType', message: 'Event Type is required.' });
                }
                const productType = document.getElementById('productType');
                if (!productType || !productType.value) {
                    errors.push({ field: 'productType', message: 'Product Type is required.' });
                }
                const themeStyle = document.getElementById('themeStyle');
                if (!themeStyle || !themeStyle.value.trim()) {
                    errors.push({ field: 'themeStyle', message: 'Theme / Style is required.' });
                }

                const errorSummary = document.querySelector('.page2 .error-summary');
                const errorList = document.querySelector('.page2 #error-list-page2');
                if (errors.length > 0) {
                    if (errorList) {
                        errorList.innerHTML = '';
                        errors.forEach(error => {
                            const li = document.createElement('li');
                            li.textContent = error.message;
                            errorList.appendChild(li);
                            const errorSpan = document.getElementById(`${error.field}-error`);
                            if (errorSpan) {
                                errorSpan.textContent = error.message;
                                errorSpan.style.display = 'block';
                            }
                        });
                    }
                    if (errorSummary) errorSummary.style.display = 'block';
                    return false;
                }

                if (errorSummary) errorSummary.style.display = 'none';
                ['invitationName', 'eventType', 'productType', 'themeStyle'].forEach(field => {
                    const errorSpan = document.getElementById(`${field}-error`);
                    if (errorSpan) {
                        errorSpan.textContent = '';
                        errorSpan.style.display = 'none';
                    }
                });
            }
            return true;
        }

        document.querySelectorAll('.continue-btn').forEach(button => {
            if (button.dataset.templateId) return;
            button.addEventListener('click', () => {
                const currentPage = Navigation.currentPage;
                if (validatePage(currentPage)) {
                    showNextPage();
                }
            });
        });

        showPage(0);

        return {
            showPage,
            showNextPage,
            get currentPage() {
                return currentIndex;
            }
        };
    })();

    window.Navigation = Navigation;

    function ensureOption(select, value) {
        if (!select) return;
        if (!value) {
            select.value = '';
            return;
        }
        const match = Array.from(select.options).find(opt => normalize(opt.value) === normalize(value));
        if (match) {
            select.value = match.value;
        } else {
            const option = new Option(value, value, true, true);
            select.add(option);
            select.value = option.value;
        }
    }

    function resolvePreviewPath(path) {
        if (!path) return '';
        if (/^(https?:)?\/\//i.test(path)) return path;
        if (path.startsWith('/')) return path;
        const base = assetBase ? `${assetBase}/storage/` : '/storage/';
        return `${base}${path}`.replace(/\/+/g, '/');
    }

    function applyTemplateToForm(template) {
        if (!template) return;

        const templateField = document.getElementById('template_id');
        if (templateField) templateField.value = template.id || '';

        const nameInput = document.getElementById('invitationName');
        if (nameInput) {
            nameInput.value = template.name || '';
            nameInput.dataset.templateValue = template.name || '';
        }

        const eventSelect = document.getElementById('eventType');
        const eventValue = template.event_type || template.eventType || '';
        if (eventSelect) {
            ensureOption(eventSelect, eventValue);
            eventSelect.dataset.templateValue = eventValue;
        }

        const productSelect = document.getElementById('productType');
        const productValue = template.product_type || template.productType || '';
        if (productSelect) {
            ensureOption(productSelect, productValue);
            productSelect.dataset.templateValue = productValue;
        }

        const themeStyleInput = document.getElementById('themeStyle');
        const themeValue = template.theme_style || template.themeStyle || '';
        if (themeStyleInput) {
            themeStyleInput.value = themeValue;
            themeStyleInput.dataset.templateValue = themeValue;
        }

        const descriptionTextarea = document.getElementById('description');
        const descriptionEditor = document.getElementById('description-editor');
        const description = template.description || '';
        if (descriptionTextarea) descriptionTextarea.value = description;
        if (descriptionEditor) descriptionEditor.innerHTML = description;

        const previewImg = document.getElementById('template-preview-img');
        if (previewImg) {
            const previewCandidates = [
                template.preview_url,
                template.preview,
                template.preview_image,
                template.image_url,
                template.image
            ];
            const previewPath = previewCandidates.find(Boolean) || '';
            const resolved = previewPath ? resolvePreviewPath(previewPath) : '';
            if (resolved) {
                previewImg.src = resolved;
                previewImg.style.display = 'block';
                previewImg.alt = `${template.name || 'Selected'} Preview`;
            } else {
                previewImg.src = '';
                previewImg.style.display = 'none';
                previewImg.alt = 'No preview available';
            }
        }
    }

    document.querySelectorAll('.continue-btn[data-template-id]').forEach(button => {
        button.addEventListener('click', () => {
            const templateId = button.dataset.templateId;
            const template = templates.find(t => String(t.id) === String(templateId));
            if (template) {
                applyTemplateToForm(template);
            }
            Navigation.showPage(1);
            const nameInput = document.getElementById('invitationName');
            if (nameInput) nameInput.focus();
        });
    });

    const editorToolbar = document.querySelector('.editor-toolbar');
    if (editorToolbar) {
        editorToolbar.addEventListener('click', event => {
            const button = event.target.closest('.editor-btn');
            if (!button) return;
            event.preventDefault();
            const command = button.dataset.command;
            if (command) {
                document.execCommand(command, false, null);
                const descriptionEditor = document.getElementById('description-editor');
                const descriptionTextarea = document.getElementById('description');
                if (descriptionEditor && descriptionTextarea) {
                    descriptionTextarea.value = descriptionEditor.innerHTML;
                }
            }
        });
    }

    const descriptionEditor = document.getElementById('description-editor');
    if (descriptionEditor) {
        descriptionEditor.addEventListener('input', () => {
            const descriptionTextarea = document.getElementById('description');
            if (descriptionTextarea) {
                descriptionTextarea.value = descriptionEditor.innerHTML;
            }
        });
    }

    const templateField = document.getElementById('template_id');
    const presetTemplateId = templateField ? templateField.value : '';
    if (presetTemplateId) {
        const presetTemplate = templates.find(t => String(t.id) === String(presetTemplateId));
        if (presetTemplate) {
            applyTemplateToForm(presetTemplate);
        }
    }

    function bestPaperMaterialMatch(row) {
        if (!row) return null;
        const hidden = row.querySelector('.paper-stock-material-id');
        if (hidden && hidden.value) {
            const material = findMaterialById(hidden.value);
            if (material) return material;
        }
        const select = row.querySelector('.paper-stock-name-select');
        if (select) {
            if (select.value) {
                const byName = findMaterialByName(select.value);
                if (byName) return byName;
            }
            const fallbackName = select.dataset.selectedName || select.getAttribute('data-selected-name');
            if (fallbackName) {
                const byFallback = findMaterialByName(fallbackName);
                if (byFallback) return byFallback;
            }
            const selectedOption = select.selectedOptions[0];
            if (selectedOption && selectedOption.dataset.materialId) {
                const byOptionId = findMaterialById(selectedOption.dataset.materialId);
                if (byOptionId) return byOptionId;
            }
        }
        return null;
    }

    function syncPaperStockRow(row) {
        if (!row) return;
        const select = row.querySelector('.paper-stock-name-select');
        const hidden = row.querySelector('.paper-stock-material-id');
        const priceInput = row.querySelector('input[name$="[price]"]');

        if (!select || !hidden) return;
        const selectedOption = select.selectedOptions[0];
        const materialId = selectedOption ? (selectedOption.dataset.materialId || selectedOption.value) : '';
        let material = materialId ? findMaterialById(materialId) : null;
        if (!material) material = bestPaperMaterialMatch(row);

        if (material) {
            hidden.value = material.id || '';
            if (!select.value) {
                const match = Array.from(select.options).find(opt => normalize(opt.dataset.materialId) === normalize(material.id));
                if (match) select.value = match.value;
            }
            if (priceInput && (!priceInput.value || priceInput.dataset.autofill === 'true')) {
                if (material.unitCost != null) {
                    priceInput.value = material.unitCost;
                    priceInput.dataset.autofill = 'true';
                }
            }
        } else if (!selectedOption || !selectedOption.value) {
            hidden.value = '';
        }
    }

    function resolveAddonMaterial(row) {
        if (!row) return null;
        const hidden = row.querySelector('.addon-material-id');
        if (hidden && hidden.value) {
            const byId = findMaterialById(hidden.value);
            if (byId) return byId;
        }
        const select = row.querySelector('.addon-name-select');
        if (select) {
            const selectedOption = select.selectedOptions[0];
            if (selectedOption) {
                const byId = findMaterialById(selectedOption.value);
                if (byId) return byId;
                const byName = findMaterialByName(selectedOption.dataset.name || selectedOption.textContent);
                if (byName) return byName;
            }
        }
        const input = row.querySelector('.addon-name-input');
        if (input && input.value) {
            const byName = findMaterialByName(input.value);
            if (byName) return byName;
        }
        return null;
    }

    function syncAddonRow(row) {
        if (!row) return;
        const select = row.querySelector('.addon-name-select');
        const hidden = row.querySelector('.addon-material-id');
        const textInput = row.querySelector('.addon-name-input');
        const priceInput = row.querySelector('input[name$="[price]"]');

        const material = resolveAddonMaterial(row);
        if (material) {
            if (hidden) hidden.value = material.id || '';
            if (select) {
                const matchingOption = Array.from(select.options).find(opt => normalize(opt.value) === normalize(material.id) || normalize(opt.value) === normalize(material.name));
                if (matchingOption) {
                    select.value = matchingOption.value;
                }
            }
            if (textInput && !textInput.value) {
                textInput.value = material.name || '';
            }
            if (priceInput && (!priceInput.value || priceInput.dataset.autofill === 'true')) {
                if (material.unitCost != null) {
                    priceInput.value = material.unitCost;
                    priceInput.dataset.autofill = 'true';
                }
            }
        } else {
            if (hidden) hidden.value = '';
        }
    }

    function toggleAddonInputs(row) {
        if (!row) return;
        const typeSelect = row.querySelector('select[name$="[addon_type]"]');
        const select = row.querySelector('.addon-name-select');
        const input = row.querySelector('.addon-name-input');
        const type = typeSelect ? typeSelect.value.toLowerCase() : '';
        const shouldUseSelect = type === 'embossed';

        if (select) {
            select.style.display = shouldUseSelect ? '' : 'none';
            select.disabled = !shouldUseSelect;
        }
        if (input) {
            input.style.display = shouldUseSelect ? 'none' : '';
        }

        if (shouldUseSelect) {
            syncAddonRow(row);
        }
    }

    document.querySelectorAll('.paper-stock-row').forEach(syncPaperStockRow);
    document.querySelectorAll('.addon-row').forEach(row => {
        toggleAddonInputs(row);
        syncAddonRow(row);
    });

    document.addEventListener('change', event => {
        if (event.target.matches('.paper-stock-name-select')) {
            const select = event.target;
            const row = select.closest('.paper-stock-row');
            if (!row) return;

            const hidden = row.querySelector('.paper-stock-material-id');
            const priceInput = row.querySelector('input[name$="[price]"]');
            const selectedOption = select.selectedOptions[0];
            const materialId = selectedOption ? (selectedOption.dataset.materialId || selectedOption.value) : '';

            if (hidden) hidden.value = materialId || '';

            if (priceInput) {
                const material = findMaterialById(materialId) || findMaterialByName(select.value);
                if (material && material.unitCost != null && (!priceInput.value || priceInput.dataset.autofill === 'true')) {
                    priceInput.value = material.unitCost;
                    priceInput.dataset.autofill = 'true';
                } else if (!material) {
                    priceInput.dataset.autofill = 'false';
                }
            }
        }

        if (event.target.matches('.addon-name-select')) {
            const select = event.target;
            const row = select.closest('.addon-row');
            if (!row) return;

            const hidden = row.querySelector('.addon-material-id');
            const textInput = row.querySelector('.addon-name-input');
            const priceInput = row.querySelector('input[name$="[price]"]');
            const selectedOption = select.selectedOptions[0];
            const materialId = selectedOption ? selectedOption.value : '';
            const materialName = selectedOption ? (selectedOption.dataset.name || selectedOption.textContent.trim()) : '';

            if (hidden) hidden.value = materialId || '';
            if (textInput && materialName) textInput.value = materialName;

            if (priceInput) {
                const material = findMaterialById(materialId);
                if (material && material.unitCost != null && (!priceInput.value || priceInput.dataset.autofill === 'true')) {
                    priceInput.value = material.unitCost;
                    priceInput.dataset.autofill = 'true';
                }
            }
        }

        if (event.target.matches('select[name$="[addon_type]"]')) {
            const select = event.target;
            const row = select.closest('.addon-row');
            toggleAddonInputs(row);
            syncAddonRow(row);
        }
    });

    document.addEventListener('input', event => {
        if (event.target.matches('.addon-name-input')) {
            const input = event.target;
            const row = input.closest('.addon-row');
            if (!row) return;

            const hidden = row.querySelector('.addon-material-id');
            const priceInput = row.querySelector('input[name$="[price]"]');
            const material = findMaterialByName(input.value);

            if (material) {
                if (hidden) hidden.value = material.id;
                if (priceInput && (!priceInput.value || priceInput.dataset.autofill === 'true')) {
                    if (material.unitCost != null) {
                        priceInput.value = material.unitCost;
                        priceInput.dataset.autofill = 'true';
                    }
                }
            } else {
                if (hidden) hidden.value = '';
                if (priceInput) priceInput.dataset.autofill = 'false';
            }
        }

        if (event.target.matches('.paper-stock-row input[name$="[price]"]') || event.target.matches('.addon-row input[name$="[price]"]')) {
            event.target.dataset.autofill = 'false';
        }
    });
});