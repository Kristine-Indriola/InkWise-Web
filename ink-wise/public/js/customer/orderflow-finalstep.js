document.addEventListener('DOMContentLoaded', () => {
  const shell = document.querySelector('.finalstep-shell');
  const form = document.getElementById('finalOrderForm');
  const quantityInput = document.getElementById('quantityInput');
  const priceDisplay = document.getElementById('priceDisplay');
  const paperStockIdInput = document.getElementById('paperStockId');
  const paperStockPriceInput = document.getElementById('paperStockPrice');
  const paperGrid = form?.querySelector('.paper-stocks-group .feature-grid.small');
  const addonCheckboxes = Array.from(form?.querySelectorAll('input[name="addons[]"]') ?? []);
  const orderTotalEl = form?.querySelector('[data-order-total]');
  const addToCartBtn = document.getElementById('addToCartBtn');
  const toast = document.getElementById('finalStepToast');
  const flipContainer = document.querySelector('.card-flip');
  const toggleButtons = Array.from(document.querySelectorAll('.preview-toggle button'));
  const previewImages = Array.from(document.querySelectorAll('.card-face img'));
  const estimatedDateInput = document.getElementById('estimatedDate');
  const estimatedDateError = document.getElementById('estimatedDateError');
  const estimatedDateFinalLabel = document.getElementById('estimatedDateFinalLabel');
  const continueToCheckoutEl = document.getElementById('continueToCheckout');
  const paperSelectErrorEl = document.getElementById('paperSelectError');
  const preOrderModal = document.getElementById('preOrderModal');
  const preOrderConfirm = document.getElementById('preOrderConfirm');
  const preOrderCancel = document.getElementById('preOrderCancel');
  const productNameMeta = shell?.dataset?.productName
    ?? document.querySelector('.finalstep-preview')?.dataset?.productName
    ?? null;

  const previewPlaceholder = '/images/placeholder.png';
  const storageKey = shell?.dataset?.storageKey ?? 'inkwise-finalstep';
  const envelopeUrl = shell?.dataset?.envelopeUrl ?? addToCartBtn?.dataset?.envelopeUrl ?? '/order/envelope';
  const addToCartUrl = addToCartBtn?.dataset?.cartUrl ?? shell?.dataset?.cartUrl ?? '/order/addtocart';
  const allowFallbackSamples = shell?.dataset?.fallbackSamples === 'true';
  const finalStepSaveUrl = shell?.dataset?.saveUrl ?? null;
  const DEFAULT_TAX_RATE = 0;
  const DEFAULT_SHIPPING_FEE = 0;
  const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
  let toastTimeout = null;
  let isPreOrderConfirmed = false;
  let computedTotals = {
    base: 0,
    paper: 0,
    addons: 0,
    subtotal: 0,
    tax: 0,
  shipping: DEFAULT_SHIPPING_FEE,
    total: 0
  };

  const PREVIEW_STORAGE_KEY = 'inkwise-preview-selections';
  const productId = document.body?.dataset?.productId ?? shell?.dataset?.productId ?? null;
  const productNameFromBody = document.body?.dataset?.productName ?? null;
  let processingDays = (() => {
    const raw = shell?.dataset?.processingDays;
    const parsed = Number.parseInt(String(raw ?? ''), 10);
    return Number.isFinite(parsed) && parsed >= 0 ? parsed : 7;
  })();
  const estimatedArrivalEl = document.getElementById('estimatedArrival');

  // Initialize Flatpickr for date picker
  let fp = null;
  if (estimatedDateInput) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const minDate = new Date(today);
    minDate.setDate(minDate.getDate() + 10);
    const maxDate = new Date(today);
    maxDate.setDate(maxDate.getDate() + 30);

    // Prepare a sensible defaultDate: prefer server-provided if within range, otherwise clamp to minDate
    const serverDefaultRaw = shell?.dataset?.estimatedDeliveryDateFormatted ?? '';
    let defaultDate = minDate;
    if (serverDefaultRaw) {
      const parsed = new Date(serverDefaultRaw + 'T00:00:00');
      if (!Number.isNaN(parsed.getTime())) {
        if (parsed >= minDate && parsed <= maxDate) {
          defaultDate = parsed;
        } else if (parsed < minDate) {
          defaultDate = minDate;
        } else if (parsed > maxDate) {
          defaultDate = maxDate;
        }
      }
    }

    fp = flatpickr(estimatedDateInput, {
      dateFormat: 'Y-m-d',
      minDate: minDate,
      maxDate: maxDate,
      defaultDate: defaultDate
    });
  }

  const readSummary = () => {
    try {
      const raw = window.sessionStorage.getItem(storageKey);
      return raw ? JSON.parse(raw) : null;
    } catch (error) {
      console.warn('Unable to parse stored order summary', error);
      return null;
    }
  };

  const writeSummary = (summary) => {
    try {
      window.sessionStorage.setItem(storageKey, JSON.stringify(summary));
    } catch (error) {
      console.warn('Unable to persist order summary', error);
    }
  };

  const getCsrfToken = () => csrfTokenMeta?.getAttribute('content') ?? null;

  const toNumericOrNull = (value) => {
    if (value === null || value === undefined) return null;
    const parsed = Number.parseInt(String(value), 10);
    return Number.isFinite(parsed) ? parsed : null;
  };

  const persistFinalSelections = async (summary) => {
    if (!finalStepSaveUrl) return null;

    const addonIdsForApi = Array.isArray(summary.addonIds)
      ? summary.addonIds
          .map(toNumericOrNull)
          .filter((id) => Number.isInteger(id) && id > 0)
      : [];

    const normalizedAddonQuantities = {};
    if (summary.addonQuantities && typeof summary.addonQuantities === 'object') {
      Object.entries(summary.addonQuantities).forEach(([key, value]) => {
        const numericId = toNumericOrNull(key);
        const numericQuantity = toNumericOrNull(value);
        if (!Number.isInteger(numericId) || numericId <= 0) return;
        if (!Number.isInteger(numericQuantity) || numericQuantity < 1) return;
        normalizedAddonQuantities[numericId] = numericQuantity;
      });
    }

    const orderQuantity = Number.isFinite(Number(summary.quantity)) && Number(summary.quantity) > 0
      ? Number(summary.quantity)
      : null;

    if (!Object.keys(normalizedAddonQuantities).length && addonIdsForApi.length && orderQuantity) {
      addonIdsForApi.forEach((id) => {
        normalizedAddonQuantities[id] = orderQuantity;
      });
    }

    const metadataPayload = {};
    if (summary.extras) metadataPayload.extras = summary.extras;
    if (summary.productName) metadataPayload.product_name = summary.productName;
    if (summary.previewImages) metadataPayload.preview_images = summary.previewImages;
    if (summary.quantityOptions) metadataPayload.quantity_options = summary.quantityOptions;
    metadataPayload.totals = {
      subtotal: summary.subtotalAmount,
      tax: summary.taxAmount,
      shipping: summary.shippingFee,
      total: summary.totalAmount
    };

    const estimatedDateValue = summary.estimated_date ?? summary.dateNeeded ?? null;
    if (estimatedDateValue) {
      metadataPayload.estimated_date = estimatedDateValue;
      if (summary.dateNeededLabel) {
        metadataPayload.estimated_date_label = summary.dateNeededLabel;
      }
    }

    const payload = {
      quantity: summary.quantity,
      estimated_date: estimatedDateValue,
      paper_stock_id: toNumericOrNull(summary.paperStockId),
      paper_stock_price: summary.paperStockPrice,
      paper_stock_name: summary.paperStockName,
      addons: addonIdsForApi,
      metadata: metadataPayload,
      preview_selections: summary.previewSelections ?? null
    };

    if (Object.keys(normalizedAddonQuantities).length) {
      payload.addon_quantities = normalizedAddonQuantities;
    }

    if (!payload.paper_stock_id) delete payload.paper_stock_id;
    if (payload.paper_stock_price === null || Number.isNaN(payload.paper_stock_price)) delete payload.paper_stock_price;
    if (!payload.paper_stock_name) delete payload.paper_stock_name;
    if (!payload.addons.length) delete payload.addons;
    if (!payload.estimated_date) delete payload.estimated_date;
    if (!Object.keys(metadataPayload).length) delete payload.metadata;

    const csrfToken = getCsrfToken();

    try {
      const response = await fetch(finalStepSaveUrl, {
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
        console.warn('Failed to persist final selections', response.status);
        return null;
      }

      const data = await response.json();
      if (data?.summary) {
        writeSummary(data.summary);
      }
      return data;
    } catch (error) {
      console.error('Error persisting final selections', error);
      return null;
    }
  };

  const readPreviewStore = () => {
    try {
      const raw = window.sessionStorage.getItem(PREVIEW_STORAGE_KEY);
      return raw ? JSON.parse(raw) : {};
    } catch (error) {
      console.warn('Unable to parse stored preview selections', error);
      return {};
    }
  };

  // Stock availability elements
  const paperStockAvailableEl = document.getElementById('paperStockAvailable');
  const paperStockAvailableCount = document.getElementById('paperStockAvailableCount');
  const stockErrorEl = document.getElementById('stockError');

  const getSelectedPaperAvailable = () => {
    const selected = document.querySelector('.paper-stock-card[aria-pressed="true"]');
    if (!selected) return null;
    const raw = selected.dataset?.available;
    const parsed = Number.parseInt(String(raw ?? ''), 10);
    return Number.isFinite(parsed) ? parsed : null;
  };

  const showPaperAvailable = (available) => {
    if (!paperStockAvailableEl || !paperStockAvailableCount) return;
    if (available === null || available === undefined) {
      paperStockAvailableEl.style.display = 'none';
      paperStockAvailableCount.textContent = '0';
      return;
    }
    paperStockAvailableCount.textContent = String(available);
    paperStockAvailableEl.style.display = 'block';
  };

  const ensurePaperSelected = () => {
    if (!continueToCheckoutEl || !paperSelectErrorEl) return;
    const qty = Number.parseInt(String(quantityInput?.value ?? ''), 10) || 0;
    const selected = document.querySelector('.paper-stock-card[aria-pressed="true"]');
    if (!selected) {
      continueToCheckoutEl.setAttribute('aria-disabled', 'true');
      paperSelectErrorEl.textContent = 'Please select a paper type to continue.';
      paperSelectErrorEl.style.display = 'block';
      return false;
    }
    const available = getSelectedPaperAvailable();
    if (available === 0 && !isPreOrderConfirmed) {
      continueToCheckoutEl.setAttribute('aria-disabled', 'true');
      paperSelectErrorEl.textContent = 'Please confirm pre-order to continue.';
      paperSelectErrorEl.style.display = 'block';
      return false;
    }
    if (available > 0 && qty > available) {
      continueToCheckoutEl.setAttribute('aria-disabled', 'true');
      paperSelectErrorEl.textContent = 'Order quantity exceeds available stock. Please adjust your quantity.';
      paperSelectErrorEl.style.display = 'block';
      return false;
    }
    continueToCheckoutEl.setAttribute('aria-disabled', 'false');
    paperSelectErrorEl.style.display = 'none';
    return true;
  };

  const validateStock = () => {
    if (!stockErrorEl || !quantityInput) return true;
    const qty = Number.parseInt(String(quantityInput.value ?? ''), 10) || 0;
    const available = getSelectedPaperAvailable();
    if (available === null) {
      stockErrorEl.style.display = 'none';
      addToCartBtn?.removeAttribute('disabled');
      return true;
    }
    if (qty <= 0) {
      stockErrorEl.style.display = 'none';
      addToCartBtn?.removeAttribute('disabled');
      return true;
    }
    if (qty > available && available > 0) {
      stockErrorEl.textContent = 'Order quantity exceeds available stock. Please adjust your quantity.';
      stockErrorEl.style.display = 'block';
      addToCartBtn?.setAttribute('disabled', 'true');
      return false;
    }
    stockErrorEl.style.display = 'none';
    addToCartBtn?.removeAttribute('disabled');
    return true;
  };

  const writePreviewStore = (store) => {
    try {
      window.sessionStorage.setItem(PREVIEW_STORAGE_KEY, JSON.stringify(store));
    } catch (error) {
      console.warn('Unable to persist preview selections', error);
    }
  };

  const getPreviewEntry = () => {
    if (!productId) return null;
    const store = readPreviewStore();
    const entry = store?.[productId];
    if (!entry || typeof entry !== 'object') return null;
    if (!entry.selections || typeof entry.selections !== 'object') return null;
    return entry;
  };

  const previewEntry = getPreviewEntry();
  const storedPreviewSelectionsSnapshot = previewEntry?.selections
    ? JSON.parse(JSON.stringify(previewEntry.selections))
    : null;
  const previewSelections = storedPreviewSelectionsSnapshot
    ? JSON.parse(JSON.stringify(storedPreviewSelectionsSnapshot))
    : {};

  let suppressPreviewSync = false;

  const persistPreviewSelections = () => {
    if (suppressPreviewSync || !productId) return;
    const store = readPreviewStore();
    store[productId] = {
      productId,
      productName: productNameMeta ?? productNameFromBody ?? null,
      selections: Object.fromEntries(
        Object.entries(previewSelections).map(([group, payload]) => [
          group,
          payload && typeof payload === 'object' ? { ...payload } : payload
        ])
      ),
      updatedAt: Date.now()
    };
    writePreviewStore(store);
  };

  const setPreviewSelection = (group, payload) => {
    if (!group || !productId || suppressPreviewSync) return;
    if (payload && typeof payload === 'object') {
      previewSelections[group] = { ...payload };
    } else {
      delete previewSelections[group];
    }
    persistPreviewSelections();
  };

  const formatMoney = (value) => new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(value ?? 0);

  const parseMoney = (value) => {
    if (typeof value === 'number') return value;
    if (!value) return 0;
    const numeric = String(value).replace(/[^0-9.-]+/g, '');
    const parsed = Number.parseFloat(numeric);
    return Number.isFinite(parsed) ? parsed : 0;
  };

  const roundCurrency = (value) => Number.parseFloat((Number(value) || 0).toFixed(2));

  const showToast = (message) => {
    if (!toast) return;
    toast.textContent = message;
    toast.hidden = false;
    toast.classList.add('is-visible');
    if (toastTimeout) window.clearTimeout(toastTimeout);
    toastTimeout = window.setTimeout(() => {
      toast.classList.remove('is-visible');
      toastTimeout = window.setTimeout(() => {
        toast.hidden = true;
      }, 260);
    }, 2600);
  };

  // Estimated date validation: require a date between 5 and 10 days from today (inclusive)
  const validateEstimatedDate = () => {
    if (!estimatedDateInput) return true;
    const val = estimatedDateInput.value;
    if (!val) {
      if (estimatedDateError) {
        estimatedDateError.textContent = 'Please pick an estimated delivery date.';
        estimatedDateError.style.display = 'block';
      }
      return false;
    }

    // parse yyyy-mm-dd safely
    const selected = new Date(val + 'T00:00:00');
    if (Number.isNaN(selected.getTime())) {
      if (estimatedDateError) {
        estimatedDateError.textContent = 'Invalid date format.';
        estimatedDateError.style.display = 'block';
      }
      return false;
    }

    const today = new Date();
    today.setHours(0,0,0,0);
    const minDate = new Date(today);
    minDate.setDate(minDate.getDate() + 10);
    const maxDate = new Date(today);
    maxDate.setDate(maxDate.getDate() + 30); // up to 1 month

    if (selected < minDate || selected > maxDate) {
      if (estimatedDateError) {
        const fmt = (d) => d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
        estimatedDateError.textContent = `Please choose a date between ${fmt(minDate)} and ${fmt(maxDate)}.`;
        estimatedDateError.style.display = 'block';
      }
      return false;
    }

    if (estimatedDateError) {
      estimatedDateError.textContent = '';
      estimatedDateError.style.display = 'none';
    }
    return true;
  };

  const computeArrival = (selectedDate) => {
    if (!selectedDate || !Number.isFinite(selectedDate.getTime())) return null;
    const arrival = new Date(selectedDate);
    arrival.setDate(arrival.getDate() + processingDays);
    return arrival;
  };

  const updateEstimatedArrival = () => {
    if (!estimatedArrivalEl || !estimatedDateInput) return;
    const val = estimatedDateInput.value;
    if (!val) {
      estimatedArrivalEl.style.display = 'none';
      estimatedArrivalEl.textContent = '';
      return;
    }
    const selected = new Date(val + 'T00:00:00');
    if (Number.isNaN(selected.getTime())) {
      estimatedArrivalEl.style.display = 'none';
      estimatedArrivalEl.textContent = '';
      return;
    }
    const arrival = computeArrival(selected);
    if (!arrival) {
      estimatedArrivalEl.style.display = 'none';
      estimatedArrivalEl.textContent = '';
      return;
    }
    const formatted = arrival.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
    estimatedArrivalEl.textContent = `Estimated pickup: ${formatted}`;
    estimatedArrivalEl.style.display = 'block';
  };

  // Update the final, human-readable label shown to the right of the date input
  const updateFinalDateLabel = () => {
    if (!estimatedDateFinalLabel || !estimatedDateInput) return;
    const val = estimatedDateInput.value;
    if (!val) {
      estimatedDateFinalLabel.textContent = '';
      return;
    }
    const dt = new Date(val + 'T00:00:00');
    if (Number.isNaN(dt.getTime())) {
      estimatedDateFinalLabel.textContent = '';
      return;
    }
    const formatted = dt.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
    estimatedDateFinalLabel.textContent = String(formatted).toUpperCase();
  };

  const ensureSamplePaperStocks = () => {
    if (!paperGrid) return;
    const existing = paperGrid.querySelectorAll('.paper-stock-card');
    if (existing.length) return;

    const samples = [
      { id: 'sample_1', name: 'Smooth Matte', price: 0, image: previewPlaceholder },
      { id: 'sample_2', name: 'Luxe Cotton', price: 120, image: previewPlaceholder },
      { id: 'sample_3', name: 'Pearlescent', price: 180, image: previewPlaceholder }
    ];

    samples.forEach((sample) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'feature-card selectable-card paper-stock-card';
      button.dataset.id = sample.id;
      button.dataset.price = String(sample.price ?? 0);
      button.setAttribute('aria-pressed', 'false');

      const media = document.createElement('div');
      media.className = 'feature-card-media';
      const img = document.createElement('img');
      img.src = sample.image;
      img.alt = sample.name;
      media.appendChild(img);

      const info = document.createElement('div');
      info.className = 'feature-card-info';
      const title = document.createElement('span');
      title.className = 'feature-card-title';
      title.textContent = sample.name;
      const price = document.createElement('span');
      price.className = 'feature-card-price';
      price.textContent = sample.price ? formatMoney(sample.price) : 'On request';
      info.appendChild(title);
      info.appendChild(price);

      button.appendChild(media);
      button.appendChild(info);
      paperGrid.appendChild(button);
    });
  };

  const ensureSampleAddons = () => {
    const existing = document.querySelectorAll('.addon-grid .feature-card');
    if (existing.length) return;

    const trimSamples = [
      { id: 'trim_1', name: 'Straight Cut', price: 8, image: previewPlaceholder },
      { id: 'trim_2', name: 'Deckle Edge', price: 12, image: previewPlaceholder },
      { id: 'trim_3', name: 'Rounded Corners', price: 10, image: previewPlaceholder }
    ];

    const embossedSamples = [
      { id: 'emboss_1', name: 'Gold Emboss', price: 25, image: previewPlaceholder },
      { id: 'emboss_2', name: 'Silver Emboss', price: 22, image: previewPlaceholder },
      { id: 'emboss_3', name: 'Pearl Emboss', price: 30, image: previewPlaceholder }
    ];

    const populate = (selector, samples, type) => {
      const grid = document.querySelector(selector);
      if (!grid) return;
      samples.forEach((sample) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'feature-card selectable-card addon-card';
        button.dataset.id = sample.id;
        button.dataset.price = String(sample.price ?? 0);
        button.dataset.type = type;
        button.setAttribute('aria-pressed', 'false');

        const media = document.createElement('div');
        media.className = 'feature-card-media';
        const img = document.createElement('img');
        img.src = sample.image;
        img.alt = sample.name;
        media.appendChild(img);

        const info = document.createElement('div');
        info.className = 'feature-card-info';
        const title = document.createElement('span');
        title.className = 'feature-card-title';
        title.textContent = sample.name;
        const price = document.createElement('span');
        price.className = 'feature-card-price';
        price.textContent = sample.price ? formatMoney(sample.price) : 'On request';
        info.appendChild(title);
        info.appendChild(price);

        button.appendChild(media);
        button.appendChild(info);
        grid.appendChild(button);
      });
    };

    populate('.addon-grid[data-addon-type="trim"]', trimSamples, 'trim');
    populate('.addon-grid[data-addon-type="embossed_powder"]', embossedSamples, 'embossed_powder');
  };

  const updateTotals = () => {
    if (!quantityInput) return;
    const base = Number(priceDisplay?.textContent?.replace('₱', '') ?? 0);
    const paper = Number(paperStockPriceInput?.value ?? 0);

    const addonsFromInputs = addonCheckboxes
      .filter((checkbox) => checkbox.checked)
      .reduce((sum, checkbox) => sum + Number(checkbox.dataset.price ?? 0), 0);

    const selectedAddonCards = Array.from(document.querySelectorAll('.addon-card[aria-pressed="true"]'));
    const addonsFromCards = selectedAddonCards.reduce((sum, card) => sum + Number(card.dataset.price ?? 0), 0);

    const addonsTotal = addonsFromInputs + addonsFromCards;
    const subtotal = base + paper + addonsTotal;
    const tax = subtotal * DEFAULT_TAX_RATE;
    const total = subtotal + tax + DEFAULT_SHIPPING_FEE;

    computedTotals = {
      base,
      paper,
      addons: addonsTotal,
      subtotal,
      tax,
      shipping: DEFAULT_SHIPPING_FEE,
      total
    };

    if (orderTotalEl) orderTotalEl.textContent = formatMoney(total);
    if (paperStockPriceInput) paperStockPriceInput.value = String(paper || 0);
  };

  const togglePreview = (face) => {
    if (!flipContainer) return;
    const isBack = face === 'back';
    flipContainer.classList.toggle('flipped', isBack);
    toggleButtons.forEach((button) => {
      const active = button.dataset.face === face;
      button.classList.toggle('active', active);
      button.setAttribute('aria-pressed', String(active));
    });
  };

  if (allowFallbackSamples) {
    ensureSamplePaperStocks();
    ensureSampleAddons();
  }

  estimatedDateInput?.addEventListener('change', () => {
    validateEstimatedDate();
    updateFinalDateLabel();
    updateEstimatedArrival();
  });

  toggleButtons.forEach((button) => {
    button.addEventListener('click', () => togglePreview(button.dataset.face));
    button.addEventListener('keydown', (event) => {
      if (!['Enter', ' '].includes(event.key)) return;
      event.preventDefault();
      button.click();
    });
  });

  quantityInput?.addEventListener('input', updateTotals);

  const paperCards = Array.from(document.querySelectorAll('.paper-stock-card'));
  paperCards.forEach((card) => {
    card.addEventListener('click', () => {
      const wasSelected = card.getAttribute('aria-pressed') === 'true';
      paperCards.forEach((c) => c.setAttribute('aria-pressed', 'false'));

      if (!wasSelected) {
        card.setAttribute('aria-pressed', 'true');
        const id = card.dataset.id ?? '';
        const priceValue = Number(card.dataset.price ?? 0);
        const name = card.querySelector('.feature-card-title')?.textContent?.trim() ?? '';
        const image = card.querySelector('.feature-card-media img')?.getAttribute('src') ?? '';
        paperStockIdInput.value = id;
        paperStockPriceInput.value = String(priceValue);
        setPreviewSelection('paper_stock', {
          id: String(id),
          name,
          price: priceValue,
          image
        });
      } else {
        paperStockIdInput.value = '';
        paperStockPriceInput.value = '0';
        setPreviewSelection('paper_stock', null);
      }
      updateTotals();
      // Update available display and validate stock after selection
      const available = getSelectedPaperAvailable();
      showPaperAvailable(available);

      // Handle pre-order logic
      if (available === 0) {
        // Out of stock - show modal if not confirmed
        if (!isPreOrderConfirmed) {
          preOrderModal.removeAttribute('aria-hidden');
          preOrderModal.style.display = 'flex';
          preOrderConfirm.focus();
        }
      } else {
        // In stock - reset pre-order state
        isPreOrderConfirmed = false;
        processingDays = 7; // Reset to default
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const minDate = new Date(today);
        minDate.setDate(minDate.getDate() + 10);
        const maxDate = new Date(today);
        maxDate.setDate(maxDate.getDate() + 30);
        if (fp) {
          fp.set('minDate', minDate);
          fp.set('maxDate', maxDate);
          // Keep current date if valid, else set to min
          const currentDate = new Date(estimatedDateInput.value + 'T00:00:00');
          if (currentDate >= minDate && currentDate <= maxDate) {
            fp.set('defaultDate', currentDate);
          } else {
            fp.set('defaultDate', minDate);
            estimatedDateInput.value = minDate.toISOString().split('T')[0];
          }
          updateEstimatedArrival();
        }
      }

      validateStock();
      ensurePaperSelected();
    });
  });

  const addonCards = Array.from(document.querySelectorAll('.addon-card'));
  addonCards.forEach((card) => {
    card.addEventListener('click', () => {
      const type = card.dataset.type || 'additional';
      const wasSelected = card.getAttribute('aria-pressed') === 'true';
      document.querySelectorAll(`.addon-card[data-type="${type}"]`).forEach((c) => c.setAttribute('aria-pressed', 'false'));

      if (wasSelected) {
        card.setAttribute('aria-pressed', 'false');
        setPreviewSelection(type, null);
      } else {
        card.setAttribute('aria-pressed', 'true');
        const id = card.dataset.id ?? '';
        const priceValue = Number(card.dataset.price ?? 0);
        const name = card.querySelector('.feature-card-title')?.textContent?.trim() ?? '';
        const image = card.querySelector('.feature-card-media img')?.getAttribute('src') ?? '';
        setPreviewSelection(type, {
          id: String(id),
          name,
          price: priceValue,
          image,
          type
        });
      }
      updateTotals();
    });
  });

  const applyStoredSelections = () => {
    const summary = readSummary();

    if (!summary || typeof summary !== 'object') {
      updateTotals();
      // if a paper selection was applied from stored summary show availability
      const available = getSelectedPaperAvailable();
      showPaperAvailable(available);
      validateStock();
      ensurePaperSelected();
      return;
    }

    if (quantityInput && summary.quantity) {
      const desired = Number(summary.quantity);
      quantityInput.value = String(desired);
    }

    let paperMatched = false;
    if (summary.paperStockId) {
      const paperCard = paperCards.find((node) => node.dataset.id === String(summary.paperStockId));
      if (paperCard) {
        paperCard.setAttribute('aria-pressed', 'false');
        paperCard.click();
        paperMatched = true;
      }
    }

    if (!paperMatched && summary.paperStockName) {
      const targetName = String(summary.paperStockName).trim().toLowerCase();
      const paperCard = paperCards.find((node) => node.querySelector('.feature-card-title')?.textContent?.trim().toLowerCase() === targetName);
      if (paperCard) {
        paperCard.setAttribute('aria-pressed', 'false');
        paperCard.click();
        paperMatched = true;
      }
    }

    if (!paperMatched) {
      paperCards.forEach((card) => card.setAttribute('aria-pressed', 'false'));
      if (paperStockIdInput) {
        paperStockIdInput.value = summary.paperStockId ? String(summary.paperStockId) : '';
      }
      if (paperStockPriceInput) {
        const storedPrice = parseMoney(summary.paperStockPrice ?? 0);
        paperStockPriceInput.value = Number.isFinite(storedPrice) ? String(storedPrice) : '0';
      }
    }

    const addonSelections = (() => {
      const ids = new Set();

      if (Array.isArray(summary.addonIds)) {
        summary.addonIds.forEach((id) => ids.add(String(id)));
      }

      if (Array.isArray(summary.addons)) {
        summary.addons.forEach((addon) => {
          if (typeof addon === 'string' || typeof addon === 'number') {
            ids.add(String(addon));
            return;
          }

          if (addon && typeof addon === 'object') {
            const candidate = addon.id ?? addon.addon_id ?? addon.value ?? null;
            if (candidate !== null && candidate !== undefined) {
              ids.add(String(candidate));
            }
          }
        });
      }

      return Array.from(ids);
    })();

    addonCards.forEach((card) => card.setAttribute('aria-pressed', 'false'));

    if (addonSelections.length) {
      const occupiedTypes = new Set();
      addonSelections.forEach((id) => {
        const target = addonCards.find((node) => node.dataset.id === String(id));
        if (!target) return;
        const type = target.dataset.type || 'additional';
        if (occupiedTypes.has(type)) return;
        occupiedTypes.add(type);
        target.click();
      });
    }

    updateTotals();
  };

  const applyPreviewSelections = () => {
    if (!storedPreviewSelectionsSnapshot || !Object.keys(storedPreviewSelectionsSnapshot).length) {
      return false;
    }

    let applied = false;

    const paperSelection = storedPreviewSelectionsSnapshot.paper_stock;
    if (paperSelection && paperSelection.id) {
      const paperCard = paperCards.find((node) => node.dataset.id === String(paperSelection.id));
      if (paperCard) {
        if (paperCard.getAttribute('aria-pressed') !== 'true') {
          paperCards.forEach((c) => c.setAttribute('aria-pressed', 'false'));
          paperCard.click();
          applied = true;
        }
      } else {
        if (paperStockIdInput) {
          paperStockIdInput.value = String(paperSelection.id);
        }
        if (paperStockPriceInput) {
          const priceValue = Number(paperSelection.price ?? 0);
          paperStockPriceInput.value = Number.isFinite(priceValue) ? String(priceValue) : '0';
        }
      }
    }

    Object.entries(storedPreviewSelectionsSnapshot).forEach(([group, payload]) => {
      if (group === 'paper_stock') return;
      if (!payload || typeof payload !== 'object' || !payload.id) return;
      const target = addonCards.find((node) =>
        node.dataset.type === group && node.dataset.id === String(payload.id)
      );
      if (!target) return;
      if (target.getAttribute('aria-pressed') === 'true') return;
      document.querySelectorAll(`.addon-card[data-type="${group}"]`).forEach((node) => node.setAttribute('aria-pressed', 'false'));
      target.click();
      applied = true;
    });

    if (applied) {
      updateTotals();
    }

    return applied;
  };

  addonCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', updateTotals);
  });

  // Validate stock whenever quantity changes
  quantityInput?.addEventListener('input', () => {
    updateTotals();
    validateStock();
    ensurePaperSelected();
  });

  // Block continue to checkout when disabled
  continueToCheckoutEl?.addEventListener('click', (ev) => {
    if (continueToCheckoutEl.getAttribute('aria-disabled') === 'true') {
      ev.preventDefault();
      showToast('Please select a paper type to continue.');
      return;
    }
  });

  addToCartBtn?.addEventListener('click', async (event) => {
    event.preventDefault();

    // Validate estimated date before proceeding
    if (!validateEstimatedDate()) {
      showToast('Please select a valid estimated date.');
      estimatedDateInput?.focus();
      return;
    }

    // Validate stock before proceeding
    if (!validateStock()) {
      showToast('Quantity exceeds available stock.');
      quantityInput?.focus();
      return;
    }

    const selectedAddonCards = Array.from(document.querySelectorAll('.addon-card[aria-pressed="true"]'));
    const addonInputsChecked = addonCheckboxes.filter((checkbox) => checkbox.checked);

    const addonIds = selectedAddonCards.map((card) => card.dataset.id).concat(
      addonInputsChecked.map((checkbox) => checkbox.value)
    ).filter((value) => value !== undefined && value !== null && String(value).trim().length);

    const paperCardSelected = document.querySelector('.paper-stock-card[aria-pressed="true"]');
    const paperStockName = paperCardSelected?.querySelector('.feature-card-title')?.textContent?.trim() ?? null;
    const paperStockPriceValue = Number.isFinite(computedTotals.paper)
      ? Number(computedTotals.paper)
      : Number(paperStockPriceInput?.value ?? 0);

    const addonCardDetails = selectedAddonCards.map((card) => ({
      id: card.dataset.id,
      name: card.querySelector('.feature-card-title')?.textContent?.trim() ?? card.dataset.id,
      price: Number(card.dataset.price ?? 0),
      type: card.dataset.type ?? null,
      image: card.querySelector('.feature-card-media img')?.getAttribute('src') ?? null
    }));

    const addonInputDetails = addonInputsChecked.map((checkbox) => ({
      id: checkbox.value,
      name: checkbox.dataset.label ?? checkbox.value,
      price: Number(checkbox.dataset.price ?? 0),
      type: checkbox.dataset.type ?? 'addon'
    }));

    const subtotalAmount = roundCurrency(computedTotals.subtotal);
    const taxAmount = roundCurrency(computedTotals.tax);
    const shippingFee = roundCurrency(computedTotals.shipping);
    const totalAmount = roundCurrency(computedTotals.total);

    const orderTotalText = formatMoney(totalAmount);
    const orderTotalValue = totalAmount;

    const previewSources = previewImages
      .map((img) => img?.getAttribute('src'))
      .filter((src) => typeof src === 'string' && src.length);

    const previewSelectionSnapshot = Object.fromEntries(
      Object.entries(previewSelections).map(([group, payload]) => [group, payload ? { ...payload } : payload])
    );

    const recognisedSelectionFields = {
      orientation: 'orientation',
      size: 'size',
      trim: 'trim',
      embossed_powder: 'foilColor',
      backside: 'backside',
      foil: 'foilColor',
      foil_color: 'foilColor'
    };

    const addonsById = new Map();
    addonCardDetails.concat(addonInputDetails).forEach((addon) => {
      if (!addon?.id) return;
      addonsById.set(String(addon.id), { ...addon, id: String(addon.id) });
    });

    const summary = {
      quantity: Number(quantityInput?.value ?? 0),
      estimated_date: document.getElementById('estimatedDate')?.value || null,
      paperStockId: paperStockIdInput?.value || null,
      paperStockPrice: roundCurrency(paperStockPriceValue),
      paperStockName,
      subtotalAmount,
      taxAmount,
      shippingFee,
      total: orderTotalText,
      totalAmount,
      originalTotal: totalAmount,
      currency: 'PHP',
      previewSelections: previewSelectionSnapshot,
      extras: {
        base: roundCurrency(computedTotals.base),
        paper: roundCurrency(paperStockPriceValue),
        addons: roundCurrency(computedTotals.addons)
      }
    };

    if (summary.estimated_date) {
      summary.dateNeeded = summary.estimated_date;
      try {
        const parsedDate = new Date(summary.estimated_date);
        if (!Number.isNaN(parsedDate.getTime())) {
          summary.dateNeededLabel = parsedDate.toLocaleDateString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
          });
        }
      } catch (error) {
        // ignore formatting errors
      }
    }

    Object.entries(previewSelectionSnapshot).forEach(([group, payload]) => {
      if (!payload || typeof payload !== 'object') return;

      if (group === 'paper_stock') {
        if (!summary.paperStockName && payload.name) summary.paperStockName = payload.name;
        if ((!summary.paperStockId || summary.paperStockId === 'null') && payload.id) {
          summary.paperStockId = String(payload.id);
        }
        if ((!summary.paperStockPrice || summary.paperStockPrice === 0) && Number.isFinite(payload.price)) {
          summary.paperStockPrice = Number(payload.price);
        }
        return;
      }

      const mappedField = recognisedSelectionFields[group];
      if (mappedField && !summary[mappedField] && payload.name) {
        summary[mappedField] = payload.name;
      }

      const payloadId = payload.id ?? null;
      if (payloadId && !addonsById.has(String(payloadId))) {
        addonsById.set(String(payloadId), {
          id: String(payloadId),
          name: payload.name ?? String(payloadId),
          price: Number(payload.price ?? 0),
          type: payload.type ?? group,
          image: payload.image ?? null
        });
      }
    });

    const mergedAddons = Array.from(addonsById.values());
    summary.addonIds = Array.from(new Set([
      ...addonIds,
      ...mergedAddons.map((addon) => addon.id)
    ]));
    summary.addons = mergedAddons;
    if (mergedAddons.length) summary.addonItems = mergedAddons;

    const orderQuantityValue = Number.isFinite(summary.quantity) && summary.quantity > 0 ? summary.quantity : null;
    if (orderQuantityValue && Array.isArray(summary.addonIds) && summary.addonIds.length) {
      const addonQuantities = {};
      summary.addonIds.forEach((id) => {
        if (!Number.isFinite(Number(id))) return;
        addonQuantities[String(id)] = orderQuantityValue;
      });

      if (Object.keys(addonQuantities).length) {
        summary.addonQuantities = addonQuantities;
      }
    }

    if (productNameMeta) summary.productName = productNameMeta;
    if (previewSources.length) {
      summary.previewImages = previewSources;
      summary.previewImage = previewSources[0];
    }

    if (summary.paperStockName || summary.paperStockId || summary.paperStockPrice) {
      summary.paperStock = {
        id: summary.paperStockId,
        name: summary.paperStockName,
        price: summary.paperStockPrice
      };
    }

    const quantityOptionsSnapshot = [];
    if (quantityOptionsSnapshot.length) {
      summary.quantityOptions = quantityOptionsSnapshot;
    }

    if (window && window.console && typeof window.console.debug === 'function') {
      console.debug('FinalStep: summary before writeSummary', {
        addonIds: summary.addonIds,
        addons: summary.addons,
        previewSelections: summary.previewSelections,
      });
    }
    writeSummary(summary);

    if (finalStepSaveUrl) {
      try {
        await persistFinalSelections(summary);
      } catch (error) {
        console.error('Unable to sync final selections with the server', error);
      }
    }

    showToast('Adding to cart — redirecting...');

    window.setTimeout(() => {
      window.location.href = addToCartUrl;
    }, 600);
  });

  window.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') togglePreview('front');
    if (event.key === 'ArrowRight') togglePreview('back');
  });

  togglePreview('front');
  suppressPreviewSync = true;
  applyStoredSelections();
  suppressPreviewSync = false;
  applyPreviewSelections();
  updateTotals();
  // Ensure the final, human-readable date label is in sync on load
  updateFinalDateLabel();
  // Ensure the estimated arrival is shown on load if a date exists
  updateEstimatedArrival();
  // Ensure paper selection requirement is enforced on load
  ensurePaperSelected();

  // Modal event listeners
  if (preOrderConfirm) {
    preOrderConfirm.addEventListener('click', () => {
      isPreOrderConfirmed = true;
      processingDays = 15;
      const today = new Date();
      today.setHours(0, 0, 0, 0);
      const preOrderMinDate = new Date(today);
      preOrderMinDate.setDate(preOrderMinDate.getDate() + 15);
      const preOrderMaxDate = new Date(today);
      preOrderMaxDate.setDate(preOrderMaxDate.getDate() + 30);

      if (fp) {
        fp.set('minDate', preOrderMinDate);
        fp.set('maxDate', preOrderMaxDate);
        fp.set('defaultDate', preOrderMinDate);
        estimatedDateInput.value = preOrderMinDate.toISOString().split('T')[0];
        updateEstimatedArrival();
      }

      preOrderModal.setAttribute('aria-hidden', 'true');
      preOrderModal.style.display = 'none';
      ensurePaperSelected();
    });
  }

  if (preOrderCancel) {
    preOrderCancel.addEventListener('click', () => {
      isPreOrderConfirmed = false;
      preOrderModal.setAttribute('aria-hidden', 'true');
      preOrderModal.style.display = 'none';
      stockErrorEl.textContent = 'Paper stock unavailable. Please choose another paper.';
      stockErrorEl.style.display = 'block';
      ensurePaperSelected();
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

  // Modal keyboard navigation
  document.addEventListener('keydown', (event) => {
    if (preOrderModal && preOrderModal.style.display !== 'none') {
      if (event.key === 'Escape') {
        preOrderCancel.click();
      } else if (event.key === 'Tab') {
        // Trap focus within modal
        const focusableElements = preOrderModal.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        if (event.shiftKey) {
          if (document.activeElement === firstElement) {
            event.preventDefault();
            lastElement.focus();
          }
        } else {
          if (document.activeElement === lastElement) {
            event.preventDefault();
            firstElement.focus();
          }
        }
      }
    }
  });

});
