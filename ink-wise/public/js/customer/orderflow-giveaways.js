document.addEventListener('DOMContentLoaded', () => {
    const shell = document.querySelector('.giveaways-shell');
    if (!shell) return;

    const inlineCatalog = (() => {
        const script = document.getElementById('giveawayCatalogData');
        if (!script) return [];
        try {
            const payload = script.textContent?.trim();
            const parsed = payload ? JSON.parse(payload) : [];
            script.remove();
            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            console.warn('Failed to parse inline giveaway catalog', error);
            return [];
        }
    })();

    const giveawayGrid = shell.querySelector('#giveawaysGrid');
    giveawayGrid?.classList.add('envelope-grid');
    giveawayGrid?.classList.add('envelope-item-container');
    const emptyState = shell.querySelector('#giveawaysEmptyState');
    const summaryBody = shell.querySelector('#giveawaySummaryBody');
    const statusBadge = shell.querySelector('#giveawaysStatusBadge');
    const skipBtn = shell.querySelector('#skipGiveawaysBtn');
    const continueBtn = shell.querySelector('#giveawaysContinueBtn');
    const toast = shell.querySelector('#giveawayToast');

    // Modal elements
    const preOrderModal = document.getElementById('preOrderModal');
    const preOrderConfirm = document.getElementById('preOrderConfirm');
    const preOrderCancel = document.getElementById('preOrderCancel');

    const toCleanUrl = (value) => {
        if (typeof value !== 'string') return '';
        const trimmed = value.trim();
        if (!trimmed.length) return '';
        if (/^javascript:/i.test(trimmed)) return '';
        return trimmed;
    };

    const placeholderImage = toCleanUrl(shell.dataset.placeholder)
        || toCleanUrl(shell.dataset.placeholderImage)
        || '/images/no-image.png';

    // Track quantity validity so we never read an undeclared variable
    let isMinQuantityValid = true;

    const extractImageSource = (candidate) => {
        if (!candidate) return '';
        
        // If it's already a full URL or absolute path, return it
        if (typeof candidate === 'string') {
            const trimmed = candidate.trim();
            if (trimmed.startsWith('http') || trimmed.startsWith('/') || trimmed.startsWith('data:')) {
                return trimmed;
            }
            // If it looks like a path but doesn't start with /, it might be a storage path
            // but we'll let the backend handle that or the error handler catch it.
            return trimmed;
        }

        if (typeof candidate === 'object') {
            const keys = ['src', 'url', 'href', 'image', 'path', 'value', 'preview_front', 'preview'];
            for (const key of keys) {
                const value = candidate[key];
                if (typeof value === 'string' && value.trim().length) {
                    return extractImageSource(value);
                }
            }
        }
        return '';
    };

    const normalizeImageArray = (payload) => {
        if (!payload) return [];
        const list = Array.isArray(payload) ? payload : [payload];
        return list
            .map((item) => extractImageSource(item))
            .filter((src) => typeof src === 'string' && src.length);
    };

    const summaryUrl = shell.dataset.summaryUrl || '/order/summary';
    const summaryApiUrl = shell.dataset.summaryApi || summaryUrl;
    const optionsUrl = shell.dataset.optionsUrl || '/api/giveaways';
    const syncUrl = shell.dataset.syncUrl || '/order/giveaways';
    const clearUrl = shell.dataset.clearUrl || syncUrl;
    const storageKey = shell.dataset.storageKey || 'inkwise-finalstep';
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');

    const initialCatalog = inlineCatalog.length ? inlineCatalog : [];

    const sampleGiveaways = [
        {
            id: 'giveaway_sample_1',
            product_id: 'giveaway_sample_1',
            name: 'Signature Wax Seals',
            price: 12.5,
            image: placeholderImage,
            description: 'Hand-poured wax seals with custom initials and velvet ribbon accents.',
            min_qty: 50,
            max_qty: 200,
            step: 10,
            default_qty: 50,
            preview_url: null,
        },
        {
            id: 'giveaway_sample_2',
            product_id: 'giveaway_sample_2',
            name: 'Mini Scented Candles',
            price: 18,
            image: placeholderImage,
            description: 'Soy candles infused with lavender and cotton, packaged with thank-you tags.',
            min_qty: 30,
            max_qty: 150,
            step: 5,
            default_qty: 30,
            preview_url: null,
        },
        {
            id: 'giveaway_sample_3',
            product_id: 'giveaway_sample_3',
            name: 'Thank You Stickers',
            price: 5.5,
            image: placeholderImage,
            description: 'Metallic stickers for sealing envelopes or packaging event favors.',
            min_qty: 100,
            max_qty: 300,
            step: 10,
            default_qty: 120,
            preview_url: null,
        },
    ];

    const state = {
        items: initialCatalog,
        selectedIds: new Set(),
        skeletonCount: Math.min(6, Math.max(3, Math.floor(((window.innerWidth || 1200) - 180) / 260))),
        isSaving: false,
    };

    let toastTimer;

    const formatMoney = (value) => new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
    }).format(Number(value) || 0);

    const normalisePrice = (value, fallback = 0) => {
        const numeric = Number.parseFloat(value);
        return Number.isFinite(numeric) ? numeric : fallback;
    };

    const readSummary = () => {
        try {
            const raw = window.sessionStorage.getItem(storageKey);
            return raw ? JSON.parse(raw) : {};
        } catch (error) {
            console.warn('Failed to parse stored summary', error);
            return {};
        }
    };

    const writeSummary = (summary) => {
        window.sessionStorage.setItem(storageKey, JSON.stringify(summary));
    };

    let isPreOrderConfirmed = false;
    let pendingPreOrderSelection = null;
    const confirmedPreOrderIds = new Set();

    const applyContinueDisabled = (disabled = false) => {
        if (!continueBtn) return;
        const shouldDisable = Boolean(disabled || !isMinQuantityValid);
        continueBtn.disabled = shouldDisable;
        continueBtn.classList.toggle('is-disabled', shouldDisable);
    };

    const setContinueState = (disabled) => {
        applyContinueDisabled(disabled);
    };

    const setRemoveState = (hidden) => {
        // No-op as remove button is now per-item
    };

    const setBadgeState = ({ label, tone }) => {
        if (!statusBadge) return;
        statusBadge.textContent = label;
        statusBadge.classList.remove('summary-badge--success', 'summary-badge--alert');
        if (tone) statusBadge.classList.add(tone);
    };

    const showToast = (message) => {
        if (!toast) return;
        toast.textContent = message;
        toast.hidden = false;
        toast.classList.add('is-visible');
        clearTimeout(toastTimer);
        toastTimer = window.setTimeout(() => {
            toast.classList.remove('is-visible');
            toastTimer = window.setTimeout(() => {
                toast.hidden = true;
            }, 220);
        }, 2400);
    };

    const showSkeleton = (count = 4) => {
        if (!giveawayGrid) return;
        giveawayGrid.dataset.loading = 'true';
        giveawayGrid.innerHTML = '';
        for (let index = 0; index < count; index += 1) {
            const placeholder = document.createElement('div');
            placeholder.className = 'giveaway-card giveaway-card--placeholder';
            placeholder.innerHTML = `
                <div class="giveaway-card__media"></div>
                <div class="giveaway-card__body">
                    <div class="giveaway-card__title"></div>
                    <div class="giveaway-card__text"></div>
                    <div class="giveaway-card__controls"></div>
                </div>
            `;
            giveawayGrid.appendChild(placeholder);
        }
    };

    const clearSkeleton = () => {
        if (!giveawayGrid) return;
        giveawayGrid.removeAttribute('data-loading');
    };

    const getCsrfToken = () => csrfTokenMeta?.getAttribute('content') ?? null;

    const applyServerSummary = (summary) => {
        if (!summary || typeof summary !== 'object') {
            return;
        }

        writeSummary(summary);
        syncSelectionState(summary);
    };

    const fetchSummaryFromServer = async () => {
        if (!summaryApiUrl) {
            return null;
        }

        try {
            const response = await fetch(summaryApiUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                console.warn('Giveaway summary API returned status', response.status);
                return null;
            }

            const payload = await response.json();
            const summary = payload?.data ?? payload;
            if (summary && typeof summary === 'object') {
                applyServerSummary(summary);
                return summary;
            }
        } catch (error) {
            console.error('Error fetching order summary for giveaways', error);
        }

        return null;
    };

    const persistGiveawaySelection = async (payload) => {
        if (!syncUrl) return { ok: false };

        const csrfToken = getCsrfToken();

        try {
            const response = await fetch(syncUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                console.warn('Failed to persist giveaway selection', response.status);
                return { ok: false, status: response.status };
            }

            let data = null;
            try {
                data = await response.json();
            } catch (error) {
                console.warn('Giveaway response could not be parsed', error);
            }

            return { ok: true, data };
        } catch (error) {
            console.error('Error persisting giveaway selection', error);
            return { ok: false, status: 0, error };
        }
    };

    const clearGiveawaySelection = async (productId = null) => {
        if (!clearUrl) return { ok: false };

        const csrfToken = getCsrfToken();

        try {
            const url = new URL(clearUrl, window.location.origin);
            if (productId) {
                url.searchParams.append('product_id', productId);
            }

            const response = await fetch(url.toString(), {
                method: 'DELETE',
                headers: {
                    Accept: 'application/json',
                    ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                console.warn('Failed to clear giveaway selection', response.status);
                return { ok: false, status: response.status };
            }

            let data = null;
            try {
                data = await response.json();
            } catch (error) {
                console.warn('Giveaway clear response could not be parsed', error);
            }

            return { ok: true, data };
        } catch (error) {
            console.error('Error clearing giveaway selection', error);
            return { ok: false, status: 0, error };
        }
    };

    const buildSummaryMarkup = (giveaways) => {
        if (!Array.isArray(giveaways) || !giveaways.length) return '';

        const totalCost = giveaways.reduce((sum, item) => {
            const qty = Number(item.qty) || 0;
            const unitPrice = normalisePrice(item.price);
            const lineTotal = Number.isFinite(item.total) ? Number(item.total) : unitPrice * qty;
            return sum + (Number.isFinite(lineTotal) ? lineTotal : 0);
        }, 0);

        const totalQuantity = giveaways.reduce((sum, item) => sum + (Number(item.qty) || 0), 0);

        let markup = `<h4 class="summary-title">Selected Giveaways (${giveaways.length})</h4>`;

        giveaways.forEach((giveaway) => {
            const imageSrc = extractImageSource(giveaway.image) || placeholderImage;
            const qty = Number(giveaway.qty) || 0;
            const unitPrice = normalisePrice(giveaway.price);
            const lineTotal = Number.isFinite(giveaway.total) ? Number(giveaway.total) : unitPrice * qty;
            const materialParts = [];
            if (giveaway.material) materialParts.push(giveaway.material);
            if (giveaway.material_type) materialParts.push(giveaway.material_type);
            const materialLabel = materialParts.length ? materialParts.join(' • ') : null;

            const detailParts = [];
            if (materialLabel) detailParts.push(materialLabel);
            if (giveaway.description) detailParts.push(giveaway.description);
            if (qty) detailParts.push(`${qty} pcs`);
            if (Number.isFinite(lineTotal)) detailParts.push(formatMoney(lineTotal));
            const meta = detailParts.length ? detailParts.join(' • ') : 'Custom giveaway';

            markup += `
                <div class="summary-selection summary-selection--multiple">
                    <div class="summary-selection__media">
                        <img src="${imageSrc}" alt="${giveaway.name}" onerror="this.style.opacity='0.3';this.src='${placeholderImage}'">
                    </div>
                    <div class="summary-selection__main">
                        <p class="summary-selection__name">${giveaway.name}</p>
                        <p class="summary-selection__meta">${meta}</p>
                    </div>
                </div>
            `;
        });

        markup += `
            <div class="summary-totals">
                <div class="summary-total-row">
                    <span class="summary-total-label">Total Quantity:</span>
                    <span class="summary-total-value">${totalQuantity} pcs</span>
                </div>
                <div class="summary-total-row summary-total-row--grand">
                    <span class="summary-total-label">Total Cost:</span>
                    <span class="summary-total-value">${formatMoney(totalCost)}</span>
                </div>
            </div>
        `;

        return markup;
    };

    const highlightSelectedCard = () => {
        if (!giveawayGrid) return;
        giveawayGrid.querySelectorAll('.giveaway-card').forEach((card) => {
            const id = String(card.dataset.giveawayId);
            const isSelected = state.selectedIds.has(id);
            card.classList.toggle('is-selected', isSelected);
            const button = card.querySelector('.envelope-item__select');
            if (button) {
                button.textContent = isSelected ? 'Unselect giveaways' : 'Select giveaway';
                button.disabled = state.isSaving;
            }
        });
    };

    const syncSelectionState = (summaryOverride = null) => {
        const summary = summaryOverride ?? readSummary() ?? {};
        
        // Normalize giveaways to array
        let giveaways = [];
        if (summary.giveaways && typeof summary.giveaways === 'object') {
            giveaways = Object.values(summary.giveaways);
        } else if (summary.giveaway) {
            giveaways = [summary.giveaway];
        }

        console.log('[giveaways] syncSelectionState called', { summary, giveaways });

        state.selectedIds = new Set(giveaways.map(g => String(g.product_id ?? g.id)));

        if (!giveaways.length) {
            if (summaryBody) {
                summaryBody.innerHTML = '<p class="summary-empty">Choose a giveaway to see its details here.</p>';
            }
            setBadgeState({ label: 'Pending' });
            setContinueState(true);
            setRemoveState(true);
            highlightSelectedCard();
            return;
        }

        if (summaryBody) {
            summaryBody.innerHTML = buildSummaryMarkup(giveaways);
        }
        
        setBadgeState({ label: `${giveaways.length} Selected`, tone: 'summary-badge--success' });
        setContinueState(false);
        setRemoveState(false);
        highlightSelectedCard();
    };

    const createCard = (item) => {
        const card = document.createElement('article');
        card.className = 'giveaway-card envelope-item';
        card.dataset.giveawayId = String(item.id ?? item.product_id ?? '');
        card.dataset.productId = card.dataset.giveawayId;

        const price = normalisePrice(item.price, 0);
        const hardMinQty = 10;
        const minQty = hardMinQty; // Force minimum quantity to 10 for giveaways
        const step = 1; // allow any whole number increment
        const defaultQty = Math.max(minQty, Math.floor(Number(item.default_qty ?? item.qty ?? minQty) || minQty));
        const stockQty = item.stock_qty !== undefined && item.stock_qty !== null ? Number(item.stock_qty) : null;
        const initialMax = item.max_qty !== undefined && item.max_qty !== null ? Number(item.max_qty) : null;
        const maxQty = null; // Allow unlimited quantity for giveaways (minimum enforced at 10)
        const imageSrc = extractImageSource(item.image) || placeholderImage;
        const previewUrl = item.preview_url || null;
        const description = item.description || '';
        const total = price * defaultQty;
        const designUrl = toCleanUrl(item.design_url || item.studio_url || item.designUrl || '');
        const materialLabelParts = [];
        if (item.material) materialLabelParts.push(item.material);
        if (item.material_type) materialLabelParts.push(item.material_type);
        const materialLabel = materialLabelParts.length ? materialLabelParts.join(' • ') : null;

        const isOutOfStock = stockQty !== null && stockQty === 0;
        const preOrderBadge = isOutOfStock ? '<span class="pre-order-badge">Pre-Order</span>' : '';

        card.innerHTML = `
            <div class="envelope-item__media">
                <img src="${imageSrc}" alt="${item.name ?? 'Giveaway'} preview" class="preview-trigger ${isOutOfStock ? 'out-of-stock' : ''}" ${previewUrl ? `data-preview-url="${previewUrl}"` : ''} loading="lazy" onerror="this.src='${placeholderImage}'; this.style.opacity='0.4';">
                ${preOrderBadge}
            </div>
            <h3 class="envelope-item__title ${isOutOfStock ? 'pre-order-title' : ''}">${item.name ?? 'Giveaway'}</h3>
            ${materialLabel ? `<p class="envelope-item__material">${materialLabel}</p>` : ''}
            ${description ? `<p class="envelope-item__meta">${description}</p>` : ''}
            <p class="envelope-item__price">${price ? `${formatMoney(price)} <span class="per-tag">per piece</span>` : '<span class="is-muted">Pricing on request</span>'}</p>
            <div class="envelope-item__controls">
                <div class="quantity-control">
                    <div class="quantity-input-group">
                        <label for="qty-${card.dataset.giveawayId}">Quantity</label>
                        <div class="quantity-input-wrapper">
                            <input
                                type="number"
                                id="qty-${card.dataset.giveawayId}"
                                class="quantity-input"
                                data-qty-input
                                min="${minQty}"
                                ${maxQty ? `max="${maxQty}"` : ''}
                                step="${step}"
                                value="${defaultQty}"
                            />
                            <span class="quantity-total" data-total-display>${formatMoney(total)}</span>
                        </div>
                    </div>
                    <p class="quantity-helper summary-note" data-qty-helper>Min ${minQty}${maxQty ? `, Max ${maxQty}${stockQty !== null && maxQty === stockQty ? ' (stock)' : ''}` : ''}</p>
                </div>
                <div class="control-buttons">
                    <button class="btn btn-secondary envelope-item__design" type="button" title="Add/Edit My Design" ${designUrl ? `data-design-url="${designUrl}"` : ''}>
                        <i class="fas fa-edit"></i> Design
                    </button>
                    <button class="primary-action envelope-item__select ${isOutOfStock ? 'pre-order-btn' : ''}" type="button">${isOutOfStock ? 'Pre-Order' : 'Select giveaway'}</button>
                </div>
            </div>
        `;

        const qtyInput = card.querySelector('[data-qty-input]');
        const totalDisplay = card.querySelector('[data-total-display]');
        const selectBtn = card.querySelector('.envelope-item__select');
        const helper = card.querySelector('[data-qty-helper]');

        const setHelper = (message, isAlert = false) => {
            if (!helper) return;
            helper.textContent = message;
            helper.classList.toggle('is-alert', Boolean(isAlert));
        };

        const baseHelperMessage = isOutOfStock 
            ? `Min ${minQty} • Pre-order available (15 days delivery)`
            : `Min ${minQty}${maxQty ? `, Max ${maxQty}${stockQty !== null && maxQty === stockQty ? ' (stock)' : ''}` : ''}`;
        setHelper(baseHelperMessage, false);

        const enforceQuantityBounds = (rawQty) => {
            let quantity = Math.floor(Number(rawQty));
            if (!Number.isFinite(quantity)) quantity = minQty;
            if (quantity < minQty) quantity = minQty;
            let message = null;
            if (maxQty && quantity > maxQty) {
                quantity = maxQty;
                message = `Maximum allowed is ${maxQty} based on available stock.`;
            }
            return { quantity, message };
        };

        const computeTotal = (qty) => {
            const quantity = Math.max(minQty, Math.floor(Number(qty) || minQty));
            
            // Find matching tier if available
            let currentPrice = price;
            if (item.tiers && item.tiers.length > 0) {
                const matchingTier = item.tiers.find(tier => {
                    const min = tier.min_qty !== null ? Number(tier.min_qty) : null;
                    const max = tier.max_qty !== null ? Number(tier.max_qty) : null;
                    return (min === null || quantity >= min) && (max === null || quantity <= max);
                });
                if (matchingTier && matchingTier.price) {
                    currentPrice = Number(matchingTier.price);
                }
            }

            const computedTotal = currentPrice * quantity;
            if (totalDisplay) {
                totalDisplay.textContent = formatMoney(computedTotal);
            }
            
            // Update price label if it changed
            const priceLabel = card.querySelector('.price-label');
            if (priceLabel && item.tiers && item.tiers.length > 0) {
                priceLabel.innerHTML = `${formatMoney(currentPrice)} <span>per piece</span>`;
            }

            return { quantity, total: computedTotal, unitPrice: currentPrice };
        };

        qtyInput?.addEventListener('input', () => {
            const raw = qtyInput.value;
            // Allow typing the first digit(s) without immediately snapping to the minimum
            if (raw === '' || raw.length < String(minQty).length) {
                isMinQuantityValid = false;
                applyContinueDisabled();
                setHelper('Minimum quantity is 10', true);
                return;
            }

            const { quantity, message } = enforceQuantityBounds(raw);
            const isValid = Number(quantity) >= minQty;
            isMinQuantityValid = isValid;
            applyContinueDisabled();

            qtyInput.value = quantity;
            if (message) {
                setHelper(message, true);
                showToast(message);
            } else if (!isValid) {
                setHelper('Minimum quantity is 10', true);
            } else {
                setHelper(baseHelperMessage, false);
            }
            computeTotal(quantity);
        });

        qtyInput?.addEventListener('change', () => {
            const { quantity, message } = enforceQuantityBounds(qtyInput.value);
            isMinQuantityValid = Number(quantity) >= minQty;
            applyContinueDisabled();
            qtyInput.value = quantity;
            if (message) {
                setHelper(message, true);
                showToast(message);
            } else {
                setHelper(baseHelperMessage, false);
            }

            const { total: computedTotal, unitPrice: currentPrice } = computeTotal(quantity);
            if (state.selectedIds.has(String(card.dataset.giveawayId))) {
                selectGiveaway({ ...item, price: currentPrice }, quantity, computedTotal, {
                    silent: true,
                    cardElement: card,
                    triggerButton: selectBtn,
                });
            }
        });

        selectBtn?.addEventListener('click', async () => {
            const giveawayId = String(card.dataset.giveawayId);
            const isCurrentlySelected = state.selectedIds.has(giveawayId);

            const removeLocalSelection = () => {
                const existingSummary = readSummary() ?? {};
                const giveaways = existingSummary.giveaways ? { ...existingSummary.giveaways } : {};
                delete giveaways[giveawayId];

                // Remove from confirmed pre-orders if it was a pre-order
                confirmedPreOrderIds.delete(giveawayId);

                const extras = existingSummary.extras ?? {};
                const giveawaysTotal = Object.values(giveaways).reduce((sum, g) => sum + (Number(g.total) || 0), 0);
                existingSummary.giveaways = giveaways;
                existingSummary.extras = {
                    paper: Number(extras.paper ?? 0),
                    addons: Number(extras.addons ?? 0),
                    envelope: Number(extras.envelope ?? 0),
                    giveaway: giveawaysTotal,
                };

                writeSummary(existingSummary);
                syncSelectionState(existingSummary);
            };

            // Instant toggle
            selectBtn.textContent = isCurrentlySelected ? 'Select giveaway' : 'Unselect giveaway';

            if (isCurrentlySelected) {
                removeLocalSelection();
                // Fire and forget server clear
                clearGiveawaySelection(giveawayId).then((result) => {
                    if (result?.ok && result.data?.summary) {
                        applyServerSummary(result.data.summary);
                    }
                }).catch(() => {
                    /* ignore errors; UI already updated locally */
                });
                showToast(`${item.name} removed from selection`);
                return;
            }

            const { quantity, message } = enforceQuantityBounds(qtyInput?.value || minQty);
            qtyInput.value = quantity;
            if (message) {
                setHelper(message, true);
                showToast(message);
            } else {
                setHelper(baseHelperMessage, false);
            }
            const { total: computedTotal, unitPrice: currentPrice } = computeTotal(quantity);

            // Immediate apply
            selectGiveaway({ ...item, price: currentPrice }, quantity, computedTotal, {
                cardElement: card,
                triggerButton: selectBtn,
            }).catch(() => {
                /* ignore errors; local state already applied */
            });
        });

        const designBtn = card.querySelector('.envelope-item__design');
        designBtn?.addEventListener('click', () => {
            if (!designBtn) {
                return;
            }

            const targetUrl = toCleanUrl(designBtn.dataset.designUrl || '');
            if (targetUrl) {
                window.location.href = targetUrl;
                return;
            }

            showToast('Studio access is unavailable for this giveaway.');
        });

        document.dispatchEvent(new CustomEvent('preview:register-triggers'));

        const image = card.querySelector('.giveaway-card__image');
        if (image) {
            image.addEventListener('error', () => {
                console.error('[giveaways] image failed to load, falling back to placeholder', { src: image.src, id: card.dataset.giveawayId });
                if (image.src === placeholderImage) return;
                image.src = placeholderImage;
            }, { once: true });
        }

        return card;
    };

    const renderCards = (items) => {
        if (!giveawayGrid) return;
        giveawayGrid.innerHTML = '';

        if (!items.length) {
            if (emptyState) emptyState.hidden = false;
            return;
        }

        if (emptyState) emptyState.hidden = true;

        items.forEach((item) => {
            const card = createCard(item);
            giveawayGrid.appendChild(card);
        });

        highlightSelectedCard();
    };

    const selectGiveaway = async (item, quantity, total, options = {}) => {
        // Check stock availability
        const stockQty = item.stock_qty ?? null;
        const itemId = String(item.product_id ?? item.id);
        if (stockQty !== null && stockQty === 0 && !confirmedPreOrderIds.has(itemId)) {
            // Out of stock - show pre-order modal and store selection for later
            pendingPreOrderSelection = { item, quantity, total, options };
            preOrderModal.removeAttribute('aria-hidden');
            preOrderModal.style.display = 'flex';
            preOrderConfirm.focus();
            return; // Don't proceed with selection until confirmed
        }

        // Clear any pending pre-order selection
        pendingPreOrderSelection = null;

        // Immediate UI update — no loading state
        const normalizedImages = normalizeImageArray(item.images);
        const primaryImage = extractImageSource(item.image) || normalizedImages[0] || placeholderImage;

        const applyLocalSelection = () => {
            const existingSummary = readSummary() ?? {};
            const giveawayMeta = {
                id: item.product_id ?? item.id,
                product_id: item.product_id ?? item.id,
                name: item.name ?? 'Giveaway',
                price: normalisePrice(item.price, 0),
                qty: quantity,
                total: Number(total) || 0,
                image: primaryImage,
                description: item.description ?? '',
                max_qty: item.max_qty ?? null,
                min_qty: item.min_qty ?? null,
                material: item.material ?? null,
                material_type: item.material_type ?? null,
                stock_qty: item.stock_qty ?? null,
                is_preorder: (item.stock_qty !== null && item.stock_qty === 0) || confirmedPreOrderIds.has(String(item.product_id ?? item.id)),
                updated_at: new Date().toISOString(),
            };

            console.log('[giveaways] Applying local selection', { giveawayMeta, existingSummary });

            // Handle multiple giveaways locally
            let giveaways = existingSummary.giveaways || {};
            // Migrate old single giveaway if exists
            if (Object.keys(giveaways).length === 0 && existingSummary.giveaway) {
                const oldId = existingSummary.giveaway.product_id || existingSummary.giveaway.id;
                if (oldId) {
                    giveaways[oldId] = existingSummary.giveaway;
                }
            }
            
            giveaways[giveawayMeta.product_id] = giveawayMeta;
            existingSummary.giveaways = giveaways;
            delete existingSummary.giveaway;

            const extras = existingSummary.extras ?? {};
            const giveawaysTotal = Object.values(giveaways).reduce((sum, g) => sum + (Number(g.total) || 0), 0);
            
            existingSummary.extras = {
                paper: Number(extras.paper ?? 0),
                addons: Number(extras.addons ?? 0),
                envelope: Number(extras.envelope ?? 0),
                giveaway: giveawaysTotal,
            };

            writeSummary(existingSummary);
            console.log('[giveaways] Summary written to storage', existingSummary);
            syncSelectionState(existingSummary);
        };

        // Apply immediately for instant toggle
        applyLocalSelection();

        const payload = {
            product_id: item.product_id ?? item.id,
            quantity,
            unit_price: item.price,
            total_price: total,
            metadata: {
                id: item.product_id ?? item.id,
                name: item.name,
                image: primaryImage,
                images: normalizedImages.length ? normalizedImages : undefined,
                description: item.description,
                min_qty: item.min_qty,
                max_qty: item.max_qty,
                material: item.material,
                material_type: item.material_type,
                stock_qty: item.stock_qty,
                preview_url: item.preview_url,
            },
        };

        const result = await persistGiveawaySelection(payload);
        
        if (result?.ok) {
            if (result.data?.summary) {
                applyServerSummary(result.data.summary);
            } else {
                await fetchSummaryFromServer();
            }
            
            if (!options.silent) {
                showToast(`${item.name} added — ${quantity} pcs for ${formatMoney(total)}`);
            }
            return;
        }

        const status = result?.status ?? 0;
        if (status === 409 || status === 422) {
            showToast('That giveaway is no longer available. Refreshing options…');
            await loadGiveaways();
            await fetchSummaryFromServer();
            return;
        }

        // Even if server fails, we already applied local selection for immediate feedback
        if (!options.silent) {
            showToast(`${item.name} selected — ${quantity} pcs for ${formatMoney(total)}`);
        }
    };

    const loadGiveaways = async ({ useSkeleton = true } = {}) => {
        if (useSkeleton) {
            showSkeleton(state.skeletonCount);
        }
        try {
            const url = new URL(optionsUrl, window.location.origin);
            url.searchParams.set('_', Date.now().toString());

            const response = await fetch(url.toString(), {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (response.ok) {
                const data = await response.json();
                if (Array.isArray(data) && data.length) {
                    state.items = data.map((item) => {
                        const normalizedImages = normalizeImageArray(item.images);
                        const primaryImage = extractImageSource(item.image)
                            || normalizedImages[0]
                            || placeholderImage;

                        const stockQty = item.stock_qty !== undefined && item.stock_qty !== null ? Number(item.stock_qty) : null;
                        const resolvedMax = stockQty !== null ? stockQty : null;

                        return {
                            id: item.id ?? item.product_id,
                            product_id: item.product_id ?? item.id,
                            name: item.name ?? 'Giveaway',
                            price: normalisePrice(item.price, 0),
                            tiers: item.tiers || [],
                            image: primaryImage,
                            images: normalizedImages,
                            description: item.description,
                            min_qty: item.min_qty,
                            max_qty: resolvedMax,
                            stock_qty: stockQty,
                            material: item.material,
                            material_type: item.material_type,
                            step: item.step ?? 5,
                            default_qty: item.default_qty ?? item.min_qty ?? 10,
                            preview_url: item.preview_url,
                            event_type: item.event_type,
                            theme_style: item.theme_style,
                            design_url: item.design_url || item.studio_url || null,
                        };
                    });
                    // Keep out-of-stock items visible as pre-order options
                    // state.items = state.items.filter((item) => {
                    //     if (item.stock_qty === null || item.stock_qty === undefined) return true;
                    //     return item.stock_qty > 0;
                    // });
                } else if (initialCatalog.length) {
                    state.items = initialCatalog;
                } else {
                    state.items = sampleGiveaways;
                }
            } else {
                console.warn('Giveaway API returned status', response.status);
                state.items = initialCatalog.length ? initialCatalog : sampleGiveaways;
                setBadgeState({ label: 'Offline', tone: 'summary-badge--alert' });
            }
        } catch (error) {
            console.error('Error loading giveaways', error);
            state.items = initialCatalog.length ? initialCatalog : sampleGiveaways;
            setBadgeState({ label: 'Offline', tone: 'summary-badge--alert' });
        } finally {
            if (useSkeleton) {
                clearSkeleton();
            }
            renderCards(state.items);
            syncSelectionState();
        }
    };

    continueBtn?.addEventListener('click', async () => {
        if (continueBtn.disabled) return;

        // Normalize summary for downstream pages and sync to server before leaving
        const summary = readSummary() ?? {};
        try {
            // Keep a canonical copy other pages already read (store minimal payload)
            try {
                const minSummary = {
                    productId: summary.productId ?? summary.product_id ?? null,
                    quantity: summary.quantity ?? null,
                    paymentMode: summary.paymentMode ?? summary.payment_mode ?? null,
                    totalAmount: summary.totalAmount ?? summary.total_amount ?? null,
                    shippingFee: summary.shippingFee ?? summary.shipping_fee ?? null,
                    order_id: summary.order_id ?? summary.orderId ?? null,
                };
                window.sessionStorage.setItem('order_summary_payload', JSON.stringify(minSummary));
            } catch (e) {
                console.warn('Failed to save minimal order_summary_payload to sessionStorage:', e);
            }

            const csrf = getCsrfToken();
            await fetch('/order/summary/sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ summary }),
            });
        } catch (error) {
            console.warn('Unable to sync giveaway summary before checkout', error);
        }

        const target = continueBtn.dataset.target || summaryUrl;
        window.location.href = target;
    });

    skipBtn?.addEventListener('click', () => {
        const target = skipBtn.dataset.target || summaryUrl;

        // Fire-and-forget clear so navigation is instant
        clearGiveawaySelection()
            .then((result) => {
                if (result?.ok && result.data?.summary) {
                    applyServerSummary(result.data.summary);
                }
                return null;
            })
            .catch((error) => {
                console.warn('Skipping giveaway failed, continuing anyway', error);
            });

        showToast('Continuing without a giveaway…');
        window.location.href = target;
    });

    // removeBtn listener removed as it is now handled per-item in syncSelectionState

    const initialise = async () => {
        setContinueState(true);
        const hasBootstrapData = initialCatalog.length > 0;

        if (hasBootstrapData) {
            renderCards(initialCatalog);
        }

        syncSelectionState();
        await fetchSummaryFromServer();

        // Always refresh from API so stock-aware maxima stay current (even if HTML had bootstrap data)
        await loadGiveaways({ useSkeleton: !hasBootstrapData });

        // Add local search filtering
        const searchInput = document.getElementById('desktop-giveaway-search') || document.getElementById('mobile-giveaway-search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                const query = e.target.value.toLowerCase().trim();
                const filtered = state.items.filter(item => 
                    (item.name && item.name.toLowerCase().includes(query)) ||
                    (item.description && item.description.toLowerCase().includes(query)) ||
                    (item.event_type && item.event_type.toLowerCase().includes(query)) ||
                    (item.theme_style && item.theme_style.toLowerCase().includes(query))
                );
                renderCards(filtered);
            });

            // Prevent form submission if we are filtering locally
            const searchForm = searchInput.closest('form');
            if (searchForm) {
                searchForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                });
            }
        }
    };

    // Modal event listeners
    if (preOrderConfirm) {
        preOrderConfirm.addEventListener('click', async () => {
            isPreOrderConfirmed = true;
            if (pendingPreOrderSelection) {
                const itemId = String(pendingPreOrderSelection.item.product_id ?? pendingPreOrderSelection.item.id);
                confirmedPreOrderIds.add(itemId);
            }
            preOrderModal.setAttribute('aria-hidden', 'true');
            preOrderModal.style.display = 'none';

            // If there's a pending pre-order selection, proceed with it now
            if (pendingPreOrderSelection) {
                const { item, quantity, total, options } = pendingPreOrderSelection;
                pendingPreOrderSelection = null;

                // Proceed with the selection now that pre-order is confirmed
                await selectGiveaway(item, quantity, total, options);
            }

            // Note: For giveaways, we don't adjust dates like in finalstep
            // as giveaways don't have date selection
        });
    }

    if (preOrderCancel) {
        preOrderCancel.addEventListener('click', () => {
            isPreOrderConfirmed = false;
            pendingPreOrderSelection = null; // Clear pending selection
            preOrderModal.setAttribute('aria-hidden', 'true');
            preOrderModal.style.display = 'none';
            // Show error message or handle cancellation
            showToast('Pre-order cancelled. Please select an in-stock giveaway.');
        });
    }

    // Close modal on backdrop click
    if (preOrderModal) {
        preOrderModal.addEventListener('click', (event) => {
            if (event.target === preOrderModal) {
                preOrderCancel.click();
            }
        });
    }

    initialise();
});
