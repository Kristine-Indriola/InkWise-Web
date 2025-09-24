document.addEventListener("DOMContentLoaded", () => {
    // Initialize all modules
    Calculations.init();
    Validation.init();
    Navigation.init();
    DynamicRows.init();
    Editor.init();
    FileUpload.init();
    Accessibility.init();
    Performance.init();

    // Global data
    window.templatesData = window.templatesData || [];
    window.materialsData = window.materialsData || [];

    // Template selection and autofill
    document.querySelectorAll('.continue-btn[data-template-id]').forEach(btn => {
        btn.addEventListener('click', function() {
            const templateId = this.getAttribute('data-template-id');
            const template = window.templatesData.find(t => t.id == templateId);
            if (template) {
                document.getElementById('template_id').value = template.id;
                document.getElementById('invitationName').value = template.name || '';
                document.getElementById('eventType').value = template.event_type || '';
                document.getElementById('productType').value = template.product_type || '';
                document.getElementById('themeStyle').value = template.theme_style || '';
                document.getElementById('description').value = template.description || '';
                document.getElementById('description-editor').innerHTML = template.description || '';
                // Set preview image from template's preview_image column
                const imagePath = template.preview_image || template.image || '';
                document.getElementById('template-preview-img').src = imagePath ? window.assetUrl + imagePath : '';
            }
            Navigation.showPage(1);
        });
    });

    // Materials autofill
    const itemInput = document.getElementById('materials_0_item');
    const typeInput = document.getElementById('materials_0_type');
    const colorInput = document.getElementById('materials_0_color');
    const weightInput = document.getElementById('materials_0_weight');
    const unitPriceInput = document.getElementById('materials_0_unitPrice');
    const qtyInput = document.getElementById('materials_0_qty');
    const costInput = document.getElementById('materials_0_cost');

    let dropdown = document.createElement('div');
    dropdown.className = 'material-dropdown';
    dropdown.style.position = 'absolute';
    dropdown.style.background = '#fff';
    dropdown.style.border = '1px solid #ccc';
    dropdown.style.zIndex = '1000';
    dropdown.style.display = 'none';
    dropdown.style.maxHeight = '200px';
    dropdown.style.overflowY = 'auto';
    itemInput.parentNode.appendChild(dropdown);

    function fillMaterialFields(material) {
        itemInput.value = material.material_name || '';
        typeInput.value = material.material_type || '';
        colorInput.value = material.color || '';
        weightInput.value = material.weight_gsm || '';
        unitPriceInput.value = material.unit_cost || '';
        const qty = parseInt(qtyInput.value, 10) || 0;
        costInput.value = qty > 0 ? (material.unit_cost * qty).toFixed(2) : '';
    }

    function showDropdown(matches) {
        dropdown.innerHTML = '';
        if (matches.length === 0) {
            dropdown.style.display = 'none';
            return;
        }
        matches.forEach(material => {
            const option = document.createElement('div');
            option.className = 'material-option';
            option.style.padding = '4px 8px';
            option.style.cursor = 'pointer';
            option.textContent = `${material.material_name} (${material.material_type})`;
            option.onclick = () => {
                fillMaterialFields(material);
                dropdown.style.display = 'none';
            };
            dropdown.appendChild(option);
        });
        dropdown.style.display = 'block';
        dropdown.style.left = '0px';
        dropdown.style.top = itemInput.offsetHeight + 'px';
        dropdown.style.width = itemInput.offsetWidth + 'px';
    }

    function updateMaterialFields() {
        const item = itemInput.value.trim().toLowerCase();
        const type = typeInput.value.trim().toLowerCase();
        const matches = window.materialsData.filter(m =>
            m.material_name && m.material_name.toLowerCase().includes(item) &&
            m.material_type && m.material_type.toLowerCase().includes(type)
        );
        if (matches.length === 1) {
            fillMaterialFields(matches[0]);
            dropdown.style.display = 'none';
        } else if (matches.length > 1) {
            showDropdown(matches);
        } else {
            colorInput.value = '';
            weightInput.value = '';
            unitPriceInput.value = '';
            costInput.value = '';
            dropdown.style.display = 'none';
        }
    }

    itemInput.addEventListener('input', updateMaterialFields);
    typeInput.addEventListener('input', updateMaterialFields);
    qtyInput.addEventListener('input', updateMaterialFields);

    document.addEventListener('click', e => {
        if (!dropdown.contains(e.target) && e.target !== itemInput) {
            dropdown.style.display = 'none';
        }
    });

    // Add function to update total raw cost
    function updateTotalRawCost() {
        let total = 0;
        document.querySelectorAll('input[name*="materials"][name*="cost"]').forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        document.getElementById('totalRawCost').value = total.toFixed(2);
        Calculations.debounceCalculate(); // Trigger other calculations
    }

    // For inks
    const inkItemInput = document.getElementById('inks_0_item');
    const inkTypeInput = document.getElementById('inks_0_type');
    const inkUsageInput = document.getElementById('inks_0_usage');
    const inkCostPerMlInput = document.getElementById('inks_0_costPerMl');
    const inkTotalCostInput = document.getElementById('inks_0_totalCost');

    function updateInkFields() {
        const usage = parseFloat(inkUsageInput.value) || 0;
        const costPerMl = parseFloat(inkCostPerMlInput.value) || 0;
        const totalCost = usage * costPerMl;
        inkTotalCostInput.value = totalCost.toFixed(2);
        updateTotalRawCost(); // Assuming inks contribute to total raw cost
    }

    // Add event listeners for inks
    inkUsageInput.addEventListener('input', updateInkFields);
    inkCostPerMlInput.addEventListener('input', updateInkFields);

    // Autofill for inks if data available, but since inks are select, perhaps on change
    inkItemInput.addEventListener('change', () => {
        // Assuming window.inksData or similar, but not defined, so skip or add if needed
    });

    // Add function to add listeners for a material row
    function addRowListeners(row) {
        // Existing for materials
        const itemInput = row.querySelector('input[name*="materials"][name*="item"]');
        const typeInput = row.querySelector('input[name*="materials"][name*="type"]');
        const qtyInput = row.querySelector('input[name*="materials"][name*="qty"]');
        const unitPriceInput = row.querySelector('input[name*="materials"][name*="unitPrice"]');
        const costInput = row.querySelector('input[name*="materials"][name*="cost"]');

        if (itemInput && typeInput && qtyInput && unitPriceInput && costInput) {
            // Materials logic
            function updateMaterialFields() {
                const item = itemInput.value.trim().toLowerCase();
                const type = typeInput.value.trim().toLowerCase();
                const qty = parseInt(qtyInput.value, 10) || 0;
                const matches = window.materialsData.filter(m =>
                    m.material_name && m.material_name.toLowerCase().includes(item) &&
                    m.material_type && m.material_type.toLowerCase().includes(type)
                );
                if (matches.length === 1 && !unitPriceInput.value) {
                    unitPriceInput.value = matches[0].unit_cost || '';
                }
                costInput.value = qty > 0 && unitPriceInput.value ? (parseFloat(unitPriceInput.value) * qty).toFixed(2) : '';
                updateTotalRawCost();
            }

            itemInput.addEventListener('input', updateMaterialFields);
            typeInput.addEventListener('input', updateMaterialFields);
            qtyInput.addEventListener('input', updateMaterialFields);
            unitPriceInput.addEventListener('input', () => {
                const qty = parseInt(qtyInput.value, 10) || 0;
                costInput.value = qty > 0 && unitPriceInput.value ? (parseFloat(unitPriceInput.value) * qty).toFixed(2) : '';
                updateTotalRawCost();
            });
        }

        // For inks
        const inkItemInput = row.querySelector('select[name*="inks"][name*="item"]');
        const inkUsageInput = row.querySelector('input[name*="inks"][name*="usage"]');
        const inkCostPerMlInput = row.querySelector('input[name*="inks"][name*="costPerMl"]');
        const inkTotalCostInput = row.querySelector('input[name*="inks"][name*="totalCost"]');

        if (inkUsageInput && inkCostPerMlInput && inkTotalCostInput) {
            function updateInkFields() {
                const usage = parseFloat(inkUsageInput.value) || 0;
                const costPerMl = parseFloat(inkCostPerMlInput.value) || 0;
                const totalCost = usage * costPerMl;
                inkTotalCostInput.value = totalCost.toFixed(2);
                updateTotalRawCost();
            }

            inkUsageInput.addEventListener('input', updateInkFields);
            inkCostPerMlInput.addEventListener('input', updateInkFields);
        }
    }

    // In the initial setup, add listeners to the first row
    const firstMaterialRow = document.querySelector('.material-row');
    if (firstMaterialRow) {
        addRowListeners(firstMaterialRow);
    }

    document.getElementById('invitation-form').addEventListener('submit', function() {
        const btn = document.getElementById('submit-btn');
        btn.disabled = true;
        document.querySelector('#submit-btn .btn-text').style.display = 'none';
        document.querySelector('#submit-btn .loading-spinner').style.display = 'inline-block';
    });

    document.getElementById('invitation-form').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        const formData = new FormData(this);
        const submitBtn = document.getElementById('submit-btn');
        const btnText = submitBtn.querySelector('.btn-text');
        const loadingSpinner = submitBtn.querySelector('.loading-spinner');

        // Show loading state
        btnText.style.display = 'none';
        loadingSpinner.style.display = 'inline';
        submitBtn.disabled = true;
        submitBtn.setAttribute('aria-busy', 'true');

        // Send AJAX request
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to product index
                window.location.href = '{{ route("admin.products.index") }}';
            } else {
                // Handle errors (e.g., show validation errors)
                console.error('Errors:', data.errors);
                // You can display errors in the UI here
            }
        })
        .catch(error => {
            console.error('Error:', error);
        })
        .finally(() => {
            // Reset button state
            btnText.style.display = 'inline';
            loadingSpinner.style.display = 'none';
            submitBtn.disabled = false;
            submitBtn.removeAttribute('aria-busy');
        });
    });

    // Attach event listener to the container, not individual inputs
    document.querySelector('.material-rows').addEventListener('input', function(e) {
        if (e.target.matches('input[name^="materials"]')) {
            // Find the row
            const row = e.target.closest('.material-row');
            // Get values
            const unitPrice = parseFloat(row.querySelector('input[name$="[unitPrice]"]').value) || 0;
            const qty = parseFloat(row.querySelector('input[name$="[qty]"]').value) || 0;
            // Calculate cost
            const cost = unitPrice * qty;
            row.querySelector('input[name$="[cost]"]').value = cost.toFixed(2);
        }
    });

    // Event delegation for ink total cost calculation
    document.querySelector('.ink-rows').addEventListener('input', function(e) {
        if (
            e.target.matches('input[name^="inks"][name$="[usage]"]') ||
            e.target.matches('input[name^="inks"][name$="[costPerMl]"]')
        ) {
            const row = e.target.closest('.ink-row');
            if (!row) return;
            const usage = parseFloat(row.querySelector('input[name$="[usage]"]').value) || 0;
            const costPerMl = parseFloat(row.querySelector('input[name$="[costPerMl]"]').value) || 0;
            const totalCost = usage * costPerMl;
            row.querySelector('input[name$="[totalCost]"]').value = totalCost.toFixed(2);
        }
    });

    // Add new ink row dynamically
    document.querySelector('.ink-rows').addEventListener('click', function(e) {
        if (e.target.classList.contains('add-row')) {
            e.preventDefault();
            const inkRows = document.querySelector('.ink-rows');
            const lastRow = inkRows.querySelector('.ink-row:last-child');
            const newIndex = inkRows.querySelectorAll('.ink-row').length;
            const newRow = lastRow.cloneNode(true);

            // Update input names and ids for the new row
            newRow.querySelectorAll('input, select').forEach(input => {
                if (input.name) {
                    input.name = input.name.replace(/\[\d+\]/, `[${newIndex}]`);
                }
                if (input.id) {
                    input.id = input.id.replace(/_\d+_/, `_${newIndex}_`);
                }
                if (input.type === 'number' || input.tagName === 'SELECT' || input.tagName === 'INPUT') {
                    input.value = '';
                }
            });

            inkRows.appendChild(newRow);
        }
    });

    // For material rows
    document.querySelector('.material-rows').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            const rows = this.querySelectorAll('.material-row');
            if (rows.length > 1) {
                e.target.closest('.material-row').remove();
            }
        }
    });

    // For ink rows
    document.querySelector('.ink-rows').addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-row')) {
            const rows = this.querySelectorAll('.ink-row');
            if (rows.length > 1) {
                e.target.closest('.ink-row').remove();
            }
        }
    });

    // Set preview image when a template is selected
    document.querySelectorAll('.continue-btn[data-template-id]').forEach(btn => {
        btn.addEventListener('click', function() {
            const templateId = this.getAttribute('data-template-id');
            const template = window.templatesData.find(t => t.id == templateId);
            const previewImg = document.getElementById('template-preview-img');
            if (previewImg) {
                if (template && template.preview) {
                    previewImg.src = window.assetUrl + 'storage/' + template.preview;
                    previewImg.alt = template.name + ' Preview';
                } else {
                    previewImg.src = '';
                    previewImg.alt = 'No preview available';
                }
            }
        });
    });
});

