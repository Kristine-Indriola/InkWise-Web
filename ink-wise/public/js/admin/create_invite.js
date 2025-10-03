document.addEventListener('DOMContentLoaded', () => {
    const templates = Array.isArray(window.templatesData) ? window.templatesData : [];
    const materials = Array.isArray(window.materialsData) ? window.materialsData : [];
    const assetBase = ((window.assetUrl || '').replace(/\/$/, '')) || '';

    function normalize(value) {
        return value === undefined || value === null ? '' : String(value).trim().toLowerCase();
    }

    function uniqueMaterialTypes() {
        const seen = new Set();
        materials.forEach(material => {
            if (!material || !material.material_type) return;
            const type = material.material_type.toString().trim();
            if (!type) return;
            seen.add(type);
        });
        return Array.from(seen).sort((a, b) => a.localeCompare(b));
    }

    function materialOptionsForType(type) {
        const normalized = normalize(type);
        return materials
            .filter(material => {
                if (!material) return false;
                if (!material.material_name) return false;
                if (!normalized) return true;
                return normalize(material.material_type) === normalized;
            })
            .map(material => material.material_name.toString().trim())
            .filter(Boolean)
            .sort((a, b) => a.localeCompare(b));
    }

    function findMaterialRecord(type, name) {
        const typeNorm = normalize(type);
        const nameNorm = normalize(name);
        return materials.find(material => {
            if (!material) return false;
            const matchesType = typeNorm ? normalize(material.material_type) === typeNorm : true;
            const matchesName = nameNorm ? normalize(material.material_name) === nameNorm : true;
            return matchesType && matchesName;
        }) || null;
    }

    function populateMaterialTypeSelect(row) {
        const select = row.querySelector('select[name*="[type]"]');
        if (!select) return;
        const current = select.getAttribute('data-current') || select.value || '';
        const placeholder = select.getAttribute('data-placeholder') || 'Select material type';
        const options = uniqueMaterialTypes();

        select.innerHTML = '';
        const placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = placeholder;
        placeholderOption.disabled = true;
        select.appendChild(placeholderOption);

        options.forEach(value => {
            const opt = document.createElement('option');
            opt.value = value;
            opt.textContent = value;
            select.appendChild(opt);
        });

        if (current) {
            const match = Array.from(select.options).find(opt => normalize(opt.value) === normalize(current));
            if (match) {
                select.value = match.value;
            } else {
                const dynamicOption = new Option(current, current, true, true);
                select.add(dynamicOption);
                select.value = dynamicOption.value;
            }
        } else {
            select.value = '';
            placeholderOption.selected = true;
        }

        select.dataset.current = '';
    }

    function populateMaterialNameSelect(row) {
        const nameSelect = row.querySelector('select[name*="[item]"]');
        const typeSelect = row.querySelector('select[name*="[type]"]');
        if (!nameSelect) return;
        const current = nameSelect.getAttribute('data-current') || nameSelect.value || '';
        const placeholder = nameSelect.getAttribute('data-placeholder') || 'Select material';
        const options = materialOptionsForType(typeSelect ? typeSelect.value : '');

        nameSelect.innerHTML = '';
        const placeholderOption = document.createElement('option');
        placeholderOption.value = '';
        placeholderOption.textContent = placeholder;
        placeholderOption.disabled = true;
        nameSelect.appendChild(placeholderOption);

        options.forEach(value => {
            const opt = document.createElement('option');
            opt.value = value;
            opt.textContent = value;
            nameSelect.appendChild(opt);
        });

        if (current) {
            const match = Array.from(nameSelect.options).find(opt => normalize(opt.value) === normalize(current));
            if (match) {
                nameSelect.value = match.value;
            } else {
                const dynamicOption = new Option(current, current, true, true);
                nameSelect.add(dynamicOption);
                nameSelect.value = dynamicOption.value;
            }
        } else {
            nameSelect.value = '';
            placeholderOption.selected = true;
        }

        nameSelect.dataset.current = '';
    }

    function applyMaterialRecord(row, record) {
        const fieldMap = {
            color: record ? record.color : '',
            size: record ? record.size : '',
            weight: record ? record.weight_gsm : '',
            unit: record ? record.unit : '',
            unitPrice: record && record.unit_cost !== undefined ? record.unit_cost : ''
        };

        Object.entries(fieldMap).forEach(([key, value]) => {
            const input = row.querySelector(`input[name*="[${key}]"]`);
            if (!input) return;
            if (value === undefined || value === null) {
                input.value = '';
            } else if (typeof value === 'number') {
                input.value = value.toString();
            } else {
                input.value = value;
            }
        });

        const idInput = row.querySelector('input[name*="[id]"]');
        if (idInput) {
            idInput.value = record && record.material_id !== undefined ? record.material_id : '';
        }
    }

    function refreshMaterialRow(row) {
        populateMaterialTypeSelect(row);
        populateMaterialNameSelect(row);
        const typeSelect = row.querySelector('select[name*="[type]"]');
        const nameSelect = row.querySelector('select[name*="[item]"]');
        const record = findMaterialRecord(typeSelect ? typeSelect.value : '', nameSelect ? nameSelect.value : '');
        applyMaterialRecord(row, record);
    }

    function attachMaterialListeners(row) {
        const typeSelect = row.querySelector('select[name*="[type]"]');
        const nameSelect = row.querySelector('select[name*="[item]"]');

        if (typeSelect) {
            typeSelect.addEventListener('change', () => {
                populateMaterialNameSelect(row);
                const record = findMaterialRecord(typeSelect.value, row.querySelector('select[name*="[item]"]')?.value || '');
                applyMaterialRecord(row, record);
            });
        }

        if (nameSelect) {
            nameSelect.addEventListener('change', () => {
                const record = findMaterialRecord(row.querySelector('select[name*="[type]"]')?.value || '', nameSelect.value);
                applyMaterialRecord(row, record);
            });
        }
    }

    document.querySelectorAll('.material-row').forEach(row => {
        refreshMaterialRow(row);
        attachMaterialListeners(row);
    });

    document.querySelectorAll('.material-rows').forEach(container => {
        container.addEventListener('click', event => {
            if (event.target.classList.contains('add-row')) {
                event.preventDefault();
                const rows = container.querySelectorAll('.material-row');
                if (!rows.length) return;
                const lastRow = rows[rows.length - 1];
                const newRow = lastRow.cloneNode(true);
                const newIndex = rows.length;

                newRow.querySelectorAll('[name]').forEach(element => {
                    element.name = element.name.replace(/\[\d+\]/, `[${newIndex}]`);
                });

                newRow.querySelectorAll('[id]').forEach(element => {
                    element.id = element.id.replace(/_(\d+)(?=_[^_]+$)/, `_${newIndex}`);
                    element.id = element.id.replace(/_(\d+)$/, `_${newIndex}`);
                });

                newRow.querySelectorAll('input').forEach(input => {
                    if (input.type === 'hidden') {
                        input.value = '';
                    } else {
                        input.value = '';
                    }
                    if (input.hasAttribute('data-current')) {
                        input.setAttribute('data-current', '');
                    }
                });

                newRow.querySelectorAll('select').forEach(select => {
                    select.value = '';
                    if (select.hasAttribute('data-current')) {
                        select.setAttribute('data-current', '');
                    }
                });

                container.appendChild(newRow);
                refreshMaterialRow(newRow);
                attachMaterialListeners(newRow);
            }

            if (event.target.classList.contains('remove-row')) {
                event.preventDefault();
                const rows = container.querySelectorAll('.material-row');
                if (rows.length <= 1) return;
                const row = event.target.closest('.material-row');
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
        if (pageIndex === 1) { // Page 2 validation
            const errors = [];
            const invitationName = document.getElementById('invitationName');
            if (!invitationName || !invitationName.value.trim()) {
                errors.push({field: 'invitationName', message: 'Invitation Name is required.'});
            }
            const eventType = document.getElementById('eventType');
            if (!eventType || !eventType.value) {
                errors.push({field: 'eventType', message: 'Event Type is required.'});
            }
            const productType = document.getElementById('productType');
            if (!productType || !productType.value) {
                errors.push({field: 'productType', message: 'Product Type is required.'});
            }
            const themeStyle = document.getElementById('themeStyle');
            if (!themeStyle || !themeStyle.value.trim()) {
                errors.push({field: 'themeStyle', message: 'Theme / Style is required.'});
            }
            // Show errors
            const errorSummary = document.querySelector('.page2 .error-summary');
            const errorList = document.querySelector('.page2 #error-list-page2');
            if (errors.length > 0) {
                errorList.innerHTML = '';
                errors.forEach(error => {
                    const li = document.createElement('li');
                    li.textContent = error.message;
                    errorList.appendChild(li);
                    const errorSpan = document.getElementById(error.field + '-error');
                    if (errorSpan) {
                        errorSpan.textContent = error.message;
                        errorSpan.style.display = 'block';
                    }
                });
                errorSummary.style.display = 'block';
                return false;
            } else {
                errorSummary.style.display = 'none';
                ['invitationName', 'eventType', 'productType', 'themeStyle'].forEach(field => {
                    const errorSpan = document.getElementById(field + '-error');
                    if (errorSpan) {
                        errorSpan.textContent = '';
                        errorSpan.style.display = 'none';
                    }
                });
                return true;
            }
        }
        return true;
    }

    document.querySelectorAll('.continue-btn').forEach(button => {
        if (button.dataset.templateId) return;
        button.addEventListener('click', (e) => {
            const currentPage = Navigation.currentPage;
            if (validatePage(currentPage)) {
                Navigation.showNextPage();
            }
        });
    });        showPage(0);

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

    document.querySelectorAll('.continue-btn[data-template-id]').forEach(button => {
        button.addEventListener('click', () => {
            const templateId = button.dataset.templateId;
            const template = templates.find(t => String(t.id) === String(templateId));
            if (!template) {
                Navigation.showPage(1);
                return;
            }

            const templateField = document.getElementById('template_id');
            if (templateField) templateField.value = template.id;

            const nameInput = document.getElementById('invitationName');
            if (nameInput) nameInput.value = template.name || '';

            ensureOption(document.getElementById('eventType'), template.event_type || template.eventType || '');
            ensureOption(document.getElementById('productType'), template.product_type || template.productType || '');

            const themeStyleInput = document.getElementById('themeStyle');
            if (themeStyleInput) themeStyleInput.value = template.theme_style || template.themeStyle || '';

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

            Navigation.showPage(1);
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
});