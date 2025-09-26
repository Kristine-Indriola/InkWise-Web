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

        // expose global singleton
        try {
            window.ProductGridStore = window.ProductGridStore || new ProductGridStore('.products-grid');
        } catch (e) { /* ignore in non-browser contexts */ }
    })();