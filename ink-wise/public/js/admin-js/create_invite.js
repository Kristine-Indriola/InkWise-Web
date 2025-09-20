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

    // Continue button event - no validation, just navigate
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