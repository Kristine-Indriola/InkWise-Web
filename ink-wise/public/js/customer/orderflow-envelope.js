document.addEventListener('DOMContentLoaded', () => {
  const shell = document.querySelector('.envelope-shell');
  if (!shell) return;

  const envelopeGrid = shell.querySelector('#envelopeGrid');
  const summaryBody = shell.querySelector('#envelopeSummaryBody');
  const selectionBadge = shell.querySelector('#envSelectionBadge');
  const continueBtn = shell.querySelector('#envContinueBtn');
  const skipBtn = shell.querySelector('#skipEnvelopeBtn');
  const toast = shell.querySelector('#envToast');

  const inlineCatalog = (() => {
    const script = document.getElementById('envelopeCatalogData');
    if (!script) return [];
    try {
      const payload = script.textContent?.trim();
      const parsed = payload ? JSON.parse(payload) : [];
      script.remove();
      return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
      console.warn('Failed to parse inline envelope catalog', error);
      return [];
    }
  })();

  const summaryUrl = shell.dataset.summaryUrl || '/order/summary';
  const summaryApiUrl = shell.dataset.summaryApi || summaryUrl;
  const envelopesUrl = shell.dataset.envelopesUrl || '/api/envelopes';
  const giveawaysUrl = shell.dataset.giveawaysUrl || '/order/giveaways';
  const syncUrl = shell.dataset.syncUrl || '/order/envelope';
  const STORAGE_KEY = 'inkwise-finalstep';
  const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');

  if (continueBtn) {
    continueBtn.setAttribute('aria-disabled', 'true');
    continueBtn.style.pointerEvents = 'none';
    continueBtn.style.opacity = '0.6';
  }

  const sampleEnvelopes = [
    // Removed default samples as per request
  ];

  const state = {
    envelopes: inlineCatalog.length ? inlineCatalog : [],
    selectedIds: [], // Changed from selectedId to selectedIds array
    skeletonCount: Math.min(6, Math.max(3, Math.floor(((window.innerWidth || 1200) - 180) / 260))),
    isSaving: false
  };

  // Debounce utility for performance
  const debounce = (func, wait) => {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  };

  const formatMoney = (value) => new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(Number(value) || 0);

  const normalisePrice = (value) => {
    const numeric = Number.parseFloat(value);
    return Number.isFinite(numeric) ? numeric : 0;
  };

  const resolveEnvelopeImage = (envelope) => {
    const candidate = envelope?.image;
    if (candidate && !String(candidate).includes('no-image')) return candidate;
    const match = state.envelopes?.find((env) => String(env.id) === String(envelope?.id));
    if (match?.image) return match.image;
    return '/images/no-image.png';
  };

  const resolveMaterialLabel = (item) => {
    const parts = [item?.material, item?.material_type].filter(Boolean);
    return parts.join(' • ');
  };

  const readSummary = () => {
    try {
      const raw = window.sessionStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : {};
    } catch (error) {
      console.warn('Failed to parse stored summary', error);
      return {};
    }
  };

  const writeSummary = (summary) => {
    window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(summary));
  };

  const clearLocalEnvelopes = () => {
    const summary = readSummary() ?? {};
    summary.envelopes = [];
    summary.extras = summary.extras ?? { paper: 0, addons: 0, envelope: 0, giveaway: 0 };
    summary.extras.envelope = 0;
    writeSummary(summary);
    state.selectedIds = [];
    if (summaryBody) {
      summaryBody.innerHTML = '<p class="summary-empty">Choose an envelope to see the details here.</p>';
    }
    setBadgeState({ label: 'Pending' });
    setContinueState(true);
    highlightSelectedCard();
  };

  const applyLocalEnvelopeSelection = (env, qty, total, isRemoval = false) => {
    const summary = readSummary() ?? {};
    const price = normalisePrice(env.price);
    const resolvedTotal = Number.isFinite(total) ? total : price * qty;
    const imageSrc = env.image || '/images/no-image.png';
    const materialLabel = resolveMaterialLabel(env);

    const envelopeMeta = {
      id: env.id ?? null,
      name: env.name ?? 'Envelope',
      price,
      qty,
      total: resolvedTotal,
      image: imageSrc,
      material: env.material ?? null,
      material_type: env.material_type ?? null,
      material_label: materialLabel || null,
    };

    // Initialize envelopes array if it doesn't exist
    if (!summary.envelopes) {
      summary.envelopes = [];
    }

    if (isRemoval) {
      // Remove the envelope from the array
      summary.envelopes = summary.envelopes.filter(e => e.id !== env.id);
    } else {
      // Add or update the envelope in the array
      const existingIndex = summary.envelopes.findIndex(e => e.id === env.id);
      if (existingIndex >= 0) {
        summary.envelopes[existingIndex] = envelopeMeta;
      } else {
        summary.envelopes.push(envelopeMeta);
      }
    }

    // Update selected IDs
    state.selectedIds = summary.envelopes.map(e => String(e.id));

    // Calculate total for all envelopes
    const totalEnvelopeCost = summary.envelopes.reduce((sum, e) => sum + e.total, 0);
    summary.extras = summary.extras ?? { paper: 0, addons: 0, envelope: 0, giveaway: 0 };
    summary.extras.envelope = totalEnvelopeCost;

    writeSummary(summary);
    syncSelectionState(summary);
  };

  const revertLocalEnvelopeSelection = (env) => {
    // Revert a single envelope selection locally without clearing other selections
    const summary = readSummary() ?? {};
    if (!summary.envelopes || !Array.isArray(summary.envelopes)) return;
    summary.envelopes = summary.envelopes.filter(e => String(e.id) !== String(env.id));
    // Recompute envelope extras total
    const totalEnvelopeCost = summary.envelopes.reduce((sum, e) => sum + (Number(e.total) || 0), 0);
    summary.extras = summary.extras ?? { paper: 0, addons: 0, envelope: 0, giveaway: 0 };
    summary.extras.envelope = totalEnvelopeCost;
    writeSummary(summary);
    state.selectedIds = summary.envelopes.map(e => String(e.id));
    // Update UI parts
    syncSelectionState(summary);
  };

  const setContinueState = (disabled) => {
    if (!continueBtn) return;
    const isDisabled = Boolean(disabled);
    // Support both button and anchor variants
    if (continueBtn.tagName.toLowerCase() === 'a') {
      continueBtn.setAttribute('aria-disabled', isDisabled ? 'true' : 'false');
      continueBtn.classList.toggle('is-disabled', isDisabled);
      continueBtn.style.pointerEvents = isDisabled ? 'none' : '';
      continueBtn.style.opacity = isDisabled ? '0.6' : '';
    } else {
      continueBtn.disabled = isDisabled;
    }
  };

  const applyServerSummary = (summary) => {
    if (!summary || typeof summary !== 'object') {
      clearLocalEnvelopes();
      return;
    }

    writeSummary(summary);
    syncSelectionState(summary);
  };

  const getCsrfToken = () => csrfTokenMeta?.getAttribute('content') ?? null;

  const persistEnvelopeSelection = async (payload) => {
    if (!syncUrl) return { ok: false };

    const csrfToken = getCsrfToken();

    try {
      const response = await fetch(syncUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload)
      });

      if (!response.ok) {
        console.warn('Failed to persist envelope selection', response.status);
        return { ok: false, status: response.status };
      }

      let data = null;
      try {
        data = await response.json();
      } catch (error) {
        console.warn('Envelope selection response could not be parsed', error);
      }

      return { ok: true, data };
    } catch (error) {
      console.error('Error persisting envelope selection', error);
      return { ok: false, status: 0, error };
    }
  };

  const clearEnvelopeSelection = async () => {
    if (!syncUrl) return { ok: false };

    const csrfToken = getCsrfToken();

    try {
      const response = await fetch(syncUrl, {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
        },
        credentials: 'same-origin',
        keepalive: true
      });

      if (!response.ok) {
        console.warn('Failed to clear envelope selection', response.status);
        return { ok: false, status: response.status };
      }

      let data = null;
      try {
        data = await response.json();
      } catch (error) {
        console.warn('Envelope clear response could not be parsed', error);
      }

      return { ok: true, data };
    } catch (error) {
      console.error('Error clearing envelope selection', error);
      return { ok: false, status: 0, error };
    }
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
        console.warn('Envelope summary API returned status', response.status);
        clearLocalEnvelopes();
        return null;
      }

      const payload = await response.json();
      const summary = (payload && typeof payload === 'object' && payload.data)
        ? payload.data
        : payload;

      if (summary && typeof summary === 'object') {
        applyServerSummary(summary);
        return summary;
      }
    } catch (error) {
      console.error('Error fetching order summary for envelopes', error);
    }

    // If no summary returned, clear any stale local data
    clearLocalEnvelopes();
    return null;
  };

  const setBadgeState = ({ label, tone }) => {
    if (!selectionBadge) return;
    selectionBadge.textContent = label;
    selectionBadge.classList.remove('summary-badge--success', 'summary-badge--alert');
    if (tone) selectionBadge.classList.add(tone);
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
    if (!envelopeGrid) return;
    envelopeGrid.dataset.loading = 'true';
    envelopeGrid.innerHTML = '';
    for (let i = 0; i < count; i += 1) {
      const placeholder = document.createElement('div');
      placeholder.className = 'envelope-item envelope-item--placeholder';
      placeholder.innerHTML = `
        <div class="envelope-item__media"></div>
        <div></div>
        <div></div>
        <div class="envelope-item__controls"></div>
      `;
      envelopeGrid.appendChild(placeholder);
    }
  };

  const clearSkeleton = () => {
    if (!envelopeGrid) return;
    envelopeGrid.removeAttribute('data-loading');
  };

  const buildSummaryMarkup = (envelope) => {
    const total = envelope.total;
    const imgSrc = resolveEnvelopeImage(envelope);
    const detailParts = [];
    const materialLabel = resolveMaterialLabel(envelope) || envelope.material;
    if (materialLabel) detailParts.push(materialLabel);
    if (Number.isFinite(envelope.qty)) detailParts.push(`${envelope.qty} pcs`);
    if (Number.isFinite(total)) detailParts.push(formatMoney(total));
    const meta = detailParts.length ? detailParts.join(' • ') : 'Custom envelope';
    return `
      <div class="summary-selection">
        <div class="summary-selection__media">
          <img src="${imgSrc}" alt="${envelope.name}" onerror="this.style.opacity='0.3';this.src='/images/no-image.png'">
        </div>
        <div class="summary-selection__main">
          <p class="summary-selection__name">${envelope.name}</p>
          <p class="summary-selection__meta">${meta}</p>
        </div>
      </div>
      <ul class="summary-list">
        <li class="summary-list__item"><dt>Quantity</dt><dd>${envelope.qty} pcs</dd></li>
        <li class="summary-list__item"><dt>Unit price</dt><dd>${formatMoney(envelope.price)}</dd></li>
        <li class="summary-list__item"><dt>Total</dt><dd>${formatMoney(total)}</dd></li>
      </ul>
    `;
  };

  const buildSummaryMarkupMultiple = (envelopes) => {
    const totalCost = envelopes.reduce((sum, e) => sum + e.total, 0);
    const totalQuantity = envelopes.reduce((sum, e) => sum + e.qty, 0);

    let markup = `<h4 class="summary-title">Selected Envelopes (${envelopes.length})</h4>`;

    envelopes.forEach((envelope, index) => {
        const detailParts = [];
        const materialLabel = resolveMaterialLabel(envelope) || envelope.material;
        if (materialLabel) detailParts.push(materialLabel);
        if (Number.isFinite(envelope.qty)) detailParts.push(`${envelope.qty} pcs`);
        if (Number.isFinite(envelope.total)) detailParts.push(formatMoney(envelope.total));
        const meta = detailParts.length ? detailParts.join(' • ') : 'Custom envelope';

      markup += `
        <div class="summary-selection summary-selection--multiple">
          <div class="summary-selection__media">
            <img src="${resolveEnvelopeImage(envelope)}" alt="${envelope.name}" onerror="this.style.opacity='0.3';this.src='/images/no-image.png'">
          </div>
          <div class="summary-selection__main">
            <p class="summary-selection__name">${envelope.name}</p>
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

  const syncSelectionState = (summaryOverride = null) => {
    const summary = summaryOverride ?? readSummary() ?? {};
    const envelopes = summary.envelopes || [];

      // If summary claims selections that don't exist in the current catalog, treat as stale and clear
      if (envelopes.length && state.envelopes.length) {
        const catalogIds = new Set(state.envelopes.map((e) => String(e.id)));
        const hasValid = envelopes.some((e) => catalogIds.has(String(e.id)));
        if (!hasValid) {
          clearLocalEnvelopes();
          return;
        }
      }
    state.selectedIds = envelopes.map(e => String(e.id));

    if (!envelopes.length) {
      if (summaryBody) {
        summaryBody.innerHTML = '<p class="summary-empty">Choose envelopes to see the details here.</p>';
      }
      setBadgeState({ label: 'Pending' });
      setContinueState(true);
      highlightSelectedCard();
      return;
    }

    if (summaryBody) {
      summaryBody.innerHTML = buildSummaryMarkupMultiple(envelopes);
    }
    setBadgeState({ label: `${envelopes.length} Selected`, tone: 'summary-badge--success' });
    highlightSelectedCard();
    setContinueState(false);
  };

  const highlightSelectedCard = () => {
    if (!envelopeGrid) return;
    envelopeGrid.querySelectorAll('.envelope-item').forEach((card) => {
      const envelopeId = card.dataset.envelopeId;
      const isSelected = state.selectedIds.includes(envelopeId);
      card.classList.toggle('is-selected', isSelected);
      const btn = card.querySelector('.envelope-item__select');
      if (btn) {
        btn.textContent = isSelected ? 'Unselect envelope' : 'Select envelope';
        // Apply primary-action styling to both select and unselect buttons
        if (isSelected) {
          btn.className = 'primary-action envelope-item__select';
        } else {
          btn.className = 'primary-action envelope-item__select';
        }
      }
    });
  };

  const selectEnvelope = async (env, qty, total, options = {}) => {
    // Immediate apply; no loading state
    const card = options.cardElement ?? envelopeGrid?.querySelector(`[data-envelope-id="${env.id}"]`);
    const triggerButton = options.triggerButton ?? card?.querySelector('.envelope-item__select');

    const numericEnvelopeId = Number(env.id);
    const envelopeId = Number.isFinite(numericEnvelopeId) && numericEnvelopeId > 0
      ? numericEnvelopeId
      : null;
    const numericProductId = Number(env.product_id);
    const productId = Number.isFinite(numericProductId) && numericProductId > 0
      ? numericProductId
      : null;

    const payload = {
      product_id: productId,
      envelope_id: envelopeId,
      quantity: qty,
      unit_price: env.price,
      total_price: total,
      metadata: {
        id: envelopeId ?? env.id ?? null,
        name: env.name,
        material: env.material ?? null,
        material_type: env.material_type ?? null,
        image: env.image ?? null,
        min_qty: env.min_qty ?? 10,
        max_qty: env.max_qty ?? null
      }
    };

    // Optimistically reflect the selection locally so the UI updates even if the API is slow or fails.
    applyLocalEnvelopeSelection(env, qty, total);

    const result = await persistEnvelopeSelection(payload);

    if (triggerButton) {
      const selectedText = state.selectedIds.includes(String(env.id)) ? 'Unselect envelope' : 'Select envelope';
      triggerButton.textContent = selectedText;
      triggerButton.className = 'primary-action envelope-item__select';
    }

    if (result?.ok) {
      if (result.data?.summary) {
        applyServerSummary(result.data.summary);
        if (!options.silent) {
          showToast(`${env.name} added — ${qty} pcs for ${formatMoney(total)}`);
        }
        return;
      }

      const refreshed = await fetchSummaryFromServer();
      if (refreshed) {
        if (!options.silent) {
          showToast(`${env.name} added — ${qty} pcs for ${formatMoney(total)}`);
        }
        return;
      }
    }

    const status = result?.status ?? 0;
    if (status === 409 || status === 422) {
      showToast('That envelope is no longer available.');
      await loadEnvelopes();
      const refreshed = await fetchSummaryFromServer();
      if (!refreshed) {
        syncSelectionState();
      }
      return;
    }

    // Revert only the envelope that failed to persist instead of clearing all selections.
    revertLocalEnvelopeSelection(env);
    showToast('Unable to save envelope. Selection reverted for that item.');
  };

  const createCard = (env) => {
    const price = normalisePrice(env.price);
    const defaultMinQty = 10;
    const apiMinQty = env.min_qty || defaultMinQty;
    const apiMaxQty = env.stock_qty ?? env.max_qty ?? null;
    
    // Ensure min_qty doesn't exceed max_qty if max_qty is set
    let minQty = apiMinQty;
    if (apiMaxQty !== null && apiMaxQty !== undefined && minQty > apiMaxQty) {
      minQty = Math.max(1, apiMaxQty); // Set min to max if they're inverted, or 1 if max is 0
    }
    
    const initialQty = Math.max(minQty, apiMinQty); // Start with at least the minimum
    const placeholderImage = '/images/no-image.png';

    const card = document.createElement('div');
    card.className = 'envelope-item';
    card.dataset.envelopeId = env.id;

    const hasValidImage = env.image;
    const imgSrc = hasValidImage ? env.image : '/images/no-image.png';
    
    card.innerHTML = `
      <div class="envelope-item__media">
        <img src="${imgSrc}" alt="${env.name}" loading="lazy">
      </div>
      <h3 class="envelope-item__title">${env.name}</h3>
      <p class="envelope-item__price">${formatMoney(price)} <span class="per-tag">per piece</span></p>
      ${resolveMaterialLabel(env) ? `<p class="envelope-item__meta">${resolveMaterialLabel(env)}</p>` : ''}
      <div class="envelope-item__controls">
        <div class="quantity-control">
          <div class="quantity-input-group">
            <label for="qty-${env.id}">Quantity</label>
            <div class="quantity-input-wrapper">
                <div class="quantity-input-controls">
                  <input type="number" id="qty-${env.id}" data-id="${env.id}" value="${initialQty}" min="${minQty}" max="${apiMaxQty || ''}" step="1">
                </div>
              <span class="quantity-total" data-total-display>${formatMoney(price * initialQty)}</span>
            </div>
          </div>
          <div class="quantity-error" style="display: none;"></div>
          <p class="quantity-helper summary-note">Select a quantity. Minimum order is ${minQty}${apiMaxQty ? `, max ${apiMaxQty}` : ''}</p>
        </div>
        <div class="control-buttons">
          <button class="primary-action envelope-item__select" type="button">Select envelope</button>
        </div>
      </div>
    `;

    const qtyInput = card.querySelector('input[type="number"]');
    const addBtn = card.querySelector('.envelope-item__select');
    const totalDisplay = card.querySelector('[data-total-display]');
    const errorDisplay = card.querySelector('.quantity-error');

    const updateTotal = () => {
      const qty = Number(qtyInput.value) || initialQty;
      if (qty < 0) return { qty: initialQty, total: price * initialQty }; // Prevent negative values
      const total = price * qty;
      if (totalDisplay) totalDisplay.textContent = formatMoney(total);
      return { qty, total };
    };

    // Quantity input now uses direct numeric entry (no +/- buttons)

    const validateQuantity = () => {
      const qty = Number(qtyInput.value);
      const min = minQty;
      const max = apiMaxQty;

      if (isNaN(qty) || qty < min) {
        errorDisplay.textContent = `Quantity must be at least ${min}`;
        errorDisplay.style.display = 'block';
        qtyInput.classList.add('error');
        return false;
      }

      if (max && qty > max) {
        errorDisplay.textContent = `Maximum allowed is ${max} based on available stock.`;
        errorDisplay.style.display = 'block';
        qtyInput.classList.add('error');
        return false;
      }

      errorDisplay.style.display = 'none';
      qtyInput.classList.remove('error');
      return true;
    };

    if (qtyInput) {
      qtyInput.addEventListener('input', debounce(() => {
        updateTotal();
        validateQuantity();
        if (state.selectedIds.includes(String(env.id))) {
          const { qty, total } = updateTotal();
          selectEnvelope(env, qty, total, { silent: true, cardElement: card, triggerButton: addBtn });
        }
      }, 300));
    }

    // Design button removed for envelopes.

    addBtn?.addEventListener('click', async () => {
      const envelopeId = String(env.id);
      const isCurrentlySelected = state.selectedIds.includes(envelopeId);

      if (isCurrentlySelected) {
        // Remove locally first
        applyLocalEnvelopeSelection(env, 0, 0, true);
        showToast(`${env.name} removed from selection`);
        // Fire-and-forget server clear
        clearEnvelopeSelection().catch(() => {});
        return;
      }

      if (!validateQuantity()) {
        qtyInput.focus();
        return;
      }
      const { qty, total } = updateTotal();
      selectEnvelope(env, qty, total, { cardElement: card, triggerButton: addBtn }).catch(() => {});
    });

    return card;
  };

  const renderCards = (items = state.envelopes, options = {}) => {
    if (!envelopeGrid) return;
    envelopeGrid.innerHTML = '';

    if (!items.length) {
      const empty = document.createElement('div');
      empty.className = 'envelope-item envelope-item--empty';
      const message = options.emptyMessage ?? 'No stocks available.';
      empty.innerHTML = `<p>${message}</p>`;
      envelopeGrid.appendChild(empty);
      return;
    }

    // Use DocumentFragment for better performance
    const fragment = document.createDocumentFragment();
    items.forEach((env) => fragment.appendChild(createCard(env)));
    envelopeGrid.appendChild(fragment);

    // Load selected envelopes from summary
    const summary = readSummary() || {};
    const envelopes = summary.envelopes || [];
    state.selectedIds = envelopes.map(e => String(e.id));

    highlightSelectedCard();
  };

  const loadEnvelopes = async () => {
    const shouldShowSkeleton = !state.envelopes.length;
    if (shouldShowSkeleton) showSkeleton(state.skeletonCount);
    try {
      const response = await fetch(envelopesUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        if (response.ok) {
          const payload = await response.json();
          const data = Array.isArray(payload) ? payload : (Array.isArray(payload?.data) ? payload.data : []);
          if (Array.isArray(data) && data.length) {
            state.envelopes = data.map((item, index) => ({
            id: item.id ?? `env_${index}`,
            product_id: item.product_id,
            name: item.name ?? 'Envelope',
            price: normalisePrice(item.price),
            image: item.image,
            material: item.material,
            material_type: item.material_type,
            min_qty: item.min_qty ?? 10,
            max_qty: item.max_qty,
            stock_qty: item.stock_qty
          })).filter((item) => {
            if (item.stock_qty === null || item.stock_qty === undefined) return true;
            return item.stock_qty > 0;
          });
        } else {
          state.envelopes = [];
        }
      } else {
        console.warn('Envelope API returned status', response.status);
        state.envelopes = sampleEnvelopes;
        setBadgeState({ label: 'Offline', tone: 'summary-badge--alert' });
      }
    } catch (error) {
      console.error('Error loading envelopes', error);
      state.envelopes = [];
      setBadgeState({ label: 'Offline', tone: 'summary-badge--alert' });
    } finally {
      if (shouldShowSkeleton) clearSkeleton();
      if (!state.envelopes.length) {
        // No envelopes available
      }
      renderCards(state.envelopes);
      syncSelectionState();
    }
  };

  continueBtn?.addEventListener('click', (event) => {
    if (continueBtn.getAttribute('aria-disabled') === 'true') {
      event.preventDefault();
      return;
    }
    const target = continueBtn.dataset.summaryUrl || giveawaysUrl || summaryUrl;
    // Direct navigation for snappier transition
    window.location.assign(target);
  });

  skipBtn?.addEventListener('click', async () => {
    if (state.isSaving) return;

    state.isSaving = true;
    const target = skipBtn.dataset.summaryUrl || giveawaysUrl;

    // Fire-and-forget clear so navigation is instant
    clearEnvelopeSelection()
      .then((result) => {
        if (result?.ok && result.data?.summary) {
          applyServerSummary(result.data.summary);
        }
        return null;
      })
      .catch((error) => {
        console.warn('Skipping envelope failed, continuing anyway', error);
      });

    showToast('Continuing without an envelope…');
    window.location.href = target;
  });

  const initialise = async () => {
    await fetchSummaryFromServer();
    if (inlineCatalog.length) {
      renderCards(inlineCatalog);
      syncSelectionState();
    }
    await loadEnvelopes();
  };

  initialise();
});
