// View modal handler - populate and show single modal with per-grid data
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-view-ajax');
    if (!btn) return;
    e.preventDefault();
    console.log('View button clicked');
    
    const productCard = btn.closest('.product-card');
    if (!productCard) {
        console.log('No product-card found');
        return;
    }
    console.log('Product card found');
    
    const productData = productCard.querySelector('.product-data');
    if (!productData) {
        console.log('No product-data found');
        return;
    }
    console.log('Product data found');
    
    const modal = document.getElementById('product-modal');
    if (!modal) {
        console.log('No modal found');
        return;
    }
    console.log('Modal found');
    
    const modalContent = modal.querySelector('.floating-content');
    if (!modalContent) {
        console.log('No modal content found');
        return;
    }
    console.log('Modal content found');
    
    // Clear previous content and clone new content
    modalContent.innerHTML = '';
    const clone = productData.querySelector('.floating-content').cloneNode(true);
    modalContent.appendChild(clone);
    
    // Show modal
    modal.style.display = 'flex';
    console.log('Showing modal');
});

// Close modal when clicking close button
document.addEventListener('click', function (e) {
    if (e.target.closest('.floating-close')) {
        const modal = document.getElementById('product-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
});

// Close modal when clicking outside
document.addEventListener('click', function (e) {
    if (e.target.id === 'product-modal') {
        e.target.style.display = 'none';
    }
});