document.addEventListener("DOMContentLoaded", () => {
    const quantityInput = document.getElementById("quantityOrdered");
    const markupSelect = document.getElementById("markup");
    const totalRawCostInput = document.getElementById("totalRawCost");
    const costPerInviteInput = document.getElementById("costPerInvite");
    const sellingPriceInput = document.getElementById("sellingPrice");
    const totalSellingPriceInput = document.getElementById("totalSellingPrice");


    // Improved calculation function with error handling
    function calculateAll() {
        try {
            const quantity = parseFloat(quantityInput.value) || 0;
            const markup = parseFloat(markupSelect.value) || 0;
            const totalRawCost = parseFloat(totalRawCostInput.value) || 0;

            const costPerInvite = totalRawCost / quantity;
            const sellingPrice = costPerInvite * (1 + markup / 100);
            const totalSellingPrice = sellingPrice * quantity;

            costPerInviteInput.value = costPerInvite.toFixed(2);
            sellingPriceInput.value = sellingPrice.toFixed(2);
            totalSellingPriceInput.value = totalSellingPrice.toFixed(2);
        } catch (error) {
            console.error("Calculation error:", error);
            alert("An error occurred during calculation. Please check your inputs.");
        }
    }

    // Event listeners with debouncing for performance
    let calculationTimeout;
    function debounceCalculate() {
        clearTimeout(calculationTimeout);
        calculationTimeout = setTimeout(calculateAll, 300);
    }

    quantityInput.addEventListener("input", debounceCalculate);
    markupSelect.addEventListener("change", debounceCalculate);
    totalRawCostInput.addEventListener("input", debounceCalculate);

    // Add back button functionality
    const backBtn = document.getElementById("backBtn");
    if (backBtn) {
        backBtn.addEventListener("click", () => {
            const currentPage = parseInt(document.querySelector(".page:not([style*='display: none'])").classList[1].slice(-1));
            if (currentPage > 1) {
                showPage(currentPage - 1);
            }
        });
    }

    // Function to show page (assuming it's defined elsewhere or add it)
    function showPage(pageNumber) {
        const pages = document.querySelectorAll(".page");
        pages.forEach((page, index) => {
            page.style.display = index + 1 === pageNumber ? "block" : "none";
        });
    }

    // Initial calculation
    calculateAll();

    // Editor functionality with better handling
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('editor-btn')) {
            const command = e.target.getAttribute('data-command');
            document.execCommand(command, false, null);
            document.getElementById('description').focus();
        }
    });

    // Page navigation with validation
    const pages = document.querySelectorAll('.page');
    let currentPage = 0;

    function validatePage(pageIndex) {
        const page = pages[pageIndex];
        const requiredFields = page.querySelectorAll('input[required], select[required]');
        let valid = true;
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.style.borderColor = '#ff6b6b';
                valid = false;
            } else {
                field.style.borderColor = '#e0e0e0';
            }
        });
        return valid;
    }

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('continue-btn')) {
            if (validatePage(currentPage)) {
                pages[currentPage].style.display = 'none';
                currentPage++;
                if (pages[currentPage]) {
                    pages[currentPage].style.display = 'block';
                }
            } else {
                alert("Please fill in all required fields.");
            }
        }
    });

    // Improved add row functionality
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-row')) {
            const rowsContainer = e.target.closest('.material-rows');
            const row = e.target.closest('.material-row');
            const newRow = row.cloneNode(true);
            // Clear values in new row
            newRow.querySelectorAll('input').forEach(input => input.value = '');
            newRow.querySelectorAll('select').forEach(select => select.selectedIndex = 0);
            rowsContainer.appendChild(newRow);
            // Trigger calculation
            debounceCalculate();
        }
    });

    // Initial setup for pages
    showPage(1);
});
let currentPage = 1;
const totalPages = 3;

