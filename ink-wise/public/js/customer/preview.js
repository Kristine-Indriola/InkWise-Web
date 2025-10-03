document.addEventListener('DOMContentLoaded', () => {
  const flipper = document.getElementById('flipper');
  const frontBtn = document.getElementById('frontBtn');
  const backBtn = document.getElementById('backBtn');
  const colorBtns = Array.from(document.querySelectorAll('.color-btn'));
  const selectableCards = Array.from(document.querySelectorAll('.selectable-card'));
  const editBtn = document.querySelector('.edit-btn');
  const selectionToast = document.getElementById('addonToast');
  const optionalAddonGroups = ['trim', 'embossed_powder', 'orientation', 'size'];
  const selectionState = {};
  const imageCache = new Map();
  let toastTimeout = null;

  if (!flipper || !frontBtn) {
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

  frontBtn.addEventListener('click', showFront);
  handleToggleKeydown(frontBtn, showFront);

  if (backBtn) {
    backBtn.addEventListener('click', showBack);
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
    if (!editBtn) return;
    editBtn.dataset.selectedOptions = JSON.stringify(selectionState);
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

  const toggleSelection = (card) => {
    const group = card.dataset.optionGroup;
    if (!group) return;

    const current = document.querySelector(
      `.selectable-card.selected[data-option-group="${group}"]`
    );

    if (current && current !== card) {
      current.classList.remove('selected');
      current.setAttribute('aria-pressed', 'false');
    }

    const isSelected = card.classList.toggle('selected');
    card.setAttribute('aria-pressed', isSelected ? 'true' : 'false');

    if (isSelected) {
      selectionState[group] = {
        id: card.dataset.optionId || null,
        name: card.dataset.optionName || '',
        price: card.dataset.optionPrice || '',
        image: card.dataset.optionImage || ''
      };
      showToast(`${selectionState[group].name || 'Option'} selected • ${formatPrice(selectionState[group].price)}`);
    } else {
      delete selectionState[group];
      showToast('Selection cleared.');
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
        card.classList.remove('selected');
        card.setAttribute('aria-pressed', 'false');
        const group = card.dataset.optionGroup;
        if (group) {
          delete selectionState[group];
          syncEditButtonState();
          showToast('Selection cleared.');
        }
      }
    });
  });

  if (selectableCards.length && selectionToast) {
    showToast('Customize your invite: select paper stock and optional add-ons.', 4200);
  }

  if (editBtn) {
    editBtn.addEventListener('click', () => {
      const hasOptionalAddon = optionalAddonGroups.some((group) => selectionState[group]);
      if (!hasOptionalAddon && selectionToast) {
        showToast('Add-ons are optional. Pick trim, embossed powder, orientation, or size if you like.');
      }
    });
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
