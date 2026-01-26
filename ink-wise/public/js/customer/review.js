document.addEventListener('DOMContentLoaded', () => {
  const shell = document.querySelector('.review-shell');
  const flipContainer = document.querySelector('.card-flip');
  const inner = flipContainer?.querySelector('.inner');
  const frontBtn = document.querySelector('[data-face="front"]');
  const backBtn = document.querySelector('[data-face="back"]');
  const cardFaces = {
    front: flipContainer?.querySelector('.card-face.front img') || flipContainer?.querySelector('.card-face.front .svg-container') || null,
    back: flipContainer?.querySelector('.card-face.back img') || flipContainer?.querySelector('.card-face.back .svg-container') || null
  };

  // Upload controls
  const uploadButton = document.getElementById('upload-button');
  const uploadFileInput = document.getElementById('review-image-upload');
  const uploadSideFrontBtn = document.getElementById('upload-side-front');
  const uploadSideBackBtn = document.getElementById('upload-side-back');
  const uploadSideLabel = document.getElementById('upload-side-label');
  let uploadTarget = 'front';

  const updateUploadLabel = (side) => {
    const text = (side || 'front').charAt(0).toUpperCase() + (side || 'front').slice(1);
    if (uploadSideLabel) uploadSideLabel.textContent = text;
    if (uploadButton) uploadButton.setAttribute('aria-label', `Upload image for: ${text}`);
  };

  const setUploadTarget = (side) => {
    uploadTarget = side || 'front';
    if (uploadSideFrontBtn) uploadSideFrontBtn.classList.toggle('active', uploadTarget === 'front');
    if (uploadSideBackBtn) uploadSideBackBtn.classList.toggle('active', uploadTarget === 'back');
    if (uploadSideFrontBtn) uploadSideFrontBtn.setAttribute('aria-pressed', String(uploadTarget === 'front'));
    if (uploadSideBackBtn) uploadSideBackBtn.setAttribute('aria-pressed', String(uploadTarget === 'back'));
    updateUploadLabel(uploadTarget);
  };

  if (uploadSideFrontBtn) uploadSideFrontBtn.addEventListener('click', () => setUploadTarget('front'));
  if (uploadSideBackBtn) uploadSideBackBtn.addEventListener('click', () => setUploadTarget('back'));

  if (uploadButton) uploadButton.addEventListener('click', () => uploadFileInput && uploadFileInput.click());

  const isSvgString = (text) => typeof text === 'string' && /<svg[\s>]/i.test(text.trim());

  const updateSummaryImage = (side, dataUrl) => {
    try {
      if (!window.summaryData) window.summaryData = {};
      if (!Array.isArray(window.summaryData.previewImages)) window.summaryData.previewImages = [];
      if (side === 'front') {
        window.summaryData.previewImage = dataUrl;
        window.summaryData.previewImages[0] = dataUrl;
      } else {
        window.summaryData.previewImages[1] = dataUrl;
      }
      try { window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(window.summaryData)); } catch (e) {}
    } catch (e) {}
  };

  const setFrontSvgMarkup = (svgText) => {
    const frontFace = document.querySelector('.card-face.front');
    if (!frontFace) return;
    const svgContainer = frontFace.querySelector('.svg-container');
    if (svgContainer) {
      svgContainer.innerHTML = svgText;
    } else {
      // Replace existing img with svg container
      const img = frontFace.querySelector('img');
      const container = document.createElement('div');
      container.className = 'svg-container';
      container.style.width = '100%';
      container.style.height = '100%';
      container.style.pointerEvents = 'none';
      container.innerHTML = svgText;
      if (img) img.replaceWith(container); else frontFace.appendChild(container);
    }
  };

  const uploadFileToServer = async (file, side) => {
    if (!file) return null;
    const fd = new FormData();
    fd.append('image', file);
    fd.append('side', side);

    try {
      const res = await fetch("/order/review/upload-image", {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'X-CSRF-TOKEN': csrfToken || '',
          'Accept': 'application/json',
        },
        body: fd,
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(data?.message || 'Upload failed');
      return data;
    } catch (err) {
      showToast(err?.message || 'Upload to server failed');
      return null;
    }
  };

  const uploadSvgToServer = async (svgText, side) => {
    if (!svgText) return null;
    try {
      const res = await fetch("/order/review/upload-image", {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken || '',
          'Accept': 'application/json',
        },
        body: JSON.stringify({ svg: svgText, side }),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(data?.message || 'Upload failed');
      return data;
    } catch (err) {
      showToast(err?.message || 'Upload to server failed');
      return null;
    }
  };

  uploadFileInput?.addEventListener('change', async (ev) => {
    const file = ev.target.files && ev.target.files[0];
    if (!file) return;

    if (file.type === 'image/svg+xml' || (file.name && file.name.toLowerCase().endsWith('.svg'))) {
      const reader = new FileReader();
      reader.onload = async (e) => {
        const svgText = e.target.result;
        if (!svgText) return;

        // Persist the SVG to server
        const serverResult = await uploadSvgToServer(svgText, uploadTarget);

        // embed SVG for chosen side
        if (uploadTarget === 'front') {
          setFrontSvgMarkup(svgText);
          const dataUrl = serverResult?.url || ('data:image/svg+xml;base64,' + window.btoa(unescape(encodeURIComponent(svgText))));
          updateSummaryImage('front', dataUrl);
        } else {
          const backFace = document.querySelector('.card-face.back');
          if (!backFace) return;
          const svgContainer = backFace.querySelector('.svg-container');
          if (svgContainer) {
            svgContainer.innerHTML = svgText;
          } else {
            const container = document.createElement('div');
            container.className = 'svg-container';
            container.style.width = '100%';
            container.style.height = '100%';
            container.style.pointerEvents = 'none';
            container.innerHTML = svgText;
            const img = backFace.querySelector('img');
            if (img) img.replaceWith(container); else backFace.appendChild(container);
          }
          const dataUrl = serverResult?.url || ('data:image/svg+xml;base64,' + window.btoa(unescape(encodeURIComponent(svgText))));
          updateSummaryImage('back', dataUrl);
        }

        // Switch preview to that side so user sees result
        setActiveFace(uploadTarget);
        showToast(serverResult ? 'Uploaded and embedded SVG successfully (saved)' : 'Uploaded and embedded SVG successfully', 3000);
      };
      reader.readAsText(file);
      return;
    }

    // Raster images
    // First persist to server so server-stored URL is used when possible
    const serverResult = await uploadFileToServer(file, uploadTarget);

    const reader = new FileReader();
    reader.onload = (e) => {
      const dataUrl = e.target.result;
      if (!dataUrl) return;

      const face = document.querySelector(`.card-face.${uploadTarget}`);
      if (!face) return;

      const svgContainer = face.querySelector('.svg-container');
      const setImageSrc = (src) => {
        if (svgContainer) {
          // Replace svg container with an img
          const img = document.createElement('img');
          img.src = src;
          img.alt = `${uploadTarget.charAt(0).toUpperCase() + uploadTarget.slice(1)} of your design`;
          img.loading = 'lazy';
          img.decoding = 'async';
          svgContainer.replaceWith(img);
        } else {
          const img = face.querySelector('img');
          if (img) {
            img.src = src;
          } else {
            const newImg = document.createElement('img');
            newImg.src = src;
            newImg.alt = `${uploadTarget.charAt(0).toUpperCase() + uploadTarget.slice(1)} of your design`;
            newImg.loading = 'lazy';
            newImg.decoding = 'async';
            face.appendChild(newImg);
          }
        }
      };

      // Prefer server URL if we got one
      const preferredUrl = serverResult?.url || dataUrl;
      setImageSrc(preferredUrl);

      updateSummaryImage(uploadTarget, preferredUrl);

      // Switch preview to that side so user sees result
      setActiveFace(uploadTarget);
      showToast(serverResult ? 'Uploaded image applied and saved' : 'Uploaded image applied', 2500);
    };
    reader.readAsDataURL(file);
  });
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