document.addEventListener('DOMContentLoaded', function() {
    // Initialize page navigation
    document.querySelectorAll('.continue-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (validatePage(currentPage)) {
                showNextPage();
            }
        });
    });

    document.querySelectorAll('.btn-cancel').forEach(btn => {
        btn.addEventListener('click', function() {
            // Handle cancel, e.g., redirect or reset
            window.location.href = '{{ route("admin.products.index") }}'; // Adjust route as needed
        });
    });

    // Handle form submission on last page
    document.querySelector('.btn-save').addEventListener('click', function(e) {
        if (!validatePage(currentPage)) {
            e.preventDefault();
        }
    });

    // Sync contenteditable to textarea
    document.getElementById('description-editor').addEventListener('input', function() {
        document.getElementById('description').value = this.innerHTML;
    });
});

function validatePage(page) {
    let isValid = true;
    const requiredFields = document.querySelectorAll(`.page${page} [required]`);
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
            // Optionally, show error message
        } else {
            field.classList.remove('error');
        }
    });
    return isValid;
}

function showNextPage() {
    document.querySelector(`.page${currentPage}`).style.display = 'none';
    currentPage++;
    if (currentPage <= totalPages) {
        document.querySelector(`.page${currentPage}`).style.display = 'block';
    }
}

// Function to add dynamic rows for materials and inks
function addRow(button, groupType) {
    const materialRow = button.closest('.material-row');
    const newRow = materialRow.cloneNode(true);
    const rowsContainer = materialRow.parentElement;

    // Update IDs and names for the new row
    const inputs = newRow.querySelectorAll('input, select');
    const rowIndex = rowsContainer.children.length; // Get new index
    inputs.forEach(input => {
        const baseId = input.id.replace(/_\d+$/, ''); // Remove existing index
        input.id = `${baseId}_${rowIndex}`;
        if (input.name) {
            input.name = input.name.replace(/\[\d+\]/, `[${rowIndex}]`);
        }
        input.value = ''; // Clear values for new row
    });

    // Add remove button to new row
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'remove-row';
    removeBtn.textContent = '-';
    removeBtn.addEventListener('click', function() {
        removeRow(this);
    });
    newRow.querySelector('.input-row:last-child').appendChild(removeBtn);

    // Append new row
    rowsContainer.appendChild(newRow);

    // Update calculations if needed
    updateCalculations();
}

function removeRow(button) {
    const materialRow = button.closest('.material-row');
    const rowsContainer = materialRow.parentElement;
    if (rowsContainer.children.length > 1) { // Prevent removing the last row
        materialRow.remove();
        updateCalculations();
    }
}

// Attach event listeners to add-row buttons
document.addEventListener('DOMContentLoaded', function() {
    // ...existing code...
    document.querySelectorAll('.add-row').forEach(btn => {
        btn.addEventListener('click', function() {
            const groupType = this.closest('.material-group').querySelector('h3').textContent.toLowerCase();
            addRow(this, groupType);
        });
    });
});

// Function to update calculations (placeholder, expand as needed)
function updateCalculations() {
    // Calculate total raw cost, etc.
    // This will be expanded in the next improvement
}

// Function to sanitize contenteditable input to prevent XSS
function sanitizeInput(input) {
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = input;
    // Remove script tags and other dangerous elements
    const scripts = tempDiv.querySelectorAll('script');
    scripts.forEach(script => script.remove());
    // Allow only safe tags like <b>, <i>, <u>, <br>
    const allowedTags = ['b', 'i', 'u', 'br', 'p', 'div'];
    const allElements = tempDiv.querySelectorAll('*');
    allElements.forEach(el => {
        if (!allowedTags.includes(el.tagName.toLowerCase())) {
            el.remove();
        }
    });
    return tempDiv.innerHTML;
}

// Sync contenteditable to textarea with sanitization
document.getElementById('description-editor').addEventListener('input', function() {
    const sanitized = sanitizeInput(this.innerHTML);
    document.getElementById('description').value = sanitized;
});

// File upload validation
document.getElementById('images').addEventListener('change', function(e) {
    const files = e.target.files;
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    let valid = true;
    for (let file of files) {
        if (!allowedTypes.includes(file.type)) {
            alert(`Invalid file type: ${file.name}. Only JPG, PNG, GIF allowed.`);
            valid = false;
        }
        if (file.size > maxSize) {
            alert(`File too large: ${file.name}. Max size 5MB.`);
            valid = false;
        }
    }
    if (!valid) {
        e.target.value = ''; // Clear invalid files
    }
});

