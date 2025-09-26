<<<<<<< HEAD
// Minimal Product Dashboard JS - focused on grid view and search
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('productSearch');
    const searchForm = document.getElementById('productSearchForm');
    const grid = document.querySelector('.products-grid');
    const cards = grid ? Array.from(grid.querySelectorAll('.product-card')) : [];
    let searchTimeout = null;

    function sanitizeInput(input) {
        return (input || '').replace(/[<>\"']/g, '');
    }

    // If there's a server-backed search form, debounce submissions.
    if (searchInput && searchForm) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => searchForm.submit(), 450);
        });
    }

    // Otherwise provide a light client-side filter for the grid of cards
    if (searchInput && !searchForm && cards.length) {
        searchInput.addEventListener('input', function () {
            const q = sanitizeInput(this.value).toLowerCase().trim();
            let visibleCount = 0;
            cards.forEach(card => {
                const text = (card.textContent || '').toLowerCase();
                const match = q === '' || text.includes(q);
                card.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });

            // Update entries info if present
            const entriesInfo = document.querySelector('.entries-info');
            if (entriesInfo) {
                entriesInfo.textContent = q === ''
                    ? entriesInfo.getAttribute('data-original') || entriesInfo.textContent
                    : `Showing ${visibleCount} of ${cards.length} entries`;
            }
        });
    }

    // Toggle sections (materials/inks) if present on page
    const togglePairs = [
        { btnId: 'toggle-materials', sectionId: 'materials-section', showText: 'Hide Materials', hideText: 'Show Materials' },
        { btnId: 'toggle-inks', sectionId: 'inks-section', showText: 'Hide Inks', hideText: 'Show Inks' }
    ];
    togglePairs.forEach(({btnId, sectionId, showText, hideText}) => {
        const btn = document.getElementById(btnId);
        const section = document.getElementById(sectionId);
        if (!btn || !section) return;
        btn.addEventListener('click', () => {
            const isHidden = section.style.display === 'none';
            section.style.display = isHidden ? '' : 'none';
            btn.textContent = isHidden ? showText : hideText;
        });
    });

    // Safety: if other scripts expect window.attachProductPanelHandlers or loader, leave untouched.
});

    // ProductGridStore: a lightweight in-memory store and DOM renderer for the products grid.
    // Usage: window.ProductGridStore.add(productObj) after creating a product to insert it into the UI.
    (function () {
        function qs(sel, ctx) { return (ctx || document).querySelector(sel); }
        function qsa(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }

        function createText(tag, text, className) {
            var el = document.createElement(tag);
            if (className) el.className = className;
            el.textContent = text || '';
            return el;
        }

        function escapeHtml(str) {
            if (str == null) return '';
            return String(str).replace(/[&<>"']/g, function (s) {
                return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"})[s];
            });
        }
=======
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
>>>>>>> origin/dashboard17

        function ProductGridStore(selector) {
            this.selector = selector || '.products-grid';
            this.grid = qs(this.selector);
            this.entriesInfo = qs('.entries-info');
            this.products = [];
            this._init();
        }

        ProductGridStore.prototype._init = function () {
            if (!this.grid) return;
            var self = this;
            // Capture existing product cards
            var cards = qsa('.product-card', this.grid);
            this.products = cards.map(function (card) {
                return self._productFromCard(card);
            });

            // store original entries-info text if present
            if (this.entriesInfo && !this.entriesInfo.getAttribute('data-original')) {
                this.entriesInfo.setAttribute('data-original', this.entriesInfo.textContent.trim());
            }
        };

        ProductGridStore.prototype._productFromCard = function (card) {
            var id = card.getAttribute('data-id') || null;
            var title = qs('.product-card-title', card)?.textContent?.trim() || '';
            var img = qs('img.product-card-thumb', card)?.getAttribute('src') || '';
            var desc = qs('.product-card-desc', card)?.textContent?.trim() || '';
            var price = qs('.price', card)?.textContent?.trim() || '';
            var qty = qs('.qty', card)?.textContent?.trim() || '';
            var status = qs('.status', card)?.textContent?.trim() || '';
            return { id: id, name: title, image: img, description: desc, price: price, quantity: qty, status: status };
        };

        ProductGridStore.prototype._renderCard = function (product) {
            var card = document.createElement('div');
            card.className = 'product-card';
            if (product.id) card.setAttribute('data-id', product.id);

            // media
            var media = document.createElement('div'); media.className = 'product-card-media';
            var img = document.createElement('img'); img.className = 'product-card-thumb';
            img.setAttribute('alt', escapeHtml(product.name || 'Product'));
            img.setAttribute('loading', 'lazy');
            img.src = product.image || (window.__productPlaceholder || '/images/no-image.png');
            media.appendChild(img);

            // body
            var body = document.createElement('div'); body.className = 'product-card-body';
            var h3 = createText('h3', product.name || '', 'product-card-title');
            body.appendChild(h3);
            if (product.description) {
                var p = createText('p', product.description, 'product-card-desc');
                body.appendChild(p);
            }
            var meta = document.createElement('div'); meta.className = 'product-card-meta';
            var eType = createText('span', product.event_type || '-', 'meta-item');
            var sep = createText('span', '•', 'meta-sep');
            var pType = createText('span', product.product_type || '-', 'meta-item');
            meta.appendChild(eType); meta.appendChild(sep); meta.appendChild(pType);
            body.appendChild(meta);

            // footer
            var footer = document.createElement('div'); footer.className = 'product-card-footer';
            var price = createText('div', product.price ? product.price : '₱0.00', 'price');
            var qty = createText('div', product.quantity ? ('Qty: ' + product.quantity) : 'Qty: 0', 'qty');
            var statusWrap = document.createElement('div'); statusWrap.className = 'status-wrap';
            var statusSpan = createText('span', product.status || 'unknown', 'status');
            statusWrap.appendChild(statusSpan);
            footer.appendChild(price); footer.appendChild(qty); footer.appendChild(statusWrap);

            // actions
            var actions = document.createElement('div'); actions.className = 'card-actions';
            var viewBtn = document.createElement('button');
            viewBtn.type = 'button'; viewBtn.className = 'btn-view btn-view-ajax';
            viewBtn.setAttribute('aria-label', 'View ' + (product.name || 'Product'));
            viewBtn.setAttribute('data-id', product.id || '');
            if (product.url) viewBtn.setAttribute('data-url', product.url);
            viewBtn.innerHTML = '<i class="fi fi-sr-eye"></i>';
            actions.appendChild(viewBtn);

            var editA = document.createElement('a'); editA.className = 'btn-update'; editA.setAttribute('aria-label', 'Edit ' + (product.name || 'Product'));
            if (product.editUrl) editA.href = product.editUrl; else editA.href = '#';
            editA.innerHTML = '<i class="fa-solid fa-pen-to-square"></i>';
            actions.appendChild(editA);

            footer.appendChild(actions);

            card.appendChild(media); card.appendChild(body); card.appendChild(footer);
            return card;
        };

        ProductGridStore.prototype.add = function (product, prepend) {
            if (!this.grid) return null;
            // normalize
            var p = Object.assign({}, product);
            this.products.unshift(p);
            var node = this._renderCard(p);
            if (prepend && this.grid.firstChild) this.grid.insertBefore(node, this.grid.firstChild);
            else this.grid.appendChild(node);
            this._updateEntries(1);
            // dispatch event
            try { document.dispatchEvent(new CustomEvent('productgrid:add', { detail: p })); } catch (e) {}
            return node;
        };

        ProductGridStore.prototype.remove = function (id) {
            if (!this.grid) return false;
            var idx = this.products.findIndex(function (p) { return String(p.id) === String(id); });
            if (idx !== -1) this.products.splice(idx, 1);
            var node = qs('.product-card[data-id="' + id + '"]', this.grid);
            if (node) node.remove();
            this._updateEntries(-1);
            try { document.dispatchEvent(new CustomEvent('productgrid:remove', { detail: { id: id } })); } catch (e) {}
            return true;
        };

        ProductGridStore.prototype.update = function (id, changes) {
            var idx = this.products.findIndex(function (p) { return String(p.id) === String(id); });
            if (idx === -1) return null;
            this.products[idx] = Object.assign({}, this.products[idx], changes);
            var oldNode = qs('.product-card[data-id="' + id + '"]', this.grid);
            if (oldNode) {
                var newNode = this._renderCard(this.products[idx]);
                oldNode.parentNode.replaceChild(newNode, oldNode);
            }
            try { document.dispatchEvent(new CustomEvent('productgrid:update', { detail: this.products[idx] })); } catch (e) {}
            return this.products[idx];
        };

        ProductGridStore.prototype._updateEntries = function (delta) {
            if (!this.entriesInfo) return;
            // try to keep it simple: if original text included totals, update numeric part.
            var orig = this.entriesInfo.getAttribute('data-original') || this.entriesInfo.textContent;
            // if orig is like 'Showing X to Y of Z entries' we try to increment Z
            var m = orig.match(/of\s+(\d+)\s+entries/i);
            if (m) {
                var total = parseInt(m[1], 10) + delta;
                this.entriesInfo.textContent = 'Showing 1 to ' + Math.min(20, Math.max(0, total)) + ' of ' + total + ' entries';
            } else {
                // fallback: set simple count
                this.entriesInfo.textContent = 'Showing ' + this.products.length + ' entries';
            }
        };

<<<<<<< HEAD
        // expose global singleton
        try {
            window.ProductGridStore = window.ProductGridStore || new ProductGridStore('.products-grid');
        } catch (e) { /* ignore in non-browser contexts */ }
    })();
=======
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
>>>>>>> origin/dashboard17
