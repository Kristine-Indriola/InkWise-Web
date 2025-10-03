document.addEventListener('DOMContentLoaded', () => {
  const shell = document.querySelector('.review-shell');
  const flipContainer = document.querySelector('.card-flip');
  const inner = flipContainer?.querySelector('.inner');
  const frontBtn = document.querySelector('[data-face="front"]');
  const backBtn = document.querySelector('[data-face="back"]');
  const cardFaces = {
    front: flipContainer?.querySelector('.card-face.front img') || null,
    back: flipContainer?.querySelector('.card-face.back img') || null
  };
  const approvalCheckbox = document.getElementById('approvalCheckbox');
  const continueBtn = document.getElementById('continueBtn');
  const unresolvedPlaceholders = document.querySelectorAll('.placeholder-list li');
  const placeholderCounter = document.querySelector('[data-placeholder-count]');
  const toast = document.getElementById('reviewToast');
  let toastTimeout = null;

  if (placeholderCounter) {
    placeholderCounter.textContent = unresolvedPlaceholders.length.toString();
  }

  const showToast = (message, timeout = 3200) => {
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add('visible');
    if (toastTimeout) window.clearTimeout(toastTimeout);
    toastTimeout = window.setTimeout(() => {
      toast.classList.remove('visible');
    }, timeout);
  };

  const updateContinueState = () => {
    if (!continueBtn || !approvalCheckbox) return;
    continueBtn.disabled = !approvalCheckbox.checked;
    if (continueBtn.disabled) {
      continueBtn.setAttribute('aria-disabled', 'true');
    } else {
      continueBtn.removeAttribute('aria-disabled');
    }
  };

  const setActiveFace = (face) => {
    if (!inner) return;
    const isBack = face === 'back';
    if (isBack && (!cardFaces.back || cardFaces.back.src === cardFaces.front?.src)) {
      showToast('Back preview matches the front artwork.');
    }
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

  const handleToggle = (targetFace) => {
    if (!cardFaces[targetFace]) {
      showToast(`No ${targetFace} artwork available to display.`);
      return;
    }
    setActiveFace(targetFace);
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

  if (approvalCheckbox) {
    approvalCheckbox.addEventListener('change', () => {
      updateContinueState();
      if (approvalCheckbox.checked) {
        showToast('Thanks! You can continue now.');
      }
    });
    updateContinueState();
  }

  continueBtn?.addEventListener('click', (event) => {
    if (continueBtn.disabled) {
      event.preventDefault();
      showToast('Please confirm you have reviewed your design.');
      return;
    }
    const target = continueBtn.dataset.href;
    if (target) {
      window.location.href = target;
    } else {
      event.preventDefault();
      showToast('Flow placeholder only — wire up the continue action later.');
    }
  });

  const updatePlaceholderToggle = (btn, expanded, count) => {
    const expandedLabel = btn.getAttribute('data-label-expanded') || 'Hide placeholder items';
    const collapsedLabel = btn.getAttribute('data-label-collapsed') || 'View placeholder items';
    const labelEl = btn.querySelector('[data-placeholder-label]');
    const countEl = btn.querySelector('[data-placeholder-count]');
    if (labelEl) {
      labelEl.textContent = expanded ? expandedLabel : collapsedLabel;
    }
    if (countEl) {
      countEl.textContent = count.toString();
    }
    btn.setAttribute('aria-expanded', String(expanded));
  };

  document.querySelectorAll('[data-placeholder-toggle]').forEach((btn) => {
    const targetSelector = btn.getAttribute('data-placeholder-toggle');
    const target = targetSelector ? document.querySelector(targetSelector) : null;
    const count = target ? target.querySelectorAll('li').length : 0;
    updatePlaceholderToggle(btn, target?.getAttribute('data-expanded') === 'true', count);

    btn.addEventListener('click', () => {
      if (!target) return;
      const isHidden = target.getAttribute('data-expanded') !== 'true';
      target.setAttribute('data-expanded', String(isHidden));
      target.classList.toggle('expanded', isHidden);
      updatePlaceholderToggle(btn, isHidden, target.querySelectorAll('li').length);
    });

    btn.addEventListener('keydown', (event) => {
      if (!['Enter', ' '].includes(event.key)) return;
      event.preventDefault();
      btn.click();
    });
  });

  shell?.classList.add('mounted');

  window.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') {
      handleToggle('front');
    }
    if (event.key === 'ArrowRight') {
      handleToggle('back');
    }
  });
});
