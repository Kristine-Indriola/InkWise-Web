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
// ...existing code...

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
// ...existing code...

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
// ...existing code...

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
// Create Invitation Form JS
// ================================
document.addEventListener("DOMContentLoaded", () => {
    const pages = document.querySelectorAll('.page');
    const continueBtns = document.querySelectorAll('.continue-btn');
    const backBtns = document.querySelectorAll('.btn-back');
    const cancelBtn = document.querySelector('.btn-cancel');
    let currentPage = 0;

    // Show the first page
    showPage(currentPage);

    // Continue button event
    continueBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            if (currentPage < pages.length - 1) {
                currentPage++;
                showPage(currentPage);
            }
        });
    });

    // Back button event
    backBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            if (currentPage > 0) {
                currentPage--;
                showPage(currentPage);
            }
        });
    });

    // Cancel button event
    cancelBtn.addEventListener('click', () => {
        // For now, just go back to first page or alert
        alert('Cancelled. Returning to first page.');
        currentPage = 0;
        showPage(currentPage);
    });

    function showPage(pageIndex) {
        pages.forEach((page, index) => {
            page.style.display = index === pageIndex ? 'block' : 'none';
        });
    }

    // Additional form logic can be added here, like validation, but for now, just navigation
});
