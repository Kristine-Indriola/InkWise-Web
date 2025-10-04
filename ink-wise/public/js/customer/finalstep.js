document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('finalOrderForm');
  const quantitySelect = document.getElementById('quantitySelect');
  const paperStockIdInput = document.getElementById('paperStockId');
  const paperStockPriceInput = document.getElementById('paperStockPrice');
  const paperGrid = document.querySelector('.feature-grid.small');

  // helper to create sample paper-stock cards client-side when server hasn't provided any
  const ensureSamplePaperStocks = () => {
    const existing = document.querySelectorAll('.paper-stock-card');
    if (existing.length) return; // server provided cards
    if (!paperGrid) return;

    const samples = [
      { id: 'sample_1', name: 'Smooth Matte', price: 0, image: '/images/placeholder.png' },
      { id: 'sample_2', name: 'Luxe Cotton', price: 120, image: '/images/placeholder.png' },
      { id: 'sample_3', name: 'Pearlescent', price: 180, image: '/images/placeholder.png' }
    ];

    samples.forEach((s) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'feature-card selectable-card paper-stock-card';
      btn.setAttribute('data-id', s.id);
      btn.setAttribute('data-price', String(s.price));
      btn.setAttribute('aria-pressed', 'false');

      const media = document.createElement('div');
      media.className = 'feature-card-media';
      const img = document.createElement('img');
      img.src = s.image;
      img.alt = s.name;
      media.appendChild(img);

      const info = document.createElement('div');
      info.className = 'feature-card-info';
      const title = document.createElement('span');
      title.className = 'feature-card-title';
      title.textContent = s.name;
      const price = document.createElement('span');
      price.className = 'feature-card-price';
      price.textContent = s.price ? `₱${Number(s.price).toFixed(2)}` : 'On request';
      info.appendChild(title);
      info.appendChild(price);

      btn.appendChild(media);
      btn.appendChild(info);
      paperGrid.appendChild(btn);
    });
  };
  const addonCheckboxes = Array.from(form?.querySelectorAll('input[name="addons[]"]') ?? []);
  const orderTotalEl = document.querySelector('[data-order-total]');
  const addToCartBtn = document.getElementById('addToCartBtn');
  const toast = document.getElementById('finalStepToast');
  const flipContainer = document.querySelector('.card-flip');
  const inner = flipContainer?.querySelector('.inner');
  const frontBtn = document.querySelector('[data-face="front"]');
  const backBtn = document.querySelector('[data-face="back"]');
  const previewImageElements = Array.from(document.querySelectorAll('.card-face img'));
  const productNameMeta = document.querySelector('.artwork-preview')?.dataset?.productName;
  const previewPlaceholder = '/images/placeholder.png';
  let toastTimeout = null;

  const formatMoney = (value) => {
    // Philippine Peso formatting
    return new Intl.NumberFormat('en-PH', {
      style: 'currency',
      currency: 'PHP'
    }).format(value);
  };

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
    toast.classList.add('visible');
    if (toastTimeout) window.clearTimeout(toastTimeout);
    toastTimeout = window.setTimeout(() => {
      toast.classList.remove('visible');
      toastTimeout = window.setTimeout(() => {
        toast.hidden = true;
      }, 300);
    }, 2600);
  };

  const updateTotals = () => {
    if (!quantitySelect) return;
    const base = Number(quantitySelect.selectedOptions[0]?.dataset.price ?? 0);
    const paper = Number(paperStockPriceInput?.value ?? 0);

    // addons may come from server-side checkboxes or client-side addon-cards
    const addonsFromInputs = addonCheckboxes
      .filter((checkbox) => checkbox.checked)
      .reduce((sum, checkbox) => sum + Number(checkbox.dataset.price ?? 0), 0);

    const selectedAddonCards = Array.from(document.querySelectorAll('.addon-card[aria-pressed="true"]'));
    const addonsFromCards = selectedAddonCards.reduce((sum, card) => sum + Number(card.dataset.price ?? 0), 0);

    const addons = addonsFromInputs + addonsFromCards;

    const total = base + paper + addons;

    // Only show the total in the UI; keep other calculated values for storage if needed
    if (orderTotalEl) orderTotalEl.textContent = formatMoney(total);
    // keep hidden inputs updated
    if (paperStockPriceInput) paperStockPriceInput.value = String(paper || 0);
  };

  const handleToggle = (face) => {
    if (!inner) return;
    const isBack = face === 'back';
    inner.parentElement.classList.toggle('flipped', isBack);
    if (frontBtn) {
      frontBtn.classList.toggle('active', !isBack);
      frontBtn.setAttribute('aria-pressed', String(!isBack));
    }
    if (backBtn) {
      backBtn.classList.toggle('active', isBack);
      backBtn.setAttribute('aria-pressed', String(isBack));
    }
  };

  frontBtn?.addEventListener('click', () => handleToggle('front'));
  backBtn?.addEventListener('click', () => handleToggle('back'));

  [frontBtn, backBtn].forEach((btn) => {
    btn?.addEventListener('keydown', (event) => {
      if (!['Enter', ' '].includes(event.key)) return;
      event.preventDefault();
      btn.click();
    });
  });

  quantitySelect?.addEventListener('change', updateTotals);

  // Ensure sample UI exists for visual flow if server doesn't provide data
  ensureSamplePaperStocks();

  // wire up (new or existing) paper-stock cards
  const paperStockCards = Array.from(document.querySelectorAll('.paper-stock-card'));
  paperStockCards.forEach((card) => {
    card.addEventListener('click', () => {
      const id = card.dataset.id;
      const price = Number(card.dataset.price ?? 0);
      const currentlySelected = card.getAttribute('aria-pressed') === 'true';
      paperStockCards.forEach((c) => c.setAttribute('aria-pressed', 'false'));
      if (!currentlySelected) {
        card.setAttribute('aria-pressed', 'true');
        paperStockIdInput.value = id;
        paperStockPriceInput.value = price;
      } else {
        paperStockIdInput.value = '';
        paperStockPriceInput.value = 0;
      }
      updateTotals();
    });
  });

  // ------- Add-ons: client-side samples and wiring -------
  const ensureSampleAddons = () => {
    const existing = document.querySelectorAll('.addon-grid .feature-card');
    if (existing.length) return; // server provided addons

    const trimSamples = [
      { id: 'trim_1', name: 'Straight Cut', price: 8, image: '/images/placeholder.png' },
      { id: 'trim_2', name: 'Deckle Edge', price: 12, image: '/images/placeholder.png' },
      { id: 'trim_3', name: 'Rounded Corners', price: 10, image: '/images/placeholder.png' },
    ];

    const embossedSamples = [
      { id: 'emboss_1', name: 'Gold Emboss', price: 25, image: '/images/placeholder.png' },
      { id: 'emboss_2', name: 'Silver Emboss', price: 22, image: '/images/placeholder.png' },
      { id: 'emboss_3', name: 'Pearl Emboss', price: 30, image: '/images/placeholder.png' },
    ];

    const populate = (gridSelector, samples, type) => {
      const grid = document.querySelector(gridSelector);
      if (!grid) return;
      samples.forEach((s) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'feature-card selectable-card addon-card';
        btn.setAttribute('data-id', s.id);
        btn.setAttribute('data-price', String(s.price));
        btn.setAttribute('data-type', type);
        btn.setAttribute('aria-pressed', 'false');

        const media = document.createElement('div');
        media.className = 'feature-card-media';
        const img = document.createElement('img'); img.src = s.image; img.alt = s.name; media.appendChild(img);

        const info = document.createElement('div'); info.className = 'feature-card-info';
        const title = document.createElement('span'); title.className = 'feature-card-title'; title.textContent = s.name;
        const price = document.createElement('span'); price.className = 'feature-card-price'; price.textContent = s.price ? `₱${Number(s.price).toFixed(2)}` : 'On request';
        info.appendChild(title); info.appendChild(price);

        btn.appendChild(media); btn.appendChild(info);
        grid.appendChild(btn);
      });
    };

    populate('.addon-grid[data-addon-type="trim"]', trimSamples, 'trim');
    populate('.addon-grid[data-addon-type="embossed_powder"]', embossedSamples, 'embossed_powder');
  };

  ensureSampleAddons();

  // wire addon card behavior (single-select per type)
  const addonCards = Array.from(document.querySelectorAll('.addon-card'));
  addonCards.forEach((card) => {
    card.addEventListener('click', () => {
      const type = card.dataset.type;
      // deselect other cards of same type
      document.querySelectorAll(`.addon-card[data-type="${type}"]`).forEach((c) => c.setAttribute('aria-pressed', 'false'));
      const selected = card.getAttribute('aria-pressed') === 'true';
      if (!selected) card.setAttribute('aria-pressed', 'true');
      else card.setAttribute('aria-pressed', 'false');
      // update addon total (just sum all pressed addon-card prices)
      updateTotals();
    });
  });
  addonCheckboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', updateTotals);
  });

  addToCartBtn?.addEventListener('click', (event) => {
    event.preventDefault(); // Prevent any form submission

    // Store the summary
    const selectedAddonCards = Array.from(document.querySelectorAll('.addon-card[aria-pressed="true"]'));
    const addonInputsChecked = addonCheckboxes.filter((cb) => cb.checked);

    const addonIds = selectedAddonCards.map((c) => c.dataset.id).concat(
      addonInputsChecked.map((cb) => cb.value)
    );

    const selectedPaperCard = document.querySelector('.paper-stock-card[aria-pressed="true"]');
    const paperStockName = selectedPaperCard?.querySelector('.feature-card-title')?.textContent?.trim() ?? null;
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

    const addonDetails = addonCardDetails.concat(addonInputDetails);

    const orderTotalText = orderTotalEl?.textContent ?? '₱0.00';
    const orderTotalValue = parseMoney(orderTotalText);

    const previewImages = previewImageElements
      .map((img) => img?.getAttribute('src'))
      .filter((src) => typeof src === 'string' && src.length);

    const summary = {
      quantity: Number(quantitySelect?.value ?? 0),
      paperStockId: (paperStockIdInput?.value) || null,
      paperStockPrice: paperStockPriceValue,
      addons: addonIds,
      total: orderTotalText,
      totalAmount: orderTotalValue,
      originalTotal: orderTotalValue + 20, // dummy original for discount
      currency: 'PHP'
    };

    if (paperStockName) summary.paperStockName = paperStockName;
    if (addonDetails.length) summary.addonItems = addonDetails;
    if (productNameMeta) summary.productName = productNameMeta;
    if (previewImages.length) {
      summary.previewImages = previewImages;
      summary.previewImage = previewImages[0];
    }

    // Add dummy options for preview
    summary.foldType = 'Half Fold';
    summary.orientation = 'Portrait';
    summary.foilColor = null; // null means included
    summary.backside = 'None';
    summary.trim = 'Straight Cut';
    summary.size = '5x7 inches';
    summary.paperStockOriginalPrice = 100; // dummy original price

    // Add dummy envelope data
    summary.envelope = {
      name: 'White Envelope',
      qty: 10,
      price: 50,
      originalPrice: 60,
      type: 'Standard',
      color: 'White',
      size: 'A6',
      printing: null // included
    };
    summary.envelopeImages = [previewPlaceholder];

    // Add dummy giveaways data
    summary.giveaways = {
      name: 'Keychain',
      qty: 10,
      price: 30,
      originalPrice: 40,
      type: 'Keychain',
      material: 'Plastic',
      customization: null // included
    };
    summary.giveawaysImages = [previewPlaceholder];

    window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(summary));
    showToast('Added to cart — redirecting to envelope options...');

    // Redirect immediately to the customer envelope flow
    window.location.href = '/order/envelope';
  });

  window.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') {
      handleToggle('front');
    }
    if (event.key === 'ArrowRight') {
      handleToggle('back');
    }
  });

  updateTotals();
});