// Module: Calculations
const Calculations = {
    lastValues: { quantity: null, markup: null, totalRawCost: null },
    calculateAll() {
        const quantity = parseFloat(document.getElementById("quantityOrdered").value) || 0;
        const markup = parseFloat(document.getElementById("markup").value) || 0;
        const totalRawCost = parseFloat(document.getElementById("totalRawCost").value) || 0;
        if (this.lastValues.quantity === quantity && this.lastValues.markup === markup && this.lastValues.totalRawCost === totalRawCost) return;
        this.lastValues = { quantity, markup, totalRawCost };
        if (quantity === 0) {
            document.getElementById("costPerInvite").value = '0.00';
            document.getElementById("sellingPrice").value = '0.00';
            document.getElementById("totalSellingPrice").value = '0.00';
            return;
        }
        const costPerInvite = totalRawCost / quantity;
        const sellingPrice = costPerInvite * (1 + markup / 100);
        const totalSellingPrice = sellingPrice * quantity;
        document.getElementById("costPerInvite").value = costPerInvite.toFixed(2);
        document.getElementById("sellingPrice").value = sellingPrice.toFixed(2);
        document.getElementById("totalSellingPrice").value = totalSellingPrice.toFixed(2);
    },
    debounceCalculate() {
        clearTimeout(this.calculationTimeout);
        this.calculationTimeout = setTimeout(this.calculateAll.bind(this), 300);
    },
    calculationTimeout: null,
    init() {
        document.getElementById("quantityOrdered").addEventListener("input", () => this.debounceCalculate());
        document.getElementById("markup").addEventListener("change", () => this.debounceCalculate());
        document.getElementById("totalRawCost").addEventListener("input", () => this.debounceCalculate());
        this.calculateAll();
    }
};