document.getElementById('customImage').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const maxSize = 2 * 1024 * 1024; // 2MB
        const allowedTypes = ['image/jpeg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            alert('Invalid file type. Only JPG, PNG allowed.');
            e.target.value = '';
        } else if (file.size > maxSize) {
            alert('File too large. Max size 2MB.');
            e.target.value = '';
        }
    }
});

// Enhanced validation for all fields
function validatePage(page) {
    let isValid = true;
    const requiredFields = document.querySelectorAll(`.page${page} [required]`);
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('error');
            const errorSpan = document.getElementById(field.id + '-error');
            if (errorSpan) {
                errorSpan.textContent = 'This field is required.';
                errorSpan.style.display = 'block';
            }
        } else {
            field.classList.remove('error');
            const errorSpan = document.getElementById(field.id + '-error');
            if (errorSpan) {
                errorSpan.style.display = 'none';
            }
        }
    });
    // Additional validations
    const emailFields = document.querySelectorAll(`.page${page} input[type="email"]`);
    emailFields.forEach(field => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (field.value && !emailRegex.test(field.value)) {
            isValid = false;
            field.classList.add('error');
            const errorSpan = document.getElementById(field.id + '-error');
            if (errorSpan) {
                errorSpan.textContent = 'Invalid email format.';
                errorSpan.style.display = 'block';
            }
        }
    });
    return isValid;
}

// Update event listeners for navigation
document.addEventListener('DOMContentLoaded', function() {
    // ...existing code...
    document.querySelectorAll('.continue-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (validatePage(currentPage)) {
                showNextPage();
            }
        });
    });

    document.querySelectorAll('.btn-back').forEach(btn => {
        btn.addEventListener('click', function() {
            showPreviousPage();
        });
    });

    document.querySelectorAll('.btn-cancel').forEach(btn => {
        btn.addEventListener('click', function() {
            // Handle cancel, e.g., redirect or reset
            window.location.href = '{{ route("admin.products.index") }}'; // Adjust route as needed
        });
    });

    // ...existing code...
});

// Function to show previous page
function showPreviousPage() {
    if (currentPage > 1) {
        document.querySelector(`.page${currentPage}`).style.display = 'none';
        currentPage--;
        document.querySelector(`.page${currentPage}`).style.display = 'block';
    }
}

// ================================
// Modularized JS for Create Invitation Form
// ================================

// Module 1: Calculations
const Calculations = {
    lastValues: { quantity: null, markup: null, totalRawCost: null },

    calculateAll: function() {
        try {
            console.log('Starting calculation...');
            const quantity = parseFloat(document.getElementById("quantityOrdered").value) || 0;
            const markup = parseFloat(document.getElementById("markup").value) || 0;
            const totalRawCost = parseFloat(document.getElementById("totalRawCost").value) || 0;

            console.log(`Inputs: quantity=${quantity}, markup=${markup}, totalRawCost=${totalRawCost}`);

            // Avoid unnecessary calculations if values haven't changed
            if (this.lastValues.quantity === quantity && this.lastValues.markup === markup && this.lastValues.totalRawCost === totalRawCost) {
                console.log('Values unchanged, skipping calculation.');
                return;
            }

            this.lastValues = { quantity, markup, totalRawCost };

            // Handle edge cases
            if (quantity === 0) {
                console.warn('Quantity is 0, setting outputs to 0.00');
                document.getElementById("costPerInvite").value = '0.00';
                document.getElementById("sellingPrice").value = '0.00';
                document.getElementById("totalSellingPrice").value = '0.00';
                return;
            }

            const costPerInvite = totalRawCost / quantity;
            const sellingPrice = costPerInvite * (1 + markup / 100);
            const totalSellingPrice = sellingPrice * quantity;

            console.log(`Calculated: costPerInvite=${costPerInvite}, sellingPrice=${sellingPrice}, totalSellingPrice=${totalSellingPrice}`);

            document.getElementById("costPerInvite").value = costPerInvite.toFixed(2);
            document.getElementById("sellingPrice").value = sellingPrice.toFixed(2);
            document.getElementById("totalSellingPrice").value = totalSellingPrice.toFixed(2);
        } catch (error) {
            console.error("Calculation error:", error);
            alert("An error occurred during calculation. Please check your inputs.");
        }
    },

    debounceCalculate: function() {
        clearTimeout(this.calculationTimeout);
        this.calculationTimeout = setTimeout(this.calculateAll.bind(this), 300);
    },

    calculationTimeout: null,

    init: function() {
        const quantityInput = document.getElementById("quantityOrdered");
        const markupSelect = document.getElementById("markup");
        const totalRawCostInput = document.getElementById("totalRawCost");

        quantityInput.addEventListener("input", () => this.debounceCalculate());
        markupSelect.addEventListener("change", () => this.debounceCalculate());
        totalRawCostInput.addEventListener("input", () => this.debounceCalculate());

        this.calculateAll(); // Initial calculation
    }
};

