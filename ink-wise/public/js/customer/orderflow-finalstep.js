document.addEventListener('DOMContentLoaded', () => {
  const shell = document.querySelector('.finalstep-shell');
  const form = document.getElementById('finalOrderForm');
  const quantitySelect = document.getElementById('quantitySelect');
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
  const productNameMeta = shell?.dataset?.productName
    ?? document.querySelector('.finalstep-preview')?.dataset?.productName
    ?? null;

  const previewPlaceholder = '/images/placeholder.png';
  const storageKey = shell?.dataset?.storageKey ?? 'inkwise-finalstep';
  const envelopeUrl = addToCartBtn?.dataset?.envelopeUrl ?? shell?.dataset?.envelopeUrl ?? '/order/envelope';
  const allowFallbackSamples = shell?.dataset?.fallbackSamples === 'true';
  const finalStepSaveUrl = shell?.dataset?.saveUrl ?? null;
  const DEFAULT_TAX_RATE = 0.12;
  const DEFAULT_SHIPPING_FEE = 250;
  const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
  let toastTimeout = null;
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

    const payload = {
      quantity: summary.quantity,
      paper_stock_id: toNumericOrNull(summary.paperStockId),
      paper_stock_price: summary.paperStockPrice,
      paper_stock_name: summary.paperStockName,
      addons: addonIdsForApi,
      metadata: metadataPayload,
      preview_selections: summary.previewSelections ?? null
    };

    if (!payload.paper_stock_id) delete payload.paper_stock_id;
    if (payload.paper_stock_price === null || Number.isNaN(payload.paper_stock_price)) delete payload.paper_stock_price;
    if (!payload.paper_stock_name) delete payload.paper_stock_name;
    if (!payload.addons.length) delete payload.addons;
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
    if (!quantitySelect) return;
    const base = Number(quantitySelect.selectedOptions[0]?.dataset.price ?? 0);
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

  toggleButtons.forEach((button) => {
    button.addEventListener('click', () => togglePreview(button.dataset.face));
    button.addEventListener('keydown', (event) => {
      if (!['Enter', ' '].includes(event.key)) return;
      event.preventDefault();
      button.click();
    });
  });

  quantitySelect?.addEventListener('change', updateTotals);

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
      return;
    }

    if (quantitySelect && summary.quantity) {
      const desired = Number(summary.quantity);
      const matchingOption = Array.from(quantitySelect.options).find((option) => Number(option.value) === desired);
      if (matchingOption) {
        quantitySelect.value = String(desired);
      }
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

  addToCartBtn?.addEventListener('click', async (event) => {
    event.preventDefault();

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
      quantity: Number(quantitySelect?.value ?? 0),
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

    const quantityOptionsSnapshot = Array.from(quantitySelect?.options ?? []).map((option) => ({
      value: Number(option.value ?? option.dataset.value ?? 0),
      label: option.textContent?.trim() ?? String(option.value ?? ''),
      price: Number(option.dataset.price ?? 0)
    })).filter((option) => Number.isFinite(option.value) && option.value > 0);
    if (quantityOptionsSnapshot.length) {
      summary.quantityOptions = quantityOptionsSnapshot;
    }

    writeSummary(summary);

    if (finalStepSaveUrl) {
      try {
        await persistFinalSelections(summary);
      } catch (error) {
        console.error('Unable to sync final selections with the server', error);
      }
    }

    showToast('Added to cart — redirecting to envelope options...');

    window.setTimeout(() => {
      window.location.href = envelopeUrl;
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
});
