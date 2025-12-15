document.addEventListener('DOMContentLoaded', () => {
  const shell = document.querySelector('.envelope-shell');
  if (!shell) return;

  const envelopeGrid = shell.querySelector('#envelopeGrid');
  const summaryBody = shell.querySelector('#envelopeSummaryBody');
  const selectionBadge = shell.querySelector('#envSelectionBadge');
  const continueBtn = shell.querySelector('#envContinueBtn');
  const skipBtn = shell.querySelector('#skipEnvelopeBtn');
  const toast = shell.querySelector('#envToast');

  const summaryUrl = shell.dataset.summaryUrl || '/order/summary';
  const summaryApiUrl = shell.dataset.summaryApi || summaryUrl;
  const envelopesUrl = shell.dataset.envelopesUrl || '/api/envelopes';
  const giveawaysUrl = shell.dataset.giveawaysUrl || '/order/giveaways';
  const syncUrl = shell.dataset.syncUrl || '/order/envelope';
  const STORAGE_KEY = 'inkwise-finalstep';
  const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');

  if (continueBtn) {
    continueBtn.disabled = true;
  }

  const sampleEnvelopes = [
    // Removed default samples as per request
  ];

  const state = {
    envelopes: [],
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

  const applyLocalEnvelopeSelection = (env, qty, total, isRemoval = false) => {
    const summary = readSummary() ?? {};
    const price = normalisePrice(env.price);
    const resolvedTotal = Number.isFinite(total) ? total : price * qty;

    const envelopeMeta = {
      id: env.id ?? null,
      product_id: env.product_id ?? null,
      name: env.name ?? 'Envelope',
      price,
      qty,
      total: resolvedTotal,
      material: env.material ?? null,
      image: env.image ?? null,
      min_qty: env.min_qty ?? 10,
      max_qty: env.max_qty ?? null,
      updated_at: new Date().toISOString()
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

  const clearLocalEnvelopeSelection = () => {
    const summary = readSummary() ?? {};
    if (summary.extras) {
      summary.extras.envelope = 0;
    }
    delete summary.envelopes; // Changed from envelope to envelopes
    writeSummary(summary);
    state.selectedIds = []; // Changed from selectedId to selectedIds
    syncSelectionState(summary);
  };

  const setContinueState = (disabled) => {
    if (!continueBtn) return;
    continueBtn.disabled = Boolean(disabled);
  };

  const applyServerSummary = (summary) => {
    if (!summary || typeof summary !== 'object') {
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
        credentials: 'same-origin'
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
    return `
      <div class="summary-selection">
        <div class="summary-selection__media">
          <img src="${envelope.image ?? '/images/no-image.png'}" alt="${envelope.name}">
        </div>
        <div class="summary-selection__main">
          <p class="summary-selection__name">${envelope.name}</p>
          <p class="summary-selection__meta">${envelope.material ?? 'Custom envelope'}</p>
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
      markup += `
        <div class="summary-selection summary-selection--multiple">
          <div class="summary-selection__media">
            <img src="${envelope.image ?? '/images/no-image.png'}" alt="${envelope.name}">
          </div>
          <div class="summary-selection__main">
            <p class="summary-selection__name">${envelope.name}</p>
            <p class="summary-selection__meta">${envelope.material ?? 'Custom envelope'}</p>
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
    if (state.isSaving) return;

    state.isSaving = true;
    setContinueState(true);

    const card = options.cardElement ?? envelopeGrid?.querySelector(`[data-envelope-id="${env.id}"]`);
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
        image: env.image ?? null,
        min_qty: env.min_qty ?? 10,
        max_qty: env.max_qty ?? null
      }
    };

    // Optimistically reflect the selection locally so the UI updates even if the API is slow or fails.
    applyLocalEnvelopeSelection(env, qty, total);

    const result = await persistEnvelopeSelection(payload);

    if (triggerButton) {
      triggerButton.disabled = false;
      const selectedText = state.selectedIds.includes(String(env.id)) ? 'Unselect envelope' : 'Select envelope';
      triggerButton.textContent = selectedText;
      // Apply primary-action styling to both select and unselect buttons
      if (state.selectedIds.includes(String(env.id))) {
        triggerButton.className = 'primary-action envelope-item__select';
      } else {
        triggerButton.className = 'primary-action envelope-item__select';
      }
    }
    if (card) {
      card.classList.remove('is-saving');
    }

    state.isSaving = false;

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

    showToast('Unable to save envelope. Please try again.');
    syncSelectionState();
  };

  const createCard = (env) => {
    const price = normalisePrice(env.price);
    const minQty = Math.max(env.min_qty || 1, 20); // Ensure minimum is at least 20
    const initialQty = Math.max(env.min_qty || 10, 20); // Ensure initial quantity is at least 20
    const placeholderImage = '/images/no-image.png';

    const card = document.createElement('div');
    card.className = 'envelope-item';
    card.dataset.envelopeId = env.id;

    card.innerHTML = `
      <div class="envelope-item__media">
        <img src="${env.image || placeholderImage}" alt="${env.name}" loading="lazy">
      </div>
      <h3 class="envelope-item__title">${env.name}</h3>
      <p class="envelope-item__price">${formatMoney(price)} <span class="per-tag">per piece</span></p>
      ${env.material ? `<p class="envelope-item__meta">${env.material}</p>` : ''}
      <div class="envelope-item__controls">
        <div class="quantity-control">
          <div class="quantity-input-group">
            <label for="qty-${env.id}">Quantity</label>
            <div class="quantity-input-wrapper">
                <div class="quantity-input-controls">
                  <input type="number" id="qty-${env.id}" data-id="${env.id}" value="${initialQty}" min="${minQty}" max="${env.max_qty || ''}" step="1">
                </div>
              <span class="quantity-total" data-total-display>${formatMoney(price * initialQty)}</span>
            </div>
          </div>
          <div class="quantity-error" style="display: none;"></div>
          <p class="quantity-helper summary-note">Select a quantity. Minimum order is ${minQty}</p>
        </div>
        <div class="control-buttons">
          <button class="btn btn-secondary envelope-item__design" type="button" title="Add/Edit My Design">
            <i class="fas fa-edit"></i> Design
          </button>
          <button class="primary-action envelope-item__select" type="button">Select envelope</button>
        </div>
      </div>
    `;

    const qtyInput = card.querySelector('input[type="number"]');
    const designBtn = card.querySelector('.envelope-item__design');
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
      const min = minQty; // Use the enforced minimum of at least 20
      const max = env.max_qty;

      if (isNaN(qty) || qty < min) {
        errorDisplay.textContent = `Quantity must be at least ${min}`;
        errorDisplay.style.display = 'block';
        qtyInput.classList.add('error');
        return false;
      }

      if (max && qty > max) {
        errorDisplay.textContent = `Maximum quantity is ${max}`;
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

    designBtn?.addEventListener('click', () => {
      // TODO: Implement design functionality
      showToast('Design feature coming soon!');
    });

    addBtn?.addEventListener('click', async () => {
      const envelopeId = String(env.id);
      const isCurrentlySelected = state.selectedIds.includes(envelopeId);

      if (isCurrentlySelected) {
        // Remove this envelope from selection
        applyLocalEnvelopeSelection(env, 0, 0, true);
        showToast(`${env.name} removed from selection`);
        return;
      }

      if (!validateQuantity()) {
        qtyInput.focus();
        return;
      }
      const { qty, total } = updateTotal();
      await selectEnvelope(env, qty, total, { cardElement: card, triggerButton: addBtn });
    });

    return card;
  };

  const renderCards = (items) => {
    if (!envelopeGrid) return;
    envelopeGrid.innerHTML = '';

    if (!items.length) {
      const empty = document.createElement('div');
      empty.className = 'envelope-item envelope-item--empty';
      empty.innerHTML = '<p>No stocks available.</p>';
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
    // Check cache first (5 minute cache)
    const cacheKey = 'envelope_data_cache';
    const cacheTimestamp = 'envelope_cache_timestamp';
    const now = Date.now();
    const cacheExpiry = 5 * 60 * 1000; // 5 minutes

    const cachedData = sessionStorage.getItem(cacheKey);
    const cachedTime = sessionStorage.getItem(cacheTimestamp);

    if (cachedData && cachedTime && (now - parseInt(cachedTime)) < cacheExpiry) {
      try {
        const data = JSON.parse(cachedData);
        state.envelopes = data.map((item, index) => ({
          id: item.id ?? `env_${index}`,
          product_id: item.product_id,
          name: item.name ?? 'Envelope',
          price: normalisePrice(item.price),
          image: item.image,
          material: item.material,
          min_qty: item.min_qty ?? 10,
          max_qty: item.max_qty
        }));
        clearSkeleton();
        renderCards(state.envelopes);
        syncSelectionState();
        return;
      } catch (e) {
        // Cache corrupted, continue with API call
      }
    }

    showSkeleton(state.skeletonCount);
    try {
      const response = await fetch(envelopesUrl, { headers: { Accept: 'application/json' } });
      if (response.ok) {
        const data = await response.json();
        if (Array.isArray(data) && data.length) {
          state.envelopes = data.map((item, index) => ({
            id: item.id ?? `env_${index}`,
            product_id: item.product_id,
            name: item.name ?? 'Envelope',
            price: normalisePrice(item.price),
            image: item.image,
            material: item.material,
            min_qty: item.min_qty ?? 10,
            max_qty: item.max_qty
          }));

          // Cache the raw API data
          sessionStorage.setItem(cacheKey, JSON.stringify(data));
          sessionStorage.setItem(cacheTimestamp, now.toString());
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
      clearSkeleton();
      renderCards(state.envelopes);
      syncSelectionState();
    }
  };

  continueBtn?.addEventListener('click', () => {
    const target = continueBtn.dataset.summaryUrl || summaryUrl;
    window.location.href = target;
  });

  skipBtn?.addEventListener('click', async () => {
    if (state.isSaving) return;

    state.isSaving = true;
    setContinueState(true);

    const target = skipBtn.dataset.summaryUrl || giveawaysUrl;
    skipBtn.disabled = true;

    // Try to clear any saved envelope, but never block navigation.
    try {
      const result = await clearEnvelopeSelection();

      if (result?.ok) {
        if (result.data?.summary) {
          applyServerSummary(result.data.summary);
        } else {
          await fetchSummaryFromServer();
        }
      }
    } catch (error) {
      console.warn('Skipping envelope failed, continuing anyway', error);
    }

    skipBtn.disabled = false;
    state.isSaving = false;

    showToast('Continuing without an envelope…');
    window.setTimeout(() => {
      window.location.href = target;
    }, 250);
  });

  const initialise = async () => {
    await fetchSummaryFromServer();
    await loadEnvelopes();
  };

  initialise();
});