// Module 2: Validation
const Validation = {
    validatePage: function(page) {
        try {
            console.log(`Validating page ${page}...`);
            let isValid = true;
            const requiredFields = document.querySelectorAll(`.page${page} [required]`);
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    this.showError(field, 'This field is required.');
                } else {
                    this.clearError(field);
                }
            });

            // Additional validations
            const emailFields = document.querySelectorAll(`.page${page} input[type="email"]`);
            emailFields.forEach(field => {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (field.value && !emailRegex.test(field.value)) {
                    isValid = false;
                    this.showError(field, 'Invalid email format.');
                } else if (field.value) {
                    this.clearError(field);
                }
            });

            const numberFields = document.querySelectorAll(`.page${page} input[type="number"]`);
            numberFields.forEach(field => {
                const value = parseFloat(field.value);
                const min = parseFloat(field.getAttribute('min')) || 0;
                const max = parseFloat(field.getAttribute('max')) || Infinity;
                if (field.value && (isNaN(value) || value < min || value > max)) {
                    isValid = false;
                    this.showError(field, `Value must be a number between ${min} and ${max}.`);
                } else if (field.value) {
                    this.clearError(field);
                }
            });

            const textFields = document.querySelectorAll(`.page${page} input[type="text"], .page${page} textarea`);
            textFields.forEach(field => {
                const minLength = parseInt(field.getAttribute('minlength')) || 0;
                const maxLength = parseInt(field.getAttribute('maxlength')) || Infinity;
                if (field.value && (field.value.length < minLength || field.value.length > maxLength)) {
                    isValid = false;
                    this.showError(field, `Length must be between ${minLength} and ${maxLength} characters.`);
                } else if (field.value) {
                    this.clearError(field);
                }
            });

            console.log(`Page ${page} validation result: ${isValid}`);
            return isValid;
        } catch (error) {
            console.error(`Validation error on page ${page}:`, error);
            return false;
        }
    },

    showError: function(field, message) {
        field.classList.add('error');
        const errorSpan = document.getElementById(field.id + '-error');
        if (errorSpan) {
            errorSpan.textContent = message;
            errorSpan.style.display = 'block';
        }
    },

    clearError: function(field) {
        field.classList.remove('error');
        const errorSpan = document.getElementById(field.id + '-error');
        if (errorSpan) {
            errorSpan.style.display = 'none';
        }
    },

    sanitizeInput: function(input) {
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = input;
        const scripts = tempDiv.querySelectorAll('script');
        scripts.forEach(script => script.remove());
        const allowedTags = ['b', 'i', 'u', 'br', 'p', 'div'];
        const allElements = tempDiv.querySelectorAll('*');
        allElements.forEach(el => {
            if (!allowedTags.includes(el.tagName.toLowerCase())) {
                el.remove();
            }
        });
        return tempDiv.innerHTML;
    }
};

