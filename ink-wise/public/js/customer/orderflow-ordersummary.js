document.addEventListener('DOMContentLoaded', () => {
  const shell = document.querySelector('.os-shell');
  if (!shell) return;

  const storageKey = shell.dataset.storageKey || 'inkwise-finalstep';
  const envelopeUrl = shell.dataset.envelopesUrl || '/order/envelope';
  const checkoutUrl = shell.dataset.checkoutUrl || '/checkout';
  const editUrl = shell.dataset.editUrl || '/order/final-step';
  const giveawaysUrl = shell.dataset.giveawaysUrl || '/order/giveaways';

  const layout = shell.querySelector('[data-summary-wrapper]');
  const summaryGrid = shell.querySelector('[data-summary-grid]');
  const summaryCard = shell.querySelector('[data-summary-card]');
  const emptyState = shell.querySelector('[data-empty-state]');

  const previewFrame = shell.querySelector('[data-preview-frame]');
  const previewImageEl = shell.querySelector('[data-preview-image]');
  const previewPrevBtn = shell.querySelector('[data-preview-prev]');
  const previewNextBtn = shell.querySelector('[data-preview-next]');
  const previewNameEl = shell.querySelector('[data-preview-name]');
  const previewEditLink = shell.querySelector('[data-preview-edit]');
  const previewQuantitySelect = shell.querySelector('[data-preview-quantity]');
  const previewSavingsEl = shell.querySelector('[data-preview-savings]');
  const removeProductBtn = shell.querySelector('#osRemoveProductBtn');

  const optionElements = {
    orientation: shell.querySelector('[data-option="orientation"]'),
    foilColor: shell.querySelector('[data-option="foil-color"]'),
    backside: shell.querySelector('[data-option="backside"]'),
    trim: shell.querySelector('[data-option="trim"]'),
    size: shell.querySelector('[data-option="size"]'),
    paperStock: shell.querySelector('[data-option="paper-stock"]')
  };

  const previewOldTotalEl = shell.querySelector('[data-preview-old-total]');
  const previewNewTotalEl = shell.querySelector('[data-preview-new-total]');

  const envelopePreviewFrame = shell.querySelector('[data-envelope-preview-frame]');
  const envelopePreviewImageEl = shell.querySelector('[data-envelope-preview-image]');
  const envelopePreviewPrevBtn = shell.querySelector('[data-envelope-preview-prev]');
  const envelopePreviewNextBtn = shell.querySelector('[data-envelope-preview-next]');
  const envelopeNameEl = shell.querySelector('[data-envelope-name]');
  const envelopeEditLink = shell.querySelector('[data-envelope-edit]');
  const envelopeQuantitySelect = shell.querySelector('[data-envelope-quantity]');
  const removeEnvelopeBtn = shell.querySelector('#osRemoveEnvelopeBtn');

  const envelopeOptionElements = {
    type: shell.querySelector('[data-envelope-option="type"]'),
    color: shell.querySelector('[data-envelope-option="color"]'),
    size: shell.querySelector('[data-envelope-option="size"]'),
    printing: shell.querySelector('[data-envelope-option="printing"]')
  };

  const envelopeOldTotalEl = shell.querySelector('[data-envelope-old-total]');
  const envelopeNewTotalEl = shell.querySelector('[data-envelope-new-total]');
  const envelopeSavingsEl = shell.querySelector('[data-envelope-savings]');

  const giveawaysPreviewFrame = shell.querySelector('[data-giveaways-preview-frame]');
  const giveawaysPreviewImageEl = shell.querySelector('[data-giveaways-preview-image]');
  const giveawaysPreviewPrevBtn = shell.querySelector('[data-giveaways-preview-prev]');
  const giveawaysPreviewNextBtn = shell.querySelector('[data-giveaways-preview-next]');
  const giveawaysNameEl = shell.querySelector('[data-giveaways-name]');
  const giveawaysEditLink = shell.querySelector('[data-giveaways-edit]');
  const giveawaysQuantitySelect = shell.querySelector('[data-giveaways-quantity]');
  const removeGiveawaysBtn = shell.querySelector('#osRemoveGiveawaysBtn');

  const giveawaysOptionElements = {
    type: shell.querySelector('[data-giveaways-option="type"]'),
    material: shell.querySelector('[data-giveaways-option="material"]'),
    customization: shell.querySelector('[data-giveaways-option="customization"]')
  };

  const giveawaysOldTotalEl = shell.querySelector('[data-giveaways-old-total]');
  const giveawaysNewTotalEl = shell.querySelector('[data-giveaways-new-total]');
  const giveawaysSavingsEl = shell.querySelector('[data-giveaways-savings]');

  const subtotalOriginalEl = shell.querySelector('[data-summary="subtotal-original"]');
  const subtotalDiscountedEl = shell.querySelector('[data-summary="subtotal-discounted"]');
  const subtotalSavingsEl = shell.querySelector('[data-summary="subtotal-savings"]');
  const grandTotalEl = shell.querySelector('[data-summary="grand-total"]');
  const toast = shell.querySelector('#osToast');
  const checkoutBtn = shell.querySelector('#osCheckoutBtn');

  const previewPlaceholder = previewImageEl?.getAttribute('src') || shell.dataset.placeholder || '';
  let previewImages = [];
  let previewIndex = 0;
  let envelopeImages = [];
  let envelopeIndex = 0;
  let giveawaysImages = [];
  let giveawaysIndex = 0;

  const getSummary = () => {
    const raw = window.sessionStorage.getItem(storageKey);
    if (!raw) return null;
    try {
      return JSON.parse(raw);
    } catch (error) {
      console.warn('Failed to parse order summary', error);
      return null;
    }
  };

  const setSummary = (summary) => {
    window.sessionStorage.setItem(storageKey, JSON.stringify(summary));
  };

  const parseMoney = (value) => {
    if (typeof value === 'number') return value;
    if (!value) return 0;
    const numeric = String(value).replace(/[^0-9.-]+/g, '');
    const parsed = Number.parseFloat(numeric);
    return Number.isFinite(parsed) ? parsed : 0;
  };

  const moneyFormatter = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  });

  const formatMoney = (amount) => moneyFormatter.format(amount ?? 0);

  const setHidden = (node, hidden) => {
    if (!node) return;
    node.hidden = Boolean(hidden);
  };

  const hasValue = (value) => {
    if (value === undefined || value === null) return false;
    if (typeof value === 'string') return value.trim().length > 0;
    return true;
  };

  const getByPath = (root, path) => {
    if (!root || !path) return null;
    const segments = Array.isArray(path) ? path : String(path).split('.');
    let current = root;
    for (const segment of segments) {
      if (current === undefined || current === null) return null;
      const key = segment;
      if (typeof current !== 'object' || !(key in current)) {
        return null;
      }
      current = current[key];
    }
    return current;
  };

  const getFirstValue = (root, ...candidates) => {
    for (const candidate of candidates) {
      let value;
      if (typeof candidate === 'function') {
        try {
          value = candidate(root);
        } catch (error) {
          value = undefined;
        }
      } else if (Array.isArray(candidate) || typeof candidate === 'string') {
        value = getByPath(root, candidate);
      } else {
        value = candidate;
      }

      if (hasValue(value)) {
        return value;
      }
    }

    return null;
  };

  const findAddonMatch = (summary, keywords) => {
    const addons = Array.isArray(summary?.addons) ? summary.addons : [];
    const normalizedKeywords = keywords.map((keyword) => String(keyword).toLowerCase());

    const match = addons.find((addon) => {
      const type = (addon?.type ?? '').toString().toLowerCase();
      const name = (addon?.name ?? '').toString().toLowerCase();
      return normalizedKeywords.some((keyword) => type.includes(keyword) || name.includes(keyword));
    });

    return match || null;
  };

  const normaliseQuantityOption = (option) => {
    if (!option) return null;
    const value = Number(option.value ?? option.qty ?? option.quantity);
    if (!Number.isFinite(value) || value <= 0) return null;
    const label = option.label && option.label.toString().trim().length
      ? option.label.toString().trim()
      : `${value}`;
    const price = parseMoney(option.price ?? option.amount ?? option.total ?? 0);
    return { value, label, price };
  };

  const getQuantityOptions = (summary) => {
    const options = Array.isArray(summary?.quantityOptions) ? summary.quantityOptions : [];
    const normalized = options
      .map((option) => normaliseQuantityOption(option))
      .filter(Boolean);

    if (normalized.length) {
      return normalized;
    }

    const quantity = Number(summary?.quantity ?? 0);
    const unitPrice = parseMoney(
      getFirstValue(summary, 'unitPrice', 'unit_price', 'metadata.pricing.unit_price', 'metadata.pricing.unitPrice')
    );

    if (Number.isFinite(unitPrice) && unitPrice > 0) {
      const quantities = new Set();
      for (let qty = 10; qty <= 200; qty += 10) {
        quantities.add(qty);
      }
      if (Number.isFinite(quantity) && quantity > 0) {
        quantities.add(quantity);
      }

      return Array.from(quantities)
        .filter((qty) => Number.isFinite(qty) && qty > 0)
        .sort((a, b) => a - b)
        .map((qty) => ({
          value: qty,
          label: `${qty} Invitations`,
          price: Math.round(qty * unitPrice * 100) / 100,
        }));
    }

    if (!Number.isFinite(quantity) || quantity <= 0) {
      return [];
    }

    const fallbackPrice = parseMoney(summary?.totalAmount ?? summary?.total ?? 0);
    return [
      {
        value: quantity,
        label: summary?.quantityLabel || `${quantity}`,
        price: fallbackPrice,
      }
    ];
  };

  const computeExtras = (summary) => {
    const paperPrice = parseMoney(
      getFirstValue(
        summary,
        () => summary?.paperStockPrice,
        'paperStock.price',
        'metadata.paper_stock.price'
      )
    );

    const addonList = Array.isArray(summary?.addons)
      ? summary.addons
      : Array.isArray(summary?.addonItems)
        ? summary.addonItems
        : [];

    const addonTotal = addonList.reduce((sum, addon) => {
      const price = parseMoney(addon?.price ?? addon?.amount ?? 0);
      return sum + price;
    }, 0);

    return paperPrice + addonTotal;
  };

  let toastTimer;
  const showToast = (message) => {
    if (!toast) return;
    toast.textContent = message;
    toast.hidden = false;
    toast.classList.add('is-visible');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => {
      toast.classList.remove('is-visible');
      toastTimer = window.setTimeout(() => {
        toast.hidden = true;
      }, 240);
    }, 2400);
  };

  const updatePreviewNav = () => {
    const hasMultiple = previewImages.length > 1;
    if (previewPrevBtn) previewPrevBtn.disabled = !hasMultiple;
    if (previewNextBtn) previewNextBtn.disabled = !hasMultiple;
  };

  const applyPreviewImage = () => {
    if (!previewImageEl || !previewImages.length) return;
    const src = previewImages[previewIndex] ?? previewImages[0];
    if (src) {
      previewImageEl.src = src;
      if (previewNameEl) {
        const name = previewNameEl.textContent?.trim() || 'Invitation preview';
        previewImageEl.alt = `Invitation preview — ${name}`;
      }
    }
  };

  const renderPreview = (summary) => {
    if (!previewFrame || !previewImageEl) return;

    const providedImages = Array.isArray(summary?.previewImages)
      ? summary.previewImages.filter((src) => typeof src === 'string' && src.length)
      : [];

    const fallbackImage = summary?.previewImage || summary?.invitationImage || previewPlaceholder;

    const summaryMetadata = summary?.metadata || {};

    const applyOption = (node, value) => {
      if (!node) return;
      const container = node.closest('.os-option');
      if (!hasValue(value)) {
        if (container) container.hidden = true;
        node.textContent = '—';
        return;
      }
      if (container) container.hidden = false;
      node.textContent = typeof value === 'string' ? value : String(value);
    };

    const resolvedProductName = getFirstValue(
      summary,
      'productName',
      'metadata.product.name',
      'metadata.template.name'
    ) || 'Custom invitation';
    if (previewNameEl) previewNameEl.textContent = resolvedProductName;

    const resolvedEditUrl = getFirstValue(
      summary,
      'editUrl',
      'metadata.editUrl',
      () => summaryMetadata?.links?.edit
    ) || editUrl;
    if (previewEditLink) previewEditLink.href = resolvedEditUrl;

    if (previewQuantitySelect) {
      const quantityContainer = previewQuantitySelect.closest('.os-preview-quantity');
      const quantityOptions = getQuantityOptions(summary);
      const currentQuantity = Number(summary?.quantity ?? quantityOptions[0]?.value ?? 0);

      previewQuantitySelect.innerHTML = '';

      if (quantityOptions.length) {
        quantityOptions.forEach((option) => {
          const opt = document.createElement('option');
          opt.value = String(option.value);
          opt.textContent = option.label || `${option.value}`;
          previewQuantitySelect.appendChild(opt);
        });

        const selectedOption = quantityOptions.find((option) => option.value === currentQuantity) || quantityOptions[0];
        previewQuantitySelect.value = String(selectedOption.value);
        previewQuantitySelect.disabled = quantityOptions.length === 1;
        if (quantityContainer) setHidden(quantityContainer, false);
      } else {
        previewQuantitySelect.disabled = true;
        if (quantityContainer) setHidden(quantityContainer, true);
      }
    }

    const orientation = getFirstValue(
      summary,
      'orientation',
      'metadata.invitation.orientation',
      'metadata.final_step.orientation',
      'metadata.product.orientation'
    );
    applyOption(optionElements.orientation, orientation);

    const foilAddon = findAddonMatch(summary, ['foil', 'metal', 'emboss']);
    const foilColor = getFirstValue(
      summary,
      'foilColor',
      'metadata.final_step.foilColor',
      'metadata.invitation.foilColor',
      () => foilAddon?.name
    );
    applyOption(optionElements.foilColor, foilColor);

    const backsideAddon = findAddonMatch(summary, ['back', 'double', 'reverse']);
    const backside = getFirstValue(
      summary,
      'backside',
      'metadata.final_step.backside',
      'metadata.invitation.backside',
      () => backsideAddon?.name
    );
    applyOption(optionElements.backside, backside);

    const trimAddon = findAddonMatch(summary, ['trim', 'edge', 'corner']);
    const trim = getFirstValue(
      summary,
      'trim',
      'metadata.final_step.trim',
      'metadata.invitation.trim',
      () => trimAddon?.name
    );
    applyOption(optionElements.trim, trim);

    const resolvedSize = getFirstValue(
      summary,
      'size',
      'metadata.product.size',
      'metadata.template.size',
      'metadata.invitation.size'
    );
    applyOption(optionElements.size, resolvedSize);

    if (optionElements.paperStock) {
      const paperName = getFirstValue(summary, 'paperStockName', 'paperStock.name', 'metadata.paper_stock.name');
      const paperPrice = parseMoney(
        getFirstValue(summary, () => summary?.paperStockPrice, 'paperStock.price', 'metadata.paper_stock.price')
      );
      const display = hasValue(paperName)
        ? `${paperName}${paperPrice > 0.009 ? ` — ${formatMoney(paperPrice)}` : ' — Included'}`
        : null;
      applyOption(optionElements.paperStock, display);
    }

    const basePrice = parseMoney(summary?.totalAmount ?? summary?.total ?? 0);
    const originalBasePrice = parseMoney(summary?.originalTotal ?? summary?.subtotalOriginal ?? basePrice);
    const savings = originalBasePrice - basePrice;

    if (previewOldTotalEl) previewOldTotalEl.textContent = formatMoney(originalBasePrice);
    if (previewNewTotalEl) previewNewTotalEl.textContent = formatMoney(basePrice);
    if (previewSavingsEl) {
      if (savings > 0.009) {
        previewSavingsEl.textContent = `You saved ${formatMoney(savings)}`;
        setHidden(previewSavingsEl, false);
      } else {
        setHidden(previewSavingsEl, true);
      }
    }

    previewImages = providedImages.length ? providedImages : (fallbackImage ? [fallbackImage] : []);
    if (!previewImages.length && previewPlaceholder) previewImages = [previewPlaceholder];
    previewIndex = 0;
    applyPreviewImage();
    updatePreviewNav();
  };

  const shiftPreview = (direction) => {
    if (!previewImages.length || previewImages.length === 1) return;
    previewIndex = (previewIndex + direction + previewImages.length) % previewImages.length;
    applyPreviewImage();
  };

  const updateEnvelopeNav = () => {
    const hasMultiple = envelopeImages.length > 1;
    if (envelopePreviewPrevBtn) envelopePreviewPrevBtn.disabled = !hasMultiple;
    if (envelopePreviewNextBtn) envelopePreviewNextBtn.disabled = !hasMultiple;
  };

  const applyEnvelopeImage = () => {
    if (!envelopePreviewImageEl || !envelopeImages.length) return;
    const src = envelopeImages[envelopeIndex] ?? envelopeImages[0];
    if (src) {
      envelopePreviewImageEl.src = src;
      const name = envelopeNameEl?.textContent?.trim() || 'Envelope';
      envelopePreviewImageEl.alt = `Envelope preview — ${name}`;
    }
  };

  const renderEnvelopePreview = (summary) => {
    if (!envelopePreviewFrame || !envelopePreviewImageEl) return;

    const providedImages = Array.isArray(summary?.envelopeImages)
      ? summary.envelopeImages.filter((src) => typeof src === 'string' && src.length)
      : [];

    const fallbackImage = summary?.envelopeImage || summary?.envelope?.image || previewPlaceholder;

    if (envelopeNameEl) envelopeNameEl.textContent = summary?.envelope?.name || 'Envelope';
    if (envelopeEditLink) envelopeEditLink.href = envelopeUrl;
    if (envelopeQuantitySelect) envelopeQuantitySelect.value = summary?.envelope?.qty || summary?.quantity || 10;

    if (envelopeOptionElements.type) envelopeOptionElements.type.textContent = summary?.envelope?.type || 'Standard';
    if (envelopeOptionElements.color) envelopeOptionElements.color.textContent = summary?.envelope?.color || 'White';
    if (envelopeOptionElements.size) envelopeOptionElements.size.textContent = summary?.envelope?.size || 'A6';
    if (envelopeOptionElements.printing) envelopeOptionElements.printing.textContent = summary?.envelope?.printing || 'Included';

    const currentTotal = parseMoney(summary?.envelope?.price || summary?.envelope?.total || 0);
    const original = parseMoney(summary?.envelope?.originalPrice || currentTotal);
    const savings = original - currentTotal;

    if (envelopeOldTotalEl) envelopeOldTotalEl.textContent = formatMoney(original);
    if (envelopeNewTotalEl) envelopeNewTotalEl.textContent = formatMoney(currentTotal);
    if (envelopeSavingsEl) {
      if (savings > 0.009) {
        envelopeSavingsEl.textContent = `You saved ${formatMoney(savings)}`;
        setHidden(envelopeSavingsEl, false);
      } else {
        setHidden(envelopeSavingsEl, true);
      }
    }

    envelopeImages = providedImages.length ? providedImages : (fallbackImage ? [fallbackImage] : []);
    if (!envelopeImages.length && previewPlaceholder) envelopeImages = [previewPlaceholder];
    envelopeIndex = 0;
    applyEnvelopeImage();
    updateEnvelopeNav();
  };

  const shiftEnvelopePreview = (direction) => {
    if (!envelopeImages.length || envelopeImages.length === 1) return;
    envelopeIndex = (envelopeIndex + direction + envelopeImages.length) % envelopeImages.length;
    applyEnvelopeImage();
  };

  const updateGiveawaysNav = () => {
    const hasMultiple = giveawaysImages.length > 1;
    if (giveawaysPreviewPrevBtn) giveawaysPreviewPrevBtn.disabled = !hasMultiple;
    if (giveawaysPreviewNextBtn) giveawaysPreviewNextBtn.disabled = !hasMultiple;
  };

  const applyGiveawaysImage = () => {
    if (!giveawaysPreviewImageEl || !giveawaysImages.length) return;
    const src = giveawaysImages[giveawaysIndex] ?? giveawaysImages[0];
    if (src) {
      giveawaysPreviewImageEl.src = src;
      const name = giveawaysNameEl?.textContent?.trim() || 'Giveaways';
      giveawaysPreviewImageEl.alt = `Giveaways preview — ${name}`;
    }
  };

  const renderGiveawaysPreview = (summary) => {
    if (!giveawaysPreviewFrame || !giveawaysPreviewImageEl) return;

    const providedImages = Array.isArray(summary?.giveawaysImages)
      ? summary.giveawaysImages.filter((src) => typeof src === 'string' && src.length)
      : [];

    const fallbackImage = summary?.giveawaysImage || previewPlaceholder;

    if (giveawaysNameEl) giveawaysNameEl.textContent = summary?.giveaways?.name || 'Giveaways';
    if (giveawaysEditLink) giveawaysEditLink.href = giveawaysUrl;
    if (giveawaysQuantitySelect) giveawaysQuantitySelect.value = summary?.giveaways?.qty || summary?.quantity || 10;

    if (giveawaysOptionElements.type) giveawaysOptionElements.type.textContent = summary?.giveaways?.type || 'Keychain';
    if (giveawaysOptionElements.material) giveawaysOptionElements.material.textContent = summary?.giveaways?.material || 'Acrylic';
    if (giveawaysOptionElements.customization) giveawaysOptionElements.customization.textContent = summary?.giveaways?.customization || 'Included';

    const currentTotal = parseMoney(summary?.giveaways?.price || summary?.giveaways?.total || 0);
    const original = parseMoney(summary?.giveaways?.originalPrice || currentTotal);
    const savings = original - currentTotal;

    if (giveawaysOldTotalEl) giveawaysOldTotalEl.textContent = formatMoney(original);
    if (giveawaysNewTotalEl) giveawaysNewTotalEl.textContent = formatMoney(currentTotal);
    if (giveawaysSavingsEl) {
      if (savings > 0.009) {
        giveawaysSavingsEl.textContent = `You saved ${formatMoney(savings)}`;
        setHidden(giveawaysSavingsEl, false);
      } else {
        setHidden(giveawaysSavingsEl, true);
      }
    }

    giveawaysImages = providedImages.length ? providedImages : (fallbackImage ? [fallbackImage] : []);
    if (!giveawaysImages.length && previewPlaceholder) giveawaysImages = [previewPlaceholder];
    giveawaysIndex = 0;
    applyGiveawaysImage();
    updateGiveawaysNav();
  };

  const shiftGiveawaysPreview = (direction) => {
    if (!giveawaysImages.length || giveawaysImages.length === 1) return;
    giveawaysIndex = (giveawaysIndex + direction + giveawaysImages.length) % giveawaysImages.length;
    applyGiveawaysImage();
  };

  const updateSummaryCard = (summary) => {
    const invitationTotal = parseMoney(summary?.totalAmount ?? summary?.total);
    const envelopeTotal = parseMoney(summary?.envelope?.price ?? summary?.envelope?.total);
    const giveawaysTotal = parseMoney(summary?.giveaways?.price ?? summary?.giveaways?.total);

    const discountedSubtotal = invitationTotal + envelopeTotal + giveawaysTotal;
    const originalSubtotal = parseMoney(
      summary?.originalTotal ?? summary?.subtotalOriginal ?? discountedSubtotal
    );

    const savings = originalSubtotal - discountedSubtotal;

    if (subtotalOriginalEl) subtotalOriginalEl.textContent = formatMoney(originalSubtotal);
    if (subtotalDiscountedEl) subtotalDiscountedEl.textContent = formatMoney(discountedSubtotal);
    if (subtotalSavingsEl) {
      if (savings > 0.009) {
        subtotalSavingsEl.textContent = `You saved ${formatMoney(savings)}`;
        setHidden(subtotalSavingsEl, false);
      } else {
        setHidden(subtotalSavingsEl, true);
      }
    }
    if (grandTotalEl) grandTotalEl.textContent = formatMoney(discountedSubtotal);
  };

  const handleQuantityChange = (event) => {
    const current = getSummary();
    if (!current) return;

    const select = event?.target || previewQuantitySelect;
    if (!select) return;

    const value = Number(select.value);
    if (!Number.isFinite(value) || value <= 0) return;

    const options = getQuantityOptions(current);
    const selectedOption = options.find((option) => option.value === value) || options[0];
    if (!selectedOption) return;

    const extras = computeExtras(current);
    const basePrice = parseMoney(selectedOption.price ?? 0);
    const updatedTotal = basePrice + extras;

    current.quantity = selectedOption.value;
    current.quantityLabel = selectedOption.label;
    current.quantityOptions = options;
    current.totalAmount = updatedTotal;
    current.total = formatMoney(updatedTotal);
  current.total_amount = updatedTotal;
    current.subtotalAmount = updatedTotal;

    if (selectedOption.value > 0) {
      const recalculatedUnit = basePrice / selectedOption.value;
      if (Number.isFinite(recalculatedUnit)) {
        current.unitPrice = Math.round(recalculatedUnit * 100) / 100;
      }
    }

    const originalTotal = parseMoney(current.originalTotal ?? current.subtotalOriginal ?? 0);
    if (!Number.isFinite(originalTotal) || originalTotal < updatedTotal) {
      current.originalTotal = updatedTotal;
      current.subtotalOriginal = updatedTotal;
    }

    setSummary(current);
    renderSummary(current);
  };

  const showEmptyState = () => {
    setHidden(summaryGrid, true);
    setHidden(summaryCard, true);
    setHidden(layout, true);
    setHidden(emptyState, false);
  };

  const renderSummary = (summary) => {
    if (!summary || (!Number(summary.quantity) && !getQuantityOptions(summary).length)) {
      showEmptyState();
      return;
    }

    setHidden(emptyState, true);
    setHidden(layout, false);
    setHidden(summaryGrid, false);
    setHidden(summaryCard, false);

    renderPreview(summary);
    renderEnvelopePreview(summary);
    renderGiveawaysPreview(summary);
    updateSummaryCard(summary);
  };

  const redirectToCheckout = () => {
    window.location.href = checkoutUrl;
  };

  const summary = getSummary();
  renderSummary(summary);

  previewQuantitySelect?.addEventListener('change', handleQuantityChange);
  previewPrevBtn?.addEventListener('click', () => shiftPreview(-1));
  previewNextBtn?.addEventListener('click', () => shiftPreview(1));
  envelopePreviewPrevBtn?.addEventListener('click', () => shiftEnvelopePreview(-1));
  envelopePreviewNextBtn?.addEventListener('click', () => shiftEnvelopePreview(1));
  giveawaysPreviewPrevBtn?.addEventListener('click', () => shiftGiveawaysPreview(-1));
  giveawaysPreviewNextBtn?.addEventListener('click', () => shiftGiveawaysPreview(1));

  removeProductBtn?.addEventListener('click', (event) => {
    event.preventDefault();
    window.sessionStorage.removeItem(storageKey);
    showEmptyState();
    showToast('Invitation removed from your order.');
  });

  removeEnvelopeBtn?.addEventListener('click', (event) => {
    event.preventDefault();
    const current = getSummary();
    if (!current) return;
    if (current.envelope) delete current.envelope;
    setSummary(current);
    renderSummary(current);
    showToast('Envelope removed from your order.');
  });

  removeGiveawaysBtn?.addEventListener('click', (event) => {
    event.preventDefault();
    const current = getSummary();
    if (!current) return;
    if (current.giveaways) delete current.giveaways;
    setSummary(current);
    renderSummary(current);
    showToast('Giveaways removed from your order.');
  });

  checkoutBtn?.addEventListener('click', () => {
    const current = getSummary();
    if (!current || !current.quantity) {
      showToast('Add an invitation before checking out.');
      return;
    }
    redirectToCheckout();
  });
});
