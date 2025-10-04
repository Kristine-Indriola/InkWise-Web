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
    { id: 'env_sample_1', name: 'Classic White', price: 8.5, image: '/images/no-image.png', material: 'Uncoated smooth', min_qty: 10, max_qty: 200 },
    { id: 'env_sample_2', name: 'Natural Kraft', price: 9.25, image: '/images/no-image.png', material: 'Kraft paper', min_qty: 10, max_qty: 300 },
    { id: 'env_sample_3', name: 'Pearl Shimmer', price: 14.0, image: '/images/no-image.png', material: 'Pearlescent shimmer', min_qty: 10, max_qty: 150 },
    { id: 'env_sample_4', name: 'Black Luxe', price: 16.5, image: '/images/no-image.png', material: 'Premium matte', min_qty: 10, max_qty: 120 }
  ];

  const state = {
    envelopes: [],
    selectedId: null,
    skeletonCount: Math.min(6, Math.max(3, Math.floor(((window.innerWidth || 1200) - 180) / 260))),
    isSaving: false
  };

  let toastTimer;

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
    const total = envelope.total ?? (envelope.price * (envelope.qty || 0));
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

  const syncSelectionState = (summaryOverride = null) => {
    const summary = summaryOverride ?? readSummary() ?? {};
    const envelope = summary.envelope;

    state.selectedId = envelope?.id ? String(envelope.id) : null;

    if (!envelope) {
      if (summaryBody) {
        summaryBody.innerHTML = '<p class="summary-empty">Choose an envelope to see the details here.</p>';
      }
      setBadgeState({ label: 'Pending' });
      setContinueState(true);
      highlightSelectedCard();
      return;
    }

    if (summaryBody) {
      summaryBody.innerHTML = buildSummaryMarkup(envelope);
    }
    setBadgeState({ label: 'Selected', tone: 'summary-badge--success' });
    highlightSelectedCard();
    setContinueState(false);
  };

  const highlightSelectedCard = () => {
    if (!envelopeGrid) return;
    envelopeGrid.querySelectorAll('.envelope-item').forEach((card) => {
      const isSelected = card.dataset.envelopeId === state.selectedId;
      card.classList.toggle('is-selected', isSelected);
      const btn = card.querySelector('.envelope-item__select');
      if (btn) btn.textContent = isSelected ? 'Selected envelope' : 'Select envelope';
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

    const result = await persistEnvelopeSelection(payload);

    if (triggerButton) {
      triggerButton.disabled = false;
      triggerButton.textContent = originalButtonText ?? 'Select envelope';
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
    const initialQty = 10;
    const placeholderImage = '/images/no-image.png';

    const card = document.createElement('div');
    card.className = 'envelope-item';
    card.dataset.envelopeId = env.id;

    const quantitySteps = Array.from({ length: 12 }, (_, idx) => (idx + 1) * 10);
    const options = quantitySteps.map((qty) => {
      const total = price * qty;
      return `<option value="${qty}" data-total="${total}">${qty} pcs — ${formatMoney(total)}</option>`;
    }).join('');

    card.innerHTML = `
      <div class="envelope-item__media">
        <img src="${env.image || placeholderImage}" alt="${env.name}">
      </div>
      <h3 class="envelope-item__title">${env.name}</h3>
      <p class="envelope-item__price">${formatMoney(price)} <span class="per-tag">per piece</span></p>
      <p class="envelope-item__total" data-total-display>${initialQty} pcs — ${formatMoney(price * initialQty)}</p>
      ${env.material ? `<p class="envelope-item__meta">${env.material}</p>` : ''}
      <div class="envelope-item__controls">
        <label>Quantity
          <select data-id="${env.id}">${options}</select>
        </label>
        <button class="btn btn-primary envelope-item__select" type="button">Select envelope</button>
      </div>
    `;

    const select = card.querySelector('select');
    const addBtn = card.querySelector('.envelope-item__select');
    const totalDisplay = card.querySelector('[data-total-display]');

    if (select) {
      select.value = String(initialQty);
      select.addEventListener('change', async () => {
        const qty = Number(select.value || initialQty);
        const total = price * qty;
        if (totalDisplay) totalDisplay.textContent = `${qty} pcs — ${formatMoney(total)}`;
        if (state.selectedId === env.id) {
          await selectEnvelope(env, qty, total, { silent: true, cardElement: card, triggerButton: addBtn });
        }
      });
    }

    addBtn?.addEventListener('click', async () => {
      const qty = Number(select?.value || initialQty);
      const total = price * qty;
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
      empty.innerHTML = '<p>No envelopes available yet. Please check back soon.</p>';
      envelopeGrid.appendChild(empty);
      return;
    }

    items.forEach((env) => envelopeGrid.appendChild(createCard(env)));

    const storedId = readSummary()?.envelope?.id;
    state.selectedId = storedId ? String(storedId) : null;
    highlightSelectedCard();
  };

  const loadEnvelopes = async () => {
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
        } else {
          state.envelopes = sampleEnvelopes;
        }
      } else {
        console.warn('Envelope API returned status', response.status);
        state.envelopes = sampleEnvelopes;
        setBadgeState({ label: 'Offline', tone: 'summary-badge--alert' });
      }
    } catch (error) {
      console.error('Error loading envelopes', error);
      state.envelopes = sampleEnvelopes;
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

    const result = await clearEnvelopeSelection();

    skipBtn.disabled = false;
    state.isSaving = false;

    if (result?.ok) {
      if (result.data?.summary) {
        applyServerSummary(result.data.summary);
      } else {
        const refreshed = await fetchSummaryFromServer();
        if (!refreshed) {
          syncSelectionState();
          showToast('Unable to refresh order summary. Please try again.');
          return;
        }
      }

      showToast('Continuing without an envelope…');
      window.setTimeout(() => {
        window.location.href = target;
      }, 500);
      return;
    }

    const status = result?.status ?? 0;
    if (status === 409 || status === 422) {
      showToast('That envelope is no longer available. Refreshing options…');
      await loadEnvelopes();
      const refreshed = await fetchSummaryFromServer();
      if (!refreshed) {
        syncSelectionState();
      }
      return;
    }

    showToast('Unable to clear envelope. Please try again.');
    syncSelectionState();
  });

  const initialise = async () => {
    await fetchSummaryFromServer();
    await loadEnvelopes();
  };

  initialise();
});
