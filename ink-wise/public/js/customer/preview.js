document.addEventListener('DOMContentLoaded', () => {
  console.log('Preview JS loaded');
  const flipper = document.getElementById('flipper');
  const frontBtn = document.getElementById('frontBtn');
  const backBtn = document.getElementById('backBtn');
  console.log('Flipper:', flipper, 'FrontBtn:', frontBtn, 'BackBtn:', backBtn);
  const colorBtns = Array.from(document.querySelectorAll('.color-btn'));
  const selectableCards = Array.from(document.querySelectorAll('.selectable-card'));
  const editBtn = document.querySelector('.edit-btn');
  const selectionToast = document.getElementById('addonToast');
  const optionalAddonGroups = ['trim', 'embossed_powder', 'orientation', 'size'];
  const selectionState = {};
  const imageCache = new Map();
  let toastTimeout = null;

  const STORAGE_KEY = 'inkwise-preview-selections';
  const productId = document.body?.dataset?.productId ?? null;
  const productName = document.body?.dataset?.productName ?? null;

  const readSelectionStore = () => {
    try {
      const raw = window.sessionStorage.getItem(STORAGE_KEY);
      return raw ? JSON.parse(raw) : {};
    } catch (error) {
      console.warn('Unable to parse stored preview selections', error);
      return {};
    }
  };

  const writeSelectionStore = (store) => {
    try {
      window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(store));
    } catch (error) {
      console.warn('Unable to persist preview selections', error);
    }
  };

  const getStoredEntry = () => {
    if (!productId) return null;
    const store = readSelectionStore();
    const entry = store?.[productId];
    if (!entry || typeof entry !== 'object') return null;
    if (!entry.selections || typeof entry.selections !== 'object') return null;
    return entry;
  };

  let suppressPersist = false;

  const cloneSelections = () => Object.fromEntries(
    Object.entries(selectionState).map(([key, value]) => [key, { ...value }])
  );

  const persistSelectionState = () => {
    if (suppressPersist || !productId) return;
    const store = readSelectionStore();
    store[productId] = {
      productId,
      productName,
      selections: cloneSelections(),
      updatedAt: Date.now()
    };
    writeSelectionStore(store);
  };

  const storedEntry = getStoredEntry();
  const initialStoredSelections = storedEntry?.selections
    ? JSON.parse(JSON.stringify(storedEntry.selections))
    : null;

  if (storedEntry?.selections) {
    Object.assign(selectionState, storedEntry.selections);
  }

  if (!flipper || !frontBtn) {
    console.log('Flipper or frontBtn not found, exiting');
    return;
  }

  const frontImg = flipper.querySelector('.front img');
  const backImg = flipper.querySelector('.back img');

  const state = {
    front: frontImg?.src || '',
    back: backImg?.src || '',
    showingFront: true
  };

  const preloadImage = (src) => {
    if (!src || imageCache.has(src)) return;
    const image = new Image();
    image.src = src;
    image.decode?.().catch(() => null);
    imageCache.set(src, true);
  };

  const setImage = (imgElement, src) => {
    if (!imgElement || !src) return;
    if (imgElement.src === src) return;
    imgElement.dataset.pendingSrc = src;
    preloadImage(src);
    requestAnimationFrame(() => {
      imgElement.src = src;
      imgElement.removeAttribute('data-pending-src');
    });
  };

  const updateToggleVisibility = () => {
    if (!backBtn) return;
    const hasBack = Boolean(state.back) && state.back !== state.front;
    backBtn.style.display = hasBack ? 'inline-flex' : 'none';
    if (!hasBack && !state.showingFront) {
      showFront();
    }
  };

  const updateActiveToggle = () => {
    console.log('updateActiveToggle, showingFront:', state.showingFront);
    frontBtn.classList.toggle('active', state.showingFront);
    if (backBtn) {
      backBtn.classList.toggle('active', !state.showingFront);
    }
    flipper.classList.toggle('flipped', !state.showingFront);
  };

  const showFront = () => {
    state.showingFront = true;
    updateActiveToggle();
  };

  const showBack = () => {
    if (!state.back || state.back === state.front) return;
    state.showingFront = false;
    updateActiveToggle();
  };

  const handleToggleKeydown = (button, action) => {
    button.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        action();
      }
      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        showFront();
        frontBtn.focus();
      }
      if (event.key === 'ArrowRight' && backBtn && backBtn.style.display !== 'none') {
        event.preventDefault();
        showBack();
        backBtn.focus();
      }
    });
  };

  frontBtn.addEventListener('click', () => {
    console.log('Front button clicked');
    showFront();
  });
  handleToggleKeydown(frontBtn, showFront);

  if (backBtn) {
    backBtn.addEventListener('click', () => {
      console.log('Back button clicked');
      showBack();
    });
    handleToggleKeydown(backBtn, showBack);
  }

  colorBtns.forEach((btn) => {
    btn.addEventListener('click', () => {
      if (btn.classList.contains('active')) return;
      colorBtns.forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');

      state.front = btn.dataset.front || state.front;
      state.back = btn.dataset.back || state.back;

      setImage(frontImg, state.front);
      setImage(backImg, state.back || state.front);

      updateToggleVisibility();
      if (!state.showingFront) {
        showBack();
      }
    });

    btn.setAttribute('role', 'radio');
    btn.setAttribute('tabindex', btn.classList.contains('active') ? '0' : '-1');
    btn.addEventListener('keydown', (event) => {
      if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
      event.preventDefault();
      const activeIndex = colorBtns.indexOf(btn);
      const delta = event.key === 'ArrowRight' ? 1 : -1;
      const nextBtn = colorBtns.at((activeIndex + delta + colorBtns.length) % colorBtns.length);
      nextBtn?.focus();
      nextBtn?.click();
    });
  });

  const syncEditButtonState = () => {
    if (editBtn) {
      editBtn.dataset.selectedOptions = JSON.stringify(selectionState);
    }
    persistSelectionState();
  };

  const showToast = (message, duration = 3200) => {
    if (!selectionToast) return;
    selectionToast.textContent = message;
    selectionToast.classList.add('visible');
    if (toastTimeout) {
      clearTimeout(toastTimeout);
    }
    toastTimeout = setTimeout(() => {
      selectionToast.classList.remove('visible');
    }, duration);
  };

  const formatPrice = (price) => {
    if (price === undefined || price === null || price === '') return 'On request';
    const numeric = Number(price);
    return Number.isFinite(numeric) ? `₱${numeric.toLocaleString(undefined, { minimumFractionDigits: 2 })}` : price;
  };

  const toggleSelection = (card, options = {}) => {
    const group = card.dataset.optionGroup;
    if (!group) return;

    const existing = document.querySelector(
      `.selectable-card.selected[data-option-group="${group}"]`
    );

    const forceProvided = Object.prototype.hasOwnProperty.call(options, 'forceSelect');
    const shouldSelect = forceProvided ? Boolean(options.forceSelect) : !card.classList.contains('selected');

    if (shouldSelect && existing && existing !== card) {
      existing.classList.remove('selected');
      existing.setAttribute('aria-pressed', 'false');
    }

    if (shouldSelect) {
      card.classList.add('selected');
      card.setAttribute('aria-pressed', 'true');
      const payload = {
        id: card.dataset.optionId ? String(card.dataset.optionId) : null,
        name: card.dataset.optionName || '',
        price: card.dataset.optionPrice || '',
        image: card.dataset.optionImage || ''
      };
      selectionState[group] = payload;
      if (!options.silent) {
        showToast(`${payload.name || 'Option'} selected • ${formatPrice(payload.price)}`);
      }
    } else {
      if (card.classList.contains('selected')) {
        card.classList.remove('selected');
        card.setAttribute('aria-pressed', 'false');
      }
      delete selectionState[group];
      if (!options.silent) {
        showToast('Selection cleared.');
      }
    }

    syncEditButtonState();
  };

  selectableCards.forEach((card) => {
    card.setAttribute('role', 'button');
    card.setAttribute('tabindex', '0');
    card.setAttribute('aria-pressed', 'false');
    card.addEventListener('click', () => toggleSelection(card));
    card.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        toggleSelection(card);
      }
      if (event.key === 'Escape' && card.classList.contains('selected')) {
        event.preventDefault();
        toggleSelection(card, { forceSelect: false });
      }
    });
  });

  if (initialStoredSelections) {
    suppressPersist = true;
    Object.entries(initialStoredSelections).forEach(([group, payload]) => {
      if (!payload || typeof payload !== 'object') return;
      const card = selectableCards.find((node) =>
        node.dataset.optionGroup === group &&
        String(node.dataset.optionId ?? '') === String(payload.id ?? '')
      );
      if (card) {
        toggleSelection(card, { forceSelect: true, silent: true });
      }
    });
    suppressPersist = false;
  }

  syncEditButtonState();

  if (selectableCards.length && selectionToast) {
    showToast('Customize your invite: select paper stock and optional add-ons.', 4200);
  }

  if (editBtn) {
    console.log('Edit button found');
    editBtn.addEventListener('click', () => {
      console.log('Edit button clicked');
      const hasOptionalAddon = optionalAddonGroups.some((group) => selectionState[group]);
      if (!hasOptionalAddon && selectionToast) {
        showToast('Add-ons are optional. Pick trim, embossed powder, orientation, or size if you like.');
      }
    });
  } else {
    console.log('Edit button not found');
  }

  updateToggleVisibility();
  updateActiveToggle();

  window.addEventListener('keydown', (event) => {
    if (event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement) {
      return;
    }
    if (event.key === 'ArrowUp') {
      showFront();
    }
    if (event.key === 'ArrowDown') {
      showBack();
    }
  });
});