// Module 3: Navigation
const Navigation = {
    currentPage: 1,
    totalPages: 3,
    history: [1], // Track page history
    historyIndex: 0,

    showPage: function(pageIndex) {
        const pages = document.querySelectorAll('.page');
        pages.forEach((page, index) => {
            page.style.display = index === pageIndex ? 'block' : 'none';
        });
    },

    showNextPage: function() {
        try {
            console.log('Navigating to next page...');
            if (this.currentPage < this.totalPages) {
                this.history = this.history.slice(0, this.historyIndex + 1); // Trim future history
                this.currentPage++;
                this.history.push(this.currentPage);
                this.historyIndex++;
                this.showPage(this.currentPage - 1);
                console.log(`Now on page ${this.currentPage}`);
            } else {
                console.warn('Already on last page, cannot go next.');
            }
        } catch (error) {
            console.error('Navigation error:', error);
        }
    },

    showPreviousPage: function() {
        if (this.currentPage > 1) {
            this.currentPage--;
            this.historyIndex--;
            this.showPage(this.currentPage - 1);
        }
    },

    goForward: function() {
        if (this.historyIndex < this.history.length - 1) {
            this.historyIndex++;
            this.currentPage = this.history[this.historyIndex];
            this.showPage(this.currentPage - 1);
        }
    },

    goBack: function() {
        if (this.historyIndex > 0) {
            this.historyIndex--;
            this.currentPage = this.history[this.historyIndex];
            this.showPage(this.currentPage - 1);
        }
    },

    init: function() {
        document.querySelectorAll('.continue-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (Validation.validatePage(this.currentPage)) {
                    this.showNextPage();
                }
            });
        });

        document.querySelectorAll('.btn-back').forEach(btn => {
            btn.addEventListener('click', () => {
                this.showPreviousPage();
            });
        });

        document.querySelectorAll('.btn-cancel').forEach(btn => {
            btn.addEventListener('click', () => {
                window.location.href = '{{ route("admin.products.index") }}'; // Adjust route as needed
            });
        });

        // Add forward button if needed, e.g., for future enhancement
        // document.querySelectorAll('.btn-forward').forEach(btn => {
        //     btn.addEventListener('click', () => this.goForward());
        // });

        this.showPage(0); // Start with first page
    }
};

// Module 4: Dynamic Rows
const DynamicRows = {
    init: function() {
        // Use event delegation for dynamic elements
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-row')) {
                const groupType = e.target.closest('.material-group').querySelector('h3').textContent.toLowerCase();
                this.addRow(e.target, groupType);
            }
            if (e.target.classList.contains('remove-row')) {
                this.removeRow(e.target);
            }
        });
    },

    addRow: function(button, groupType) {
        try {
            console.log(`Adding row for group: ${groupType}`);
            const materialRow = button.closest('.material-row');
            const newRow = materialRow.cloneNode(true);
            const rowsContainer = materialRow.parentElement;

            const inputs = newRow.querySelectorAll('input, select');
            const rowIndex = rowsContainer.children.length;
            inputs.forEach(input => {
                const baseId = input.id.replace(/_\d+$/, '');
                input.id = `${baseId}_${rowIndex}`;
                if (input.name) {
                    input.name = input.name.replace(/\[\d+\]/, `[${rowIndex}]`);
                }
                input.value = '';
            });

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'remove-row';
            removeBtn.textContent = '-';
            newRow.querySelector('.input-row:last-child').appendChild(removeBtn);

            rowsContainer.appendChild(newRow);
            Calculations.debounceCalculate();
            console.log('Row added successfully.');
        } catch (error) {
            console.error('Error adding row:', error);
        }
    },

    removeRow: function(button) {
        const materialRow = button.closest('.material-row');
        const rowsContainer = materialRow.parentElement;
        if (rowsContainer.children.length > 1) {
            materialRow.remove();
            Calculations.debounceCalculate();
        }
    }
};

