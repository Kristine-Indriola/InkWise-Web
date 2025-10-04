document.addEventListener('DOMContentLoaded', () => {
  const shell = document.querySelector('.os-shell');
  const layout = document.querySelector('[data-summary-wrapper]');
  const summaryGrid = document.querySelector('[data-summary-grid]');
  const summaryCard = document.querySelector('[data-summary-card]');
  const emptyState = document.querySelector('[data-empty-state]');
  const previewFrame = document.querySelector('[data-preview-frame]');
  const previewImageEl = document.querySelector('[data-preview-image]');
  const previewPrevBtn = document.querySelector('[data-preview-prev]');
  const previewNextBtn = document.querySelector('[data-preview-next]');
  const previewNameEl = document.querySelector('[data-preview-name]');
  const previewEditLink = document.querySelector('[data-preview-edit]');
  const previewQuantitySelect = document.querySelector('[data-preview-quantity]');
  const removeProductBtn = document.getElementById('osRemoveProductBtn');
  const optionElements = {
    foldType: document.querySelector('[data-option="fold-type"]'),
    orientation: document.querySelector('[data-option="orientation"]'),
    foilColor: document.querySelector('[data-option="foil-color"]'),
    backside: document.querySelector('[data-option="backside"]'),
    trim: document.querySelector('[data-option="trim"]'),
    size: document.querySelector('[data-option="size"]'),
    paperStock: document.querySelector('[data-option="paper-stock"]')
  };
  const previewOldTotalEl = document.querySelector('[data-preview-old-total]');
  const previewNewTotalEl = document.querySelector('[data-preview-new-total]');
  const envelopePreviewFrame = document.querySelector('[data-envelope-preview-frame]');
  const envelopePreviewImageEl = document.querySelector('[data-envelope-preview-image]');
  const envelopePreviewPrevBtn = document.querySelector('[data-envelope-preview-prev]');
  const envelopePreviewNextBtn = document.querySelector('[data-envelope-preview-next]');
  const envelopeNameEl = document.querySelector('[data-envelope-name]');
  const envelopeEditLink = document.querySelector('[data-envelope-edit]');
  const envelopeQuantitySelect = document.querySelector('[data-envelope-quantity]');
  const removeEnvelopeBtn = document.getElementById('osRemoveEnvelopeBtn');
  const envelopeOptionElements = {
    type: document.querySelector('[data-envelope-option="type"]'),
    color: document.querySelector('[data-envelope-option="color"]'),
    size: document.querySelector('[data-envelope-option="size"]'),
    printing: document.querySelector('[data-envelope-option="printing"]')
  };
  const envelopeOldTotalEl = document.querySelector('[data-envelope-old-total]');
  const envelopeNewTotalEl = document.querySelector('[data-envelope-new-total]');
  const envelopeSavingsEl = document.querySelector('[data-envelope-savings]');
  const giveawaysPreviewFrame = document.querySelector('[data-giveaways-preview-frame]');
  const giveawaysPreviewImageEl = document.querySelector('[data-giveaways-preview-image]');
  const giveawaysPreviewPrevBtn = document.querySelector('[data-giveaways-preview-prev]');
  const giveawaysPreviewNextBtn = document.querySelector('[data-giveaways-preview-next]');
  const giveawaysNameEl = document.querySelector('[data-giveaways-name]');
  const giveawaysEditLink = document.querySelector('[data-giveaways-edit]');
  const giveawaysQuantitySelect = document.querySelector('[data-giveaways-quantity]');
  const removeGiveawaysBtn = document.getElementById('osRemoveGiveawaysBtn');
  const giveawaysOptionElements = {
    type: document.querySelector('[data-giveaways-option="type"]'),
    material: document.querySelector('[data-giveaways-option="material"]'),
    customization: document.querySelector('[data-giveaways-option="customization"]')
  };
  const giveawaysOldTotalEl = document.querySelector('[data-giveaways-old-total]');
  const giveawaysNewTotalEl = document.querySelector('[data-giveaways-new-total]');
  const giveawaysSavingsEl = document.querySelector('[data-giveaways-savings]');
  const subtotalOriginalEl = document.querySelector('[data-summary="subtotal-original"]');
  const subtotalDiscountedEl = document.querySelector('[data-summary="subtotal-discounted"]');
  const subtotalSavingsEl = document.querySelector('[data-summary="subtotal-savings"]');
  const grandTotalEl = document.querySelector('[data-summary="grand-total"]');
  const toast = document.getElementById('osToast');

  const checkoutBtn = document.getElementById('osCheckoutBtn');
  const shellEditUrl = shell?.dataset?.editUrl;
  const previewPlaceholder = previewImageEl?.getAttribute('src') ?? '';
  let previewImages = [];
  let previewIndex = 0;
  let envelopeImages = [];
  let envelopeIndex = 0;
  let giveawaysImages = [];
  let giveawaysIndex = 0;

  const getSummary = () => {
    const raw = window.sessionStorage.getItem('inkwise-finalstep');
    if (!raw) return null;
    try {
      return JSON.parse(raw);
    } catch (error) {
      console.warn('Failed to parse saved summary', error);
      return null;
    }
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

  const showToast = (message) => {
    if (!toast) return;
    toast.textContent = message;
    toast.hidden = false;
    requestAnimationFrame(() => toast.classList.add('visible'));
    window.setTimeout(() => toast.classList.remove('visible'), 2200);
    window.setTimeout(() => {
      if (!toast.classList.contains('visible')) {
        toast.hidden = true;
      }
    }, 2600);
  };

  const prettifyLabel = (value) => {
    if (!value) return 'Custom add-on';
    return value
      .replace(/[_-]+/g, ' ')
      .replace(/\s+/g, ' ')
      .trim()
      .replace(/\b\w/g, (char) => char.toUpperCase());
  };

  const updatePreviewNav = () => {
    const hasMultiple = previewImages.length > 1;
    if (previewPrevBtn) previewPrevBtn.disabled = !hasMultiple;
    if (previewNextBtn) previewNextBtn.disabled = !hasMultiple;
  };

  const applyPreviewImage = () => {
    if (!previewImageEl || !previewImages.length) return;
    const currentSrc = previewImages[previewIndex] ?? previewImages[0];
    if (currentSrc) {
      previewImageEl.src = currentSrc;
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

    const fallbackImage = summary?.previewImage || summary?.invitationImage || summary?.primaryImage || previewPlaceholder;

    if (previewNameEl) {
      previewNameEl.textContent = summary?.productName || 'Custom invitation';
    }

    if (previewEditLink) {
      const customEdit = summary?.editUrl || shellEditUrl;
      if (customEdit) previewEditLink.href = customEdit;
    }

    if (previewQuantitySelect) {
      const qty = summary?.quantity || 10;
      previewQuantitySelect.value = qty;
    }

    // Populate options
    if (optionElements.foldType) optionElements.foldType.textContent = summary?.foldType || 'Half Fold';
    if (optionElements.orientation) optionElements.orientation.textContent = summary?.orientation || 'Portrait';
    if (optionElements.foilColor) optionElements.foilColor.textContent = summary?.foilColor ? summary.foilColor : 'Included';
    if (optionElements.backside) optionElements.backside.textContent = summary?.backside || 'None';
    if (optionElements.trim) optionElements.trim.textContent = summary?.trim || 'Straight Cut';
    if (optionElements.size) optionElements.size.textContent = summary?.size || '5x7 inches';
    if (optionElements.paperStock) {
      const paperName = summary?.paperStockName || 'Standard';
      const price = parseMoney(summary?.paperStockPrice || 0);
      const originalPrice = parseMoney(summary?.paperStockOriginalPrice || price);
      let priceText = paperName;
      if (price > 0) {
        if (originalPrice > price) {
          priceText += ` — <span class="os-price-old">${formatMoney(originalPrice)}</span> <span class="os-price-new">${formatMoney(price)}</span>`;
        } else {
          priceText += ` — ${formatMoney(price)}`;
        }
      } else {
        priceText += ' — Included';
      }
      optionElements.paperStock.innerHTML = priceText;
    }

    // Calculate item total
    const basePrice = parseMoney(summary?.totalAmount ?? summary?.total ?? 0);
    const originalBasePrice = parseMoney(summary?.originalTotal ?? summary?.subtotalOriginal ?? basePrice);
    const savings = originalBasePrice - basePrice;

    if (previewOldTotalEl) {
      previewOldTotalEl.textContent = formatMoney(originalBasePrice);
    }
    if (previewNewTotalEl) {
      previewNewTotalEl.textContent = formatMoney(basePrice);
    }
    if (previewSavingsEl) {
      if (savings > 0.009) {
        previewSavingsEl.textContent = `You saved ${formatMoney(savings)}`;
        setHidden(previewSavingsEl, false);
      } else {
        setHidden(previewSavingsEl, true);
      }
    }

    previewImages = providedImages.length ? providedImages : (fallbackImage ? [fallbackImage] : []);
    if (!previewImages.length && previewPlaceholder) {
      previewImages = [previewPlaceholder];
    }

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
    const currentSrc = envelopeImages[envelopeIndex] ?? envelopeImages[0];
    if (currentSrc) {
      envelopePreviewImageEl.src = currentSrc;
      if (envelopeNameEl) {
        envelopePreviewImageEl.alt = `Envelope preview — ${envelopeNameEl.textContent?.trim() || 'Envelope'}`;
      }
    }
  };

  const renderEnvelopePreview = (summary) => {
    if (!envelopePreviewFrame || !envelopePreviewImageEl) return;

    const providedImages = Array.isArray(summary?.envelopeImages)
      ? summary.envelopeImages.filter((src) => typeof src === 'string' && src.length)
      : [];

    const fallbackImage = summary?.envelopeImage || summary?.envelope?.image || previewPlaceholder;

    if (envelopeNameEl) {
      envelopeNameEl.textContent = summary?.envelopeName || summary?.envelope?.name || 'Envelope';
    }

    if (envelopeEditLink) {
      envelopeEditLink.href = shell?.dataset?.envelopesUrl || '/order/envelope';
    }

    if (envelopeQuantitySelect) {
      const qty = summary?.envelope?.qty || 10;
      envelopeQuantitySelect.value = qty;
    }

    // Populate options
    if (envelopeOptionElements.type) envelopeOptionElements.type.textContent = summary?.envelope?.type || 'Standard';
    if (envelopeOptionElements.color) envelopeOptionElements.color.textContent = summary?.envelope?.color || 'White';
    if (envelopeOptionElements.size) envelopeOptionElements.size.textContent = summary?.envelope?.size || 'A6';
    if (envelopeOptionElements.printing) envelopeOptionElements.printing.textContent = summary?.envelope?.printing ? summary.envelope.printing : 'Included';

    // Calculate envelope total
    const price = parseMoney(summary?.envelope?.price || 0);
    const originalPrice = parseMoney(summary?.envelope?.originalPrice || price);
    const savings = originalPrice - price;

    if (envelopeOldTotalEl) {
      envelopeOldTotalEl.textContent = formatMoney(originalPrice);
    }
    if (envelopeNewTotalEl) {
      envelopeNewTotalEl.textContent = formatMoney(price);
    }
    if (envelopeSavingsEl) {
      if (savings > 0.009) {
        envelopeSavingsEl.textContent = `You saved ${formatMoney(savings)}`;
        setHidden(envelopeSavingsEl, false);
      } else {
        setHidden(envelopeSavingsEl, true);
      }
    }

    envelopeImages = providedImages.length ? providedImages : (fallbackImage ? [fallbackImage] : []);
    if (!envelopeImages.length && previewPlaceholder) {
      envelopeImages = [previewPlaceholder];
    }

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
    const currentSrc = giveawaysImages[giveawaysIndex] ?? giveawaysImages[0];
    if (currentSrc) {
      giveawaysPreviewImageEl.src = currentSrc;
      if (giveawaysNameEl) {
        giveawaysPreviewImageEl.alt = `Giveaways preview — ${giveawaysNameEl.textContent?.trim() || 'Giveaways'}`;
      }
    }
  };

  const renderGiveawaysPreview = (summary) => {
    if (!giveawaysPreviewFrame || !giveawaysPreviewImageEl) return;

    const providedImages = Array.isArray(summary?.giveawaysImages)
      ? summary.giveawaysImages.filter((src) => typeof src === 'string' && src.length)
      : [];

    const fallbackImage = summary?.giveawaysImage || previewPlaceholder;

    if (giveawaysNameEl) {
      giveawaysNameEl.textContent = summary?.giveawaysName || 'Giveaways';
    }

    if (giveawaysEditLink) {
      giveawaysEditLink.href = '#'; // placeholder
    }

    if (giveawaysQuantitySelect) {
      const qty = summary?.giveaways?.qty || 10;
      giveawaysQuantitySelect.value = qty;
    }

    // Populate options
    if (giveawaysOptionElements.type) giveawaysOptionElements.type.textContent = summary?.giveaways?.type || 'Keychain';
    if (giveawaysOptionElements.material) giveawaysOptionElements.material.textContent = summary?.giveaways?.material || 'Plastic';
    if (giveawaysOptionElements.customization) giveawaysOptionElements.customization.textContent = summary?.giveaways?.customization ? summary.giveaways.customization : 'Included';

    // Calculate giveaways total
    const price = parseMoney(summary?.giveaways?.price || 0);
    const originalPrice = parseMoney(summary?.giveaways?.originalPrice || price);
    const savings = originalPrice - price;

    if (giveawaysOldTotalEl) {
      giveawaysOldTotalEl.textContent = formatMoney(originalPrice);
    }
    if (giveawaysNewTotalEl) {
      giveawaysNewTotalEl.textContent = formatMoney(price);
    }
    if (giveawaysSavingsEl) {
      if (savings > 0.009) {
        giveawaysSavingsEl.textContent = `You saved ${formatMoney(savings)}`;
        setHidden(giveawaysSavingsEl, false);
      } else {
        setHidden(giveawaysSavingsEl, true);
      }
    }

    giveawaysImages = providedImages.length ? providedImages : (fallbackImage ? [fallbackImage] : []);
    if (!giveawaysImages.length && previewPlaceholder) {
      giveawaysImages = [previewPlaceholder];
    }

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
    const envelopeTotal = parseMoney(summary?.envelope?.total);
    const discountedSubtotal = invitationTotal + envelopeTotal;
    const originalSubtotal = parseMoney(
      summary?.originalTotal ?? summary?.subtotalOriginal ?? summary?.totalBeforeDiscount ?? discountedSubtotal
    );
    const savings = originalSubtotal - discountedSubtotal;

    if (subtotalOriginalEl) {
      subtotalOriginalEl.textContent = formatMoney(originalSubtotal);
    }
    if (subtotalDiscountedEl) {
      subtotalDiscountedEl.textContent = formatMoney(discountedSubtotal);
    }
    if (subtotalSavingsEl) {
      if (savings > 0.009) {
        subtotalSavingsEl.textContent = `You saved ${formatMoney(savings)}`;
        setHidden(subtotalSavingsEl, false);
      } else {
        setHidden(subtotalSavingsEl, true);
      }
    }
    if (grandTotalEl) {
      grandTotalEl.textContent = formatMoney(discountedSubtotal);
    }
  };

  const renderSummary = (summary) => {
    if (!summary) {
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

  const showEmptyState = () => {
    setHidden(summaryGrid, true);
    setHidden(summaryCard, true);
    setHidden(layout, true);
    setHidden(emptyState, false);
  };

  const redirectToEnvelopes = () => {
    const fallback = '/order/envelope';
    const target = shell?.dataset.envelopesUrl || fallback;
    window.location.href = target;
  };

  const redirectToCheckout = () => {
    const fallback = '/checkout';
    const target = shell?.dataset.checkoutUrl || fallback;
    window.location.href = target;
  };

  const goToCheckout = () => {
    const latest = getSummary();
    // Allow checkout even if no session data exists
    redirectToCheckout();
  };

  const summary = getSummary();
  if (!summary || !summary.quantity) {
    showEmptyState();
  } else {
    renderSummary(summary);
  }

  previewPrevBtn?.addEventListener('click', () => shiftPreview(-1));
  previewNextBtn?.addEventListener('click', () => shiftPreview(1));

  envelopePreviewPrevBtn?.addEventListener('click', () => shiftEnvelopePreview(-1));
  envelopePreviewNextBtn?.addEventListener('click', () => shiftEnvelopePreview(1));

  giveawaysPreviewPrevBtn?.addEventListener('click', () => shiftGiveawaysPreview(-1));
  giveawaysPreviewNextBtn?.addEventListener('click', () => shiftGiveawaysPreview(1));

  removeProductBtn?.addEventListener('click', (event) => {
    event.preventDefault();
    window.sessionStorage.removeItem('inkwise-finalstep');
    showEmptyState();
    showToast('Product removed from your cart.');
  });

  removeEnvelopeBtn?.addEventListener('click', (event) => {
    event.preventDefault();
    const current = getSummary();
    if (!current) return;
    if (current.envelope) delete current.envelope;
    window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(current));
    renderSummary(current);
    showToast('Envelope removed from your order.');
  });

  removeGiveawaysBtn?.addEventListener('click', (event) => {
    event.preventDefault();
    const current = getSummary();
    if (!current) return;
    if (current.giveaways) delete current.giveaways;
    window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(current));
    renderSummary(current);
    showToast('Giveaways removed from your order.');
  });

  checkoutBtn?.addEventListener('click', () => goToCheckout());

});