// Module: Validation
const Validation = {
    validatePage(page) {
        let isValid = true;
        const requiredFields = document.querySelectorAll(`.page${page} [required]`);
        const errorList = document.getElementById(`error-list-page${page}`);
        if (errorList) errorList.innerHTML = '';
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                this.showError(field, 'This field is required.');
                if (errorList) {
                    const li = document.createElement('li');
                    li.textContent = `${field.previousElementSibling.textContent} is required.`;
                    errorList.appendChild(li);
                }
            } else {
                this.clearError(field);
            }
        });
        const errorSummary = document.querySelector(`.page${page} .error-summary`);
        if (errorSummary) errorSummary.style.display = isValid ? 'none' : 'block';
        return isValid;
    },
    showError(field, message) {
        field.classList.add('error');
        const errorSpan = document.getElementById(field.id + '-error');
        if (errorSpan) {
            errorSpan.textContent = message;
            errorSpan.style.display = 'block';
        }
    },
    clearError(field) {
        field.classList.remove('error');
        const errorSpan = document.getElementById(field.id + '-error');
        if (errorSpan) errorSpan.style.display = 'none';
    },
    sanitizeInput(input) {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = input;
        const scripts = tempDiv.querySelectorAll('script');
        scripts.forEach(script => script.remove());
        const allowedTags = ['b', 'i', 'u', 'br', 'p', 'div'];
        const allElements = tempDiv.querySelectorAll('*');
        allElements.forEach(el => {
            if (!allowedTags.includes(el.tagName.toLowerCase())) el.remove();
        });
        return tempDiv.innerHTML;
    },
    init() {
        // Sync editor
        const editor = document.getElementById('description-editor');
        const textarea = document.getElementById('description');
        if (editor && textarea) {
            editor.addEventListener('input', () => {
                textarea.value = this.sanitizeInput(editor.innerHTML);
            });
        }
    }
};

