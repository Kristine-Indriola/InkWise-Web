document.addEventListener('DOMContentLoaded', () => {
  const shell = document.querySelector('.env-shell');
  if (!shell) return;

  const envelopeGrid = shell.querySelector('#envelopeGrid');
  const summaryBody = document.getElementById('envelopeSummaryBody');
  const selectionBadge = document.getElementById('envSelectionBadge');
  const continueBtn = document.getElementById('envContinueBtn');
  const skipBtn = document.getElementById('skipEnvelopeBtn');
  const toast = document.getElementById('envToast');

  const summaryUrl = shell.dataset.summaryUrl || '/order/summary';
  const envelopesUrl = shell.dataset.envelopesUrl || '/api/envelopes';
  const STORAGE_KEY = 'inkwise-finalstep';

  const sampleEnvelopes = [
    { id: 'env_sample_1', name: 'Classic White', price: 8.5, image: '/images/no-image.png', material: 'Uncoated smooth', max_qty: 200 },
    { id: 'env_sample_2', name: 'Natural Kraft', price: 9.25, image: '/images/no-image.png', material: 'Kraft paper', max_qty: 300 },
    { id: 'env_sample_3', name: 'Pearl Shimmer', price: 14.0, image: '/images/no-image.png', material: 'Pearlescent', max_qty: 150 },
    { id: 'env_sample_4', name: 'Black Luxe', price: 16.5, image: '/images/no-image.png', material: 'Premium matte', max_qty: 120 }
  ];

  const state = {
    envelopes: [],
    selectedId: null,
    skeletonCount: Math.min(6, Math.max(3, Math.floor(((window.innerWidth || 1200) - 180) / 260)))
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
      placeholder.className = 'env-card env-card--placeholder';
      placeholder.innerHTML = `
        <div class="media"></div>
        <div class="title"></div>
        <div class="price"></div>
        <div class="controls"></div>
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

  const syncSelectionState = () => {
    const summary = readSummary();
    const envelope = summary?.envelope;

    if (!envelope) {
      if (summaryBody) {
        summaryBody.innerHTML = '<p class="summary-empty">Choose an envelope to see the details here.</p>';
      }
      setBadgeState({ label: 'Pending' });
      continueBtn?.setAttribute('hidden', 'true');
      return;
    }

    if (summaryBody) {
      summaryBody.innerHTML = buildSummaryMarkup(envelope);
    }
    setBadgeState({ label: 'Selected', tone: 'summary-badge--success' });
    continueBtn?.removeAttribute('hidden');
  };

  const highlightSelectedCard = () => {
    if (!envelopeGrid) return;
    envelopeGrid.querySelectorAll('.env-card').forEach((card) => {
      const isSelected = card.dataset.envelopeId === state.selectedId;
      card.classList.toggle('is-selected', isSelected);
      const btn = card.querySelector('.btn.add');
      if (btn) {
        btn.textContent = isSelected ? 'Selected' : 'Select envelope';
      }
    });
  };

  const selectEnvelope = (env, qty, total, options = {}) => {
    const summary = readSummary();
    summary.envelope = {
      id: env.id,
      product_id: env.product_id,
      name: env.name,
      price: env.price,
      qty,
      total,
      image: env.image,
      material: env.material ?? null,
      max_qty: env.max_qty ?? null
    };
    writeSummary(summary);
    state.selectedId = env.id;
  highlightSelectedCard();
  syncSelectionState();
    if (!options.silent) {
      showToast(`${env.name} added — ${qty} pcs for ${formatMoney(total)}`);
    }
  };

  const createCard = (env) => {
    const price = normalisePrice(env.price);
    const initialQty = 10;
    const placeholderImage = '/images/no-image.png';

    const card = document.createElement('div');
    card.className = 'env-card';
    card.dataset.envelopeId = env.id;

    const quantitySteps = Array.from({ length: 12 }, (_, idx) => (idx + 1) * 10);
    const options = quantitySteps.map((qty) => {
      const total = price * qty;
      return `<option value="${qty}" data-total="${total}">${qty} pcs — ${formatMoney(total)}</option>`;
    }).join('');

    card.innerHTML = `
      <div class="media">
        <img src="${env.image || placeholderImage}" alt="${env.name}">
      </div>
      <h3 class="title">${env.name}</h3>
      <div class="price">${formatMoney(price)} <span class="per-tag">per piece</span></div>
      <div class="total" data-total-display>${initialQty} pcs — ${formatMoney(price * initialQty)}</div>
      ${env.material ? `<p class="material">${env.material}</p>` : ''}
      <div class="controls">
        <label class="select-label">Quantity
          <select data-id="${env.id}">${options}</select>
        </label>
        <div class="env-actions">
          <button class="btn add" type="button">Select envelope</button>
        </div>
      </div>
    `;

    const select = card.querySelector('select');
    const addBtn = card.querySelector('.btn.add');
    const totalDisplay = card.querySelector('[data-total-display]');

    if (select) {
      select.value = String(initialQty);
      select.addEventListener('change', () => {
        const qty = Number(select.value || initialQty);
        const total = price * qty;
        if (totalDisplay) totalDisplay.textContent = `${qty} pcs — ${formatMoney(total)}`;
        if (state.selectedId === env.id) {
          selectEnvelope(env, qty, total, { silent: true });
        }
      });
    }

    addBtn?.addEventListener('click', () => {
      const qty = Number(select?.value || initialQty);
      const total = price * qty;
      selectEnvelope(env, qty, total);
    });

    return card;
  };

  const renderCards = (items) => {
    if (!envelopeGrid) return;
    envelopeGrid.innerHTML = '';

    if (!items.length) {
      const empty = document.createElement('div');
      empty.className = 'env-card env-card--empty';
      empty.innerHTML = '<p>No envelopes available yet. Please check back soon.</p>';
      envelopeGrid.appendChild(empty);
      return;
    }

    items.forEach((env) => envelopeGrid.appendChild(createCard(env)));

    const storedId = readSummary()?.envelope?.id;
    if (storedId) {
      state.selectedId = storedId;
    }
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

  skipBtn?.addEventListener('click', () => {
    const summary = readSummary();
    delete summary.envelope;
    writeSummary(summary);
    state.selectedId = null;
  highlightSelectedCard();
  syncSelectionState();
    showToast('Continuing without an envelope…');
    const target = skipBtn.dataset.summaryUrl || summaryUrl;
    window.setTimeout(() => {
      window.location.href = target;
    }, 450);
  });

  loadEnvelopes();
});
