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
  const productNameMeta = document.querySelector('.finalstep-preview')?.dataset?.productName ?? null;

  const previewPlaceholder = '/images/placeholder.png';
  const storageKey = shell?.dataset?.storageKey ?? 'inkwise-finalstep';
  const envelopeUrl = addToCartBtn?.dataset?.envelopeUrl ?? shell?.dataset?.envelopeUrl ?? '/order/envelope';
  const allowFallbackSamples = shell?.dataset?.fallbackSamples === 'true';
  let toastTimeout = null;

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

    const total = base + paper + addonsFromInputs + addonsFromCards;

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
      const currentlySelected = card.getAttribute('aria-pressed') === 'true';
      paperCards.forEach((c) => c.setAttribute('aria-pressed', 'false'));
      if (!currentlySelected) {
        card.setAttribute('aria-pressed', 'true');
        paperStockIdInput.value = card.dataset.id ?? '';
        paperStockPriceInput.value = card.dataset.price ?? '0';
      } else {
        paperStockIdInput.value = '';
        paperStockPriceInput.value = '0';
      }
      updateTotals();
    });
  });

  const addonCards = Array.from(document.querySelectorAll('.addon-card'));
  addonCards.forEach((card) => {
    card.addEventListener('click', () => {
      const type = card.dataset.type || 'additional';
      document.querySelectorAll(`.addon-card[data-type="${type}"]`).forEach((c) => c.setAttribute('aria-pressed', 'false'));
      const alreadySelected = card.getAttribute('aria-pressed') === 'true';
      card.setAttribute('aria-pressed', alreadySelected ? 'false' : 'true');
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

  addonCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', updateTotals);
  });

  addToCartBtn?.addEventListener('click', (event) => {
    event.preventDefault();

    const selectedAddonCards = Array.from(document.querySelectorAll('.addon-card[aria-pressed="true"]'));
    const addonInputsChecked = addonCheckboxes.filter((checkbox) => checkbox.checked);

    const addonIds = selectedAddonCards.map((card) => card.dataset.id).concat(
      addonInputsChecked.map((checkbox) => checkbox.value)
    );

    const paperCardSelected = document.querySelector('.paper-stock-card[aria-pressed="true"]');
    const paperStockName = paperCardSelected?.querySelector('.feature-card-title')?.textContent?.trim() ?? null;
    const paperStockPriceValue = Number(paperStockPriceInput?.value ?? 0);

    const addonCardDetails = selectedAddonCards.map((card) => ({
      id: card.dataset.id,
      name: card.querySelector('.feature-card-title')?.textContent?.trim() ?? card.dataset.id,
      price: Number(card.dataset.price ?? 0),
      type: card.dataset.type ?? null
    }));

    const addonInputDetails = addonInputsChecked.map((checkbox) => ({
      id: checkbox.value,
      name: checkbox.dataset.label ?? checkbox.value,
      price: Number(checkbox.dataset.price ?? 0),
      type: checkbox.dataset.type ?? 'addon'
    }));

    const orderTotalText = orderTotalEl?.textContent ?? formatMoney(0);
    const orderTotalValue = parseMoney(orderTotalText);

    const previewSources = previewImages
      .map((img) => img?.getAttribute('src'))
      .filter((src) => typeof src === 'string' && src.length);

    const summary = {
      quantity: Number(quantitySelect?.value ?? 0),
      paperStockId: paperStockIdInput?.value || null,
      paperStockPrice: paperStockPriceValue,
      total: orderTotalText,
      totalAmount: orderTotalValue,
      originalTotal: orderTotalValue + 20,
      currency: 'PHP'
    };

    if (paperStockName) summary.paperStockName = paperStockName;
    const addonDetails = addonCardDetails.concat(addonInputDetails);
    summary.addonIds = addonIds;
    summary.addons = addonDetails;
    if (addonDetails.length) summary.addonItems = addonDetails;
    if (productNameMeta) summary.productName = productNameMeta;
    if (previewSources.length) {
      summary.previewImages = previewSources;
      summary.previewImage = previewSources[0];
    }

    summary.foldType = 'Half Fold';
    summary.orientation = 'Portrait';
    summary.foilColor = null;
    summary.backside = 'None';
    summary.trim = 'Straight Cut';
    summary.size = '5x7 inches';
    summary.envelope = {
      name: 'White Envelope',
      qty: Number(quantitySelect?.value ?? 10),
      price: 50,
      originalPrice: 60,
      type: 'Standard',
      color: 'White',
      size: 'A6',
      printing: null
    };
    summary.envelopeImages = [previewPlaceholder];

    summary.giveaways = {
      name: 'Keychain',
      qty: Number(quantitySelect?.value ?? 10),
      price: 30,
      originalPrice: 40,
      type: 'Keychain',
      material: 'Acrylic',
      customization: null
    };
    summary.giveawaysImages = [previewPlaceholder];

    window.sessionStorage.setItem(storageKey, JSON.stringify(summary));
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
  applyStoredSelections();
});
