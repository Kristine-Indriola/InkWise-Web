// Small, focused product admin JS
// This file intentionally no longer contains table/search/pagination logic
// (table & summary card UI removed). Keep minimal behaviors: delete + modal.

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

// ...existing code...

// ================================
// Live Search
// ================================
function sanitizeInput(input) {
    return input.replace(/[<>\"']/g, ''); // Basic sanitization to prevent XSS
}

searchInput.addEventListener("keyup", function () {
    const query = sanitizeInput(this.value.toLowerCase());
    const rows = Array.from(table.querySelectorAll("tr"));
    let visibleRows = [];

    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(query)) {
            row.style.display = "";
            visibleRows.push(row);
        } else {
            row.style.display = "none";
        }
    });

    // Handle case where no rows match
    if (visibleRows.length === 0) {
        // Optionally, show a "no results" message
        console.log("No matching products found.");
    }

    // Update pagination after search
    currentPage = 1;
    paginate(visibleRows.length > 0 ? visibleRows : rows);
});
// ...existing code...

// ================================
// Sort Functions
// ================================
function sortTable(order = "asc") {
    const sorted = [...rows].sort((a, b) => {
        const nameA = a.cells[1].innerText.toLowerCase();
        const nameB = b.cells[1].innerText.toLowerCase();
        return order === "asc"
            ? nameA.localeCompare(nameB)
            : nameB.localeCompare(nameA);
    });
    // Instead of clearing innerHTML, reorder by appending sorted rows
    sorted.forEach(row => table.appendChild(row));
    paginate();
}
document.addEventListener('DOMContentLoaded', function() {
    const matBtn = document.getElementById('toggle-materials');
    const matSection = document.getElementById('materials-section');
    if (matBtn && matSection) {
        matBtn.addEventListener('click', function() {
            if (matSection.style.display === 'none') {
                matSection.style.display = '';
                matBtn.textContent = 'Hide Materials';
            } else {
                matSection.style.display = 'none';
                matBtn.textContent = 'Show Materials';
            }
        });
    }

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


// ...existing code...
// ...existing code...

// ================================
// Modal handlers & animation helpers
// ================================
(function(){
    function attachProductPanelHandlers(container) {
        if (!container) return;
        // ensure body locked to prevent scroll
        document.body.classList.add('modal-open');

        // add enter animation class
        container.classList.remove('modal-exit');
        container.classList.add('modal-enter');

        // close helpers
        var backdrop = container.querySelector('#panel-backdrop');
        var closeBtn = container.querySelector('#close-panel');
        var closeSecondary = container.querySelector('#panel-close-secondary');
        var previouslyFocused = document.__lastActiveElement || document.activeElement;

        function closeCleanup() {
            document.removeEventListener('keydown', keyHandler);
            try { if (container && container.__attachedThumbHandlers) {
                container.__attachedThumbHandlers.forEach(function(h){ h.remove(); });
            }} catch(e){}
            try { document.body.classList.remove('modal-open'); } catch(e){}
        }

        function doClose() {
            container.classList.remove('modal-enter');
            container.classList.add('modal-exit');
            // wait for animation to finish then cleanup
            container.addEventListener('animationend', function onEnd() {
                try { container.remove(); } catch(e){}
                container.removeEventListener('animationend', onEnd);
                closeCleanup();
                // restore focus
                try { if (previouslyFocused) previouslyFocused.focus(); } catch(e){}
            });
        }

        if (backdrop) backdrop.addEventListener('click', doClose);
        if (closeBtn) closeBtn.addEventListener('click', doClose);
        if (closeSecondary) closeSecondary.addEventListener('click', doClose);

        // focus trap and ESC handling
        function getFocusable(el) {
            if (!el) return [];
            var els = Array.from(el.querySelectorAll('a[href], area[href], input:not([disabled]):not([type=hidden]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, object, embed, [tabindex]:not([tabindex="-1"]), [contenteditable]'));
            return els.filter(function(x){ return x.offsetWidth > 0 || x.offsetHeight > 0 || x.getClientRects().length; });
        }

        function keyHandler(e) {
            if (e.key === 'Escape' || e.key === 'Esc') {
                e.preventDefault();
                doClose();
                return;
            }
            if (e.key === 'Tab') {
                var focusables = getFocusable(container);
                if (focusables.length === 0) {
                    e.preventDefault();
                    return;
                }
                var first = focusables[0];
                var last = focusables[focusables.length - 1];
                if (e.shiftKey) {
                    if (document.activeElement === first) {
                        e.preventDefault(); last.focus();
                    }
                } else {
                    if (document.activeElement === last) {
                        e.preventDefault(); first.focus();
                    }
                }
            }
        }

        document.addEventListener('keydown', keyHandler);

        // thumbnails: update main image and selected state
        var thumbs = Array.from(container.querySelectorAll('.thumb'));
        var mainImage = container.querySelector('#panel-main-image');
        container.__attachedThumbHandlers = [];
        thumbs.forEach(function(t){
            var handler = function(){
                var src = t.getAttribute('data-src');
                if (mainImage && src) mainImage.src = src;
                thumbs.forEach(function(x){ x.classList.remove('selected'); });
                t.classList.add('selected');
            };
            t.addEventListener('click', handler);
            container.__attachedThumbHandlers.push({ remove: function(){ t.removeEventListener('click', handler); } });
        });

        // cleanup on exit animation (safety)
        container.addEventListener('animationend', function(e){
            if (e.animationName === 'modalOut') {
                try { container.remove(); } catch(e){}
                closeCleanup();
            }
        });
    }

    // Animated card removal helper
    function removeCardWithAnimation(card) {
        if (!card) return;
        card.classList.add('card-removing');
        card.addEventListener('animationend', function onEnd(){
            try { card.remove(); } catch(e){}
            card.removeEventListener('animationend', onEnd);
        });
    }

    // Expose globally so the Blade injection can call it
    window.attachProductPanelHandlers = attachProductPanelHandlers;
    window.__removeCardWithAnimation = removeCardWithAnimation;
})();