// Module 5: Editor and Sync
const Editor = {
    maxLength: 500, // Example max length

    init: function() {
        try {
            console.log('Initializing editor...');
            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('editor-btn')) {
                    const command = e.target.getAttribute('data-command');
                    document.execCommand(command, false, null);
                    document.getElementById('description-editor').focus();
                }
            });

            const editor = document.getElementById('description-editor');
            const charLimitSpan = document.createElement('span');
            charLimitSpan.className = 'char-limit';
            charLimitSpan.id = 'char-limit';
            editor.parentElement.appendChild(charLimitSpan);

            editor.addEventListener('input', () => {
                const sanitized = Validation.sanitizeInput(editor.innerHTML);
                document.getElementById('description').value = sanitized;

                // Update character limit indicator
                const length = sanitized.length;
                charLimitSpan.textContent = `${length}/${this.maxLength}`;
                if (length > this.maxLength) {
                    charLimitSpan.classList.add('over');
                    charLimitSpan.classList.remove('warning');
                } else if (length > this.maxLength * 0.9) {
                    charLimitSpan.classList.add('warning');
                    charLimitSpan.classList.remove('over');
                } else {
                    charLimitSpan.classList.remove('warning', 'over');
                }
            });

            // Initial update
            editor.dispatchEvent(new Event('input'));
        } catch (error) {
            console.error('Editor initialization error:', error);
        }
    }
};

// Module 6: File Upload
const FileUpload = {
    init: function() {
        const imageInput = document.getElementById('images');
        const customImageInput = document.getElementById('customImage');

        // Add drag-and-drop support
        this.addDragDrop(imageInput);
        this.addDragDrop(customImageInput);

        // Existing change listeners
        imageInput.addEventListener('change', (e) => this.validateFiles(e.target, 'images'));
        customImageInput.addEventListener('change', (e) => this.validateFiles(e.target, 'custom'));
    },

    addDragDrop: function(input) {
        const dropZone = input.closest('.file-upload-zone') || input.parentElement; // Assume a wrapper div with class 'file-upload-zone'

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('drag-over');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('drag-over');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('drag-over');
            const files = e.dataTransfer.files;
            input.files = files;
            input.dispatchEvent(new Event('change'));
        });
    },

    validateFiles: function(input, type) {
        try {
            console.log(`Validating files for ${type}...`);
            const files = input.files;
            const maxSize = type === 'images' ? 5 * 1024 * 1024 : 2 * 1024 * 1024;
            const allowedTypes = type === 'images' ? ['image/jpeg', 'image/png', 'image/gif'] : ['image/jpeg', 'image/png'];
            let valid = true;
            for (let file of files) {
                if (!allowedTypes.includes(file.type)) {
                    alert(`Invalid file type: ${file.name}. Only ${allowedTypes.join(', ')} allowed.`);
                    valid = false;
                }
                if (file.size > maxSize) {
                    alert(`File too large: ${file.name}. Max size ${maxSize / 1024 / 1024}MB.`);
                    valid = false;
                }
            }
            if (!valid) {
                input.value = '';
            } else {
                // Show progress indicator (simulate for now)
                this.showProgress(input);
            }
            console.log('File validation complete.');
        } catch (error) {
            console.error('File validation error:', error);
        }
    },

    showProgress: function(input) {
        const progressBar = document.createElement('progress');
        progressBar.value = 0;
        progressBar.max = 100;
        progressBar.className = 'upload-progress';
        input.parentElement.appendChild(progressBar);

        // Simulate progress (in real app, use XMLHttpRequest upload progress)
        let progress = 0;
        const interval = setInterval(() => {
            progress += 10;
            progressBar.value = progress;
            if (progress >= 100) {
                clearInterval(interval);
                setTimeout(() => progressBar.remove(), 1000);
            }
        }, 100);
    }
};

