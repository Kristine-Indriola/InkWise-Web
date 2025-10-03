document.addEventListener('DOMContentLoaded', () => {
    const shell = document.querySelector('.giveaways-shell');
    if (!shell) return;

    const STORAGE_KEY = shell.dataset.storageKey || 'inkwise-finalstep';
    const summaryApiUrl = shell.dataset.summaryApi || shell.dataset.summaryUrl || '/order/summary';
    const cards = Array.from(document.querySelectorAll('.giveaway-card'));
    const grid = document.getElementById('giveawaysGrid');
    const searchInput = document.getElementById('giveawaysSearch');
    const eventFilter = document.getElementById('giveawaysEventFilter');
    const resultCount = document.getElementById('giveawaysResultCount');
    const emptyState = document.getElementById('giveawaysEmptyState');
    const summaryBody = document.getElementById('giveawaySummaryBody');
    const statusBadge = document.getElementById('giveawaysStatusBadge');
    const removeBtn = document.getElementById('giveawaysRemoveSelection');
    const skipBtn = document.getElementById('skipGiveawaysBtn');
    const continueBtn = document.getElementById('giveawaysContinueBtn');
    const toast = document.getElementById('giveawayToast');

    const FAVORITES_KEY = 'inkwise:order:giveaways:favorites';
    let favorites;

    try {
        favorites = new Set(JSON.parse(window.localStorage.getItem(FAVORITES_KEY) || '[]'));
    } catch (error) {
        console.warn('Unable to read giveaway favorites from storage', error);
        favorites = new Set();
    }

    let toastTimer;
    let cachedSelectionId = null;

    const numberFormatter = new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
    });

    const formatCurrency = (value) => numberFormatter.format(Number(value) || 0);

    const readSummary = () => {
        try {
            const raw = window.sessionStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : {};
        } catch (error) {
            console.warn('Unable to parse giveaway summary from storage', error);
            return {};
        }
    };

    const writeSummary = (summary) => {
        window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(summary));
    };

    const fetchSummaryFromServer = async () => {
        if (!summaryApiUrl) {
            return null;
        }

        try {
            const response = await fetch(summaryApiUrl, {
                headers: { Accept: 'application/json' }
            });

            if (!response.ok) {
                console.warn('Giveaways summary API returned status', response.status);
                return null;
            }

            const payload = await response.json();
            const summary = (payload && typeof payload === 'object' && payload.data)
                ? payload.data
                : payload;

            if (summary && typeof summary === 'object') {
                writeSummary(summary);
                return summary;
            }
        } catch (error) {
            console.error('Failed to fetch order summary for giveaways', error);
        }

        return null;
    };

    const setStatusBadge = (label, state = null) => {
        if (!statusBadge) return;
        statusBadge.textContent = label;
        statusBadge.classList.remove('is-success');
        if (state === 'success') {
            statusBadge.classList.add('is-success');
        }
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

    const updateResultCount = () => {
        if (!resultCount) return;
        const visibleCount = cards.filter((card) => !card.classList.contains('is-hidden')).length;
        resultCount.textContent = `${visibleCount} giveaway${visibleCount === 1 ? '' : 's'}`;
    };

    const applyFilters = () => {
        if (!cards.length) {
            if (emptyState) emptyState.hidden = false;
            if (resultCount) resultCount.textContent = '0 giveaways';
            return;
        }

        const query = (searchInput?.value || '').trim().toLowerCase();
        const event = (eventFilter?.value || 'all').toLowerCase();
        let visible = 0;

        cards.forEach((card) => {
            const name = (card.dataset.productName || '').toLowerCase();
            const description = (card.dataset.description || '').toLowerCase();
            const eventType = (card.dataset.eventType || '').toLowerCase();

            const matchesSearch = !query || name.includes(query) || description.includes(query);
            const matchesEvent = event === 'all' || eventType === event;
            const show = matchesSearch && matchesEvent;

            card.classList.toggle('is-hidden', !show);
            if (show) {
                visible += 1;
            }
        });

        if (emptyState) {
            emptyState.hidden = visible !== 0;
        }

        updateResultCount();
    };

    const highlightCards = () => {
        cards.forEach((card) => {
            const isSelected = cachedSelectionId && card.dataset.productId === cachedSelectionId;
            card.classList.toggle('is-selected', Boolean(isSelected));
            const addBtn = card.querySelector('.giveaway-card__select');
            if (addBtn) {
                addBtn.textContent = isSelected ? 'Selected' : 'Add to order';
            }
        });
    };

    const buildSummaryMarkup = (giveaway) => `
        <div class="summary-selection">
            <div class="summary-selection__media">
                <img src="${giveaway.image || '/images/no-image.png'}" alt="${giveaway.name}">
            </div>
            <div class="summary-selection__main">
                <p class="summary-selection__name">${giveaway.name}</p>
                <p class="summary-selection__meta">${giveaway.lead_time || 'Made to order'}${giveaway.event_type ? ` • ${giveaway.event_type}` : ''}</p>
            </div>
        </div>
        <dl class="summary-list">
            <div class="summary-list__item"><dt>Quantity</dt><dd>${giveaway.qty} pcs</dd></div>
            <div class="summary-list__item"><dt>Unit price</dt><dd>${formatCurrency(giveaway.unit_price)}</dd></div>
            <div class="summary-list__item"><dt>Subtotal</dt><dd>${formatCurrency(giveaway.total)}</dd></div>
        </dl>
    `;

    const updateSummary = () => {
        const summary = readSummary();
        const giveaway = summary?.giveaway;

        if (!summaryBody || !continueBtn || !removeBtn) return;

        if (!giveaway) {
            summaryBody.innerHTML = '<p class="summary-placeholder">Choose a giveaway to see its details here.</p>';
            setStatusBadge('Pending');
            removeBtn.hidden = true;
            continueBtn.disabled = true;
            cachedSelectionId = null;
            highlightCards();
            return;
        }

        summaryBody.innerHTML = buildSummaryMarkup(giveaway);
        setStatusBadge('Selected', 'success');
        removeBtn.hidden = false;
        continueBtn.disabled = false;
        cachedSelectionId = String(giveaway.id ?? giveaway.product_id ?? '');
        highlightCards();
    };

    const updateTotalsForCard = (card) => {
        const qtyInput = card.querySelector('.giveaway-card__quantity');
        const totalDisplay = card.querySelector('[data-total-display]');
        const price = Number(card.dataset.productPrice || 0);
        const step = Number(card.dataset.step || 1) || 1;
        const defaultQty = Number(card.dataset.defaultQty || step);

        if (!qtyInput || !totalDisplay) return;

        const sanitiseValue = () => {
            let value = Number.parseInt(qtyInput.value, 10);
            if (!Number.isFinite(value) || value <= 0) {
                value = defaultQty;
            }
            if (value % step !== 0) {
                value = Math.max(step, Math.round(value / step) * step);
            }
            qtyInput.value = value;
            const total = price * value;
            totalDisplay.textContent = `${value} pcs — ${formatCurrency(total)}`;
            return value;
        };

        sanitiseValue();

        qtyInput.addEventListener('change', sanitiseValue);
        qtyInput.addEventListener('blur', sanitiseValue);
    };

    const selectCardProduct = (card) => {
        const price = Number(card.dataset.productPrice || 0);
        const qtyInput = card.querySelector('.giveaway-card__quantity');
        const quantity = qtyInput ? Number.parseInt(qtyInput.value, 10) || Number(card.dataset.defaultQty || 50) : Number(card.dataset.defaultQty || 50);
        const total = price * quantity;

        const summary = readSummary();
        summary.giveaway = {
            id: card.dataset.productId,
            product_id: card.dataset.productId,
            name: card.dataset.productName,
            qty: quantity,
            unit_price: price,
            total,
            lead_time: card.dataset.leadTime,
            event_type: card.dataset.eventType,
            image: card.dataset.productImage
        };
        writeSummary(summary);
        showToast(`${card.dataset.productName} added to your order`);
        updateSummary();
    };

    const toggleFavorite = (button, active) => {
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    };

    const attachCardHandlers = (card) => {
        const addBtn = card.querySelector('.giveaway-card__select');
        const favoriteBtn = card.querySelector('.favorite-toggle');

        updateTotalsForCard(card);

        addBtn?.addEventListener('click', () => selectCardProduct(card));

        if (favoriteBtn) {
            const productId = favoriteBtn.dataset.productId;
            if (productId && favorites.has(productId)) {
                toggleFavorite(favoriteBtn, true);
            }
            favoriteBtn.addEventListener('click', () => {
                if (!productId) return;
                const isActive = favorites.has(productId);
                if (isActive) {
                    favorites.delete(productId);
                } else {
                    favorites.add(productId);
                }
                toggleFavorite(favoriteBtn, !isActive);
                window.localStorage.setItem(FAVORITES_KEY, JSON.stringify(Array.from(favorites)));
            });
        }
    };

    const handleRemoveSelection = () => {
        const summary = readSummary();
        if (summary.giveaway) {
            delete summary.giveaway;
            writeSummary(summary);
            showToast('Giveaway removed from your order');
            updateSummary();
        }
    };

    const handleNavigation = (button) => {
        const target = button?.dataset.target || shell.dataset.summaryUrl || '/order/summary';
        if (!target) return;
        window.location.href = target;
    };

    const handleSkip = () => {
        const summary = readSummary();
        if (summary.giveaway) {
            delete summary.giveaway;
            writeSummary(summary);
        }
        showToast('Skipping giveaways for now…');
        window.setTimeout(() => handleNavigation(skipBtn), 480);
    };

    const init = async () => {
        await fetchSummaryFromServer();

        cards.forEach(attachCardHandlers);
        applyFilters();
        updateSummary();

        searchInput?.addEventListener('input', () => {
            applyFilters();
        });

        eventFilter?.addEventListener('change', () => {
            applyFilters();
        });

        removeBtn?.addEventListener('click', handleRemoveSelection);
        skipBtn?.addEventListener('click', handleSkip);
        continueBtn?.addEventListener('click', () => handleNavigation(continueBtn));
    };

    init();
});