// Module: Navigation
const Navigation = {
    currentPage: 1,
    totalPages: 4,
    showPage(pageIndex) {
        document.querySelectorAll('.page').forEach((page, idx) => {
            page.style.display = idx === pageIndex ? 'block' : 'none';
        });
        this.currentPage = pageIndex + 1;
        const steps = document.querySelectorAll('.breadcrumb-step');
        steps.forEach((step, idx) => {
            step.classList.toggle('active', idx === pageIndex);
        });
        const titles = ['Templates', 'Basic Info', 'Customization', 'Production'];
        const pageTitle = document.getElementById('page-title');
        if (pageTitle) pageTitle.textContent = titles[pageIndex] || '';
        document.getElementById('progress-fill').style.width = ((pageIndex + 1) / this.totalPages * 100) + '%';
    },
    showNextPage() {
        if (this.currentPage < this.totalPages) {
            this.currentPage++;
            this.showPage(this.currentPage - 1);
        }
    },
    init() {
        document.querySelectorAll('.continue-btn').forEach(btn => {
            btn.addEventListener('click', () => this.showNextPage());
        });
        document.querySelectorAll('.breadcrumb-step').forEach((btn, idx) => {
            btn.addEventListener('click', () => this.showPage(idx));
        });
        this.showPage(0);
    }
};

