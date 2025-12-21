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
    const emptyState = shell.querySelector('#giveawaysEmptyState');
    const summaryBody = shell.querySelector('#giveawaySummaryBody');
    const statusBadge = shell.querySelector('#giveawaysStatusBadge');
    const removeBtn = shell.querySelector('#giveawaysRemoveSelection');
    const skipBtn = shell.querySelector('#skipGiveawaysBtn');
    const continueBtn = shell.querySelector('#giveawaysContinueBtn');
    const toast = shell.querySelector('#giveawayToast');

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

    const extractImageSource = (candidate) => {
        if (!candidate) return '';
        if (typeof candidate === 'string') return candidate.trim();
        if (typeof candidate === 'object') {
            const keys = ['src', 'url', 'href', 'image', 'path', 'value'];
            for (const key of keys) {
                const value = candidate[key];
                if (typeof value === 'string' && value.trim().length) {
                    return value.trim();
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

    const initialCatalog = inlineCatalog.length
        ? inlineCatalog
        : (() => {
            if (!giveawayGrid) return [];
            return Array.from(giveawayGrid.querySelectorAll('[data-product-id]')).map((card) => {
                const quantityInput = card.querySelector('.giveaway-card__quantity');
                const totalDisplay = card.querySelector('[data-total-display]');
                const initialQty = Number(quantityInput?.value || card.dataset.defaultQty || 50);
                const unitPrice = Number.parseFloat(card.dataset.productPrice || quantityInput?.dataset.price || 0) || 0;
                const rawImage = card.dataset.productImage || card.querySelector('img')?.getAttribute('src');
                return {
                    id: card.dataset.productId ?? card.dataset.giveawayId,
                    product_id: card.dataset.productId ?? card.dataset.giveawayId,
                    name: card.dataset.productName || card.getAttribute('data-product-name') || card.querySelector('h2')?.textContent || 'Giveaway',
                    price: unitPrice,
                    image: extractImageSource(rawImage) || placeholderImage,
                    description: card.dataset.description || card.querySelector('p')?.textContent || '',
                    min_qty: Number(card.dataset.minQty ?? card.dataset.defaultQty ?? initialQty) || 1,
                    max_qty: Number(card.dataset.maxQty || 0) || null,
                    step: Number(card.dataset.step || quantityInput?.step || 10) || 10,
                    default_qty: initialQty,
                    preview_url: card.dataset.previewUrl || card.querySelector('.preview-trigger')?.dataset.previewUrl || null,
                    total_label: totalDisplay?.textContent || null,
                };
            });
        })();

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
        selectedId: null,
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

    const setContinueState = (disabled) => {
        if (!continueBtn) return;
        continueBtn.disabled = Boolean(disabled);
    };

    const setRemoveState = (hidden) => {
        if (!removeBtn) return;
        removeBtn.hidden = Boolean(hidden);
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

    const clearGiveawaySelection = async () => {
        if (!clearUrl) return { ok: false };

        const csrfToken = getCsrfToken();

        try {
            const response = await fetch(clearUrl, {
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

    const buildSummaryMarkup = (giveaway) => {
        const total = giveaway.total ?? (giveaway.price * (giveaway.qty || 0));
        const description = giveaway.description ?? '';
        return `
            <div class="summary-selection">
            <div class="summary-selection__media">
                <img src="${extractImageSource(giveaway.image) || placeholderImage}" alt="${giveaway.name}">
                </div>
                <div class="summary-selection__main">
                    <p class="summary-selection__name">${giveaway.name}</p>
                    ${description ? `<p class="summary-selection__meta">${description}</p>` : ''}
                </div>
            </div>
            <ul class="summary-list">
                <li class="summary-list__item"><dt>Total Quantity:</dt><dd>${giveaway.qty} pcs</dd></li>
                <li class="summary-list__item"><dt>Total Cost:</dt><dd>${formatMoney(total)}</dd></li>
            </ul>
        `;
    };

    const highlightSelectedCard = () => {
        if (!giveawayGrid) return;
        giveawayGrid.querySelectorAll('.giveaway-card').forEach((card) => {
            const isSelected = card.dataset.giveawayId === state.selectedId;
            card.classList.toggle('is-selected', isSelected);
            const button = card.querySelector('.envelope-item__select');
            if (button) {
                button.textContent = isSelected ? 'Unselect giveaway' : 'Select giveaway';
                button.disabled = state.isSaving && isSelected;
            }
        });
    };

    const syncSelectionState = (summaryOverride = null) => {
        const summary = summaryOverride ?? readSummary() ?? {};
        const giveaway = summary.giveaway;

        console.log('[giveaways] syncSelectionState called', { summary, giveaway });

        state.selectedId = giveaway?.product_id
            ? String(giveaway.product_id)
            : giveaway?.id
                ? String(giveaway.id)
                : null;

        if (!giveaway) {
            if (summaryBody) {
                summaryBody.innerHTML = '<p class="summary-placeholder">Choose a giveaway to see its details here.</p>';
            }
            setBadgeState({ label: 'Pending' });
            setContinueState(true);
            setRemoveState(true);
            highlightSelectedCard();
            return;
        }

        if (summaryBody) {
            summaryBody.innerHTML = buildSummaryMarkup(giveaway);
        }
        setBadgeState({ label: 'Selected', tone: 'summary-badge--success' });
        setContinueState(false);
        setRemoveState(false);
        highlightSelectedCard();
    };

    const createCard = (item) => {
        const card = document.createElement('article');
        card.className = 'giveaway-card';
        card.dataset.giveawayId = String(item.id ?? item.product_id ?? '');
        card.dataset.productId = card.dataset.giveawayId;

        const price = normalisePrice(item.price, 0);
        const minQty = Number(item.min_qty ?? 1) || 1;
        const step = Number(item.step ?? 5) || 5;
        const defaultQty = Number(item.default_qty ?? item.qty ?? minQty) || minQty;
        const maxQty = item.max_qty ? Number(item.max_qty) : null;
        const imageSrc = extractImageSource(item.image) || placeholderImage;
        if (imageSrc === placeholderImage) {
            console.warn('[giveaways] card using placeholder image', {
                id: item.id ?? item.product_id,
                product_id: item.product_id,
                provided_image: item.image,
                normalized_images: normalizeImageArray(item.images),
                preview_url: item.preview_url || null,
            });
        }
        const previewUrl = item.preview_url || null;
        const description = item.description || '';
        const total = price * defaultQty;
        const designUrl = toCleanUrl(item.design_url || item.studio_url || item.designUrl || '');

        card.innerHTML = `
            <div class="giveaway-card__media">
                <button type="button" class="favorite-toggle" aria-label="Save ${item.name ?? 'giveaway'}" data-product-id="${card.dataset.giveawayId}">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 21s-6.5-4.35-9-8.5C1.33 9.5 2.15 6 5 4.8 7.38 3.77 9.55 4.89 12 7.4c2.45-2.51 4.62-3.63 7-2.6 2.85 1.2 3.68 4.7 2 7.7-2.5 4.15-9 8.5-9 8.5Z" />
                    </svg>
                </button>
                <img src="${imageSrc}" alt="${item.name ?? 'Giveaway'} preview" class="giveaway-card__image preview-trigger" ${previewUrl ? `data-preview-url="${previewUrl}"` : ''} loading="lazy">
            </div>
            <div class="giveaway-card__body">
                <div class="giveaway-card__meta-top">
                    ${item.event_type ? `<span class="meta-badge">${item.event_type}</span>` : ''}
                    ${item.theme_style ? `<span class="meta-chip">${item.theme_style}</span>` : ''}
                </div>
                <h2>${item.name ?? 'Giveaway'}</h2>
                ${description ? `<p>${description}</p>` : ''}
                <div class="giveaway-card__pricing">
                    <div class="price-label">${price ? `${formatMoney(price)} <span>per piece</span>` : '<span class="is-muted">Pricing on request</span>'}</div>
                    <div class="quantity-input-group">
                        <label>Quantity</label>
                        <div class="quantity-input-wrapper">
                            <input
                                type="number"
                                class="giveaway-card__quantity quantity-input"
                                data-qty-input
                                min="${minQty}"
                                ${maxQty ? `max="${maxQty}"` : ''}
                                step="${step}"
                                value="${defaultQty}"
                                inputmode="numeric"
                                pattern="[0-9]*"
                            />
                            <div class="qty-total" data-total-display>${formatMoney(total)}</div>
                        </div>
                    </div>
                </div>
                <div class="giveaway-card__actions">
                    <button class="btn btn-secondary envelope-item__design" type="button" title="Add/Edit My Design" ${designUrl ? `data-design-url="${designUrl}"` : ''}>
                        <i class="fas fa-edit"></i> Design
                    </button>
                    <button class="primary-action envelope-item__select" type="button">Select giveaway</button>
                </div>
            </div>
        `;

        const qtyInput = card.querySelector('[data-qty-input]');
        const totalDisplay = card.querySelector('[data-total-display]');
        const selectBtn = card.querySelector('.envelope-item__select');

        const computeTotal = (qty) => {
            const quantity = Number(qty) || minQty;
            const computedTotal = price * quantity;
            if (totalDisplay) {
                totalDisplay.textContent = formatMoney(computedTotal);
            }
            return { quantity, total: computedTotal };
        };

        qtyInput?.addEventListener('input', () => {
            const { quantity } = computeTotal(qtyInput.value);
            if (maxQty && quantity > maxQty) {
                qtyInput.value = String(maxQty);
                computeTotal(maxQty);
            }
        });

        qtyInput?.addEventListener('change', () => {
            const { quantity, total: computedTotal } = computeTotal(qtyInput.value);
            if (state.selectedId === String(card.dataset.giveawayId)) {
                selectGiveaway(item, quantity, computedTotal, {
                    silent: true,
                    cardElement: card,
                    triggerButton: selectBtn,
                });
            }
        });

        selectBtn?.addEventListener('click', async () => {
            const giveawayId = String(card.dataset.giveawayId);
            const isCurrentlySelected = state.selectedId === giveawayId;

            // Immediately toggle button text and disable to prevent double-clicks
            selectBtn.textContent = isCurrentlySelected ? 'Select giveaway' : 'Unselect giveaway';
            selectBtn.disabled = true;

            try {
                if (isCurrentlySelected) {
                    // Remove this giveaway from selection
                    const result = await clearGiveawaySelection();
                    if (result?.ok) {
                        if (result.data?.summary) {
                            applyServerSummary(result.data.summary);
                        } else {
                            // Fallback: manually clear the state
                            state.selectedId = null;
                            highlightSelectedCard();
                            setContinueState(false);
                        }
                        showToast(`${item.name} removed from selection`);
                    } else {
                        // Revert button text on failure
                        selectBtn.textContent = 'Unselect giveaway';
                        showToast('Unable to remove giveaway. Please try again.');
                    }
                } else {
                    const quantity = Number(qtyInput?.value || minQty) || minQty;
                    const { total: computedTotal } = computeTotal(quantity);
                    const result = await selectGiveaway(item, quantity, computedTotal, {
                        cardElement: card,
                        triggerButton: selectBtn,
                    });
                    if (!result || !result.ok) {
                        // Revert button text on failure
                        selectBtn.textContent = 'Select giveaway';
                    }
                }
            } finally {
                selectBtn.disabled = false;
            }
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
        if (state.isSaving) return;
        state.isSaving = true;
        setContinueState(true);

        const card = options.cardElement ?? giveawayGrid?.querySelector(`[data-giveaway-id="${item.id}"]`);
        const triggerButton = options.triggerButton ?? card?.querySelector('.envelope-item__select');

        let originalButtonText;
        if (card) {
            card.classList.add('is-saving');
        }
        if (triggerButton) {
            originalButtonText = triggerButton.textContent;
            triggerButton.textContent = options.silent ? 'Updating…' : 'Saving…';
            triggerButton.disabled = true;
        }
        removeBtn?.setAttribute('disabled', 'true');

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
                updated_at: new Date().toISOString(),
            };

            console.log('[giveaways] Applying local selection', { giveawayMeta, existingSummary });

            existingSummary.giveaway = giveawayMeta;
            const extras = existingSummary.extras ?? {};
            existingSummary.extras = {
                paper: Number(extras.paper ?? 0),
                addons: Number(extras.addons ?? 0),
                envelope: Number(extras.envelope ?? 0),
                giveaway: Number(giveawayMeta.total ?? 0),
            };

            writeSummary(existingSummary);
            console.log('[giveaways] Summary written to storage', existingSummary);
            syncSelectionState(existingSummary);
        };

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
                preview_url: item.preview_url,
            },
        };

        const result = await persistGiveawaySelection(payload);

        if (triggerButton) {
            triggerButton.disabled = false;
            triggerButton.textContent = originalButtonText ?? 'Select giveaway';
        }
        if (card) {
            card.classList.remove('is-saving');
        }
        removeBtn?.removeAttribute('disabled');
        state.isSaving = false;

        // Always apply local selection first for immediate UI feedback
        applyLocalSelection();
        
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

    const loadGiveaways = async () => {
        showSkeleton(state.skeletonCount);
        try {
            const response = await fetch(optionsUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (response.ok) {
                const data = await response.json();
                if (Array.isArray(data) && data.length) {
                    state.items = data.map((item) => {
                        const normalizedImages = normalizeImageArray(item.images);
                        const primaryImage = extractImageSource(item.image)
                            || normalizedImages[0]
                            || placeholderImage;

                        return {
                        id: item.id ?? item.product_id,
                        product_id: item.product_id ?? item.id,
                        name: item.name ?? 'Giveaway',
                        price: normalisePrice(item.price, 0),
                        image: primaryImage,
                        images: normalizedImages,
                        description: item.description,
                        min_qty: item.min_qty,
                        max_qty: item.max_qty,
                        step: item.step ?? 5,
                        default_qty: item.default_qty ?? item.min_qty ?? 10,
                        preview_url: item.preview_url,
                        event_type: item.event_type,
                        theme_style: item.theme_style,
                        };
                    });
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
            clearSkeleton();
            renderCards(state.items);
            syncSelectionState();
        }
    };

    continueBtn?.addEventListener('click', () => {
        const target = continueBtn.dataset.target || summaryUrl;
        if (!continueBtn.disabled) {
            window.location.href = target;
        }
    });

    skipBtn?.addEventListener('click', async () => {
        if (state.isSaving) return;

        state.isSaving = true;
        setContinueState(true);
        skipBtn.disabled = true;
        removeBtn?.setAttribute('disabled', 'true');

        const target = skipBtn.dataset.target || summaryUrl;

        // Try to clear any saved giveaway, but never block navigation.
        try {
            const result = await clearGiveawaySelection();

            if (result?.ok) {
                if (result.data?.summary) {
                    applyServerSummary(result.data.summary);
                } else {
                    await fetchSummaryFromServer();
                }
            }
        } catch (error) {
            console.warn('Skipping giveaway failed, continuing anyway', error);
        }

        skipBtn.disabled = false;
        removeBtn?.removeAttribute('disabled');
        state.isSaving = false;

        showToast('Continuing without a giveaway…');
        window.setTimeout(() => {
            window.location.href = target;
        }, 250);
    });

    removeBtn?.addEventListener('click', async () => {
        if (state.isSaving || removeBtn.hidden) return;

        state.isSaving = true;
        setContinueState(true);
        removeBtn.disabled = true;

        const result = await clearGiveawaySelection();

        removeBtn.disabled = false;
        state.isSaving = false;

        if (result?.ok) {
            if (result.data?.summary) {
                applyServerSummary(result.data.summary);
            } else {
                await fetchSummaryFromServer();
            }
            showToast('Giveaway removed from your order.');
            return;
        }

        const status = result?.status ?? 0;
        if (status === 409 || status === 422) {
            showToast('That giveaway is no longer available. Refreshing options…');
            await loadGiveaways();
            const refreshed = await fetchSummaryFromServer();
            if (!refreshed) {
                syncSelectionState();
            }
            return;
        }

        showToast('Unable to remove giveaway. Please try again.');
        syncSelectionState();
    });

    const initialise = async () => {
        setContinueState(true);
        const hasBootstrapData = initialCatalog.length > 0;

        if (hasBootstrapData) {
            renderCards(initialCatalog);
        }

        syncSelectionState();
        await fetchSummaryFromServer();

        if (!hasBootstrapData) {
            await loadGiveaways();
        }
    };

    initialise();
});