// Module 7: Accessibility
const Accessibility = {
    init: function() {
        try {
            console.log('Initializing accessibility features...');
            // Add ARIA labels and roles
            document.querySelectorAll('.continue-btn').forEach(btn => {
                btn.setAttribute('aria-label', 'Continue to next page');
            });
            document.querySelectorAll('.btn-back').forEach(btn => {
                btn.setAttribute('aria-label', 'Go back to previous page');
            });
            document.querySelectorAll('.btn-save').forEach(btn => {
                btn.setAttribute('aria-label', 'Save the invitation');
            });
            document.querySelectorAll('.btn-cancel').forEach(btn => {
                btn.setAttribute('aria-label', 'Cancel and go back');
            });
            document.querySelectorAll('.add-row').forEach(btn => {
                btn.setAttribute('aria-label', 'Add a new row');
            });
            document.querySelectorAll('.remove-row').forEach(btn => {
                btn.setAttribute('aria-label', 'Remove this row');
            });
            document.querySelectorAll('.editor-btn').forEach(btn => {
                btn.setAttribute('aria-label', `Apply ${btn.getAttribute('data-command')} formatting`);
            });

            // Keyboard navigation for editor
            document.addEventListener('keydown', (e) => {
                if (e.target.id === 'description-editor') {
                    if (e.ctrlKey || e.metaKey) {
                        switch (e.key) {
                            case 'b':
                                e.preventDefault();
                                document.execCommand('bold', false, null);
                                break;
                            case 'i':
                                e.preventDefault();
                                document.execCommand('italic', false, null);
                                break;
                            case 'u':
                                e.preventDefault();
                                document.execCommand('underline', false, null);
                                break;
                        }
                    }
                }
            });

            // Announce page changes for screen readers
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        const target = mutation.target;
                        if (target.classList.contains('page') && target.style.display === 'block') {
                            const pageNumber = target.classList[1].slice(-1);
                            this.announce(`Page ${pageNumber} of ${Navigation.totalPages} is now active.`);
                        }
                    }
                });
            });
            document.querySelectorAll('.page').forEach(page => {
                observer.observe(page, { attributes: true, attributeFilter: ['style'] });
            });

            // Focus management
            document.querySelectorAll('.continue-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    setTimeout(() => {
                        const nextPage = document.querySelector(`.page${Navigation.currentPage}`);
                        const firstInput = nextPage.querySelector('input, select, textarea');
                        if (firstInput) firstInput.focus();
                    }, 100);
                });
            });
        } catch (error) {
            console.error('Accessibility initialization error:', error);
        }
    },

    announce: function(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.style.position = 'absolute';
        announcement.style.left = '-10000px';
        announcement.style.width = '1px';
        announcement.style.height = '1px';
        announcement.style.overflow = 'hidden';
        document.body.appendChild(announcement);
        announcement.textContent = message;
        setTimeout(() => document.body.removeChild(announcement), 1000);
    }
};

// Module 10: Performance Optimization
const Performance = {
    init: function() {
        // Use requestAnimationFrame for smooth animations
        this.optimizeAnimations();

        // Lazy load non-critical scripts if any
        this.lazyLoadScripts();
    },

    optimizeAnimations: function() {
        // Example: Optimize page transitions with requestAnimationFrame
        const originalShowPage = Navigation.showPage;
        Navigation.showPage = function(pageIndex) {
            requestAnimationFrame(() => {
                originalShowPage.call(this, pageIndex);
            });
        };

        // Optimize calculation updates
        const originalCalculateAll = Calculations.calculateAll;
        Calculations.calculateAll = function() {
            requestAnimationFrame(() => {
                originalCalculateAll.call(this);
            });
        };
    },

    lazyLoadScripts: function() {
        // Example: Lazy load a script if needed, e.g., for advanced features
        // const script = document.createElement('script');
        // script.src = 'path/to/non-critical-script.js';
        // script.onload = () => console.log('Lazy loaded script ready');
        // document.head.appendChild(script);
        console.log('Lazy loading setup (placeholder)');
    }
};

// Initialize all modules on DOMContentLoaded
document.addEventListener("DOMContentLoaded", () => {
    Calculations.init();
    Navigation.init();
    DynamicRows.init();
    Editor.init();
    FileUpload.init();
    Customization.init();
    DarkMode.init();
    Accessibility.init(); // Add this
});

// ================================
// Customization Toggle
// ================================
document.addEventListener("DOMContentLoaded", () => {
    const customizationAllowed = document.getElementById('customizationAllowed');
    const customFields = document.querySelectorAll('.custom-field'); // Assuming these fields have class 'custom-field'

    function toggleCustomFields() {
        const isAllowed = customizationAllowed.value === 'Yes';
        customFields.forEach(field => {
            field.style.display = isAllowed ? 'block' : 'none';
        });
    }

    customizationAllowed.addEventListener('change', toggleCustomFields);

    // Initial check
    toggleCustomFields();
});

// ================================
// Dark Mode Toggle
// ================================
const toggle = document.querySelector('.dark-mode-toggle');
toggle.addEventListener('click', () => {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
});

// On load
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
}
