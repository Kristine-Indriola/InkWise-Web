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
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
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

  // Detect unedited placeholders using the actual template snapshot (texts + images)
  const summaryData = window.summaryData;
  if (summaryData) {
    const design = summaryData.design || summaryData;
    const textEntries = Array.isArray(design.texts) ? design.texts : [];
    const imageEntries = Array.isArray(design.images) ? design.images : [];

    const uneditedLabels = new Set();
    textEntries.forEach((entry) => {
      const value = (entry.content || entry.value || '').trim();
      const def = (entry.defaultValue || '').trim();
      const label = (entry.label || entry.key || '').trim();
      if (!label) return;
      if (value === '' || (def && value === def)) {
        uneditedLabels.add(label);
      }
    });

    const uneditedBySide = { front: [], back: [] };
    let warningCount = 0;

    unresolvedPlaceholders.forEach((li) => {
      const placeholderText = li.textContent.trim();
      let side = 'front';
      let body = placeholderText;
      if (placeholderText.toLowerCase().startsWith('front:')) {
        side = 'front';
        body = placeholderText.slice(6).trim();
      } else if (placeholderText.toLowerCase().startsWith('back:')) {
        side = 'back';
        body = placeholderText.slice(5).trim();
      }

      const isImagePlaceholder = /image placeholder/i.test(body);
      const matchesUneditedText = Array.from(uneditedLabels).some((label) => label === body);
      const imageUnedited = isImagePlaceholder && imageEntries.length === 0;

      // Try to enrich message with the actual default text from the design snapshot.
      let defaultText = body;
      const entryMatch = textEntries.find((entry) => {
        const label = (entry.label || entry.key || '').trim();
        return label && label === body;
      });
      if (entryMatch) {
        defaultText = (entryMatch.defaultValue || entryMatch.content || entryMatch.value || body).trim();
      }

      if (matchesUneditedText || imageUnedited) {
        warningCount += 1;
        uneditedBySide[side]?.push(defaultText);
        li.textContent = `Warning: ${side === 'front' ? 'Front' : 'Back'} — ${defaultText} was not edited`;
        li.classList.add('unedited-warning');
      }
    });

    if (placeholderCounter) {
      placeholderCounter.textContent = warningCount.toString();
    }

    if (uneditedBySide.front.length) {
      showToast(`Front placeholders not edited: ${uneditedBySide.front.join(', ')}`, 6000);
    }
    if (uneditedBySide.back.length) {
      showToast(`Back placeholders not edited: ${uneditedBySide.back.join(', ')}`, 6000);
    }
  }

    const postReviewProgress = async () => {
      const saveUrl = continueBtn?.dataset.saveUrl;
      if (!saveUrl) return { ok: true };

      // If window.summaryData is missing, let backend pull from session by sending null.
      const summaryPayload = window.summaryData || null;

      try {
        const response = await fetch(saveUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
          },
          body: JSON.stringify({ summary: summaryPayload }),
        });

        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
          const message = data?.message || 'Unable to save your review progress.';
          throw new Error(message);
        }

        return { ok: true, redirect: data?.redirect };
      } catch (error) {
        // Do not block navigation; notify and still allow redirect if provided.
        showToast(error?.message || 'We could not save your review. Please try again.');
        return { ok: false, redirect: null };
      }
    };

    continueBtn?.addEventListener('click', async (event) => {
    if (continueBtn.disabled) {
      event.preventDefault();
      showToast('Please confirm you have reviewed your design.');
      return;
    }

      continueBtn.disabled = true;
      continueBtn.setAttribute('aria-disabled', 'true');

      const result = await postReviewProgress();
      const target = result.redirect || continueBtn.dataset.href;

      if (target) {
        window.location.href = target;
        return;
      }

      // If save failed and no target, re-enable and notify.
      continueBtn.disabled = false;
      continueBtn.removeAttribute('aria-disabled');
      showToast('We could not save your review. Please try again.');
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
