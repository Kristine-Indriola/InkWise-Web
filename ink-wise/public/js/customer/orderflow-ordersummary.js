document.addEventListener('DOMContentLoaded', () => {
  const shell = document.querySelector('.os-shell');
  if (!shell) return;

  const storageKey = shell.dataset.storageKey || 'inkwise-finalstep';
  const envelopeUrl = shell.dataset.envelopesUrl || '/order/envelope';
  const checkoutUrl = shell.dataset.checkoutUrl || '/checkout';
  const editUrl = shell.dataset.editUrl || '/order/final-step';
  const giveawaysUrl = shell.dataset.giveawaysUrl || '/order/giveaways';
  const summaryUrl = shell.dataset.summaryUrl || '/order/summary';
  const summaryApiUrl = shell.dataset.summaryApi || `${summaryUrl}.json`;
  const summaryClearUrl = shell.dataset.summaryClearUrl || shell.dataset.orderClearUrl || '/order/summary';
  const envelopeClearUrl = shell.dataset.envelopeClearUrl || '/order/envelope';
  const giveawayClearUrl = shell.dataset.giveawayClearUrl || '/order/giveaways';
  const envelopeStoreUrl = shell.dataset.envelopeStoreUrl || '/order/envelope';
  const giveawayStoreUrl = shell.dataset.giveawayStoreUrl || '/order/giveaways';
  const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');

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
  if (previewEditLink) {
    previewEditLink.dataset.defaultHref = previewEditLink.getAttribute('href') || '';
  }
  const previewQuantitySelect = shell.querySelector('[data-preview-quantity]');
  const previewSavingsEl = shell.querySelector('[data-preview-savings]');
  const removeProductBtn = shell.querySelector('#osRemoveProductBtn');
  const envelopeCard = shell.querySelector('[data-envelope-card]');
  const giveawaysCard = shell.querySelector('[data-giveaways-card]');

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

  const formatQuantityOptionLabel = (option) => {
    if (!option) return '';
    const baseLabel = option.label && option.label.toString().trim().length
      ? option.label.toString().trim()
      : (option.value !== undefined ? `${option.value}` : 'Quantity');
    const priceValue = parseMoney(option.price ?? option.amount ?? 0);
    const hasPrice = Number.isFinite(priceValue);
    if (!hasPrice) {
      return baseLabel;
    }
    return `${baseLabel} — ${formatMoney(priceValue)}`;
  };

  const setHidden = (node, hidden) => {
    if (!node) return;
    node.hidden = Boolean(hidden);
  };

  const getCsrfToken = () => csrfTokenMeta?.getAttribute('content') ?? null;

  const applySummaryPayload = (payload) => {
    if (!payload || typeof payload !== 'object') {
      return null;
    }

    const summary = payload.summary ?? payload.data ?? null;
    if (summary && typeof summary === 'object') {
      setSummary(summary);
      return summary;
    }

    return null;
  };

  const fetchSummaryFromServer = async () => {
    if (!summaryApiUrl) {
      return null;
    }

    try {
      const response = await fetch(summaryApiUrl, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });

      if (!response.ok) {
        console.warn('Order summary API returned status', response.status);
        return null;
      }

      const payload = await response.json();
      const summary = payload?.data ?? payload;
      if (summary && typeof summary === 'object') {
        setSummary(summary);
        return summary;
      }
    } catch (error) {
      console.error('Failed to fetch order summary', error);
    }

    return null;
  };

  const requestDelete = async (url) => {
    if (!url) {
      return { ok: false };
    }

    const csrfToken = getCsrfToken();

    try {
      const response = await fetch(url, {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
        credentials: 'same-origin',
      });

      if (!response.ok) {
        return { ok: false, status: response.status };
      }

      let data = null;
      try {
        data = await response.json();
      } catch (error) {
        data = null;
      }

      return { ok: true, data };
    } catch (error) {
      console.error('Failed to send DELETE request', error);
      return { ok: false, status: 0, error };
    }
  };

  const requestPost = async (url, payload) => {
    if (!url) {
      return { ok: false };
    }

    const csrfToken = getCsrfToken();

    try {
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload ?? {}),
      });

      if (!response.ok) {
        return { ok: false, status: response.status };
      }

      let data = null;
      try {
        data = await response.json();
      } catch (error) {
        data = null;
      }

      return { ok: true, data };
    } catch (error) {
      console.error('Failed to send POST request', error);
      return { ok: false, status: 0, error };
    }
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

  const normaliseKey = (value) => {
    if (value === undefined || value === null) return '';
    return String(value)
      .trim()
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_+|_+$/g, '');
  };

  const normaliseUrl = (value) => {
    if (value === undefined || value === null) return null;
    const trimmed = String(value).trim();
    if (!trimmed.length) return null;
    if (/^javascript:/i.test(trimmed)) return null;
    return trimmed;
  };

  const getPreviewSelection = (summary, ...keys) => {
    if (!summary || typeof summary !== 'object') return null;
    const selections = summary.previewSelections;
    if (!selections || typeof selections !== 'object') return null;

    for (const key of keys) {
      if (!key) continue;
      const selection = selections[key];
      if (selection && typeof selection === 'object') {
        return selection;
      }
    }

    return null;
  };

  const getPreviewSelectionName = (summary, ...keys) => {
    const selection = getPreviewSelection(summary, ...keys);
    if (!selection || typeof selection !== 'object') return null;
    const candidate = selection.name ?? selection.label ?? selection.value ?? null;
    return hasValue(candidate) ? candidate : null;
  };

  const formatSelectionWithPrice = (payload, options = {}) => {
    if (!payload || typeof payload !== 'object') return null;
    const { showIncludedLabel = true } = options;
    const label = payload.name ?? payload.label ?? payload.value ?? null;
    if (!hasValue(label)) return null;

    const priceRaw = payload.price ?? payload.amount ?? payload.total ?? payload.price_value;
    const price = parseMoney(priceRaw);
    const hasPriceInfo = priceRaw !== undefined && priceRaw !== null && !(
      typeof priceRaw === 'string' && priceRaw.trim() === ''
    );

    if (hasPriceInfo && price > 0.009) {
      return `${label} — ${formatMoney(price)}`;
    }

    if (hasPriceInfo && price <= 0.009 && showIncludedLabel) {
      return `${label} — Included`;
    }

    return label;
  };

  const findAddonByTypeExact = (summary, ...types) => {
    const addons = Array.isArray(summary?.addons) ? summary.addons : [];
    if (!addons.length) return null;

    const targets = types
      .map((type) => normaliseKey(type))
      .filter((type) => type.length);

    if (!targets.length) return null;

    return addons.find((addon) => targets.includes(normaliseKey(addon?.type)));
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

  const buildSteppedQuantityOptions = ({
    minQty,
    maxQty,
    step = 10,
    unitPrice,
    selectedQuantity,
  }) => {
    const parsedUnitPrice = parseMoney(unitPrice ?? 0);
    const quantityStep = Number.isFinite(step) && step > 0 ? Math.floor(step) : 10;
    const baseStep = quantityStep > 0 ? quantityStep : 10;

    const initialMin = Number(minQty);
    let resolvedMin = Number.isFinite(initialMin) && initialMin > 0 ? initialMin : baseStep;
    if (resolvedMin % baseStep !== 0) {
      resolvedMin = Math.ceil(resolvedMin / baseStep) * baseStep;
    }
    resolvedMin = Math.max(baseStep, resolvedMin);

    const initialMax = Number(maxQty);
    let resolvedMax = Number.isFinite(initialMax) && initialMax > 0 ? initialMax : Number(selectedQuantity) || resolvedMin;
    if (resolvedMax < resolvedMin) {
      resolvedMax = resolvedMin;
    }
    if (resolvedMax % baseStep !== 0) {
      resolvedMax = Math.ceil(resolvedMax / baseStep) * baseStep;
    }

    const options = [];
    for (let qty = resolvedMin; qty <= resolvedMax; qty += baseStep) {
      const price = Math.round(parsedUnitPrice * qty * 100) / 100;
      options.push({
        value: qty,
        label: `${qty}`,
        price,
      });
    }

    const selectedQty = Number(selectedQuantity);
    if (Number.isFinite(selectedQty) && selectedQty > 0 && !options.some((option) => option.value === selectedQty)) {
      const price = Math.round(parsedUnitPrice * selectedQty * 100) / 100;
      options.push({
        value: selectedQty,
        label: `${selectedQty}`,
        price,
      });
    }

    const seen = new Set();
    return options
      .filter((option) => {
        if (!Number.isFinite(option.value) || option.value <= 0) return false;
        const key = option.value;
        if (seen.has(key)) return false;
        seen.add(key);
        return true;
      })
      .sort((a, b) => a.value - b.value);
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
          label: `${qty}`,
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
        () => summary?.previewSelections?.paper_stock?.price,
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
    );
    const finalEditUrl = normaliseUrl(resolvedEditUrl) || resolvePreviewEditUrl(summary) || editUrl;
    if (previewEditLink) {
      previewEditLink.href = finalEditUrl;
      previewEditLink.dataset.resolvedHref = finalEditUrl;
    }

    if (previewQuantitySelect) {
      const quantityContainer = previewQuantitySelect.closest('.os-preview-quantity');
      const quantityOptions = getQuantityOptions(summary);
      const currentQuantity = Number(summary?.quantity ?? quantityOptions[0]?.value ?? 0);

      previewQuantitySelect.innerHTML = '';

      if (quantityOptions.length) {
        quantityOptions.forEach((option) => {
          const opt = document.createElement('option');
          opt.value = String(option.value);
          opt.textContent = formatQuantityOptionLabel(option);
          const priceValue = parseMoney(option.price ?? 0);
          if (Number.isFinite(priceValue)) {
            opt.dataset.price = String(priceValue);
          }
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

    const orientationSelection = getPreviewSelection(summary, 'orientation')
      || findAddonByTypeExact(summary, 'orientation');
    const orientation = formatSelectionWithPrice(orientationSelection, { showIncludedLabel: false })
      || getPreviewSelectionName(summary, 'orientation')
      || getFirstValue(
        summary,
        'orientation',
        'metadata.invitation.orientation',
        'metadata.final_step.orientation',
        'metadata.product.orientation'
      );
    applyOption(optionElements.orientation, orientation);

    const foilAddon = findAddonMatch(summary, ['foil', 'metal', 'emboss']);
    const foilSelection = getPreviewSelection(summary, 'embossed_powder', 'foil', 'foil_color', 'metallic_powder')
      || findAddonByTypeExact(summary, 'embossed_powder', 'foil', 'foil_color', 'metallic_powder')
      || foilAddon;
    const foilColor = formatSelectionWithPrice(foilSelection)
      || getPreviewSelectionName(summary, 'embossed_powder', 'foil', 'foil_color', 'metallic_powder')
      || getFirstValue(
        summary,
        'foilColor',
        'metadata.final_step.foilColor',
        'metadata.invitation.foilColor'
      );
    applyOption(optionElements.foilColor, foilColor);

    const backsideAddon = findAddonMatch(summary, ['back', 'double', 'reverse']);
    const backsideSelection = getPreviewSelection(summary, 'backside', 'double_print', 'double_sided', 'reverse', 'back_print')
      || findAddonByTypeExact(summary, 'backside', 'double_print', 'double_sided', 'reverse')
      || backsideAddon;
    const backside = formatSelectionWithPrice(backsideSelection)
      || getPreviewSelectionName(summary, 'backside', 'double_print', 'double_sided', 'reverse', 'back_print')
      || getFirstValue(
        summary,
        'backside',
        'metadata.final_step.backside',
        'metadata.invitation.backside'
      );
    applyOption(optionElements.backside, backside);

    const trimAddon = findAddonMatch(summary, ['trim', 'edge', 'corner']);
    const trimSelection = getPreviewSelection(summary, 'trim', 'edge', 'edge_finish', 'edge_trim')
      || findAddonByTypeExact(summary, 'trim', 'edge', 'edge_finish', 'edge_trim', 'corner')
      || trimAddon;
    const trim = formatSelectionWithPrice(trimSelection)
      || getPreviewSelectionName(summary, 'trim', 'edge', 'edge_finish', 'edge_trim', 'corner')
      || getFirstValue(
        summary,
        'trim',
        'metadata.final_step.trim',
        'metadata.invitation.trim'
      );
    applyOption(optionElements.trim, trim);

    const sizeSelection = getPreviewSelection(summary, 'size')
      || findAddonByTypeExact(summary, 'size');
    const resolvedSize = formatSelectionWithPrice(sizeSelection, { showIncludedLabel: false })
      || getPreviewSelectionName(summary, 'size')
      || getFirstValue(
        summary,
        'size',
        'metadata.product.size',
        'metadata.template.size',
        'metadata.invitation.size'
      );
    applyOption(optionElements.size, resolvedSize);

    if (optionElements.paperStock) {
      const paperSelection = getPreviewSelection(summary, 'paper_stock');

      let paperName = getFirstValue(
        summary,
        'paperStockName',
        'paperStock.name',
        'metadata.paper_stock.name'
      );

      if (!hasValue(paperName) && paperSelection) {
        paperName = paperSelection.name ?? paperSelection.label ?? paperSelection.value ?? null;
      }
      if (!hasValue(paperName)) {
        paperName = getPreviewSelectionName(summary, 'paper_stock');
      }

      const rawPaperPrice = getFirstValue(
        summary,
        () => summary?.paperStockPrice,
        () => summary?.previewSelections?.paper_stock?.price,
        'paperStock.price',
        'metadata.paper_stock.price',
        () => paperSelection?.price
      );
      const hasPaperPrice = rawPaperPrice !== undefined && rawPaperPrice !== null && !(
        typeof rawPaperPrice === 'string' && rawPaperPrice.trim() === ''
      );
      const paperPrice = parseMoney(rawPaperPrice);

      const selectionDisplay = formatSelectionWithPrice(paperSelection);
      const display = selectionDisplay
        || (hasValue(paperName)
          ? `${paperName}${hasPaperPrice ? (paperPrice > 0.009 ? ` — ${formatMoney(paperPrice)}` : ' — Included') : ''}`
          : null);
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
  const envelopeMeta = summary?.envelope ?? summary?.metadata?.envelope ?? null;
  const hasEnvelope = summary?.hasEnvelope ?? Boolean(envelopeMeta && typeof envelopeMeta === 'object' && Object.keys(envelopeMeta).length);

    if (envelopeCard) setHidden(envelopeCard, !hasEnvelope);
    if (removeEnvelopeBtn) removeEnvelopeBtn.hidden = !hasEnvelope;

    if (!hasEnvelope) {
      if (envelopeQuantitySelect) {
        envelopeQuantitySelect.innerHTML = '';
        envelopeQuantitySelect.disabled = true;
      }
      envelopeImages = [];
      envelopeIndex = 0;
      if (envelopePreviewImageEl && previewPlaceholder) {
        envelopePreviewImageEl.src = previewPlaceholder;
        envelopePreviewImageEl.alt = 'Envelope preview placeholder';
      }
      if (envelopeOldTotalEl) envelopeOldTotalEl.textContent = formatMoney(0);
      if (envelopeNewTotalEl) envelopeNewTotalEl.textContent = formatMoney(0);
      if (envelopeSavingsEl) setHidden(envelopeSavingsEl, true);
      updateEnvelopeNav();
      return;
    }

    if (!envelopePreviewFrame || !envelopePreviewImageEl) return;

    const providedImages = Array.isArray(envelopeMeta.images)
      ? envelopeMeta.images.filter((src) => typeof src === 'string' && src.length)
      : [];

    const fallbackImage = envelopeMeta.image || summary?.envelopeImage || previewPlaceholder;

    if (envelopeNameEl) envelopeNameEl.textContent = envelopeMeta.name || 'Envelope';
    if (envelopeEditLink) envelopeEditLink.href = envelopeUrl;

    if (envelopeQuantitySelect) {
      const quantity = Number(envelopeMeta.qty ?? summary?.quantity ?? 0);
      const unitPrice = parseMoney(envelopeMeta.price ?? (quantity ? (envelopeMeta.total ?? 0) / quantity : 0));
      const minQty = Number(envelopeMeta.min_qty ?? 10);
      const maxQty = Number(envelopeMeta.max_qty ?? 0);

      const options = buildSteppedQuantityOptions({
        minQty,
        maxQty,
        step: 10,
        unitPrice,
        selectedQuantity: quantity,
      });

      envelopeQuantitySelect.innerHTML = '';

      options.forEach((option) => {
        const opt = document.createElement('option');
        opt.value = String(option.value);
        opt.textContent = formatQuantityOptionLabel(option);
        const priceValue = parseMoney(option.price ?? 0);
        if (Number.isFinite(priceValue)) {
          opt.dataset.price = String(priceValue);
        }
        envelopeQuantitySelect.appendChild(opt);
      });

      if (options.length) {
        const selectedOption = options.find((option) => option.value === quantity) || options[0];
        envelopeQuantitySelect.value = String(selectedOption.value);
        envelopeQuantitySelect.dataset.previousValue = String(selectedOption.value);
        envelopeQuantitySelect.dataset.minQty = String(Math.max(0, options[0].value));
        envelopeQuantitySelect.dataset.maxQty = String(options[options.length - 1].value);
        envelopeQuantitySelect.disabled = options.length === 1;
      } else {
        envelopeQuantitySelect.disabled = true;
        envelopeQuantitySelect.dataset.previousValue = '';
        envelopeQuantitySelect.dataset.minQty = '';
        envelopeQuantitySelect.dataset.maxQty = '';
      }
    }

    if (envelopeOptionElements.type) envelopeOptionElements.type.textContent = envelopeMeta.type || 'Standard';
    if (envelopeOptionElements.color) envelopeOptionElements.color.textContent = envelopeMeta.color || 'White';
    if (envelopeOptionElements.size) envelopeOptionElements.size.textContent = envelopeMeta.size || 'A6';
    if (envelopeOptionElements.printing) envelopeOptionElements.printing.textContent = envelopeMeta.printing || 'Included';

    const currentTotal = parseMoney(envelopeMeta.price ?? envelopeMeta.total ?? 0);
    const original = parseMoney(envelopeMeta.originalPrice ?? currentTotal);
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
    const giveawayMeta = summary?.giveaway
      ?? summary?.giveaways
      ?? summary?.metadata?.giveaway
      ?? null;
    const hasGiveaway = summary?.hasGiveaway ?? Boolean(giveawayMeta && typeof giveawayMeta === 'object' && Object.keys(giveawayMeta).length);

    if (giveawaysCard) setHidden(giveawaysCard, !hasGiveaway);
    if (removeGiveawaysBtn) removeGiveawaysBtn.hidden = !hasGiveaway;
    if (giveawaysEditLink) giveawaysEditLink.href = giveawaysUrl;

    if (!hasGiveaway) {
      if (giveawaysQuantitySelect) {
        giveawaysQuantitySelect.innerHTML = '';
        giveawaysQuantitySelect.disabled = true;
      }
      giveawaysImages = [];
      giveawaysIndex = 0;
      if (giveawaysPreviewImageEl && previewPlaceholder) {
        giveawaysPreviewImageEl.src = previewPlaceholder;
        giveawaysPreviewImageEl.alt = 'Giveaways preview placeholder';
      }
      if (giveawaysOldTotalEl) giveawaysOldTotalEl.textContent = formatMoney(0);
      if (giveawaysNewTotalEl) giveawaysNewTotalEl.textContent = formatMoney(0);
      if (giveawaysSavingsEl) setHidden(giveawaysSavingsEl, true);
      updateGiveawaysNav();
      return;
    }

    if (!giveawaysPreviewFrame || !giveawaysPreviewImageEl) return;

    const providedImages = Array.isArray(giveawayMeta.images)
      ? giveawayMeta.images.filter((src) => typeof src === 'string' && src.length)
      : [];

    const fallbackImage = giveawayMeta.image || previewPlaceholder;

    if (giveawaysNameEl) giveawaysNameEl.textContent = giveawayMeta.name || 'Giveaways';

    if (giveawaysQuantitySelect) {
      const quantity = Number(giveawayMeta.qty ?? summary?.quantity ?? 0);
      const unitPrice = parseMoney(giveawayMeta.price ?? (quantity ? (giveawayMeta.total ?? 0) / quantity : 0));
      const minQty = Number(giveawayMeta.min_qty ?? 10);
      const maxQty = Number(giveawayMeta.max_qty ?? 0);

      const options = buildSteppedQuantityOptions({
        minQty,
        maxQty,
        step: 10,
        unitPrice,
        selectedQuantity: quantity,
      });

      giveawaysQuantitySelect.innerHTML = '';

      options.forEach((option) => {
        const opt = document.createElement('option');
        opt.value = String(option.value);
        opt.textContent = formatQuantityOptionLabel(option);
        const priceValue = parseMoney(option.price ?? 0);
        if (Number.isFinite(priceValue)) {
          opt.dataset.price = String(priceValue);
        }
        giveawaysQuantitySelect.appendChild(opt);
      });

      if (options.length) {
        const selectedOption = options.find((option) => option.value === quantity) || options[0];
        giveawaysQuantitySelect.value = String(selectedOption.value);
        giveawaysQuantitySelect.dataset.previousValue = String(selectedOption.value);
        giveawaysQuantitySelect.dataset.minQty = String(Math.max(0, options[0].value));
        giveawaysQuantitySelect.dataset.maxQty = String(options[options.length - 1].value);
        giveawaysQuantitySelect.disabled = options.length === 1;
      } else {
        giveawaysQuantitySelect.disabled = true;
        giveawaysQuantitySelect.dataset.previousValue = '';
        giveawaysQuantitySelect.dataset.minQty = '';
        giveawaysQuantitySelect.dataset.maxQty = '';
      }
    }

    if (giveawaysOptionElements.type) giveawaysOptionElements.type.textContent = giveawayMeta.type || giveawayMeta.name || 'Giveaway';
    if (giveawaysOptionElements.material) giveawaysOptionElements.material.textContent = giveawayMeta.material || '—';
    if (giveawaysOptionElements.customization) giveawaysOptionElements.customization.textContent = giveawayMeta.customization || 'Included';

    const currentTotal = parseMoney(giveawayMeta.total ?? giveawayMeta.price ?? 0);
    const original = parseMoney(giveawayMeta.originalPrice ?? currentTotal);
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
    const subtotalAmount = parseMoney(summary?.subtotalAmount ?? summary?.subtotal ?? summary?.totalAmount ?? 0);
    const originalSubtotal = parseMoney(
      summary?.originalSubtotal
        ?? summary?.subtotalOriginal
        ?? summary?.originalTotal
        ?? subtotalAmount
    );
    const grandTotal = parseMoney(summary?.totalAmount ?? summary?.total ?? subtotalAmount);

    const savings = originalSubtotal - subtotalAmount;

    if (subtotalOriginalEl) subtotalOriginalEl.textContent = formatMoney(originalSubtotal);
    if (subtotalDiscountedEl) subtotalDiscountedEl.textContent = formatMoney(subtotalAmount);
    if (subtotalSavingsEl) {
      if (savings > 0.009) {
        subtotalSavingsEl.textContent = `You saved ${formatMoney(savings)}`;
        setHidden(subtotalSavingsEl, false);
      } else {
        setHidden(subtotalSavingsEl, true);
      }
    }
    if (grandTotalEl) grandTotalEl.textContent = formatMoney(grandTotal);
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

  const resolvePreviewEditUrl = (summary) => {
    const summaryCandidate = summary
      ? getFirstValue(
          summary,
          'editUrl',
          'metadata.editUrl',
          'metadata.links.edit',
          () => summary?.previewSelections?.editUrl,
        )
      : null;

    const candidates = [
      summaryCandidate,
      previewEditLink?.dataset.resolvedHref,
      previewEditLink?.dataset.defaultHref,
      shell.dataset.editUrl,
      previewEditLink?.getAttribute('href'),
      editUrl,
    ];

    for (const candidate of candidates) {
      const normalised = normaliseUrl(candidate);
      if (normalised) {
        return normalised;
      }
    }

    return null;
  };

  const handleEnvelopeQuantityChange = async (event) => {
    const select = event?.target || envelopeQuantitySelect;
    if (!select || select.disabled) return;

    const summary = getSummary();
    const envelopeMeta = summary?.envelope || summary?.metadata?.envelope;
    if (!envelopeMeta) {
      return;
    }

    const newQuantity = Number(select.value);
    if (!Number.isFinite(newQuantity) || newQuantity <= 0) {
      select.value = String(envelopeMeta.qty ?? envelopeQuantitySelect?.dataset.previousValue ?? '');
      return;
    }

    const previousQuantity = Number(envelopeMeta.qty ?? select.dataset.previousValue ?? newQuantity);
    if (previousQuantity === newQuantity) {
      return;
    }

    const unitPrice = parseMoney(envelopeMeta.price ?? (previousQuantity ? (envelopeMeta.total ?? 0) / previousQuantity : 0));
    const totalPrice = Math.round(unitPrice * newQuantity * 100) / 100;

    if (!envelopeStoreUrl) {
      select.value = String(previousQuantity);
      showToast('Envelope updates are unavailable right now.');
      return;
    }

    select.disabled = true;

    const payload = {
      product_id: envelopeMeta.product_id ?? null,
      envelope_id: envelopeMeta.id ?? null,
      quantity: newQuantity,
      unit_price: unitPrice,
      total_price: totalPrice,
      metadata: {
        name: envelopeMeta.name ?? null,
        material: envelopeMeta.material ?? null,
        image: envelopeMeta.image ?? null,
        min_qty: Number(envelopeMeta.min_qty ?? select.dataset.minQty ?? 10) || 10,
        max_qty: Number(envelopeMeta.max_qty ?? select.dataset.maxQty ?? newQuantity) || newQuantity,
      },
    };

    const result = await requestPost(envelopeStoreUrl, payload);

    select.disabled = false;

    if (result.ok) {
      const updatedSummary = applySummaryPayload(result.data) ?? await fetchSummaryFromServer();
      const summaryToRender = updatedSummary ?? getSummary();
      renderSummary(summaryToRender);
      showToast(`Envelope quantity updated to ${newQuantity}`);
      return;
    }

    const status = result.status ?? 0;
    if (status === 409 || status === 422) {
      const refreshed = await fetchSummaryFromServer();
      renderSummary(refreshed ?? getSummary());
      showToast('Envelope updated elsewhere. Refreshed details.');
    } else {
      select.value = String(previousQuantity);
      select.dataset.previousValue = String(previousQuantity);
      showToast('Unable to update envelope quantity. Please try again.');
    }
  };

  const handleGiveawayQuantityChange = async (event) => {
    const select = event?.target || giveawaysQuantitySelect;
    if (!select || select.disabled) return;

    const summary = getSummary();
    const giveawayMeta = summary?.giveaway
      ?? summary?.giveaways
      ?? summary?.metadata?.giveaway;

    if (!giveawayMeta) {
      return;
    }

    const newQuantity = Number(select.value);
    if (!Number.isFinite(newQuantity) || newQuantity <= 0) {
      select.value = String(giveawayMeta.qty ?? select.dataset.previousValue ?? '');
      return;
    }

    const previousQuantity = Number(giveawayMeta.qty ?? select.dataset.previousValue ?? newQuantity);
    if (previousQuantity === newQuantity) {
      return;
    }

    const unitPrice = parseMoney(giveawayMeta.price ?? (previousQuantity ? (giveawayMeta.total ?? 0) / previousQuantity : 0));
    const totalPrice = Math.round(unitPrice * newQuantity * 100) / 100;

    if (!giveawayStoreUrl) {
      select.value = String(previousQuantity);
      showToast('Giveaway updates are unavailable right now.');
      return;
    }

    select.disabled = true;

    const payload = {
      product_id: giveawayMeta.product_id ?? giveawayMeta.id ?? null,
      quantity: newQuantity,
      unit_price: unitPrice,
      total_price: totalPrice,
      metadata: {
        id: giveawayMeta.id ?? null,
        name: giveawayMeta.name ?? null,
        image: giveawayMeta.image ?? null,
        description: giveawayMeta.description ?? null,
        min_qty: Number(giveawayMeta.min_qty ?? select.dataset.minQty ?? 10) || 10,
        max_qty: Number(giveawayMeta.max_qty ?? select.dataset.maxQty ?? newQuantity) || newQuantity,
      },
    };

    const result = await requestPost(giveawayStoreUrl, payload);

    select.disabled = false;

    if (result.ok) {
      const updatedSummary = applySummaryPayload(result.data) ?? await fetchSummaryFromServer();
      const summaryToRender = updatedSummary ?? getSummary();
      renderSummary(summaryToRender);
      showToast(`Giveaway quantity updated to ${newQuantity}`);
      return;
    }

    const status = result.status ?? 0;
    if (status === 409 || status === 422) {
      const refreshed = await fetchSummaryFromServer();
      renderSummary(refreshed ?? getSummary());
      showToast('Giveaway selection was refreshed.');
    } else {
      select.value = String(previousQuantity);
      select.dataset.previousValue = String(previousQuantity);
      showToast('Unable to update giveaway quantity. Please try again.');
    }
  };

  const showEmptyState = () => {
    setHidden(summaryGrid, true);
    setHidden(summaryCard, true);
    setHidden(layout, true);
    setHidden(emptyState, false);
    if (removeEnvelopeBtn) removeEnvelopeBtn.hidden = true;
    if (removeGiveawaysBtn) removeGiveawaysBtn.hidden = true;
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

  const bootstrapSummary = async () => {
    const remoteSummary = await fetchSummaryFromServer();
    if (remoteSummary) {
      renderSummary(remoteSummary);
      return;
    }

    renderSummary(getSummary());
  };

  bootstrapSummary();

  previewQuantitySelect?.addEventListener('change', handleQuantityChange);
  previewEditLink?.addEventListener('click', async (event) => {
    event.preventDefault();

    let summary = getSummary();
    if (!summary) {
      summary = await fetchSummaryFromServer();
    }

    const targetUrl = resolvePreviewEditUrl(summary) || editUrl;
    if (targetUrl) {
      window.location.href = targetUrl;
    } else {
      showToast('Unable to open the design editor right now.');
    }
  });
  envelopeQuantitySelect?.addEventListener('change', handleEnvelopeQuantityChange);
  giveawaysQuantitySelect?.addEventListener('change', handleGiveawayQuantityChange);
  previewPrevBtn?.addEventListener('click', () => shiftPreview(-1));
  previewNextBtn?.addEventListener('click', () => shiftPreview(1));
  envelopePreviewPrevBtn?.addEventListener('click', () => shiftEnvelopePreview(-1));
  envelopePreviewNextBtn?.addEventListener('click', () => shiftEnvelopePreview(1));
  giveawaysPreviewPrevBtn?.addEventListener('click', () => shiftGiveawaysPreview(-1));
  giveawaysPreviewNextBtn?.addEventListener('click', () => shiftGiveawaysPreview(1));

  removeProductBtn?.addEventListener('click', async (event) => {
    event.preventDefault();
    if (removeProductBtn.disabled) return;

    if (!summaryClearUrl) {
      window.sessionStorage.removeItem(storageKey);
      renderSummary(null);
      showToast('Invitation removed from your order.');
      return;
    }

    removeProductBtn.disabled = true;
    const result = await requestDelete(summaryClearUrl);
    removeProductBtn.disabled = false;

    if (result.ok) {
      window.sessionStorage.removeItem(storageKey);
      renderSummary(null);
      showToast('Invitation removed from your order.');
      return;
    }

    if (result.status === 409 || result.status === 422) {
      const refreshed = await fetchSummaryFromServer();
      renderSummary(refreshed ?? getSummary());
      showToast('Unable to remove invitation. Summary refreshed.');
      return;
    }

    showToast('Unable to remove invitation. Please try again.');
  });

  removeEnvelopeBtn?.addEventListener('click', async (event) => {
    event.preventDefault();
    if (removeEnvelopeBtn.disabled) return;

    if (!envelopeClearUrl) {
      const current = getSummary();
      if (!current) return;
      delete current.envelope;
      if (current.metadata && typeof current.metadata === 'object') {
        delete current.metadata.envelope;
      }
      current.hasEnvelope = false;
      setSummary(current);
      renderSummary(current);
      showToast('Envelope removed from your order.');
      return;
    }

    removeEnvelopeBtn.disabled = true;
    const result = await requestDelete(envelopeClearUrl);
    removeEnvelopeBtn.disabled = false;

    if (result.ok) {
      const summary = applySummaryPayload(result.data) ?? await fetchSummaryFromServer();
      renderSummary(summary ?? getSummary());
      showToast('Envelope removed from your order.');
      return;
    }

    if (result.status === 409 || result.status === 422) {
      const refreshed = await fetchSummaryFromServer();
      renderSummary(refreshed ?? getSummary());
      showToast('Envelope updated elsewhere. Refreshed options.');
      return;
    }

    showToast('Unable to remove envelope. Please try again.');
  });

  removeGiveawaysBtn?.addEventListener('click', async (event) => {
    event.preventDefault();
    if (removeGiveawaysBtn.disabled) return;

    if (!giveawayClearUrl) {
      const current = getSummary();
      if (!current) return;
      delete current.giveaway;
      delete current.giveaways;
      if (current.metadata && typeof current.metadata === 'object') {
        delete current.metadata.giveaway;
      }
      current.hasGiveaway = false;
      setSummary(current);
      renderSummary(current);
      showToast('Giveaway removed from your order.');
      return;
    }

    removeGiveawaysBtn.disabled = true;
    const result = await requestDelete(giveawayClearUrl);
    removeGiveawaysBtn.disabled = false;

    if (result.ok) {
      const summary = applySummaryPayload(result.data) ?? await fetchSummaryFromServer();
      renderSummary(summary ?? getSummary());
      showToast('Giveaway removed from your order.');
      return;
    }

    if (result.status === 409 || result.status === 422) {
      const refreshed = await fetchSummaryFromServer();
      renderSummary(refreshed ?? getSummary());
      showToast('Giveaway selection refreshed.');
      return;
    }

    showToast('Unable to remove giveaway. Please try again.');
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
