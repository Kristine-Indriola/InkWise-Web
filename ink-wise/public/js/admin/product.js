// Minimal product admin JS
// Provides: delete handler for ajax-delete buttons, small helpers for UI interactions.

// Global delete handler (works for grid cards and forms)
document.addEventListener('click', function(e) {
    const delBtn = e.target.closest('.ajax-delete');
    if (!delBtn) return;
    const id = delBtn.dataset.id;
    if (!id) return;
    if (!confirm("⚠️ Are you sure you want to delete this product?")) return;
    const originalHtml = delBtn.innerHTML;
    delBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
    delBtn.disabled = true;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    fetch(`/admin/products/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': token || '',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        }
    }).then(r => {
        if (r.ok) return r.json().catch(() => ({}));
        throw new Error('Delete failed');
    }).then(() => {
        const card = delBtn.closest('.product-card');
        if (card && window.__removeCardWithAnimation) {
            window.__removeCardWithAnimation(card);
            return;
        }
        const row = delBtn.closest('tr');
        if (row) { row.remove(); return; }
        const parent = delBtn.parentElement;
        if (parent) parent.remove();
    }).catch(err => {
        alert('Could not delete the product.');
        delBtn.innerHTML = originalHtml;
        delBtn.disabled = false;
    });
});

// small UI initialization for toggles (run on DOMContentLoaded)
document.addEventListener('DOMContentLoaded', function () {
    const inkBtn = document.getElementById('toggle-inks');
    const inkSection = document.getElementById('inks-section');
    if (inkBtn && inkSection) {
        inkBtn.addEventListener('click', function() {
            if (inkSection.style.display === 'none') {
                inkSection.style.display = '';
                inkBtn.textContent = 'Hide Inks';
            } else {
                inkSection.style.display = 'none';
                inkBtn.textContent = 'Show Inks';
            }
        });
    }
});

// Animated card removal helper
function removeCardWithAnimation(card) {
    if (!card) return;
    card.classList.add('card-removing');
    card.addEventListener('animationend', function onEnd(){
        try { card.remove(); } catch(e){}
        card.removeEventListener('animationend', onEnd);
    });
}

// Expose globally so other scripts can call it
window.__removeCardWithAnimation = removeCardWithAnimation;