// Module: Dynamic Rows
const DynamicRows = {
    init() {
        document.addEventListener('click', e => {
            if (e.target.classList.contains('add-row')) this.addRow(e.target);
            if (e.target.classList.contains('remove-row')) this.removeRow(e.target);
        });
    },
    addRow(button) {
        const materialRow = button.closest('.material-row');
        const newRow = materialRow.cloneNode(true);
        const rowsContainer = materialRow.parentElement;
        const inputs = newRow.querySelectorAll('input, select');
        const rowIndex = rowsContainer.children.length;
        inputs.forEach(input => {
            const baseId = input.id.replace(/_\d+$/, '');
            input.id = `${baseId}_${rowIndex}`;
            if (input.name) input.name = input.name.replace(/\[\d+\]/, `[${rowIndex}]`);
            input.value = '';
        });
        rowsContainer.appendChild(newRow);
        addRowListeners(newRow); // Add listeners to the new row
        Calculations.debounceCalculate();
    },
    removeRow(button) {
        const materialRow = button.closest('.material-row');
        const rowsContainer = materialRow.parentElement;
        if (rowsContainer.children.length > 1) {
            materialRow.remove();
            Calculations.debounceCalculate();
        }
    }
};

// Module: Editor
const Editor = {
    init() {
        document.addEventListener('click', e => {
            if (e.target.classList.contains('editor-btn')) {
                document.execCommand(e.target.getAttribute('data-command'), false, null);
                document.getElementById('description-editor').focus();
            }
        });
    }
};

// Module: File Upload
const FileUpload = {
    init() {
        const imageInput = document.getElementById('images');
        const customImageInput = document.getElementById('customImage');
        if (imageInput) {
            imageInput.addEventListener('change', e => this.validateFiles(e.target, 5 * 1024 * 1024, ['image/jpeg', 'image/png', 'image/gif']));
        }
        if (customImageInput) {
            customImageInput.addEventListener('change', e => this.validateFiles(e.target, 2 * 1024 * 1024, ['image/jpeg', 'image/png']));
        }
    },
    validateFiles(input, maxSize, allowedTypes) {
        const files = input.files;
        for (let file of files) {
            if (!allowedTypes.includes(file.type)) {
                alert(`Invalid file type: ${file.name}`);
                input.value = '';
                return;
            }
            if (file.size > maxSize) {
                alert(`File too large: ${file.name}`);
                input.value = '';
                return;
            }
        }
    }
};

// Module: Accessibility
const Accessibility = {
    init() {
        document.querySelectorAll('.continue-btn, .btn-save, .add-row, .editor-btn').forEach(btn => {
            btn.setAttribute('aria-label', btn.textContent || btn.getAttribute('data-command'));
            btn.setAttribute('aria-busy', 'true');
        });
    }
};

// Module: Performance
const Performance = {
    init() {
        // Placeholder for optimizations
    }
};