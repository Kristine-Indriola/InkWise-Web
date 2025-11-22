document.addEventListener("DOMContentLoaded", () => {
  // Always show toolbar when a text field is focused or clicked
  document.addEventListener('focusin', (e) => {
    if (e.target && e.target.matches('input[data-text-node]')) {
      showTextToolbar(e.target);
    }
  });
  document.addEventListener('click', (e) => {
    if (e.target && e.target.matches('input[data-text-node]')) {
      showTextToolbar(e.target);
    }
    // handle SVG text node click
    const textHit = e.target && e.target.closest && e.target.closest('svg [data-text-node]');
    const imageHit = e.target && e.target.closest && (e.target.closest('svg image') || e.target.closest('svg [data-uploaded]'));
    if (textHit) {
      const node = e.target.closest('[data-text-node]');
      const svg = node.closest('svg');
      const card = svg.closest('.card');
      const side = (card && card.dataset && card.dataset.card) ? card.dataset.card : (svg.id === 'cardFront' ? 'front' : 'back');
      selectedElementNode = node;
      selectedElementSide = side;
      showTextToolbar(e.target);
      createBoundingBox(node, side);
      // populate toolbar controls so they reflect the selected SVG node's styles
      try { populateToolbarForNode(node, side); } catch (e) { /* ignore */ }
    } else if (imageHit) {
      const node = e.target.closest('image') || e.target.closest('[data-uploaded]');
      if (node) {
        const svg = node.closest('svg');
        const card = svg.closest('.card');
        const side = (card && card.dataset && card.dataset.card) ? card.dataset.card : (svg.id === 'cardFront' ? 'front' : 'back');
        if (!allowCanvasImageInteractions) {
          hideTextToolbar();
          return;
        }
        selectedElementNode = node;
        selectedElementSide = side;
        // hide text toolbar when selecting image
        hideTextToolbar();
        createBoundingBox(node, side);
      }
    }
  });
  const frontBtn = document.getElementById("showFront");
  const backBtn = document.getElementById("showBack");
  const viewToggleButtons = document.querySelectorAll(".view-toggle button");
  const cardFront = document.getElementById("cardFront");
  const cardBack = document.getElementById("cardBack");

  const zoomInBtn = document.getElementById("zoomIn");
  const zoomOutBtn = document.getElementById("zoomOut");
  const zoomLevel = document.getElementById("zoomLevel");
  const canvas = document.querySelector(".canvas");

  const textFieldsContainer = document.getElementById("textFields");
  const addTextBtn = document.getElementById("addTextField");
  const sideButtons = document.querySelectorAll(".side-btn");
  const panels = document.querySelectorAll(".editor-panel");
  const imageInputs = document.querySelectorAll("[data-image-input]");
  const resetImageButtons = document.querySelectorAll("[data-reset-image]");
  const imagePreviews = {
    front: document.querySelector('[data-image-preview="front"]'),
    back: document.querySelector('[data-image-preview="back"]'),
  };
  const bodyDataset = document.body?.dataset || {};
  const imageReplacementModeRaw = (bodyDataset.imageReplacementMode || '').toLowerCase();
  let imageReplacementMode = 'full';
  if (imageReplacementModeRaw === 'panel-only' || imageReplacementModeRaw === 'disabled') {
    imageReplacementMode = imageReplacementModeRaw;
  } else if (bodyDataset.allowImageReplacement === 'false') {
    imageReplacementMode = 'disabled';
  }
  const canReplaceImages = imageReplacementMode !== 'disabled';
  const allowCanvasImageInteractions = imageReplacementMode === 'full';
  const shouldDisplayFallbackImages = imageReplacementMode === 'disabled';
  const allowImageEditing = imageInputs.length > 0;
  const textToolbar = document.getElementById("textToolbar");
  const canvasArea = document.querySelector('.canvas-area');
  const canvasWrapper = document.querySelector('.canvas');
  const changeImageBtn = document.getElementById('changeImageBtn');

  const parserData = window.inkwiseTemplateParser || {};
  const parserFront = parserData.front || {};
  const parserBack = parserData.back || {};
  const parserWarnings = Array.isArray(parserData.warnings) ? parserData.warnings : [];

  const resolveParserCount = (source, countKey, listKey) => {
    if (!source || typeof source !== 'object') {
      return 0;
    }
    const direct = source[countKey];
    if (typeof direct === 'number' && Number.isFinite(direct)) {
      return Number(direct);
    }
    const list = source[listKey];
    if (Array.isArray(list)) {
      return list.length;
    }
    return 0;
  };

  const parserTextCounts = {
    front: resolveParserCount(parserFront, 'text_count', 'text_elements'),
    back: resolveParserCount(parserBack, 'text_count', 'text_elements'),
  };

  const parserChangeableCounts = {
    front: resolveParserCount(parserFront, 'changeable_count', 'changeable_images'),
    back: resolveParserCount(parserBack, 'changeable_count', 'changeable_images'),
  };

  const parserTotalChangeable = parserChangeableCounts.front + parserChangeableCounts.back;

  function renderParserInsights() {
    const frontSummaryEl = document.getElementById('parserFrontSummary');
    if (frontSummaryEl) {
      frontSummaryEl.textContent = `Front: text ${parserTextCounts.front} • replaceable images ${parserChangeableCounts.front}`;
    }

    const backSummaryEl = document.getElementById('parserBackSummary');
    if (backSummaryEl) {
      backSummaryEl.textContent = `Back: text ${parserTextCounts.back} • replaceable images ${parserChangeableCounts.back}`;
    }

    const warningsContainer = document.getElementById('parserWarningsContainer');
    const warningsList = document.getElementById('parserWarningsList');
    if (warningsContainer && warningsList) {
      if (!parserWarnings.length) {
        warningsContainer.hidden = true;
        warningsList.innerHTML = '';
      } else {
        warningsContainer.hidden = false;
        warningsList.innerHTML = '';
        parserWarnings.forEach((message) => {
          const item = document.createElement('li');
          item.textContent = message;
          warningsList.appendChild(item);
        });
      }
    }

    const changeSummaryParts = [`front ${parserChangeableCounts.front}`];
    if (parserChangeableCounts.back) {
      changeSummaryParts.push(`back ${parserChangeableCounts.back}`);
    }

    const imageStatusEl = document.getElementById('imageParserStatus');
    if (imageStatusEl) {
      if (parserTotalChangeable > 0) {
        if (allowCanvasImageInteractions) {
          imageStatusEl.textContent = `Replaceable image areas detected: ${changeSummaryParts.join(' • ')}`;
        } else {
          imageStatusEl.textContent = 'Use the Images panel to swap the template background.';
        }
      } else {
        imageStatusEl.textContent = 'No replaceable image areas detected in this template.';
      }
    }

    if (changeImageBtn) {
      changeImageBtn.title = parserTotalChangeable > 0
        ? `Swap detected image areas (${changeSummaryParts.join(' • ')})`
        : 'Template import did not include replaceable image frames.';
    }
  }

  renderParserInsights();
  if (canvasArea) {
    const areaPosition = window.getComputedStyle(canvasArea).position;
    if (!areaPosition || areaPosition === 'static') {
      canvasArea.style.position = 'relative';
    }
  } else if (canvasWrapper) {
    const computedPosition = window.getComputedStyle(canvasWrapper).position;
    if (!computedPosition || computedPosition === 'static') {
      canvasWrapper.style.position = 'relative';
    }
  }

  // Apply explicit alignment ('left'|'center'|'right'|'justify') to the active target
  function applyAlignToActive(alignment) {
    if (!alignment) return;
    const inverse = { left: 'start', center: 'middle', right: 'end', justify: 'start' };
    const alignVal = (alignment === 'justify') ? 'justify' : (alignment || 'center');

    const active = document.activeElement;
    if (active && active.matches && active.matches('input[data-text-node]')) {
      const side = getFieldSide(active);
      const node = svgTextCache[side]?.get(getTextNodeKey(active));
      if (node) beginHistoryCapture(node, side);
      try { active.dataset.align = alignVal; } catch(e){}
      if (node) {
        try { node.setAttribute('text-anchor', inverse[alignVal] || 'middle'); } catch(e){}
        const x = node.getAttribute('x') || node.dataset.originalX || null;
        if (x) node.querySelectorAll('tspan').forEach(t => t.setAttribute('x', x));
        try { endHistoryCapture(node, side); } catch(e){}
        try { updatePreviewFromSvg(side); } catch(e){}
        createBoundingBox(node, side);
      }
      return;
    }

    if (inlineEditorNode) {
      const node = inlineEditorNode;
      const side = inlineEditorSide;
      beginHistoryCapture(node, side);
      try { node.setAttribute('text-anchor', inverse[alignVal] || 'middle'); } catch(e){}
      const x = node.getAttribute('x') || node.dataset.originalX || null;
      if (x) node.querySelectorAll('tspan').forEach(t => t.setAttribute('x', x));
      try { endHistoryCapture(node, side); } catch(e){}
      try { updatePreviewFromSvg(side); } catch(e){}
      createBoundingBox(node, side);
      return;
    }

    if (selectedElementNode && selectedElementNode.tagName && selectedElementNode.tagName.toLowerCase() === 'text') {
      const node = selectedElementNode;
      const side = selectedElementSide || currentView || 'front';
      beginHistoryCapture(node, side);
      try { node.setAttribute('text-anchor', inverse[alignVal] || 'middle'); } catch(e){}
      const x = node.getAttribute('x') || node.dataset.originalX || null;
      if (x) node.querySelectorAll('tspan').forEach(t => t.setAttribute('x', x));
      try { endHistoryCapture(node, side); } catch(e){}
      try { updatePreviewFromSvg(side); } catch(e){}
      createBoundingBox(node, side);
      try { if (node && node.tagName && node.tagName.toLowerCase() === 'text') populateToolbarForNode(node, side); } catch (e) {}
      return;
    }
  }

  let toolbarHideTimer = null;
  let fontModalOpen = false;
  let colorModalOpen = false;

  function showTextToolbar(trigger) {
    if (!textToolbar) return;
    clearTimeout(toolbarHideTimer);
    textToolbar.classList.add("is-visible");
    textToolbar.setAttribute("aria-hidden", "false");
  }

  function hideTextToolbar() {
    if (!textToolbar) return;
    clearTimeout(toolbarHideTimer);
    textToolbar.classList.remove("is-visible");
    textToolbar.setAttribute("aria-hidden", "true");
  }

  function scheduleToolbarHide() {
    if (!textToolbar) return;
    clearTimeout(toolbarHideTimer);
    toolbarHideTimer = setTimeout(() => {
      // if font modal is open we should not hide the toolbar
  // don't hide toolbar if either modal is open
  if (fontModalOpen || colorModalOpen) return;
      if (dragState.active) return;
      const activeEl = document.activeElement;
      if (!activeEl) {
        hideTextToolbar();
        return;
      }
      if (textToolbar.contains(activeEl)) return;
      if (activeEl.closest?.(".text-field")) return;
      if (inlineEditor && activeEl === inlineEditor) return;
      if (activeEl?.hasAttribute && activeEl.hasAttribute('data-text-node')) return;
      hideTextToolbar();
    }, 150);
  }

  if (textToolbar) {
    textToolbar.addEventListener("focusin", () => clearTimeout(toolbarHideTimer));
    textToolbar.addEventListener("focusout", scheduleToolbarHide);
  }

  /* FONT MODAL */
  const fontModal = document.getElementById("fontModal");
  const fontList = document.getElementById("fontList");
  const fontSearch = document.getElementById("fontSearch");
  const fontClose = document.getElementById("fontClose");
  const googleApiKey = fontModal?.dataset?.googleFontsKey;

  function openFontModal() {
    if (!fontModal) return;
    fontModal.setAttribute("aria-hidden", "false");
    fontModalOpen = true;
    // ensure toolbar remains visible while modal is open
    showTextToolbar();
    loadFontsList();
    try { fontSearch.focus(); } catch (e) {}
  }

  function closeFontModal() {
    if (!fontModal) return;
    fontModal.setAttribute("aria-hidden", "true");
    fontModalOpen = false;
  }

  if (fontClose) fontClose.addEventListener("click", closeFontModal);
  if (fontModal) fontModal.addEventListener("pointerdown", (e) => {
    if (e.target === fontModal) closeFontModal();
  });

  /* COLOR MODAL */
  const colorModal = document.getElementById('colorModal');
  const colorClose = document.getElementById('colorClose');
  const colorNative = document.getElementById('colorNative');
  const colorHexInput = document.getElementById('colorHexInput');
  const colorSample = document.getElementById('colorSample');
  const recentColorsContainer = document.getElementById('recentColors');

  function openColorModal() {
    if (!colorModal) return;
    colorModal.setAttribute('aria-hidden','false');
    colorModalOpen = true;
    showTextToolbar();
    // sync current toolbar color value
    try {
      const sw = document.querySelector('.toolbar-color-swatch');
      const v = sw?.dataset?.color || (sw && sw.style && sw.style.background) || '#1f2933';
      if (colorNative) colorNative.value = v;
      if (colorHexInput) colorHexInput.value = v;
      if (colorSample) colorSample.style.background = v;
    } catch(e){}
  }

  function closeColorModal() {
    if (!colorModal) return;
    colorModal.setAttribute('aria-hidden','true');
    colorModalOpen = false;
  }

  if (colorClose) colorClose.addEventListener('click', closeColorModal);
  if (colorModal) colorModal.addEventListener('pointerdown', (e)=>{ if (e.target===colorModal) closeColorModal(); });

  // open color modal when toolbar color swatch clicked
  document.querySelectorAll('.toolbar-color').forEach(el => {
    el.addEventListener('click', (e)=>{ e.stopPropagation(); openColorModal(); });
  });

  // sync native color and hex input
  if (colorNative) {
    colorNative.addEventListener('input', (e)=>{
      const v = colorNative.value;
      if (colorHexInput) colorHexInput.value = v;
      if (colorSample) colorSample.style.background = v;
      // apply live
      applyColorToActive(v);
    });
  }
  if (colorHexInput) {
    colorHexInput.addEventListener('change', ()=>{
      const v = colorHexInput.value.trim();
      if (/^#([0-9A-F]{3}){1,2}$/i.test(v)) {
        if (colorNative) colorNative.value = v;
        if (colorSample) colorSample.style.background = v;
        applyColorToActive(v);
        addRecentColor(v);
      }
    });
  }

  // preset swatches
  document.addEventListener('click', (e)=>{
    const btn = e.target.closest('.swatch');
    if (!btn) return;
    const c = btn.dataset.color;
    if (!c) return;
    if (colorNative) colorNative.value = c;
    if (colorHexInput) colorHexInput.value = c;
    if (colorSample) colorSample.style.background = c;
    applyColorToActive(c);
    addRecentColor(c);
  });

  function applyColorToActive(color) {
    if (!color) return;
    const active = document.activeElement;
    // attempt to capture history for the affected node
    let captureNode = null;
    let captureSide = null;
    if (active && active.matches && active.matches('input[data-text-node]')) {
      captureNode = svgTextCache[getFieldSide(active)]?.get(getTextNodeKey(active));
      captureSide = getFieldSide(active);
      if (captureNode) beginHistoryCapture(captureNode, captureSide);
      active.style.color = color;
      // update SVG node fill
      const side = getFieldSide(active);
      const key = getTextNodeKey(active);
      const node = svgTextCache[side]?.get(key);
      if (node) node.setAttribute('fill', color);
    } else if (inlineEditorNode) {
      beginHistoryCapture(inlineEditorNode, inlineEditorSide);
      inlineEditorNode.setAttribute('fill', color);
    }
    else if (selectedElementNode && selectedElementNode.tagName && selectedElementNode.tagName.toLowerCase() === 'text') {
      beginHistoryCapture(selectedElementNode, selectedElementSide);
      selectedElementNode.setAttribute('fill', color);
      try { endHistoryCapture(selectedElementNode, selectedElementSide); } catch(e){}
    }
    // update toolbar color input value
    const sw = document.querySelector('.toolbar-color-swatch');
    if (sw) { sw.style.background = color; sw.dataset.color = color; }
    // finalize history capture
    try { if (captureNode && captureSide) endHistoryCapture(captureNode, captureSide); else if (inlineEditorNode) endHistoryCapture(inlineEditorNode, inlineEditorSide); } catch(e) {}
  }

  /* --- Enhanced color picker interactions (gradient + hue) --- */
  const colorGradient = document.getElementById('colorGradient');
  const colorGradientPointer = document.getElementById('colorGradientPointer');
  const hueSlider = document.getElementById('hueSlider');
  const huePointer = document.getElementById('huePointer');
  const presetSwatches = document.getElementById('presetSwatches');
  const eyedropperBtn = document.getElementById('eyedropperBtn');

  // Helper: clamp
  const clamp = (v, a, b) => Math.max(a, Math.min(b, v));

  // Convert HSV to HEX
  function hsvToHex(h, s, v) {
    let r, g, b;
    let i = Math.floor(h * 6);
    let f = h * 6 - i;
    let p = v * (1 - s);
    let q = v * (1 - f * s);
    let t = v * (1 - (1 - f) * s);
    switch (i % 6) {
      case 0: r = v; g = t; b = p; break;
      case 1: r = q; g = v; b = p; break;
      case 2: r = p; g = v; b = t; break;
      case 3: r = p; g = q; b = v; break;
      case 4: r = t; g = p; b = v; break;
      case 5: r = v; g = p; b = q; break;
    }
    const toHex = x => Math.round(x * 255).toString(16).padStart(2, '0');
    return `#${toHex(r)}${toHex(g)}${toHex(b)}`.toUpperCase();
  }

  // Convert HEX to RGB then to HSV
  function hexToHsv(hex) {
    if (!hex) return { h:0,s:0,v:0 };
    hex = hex.replace('#','');
    if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
    const num = parseInt(hex,16);
    const r = ((num >> 16) & 255) / 255;
    const g = ((num >> 8) & 255) / 255;
    const b = (num & 255) / 255;
    const max = Math.max(r,g,b), min = Math.min(r,g,b);
    let h = 0, s = 0, v = max;
    const d = max - min;
    s = max === 0 ? 0 : d / max;
    if (max === min) h = 0;
    else {
      switch (max) {
        case r: h = (g - b) / d + (g < b ? 6 : 0); break;
        case g: h = (b - r) / d + 2; break;
        case b: h = (r - g) / d + 4; break;
      }
      h /= 6;
    }
    return { h, s, v };
  }

  // Update gradient background based on hue
  function setGradientHue(h) {
    if (!colorGradient) return;
    const hex = hsvToHex(h, 1, 1);
    colorGradient.style.background = `linear-gradient(to right, #fff, ${hex})`;
    // overlay for value is handled via ::after
  }

  // Place pointer inside element bounds
  function setPointerPos(el, pointer, x, y) {
    if (!el || !pointer) return;
    const rect = el.getBoundingClientRect();
    const px = clamp(x - rect.left, 0, rect.width);
    const py = clamp(y - rect.top, 0, rect.height);
    pointer.style.left = (px) + 'px';
    pointer.style.top = (py) + 'px';
    return { px, py, w: rect.width, h: rect.height };
  }

  let currentHue = 0; // 0..1
  let currentS = 1; // 0..1
  let currentV = 1; // 0..1

  // Gradient drag
  if (colorGradient && colorGradientPointer) {
    let dragging = false;
    colorGradient.addEventListener('pointerdown', (e)=>{
      dragging = true; colorGradient.setPointerCapture(e.pointerId);
      const p = setPointerPos(colorGradient, colorGradientPointer, e.clientX, e.clientY);
      currentS = p.px / p.w; currentV = 1 - (p.py / p.h);
      const hex = hsvToHex(currentHue, currentS, currentV);
      if (colorSample) colorSample.style.background = hex;
      if (colorHexInput) colorHexInput.value = hex;
      if (colorNative) colorNative.value = hex;
      applyColorToActive(hex);
    });
    window.addEventListener('pointermove', (e)=>{ if (!dragging) return; try{ const p = setPointerPos(colorGradient, colorGradientPointer, e.clientX, e.clientY); currentS = p.px / p.w; currentV = 1 - (p.py / p.h); const hex = hsvToHex(currentHue, currentS, currentV); if (colorSample) colorSample.style.background = hex; if (colorHexInput) colorHexInput.value = hex; if (colorNative) colorNative.value = hex; applyColorToActive(hex);}catch(e){} });
    window.addEventListener('pointerup', (e)=>{ if (!dragging) return; dragging = false; try{ colorGradient.releasePointerCapture(e.pointerId);}catch(e){} });
  }

  // Hue slider drag
  if (hueSlider && huePointer) {
    let draggingHue = false;
    hueSlider.addEventListener('pointerdown', (e)=>{
      draggingHue = true; hueSlider.setPointerCapture(e.pointerId);
      const rect = hueSlider.getBoundingClientRect();
      const px = clamp(e.clientX - rect.left, 0, rect.width);
      const h = px / rect.width;
      currentHue = h;
      huePointer.style.left = (px) + 'px';
      setGradientHue(currentHue);
      const hex = hsvToHex(currentHue, currentS, currentV);
      if (colorSample) colorSample.style.background = hex;
      if (colorHexInput) colorHexInput.value = hex;
      if (colorNative) colorNative.value = hex;
      applyColorToActive(hex);
    });
    window.addEventListener('pointermove', (e)=>{ if (!draggingHue) return; try{ const rect = hueSlider.getBoundingClientRect(); const px = clamp(e.clientX - rect.left, 0, rect.width); const h = px / rect.width; currentHue = h; huePointer.style.left = (px) + 'px'; setGradientHue(currentHue); const hex = hsvToHex(currentHue, currentS, currentV); if (colorSample) colorSample.style.background = hex; if (colorHexInput) colorHexInput.value = hex; if (colorNative) colorNative.value = hex; applyColorToActive(hex);}catch(e){} });
    window.addEventListener('pointerup', (e)=>{ if (!draggingHue) return; draggingHue = false; try{ hueSlider.releasePointerCapture(e.pointerId);}catch(e){} });
  }

  // Tabs
  document.querySelectorAll('.color-tabs .tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.color-tabs .tab').forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      const target = tab.dataset.tab;
      document.querySelectorAll('.tab-panel').forEach(p => { p.style.display = (p.dataset.panel === target) ? '' : 'none'; });
    });
  });

  // Populate preset swatches (5x5 grid) with sample palette
  if (presetSwatches) {
    const palette = [
      ['#0ea5e9','#38bdf8','#7dd3fc','#bae6fd','#e0f2fe'],
      ['#16a34a','#4ade80','#86efac','#bbf7d0','#ecfdf5'],
      ['#f59e0b','#facc15','#fde68a','#fff7cc','#fffaf0'],
      ['#ef4444','#fb7185','#fda4af','#ffdde1','#fff5f5'],
      ['#2563eb','#3b82f6','#60a5fa','#93c5fd','#dbeafe']
    ];
    palette.forEach(row => row.forEach(col => {
      const b = document.createElement('button');
      b.type='button'; b.className='swatch'; b.dataset.color = col; b.style.background = col;
      presetSwatches.appendChild(b);
    }));
  }

  // Eyedropper fallback: trigger native color input when clicked (real eyedropper requires browser API)
  if (eyedropperBtn && colorNative) {
    eyedropperBtn.addEventListener('click', () => {
      try { colorNative.click(); } catch(e){}
    });
  }

  // When external code sets the color (e.g., preset swatch clicks already handled), ensure gradient/hue pointers reflect current HEX
  function syncPickerFromHex(hex) {
    if (!hex) return;
    const hsv = hexToHsv(hex);
    currentHue = hsv.h; currentS = hsv.s; currentV = hsv.v;
    // place hue pointer
    if (hueSlider && huePointer) {
      const rect = hueSlider.getBoundingClientRect();
      huePointer.style.left = (currentHue * rect.width) + 'px';
    }
    if (colorGradient && colorGradientPointer) {
      const rect = colorGradient.getBoundingClientRect();
      colorGradientPointer.style.left = (currentS * rect.width) + 'px';
      colorGradientPointer.style.top = ((1 - currentV) * rect.height) + 'px';
    }
    setGradientHue(currentHue);
  }

  // initialize from toolbar swatch value
  try { const initialSw = document.querySelector('.toolbar-color-swatch'); if (initialSw) syncPickerFromHex(initialSw.dataset.color || initialSw.style.background); } catch(e){}

  function addRecentColor(color) {
    if (!recentColorsContainer) return;
    const list = recentColorsContainer.querySelector('.recent-list');
    if (!list) return;
    // maintain up to 8 unique colors
    const existing = Array.from(list.querySelectorAll('.swatch')).map(s=>s.dataset.color.toLowerCase());
    const c = color.toLowerCase();
    if (existing.includes(c)) return;
    const btn = document.createElement('button');
    btn.type='button';
    btn.className='swatch';
    btn.dataset.color = color;
    btn.style.background = color;
    list.insertBefore(btn, list.firstChild);
    // trim
    const all = list.querySelectorAll('.swatch');
    if (all.length > 8) all[all.length-1].remove();
  }


  async function loadFontsList() {
    if (!fontList) return;
    fontList.innerHTML = '<div class="font-list-loading">Loading fonts…</div>';
    const fallback = ["Roboto","Open Sans","Lato","Montserrat","Merriweather","Playfair Display","Source Sans Pro"];
    if (!googleApiKey) {
      // show fallback
      fontList.innerHTML = '';
      fallback.forEach(name => appendFontItem(name));
      return;
    }

    try {
      const res = await fetch(`https://www.googleapis.com/webfonts/v1/webfonts?key=${encodeURIComponent(googleApiKey)}&sort=popularity`);
      if (!res.ok) throw new Error('Failed to fetch');
      const data = await res.json();
      const items = data.items || [];
      fontList.innerHTML = '';
      items.slice(0, 200).forEach(f => appendFontItem(f.family));
      populateRecentFonts();
    } catch (err) {
      console.error('Fonts API failed', err);
      fontList.innerHTML = '';
      fallback.forEach(name => appendFontItem(name));
      populateRecentFonts();
    }
  }

  function collectRecentFonts() {
    const set = new Set();
    // scan text inputs
    document.querySelectorAll('input[data-text-node]').forEach(i => {
      const ff = i.style.fontFamily || i.dataset.fontFamily || null;
      if (ff) set.add(ff.replace(/['\"]+/g, '').split(',')[0].trim());
    });
    // scan svg nodes
    ['front','back'].forEach(side => {
      svgTextCache[side]?.forEach(node => {
        if (!node) return;
        const style = node.getAttribute('style') || '';
        const match = style.match(/font-family:\s*['"]?([^;',]+)['"]?/i);
        if (match && match[1]) set.add(match[1].trim());
      });
    });
    return Array.from(set).slice(0, 8);
  }

  function populateRecentFonts() {
    const recent = document.getElementById('recentFonts');
    const list = recent?.querySelector('.recent-list');
    if (!recent || !list) return;
    const fonts = collectRecentFonts();
    list.innerHTML = '';
    if (fonts.length === 0) {
      recent.setAttribute('aria-hidden', 'true');
      list.textContent = 'No recent fonts';
      return;
    }
    recent.setAttribute('aria-hidden', 'false');
    fonts.forEach(f => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'recent-item';
      b.textContent = f;
      b.addEventListener('click', () => selectFont(f));
      list.appendChild(b);
    });
  }

  function appendFontItem(family) {
    if (!fontList) return;
    const div = document.createElement('div');
    div.className = 'font-item';
    div.dataset.family = family;
    const sample = document.createElement('div');
    sample.className = 'font-sample';
    sample.textContent = family;
    // Use safe fallback initially; the real font will be applied when loaded by the observer
    sample.style.fontFamily = 'system-ui, sans-serif';
    div.appendChild(sample);
    div.addEventListener('click', () => selectFont(family));
    fontList.appendChild(div);
    // observe for lazy-loading
    try {
      if (fontObserver) fontObserver.observe(div);
    } catch (e) {}
  }

  let lastLoadedFonts = new Set();
  function loadFontCss(family) {
    // load via Google Fonts stylesheet
    if (lastLoadedFonts.has(family)) return Promise.resolve();
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(family)}:wght@400;700&display=swap`;
    document.head.appendChild(link);
    lastLoadedFonts.add(family);
    return new Promise(resolve => {
      link.onload = () => resolve();
      link.onerror = () => resolve();
    });
  }

  // IntersectionObserver to lazy-load fonts when their item enters view
  let fontObserver = null;
  try {
    fontObserver = new IntersectionObserver((entries) => {
      entries.forEach(async entry => {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        const family = el.dataset.family;
        if (!family) return;
        // load the font css and then apply to sample
        await loadFontCss(family);
        const sample = el.querySelector('.font-sample');
        if (sample) sample.style.fontFamily = `'${family}', system-ui, sans-serif`;
        fontObserver.unobserve(el);
      });
    }, { root: fontList, rootMargin: '200px', threshold: 0.01 });
  } catch (e) {
    fontObserver = null;
  }

  async function selectFont(family) {
    await loadFontCss(family);
    // apply to focused input or svg node
    const active = document.activeElement;
    if (active && active.matches && active.matches('input[data-text-node]')) {
      // update input dataset and value styling
      active.style.fontFamily = `'${family}', system-ui, sans-serif`;
      // persist to dataset for form submit
      try { active.dataset.fontFamily = family; } catch(e){}
      // update SVG node
      const side = getFieldSide(active);
      const nodeKey = getTextNodeKey(active);
      const node = svgTextCache[side]?.get(nodeKey);
      if (node) {
        node.setAttribute('style', `font-family: '${family}', system-ui, sans-serif;`);
        try { node.dataset.fontFamily = family; } catch(e){}
      }
    } else if (inlineEditorNode) {
      inlineEditorNode.setAttribute('style', `font-family: '${family}', system-ui, sans-serif;`);
      try { inlineEditorNode.dataset.fontFamily = family; } catch(e){}
    }
    else if (selectedElementNode && selectedElementNode.tagName && selectedElementNode.tagName.toLowerCase() === 'text') {
      // apply font to the currently selected SVG text node
      try {
        selectedElementNode.setAttribute('style', `font-family: '${family}', system-ui, sans-serif;`);
        try { selectedElementNode.dataset.fontFamily = family; } catch(e){}
        try { updatePreviewFromSvg(selectedElementSide || currentView || 'front'); } catch(e){}
        try { createBoundingBox(selectedElementNode, selectedElementSide || currentView || 'front'); } catch(e){}
      } catch (err) {
        console.error('selectFont apply to selectedElementNode failed', err);
      }
    }
    closeFontModal();
  }

  // open modal when font toolbar button clicked
  document.querySelectorAll('[data-tool="font"]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      openFontModal();
    });
  });

  // Font size input + dropdown behavior
  const fontSizeControl = document.querySelector('.font-size-control');
  const fontSizeInput = document.getElementById('toolbarFontSizeInput');
  const fontSizeDropdown = document.querySelector('.font-size-dropdown');

  function applyFontSizeToActive(size) {
    if (!size) return;
    const active = document.activeElement;
    let captureNode = null, captureSide = null;
    if (active && active.matches && active.matches('input[data-text-node]')) {
      captureNode = svgTextCache[getFieldSide(active)]?.get(getTextNodeKey(active));
      captureSide = getFieldSide(active);
      if (captureNode) beginHistoryCapture(captureNode, captureSide);
      active.style.fontSize = size + 'px';
      try { active.dataset.fontSize = String(size); } catch(e){}
      const side = getFieldSide(active);
      const nodeKey = getTextNodeKey(active);
      const node = svgTextCache[side]?.get(nodeKey);
      if (node) {
        // instead of changing font-size, adjust wrapper scale so visual size changes uniformly
        const wrapper = ensureTransformWrapper(node);
        // compute baseline font-size as numeric
        const baseline = parseFloat(node.getAttribute('font-size') || window.getComputedStyle(node).fontSize || String(size)) || size;
        const scale = Number(size) / baseline;
        applyWrapperTransform(wrapper, scale, Number(wrapper.dataset.iwRotate || 0) || 0, node);
        try { node.dataset.fontSize = String(size); } catch(e){}
      }
    } else if (inlineEditorNode) {
      // apply to the inline editor node similarly
      const node = inlineEditorNode;
      beginHistoryCapture(node, inlineEditorSide);
      const wrapper = ensureTransformWrapper(node);
      const baseline = parseFloat(node.getAttribute('font-size') || window.getComputedStyle(node).fontSize || String(size)) || size;
      const scale = Number(size) / baseline;
      applyWrapperTransform(wrapper, scale, Number(wrapper.dataset.iwRotate || 0) || 0, node);
      try { node.dataset.fontSize = String(size); } catch(e){}
      try { endHistoryCapture(node, inlineEditorSide); } catch(e) {}
    }
    else if (selectedElementNode && selectedElementNode.tagName && selectedElementNode.tagName.toLowerCase() === 'text') {
      const node = selectedElementNode;
      const side = selectedElementSide || currentView || 'front';
      beginHistoryCapture(node, side);
      const wrapper = ensureTransformWrapper(node);
      const baseline = parseFloat(node.getAttribute('font-size') || window.getComputedStyle(node).fontSize || String(size)) || size;
      const scale = Number(size) / baseline;
      applyWrapperTransform(wrapper, scale, Number(wrapper.dataset.iwRotate || 0) || 0, node);
      try { node.dataset.fontSize = String(size); } catch(e){}
      try { endHistoryCapture(node, side); } catch(e){}
    }
    try { if (captureNode && captureSide) endHistoryCapture(captureNode, captureSide); } catch(e) {}
  }

  if (fontSizeDropdown) {
    // open dropdown when input is clicked or focused+arrow key pressed
    if (fontSizeInput) {
      fontSizeInput.addEventListener('click', (e) => {
        e.stopPropagation();
        const isOpen = fontSizeDropdown.getAttribute('aria-hidden') === 'false';
        fontSizeDropdown.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
      });
      fontSizeInput.addEventListener('keydown', (e) => {
        // Space/Enter open, ArrowDown open and focus first option, Escape closes
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          const isOpen = fontSizeDropdown.getAttribute('aria-hidden') === 'false';
          fontSizeDropdown.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
        } else if (e.key === 'ArrowDown') {
          e.preventDefault();
          fontSizeDropdown.setAttribute('aria-hidden', 'false');
          try { const first = fontSizeDropdown.querySelector('.font-size-option'); if (first) first.focus(); } catch(e){}
        } else if (e.key === 'Escape') {
          fontSizeDropdown.setAttribute('aria-hidden', 'true');
        }
      });
    }

    fontSizeDropdown.addEventListener('click', (e) => {
      const btn = e.target.closest('.font-size-option');
      if (!btn) return;
      const val = Number(btn.textContent.trim());
      if (fontSizeInput) fontSizeInput.value = String(val);
      applyFontSizeToActive(val);
      fontSizeDropdown.setAttribute('aria-hidden', 'true');
    });

    document.addEventListener('click', (e) => {
      if (!fontSizeControl) return;
      if (fontSizeControl.contains(e.target)) return;
      fontSizeDropdown.setAttribute('aria-hidden', 'true');
    });
  }

  if (fontSizeInput) {
    fontSizeInput.addEventListener('change', () => {
      const val = Number(fontSizeInput.value);
      if (Number.isFinite(val)) applyFontSizeToActive(val);
    });
    // make clicking the input open the dropdown as a single control
    fontSizeInput.addEventListener('click', (e) => {
      e.stopPropagation();
      if (fontSizeDropdown) fontSizeDropdown.setAttribute('aria-hidden', 'false');
    });
    fontSizeInput.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (fontSizeDropdown) fontSizeDropdown.setAttribute('aria-hidden', 'false');
      }
    });
  }

  // Populate toolbar controls based on a selected SVG text node
  function populateToolbarForNode(node, side) {
    if (!node) return;
    // font size (map from wrapper scale back to px)
    const wrapper = ensureTransformWrapper(node);
    const baseline = parseFloat(node.getAttribute('font-size') || window.getComputedStyle(node).fontSize || '16') || 16;
    const scale = Number((wrapper && wrapper.dataset && wrapper.dataset.iwScale) || 1) || 1;
    const shownSize = Math.round(baseline * scale);
    if (fontSizeInput) fontSizeInput.value = String(shownSize);
    // color
    const fill = node.getAttribute('fill') || node.dataset.color || '#1f2933';
    const sw = document.querySelector('.toolbar-color-swatch');
    if (sw) { sw.style.background = fill; sw.dataset.color = fill; }
    if (colorNative) colorNative.value = fill;
    if (colorHexInput) colorHexInput.value = fill;
    // font family
    const family = extractFontFamily(node) || node.dataset.fontFamily || '';
    // Bold state
    try {
      const boldBtn = document.querySelector('[data-tool="bold"]');
      const fw = (node.getAttribute('font-weight') || node.style.fontWeight || node.dataset.fontWeight || '').toString();
      const isBold = (fw === '700' || fw.toLowerCase() === 'bold');
      if (boldBtn) {
        boldBtn.classList.toggle('is-active', !!isBold);
        boldBtn.setAttribute('aria-pressed', isBold ? 'true' : 'false');
      }
    } catch (e) {}

    // List state (simple heuristic: any line prefixed with bullet)
    try {
      const listBtn = document.querySelector('[data-tool="list"]');
      const textVal = getSvgNodeValue(node) || '';
      const lines = textVal.split(/\r?\n/).map(l => l.trim()).filter(Boolean);
      const hasBullets = lines.length > 0 && lines.every(l => l.startsWith('\u2022') || l.startsWith('-'));
      if (listBtn) {
        listBtn.classList.toggle('is-active', !!hasBullets);
        listBtn.setAttribute('aria-pressed', hasBullets ? 'true' : 'false');
      }
    } catch (e) {}

    // Alignment state: set dataset / title on align button so UI can indicate current align
    try {
      const alignBtn = document.querySelector('[data-tool="align"]');
      const anchor = node.getAttribute('text-anchor') || 'middle';
      const align = mapAnchorToAlign(anchor);
      if (alignBtn) {
        alignBtn.dataset.align = align;
        alignBtn.setAttribute('data-current-align', align);
        alignBtn.title = 'Align: ' + (align.charAt(0).toUpperCase() + align.slice(1));
      }
    } catch (e) {}

    // Ensure format select default
    try {
      const sel = document.querySelector('.toolbar-select');
      if (sel) sel.value = '';
    } catch (e) {}
  }

  // --- Toolbar actions: Bold / Align / List / Format (uppercase/lowercase) ---
  function toggleBoldOnActive() {
    // operate on focused input, inline editor, or selected SVG text
    const active = document.activeElement;
    if (active && active.matches && active.matches('input[data-text-node]')) {
      const side = getFieldSide(active);
      const node = svgTextCache[side]?.get(getTextNodeKey(active));
      if (node) beginHistoryCapture(node, side);
      const cur = active.style.fontWeight || active.dataset.fontWeight || '';
      const next = (cur === '700' || cur === 'bold') ? '' : '700';
      active.style.fontWeight = next;
      try { active.dataset.fontWeight = next; } catch(e){}
      if (node) {
        try { node.setAttribute('font-weight', next); } catch(e){}
        try { endHistoryCapture(node, side); } catch(e){}
        try { updatePreviewFromSvg(side); } catch(e){}
        createBoundingBox(node, side);
      }
      return;
    }

    if (inlineEditorNode) {
      beginHistoryCapture(inlineEditorNode, inlineEditorSide);
      const cur = inlineEditorNode.getAttribute('font-weight') || inlineEditorNode.style.fontWeight || '';
      const next = (cur === '700' || cur === 'bold') ? '' : '700';
      try { inlineEditorNode.setAttribute('font-weight', next); } catch(e){}
      try { inlineEditorNode.style.fontWeight = next; } catch(e){}
      try { endHistoryCapture(inlineEditorNode, inlineEditorSide); } catch(e){}
      try { updatePreviewFromSvg(inlineEditorSide); } catch(e){}
  createBoundingBox(inlineEditorNode, inlineEditorSide);
  try { populateToolbarForNode(inlineEditorNode, inlineEditorSide); } catch (e) {}
      return;
    }

    if (selectedElementNode && selectedElementNode.tagName && selectedElementNode.tagName.toLowerCase() === 'text') {
      const node = selectedElementNode;
      const side = selectedElementSide || currentView || 'front';
      beginHistoryCapture(node, side);
      const cur = node.getAttribute('font-weight') || node.style.fontWeight || '';
      const next = (cur === '700' || cur === 'bold') ? '' : '700';
      try { node.setAttribute('font-weight', next); } catch(e){}
      try { node.style.fontWeight = next; } catch(e){}
      try { endHistoryCapture(node, side); } catch(e){}
      try { updatePreviewFromSvg(side); } catch(e){}
      createBoundingBox(node, side);
      return;
    }
  }

  function cycleAlignOnActive() {
    const anchors = ['start','middle','end']; // start=left, middle=center, end=right
    const anchorMap = { start: 'left', middle: 'center', end: 'right' };
    const inverse = { left: 'start', center: 'middle', right: 'end' };

    const active = document.activeElement;
    if (active && active.matches && active.matches('input[data-text-node]')) {
      const side = getFieldSide(active);
      const node = svgTextCache[side]?.get(getTextNodeKey(active));
      if (node) beginHistoryCapture(node, side);
      const curAlign = (active.dataset.align || 'center').toLowerCase();
      const order = ['left','center','right'];
      const idx = order.indexOf(curAlign) >= 0 ? order.indexOf(curAlign) : 1;
      const next = order[(idx + 1) % order.length];
      try { active.dataset.align = next; } catch(e){}
      if (node) {
        try { node.setAttribute('text-anchor', inverse[next]); } catch(e){}
        // ensure tspans keep same x
        const x = node.getAttribute('x') || node.dataset.originalX || null;
        if (x) node.querySelectorAll('tspan').forEach(t => t.setAttribute('x', x));
        try { endHistoryCapture(node, side); } catch(e){}
        try { updatePreviewFromSvg(side); } catch(e){}
  createBoundingBox(node, side);
  try { if (node && node.tagName && node.tagName.toLowerCase() === 'text') populateToolbarForNode(node, side); } catch (e) {}
      }
      return;
    }

    if (inlineEditorNode) {
      const node = inlineEditorNode;
      const side = inlineEditorSide;
      beginHistoryCapture(node, side);
      const cur = mapAnchorToAlign(node.getAttribute('text-anchor')) || 'center';
      const order = ['left','center','right'];
      const idx = order.indexOf(cur) >= 0 ? order.indexOf(cur) : 1;
      const next = order[(idx + 1) % order.length];
      try { node.setAttribute('text-anchor', inverse[next]); } catch(e){}
      const x = node.getAttribute('x') || node.dataset.originalX || null;
      if (x) node.querySelectorAll('tspan').forEach(t => t.setAttribute('x', x));
      try { endHistoryCapture(node, side); } catch(e){}
      try { updatePreviewFromSvg(side); } catch(e){}
      createBoundingBox(node, side);
      return;
    }

    if (selectedElementNode && selectedElementNode.tagName && selectedElementNode.tagName.toLowerCase() === 'text') {
      const node = selectedElementNode;
      const side = selectedElementSide || currentView || 'front';
      beginHistoryCapture(node, side);
      const cur = mapAnchorToAlign(node.getAttribute('text-anchor')) || 'center';
      const order = ['left','center','right'];
      const idx = order.indexOf(cur) >= 0 ? order.indexOf(cur) : 1;
      const next = order[(idx + 1) % order.length];
      try { node.setAttribute('text-anchor', inverse[next]); } catch(e){}
      const x = node.getAttribute('x') || node.dataset.originalX || null;
      if (x) node.querySelectorAll('tspan').forEach(t => t.setAttribute('x', x));
      try { endHistoryCapture(node, side); } catch(e){}
      try { updatePreviewFromSvg(side); } catch(e){}
      createBoundingBox(node, side);
      return;
    }
  }

  function toggleListOnActive() {
    const bullet = '\u2022';
    const active = document.activeElement;
    if (active && active.matches && active.matches('input[data-text-node]')) {
      const side = getFieldSide(active);
      const node = svgTextCache[side]?.get(getTextNodeKey(active));
      if (node) beginHistoryCapture(node, side);
      const val = active.value || '';
      const lines = val.split(/\r?\n/).map(l => l.trim());
      const allBulleted = lines.length > 0 && lines.every(l => l.startsWith(bullet + ' '));
      const nextLines = allBulleted ? lines.map(l => l.replace(new RegExp('^' + bullet + '\\s?'), '')) : lines.map(l => (l ? bullet + ' ' + l : l));
      active.value = nextLines.join('\n');
      try { updateSvgNodeFromInput(active, active.value); } catch(e){}
      if (node) {
        try { endHistoryCapture(node, side); } catch(e){}
        try { updatePreviewFromSvg(side); } catch(e){}
        createBoundingBox(node, side);
      }
      return;
    }

    if (inlineEditorNode) {
      const node = inlineEditorNode;
      const side = inlineEditorSide;
      beginHistoryCapture(node, side);
      const cur = getSvgNodeValue(node) || '';
      const lines = cur.split(/\r?\n/).map(l => l.trim());
      const allBulleted = lines.length > 0 && lines.every(l => l.startsWith(bullet + ' '));
      const nextLines = allBulleted ? lines.map(l => l.replace(new RegExp('^' + bullet + '\\s?'), '')) : lines.map(l => (l ? bullet + ' ' + l : l));
      setSvgNodeText(node, nextLines.join('\n'));
      try { endHistoryCapture(node, side); } catch(e){}
      try { updatePreviewFromSvg(side); } catch(e){}
      createBoundingBox(node, side);
      return;
    }

    if (selectedElementNode && selectedElementNode.tagName && selectedElementNode.tagName.toLowerCase() === 'text') {
      const node = selectedElementNode;
      const side = selectedElementSide || currentView || 'front';
      beginHistoryCapture(node, side);
      const cur = getSvgNodeValue(node) || '';
      const lines = cur.split(/\r?\n/).map(l => l.trim());
      const allBulleted = lines.length > 0 && lines.every(l => l.startsWith(bullet + ' '));
      const nextLines = allBulleted ? lines.map(l => l.replace(new RegExp('^' + bullet + '\\s?'), '')) : lines.map(l => (l ? bullet + ' ' + l : l));
      setSvgNodeText(node, nextLines.join('\n'));
      try { endHistoryCapture(node, side); } catch(e){}
      try { updatePreviewFromSvg(side); } catch(e){}
      createBoundingBox(node, side);
      return;
    }
  }

  function applyTextTransformToActive(transform) {
    if (!transform) return;
    const active = document.activeElement;
    if (active && active.matches && active.matches('input[data-text-node]')) {
      const side = getFieldSide(active);
      const node = svgTextCache[side]?.get(getTextNodeKey(active));
      if (node) beginHistoryCapture(node, side);
      const v = active.value || '';
      const out = transform === 'uppercase' ? v.toUpperCase() : transform === 'lowercase' ? v.toLowerCase() : v;
      active.value = out;
      try { updateSvgNodeFromInput(active, out); } catch(e){}
      if (node) {
        try { endHistoryCapture(node, side); } catch(e){}
        try { updatePreviewFromSvg(side); } catch(e){}
        createBoundingBox(node, side);
      }
      return;
    }

    if (inlineEditorNode) {
      const node = inlineEditorNode;
      const side = inlineEditorSide;
      beginHistoryCapture(node, side);
      const v = getSvgNodeValue(node) || '';
      const out = transform === 'uppercase' ? v.toUpperCase() : transform === 'lowercase' ? v.toLowerCase() : v;
      setSvgNodeText(node, out);
      try { endHistoryCapture(node, side); } catch(e){}
      try { updatePreviewFromSvg(side); } catch(e){}
      createBoundingBox(node, side);
      return;
    }

    if (selectedElementNode && selectedElementNode.tagName && selectedElementNode.tagName.toLowerCase() === 'text') {
      const node = selectedElementNode;
      const side = selectedElementSide || currentView || 'front';
      beginHistoryCapture(node, side);
      const v = getSvgNodeValue(node) || '';
      const out = transform === 'uppercase' ? v.toUpperCase() : transform === 'lowercase' ? v.toLowerCase() : v;
      setSvgNodeText(node, out);
      try { endHistoryCapture(node, side); } catch(e){}
      try { updatePreviewFromSvg(side); } catch(e){}
      createBoundingBox(node, side);
      return;
    }
  }

  // wire toolbar buttons to these actions
  document.querySelectorAll('[data-tool="bold"]').forEach(btn => {
    btn.addEventListener('click', (e) => { e.stopPropagation(); toggleBoldOnActive(); });
  });
  document.querySelectorAll('[data-tool="align"]').forEach(btn => {
    btn.addEventListener('click', (e) => { e.stopPropagation(); cycleAlignOnActive(); });
  });
  document.querySelectorAll('[data-tool="list"]').forEach(btn => {
    btn.addEventListener('click', (e) => { e.stopPropagation(); toggleListOnActive(); });
  });
  const toolbarSelect = document.querySelector('.toolbar-select');
  if (toolbarSelect) {
    toolbarSelect.addEventListener('change', (e) => {
      const v = toolbarSelect.value;
      if (!v) return;
      applyTextTransformToActive(v);
      // reset selection to default
      toolbarSelect.value = '';
    });
  }

  // quick filter
  if (fontSearch) {
    let timer = null;
    fontSearch.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(() => {
        const q = fontSearch.value.trim().toLowerCase();
        Array.from(document.querySelectorAll('.font-item')).forEach(item => {
          const text = item.textContent.toLowerCase();
          item.style.display = q && !text.includes(q) ? 'none' : '';
        });
      }, 160);
    });
  }

  // --- List dropdown wiring (UL / OL) ---
  const toolbarListBtn = document.getElementById('toolbarListBtn');
  const listDropdownMenu = document.querySelector('.list-dropdown-menu');
  let listDropdownOpen = false;

  function openListDropdown() {
    if (!toolbarListBtn || !listDropdownMenu) return;
    toolbarListBtn.setAttribute('aria-expanded', 'true');
    listDropdownMenu.setAttribute('aria-hidden', 'false');
    listDropdownOpen = true;
    // focus first actionable item for keyboard navigation
    try {
      const first = listDropdownMenu.querySelector('.list-option');
      if (first && typeof first.focus === 'function') first.focus();
    } catch (e) {}
  }

  function closeListDropdown() {
    if (!toolbarListBtn || !listDropdownMenu) return;
    toolbarListBtn.setAttribute('aria-expanded', 'false');
    listDropdownMenu.setAttribute('aria-hidden', 'true');
    listDropdownOpen = false;
  }

  function toggleListDropdown() {
    if (listDropdownOpen) closeListDropdown(); else openListDropdown();
  }

  // click on the toolbar button toggles the dropdown; stop propagation so document handler doesn't immediately close it
  if (toolbarListBtn) {
    toolbarListBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      // Only toggle when clicking the icon inside the button (or the button itself)
      const target = e.target;
      const isIcon = target.classList && (target.classList.contains('fa-list-ul') || target.classList.contains('fa-list-ol') || target.classList.contains('fa-ban'));
      if (!isIcon && target !== toolbarListBtn && !target.closest) return;
      // if clicked an unrelated child, ignore
      // If the closest icon ancestor exists, allow toggle
      if (!isIcon && !target.closest('.fa-list-ul, .fa-list-ol, .fa-ban')) return;
      toggleListDropdown();
    });
    // keyboard support: Enter/Space toggle, Escape closes
    toolbarListBtn.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggleListDropdown();
      } else if (e.key === 'Escape') {
        closeListDropdown();
      }
    });
  }

  // clicking outside the button/menu closes the dropdown
  document.addEventListener('click', (e) => {
    if (!toolbarListBtn || !listDropdownMenu) return;
    if (toolbarListBtn.contains(e.target)) return;
    if (listDropdownMenu.contains(e.target)) return;
    closeListDropdown();
  });

  // menu option clicks
  if (listDropdownMenu) {
    listDropdownMenu.querySelectorAll('.list-option').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const type = btn.dataset.list; // 'ul' or 'ol' or 'none'
        applyListType(type);
        closeListDropdown();
      });
      btn.setAttribute('tabindex', '0');
      btn.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          const type = btn.dataset.list;
          applyListType(type);
          closeListDropdown();
        } else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
          e.preventDefault();
          const next = btn.nextElementSibling || listDropdownMenu.querySelector('.list-option');
          if (next) next.focus();
        } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
          e.preventDefault();
          const prev = btn.previousElementSibling || listDropdownMenu.querySelector('.list-option:last-child');
          if (prev) prev.focus();
        } else if (e.key === 'Escape') {
          closeListDropdown();
          try { toolbarListBtn.focus(); } catch (e) {}
        }
      });
    });
  }

  // --- Format dropdown (uppercase/lowercase) wiring ---
  const toolbarFormatBtn = document.getElementById('toolbarFormatBtn');
  const formatDropdownMenu = document.querySelector('.format-dropdown-menu');
  let formatDropdownOpen = false;

  function openFormatDropdown() {
    if (!toolbarFormatBtn || !formatDropdownMenu) return;
    toolbarFormatBtn.setAttribute('aria-expanded', 'true');
    formatDropdownMenu.setAttribute('aria-hidden', 'false');
    formatDropdownOpen = true;
    try { const first = formatDropdownMenu.querySelector('.format-option'); if (first) first.focus(); } catch(e){}
  }
  function closeFormatDropdown() { if (!toolbarFormatBtn || !formatDropdownMenu) return; toolbarFormatBtn.setAttribute('aria-expanded','false'); formatDropdownMenu.setAttribute('aria-hidden','true'); formatDropdownOpen = false; }
  function toggleFormatDropdown(){ if(formatDropdownOpen) closeFormatDropdown(); else openFormatDropdown(); }

  if (toolbarFormatBtn) {
    toolbarFormatBtn.addEventListener('click', (e)=>{ e.stopPropagation(); toggleFormatDropdown(); });
    toolbarFormatBtn.addEventListener('keydown', (e)=>{ if(e.key==='Enter' || e.key===' ') { e.preventDefault(); toggleFormatDropdown(); } else if (e.key==='Escape') closeFormatDropdown(); });
  }
  if (formatDropdownMenu) {
    const opts = formatDropdownMenu.querySelectorAll('.format-option');
    opts.forEach(btn => {
      btn.setAttribute('tabindex','0');
      btn.addEventListener('click',(e)=>{ e.stopPropagation(); const t = btn.dataset.format; applyTextTransformToActive(t); closeFormatDropdown(); });
      btn.addEventListener('keydown',(e)=>{
        if(e.key==='Enter' || e.key===' '){ e.preventDefault(); applyTextTransformToActive(btn.dataset.format); closeFormatDropdown(); }
        else if(e.key==='ArrowRight' || e.key==='ArrowDown'){ e.preventDefault(); (btn.nextElementSibling || opts[0]).focus(); }
        else if(e.key==='ArrowLeft' || e.key==='ArrowUp'){ e.preventDefault(); (btn.previousElementSibling || opts[opts.length-1]).focus(); }
        else if(e.key==='Escape'){ closeFormatDropdown(); try{ toolbarFormatBtn.focus(); }catch(e){} }
      });
    });
  }

  // close format dropdown when clicking outside
  document.addEventListener('click', (e)=>{ if(!toolbarFormatBtn || !formatDropdownMenu) return; if (toolbarFormatBtn.contains(e.target)) return; if (formatDropdownMenu.contains(e.target)) return; closeFormatDropdown(); });

  // --- Align dropdown (left/center/right/justify) wiring ---
  const toolbarAlignBtn = document.getElementById('toolbarAlignBtn');
  const alignDropdownMenu = document.querySelector('.align-dropdown-menu');
  let alignDropdownOpen = false;

  function openAlignDropdown() {
    if (!toolbarAlignBtn || !alignDropdownMenu) return;
    toolbarAlignBtn.setAttribute('aria-expanded', 'true');
    alignDropdownMenu.setAttribute('aria-hidden', 'false');
    alignDropdownOpen = true;
    try { const first = alignDropdownMenu.querySelector('.align-option'); if (first) first.focus(); } catch(e){}
  }
  function closeAlignDropdown() { if (!toolbarAlignBtn || !alignDropdownMenu) return; toolbarAlignBtn.setAttribute('aria-expanded','false'); alignDropdownMenu.setAttribute('aria-hidden','true'); alignDropdownOpen = false; }
  function toggleAlignDropdown(){ if(alignDropdownOpen) closeAlignDropdown(); else openAlignDropdown(); }

  if (toolbarAlignBtn) {
    toolbarAlignBtn.addEventListener('click', (e) => { e.stopPropagation(); toggleAlignDropdown(); });
    toolbarAlignBtn.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleAlignDropdown(); } else if (e.key === 'Escape') closeAlignDropdown(); });
  }

  if (alignDropdownMenu) {
    const opts = alignDropdownMenu.querySelectorAll('.align-option');
    opts.forEach(btn => {
      btn.setAttribute('tabindex','0');
      btn.addEventListener('click', (e) => {
        e.stopPropagation();
        const a = (btn.dataset.align || '').toLowerCase();
        // apply to active target
        applyAlignToActive(a);
        closeAlignDropdown();
      });
      btn.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); applyAlignToActive(btn.dataset.align); closeAlignDropdown(); }
        else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') { e.preventDefault(); (btn.nextElementSibling || opts[0]).focus(); }
        else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') { e.preventDefault(); (btn.previousElementSibling || opts[opts.length-1]).focus(); }
        else if (e.key === 'Escape') { closeAlignDropdown(); try { toolbarAlignBtn.focus(); } catch(e){} }
      });
    });
  }

  // close align dropdown when clicking outside
  document.addEventListener('click', (e) => { if (!toolbarAlignBtn || !alignDropdownMenu) return; if (toolbarAlignBtn.contains(e.target)) return; if (alignDropdownMenu.contains(e.target)) return; closeAlignDropdown(); });

  // close on Escape key anywhere
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeListDropdown();
  });

  // Ensure dropdown is closed on init (defensive) and ignore hover/focus auto-open
  try { closeListDropdown(); } catch (e) {}
  if (listDropdownMenu) {
    // Prevent accidental opening via hover/focus events
    listDropdownMenu.addEventListener('pointerenter', (e) => { e.stopPropagation(); });
    listDropdownMenu.addEventListener('mouseover', (e) => { e.stopPropagation(); });
  }
  if (toolbarListBtn) {
    toolbarListBtn.addEventListener('focus', (e) => { /* don't open on focus */ });
    toolbarListBtn.addEventListener('mouseover', (e) => { /* don't open on hover */ });
  }

  function applyListType(type) {
    // type: 'ul' (bulleted) or 'ol' (numbered)
    const active = document.activeElement;
    const bullet = '\u2022';
    const numberedRegex = /^\s*\d+\.\s*/;
    const bulletRegex = /^\s*[\u2022\-*]\s*/;
    if (active && active.matches && active.matches('input[data-text-node]')) {
      const side = getFieldSide(active);
      const node = svgTextCache[side]?.get(getTextNodeKey(active));
      const val = active.value || '';
      const lines = val.split(/\r?\n/).map(l => l.trim());
      let outLines = [];
      if (type === 'ul') {
        outLines = lines.map(l => (l ? bullet + ' ' + l : l));
      } else if (type === 'ol') {
        outLines = lines.map((l, i) => (l ? (i+1) + '. ' + l : l));
      } else if (type === 'none') {
        outLines = lines.map(l => l.replace(numberedRegex, '').replace(bulletRegex, ''));
      }
      active.value = outLines.join('\n');
      try { updateSvgNodeFromInput(active, active.value); } catch(e){}
      if (node) try { updatePreviewFromSvg(side); } catch(e){}
      return;
    }

    if (inlineEditorNode) {
      const node = inlineEditorNode;
      const side = inlineEditorSide;
      const cur = getSvgNodeValue(node) || '';
      const lines = cur.split(/\r?\n/).map(l => l.trim());
      let out = '';
  if (type === 'ul') out = lines.map(l => (l ? bullet + ' ' + l : l)).join('\n');
  else if (type === 'ol') out = lines.map((l,i)=> (l ? (i+1) + '. ' + l : l)).join('\n');
  else if (type === 'none') out = lines.map(l => l.replace(numberedRegex, '').replace(bulletRegex, '')).join('\n');
      beginHistoryCapture(node, side);
      setSvgNodeText(node, out);
      try { endHistoryCapture(node, side); } catch(e){}
      try { updatePreviewFromSvg(side); } catch(e){}
      createBoundingBox(node, side);
      return;
    }

    if (selectedElementNode && selectedElementNode.tagName && selectedElementNode.tagName.toLowerCase() === 'text') {
      const node = selectedElementNode;
      const side = selectedElementSide || currentView || 'front';
      const cur = getSvgNodeValue(node) || '';
      const lines = cur.split(/\r?\n/).map(l => l.trim());
      let out = '';
  if (type === 'ul') out = lines.map(l => (l ? bullet + ' ' + l : l)).join('\n');
  else if (type === 'ol') out = lines.map((l,i)=> (l ? (i+1) + '. ' + l : l)).join('\n');
  else if (type === 'none') out = lines.map(l => l.replace(numberedRegex, '').replace(bulletRegex, '')).join('\n');
      beginHistoryCapture(node, side);
      setSvgNodeText(node, out);
      try { endHistoryCapture(node, side); } catch(e){}
      try { updatePreviewFromSvg(side); } catch(e){}
      createBoundingBox(node, side);
      return;
    }
  }

  document.addEventListener("pointerdown", event => {
    if (!textToolbar) return;
    if (textToolbar.contains(event.target)) return;
    const boundingHandle = event.target.closest('.bounding-box-handle');
    if (boundingHandle) {
      event.preventDefault();
      event.stopPropagation();
      const type = boundingHandle.getAttribute('data-handle-type');
      const svg = boundingHandle.closest('svg');
      const side = svg.id === 'cardFront' ? 'front' : 'back';
      // prefer explicit selected element when available
      let node = selectedElementNode;
      // if the handle stores a target node key, prefer that to find the correct node
      try {
        const targetKey = boundingHandle.dataset && boundingHandle.dataset.targetNodeKey;
        if (!node && targetKey) {
          const safe = escapeAttr(targetKey);
          node = svg.querySelector(`[data-text-node="${safe}"]`) || svg.querySelector(`[data-text-node="${targetKey}"]`);
        }
      } catch (e) {
        // fallback to existing heuristics
      }
      if (!node) {
        node = svg.querySelector('[data-text-node]') || svg.querySelector('image');
      }
      if (!node) return;
      if (type === 'resize') {
        resizeState.active = true;
  resizeState.node = node;
        resizeState.side = side;
        resizeState.startY = event.clientY;
        // ensure wrapper for text nodes, for images we store scale on element.dataset
        try {
          if (node.tagName && node.tagName.toLowerCase() === 'text') {
            resizeState.startScale = Number((ensureTransformWrapper(node).dataset.iwScale) || 1);
          } else {
            resizeState.startScale = Number(node.dataset.iwScale || 1);
          }
        } catch(e) { resizeState.startScale = 1; }
        resizeState.pointerId = event.pointerId;
        boundingHandle.setPointerCapture(event.pointerId);
        beginHistoryCapture(node, side);
      } else if (type === 'rotate') {
        const wrapper = ensureTransformWrapper(node);
        rotateState.active = true;
        rotateState.node = node;
        rotateState.side = side;
        rotateState.startAngle = Number((wrapper && wrapper.dataset && wrapper.dataset.iwRotate) || 0) || 0;
        const pt = getSvgCoordinates(svg, event.clientX, event.clientY);
        const center = computeWrapperCenter(wrapper, node);
        rotateState.startPointerAngle = Math.atan2(pt.y - center.cy, pt.x - center.cx) * (180 / Math.PI);
        rotateState.pointerId = event.pointerId;
        boundingHandle.setPointerCapture(event.pointerId);
        beginHistoryCapture(node, side);
        // add a visual state so the rotate icon can animate while user rotates
        try { if (wrapper && wrapper.classList) wrapper.classList.add('is-rotating'); } catch(e) {}
      } else if (type === 'move') {
        dragState.active = true;
        dragState.node = node;
        dragState.side = side;
        dragState.pointerId = event.pointerId;
        const svgPoint = getSvgPoint(svg, event.clientX, event.clientY);
        // support text and image X/Y attr
        const nx = Number(node.getAttribute('x') || node.getAttribute('data-x') || 0);
        const ny = Number(node.getAttribute('y') || node.getAttribute('data-y') || 0);
        dragState.offsetX = svgPoint.x - nx;
        dragState.offsetY = svgPoint.y - ny;
        boundingHandle.setPointerCapture(event.pointerId);
        beginHistoryCapture(node, side);
      }
      return;
    }
    if (
      event.target.closest?.(".text-field") ||
      event.target.closest?.(".canvas [data-text-node]")
    ) {
      showTextToolbar(event.target);
      return;
    }
    hideTextToolbar();
    // remove bounding box if clicking outside
    removeBoundingBox('front');
    removeBoundingBox('back');
  });

  // Support pointerdown on invisible hit targets created as part of bounding handles
  document.addEventListener('pointerdown', (ev) => {
    const hit = ev.target && ev.target.closest && ev.target.closest('.bounding-box-hit');
    if (!hit) return;
    // find parent handle group
    const handleGroup = hit.closest && hit.closest('.bounding-box-handle');
    if (!handleGroup) return;
    // re-dispatch a pointerdown onto the handle group so existing logic handles it
    try {
      const pd = new PointerEvent('pointerdown', Object.assign({}, ev));
      handleGroup.dispatchEvent(pd);
      ev.preventDefault();
      ev.stopPropagation();
    } catch (e) {
      // fallback: call click handler by manually invoking existing pointer handling path
    }
  });

  const SVG_NS = "http://www.w3.org/2000/svg";
  const XLINK_NS = "http://www.w3.org/1999/xlink";
  const ZOOM_MIN = 0.5;
  const ZOOM_MAX = 2;
  const ZOOM_STEP = 0.1;
  const TEXT_BASE_TOP = 24;
  const TEXT_SPACING = 12;
  const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5MB limit

  const imageState = {
    front: {
      defaultSrc: imagePreviews.front?.dataset?.defaultSrc || cardFront?.dataset?.defaultImage || "",
      currentSrc: "",
    },
    back: {
      defaultSrc: imagePreviews.back?.dataset?.defaultSrc || cardBack?.dataset?.defaultImage || "",
      currentSrc: "",
    },
  };

  // Automatic image insertion when a file is chosen in the Images panel
  (function wireImageUploadInput() {
    if (!canReplaceImages) return;
    const fileInput = document.getElementById('imageUploadInput');
    if (!fileInput) return;
    fileInput.addEventListener('change', async (e) => {
      const f = fileInput.files && fileInput.files[0];
      if (!f) return;
      try {
        if (f.size > MAX_IMAGE_SIZE) {
          alert('Image is too large. Max 5MB allowed.');
          fileInput.value = '';
          return;
        }
        const blobUrl = URL.createObjectURL(f);
        const side = currentView || 'front';
        // set as background image for the chosen side
        try {
          applyImage(side, blobUrl);
        } catch (err) {
          console.error('applyImage failed', err);
        }

        // update recent images UI (clicking thumbnail will set as background)
        try {
          const svg = getSvgRoot(side);
          const list = document.getElementById('recentImagesList');
          if (list) {
            const thumb = document.createElement('button');
            thumb.type = 'button';
            thumb.className = 'recent-image-item';
            thumb.dataset.src = blobUrl;
            thumb.style.backgroundImage = `url('${blobUrl}')`;
            thumb.title = f.name;
            thumb.addEventListener('click', () => {
              applyImage(side, thumb.dataset.src);
            });
            if (list.firstChild && list.firstChild.classList && list.firstChild.classList.contains('recent-placeholder')) list.innerHTML = '';
            list.insertBefore(thumb, list.firstChild || null);
            const items = list.querySelectorAll('.recent-image-item');
            if (items.length > 12) items[items.length - 1].remove();
          }
        } catch (e) {}

        // keep track of blob so we can revoke on next upload if needed
        try {
          if (imageBlobUrls[side]) URL.revokeObjectURL(imageBlobUrls[side]);
        } catch (err) {}
        imageBlobUrls[side] = blobUrl;

        // clear the input so same file can be selected again if needed
        try { fileInput.value = ''; } catch (e) {}
      } catch (err) {
        console.error('image upload insert failed', err);
        try { fileInput.value = ''; } catch(e) {}
        alert('Failed to insert image.');
      }
    });
  })();

  const imageLayers = {
    front: null,
    back: null,
  };

  const imageFallbackLayers = {
    front: null,
    back: null,
  };

  // store created blob URLs for fetched SVGs so we can revoke them when replaced
  const imageBlobUrls = {
    front: null,
    back: null,
  };

  let zoom = 1.0;
  let currentView = "front";

  const svgTextCache = {
    front: new Map(),
    back: new Map(),
  };
  let autoNodeCounter = 0;

  function normalizeKeyFragment(value) {
    return String(value || "")
      .trim()
      .replace(/[\s#/]+/g, "-")
      .replace(/[^a-zA-Z0-9_-]+/g, "-")
      .replace(/^-+|-+$/g, "");
  }

  function ensureTextNodeKey(node, side) {
    if (!node) return "";
    let key = (node.getAttribute("data-text-node") || "").trim();
    if (key) return key;
    const rawId = node.getAttribute("id") || node.getAttribute("name") || "";
    let fragment = normalizeKeyFragment(rawId) || normalizeKeyFragment((node.textContent || "").split(/\s+/).slice(0, 3).join("-"));
    if (!fragment) {
      fragment = `text-${++autoNodeCounter}`;
    } else {
      fragment = fragment.toLowerCase();
    }
    key = `${side}-${fragment}`;
    while (svgTextCache[side] && svgTextCache[side].has(key)) {
      key = `${side}-${fragment}-${++autoNodeCounter}`;
    }
    node.setAttribute("data-text-node", key);
    return key;
  }

  function extractFontFamily(node) {
    if (!node) return "";
    const direct = node.getAttribute("font-family");
    if (direct) return direct.replace(/["']/g, "").trim();
    const style = node.getAttribute("style") || "";
    const match = style.match(/font-family:\s*['"]?([^;'"\n]+)/i);
    if (match && match[1]) return match[1].trim();
    return "";
  }

  function mapAnchorToAlign(anchor) {
    switch ((anchor || "").toLowerCase()) {
      case "start":
        return "left";
      case "end":
        return "right";
      case "middle":
      case "center":
        return "center";
      default:
        return "center";
    }
  }

  function computePercentPosition(node, side) {
    const svg = getSvgRoot(side);
    const metrics = parseViewBox(svg);
    const rawX = Number.parseFloat(node?.getAttribute("x") || node?.dataset?.originalX || `${metrics.x + metrics.width / 2}`);
    const rawY = Number.parseFloat(node?.getAttribute("y") || node?.dataset?.originalY || `${metrics.y + metrics.height / 2}`);
    const leftPercent = Number.isFinite(rawX) && metrics.width
      ? ((rawX - metrics.x) / metrics.width) * 100
      : 50;
    const topPercent = Number.isFinite(rawY) && metrics.height
      ? ((rawY - metrics.y) / metrics.height) * 100
      : TEXT_BASE_TOP;
    return {
      left: Number.isFinite(leftPercent) ? leftPercent.toFixed(2) : "50.00",
      top: Number.isFinite(topPercent) ? topPercent.toFixed(2) : Number(TEXT_BASE_TOP).toFixed(2),
    };
  }

  let inlineEditor = null;
  let inlineEditorNode = null;
  let inlineEditorSide = null;
  let inlineEditorOriginalValue = "";
  let resizeHandle = null;
  let resizeState = { active: false, startY: 0, startScale: 1, pointerId: null, node: null, side: null };
  let rotateHandle = null;
  let rotateState = { active: false, startAngle: 0, startPointerAngle: 0, pointerId: null, node: null, side: null };
  let dragState = {
    active: false,
    node: null,
    side: null,
    pointerId: null,
    svg: null,
    offsetX: 0,
    offsetY: 0,
  };
  // currently selected element (text or image) and its side
  let selectedElementNode = null;
  let selectedElementSide = null;
  // History stack for undo/redo
  const history = [];
  let historyIndex = -1; // points at last applied action
  let pendingCapture = null; // temporary capture during pointer/inline edits

  function readNodeState(node, side) {
    if (!node) return null;
    const text = getSvgNodeValue(node);
    const x = Number.parseFloat(node.getAttribute('x') || node.dataset.originalX || '0');
    const y = Number.parseFloat(node.getAttribute('y') || node.dataset.originalY || '0');
    const fontSize = node.getAttribute('font-size') || node.dataset.fontSize || '';
    const letterSpacing = node.getAttribute('letter-spacing') || node.dataset.letterSpacing || '';
    const fill = node.getAttribute('fill') || node.dataset.color || '';
    const fontFamily = extractFontFamily(node) || node.dataset.fontFamily || '';
    const wrapper = (node.parentNode && node.parentNode.dataset && node.parentNode.dataset.iwWrapper === 'true') ? node.parentNode : null;
    const wrapperScale = wrapper ? Number(wrapper.dataset.iwScale || 1) : 1;
    const wrapperRotate = wrapper ? Number(wrapper.dataset.iwRotate || 0) : 0;
    return { text, x, y, fontSize, letterSpacing, fill, fontFamily, wrapperScale, wrapperRotate };
  }

  function applyNodeState(nodeKey, side, state) {
    if (!state || !nodeKey) return;
    // ensure node exists
    let node = svgTextCache[side]?.get(nodeKey);
    if (!node) {
      // try to create node via corresponding input if present
      const input = findTextFieldInput(nodeKey, side);
      if (input) node = ensureSvgNodeForInput(input);
      else {
        // create a bare node
        const svg = getSvgRoot(side);
        if (!svg) return;
        node = document.createElementNS(SVG_NS, 'text');
        node.setAttribute('data-text-node', nodeKey);
        svg.appendChild(node);
        prepareSvgNode(node, side);
      }
    }
    // apply text
    if (typeof state.text === 'string') setSvgNodeText(node, state.text);
    // apply position
    if (Number.isFinite(Number(state.x)) && Number.isFinite(Number(state.y))) {
      updateNodePosition(node, side, Number(state.x), Number(state.y));
    }
    // attributes
    try { if (state.fontSize) node.setAttribute('font-size', state.fontSize); } catch(e) {}
    try { if (state.letterSpacing !== undefined) node.setAttribute('letter-spacing', state.letterSpacing); } catch(e) {}
    try { if (state.fill) node.setAttribute('fill', state.fill); } catch(e) {}
    try { if (state.fontFamily) node.setAttribute('style', `font-family: '${state.fontFamily}', system-ui, sans-serif;`); } catch(e) {}
    // wrapper transform
    const wrapper = ensureTransformWrapper(node);
    if (wrapper) {
      const scale = Number(state.wrapperScale || 1);
      const rotate = Number(state.wrapperRotate || 0);
      applyWrapperTransform(wrapper, scale, rotate, node);
    }
    // sync sidebar input
    const nodeKeyActual = node.getAttribute('data-text-node') || nodeKey;
    syncPanelInputValue(nodeKeyActual, side, getSvgNodeValue(node));
    try { updatePreviewFromSvg(side); } catch (e) {}
  }

  function pushHistory(item) {
    // truncate future history if any
    if (historyIndex < history.length - 1) history.splice(historyIndex + 1);
    history.push(item);
    historyIndex = history.length - 1;
    updateUndoRedoButtons();
  }

  // helper: returns true if a given node/side currently has an active history capture
  function historyCaptureActive(node, side) {
    try {
      if (!node) return false;
      const key = node.getAttribute && node.getAttribute('data-text-node');
      if (!key) return false;
      // rely on a capture map if present, else inspect inlineEditor flags
      if (typeof window.__iw_history_capture === 'object' && window.__iw_history_capture) {
        return !!window.__iw_history_capture[`${key}::${side}`];
      }
    } catch (e) {}
    // fallback: if inlineEditorNode is the node and it's set, assume active
    try { if (inlineEditorNode === node) return true; } catch (e) {}
    return false;
  }

  function updateUndoRedoButtons() {
    const undoBtn = document.querySelector('.undo-btn');
    const redoBtn = document.querySelector('.redo-btn');
    if (undoBtn) undoBtn.disabled = historyIndex < 0;
    if (redoBtn) redoBtn.disabled = historyIndex >= history.length - 1;
  }

  function undo() {
    if (historyIndex < 0) return;
    const item = history[historyIndex];
    if (!item) return;
    applyNodeState(item.nodeKey, item.side, item.before);
    historyIndex -= 1;
    updateUndoRedoButtons();
  }

  function redo() {
    if (historyIndex >= history.length - 1) return;
    historyIndex += 1;
    const item = history[historyIndex];
    if (!item) return;
    applyNodeState(item.nodeKey, item.side, item.after);
    updateUndoRedoButtons();
  }

  function beginHistoryCapture(node, side) {
    if (!node) return;
    const key = node.getAttribute('data-text-node') || ensureTextNodeKey(node, side);
    pendingCapture = { nodeKey: key, side, before: readNodeState(node, side) };
  }

  function endHistoryCapture(node, side) {
    if (!pendingCapture) return;
    // locate node by key
    const key = pendingCapture.nodeKey;
    const n = svgTextCache[side]?.get(key);
    const after = n ? readNodeState(n, side) : null;
    // only push if there is a meaningful change
    const before = pendingCapture.before;
    if (after && JSON.stringify(before) !== JSON.stringify(after)) {
      pushHistory({ nodeKey: key, side, before, after });
    }
    pendingCapture = null;
  }
  // Enable image debug panel during troubleshooting to surface hrefs/load status
  const DEBUG_IMAGES = allowImageEditing;
  // debug panel element
  let __iw_debug_panel = null;

  function ensureDebugPanel() {
  if (!allowImageEditing || !DEBUG_IMAGES) return null;
    if (__iw_debug_panel) return __iw_debug_panel;
    try {
      __iw_debug_panel = document.createElement('div');
      __iw_debug_panel.className = 'editor-debug-panel';
      __iw_debug_panel.innerHTML = '<h4>Image debug</h4><pre id="__iw_debug_pre">(no diagnostics yet)</pre>';
      const canvasArea = document.querySelector('.canvas-area');
      if (canvasArea) canvasArea.appendChild(__iw_debug_panel);
      return __iw_debug_panel;
    } catch (e) { return null; }
  }

  function updateDebugPanel(obj) {
  if (!allowImageEditing || !DEBUG_IMAGES) return;
    try {
      const panel = ensureDebugPanel();
      if (!panel) return;
      const pre = panel.querySelector('#__iw_debug_pre');
      if (!pre) return;
      const o = Object.assign({}, obj || {});
      // stringify bbox safely
      if (o.bbox && typeof o.bbox === 'object') {
        o.bbox = { x: o.bbox.x, y: o.bbox.y, width: o.bbox.width, height: o.bbox.height };
      }
      pre.textContent = JSON.stringify(o, null, 2);
    } catch (e) { /* ignore */ }
  }

  function escapeAttr(value) {
    if (typeof CSS !== "undefined" && CSS.escape) {
      return CSS.escape(value);
    }
    return String(value).replace(/"/g, '\"').replace(/\]/g, "\\]");
  }

  function getSvgCoordinates(svg, clientX, clientY) {
    if (!svg) return { x: 0, y: 0 };
    const point = svg.createSVGPoint();
    point.x = clientX;
    point.y = clientY;
    try {
      const matrix = svg.getScreenCTM();
      if (!matrix) return { x: 0, y: 0 };
      const transformed = point.matrixTransform(matrix.inverse());
      return { x: transformed.x, y: transformed.y };
    } catch (err) {
      return { x: 0, y: 0 };
    }
  }

  function findTextFieldInput(nodeKey, side) {
    if (!textFieldsContainer || !nodeKey) return null;
    const safeKey = escapeAttr(nodeKey);
    return textFieldsContainer.querySelector(`input[data-text-node="${safeKey}"][data-card-side="${side}"]`);
  }

  // Initialize text editor panel
  function initializeTextEditorPanel() {
    const textEditorPanel = document.querySelector('.text-editor-panel');
    if (!textEditorPanel) return;

    // Set up new text field button
    const newTextBtn = textEditorPanel.querySelector('.new-text-btn');
    if (newTextBtn) {
      newTextBtn.addEventListener('click', () => {
        addNewTextField();
      });
    }

    // Set up existing text inputs for real-time syncing
    const textInputs = textEditorPanel.querySelectorAll('.text-input');
    textInputs.forEach(input => {
      setupTextInputSync(input);
    });
  }

  // Add new text field functionality
  function addNewTextField() {
    const textFieldsContainer = document.querySelector('.text-fields-container');
    if (!textFieldsContainer) return;

    // Create new text input wrapper
    const wrapper = document.createElement('div');
    wrapper.className = 'text-input-wrapper';

    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'text-input';
    input.value = 'New Text';
    input.placeholder = 'Enter text here...';
    input.dataset.textNode = `custom-${Date.now()}-${Math.random().toString(36).slice(2, 5)}`;
    input.dataset.cardSide = currentView || 'front';

    wrapper.appendChild(input);
    textFieldsContainer.appendChild(wrapper);

    // Set up syncing for the new input
    setupTextInputSync(input);

    // create delete button above the input for convenience (accessible)
    const delBtn = document.createElement('button');
    delBtn.type = 'button';
    delBtn.className = 'delete-text';
    delBtn.setAttribute('aria-label', 'Delete text field');
    delBtn.innerHTML = '<span class="sr-only">Delete text field</span><i class="fa-solid fa-trash-can" aria-hidden="true"></i>';
    delBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const key = input.dataset.textNode;
      const side = input.dataset.cardSide || currentView || 'front';
      if (key) removeSvgNode(key, side);
      wrapper.remove();
    });
    wrapper.appendChild(delBtn);

    // Create corresponding SVG node
    const side = currentView || 'front';
    const node = ensureSvgNodeForInput(input);
    if (node) {
      setSvgNodeText(node, input.value);
      // Position in center of canvas
      const svg = getSvgRoot(side);
      if (svg) {
        const metrics = parseViewBox(svg);
        const x = metrics.x + (metrics.width || 500) * 0.5;
        const y = metrics.y + (metrics.height || 700) * 0.5;
        updateNodePosition(node, side, x, y);
      }
      prepareSvgNode(node, side);
      try { updatePreviewFromSvg(side); } catch(e){}
    }

    // Focus the new input
    setTimeout(() => {
      input.focus();
      input.select();
    }, 10);
  }

  // Set up real-time syncing for a text input
  function setupTextInputSync(input) {
    if (!input) return;

    // Sync from input to SVG
    input.addEventListener('input', () => {
      const side = input.dataset.cardSide || currentView || 'front';
      const nodeKey = input.dataset.textNode;
      if (!nodeKey) return;

      const node = svgTextCache[side]?.get(nodeKey);
      if (node) {
        setSvgNodeText(node, input.value);
        try { updatePreviewFromSvg(side); } catch(e){}
        createBoundingBoxDebounced(node, side);
      }
    });

    // Sync from SVG to input (for canvas edits)
    const side = input.dataset.cardSide || currentView || 'front';
    const nodeKey = input.dataset.textNode;
    if (nodeKey && svgTextCache[side]?.has(nodeKey)) {
      const node = svgTextCache[side].get(nodeKey);
      if (node) {
        // Initial sync
        const currentValue = getSvgNodeValue(node);
        if (currentValue !== input.value) {
          input.value = currentValue;
        }
      }
    }
  }

  // Enhanced syncInputValue to handle panel inputs
  function syncPanelInputValue(nodeKey, side, value) {
    if (!nodeKey) return;
    // Update any panel inputs specifically in the text editor panel
    try {
      const selector = `.text-editor-panel .text-input[data-text-node="${CSS && CSS.escape ? CSS.escape(nodeKey) : nodeKey}"][data-card-side="${side}"]`;
      const panelInput = document.querySelector(selector);
      if (panelInput && panelInput.value !== value) {
        panelInput.value = value;
        panelInput.dispatchEvent(new Event('input', { bubbles: true }));
      }
    } catch (e) {}

    // Also update any other legacy inputs that might reference this node
    try {
      const allInputs = Array.from(document.querySelectorAll(`input[data-text-node="${nodeKey}"]`));
      allInputs.forEach(inp => {
        try {
          const cardSide = (inp.dataset.cardSide || inp.dataset.cardSide || inp.getAttribute('data-card-side') || inp.getAttribute('data-card-side')) || '';
          // only update if different value
          if ((inp.value || '') !== (value || '')) {
            inp.value = value || '';
            inp.dispatchEvent(new Event('input', { bubbles: true }));
          }
        } catch (err) {}
      });
    } catch (e) {}
  }

  // Populate the text fields panel with an input for every SVG <text> node
  function populateTextFieldsFromSvg() {
    const container = document.querySelector('.text-fields-container');
    if (!container) return;
    ['front', 'back'].forEach(side => {
      const svg = getSvgRoot(side);
      if (!svg) return;
      const nodes = Array.from(svg.querySelectorAll('text'));
      nodes.forEach(node => {
        try {
          // ensure node has a key
          const key = ensureTextNodeKey(node, side);
          // avoid duplicate inputs
          const existing = container.querySelector(`.text-input-wrapper[data-text-node="${key}"][data-card-side="${side}"]`);
          if (existing) return;

          const value = getSvgNodeValue(node) || '';
          const pos = computePercentPosition(node, side);

          const wrapper = document.createElement('div');
          wrapper.className = 'text-input-wrapper';
          wrapper.setAttribute('data-text-node', key);
          wrapper.setAttribute('data-card-side', side);

          const input = document.createElement('input');
          input.type = 'text';
          input.className = 'text-input';
          input.value = value;
          input.placeholder = '';
          input.setAttribute('data-text-node', key);
          input.setAttribute('data-card-side', side);
          input.setAttribute('data-top-percent', pos.top);
          input.setAttribute('data-left-percent', pos.left);
          // copy font metadata if present
          try { const fs = node.getAttribute('font-size') || node.dataset.fontSize; if (fs) input.dataset.fontSize = fs; } catch(e) {}
          try { const ls = node.getAttribute('letter-spacing') || node.dataset.letterSpacing; if (ls) input.dataset.letterSpacing = ls; } catch(e) {}
          try { const ff = extractFontFamily(node) || node.dataset.fontFamily; if (ff) { input.dataset.fontFamily = ff; input.style.fontFamily = ff; } } catch(e) {}

          wrapper.appendChild(input);
          // optional delete button for convenience (accessible)
          const del = document.createElement('button');
          del.type = 'button';
          del.className = 'delete-text';
          del.setAttribute('aria-label', 'Delete text field');
          // use Font Awesome trash icon with sr-only label for screen readers
          del.innerHTML = '<span class="sr-only">Delete text field</span><i class="fa-solid fa-trash-can" aria-hidden="true"></i>';
          wrapper.appendChild(del);

          container.appendChild(wrapper);

          // wire up sync and deletion
          setupTextInputSync(input);
          del.addEventListener('click', (e) => {
            e.preventDefault();
            // remove svg node and input
            removeSvgNode(key, side);
            wrapper.remove();
          });
        } catch (e) {}
      });
    });
  }

  function updateFieldPositionFromNode(node, side, x, y) {
    if (!node) return;
    const nodeKey = node.getAttribute("data-text-node") || "";
    if (!nodeKey) return;
    const input = findTextFieldInput(nodeKey, side);
    if (!input) return;
    const svg = getSvgRoot(side);
    if (!svg) return;
    const metrics = parseViewBox(svg);
    const leftPercent = metrics.width ? ((x - metrics.x) / metrics.width) * 100 : 0;
    const topPercent = metrics.height ? ((y - metrics.y) / metrics.height) * 100 : 0;
    input.dataset.leftPercent = leftPercent.toFixed(2);
    input.dataset.topPercent = topPercent.toFixed(2);
  }

  function updateNodePosition(node, side, x, y) {
    if (!node) return;
    node.setAttribute("x", x);
    node.dataset.originalX = x;
    node.setAttribute("y", y);
    node.dataset.originalY = y;
    const tspans = node.querySelectorAll("tspan");
    tspans.forEach((tspan, index) => {
      tspan.setAttribute("x", x);
      if (index === 0) {
        tspan.setAttribute("y", y);
        tspan.removeAttribute("dy");
      } else if (!tspan.hasAttribute("dy")) {
        tspan.setAttribute("dy", "1.2em");
      }
    });
    updateFieldPositionFromNode(node, side, x, y);
  }

  function getFieldSide(input) {
    return (input?.dataset?.cardSide || "front").toLowerCase();
  }

  function getTextNodeKey(input) {
    return (input?.dataset?.textNode || "").trim();
  }

  function getSvgRoot(side) {
    const card = side === "front" ? cardFront : cardBack;
    if (!card) return null;
    return card.querySelector("svg");
  }

  function ensureImageLayer(side) {
    if (!allowImageEditing) return null;
    if (imageLayers[side]) {
      return imageLayers[side];
    }

    const card = side === 'front' ? cardFront : cardBack;
    if (!card) return null;
    try {
      card.classList.remove('background-fallback');
      card.style.removeProperty('background-image');
      if (!card.style.backgroundColor) card.style.backgroundColor = '#ffffff';
    } catch (e) {}

    const svg = getSvgRoot(side);
    if (!svg) return null;

    function getImageHref(img) {
      if (!img) return '';
      try {
        return img.getAttribute('href')
          || (img.href && img.href.baseVal)
          || img.getAttributeNS(XLINK_NS, 'href')
          || '';
      } catch (e) {
        return '';
      }
    }

    function pickSvgImageCandidate() {
      const allImages = Array.from(svg.querySelectorAll('image')).filter((img) => {
        if (!img || !img.tagName) return false;
        const tag = img.tagName.toLowerCase();
        if (tag !== 'image') return false;
        if (img.closest && img.closest('defs, symbol, clipPath, mask, pattern')) return false;
        return true;
      });
      if (!allImages.length) return null;

      const explicitEditable = allImages.find((img) => {
        const modeTag = (img.dataset && img.dataset.editableImage) || '';
        return modeTag === side;
      });
      if (explicitEditable) return explicitEditable;

      const flagged = allImages.find((img) => {
        if (!img.dataset) return false;
        const changeable = (img.dataset.changeable || '').toLowerCase();
        const uploaded = img.hasAttribute('data-uploaded');
        return changeable === 'image' || uploaded;
      });
      if (flagged) return flagged;

      let largest = allImages[0];
      let largestArea = 0;
      allImages.forEach((img) => {
        let area = 0;
        try {
          const bbox = img.getBBox();
          area = (bbox && Number.isFinite(bbox.width) && Number.isFinite(bbox.height)) ? bbox.width * bbox.height : 0;
        } catch (e) {
          const w = Number(img.getAttribute('width')) || 0;
          const h = Number(img.getAttribute('height')) || 0;
          area = w * h;
        }
        if (area > largestArea) {
          largest = img;
          largestArea = area;
        }
      });
      return largest || null;
    }

    // Prefer an existing SVG <image> instead of creating a duplicate layer
    const allImages = Array.from(svg.querySelectorAll('image')).filter((img) => {
      if (!img || !img.tagName) return false;
      const tag = img.tagName.toLowerCase();
      if (tag !== 'image') return false;
      if (img.closest && img.closest('defs, symbol, clipPath, mask, pattern')) return false;
      return true;
    });

    let layer = svg.querySelector(`image[data-editable-image="${side}"]`) || pickSvgImageCandidate();
    if (!layer) {
      layer = document.createElementNS(SVG_NS, 'image');
      layer.setAttribute('data-editable-image', side);
      layer.setAttribute('x', '0');
      layer.setAttribute('y', '0');
      layer.setAttribute('width', '100%');
      layer.setAttribute('height', '100%');
      layer.setAttribute('preserveAspectRatio', 'xMidYMid slice');
      layer.style.display = 'none';

      // Insert as the first child so all text nodes are above
      if (svg.firstChild) {
        svg.insertBefore(layer, svg.firstChild);
      } else {
        svg.appendChild(layer);
      }
    } else if (!layer.getAttribute('data-editable-image')) {
      layer.setAttribute('data-editable-image', side);
    }

    // Remove other SVG image elements that point to the same source to avoid duplicate renders
    try {
      const layerHref = getImageHref(layer);
      if (layerHref) {
        allImages.forEach((img) => {
          if (img === layer) return;
          const href = getImageHref(img);
          if (!href) return;
          if (href === layerHref) {
            try { img.remove(); } catch (err) {}
          }
        });
      }
    } catch (e) {}

    // Remove any additional SVG layers previously marked as editable for this side
    try {
      const duplicates = Array.from(svg.querySelectorAll(`image[data-editable-image="${side}"]`)).filter((img) => img !== layer);
      duplicates.forEach((img) => {
        try { img.remove(); } catch (err) {}
      });
    } catch (e) {}

    imageLayers[side] = layer;
    try {
      const href = layer.getAttribute && (layer.getAttribute('href') || layer.getAttributeNS(XLINK_NS, 'href') || (layer.href && layer.href.baseVal));
      console.debug('[ensureImageLayer] side:', side, 'layer:', layer, 'href:', href);
      updateDebugPanel({ event: 'ensureImageLayer', side, href });
    } catch (e) {}
    return layer;
  }

  function ensureFallbackImage(side) {
    if (!allowImageEditing) return null;
    if (!shouldDisplayFallbackImages) {
      // Remove any stale fallback element so only the SVG layer is visible
      try {
        const existing = imageFallbackLayers[side];
        if (existing && existing.parentNode) existing.parentNode.removeChild(existing);
      } catch (e) {}
      imageFallbackLayers[side] = null;
      return null;
    }
    if (imageFallbackLayers[side]) {
      return imageFallbackLayers[side];
    }

    const card = side === 'front' ? cardFront : cardBack;
    if (!card) return null;

    let fallback = card.querySelector(`img[data-image-layer="${side}"]`);
    if (!fallback) {
      fallback = document.createElement('img');
      fallback.setAttribute('data-image-layer', side);
      fallback.setAttribute('alt', '');
      fallback.setAttribute('aria-hidden', 'true');
      fallback.decoding = 'async';
      fallback.loading = 'lazy';
      fallback.style.display = 'none';
      fallback.style.visibility = 'hidden';

      const svg = getSvgRoot(side);
      if (svg && svg.parentNode === card) {
        card.insertBefore(fallback, svg);
      } else {
        card.appendChild(fallback);
      }
    }

    imageFallbackLayers[side] = fallback;
    return fallback;
  }

  function setImageElementSource(element, src) {
    if (!allowImageEditing) return;
    if (!element) return;
    try {
      const isImg = element.tagName && element.tagName.toLowerCase() === 'img';
      console.debug('[setImageElementSource] element:', element, 'src:', src, 'isImg:', isImg);

      if (isImg) {
        const isCardFallbackLayer = element.hasAttribute && element.hasAttribute('data-image-layer');
        element.dataset.loaded = 'pending';
        element.onload = function () {
          element.dataset.loaded = 'true';
          console.debug('[setImageElementSource] <img> load', element.currentSrc, element.naturalWidth, element.naturalHeight);
          updateDebugPanel({
            type: 'img',
            src: element.currentSrc,
            naturalWidth: element.naturalWidth,
            naturalHeight: element.naturalHeight,
          });
          const card = element.closest && element.closest('.card');
          if (card) {
            card.classList.remove('background-fallback');
            card.style.removeProperty('background-image');
          }
        };
        element.onerror = function (ev) {
          element.dataset.loaded = 'error';
          console.warn('[setImageElementSource] <img> load error', src, ev);
          updateDebugPanel({ type: 'img', src, error: 'load failed' });
          const card = element.closest && element.closest('.card');
          if (card && src) {
            try {
              card.classList.add('background-fallback');
              card.style.backgroundImage = `url("${src}")`;
            } catch (err) {}
          }
        };

        if (src) {
          element.src = src;
          if (isCardFallbackLayer && !shouldDisplayFallbackImages) {
            element.style.display = 'none';
            element.style.visibility = 'hidden';
          } else {
            try { element.style.removeProperty('display'); } catch (e) {}
            element.style.visibility = 'visible';
          }
          try { element.style.removeProperty('filter'); } catch(e){}
          try { element.style.removeProperty('image-rendering'); } catch(e){}
        } else {
          try { element.removeAttribute('src'); } catch (e) {}
          element.style.display = 'none';
          element.style.visibility = 'hidden';
          try { element.style.removeProperty('filter'); } catch(e){}
          try { element.style.removeProperty('image-rendering'); } catch(e){}
        }

        updateDebugPanel({ type: 'img', src });
        return;
      }

      // fallback for legacy SVG <image>
      try { element.removeEventListener && element.removeEventListener('load', element.__iw_onload); } catch(e) {}
      try { element.removeEventListener && element.removeEventListener('error', element.__iw_onerror); } catch(e) {}

      if (src) {
        try { element.setAttribute('href', src); } catch(e) {}
        try { element.setAttributeNS(null, 'href', src); } catch(e) {}
        try { element.setAttributeNS(XLINK_NS, 'xlink:href', src); } catch(e) {}
        try { element.setAttribute('xlink:href', src); } catch(e) {}

        // Even before load events fire, clear any background fallback so the
        // template doesn't show a duplicated image (one static background and
        // one draggable SVG layer).
        const card = element.closest && element.closest('.card');
        if (card) {
          card.classList.remove('background-fallback');
          card.style.removeProperty('background-image');
        }
        const sideKey = element.getAttribute && element.getAttribute('data-editable-image');
        if (sideKey) {
          const fallbackLayer = imageFallbackLayers[sideKey];
          if (fallbackLayer && !shouldDisplayFallbackImages) {
            fallbackLayer.style.display = 'none';
            fallbackLayer.style.visibility = 'hidden';
          }
        }

        element.__iw_onload = function () {
          console.debug('[setImageElementSource] svg image load', src);
          try { element.style.removeProperty('display'); element.style.visibility = 'visible'; } catch(e){}
          try { element.style.removeProperty('filter'); } catch(e){}
          try { element.style.removeProperty('image-rendering'); } catch(e){}
          const card = element.closest && element.closest('.card');
          if (card) {
            card.classList.remove('background-fallback');
            card.style.removeProperty('background-image');
          }
          const sideKey = element.getAttribute && element.getAttribute('data-editable-image');
          if (sideKey) {
            const fallbackLayer = imageFallbackLayers[sideKey];
            if (fallbackLayer && !shouldDisplayFallbackImages) {
              fallbackLayer.style.display = 'none';
              fallbackLayer.style.visibility = 'hidden';
            }
          }
          updateDebugPanel({
            type: 'svg-image',
            href: src,
            status: 'loaded'
          });
        };
        element.__iw_onerror = function (ev) {
          console.warn('[setImageElementSource] svg image load error', src, ev);
          const card = element.closest && element.closest('.card');
          if (card && src) {
            try {
              card.classList.add('background-fallback');
              card.style.backgroundImage = `url("${src}")`;
              try { card.style.removeProperty('filter'); } catch(e){}
              try { card.style.removeProperty('image-rendering'); } catch(e){}
            } catch (err) {}
          }
          const sideKey = element.getAttribute && element.getAttribute('data-editable-image');
          if (sideKey) {
            const fallbackLayer = imageFallbackLayers[sideKey];
            if (fallbackLayer) {
              try { fallbackLayer.style.removeProperty('display'); } catch (e) {}
              fallbackLayer.style.visibility = 'visible';
            }
          }
          updateDebugPanel({ type: 'svg-image', href: src, error: 'load failed' });
        };
        try { element.addEventListener && element.addEventListener('load', element.__iw_onload); } catch(e) {}
        try { element.addEventListener && element.addEventListener('error', element.__iw_onerror); } catch(e) {}

        try { element.style.removeProperty('display'); } catch(e) {}
        element.style.visibility = 'visible';
      } else {
        try { element.removeAttribute('href'); } catch(e) {}
        try { element.removeAttributeNS(XLINK_NS, 'xlink:href'); } catch(e) {}
        try { element.setAttribute('xlink:href', ''); } catch(e) {}
        element.style.display = 'none';
        element.style.visibility = 'hidden';
      }

      try {
        const hrefAttr = element.getAttribute('href') || (element.href && element.href.baseVal) || element.getAttributeNS(XLINK_NS, 'href');
        let bbox = null;
        try { bbox = element.getBBox && element.getBBox(); } catch (e) {}
        updateDebugPanel({ type: 'svg-image', href: hrefAttr, bbox });
      } catch (e) {}
    } catch (err) {
      console.error('setImageElementSource error', err);
    }
  }

  function updatePreview(side, src) {
    if (!allowImageEditing) return;
    const preview = imagePreviews[side];
    if (!preview) return;
    // Always use the provided src if available, else fallback
    const fallback = imageState[side]?.defaultSrc || preview.dataset.defaultSrc || preview.src;
    if (src) {
      preview.src = src;
      preview.style.filter = 'none';
    } else {
      preview.src = fallback || "";
      preview.style.filter = 'none';
    }
  }

  function applyImage(side, src, { skipPreview = false } = {}) {
    if (!allowImageEditing) return;
    if (!imageState[side]) return;

    const layer = ensureImageLayer(side);
    if (!layer) return;

    imageState[side].currentSrc = src || "";

  const displaySrc = imageState[side].currentSrc || imageState[side].defaultSrc || "";
  console.debug('[applyImage] side:', side, 'displaySrc:', displaySrc);
  updateDebugPanel({ event: 'applyImage', side, displaySrc });

  // helper: convert local filesystem-like paths into root-relative web paths when possible
  function normalizeToWebPath(u) {
    if (!u || typeof u !== 'string') return u;
  u = u.replace(/\\/g, '/');
    if (/^https?:\/\//i.test(u)) {
      // If an absolute URL points to the same host and contains a templates path,
      // convert it to a root-relative storage/templates path so we don't request
      // `/templates/...` which can 404 when files live under `/storage/templates/`.
      try {
        const tmp = new URL(u, window.location.origin);
        const p = (tmp.pathname || '') + (tmp.search || '') + (tmp.hash || '');
        if (p.startsWith('/templates/')) {
          return '/storage/templates/' + p.substring('/templates/'.length);
        }
        const idx = p.indexOf('/storage/templates/');
        if (idx !== -1) return p.substring(idx);
        // return path portion for same-origin absolute URLs, otherwise fall back to original
        if (tmp.origin === window.location.origin) return p;
        return u;
      } catch (e) {
        return u;
      }
    }
    if (u.startsWith('/templates/')) {
      const base = u.substring('/templates/'.length);
      return '/storage/templates/' + base;
    }
    if (u.startsWith('/')) return u;
    // map project paths to root-relative web paths
    const m = u.match(/.*\/public\/(.*)/i);
    if (m && m[1]) return '/' + m[1];
    // prefer explicit storage/templates if present in the string
    const n = u.match(/.*(storage\/templates\/.*)/i);
    if (n && n[1]) return '/' + n[1];
    // fallback: if something looks like templates/<file>, prefer storage/templates first
    const p = u.match(/.*templates\/(.+)$/i);
    if (p && p[1]) return '/storage/templates/' + p[1];
    return u;
  }

  const normalizedDisplaySrc = normalizeToWebPath(displaySrc);

  // revoke previous blob if any (always safe to clear before creating a new one)
  try { if (imageBlobUrls[side]) { URL.revokeObjectURL(imageBlobUrls[side]); imageBlobUrls[side] = null; } } catch(e){}

  // helper: convert data: URLs to Blob
  function dataURLToBlob(dataURL) {
    // data:[<mediatype>][;base64],<data>
    const parts = dataURL.split(',');
    if (!parts || parts.length < 2) return null;
    const meta = parts[0];
    const data = parts.slice(1).join(',');
    const isBase64 = /;base64/i.test(meta);
    let byteString;
    try {
      byteString = isBase64 ? atob(data) : decodeURIComponent(data);
    } catch (e) {
      try { byteString = atob(data); } catch (err) { byteString = null; }
    }
    if (byteString == null) return null;
    // build array
    const ia = new Uint8Array(byteString.length);
    for (let i = 0; i < byteString.length; i++) ia[i] = byteString.charCodeAt(i);
    const mimeMatch = meta.match(/data:([^;]+)[;]?/i);
    const mime = mimeMatch ? mimeMatch[1] : 'application/octet-stream';
    return new Blob([ia], { type: mime });
  }

  // If the source is raw SVG text, a data URL containing SVG, or an SVG file URL,
  // convert to a blob URL so the SVG renders reliably inside an <image> element.
  const isRawSvgText = typeof displaySrc === 'string' && displaySrc.trim().startsWith('<svg');
  const isDataUrl = typeof normalizedDisplaySrc === 'string' && normalizedDisplaySrc.trim().startsWith('data:');
  const isSvgUrl = typeof normalizedDisplaySrc === 'string' && /\.svg(\?|$)/i.test(normalizedDisplaySrc);

  if (isRawSvgText) {
    try {
      const blob = new Blob([displaySrc], { type: 'image/svg+xml' });
      const blobUrl = URL.createObjectURL(blob);
      imageBlobUrls[side] = blobUrl;
      setImageElementSource(layer, blobUrl);
      const fallbackLayer = shouldDisplayFallbackImages ? ensureFallbackImage(side) : null;
      if (fallbackLayer) setImageElementSource(fallbackLayer, blobUrl);
      updateDebugPanel({ event: 'applyImage', side, inlined: true });
    } catch (e) {
      console.warn('[applyImage] failed to inline raw svg, falling back to direct src', e);
      setImageElementSource(layer, displaySrc);
      const fallbackLayer = shouldDisplayFallbackImages ? ensureFallbackImage(side) : null;
      if (fallbackLayer) setImageElementSource(fallbackLayer, displaySrc);
    }
  } else if (isDataUrl) {
    // try to convert SVG data URLs into blobs
    try {
      const blob = dataURLToBlob(normalizedDisplaySrc);
      if (blob) {
        // if the mime indicates svg or decoded content looks like svg, use blob
        const mime = blob.type || '';
        if (/svg/i.test(mime)) {
          const blobUrl = URL.createObjectURL(blob);
          imageBlobUrls[side] = blobUrl;
          setImageElementSource(layer, blobUrl);
          const fallbackLayer = shouldDisplayFallbackImages ? ensureFallbackImage(side) : null;
          if (fallbackLayer) setImageElementSource(fallbackLayer, blobUrl);
          updateDebugPanel({ event: 'applyImage', side, dataUrl: true });
        } else {
          // not an svg data URL; set directly (e.g., PNG/JPEG data URLs)
          setImageElementSource(layer, normalizedDisplaySrc);
          const fallbackLayer = shouldDisplayFallbackImages ? ensureFallbackImage(side) : null;
          if (fallbackLayer) setImageElementSource(fallbackLayer, normalizedDisplaySrc);
        }
      } else {
        setImageElementSource(layer, normalizedDisplaySrc);
        const fallbackLayer = shouldDisplayFallbackImages ? ensureFallbackImage(side) : null;
        if (fallbackLayer) setImageElementSource(fallbackLayer, normalizedDisplaySrc);
      }
    } catch (e) {
      console.warn('[applyImage] data url handling failed', e);
      setImageElementSource(layer, normalizedDisplaySrc);
      const fallbackLayer = shouldDisplayFallbackImages ? ensureFallbackImage(side) : null;
      if (fallbackLayer) setImageElementSource(fallbackLayer, normalizedDisplaySrc);
    }
  } else if (isSvgUrl) {
    // remote or same-origin SVG URL: fetch and inline as blob
    if (normalizedDisplaySrc) {
      fetch(normalizedDisplaySrc, { credentials: 'same-origin' }).then(res => {
        if (!res.ok) throw new Error('fetch failed ' + res.status + ' for ' + normalizedDisplaySrc);
        return res.text();
      }).then(svgText => {
        try {
          const blob = new Blob([svgText], { type: 'image/svg+xml' });
          const blobUrl = URL.createObjectURL(blob);
          imageBlobUrls[side] = blobUrl;
          setImageElementSource(layer, blobUrl);
          const fallbackLayer = shouldDisplayFallbackImages ? ensureFallbackImage(side) : null;
          if (fallbackLayer) setImageElementSource(fallbackLayer, blobUrl);
          updateDebugPanel({ event: 'applyImage', side, fetched: true, blobUrl });
        } catch (e) {
          console.warn('[applyImage] failed to inline svg, falling back to direct href', e);
          setImageElementSource(layer, normalizedDisplaySrc);
          const fallbackLayer = shouldDisplayFallbackImages ? ensureFallbackImage(side) : null;
          if (fallbackLayer) setImageElementSource(fallbackLayer, normalizedDisplaySrc);
        }
      }).catch(err => {
        console.warn('[applyImage] fetch svg failed, using direct src', err);
        setImageElementSource(layer, normalizedDisplaySrc || displaySrc);
        const fallbackLayer = shouldDisplayFallbackImages ? ensureFallbackImage(side) : null;
        if (fallbackLayer) setImageElementSource(fallbackLayer, normalizedDisplaySrc || displaySrc);
      });
    }
  } else {
    // non-SVG images (PNG/JPEG/WEBP etc.) — set directly on svg <image> and fallback <img>
    setImageElementSource(layer, normalizedDisplaySrc || displaySrc);
    const fallbackLayer = shouldDisplayFallbackImages ? ensureFallbackImage(side) : null;
    if (fallbackLayer) {
      setImageElementSource(fallbackLayer, normalizedDisplaySrc || displaySrc);
    }
  }

    

    const card = side === "front" ? cardFront : cardBack;
    if (card) {
      card.dataset.currentImage = displaySrc;
      if (!displaySrc) {
        card.classList.remove('background-fallback');
        card.style.removeProperty('background-image');
      }
    }

    if (!skipPreview) {
      updatePreview(side, displaySrc);
    } else if (imagePreviews[side] && !imagePreviews[side].getAttribute("src")) {
      updatePreview(side, displaySrc);
    }
  }

  // one-time debug scan to show what SVG <image> hrefs are present after init
  if (allowImageEditing) {
    try {
      ['front','back'].forEach(s => {
        const svg = getSvgRoot(s);
        if (!svg) return;
        const img = svg.querySelector('image[data-editable-image="'+s+'"]');
        if (!img) return;
        const h = img.getAttribute('href') || img.getAttributeNS(XLINK_NS, 'href') || (img.href && img.href.baseVal) || null;
        console.debug('[init-scan] side:', s, 'svg-image-href:', h, 'element:', img);
        updateDebugPanel({ event: 'init-scan', side: s, href: h });
      });
    } catch (e) {}
  }

  function resetImage(side) {
    if (!allowImageEditing || !imageState[side]) return;
    imageState[side].currentSrc = "";
    applyImage(side, "");
    const input = document.querySelector(`[data-image-input="${side}"]`);
    if (input) {
      input.value = "";
    }
  }

  function registerTextMetaSubmitHandlers() {
    document.querySelectorAll('.next-form').forEach(form => {
      if (form.dataset.textMetaBound === 'true') return;
      form.dataset.textMetaBound = 'true';

      form.addEventListener('submit', () => {
        form.querySelectorAll('input[name^="text_fields_meta["]').forEach(el => el.remove());

        document.querySelectorAll('input[data-text-node]').forEach(input => {
          const node = input.dataset.textNode || input.getAttribute('data-text-node');
          const safe = node || '';
          const meta = {
            font_size: input.dataset.fontSize || input.getAttribute('data-font-size') || '',
            font_family: input.dataset.fontFamily || '',
            color: input.dataset.color || ''
          };

          Object.keys(meta).forEach(key => {
            const name = `text_fields_meta[${safe}][${key}]`;
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = name;
            hidden.value = meta[key];
            form.appendChild(hidden);
          });
          // also persist wrapper transform metadata if present
          try {
            const nodeKey = safe;
            const node = document.querySelector(`input[data-text-node="${escapeAttr(nodeKey)}"]`);
            // attempt to find SVG node by data-text-node
            const svgNode = document.querySelector(`[data-text-node="${escapeAttr(nodeKey)}"]`);
            if (svgNode) {
              const wrapper = (svgNode.parentNode && svgNode.parentNode.dataset && svgNode.parentNode.dataset.iwWrapper === 'true') ? svgNode.parentNode : null;
              if (wrapper) {
                const s = wrapper.dataset.iwScale || '';
                const r = wrapper.dataset.iwRotate || '';
                const hs = document.createElement('input');
                hs.type = 'hidden';
                hs.name = `text_fields_meta[${safe}][wrapper_scale]`;
                hs.value = s;
                form.appendChild(hs);
                const hr = document.createElement('input');
                hr.type = 'hidden';
                hr.name = `text_fields_meta[${safe}][wrapper_rotate]`;
                hr.value = r;
                form.appendChild(hr);
              }
            }
          } catch (e) {}
        });
      });
    });
  }

  function showPanel(panelName) {
    panels.forEach(panel => {
      panel.classList.toggle("active", panel.dataset.panel === panelName);
    });
  }

  function parseViewBox(svg) {
    const vb = svg?.getAttribute("viewBox");
    if (!vb) {
      const width = svg?.clientWidth || 0;
      const height = svg?.clientHeight || 0;
      return { x: 0, y: 0, width, height };
    }
    const parts = vb.trim().split(/\s+/).map(Number);
    if (parts.length === 4 && parts.every(n => Number.isFinite(n))) {
      return { x: parts[0], y: parts[1], width: parts[2], height: parts[3] };
    }
    return { x: 0, y: 0, width: svg.clientWidth || 0, height: svg.clientHeight || 0 };
  }

  function applyPositionToText(node, input, side) {
    if (!node || !input) return;
    const svg = getSvgRoot(side);
    if (!svg) return;
    const metrics = parseViewBox(svg);
    const topPercent = Number.parseFloat(input.dataset.topPercent ?? "");
    const leftPercent = Number.parseFloat(input.dataset.leftPercent ?? "");
    const align = (input.dataset.align || "center").toLowerCase();

    if (Number.isFinite(leftPercent)) {
      const x = metrics.x + (metrics.width * leftPercent) / 100;
      node.setAttribute("x", x);
      node.dataset.originalX = x;
    }

    if (Number.isFinite(topPercent)) {
      const y = metrics.y + (metrics.height * topPercent) / 100;
      node.setAttribute("y", y);
      node.dataset.originalY = y;
    }

    const anchors = {
      left: "start",
      center: "middle",
      right: "end",
    };
    node.setAttribute("text-anchor", anchors[align] || "middle");
    node.setAttribute("dominant-baseline", "middle");

    if (input.dataset.fontSize) {
      node.setAttribute("font-size", input.dataset.fontSize);
    }
    if (input.dataset.letterSpacing) {
      node.setAttribute("letter-spacing", input.dataset.letterSpacing);
    }
  }

  function setSvgNodeText(node, value) {
    if (!node) return;
    const lines = String(value ?? "").split(/\r?\n/);
    while (node.firstChild) node.removeChild(node.firstChild);

    const originalX = node.dataset.originalX || node.getAttribute("x") || null;
    const originalY = node.dataset.originalY || node.getAttribute("y") || null;

    lines.forEach((line, idx) => {
      const tspan = document.createElementNS(SVG_NS, "tspan");
      if (originalX) tspan.setAttribute("x", originalX);
      if (idx === 0 && originalY) {
        tspan.setAttribute("y", originalY);
      } else if (idx > 0) {
        tspan.setAttribute("dy", "1.2em");
      }
      tspan.textContent = line || "\u00A0";
      node.appendChild(tspan);
    });
    // After updating text content, refresh bounding box if the node is in the DOM (debounced)
    try {
      if (node.ownerSVGElement) {
        const svg = node.ownerSVGElement;
        const card = svg.closest && svg.closest('.card');
        const side = card && card.dataset && card.dataset.card ? card.dataset.card : (svg.id === 'cardFront' ? 'front' : 'back');
        try { createBoundingBoxDebounced(node, side); } catch (e) { /* ignore */ }
      }
    } catch (e) {}
  }

  function getSvgNodeValue(node) {
    if (!node) return "";
    const tspans = node.querySelectorAll("tspan");
    if (tspans.length === 0) {
      return (node.textContent ?? "").replace(/\u00A0/g, "");
    }
    return Array.from(tspans)
      .map(tspan => (tspan.textContent ?? "").replace(/\u00A0/g, ""))
      .join("\n");
  }

  function ensureTextFieldForNode(node, side) {
    if (!textFieldsContainer || !node) return null;
    const nodeKey = ensureTextNodeKey(node, side);
    if (!nodeKey) return null;
    const value = getSvgNodeValue(node);
    const { left, top } = computePercentPosition(node, side);
    const align = mapAnchorToAlign(node.getAttribute("text-anchor"));
    const fontSize = node.getAttribute("font-size") || "";
    const letterSpacing = node.getAttribute("letter-spacing") || "";
    const fill = node.getAttribute("fill") || "";
    const fontFamily = extractFontFamily(node);

    const existingInput = findTextFieldInput(nodeKey, side);
    if (existingInput) {
      if (!existingInput.value) existingInput.value = value;
      if (!existingInput.placeholder && value) existingInput.placeholder = value;
  if (!existingInput.dataset.cardSide) existingInput.dataset.cardSide = side;
      if (!existingInput.dataset.leftPercent) existingInput.dataset.leftPercent = left;
      if (!existingInput.dataset.topPercent) existingInput.dataset.topPercent = top;
      if (!existingInput.dataset.align) existingInput.dataset.align = align;
  if (fontSize && !existingInput.dataset.fontSize) existingInput.dataset.fontSize = fontSize;
  if (letterSpacing && !existingInput.dataset.letterSpacing) existingInput.dataset.letterSpacing = letterSpacing;
  if (fill && !existingInput.dataset.color) existingInput.dataset.color = fill;
  if (fill && !existingInput.style.color) existingInput.style.color = fill;
      if (fontFamily && !existingInput.dataset.fontFamily) {
        existingInput.dataset.fontFamily = fontFamily;
        existingInput.style.fontFamily = "'" + fontFamily + "', system-ui, sans-serif";
      }
      if (!existingInput.name) existingInput.name = `text_fields[${nodeKey}]`;
      existingInput.dataset.defaultValue = existingInput.dataset.defaultValue || value;
      const wrapper = existingInput.closest(".text-field");
      if (wrapper) {
        wrapper.dataset.cardSide = side;
        wrapper.dataset.textNode = nodeKey;
      }
      return existingInput;
    }

    const wrapper = document.createElement("div");
    wrapper.className = "text-field";
    wrapper.dataset.cardSide = side;
    wrapper.dataset.textNode = nodeKey;

    const input = document.createElement("input");
    input.type = "text";
    input.name = `text_fields[${nodeKey}]`;
    input.value = value;
    if (value) input.placeholder = value;
    input.dataset.cardSide = side;
    input.dataset.textNode = nodeKey;
    input.dataset.leftPercent = left;
    input.dataset.topPercent = top;
    input.dataset.align = align;
    input.dataset.defaultValue = value;
    if (fontSize) input.dataset.fontSize = fontSize;
    if (letterSpacing) input.dataset.letterSpacing = letterSpacing;
    if (fill) {
      input.dataset.color = fill;
      input.style.color = fill;
    }
    if (fontFamily) {
      input.dataset.fontFamily = fontFamily;
      input.style.fontFamily = "'" + fontFamily + "', system-ui, sans-serif";
    }

  const delBtn = document.createElement("button");
  delBtn.type = "button";
  delBtn.className = "delete-text";
  delBtn.setAttribute("aria-label", "Remove text field");
  // use Font Awesome trash icon
  delBtn.innerHTML = '<i class="fa-solid fa-trash-can"></i>';

    wrapper.appendChild(input);
    wrapper.appendChild(delBtn);
    textFieldsContainer.appendChild(wrapper);

    return input;
  }

  // Ensure a transform wrapper <g> exists around the text node so we can rotate/scale
  function ensureTransformWrapper(node) {
    if (!node || !node.parentNode) return null;
    // if already wrapped by an IW wrapper group, return it
    const parent = node.parentNode;
    if (parent && parent.tagName && parent.tagName.toLowerCase() === 'g' && parent.dataset && parent.dataset.iwWrapper === 'true') {
      return parent;
    }
    const svg = node.ownerSVGElement || getSvgRoot('front') || getSvgRoot('back');
    if (!svg) return null;
    const wrapper = document.createElementNS(SVG_NS, 'g');
    wrapper.dataset.iwWrapper = 'true';
    // insert wrapper in place of node
    node.parentNode.insertBefore(wrapper, node);
    wrapper.appendChild(node);
    // init transform metadata
    wrapper.dataset.iwScale = wrapper.dataset.iwScale || '1';
    wrapper.dataset.iwRotate = wrapper.dataset.iwRotate || '0';
    // apply current transform (no-op initially)
    applyWrapperTransform(wrapper, Number(wrapper.dataset.iwScale), Number(wrapper.dataset.iwRotate), node);
    return wrapper;
  }

  function applyWrapperTransform(wrapper, scale, rotateDeg, nodeForBBox) {
    try {
      scale = Number(scale) || 1;
      rotateDeg = Number(rotateDeg) || 0;
      // compute center in local SVG coordinates using node bbox
      const node = nodeForBBox || (wrapper && wrapper.querySelector && wrapper.querySelector('text'));
      let cx = 0, cy = 0;
      try {
        const bb = node.getBBox();
        cx = bb.x + bb.width / 2;
        cy = bb.y + bb.height / 2;
      } catch (e) {
        // fallback to 0,0
      }
      // construct transform: translate(cx,cy) rotate(rotateDeg) scale(scale) translate(-cx,-cy)
      const t = `translate(${cx} ${cy}) rotate(${rotateDeg}) scale(${scale}) translate(${-cx} ${-cy})`;
      wrapper.setAttribute('transform', t);
      wrapper.dataset.iwScale = String(scale);
      wrapper.dataset.iwRotate = String(rotateDeg);
    } catch (e) {
      // ignore
    }
  }

  function closeInlineEditor(commit = true) {
    if (!inlineEditor) return;
    const editor = inlineEditor;
    const node = inlineEditorNode;
    const side = inlineEditorSide;
    const nodeKey = node?.getAttribute("data-text-node") || "";
    if (node) {
      node.classList.remove("svg-text-focus");
    }
    if (nodeKey && side) {
      syncFieldHighlight(nodeKey, side, false);
    }

    editor.remove();
    inlineEditor = null;
    inlineEditorNode = null;
    inlineEditorSide = null;
    const finalValue = commit ? editor.value : inlineEditorOriginalValue;
    if (node && nodeKey && typeof finalValue === 'string') {
      setSvgNodeText(node, finalValue);
      syncPanelInputValue(nodeKey, side, finalValue);
      try { updatePreviewFromSvg(side); } catch (e) {}
    }
    inlineEditorOriginalValue = "";
    scheduleToolbarHide();
    // finish history capture for inline edits (if any)
    try { endHistoryCapture(node, side); } catch (e) {}
  }

  function startInlineEditor(node, side) {
    if (!node) return;
    const nodeKey = node.getAttribute("data-text-node") || "";
    if (!nodeKey) return;

    if (currentView !== side) {
      setActiveView(side);
    }

    closeInlineEditor(true);
    inlineEditorOriginalValue = getSvgNodeValue(node);
    // begin history capture for inline edit
    beginHistoryCapture(node, side);

    const svg = getSvgRoot(side);
    const host = canvasArea || canvasWrapper;
    if (!svg || !host) return;

    // Get the node's bounding rectangle, accounting for any SVG transformations
    const nodeRect = node.getBoundingClientRect();
    const hostRect = host.getBoundingClientRect();

    // Account for canvas zoom scaling
    const scaleFactor = zoom || 1;
    const scaledLeft = (nodeRect.left - hostRect.left) / scaleFactor;
    const scaledTop = (nodeRect.top - hostRect.top) / scaleFactor;

    const editor = document.createElement('textarea');
    editor.className = 'inline-text-editor';
    editor.setAttribute('aria-label', 'Edit canvas text');
    editor.value = inlineEditorOriginalValue;
    editor.style.position = 'absolute';
    editor.style.left = `${scaledLeft}px`;
    editor.style.top = `${scaledTop}px`;

    // initial sizing: at least as big as node bbox, otherwise readable minimums
    const minWidth = Math.max(nodeRect.width / scaleFactor, 120);
    const minHeight = Math.max(nodeRect.height / scaleFactor, 28);
    editor.style.width = `${minWidth}px`;
    editor.style.height = `${minHeight}px`;

    // visual: minimal by default, focus shows strong ring; caret color should match SVG fill
    editor.style.padding = '6px 8px';
    editor.style.border = '2px solid #3B82F6';
    editor.style.borderRadius = '6px';
    editor.style.background = 'rgba(255,255,255,0.98)';
    editor.style.boxShadow = '0 4px 12px rgba(15, 23, 42, 0.15)';
    editor.style.zIndex = '35';

    // Apply zoom scaling to the editor itself
    editor.style.transform = `scale(${scaleFactor})`;
    editor.style.transformOrigin = 'top left';

    // Adjust positioning to account for scaling
    editor.style.left = `${scaledLeft}px`;
    editor.style.top = `${scaledTop}px`;

    const rawFontSize = node.getAttribute('font-size') || window.getComputedStyle(node).fontSize || '18px';
    const normalizedFontSize = /px$/i.test(rawFontSize) ? rawFontSize : `${rawFontSize}px`;
    editor.style.fontSize = normalizedFontSize;
    editor.style.lineHeight = '1.2';
    editor.style.fontFamily = node.dataset.fontFamily ? `'${node.dataset.fontFamily}', system-ui, sans-serif` : window.getComputedStyle(node).fontFamily;
    // caret color — use SVG fill when available
    const svgFill = node.getAttribute('fill') || node.style.fill || '#1f2933';
    editor.style.color = svgFill;
    editor.style.caretColor = svgFill;
    editor.style.resize = 'none';

    // autosize helper: resize textarea height to fit content
    function autosize() {
      try {
        editor.style.height = '1px';
        const scrollH = Math.max(editor.scrollHeight, minHeight);
        editor.style.height = (scrollH / scaleFactor) + 'px';
      } catch (e) {}
    }
    // run once to ensure initial fit
    setTimeout(autosize, 0);

    host.appendChild(editor);

    inlineEditor = editor;
    inlineEditorNode = node;
    inlineEditorSide = side;

    node.classList.add('svg-text-focus');
    syncFieldHighlight(nodeKey, side, true);
    showTextToolbar(node);
    // populate toolbar with node state
    populateToolbarForNode(node, side);

    // sync styles from SVG node to textarea so typing visually matches
    try {
      const ls = node.getAttribute('letter-spacing') || node.dataset.letterSpacing || '';
      if (ls) editor.style.letterSpacing = typeof ls === 'string' && ls.endsWith('px') ? ls : `${ls}px`;
    } catch (e) {}
    try {
      const anchor = (node.getAttribute('text-anchor') || 'middle').toLowerCase();
      // map SVG text-anchor to CSS text-align
      editor.style.textAlign = anchor === 'start' ? 'left' : anchor === 'end' ? 'right' : 'center';
    } catch (e) {}
    try {
      const fs = node.getAttribute('font-size') || window.getComputedStyle(node).fontSize || '';
      if (fs) editor.style.fontSize = /px$/i.test(fs) ? fs : `${fs}px`;
    } catch (e) {}

    // composition state for IME (prevent Enter from committing during composition)
    let composing = false;
    // typing debounce to group rapid input into a single history entry
    let typingTimer = null;
    const TYPING_DEBOUNCE_MS = 600;
    editor.addEventListener('compositionstart', () => { composing = true; });
    editor.addEventListener('compositionend', (ev) => {
      composing = false;
      // after IME composition finishes, ensure SVG reflects the committed text
      setSvgNodeText(node, editor.value);
      syncPanelInputValue(nodeKey, side, editor.value);
      try { updatePreviewFromSvg(side); } catch (e) {}
      autosize();
    });

    editor.addEventListener('input', () => {
      setSvgNodeText(node, editor.value);
      syncPanelInputValue(nodeKey, side, editor.value);
      try { updatePreviewFromSvg(side); } catch (e) {}
      autosize();
      // manage history capture: start on first keystroke if not already capturing
      try {
        if (!historyCaptureActive(node, side)) {
          beginHistoryCapture(node, side);
        }
      } catch (e) {}
      // debounce endHistoryCapture so typing bursts create a single history item
      if (typingTimer) clearTimeout(typingTimer);
      typingTimer = setTimeout(() => {
        try { endHistoryCapture(node, side); } catch (e) {}
        typingTimer = null;
      }, TYPING_DEBOUNCE_MS);
    });

    editor.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        closeInlineEditor(false);
      } else if (event.key === 'Enter' && !event.shiftKey && !event.ctrlKey) {
        if (composing) {
          // during IME composition, allow Enter to finish composition without closing editor
          return;
        }
        event.preventDefault();
        closeInlineEditor(true);
      }
    });

    editor.addEventListener('blur', () => {
      // if composition is active, delay closing until compositionend
      if (composing) return;
      closeInlineEditor(true);
    });

    requestAnimationFrame(() => {
      try {
        editor.focus({ preventScroll: true });
      } catch (err) {
        editor.focus();
      }
      editor.select?.();
    });
  }  function prepareSvgNode(node, side) {
    if (!node || !side) return;
    const nodeKey = ensureTextNodeKey(node, side);
    if (!nodeKey) return;

    // Remove existing event listeners to prevent duplicates
    if (node._iwEventListeners) {
      node._iwEventListeners.forEach(({ event, handler }) => {
        try { node.removeEventListener(event, handler); } catch (e) {}
      });
      node._iwEventListeners = [];
    } else {
      node._iwEventListeners = [];
    }

    // Always prepare the node, don't skip based on dataset.prepared
    svgTextCache[side].set(nodeKey, node);

    if (!node.dataset.originalX && node.hasAttribute("x")) {
      node.dataset.originalX = node.getAttribute("x");
    }
    if (!node.dataset.originalY && node.hasAttribute("y")) {
      node.dataset.originalY = node.getAttribute("y");
    }

    // Ensure proper attributes for interaction
    node.setAttribute("contenteditable", "true");
    node.setAttribute("role", "textbox");
    node.setAttribute("spellcheck", "false");
    node.setAttribute("tabindex", "0");
    node.style.cursor = "pointer"; // Make it clear it's clickable
    node.style.userSelect = "none"; // Prevent text selection

    if (!node.hasAttribute("aria-label")) {
      try {
        const nodeKey = node.getAttribute('data-text-node') || node.getAttribute('id') || '';
        const label = nodeKey ? `Edit text: ${nodeKey}` : (node.textContent && node.textContent.trim()) || 'Edit canvas text';
        node.setAttribute('aria-label', label);
      } catch (e) { node.setAttribute('aria-label', 'Edit canvas text'); }
    }

    // Focus/blur handlers
    const focusHandler = () => {
      if (currentView !== side) setActiveView(side);
      node.classList.add("svg-text-focus");
      syncFieldHighlight(nodeKey, side, true);
      showTextToolbar(node);
      populateToolbarForNode(node, side);
      selectedElementNode = node;
      selectedElementSide = side;
      createBoundingBox(node, side);
    };
    const blurHandler = () => {
      node.classList.remove("svg-text-focus");
      syncFieldHighlight(nodeKey, side, false);
    };

    node.addEventListener("focus", focusHandler);
    node.addEventListener("blur", blurHandler);
    node._iwEventListeners.push({ event: "focus", handler: focusHandler });
    node._iwEventListeners.push({ event: "blur", handler: blurHandler });

    // Keydown handler for arrow keys and editing
    const keydownHandler = (event) => {
      const moveKeys = ["ArrowUp", "ArrowDown", "ArrowLeft", "ArrowRight"];
      if (moveKeys.includes(event.key)) {
        event.preventDefault();
        const step = event.shiftKey ? 5 : 1;
        let x = Number.parseFloat(node.getAttribute("x") || node.dataset.originalX || "0");
        let y = Number.parseFloat(node.getAttribute("y") || node.dataset.originalY || "0");
        if (event.key === "ArrowUp") y -= step;
        if (event.key === "ArrowDown") y += step;
        if (event.key === "ArrowLeft") x -= step;
        if (event.key === "ArrowRight") x += step;
        updateNodePosition(node, side, x, y);
        try { updatePreviewFromSvg(side); } catch (e) {}
        return;
      }
      if (event.key === "Enter" && !event.shiftKey) {
        event.preventDefault();
        startInlineEditor(node, side);
        return;
      } else if (event.key === "Backspace" || event.key === "Delete") {
        event.preventDefault();
        startInlineEditor(node, side);
        return;
      } else if (!event.ctrlKey && !event.metaKey && !event.altKey && event.key.length === 1) {
        event.preventDefault();
        startInlineEditor(node, side);
        return;
      }
    };

    node.addEventListener("keydown", keydownHandler);
    node._iwEventListeners.push({ event: "keydown", handler: keydownHandler });

    // Input handler
    const inputHandler = () => handleSvgNodeInput(node, side);
    node.addEventListener("input", inputHandler);
    node._iwEventListeners.push({ event: "input", handler: inputHandler });

    // Pointer events for dragging
    const pointerDownHandler = (event) => handleNodePointerDown(event, node, side);
    node.addEventListener('pointerdown', pointerDownHandler);
    node._iwEventListeners.push({ event: 'pointerdown', handler: pointerDownHandler });

    node.addEventListener('pointermove', handleNodePointerMove);
    node.addEventListener('pointerup', (event) => handleNodePointerUp(event, false));
    node.addEventListener('pointercancel', (event) => handleNodePointerUp(event, true));
    node.addEventListener('lostpointercapture', () => {
      if (dragState.active && dragState.node === node) {
        handleNodePointerUp({ pointerId: dragState.pointerId }, true);
      }
    });

    // Double-click handler for inline editing
    const dblclickHandler = (event) => {
      event.preventDefault();
      event.stopPropagation();
      startInlineEditor(node, side);
    };
    node.addEventListener('dblclick', dblclickHandler);
    node._iwEventListeners.push({ event: 'dblclick', handler: dblclickHandler });

    // Click handler as fallback for single click editing
    const clickHandler = (event) => {
      // Only handle single clicks if not already handled by double-click
      if (event.detail === 1) {
        // Delay to allow for potential double-click
        setTimeout(() => {
          if (!inlineEditor || inlineEditorNode !== node) {
            startInlineEditor(node, side);
          }
        }, 200);
      }
    };
    node.addEventListener('click', clickHandler);
    node._iwEventListeners.push({ event: 'click', handler: clickHandler });

    // Focus handlers for resize/rotate handles
    const focusHandlerForHandles = () => placeResizeHandle(node, side);
    const blurHandlerForHandles = () => hideResizeHandle();
    node.addEventListener('focus', focusHandlerForHandles);
    node.addEventListener('blur', blurHandlerForHandles);
    node._iwEventListeners.push({ event: 'focus', handler: focusHandlerForHandles });
    node._iwEventListeners.push({ event: 'blur', handler: blurHandlerForHandles });

    // Ensure there's a wrapper group for transforms
    try { ensureTransformWrapper(node); } catch (e) {}

    node.dataset.prepared = side;
  }

  function cacheSvgNodes(cardElement, side) {
    if (!cardElement) return;
    const nodes = cardElement.querySelectorAll("text");
    nodes.forEach(node => {
      if (node.closest && node.closest("defs, symbol, clipPath, mask, pattern")) return;
      if (node.hasAttribute && (node.hasAttribute("data-non-editable") || node.getAttribute("aria-hidden") === "true")) return;
      const nodeKey = ensureTextNodeKey(node, side);
      if (!nodeKey) return;
      ensureTextFieldForNode(node, side);
      prepareSvgNode(node, side);
    });
  }

  function syncFieldHighlight(nodeKey, side, highlight) {
    if (!textFieldsContainer) return;
    const safeKey = escapeAttr(nodeKey);
    const input = textFieldsContainer.querySelector(`input[data-text-node="${safeKey}"][data-card-side="${side}"]`);
    if (!input) return;
    const wrapper = input.closest(".text-field");
    if (!wrapper) return;
    wrapper.classList.toggle("is-active", highlight);
  }

  function handleSvgNodeInput(node, side) {
    const nodeKey = node.getAttribute("data-text-node") || "";
    const value = getSvgNodeValue(node);

    syncPanelInputValue(nodeKey, side, value);
    const svg = getSvgRoot(side);
    const x = Number.parseFloat(node.getAttribute("x") || node.dataset.originalX || "0");
    const y = Number.parseFloat(node.getAttribute("y") || node.dataset.originalY || "0");
    updateFieldPositionFromNode(node, side, x, y);
    // update the thumbnail preview for this side to reflect the changed SVG text
    try { updatePreviewFromSvg(side); } catch (e) { console.debug('preview-from-svg failed', e); }
  // refresh bounding box so it matches new text dimensions (debounced)
  try { createBoundingBoxDebounced(node, side); } catch (e) { /* ignore */ }
  }

  function ensureSvgNodeForInput(input) {
    const side = getFieldSide(input);
    const nodeKey = getTextNodeKey(input);
    if (!nodeKey) return null;

    const existing = svgTextCache[side].get(nodeKey);
    if (existing) return existing;

    const svg = getSvgRoot(side);
    if (!svg) return null;

    const text = document.createElementNS(SVG_NS, "text");
    text.setAttribute("data-text-node", nodeKey);
    // position and basic attributes
    applyPositionToText(text, input, side);
    // apply sensible visible defaults so text is shown when created
    try {
      const fontSize = input.dataset.fontSize || input.getAttribute('data-font-size') || '16';
      const letterSpacing = input.dataset.letterSpacing || input.getAttribute('data-letter-spacing') || '';
      const fill = input.dataset.color || input.style.color || '#1f2933';
      const family = input.dataset.fontFamily || input.style.fontFamily || '';
      const align = (input.dataset.align || 'center').toLowerCase();
      const anchor = align === 'left' ? 'start' : align === 'right' ? 'end' : 'middle';
      try { text.setAttribute('font-size', String(fontSize)); } catch(e){}
      if (letterSpacing) try { text.setAttribute('letter-spacing', String(letterSpacing)); } catch(e){}
      try { text.setAttribute('fill', fill); } catch(e){}
      if (family) try { text.setAttribute('style', `font-family: '${family}', system-ui, sans-serif;`); } catch(e){}
      try { text.setAttribute('text-anchor', anchor); } catch(e){}
      try { text.setAttribute('dominant-baseline', 'middle'); } catch(e){}
    } catch (e) {}
    setSvgNodeText(text, input.value || "");
    svg.appendChild(text);
    prepareSvgNode(text, side);
    return text;
  }

  function updateSvgNodeFromInput(input, value) {
    const side = getFieldSide(input);
    const nodeKey = getTextNodeKey(input);
    if (!nodeKey) return;
    const node = ensureSvgNodeForInput(input);
    if (!node) return;
  setSvgNodeText(node, value);
    if (inlineEditorNode === node && inlineEditor) {
      inlineEditor.value = value;
      inlineEditor.dispatchEvent(new Event("input"));
    }
    // update the thumbnail preview after programmatic update
    try { updatePreviewFromSvg(side); } catch (e) { console.debug('preview-from-svg failed', e); }
  }

  function handleNodePointerDown(event, node, side) {
    if (!node || event.button !== 0) return;
    if (event.detail > 1) return;
    const svg = getSvgRoot(side);
    if (!svg) return;
    closeInlineEditor(true);
    event.preventDefault();
    event.stopPropagation();

    const coords = getSvgCoordinates(svg, event.clientX, event.clientY);
    const currentX = Number.parseFloat(node.getAttribute('x') || node.dataset.originalX || '0');
    const currentY = Number.parseFloat(node.getAttribute('y') || node.dataset.originalY || '0');

    dragState.active = true;
    dragState.node = node;
    dragState.side = side;
    dragState.svg = svg;
    dragState.pointerId = event.pointerId;
    dragState.offsetX = currentX - coords.x;
    dragState.offsetY = currentY - coords.y;

    try { node.setPointerCapture(event.pointerId); } catch (err) {}
    node.classList.add('svg-text-dragging');
  // begin history capture for this node movement
  beginHistoryCapture(node, side);
    const nodeKey = node.getAttribute('data-text-node');
    if (nodeKey) syncFieldHighlight(nodeKey, side, true);
    showTextToolbar(node);
    try { node.focus({ preventScroll: true }); } catch (err) { try { node.focus(); } catch (e) {} }
  }

  function handleNodePointerMove(event) {
    if (!dragState.active || event.pointerId !== dragState.pointerId) return;
    const { svg, node, side, offsetX, offsetY } = dragState;
    if (!svg || !node) return;
    event.preventDefault();
    const coords = getSvgCoordinates(svg, event.clientX, event.clientY);
    const newX = coords.x + offsetX;
    const newY = coords.y + offsetY;
    updateNodePosition(node, side, newX, newY);
    // if a resize handle is visible and attached to this node, move it
    if (resizeHandle && resizeState.node === node) {
      placeResizeHandle(node, side);
    }
  }

  function resetDragState() {
    dragState.active = false;
    dragState.node = null;
    dragState.side = null;
    dragState.pointerId = null;
    dragState.svg = null;
    dragState.offsetX = 0;
    dragState.offsetY = 0;
  }

  function handleNodePointerUp(event, canceled = false) {
    if (!dragState.active) return;
    if (event && dragState.pointerId !== event.pointerId) return;
    const node = dragState.node;
    const side = dragState.side;
    if (event && node) {
      try { node.releasePointerCapture(event.pointerId); } catch (err) {}
    }
    if (node) {
      node.classList.remove('svg-text-dragging');
    }
    resetDragState();
    if (!canceled && side) {
      try { updatePreviewFromSvg(side); } catch (e) {}
      // finish history capture for this node
      try { endHistoryCapture(node, side); } catch (e) {}
      updateUndoRedoButtons();
    }
  }

  function createResizeHandle() {
    if (resizeHandle) return resizeHandle;
    const host = canvasArea || canvasWrapper;
    if (!host) return null;
    const el = document.createElement('div');
    el.className = 'svg-resize-handle';
    el.setAttribute('aria-hidden', 'true');
  // visual icon is rendered inside the SVG bounding box; keep overlay handle visually minimal
    host.appendChild(el);
    // pointer events for resizing
    el.addEventListener('pointerdown', (ev) => {
      ev.preventDefault();
      ev.stopPropagation();
      if (ev.button !== 0) return;
      const node = resizeState.node;
      if (!node) return;
      // ensure wrapper and read current scale
      const wrapper = ensureTransformWrapper(node);
      resizeState.active = true;
      resizeState.pointerId = ev.pointerId;
      resizeState.startY = ev.clientY;
      const startScale = Number((wrapper && wrapper.dataset && wrapper.dataset.iwScale) || 1) || 1;
      resizeState.startScale = startScale;
      // capture current letter-spacing if present
      const ls = parseFloat(node.getAttribute('letter-spacing') || node.dataset.letterSpacing || '0') || 0;
      resizeState.startLetterSpacing = ls;
      try { el.setPointerCapture(ev.pointerId); } catch(e){}
      // begin history capture for resize
      beginHistoryCapture(resizeState.node, resizeState.side);
    });
    el.addEventListener('pointermove', (ev) => {
      if (!resizeState.active || ev.pointerId !== resizeState.pointerId) return;
      ev.preventDefault();
      const dy = resizeState.startY - ev.clientY;
      // compute scale factor from movement; small drag = subtle scale
      const factor = 1 + (dy / 200); // adjust sensitivity
      const newScale = Math.max(0.2, resizeState.startScale * factor);
      if (resizeState.node) {
        const wrapper = ensureTransformWrapper(resizeState.node);
        if (wrapper) {
          // apply transform scale and keep existing rotation
          const rotate = Number(wrapper.dataset.iwRotate || 0) || 0;
          applyWrapperTransform(wrapper, newScale, rotate, resizeState.node);
          // adjust letter-spacing proportionally to preserve visual width
          try {
            const baseLS = resizeState.startLetterSpacing || 0;
            const scaledLS = baseLS / newScale; // inverse to preserve width
            if (Number.isFinite(scaledLS)) {
              resizeState.node.setAttribute('letter-spacing', String(scaledLS));
              try { resizeState.node.dataset.letterSpacing = String(scaledLS); } catch(e){}
            }
          } catch (e) {}
        }
        const nodeKey = resizeState.node.getAttribute('data-text-node') || '';
        if (nodeKey && resizeState.side) syncPanelInputValue(nodeKey, resizeState.side, getSvgNodeValue(resizeState.node));
        try { updatePreviewFromSvg(resizeState.side); } catch(e){}
      }
    });

    el.addEventListener('pointerup', (ev) => {
      if (!resizeState.active || ev.pointerId !== resizeState.pointerId) return;
      resizeState.active = false;
      try { el.releasePointerCapture(ev.pointerId); } catch(e){}
      resizeState.pointerId = null;
      // finish history capture
      try { endHistoryCapture(resizeState.node, resizeState.side); } catch (e) {}
      updateUndoRedoButtons();
    });

    el.addEventListener('pointercancel', (ev) => {
      resizeState.active = false;
      try { el.releasePointerCapture(ev.pointerId); } catch(e){}
      resizeState.pointerId = null;
    });

    resizeHandle = el;
    return el;
  }

  function placeResizeHandle(node, side) {
    const host = canvasArea || canvasWrapper;
    const handle = createResizeHandle();
    if (!node || !handle || !host) return;
    const svg = getSvgRoot(side);
    if (!svg) return;
    // compute bounding box in screen coords
    const bbox = node.getBoundingClientRect();
    const hostRect = host.getBoundingClientRect();
    // position handle at bottom-right of node relative to host
    const left = bbox.right - hostRect.left - 6; // offset to center handle
    const top = bbox.bottom - hostRect.top - 6;
    handle.style.left = `${left}px`;
    handle.style.top = `${top}px`;
    handle.classList.add('is-visible');
    resizeState.node = node;
    resizeState.side = side;
    // also place rotate handle above the node
    placeRotateHandle(node, side, hostRect);
  }

  function hideResizeHandle() {
    if (!resizeHandle) return;
    resizeHandle.classList.remove('is-visible');
    resizeState.node = null;
    resizeState.side = null;
    hideRotateHandle();
  }

  function createRotateHandle() {
    if (rotateHandle) return rotateHandle;
    const host = canvasArea || canvasWrapper;
    if (!host) return null;
    const el = document.createElement('div');
    el.className = 'svg-rotate-handle';
    el.setAttribute('aria-hidden', 'true');
  // visual icon is rendered inside the SVG bounding box; keep overlay handle visually minimal
    host.appendChild(el);

    el.addEventListener('pointerdown', (ev) => {
      ev.preventDefault();
      ev.stopPropagation();
      if (ev.button !== 0) return;
      const node = rotateState.node;
      if (!node) return;
      const wrapper = ensureTransformWrapper(node);
      rotateState.active = true;
      rotateState.pointerId = ev.pointerId;
      rotateState.startAngle = Number((wrapper && wrapper.dataset && wrapper.dataset.iwRotate) || 0) || 0;
      // compute pointer angle relative to center
      const svg = getSvgRoot(rotateState.side);
      const pt = getSvgCoordinates(svg, ev.clientX, ev.clientY);
      const center = computeWrapperCenter(wrapper, node);
      rotateState.startPointerAngle = Math.atan2(pt.y - center.cy, pt.x - center.cx) * (180 / Math.PI);
      try { el.setPointerCapture(ev.pointerId); } catch(e){}
      // begin history capture for rotate
      beginHistoryCapture(rotateState.node, rotateState.side);
    });

    el.addEventListener('pointermove', (ev) => {
      if (!rotateState.active || ev.pointerId !== rotateState.pointerId) return;
      ev.preventDefault();
      const svg = getSvgRoot(rotateState.side);
      const pt = getSvgCoordinates(svg, ev.clientX, ev.clientY);
      const wrapper = ensureTransformWrapper(rotateState.node);
      const center = computeWrapperCenter(wrapper, rotateState.node);
      const pointerAngle = Math.atan2(pt.y - center.cy, pt.x - center.cx) * (180 / Math.PI);
      const delta = pointerAngle - rotateState.startPointerAngle;
      const newAngle = rotateState.startAngle + delta;
      applyWrapperTransform(wrapper, Number(wrapper.dataset.iwScale || 1) || 1, newAngle, rotateState.node);
      try { updatePreviewFromSvg(rotateState.side); } catch(e){}
    });

    el.addEventListener('pointerup', (ev) => {
      if (!rotateState.active || ev.pointerId !== rotateState.pointerId) return;
      rotateState.active = false;
      try { el.releasePointerCapture(ev.pointerId); } catch(e){}
      rotateState.pointerId = null;
      try { endHistoryCapture(rotateState.node, rotateState.side); } catch (e) {}
      updateUndoRedoButtons();
      // remove rotating visual state
      try {
        const wrapper = rotateState.node && rotateState.node.parentNode && rotateState.node.parentNode.dataset && rotateState.node.parentNode.dataset.iwWrapper === 'true' ? rotateState.node.parentNode : null;
        if (wrapper && wrapper.classList) wrapper.classList.remove('is-rotating');
      } catch(e) {}
    });

    el.addEventListener('pointercancel', (ev) => {
      rotateState.active = false;
      try { el.releasePointerCapture(ev.pointerId); } catch(e){}
      rotateState.pointerId = null;
      try {
        const wrapper = rotateState.node && rotateState.node.parentNode && rotateState.node.parentNode.dataset && rotateState.node.parentNode.dataset.iwWrapper === 'true' ? rotateState.node.parentNode : null;
        if (wrapper && wrapper.classList) wrapper.classList.remove('is-rotating');
      } catch(e) {}
    });

    rotateHandle = el;
    return el;
  }

  function computeWrapperCenter(wrapper, node) {
    // compute center in SVG coordinates from node bbox
    let cx = 0, cy = 0;
    try {
      const bb = node.getBBox();
      cx = bb.x + bb.width / 2;
      cy = bb.y + bb.height / 2;
    } catch (e) {}
    return { cx, cy, // also return screen coords fallback
      // convert to screen coords when needed by transforming point via getScreenCTM
      getScreen: function(svg) {
        try {
          const pt = svg.createSVGPoint();
          pt.x = cx; pt.y = cy;
          const m = node.ownerSVGElement.getScreenCTM();
          const p = pt.matrixTransform(m);
          return { x: p.x, y: p.y };
        } catch (err) { return { x: 0, y: 0 }; }
      },
      cy
    };
  }

  function placeRotateHandle(node, side, hostRect) {
    const host = canvasArea || canvasWrapper;
    const handle = createRotateHandle();
    if (!node || !handle || !host) return;
    const svg = getSvgRoot(side);
    if (!svg) return;
    const bbox = node.getBoundingClientRect();
    hostRect = hostRect || host.getBoundingClientRect();
    // place rotation handle above top-center of node
    const left = bbox.left - hostRect.left + bbox.width / 2 - 8;
    const top = bbox.top - hostRect.top - 18; // above
    handle.style.left = `${left}px`;
    handle.style.top = `${top}px`;
    handle.classList.add('is-visible');
    rotateState.node = node;
    rotateState.side = side;
  }

  function hideRotateHandle() {
    if (!rotateHandle) return;
    rotateHandle.classList.remove('is-visible');
    rotateState.node = null;
    rotateState.side = null;
  }

  function createBoundingBox(node, side) {
    const svg = getSvgRoot(side);
    if (!svg || !node) return;
    const tagName = node.tagName ? node.tagName.toLowerCase() : '';
    if (tagName === 'image') return;
    if (node.hasAttribute && node.hasAttribute('data-uploaded')) return;
    // remove existing bounding box and handles for this specific node to avoid duplicates
    try {
      const nodeKey = node.getAttribute && node.getAttribute('data-text-node') || '';
      if (nodeKey) {
        // remove any existing elements previously tagged for this node
        const existing = Array.from(svg.querySelectorAll(`[data-iw-target-node="${nodeKey}"]`));
        existing.forEach(el => { try { el.remove(); } catch(e){} });
      } else {
        // fallback: remove all before creating if nodeKey not present
        removeBoundingBox(side);
      }
    } catch (e) {
      try { removeBoundingBox(side); } catch (err) {}
    }
    // get local bbox
    let bbox = { x:0,y:0,width:0,height:0 };
    try { bbox = node.getBBox(); } catch(e) {}

    const padding = 8; // ensure handles sit outside text
    const rx = bbox.x - padding;
    const ry = bbox.y - padding;
    const rw = Math.max(0, bbox.width + padding * 2);
    const rh = Math.max(0, bbox.height + padding * 2);

    // create rect in the same container as the node so it inherits transforms
    const rect = document.createElementNS(SVG_NS, 'rect');
    rect.classList.add('text-bounding-box');
  // tag rect with the nodeKey so duplicates can be detected/removed
  try { const nk = node.getAttribute && node.getAttribute('data-text-node') || ''; if (nk) rect.setAttribute('data-iw-target-node', nk); } catch(e){}
    rect.setAttribute('x', rx);
    rect.setAttribute('y', ry);
    rect.setAttribute('width', rw);
    rect.setAttribute('height', rh);
    rect.setAttribute('fill', 'none');
    rect.setAttribute('stroke', '#3B82F6');
    rect.setAttribute('stroke-width', '2');
    rect.setAttribute('pointer-events', 'none');

    // append rect to wrapper if present so it follows transforms
    let targetContainer = svg;
    try { const wrapper = (node.parentNode && node.parentNode.dataset && node.parentNode.dataset.iwWrapper === 'true') ? node.parentNode : null; if (wrapper) targetContainer = wrapper; } catch(e) { targetContainer = svg; }
    targetContainer.appendChild(rect);

    // ensure a top-level handles group exists so handles draw above everything
    let handleLayer = null;
    try {
      handleLayer = svg.querySelector('g[data-iw-handles-layer]');
      if (!handleLayer) {
        handleLayer = document.createElementNS(SVG_NS, 'g');
        handleLayer.setAttribute('data-iw-handles-layer', 'true');
        svg.appendChild(handleLayer);
      }
    } catch (e) { handleLayer = svg; }

    // helper to create a handle group
    function makeHandle(x, y, opts = {}) {
      const { type = 'resize', shape = 'circle', w = 10, h = 10, cursor = 'pointer', rx = 0, ry = 0 } = opts;
      const g = document.createElementNS(SVG_NS, 'g');
      g.classList.add('bounding-box-handle');
      g.setAttribute('data-handle-type', type);
  // tag handle group with target node key
  try { if (node && node.getAttribute) { const nk = node.getAttribute('data-text-node') || ''; if (nk) g.setAttribute('data-iw-target-node', nk); } } catch(e){}
      try { g.dataset.targetNodeKey = node.getAttribute && node.getAttribute('data-text-node') || ''; } catch(e) {}
      try { g.style.cursor = cursor; } catch(e) {}

      if (shape === 'circle') {
        const c = document.createElementNS(SVG_NS, 'circle');
        c.setAttribute('cx', x);
        c.setAttribute('cy', y);
        c.setAttribute('r', String(w/2));
        c.setAttribute('fill', '#fff');
        c.setAttribute('stroke', '#3B82F6');
        c.setAttribute('stroke-width', '2');
        c.setAttribute('pointer-events', 'none');
        g.appendChild(c);
      } else {
        const rectEl = document.createElementNS(SVG_NS, 'rect');
        rectEl.setAttribute('x', String(x - w/2));
        rectEl.setAttribute('y', String(y - h/2));
        rectEl.setAttribute('width', String(w));
        rectEl.setAttribute('height', String(h));
        if (rx) rectEl.setAttribute('rx', String(rx));
        if (ry) rectEl.setAttribute('ry', String(ry));
        rectEl.setAttribute('fill', '#fff');
        rectEl.setAttribute('stroke', '#3B82F6');
        rectEl.setAttribute('stroke-width', '2');
        rectEl.setAttribute('pointer-events', 'none');
        g.appendChild(rectEl);
      }

      // hit area
      const hit = document.createElementNS(SVG_NS, 'circle');
      hit.setAttribute('cx', x);
      hit.setAttribute('cy', y);
      hit.setAttribute('r', String(Math.max(12, w)));
      hit.setAttribute('fill', 'transparent');
      hit.setAttribute('pointer-events', 'visible');
      hit.setAttribute('class', 'bounding-box-hit');
      hit.setAttribute('role', 'button');
      hit.setAttribute('aria-label', type.charAt(0).toUpperCase() + type.slice(1));
      hit.setAttribute('tabindex', '-1');
      g.appendChild(hit);

      handleLayer.appendChild(g);
      return g;
    }

    // corner centers in local coords (rect coordinates)
    const corners = [
      { x: rx, y: ry, cursor: 'nw-resize' },
      { x: rx + rw, y: ry, cursor: 'ne-resize' },
      { x: rx + rw, y: ry + rh, cursor: 'se-resize' },
      { x: rx, y: ry + rh, cursor: 'sw-resize' }
    ];

    // side centers in local coords
    const sides = [
      { x: rx + rw/2, y: ry, cursor: 'n-resize', w: 16, h: 10 },
      { x: rx + rw, y: ry + rh/2, cursor: 'e-resize', w: 10, h: 16 },
      { x: rx + rw/2, y: ry + rh, cursor: 's-resize', w: 16, h: 10 },
      { x: rx, y: ry + rh/2, cursor: 'w-resize', w: 10, h: 16 }
    ];

    // create corner circular handles
    corners.forEach((c) => makeHandle(c.x, c.y, { type: 'resize', shape: 'circle', w: 12, cursor: c.cursor }));
    // create side rectangular handles
    sides.forEach((s) => makeHandle(s.x, s.y, { type: 'resize-side', shape: 'rect', w: s.w, h: s.h, rx:2, ry:2, cursor: s.cursor }));

    // create a single rotate control at top-right outside the box
    const rotateOffset = 18;
    const rotX = rx + rw + rotateOffset;
    const rotY = ry - rotateOffset;
    const rotG = document.createElementNS(SVG_NS, 'g');
    rotG.classList.add('bounding-box-handle');
    rotG.setAttribute('data-handle-type', 'rotate');
  try { const nk = node.getAttribute && node.getAttribute('data-text-node') || ''; if (nk) rotG.setAttribute('data-iw-target-node', nk); } catch(e){}
    try { rotG.dataset.targetNodeKey = node.getAttribute && node.getAttribute('data-text-node') || ''; } catch(e) {}
    try { rotG.style.cursor = 'grab'; } catch(e) {}

    const rotCircle = document.createElementNS(SVG_NS, 'circle');
    rotCircle.setAttribute('cx', String(rotX));
    rotCircle.setAttribute('cy', String(rotY));
    rotCircle.setAttribute('r', '10');
    rotCircle.setAttribute('fill', '#fff');
    rotCircle.setAttribute('stroke', '#3B82F6');
    rotCircle.setAttribute('stroke-width', '2');
    rotCircle.setAttribute('pointer-events', 'none');
    rotG.appendChild(rotCircle);

    const rotHit = document.createElementNS(SVG_NS, 'circle');
    rotHit.setAttribute('cx', String(rotX));
    rotHit.setAttribute('cy', String(rotY));
    rotHit.setAttribute('r', '16');
    rotHit.setAttribute('fill', 'transparent');
    rotHit.setAttribute('pointer-events', 'visible');
    rotHit.setAttribute('class', 'bounding-box-hit');
    rotHit.setAttribute('role', 'button');
    rotHit.setAttribute('aria-label', 'Rotate');
    rotHit.setAttribute('tabindex', '-1');
    rotG.appendChild(rotHit);

    // add rotate icon (simple path) centered inside
    const icon = document.createElementNS(SVG_NS, 'path');
    icon.setAttribute('d', 'M12 6 A6 6 0 1 0 8 4');
    icon.setAttribute('stroke', '#3B82F6');
    icon.setAttribute('stroke-width', '1.6');
    icon.setAttribute('fill', 'none');
    icon.setAttribute('transform', `translate(${rotX-6} ${rotY-6})`);
    rotG.appendChild(icon);

    handleLayer.appendChild(rotG);
  }

  // debounce helper to avoid excessive bbox recalculations during typing/dragging
  function debounce(fn, wait) {
    let t = null;
    return function debounced(...args) {
      if (t) clearTimeout(t);
      t = setTimeout(() => {
        t = null;
        try { fn.apply(this, args); } catch (e) {}
      }, wait);
    };
  }

  const createBoundingBoxDebounced = debounce(function(node, side) {
    try { createBoundingBox(node, side); } catch (e) {}
  }, 80);

  function removeBoundingBox(side) {
    const svg = getSvgRoot(side);
    if (!svg) return;
    // Determine which node's bounding box to keep (prefer currently selected element)
    let keepKey = '';
    try {
      // If a selectedElementNode exists and matches the given side, keep its boxes
      if (selectedElementNode && selectedElementSide === side) {
        keepKey = selectedElementNode.getAttribute && (selectedElementNode.getAttribute('data-text-node') || '') || '';
      }
    } catch (e) { keepKey = ''; }

    // remove any rects/handles either in svg root or inside iw wrappers
    try {
      const boxes = Array.from(svg.querySelectorAll('.text-bounding-box'));
      boxes.forEach(b => {
        try {
          const target = b.getAttribute('data-iw-target-node') || '';
          if (keepKey && target === keepKey) return; // preserve current node's box
        } catch (e) {}
        try { b.remove(); } catch (e) {}
      });
    } catch (e) {}

    try {
      const handles = Array.from(svg.querySelectorAll('.bounding-box-handle'));
      handles.forEach(h => {
        try {
          const target = h.getAttribute('data-iw-target-node') || '';
          if (keepKey && target === keepKey) return; // preserve current node's handles
        } catch (e) {}
        try { h.remove(); } catch (e) {}
      });
    } catch (e) {}

    // also clear from top-level handles layer if present (preserve handles for keepKey)
    try {
      const layer = svg.querySelector('g[data-iw-handles-layer]');
      if (layer) {
        Array.from(layer.querySelectorAll('.bounding-box-handle')).forEach(h => {
          try {
            const target = h.getAttribute('data-iw-target-node') || '';
            if (keepKey && target === keepKey) return;
          } catch (e) {}
          try { h.remove(); } catch (e) {}
        });
      }
    } catch(e) {}

    // also remove inside wrapper groups, but preserve items matching keepKey
    try {
      Array.from(svg.querySelectorAll('g[data-iw-wrapper]')).forEach(w => {
        Array.from(w.querySelectorAll('.text-bounding-box, .bounding-box-handle')).forEach(el => {
          try {
            const target = el.getAttribute && el.getAttribute('data-iw-target-node') || '';
            if (keepKey && target === keepKey) return;
          } catch (e) {}
          try { el.remove(); } catch (e) {}
        });
      });
    } catch(e) {}

    // also hide overlay handles (resize/rotate) if present -- but only if they are not for the kept node
    try {
      if (resizeHandle) {
        try {
          const nodeKey = resizeState.node && resizeState.node.getAttribute && resizeState.node.getAttribute('data-text-node') || '';
          if (!keepKey || nodeKey !== keepKey) resizeHandle.classList.remove('is-visible');
        } catch (e) { resizeHandle.classList.remove('is-visible'); }
      }
    } catch (e) {}
    try {
      if (rotateHandle) {
        try {
          const nodeKey = rotateState.node && rotateState.node.getAttribute && rotateState.node.getAttribute('data-text-node') || '';
          if (!keepKey || nodeKey !== keepKey) rotateHandle.classList.remove('is-visible');
        } catch (e) { rotateHandle.classList.remove('is-visible'); }
      }
    } catch (e) {}
  }

  // Create a blob URL from the SVG DOM and set it as the preview image src for a side
  function updatePreviewFromSvg(side) {
    const svg = getSvgRoot(side);
    const preview = imagePreviews[side];
    if (!svg || !preview) return;
    // clone and serialize the SVG so we don't modify the live DOM
    const clone = svg.cloneNode(true);
    // ensure width/height/viewBox are present for correct rasterization by the browser
    try {
      if (!clone.getAttribute('viewBox')) {
        const w = svg.getAttribute('width') || svg.clientWidth || 0;
        const h = svg.getAttribute('height') || svg.clientHeight || 0;
        if (w && h) clone.setAttribute('viewBox', `0 0 ${w} ${h}`);
      }
    } catch (e) {}

    const serializer = new XMLSerializer();
    const svgText = serializer.serializeToString(clone);
    const blob = new Blob([svgText], { type: 'image/svg+xml' });
    const url = URL.createObjectURL(blob);
    // revoke previous preview blob if we stored it
    try {
      const prev = preview.dataset.__svg_blob_url;
      if (prev) {
        URL.revokeObjectURL(prev);
      }
    } catch (e) {}
    preview.dataset.__svg_blob_url = url;
    preview.src = url;
    // ensure no filters on preview
    try { preview.style.removeProperty('filter'); } catch(e){}
  }

  function removeSvgNode(nodeKey, side) {
    const node = svgTextCache[side]?.get(nodeKey);
    if (node && node.parentNode) {
      if (inlineEditorNode === node) {
        closeInlineEditor(false);
      }
      node.parentNode.removeChild(node);
    }
    svgTextCache[side]?.delete(nodeKey);
  }

  function refreshFieldState() {
    if (!textFieldsContainer) return;
    textFieldsContainer.querySelectorAll(".text-field").forEach(wrapper => {
      const input = wrapper.querySelector("input");
      const side = getFieldSide(input);
      wrapper.classList.toggle("is-inactive", side !== currentView);
    });
  }

  function refreshPositions() {
    if (!textFieldsContainer) return;
    const grouped = new Map();

    Array.from(textFieldsContainer.querySelectorAll(".text-field")).forEach(wrapper => {
      const input = wrapper.querySelector("input");
      if (!input) return;
      const side = getFieldSide(input);
      if (!grouped.has(side)) grouped.set(side, []);
      grouped.get(side).push({ wrapper, input });
    });

    grouped.forEach((items, side) => {
      items.forEach(({ input }, index) => {
        const fallbackTop = TEXT_BASE_TOP + index * TEXT_SPACING;
        if (!input.dataset.topPercent) {
          input.dataset.topPercent = fallbackTop;
        }
        ensureSvgNodeForInput(input);
        applyPositionToText(svgTextCache[side].get(getTextNodeKey(input)), input, side);
      });
    });

    refreshFieldState();
  }

  function setupTextField(wrapper) {
    if (!wrapper || wrapper.dataset.textPrepared === "true") return;
    const input = wrapper.querySelector("input");
    const delBtn = wrapper.querySelector(".delete-text");
    if (!input) return;

    if (!input.dataset.cardSide) {
      input.dataset.cardSide = currentView;
    }

    if (!input.dataset.textNode) {
      input.dataset.textNode = `field-${Date.now()}-${Math.random().toString(36).slice(2, 5)}`;
    }

    const side = getFieldSide(input);
    const nodeKey = getTextNodeKey(input);
  const defaultValue = input.dataset.defaultValue ?? "";
  const placeholderValue = input.getAttribute("placeholder") || "";
    const existingNode = svgTextCache[side]?.get(nodeKey);
    const existingValue = getSvgNodeValue(existingNode);

    if (!input.value) {
      if (existingValue) {
        input.value = existingValue;
      } else if (defaultValue) {
        input.value = defaultValue;
      }
    }

    if (!input.placeholder && placeholderValue) {
      input.placeholder = placeholderValue;
    } else if (!input.placeholder && defaultValue) {
      input.placeholder = defaultValue;
    }

    const node = ensureSvgNodeForInput(input);
    if (!existingNode && node) {
      setSvgNodeText(node, input.value || defaultValue || "");
    } else if (existingNode && input.value && input.value !== existingValue) {
      setSvgNodeText(existingNode, input.value);
    } else if (existingNode && !existingValue && (defaultValue || input.value)) {
      setSvgNodeText(existingNode, input.value || defaultValue || "");
    }

    input.addEventListener("input", () => {
      try {
        // update the svg node from input value
        updateSvgNodeFromInput(input, input.value);
        // ensure the node exists and refresh bounding box / preview
        const node = svgTextCache[getFieldSide(input)]?.get(getTextNodeKey(input));
        if (node) {
          createBoundingBoxDebounced(node, getFieldSide(input));
          try { updatePreviewFromSvg(getFieldSide(input)); } catch(e){}
        }
      } catch (e) { console.debug('input update failed', e); }
    });

    input.addEventListener("focus", () => {
      const side = getFieldSide(input);
      if (currentView !== side) setActiveView(side);
      syncFieldHighlight(getTextNodeKey(input), side, true);
      showTextToolbar(input);
    });

    input.addEventListener("blur", () => {
      syncFieldHighlight(getTextNodeKey(input), getFieldSide(input), false);
      scheduleToolbarHide();
    });

    if (delBtn && !delBtn.dataset.bound) {
      delBtn.dataset.bound = "true";
      delBtn.addEventListener("click", () => {
        const side = getFieldSide(input);
        const nodeKey = getTextNodeKey(input);
        removeSvgNode(nodeKey, side);
        wrapper.remove();
        refreshPositions();
        scheduleToolbarHide();
      });
    }

    wrapper.dataset.textPrepared = "true";
  }

  function initializeTextFields() {
    if (!textFieldsContainer) return;
    Array.from(textFieldsContainer.querySelectorAll(".text-field")).forEach(setupTextField);
    refreshPositions();
  }

  function setActiveView(view) {
    closeInlineEditor(true);
    currentView = view;
    const isFront = view === "front";
    if (cardFront) cardFront.classList.toggle("active", isFront);
    if (cardBack) cardBack.classList.toggle("active", !isFront);

    viewToggleButtons.forEach(btn => {
      const target = btn === frontBtn ? "front" : btn === backBtn ? "back" : btn.dataset.view;
      const active = target === view;
      btn.classList.toggle("active", active);
      btn.setAttribute("aria-pressed", active ? "true" : "false");
    });

    refreshFieldState();
  }

  function updateZoom() {
    if (!canvas) return;
    closeInlineEditor(true);
    canvas.style.transform = `scale(${zoom})`;
    canvas.style.transformOrigin = "center center";
    if (zoomLevel) zoomLevel.textContent = `${Math.round(zoom * 100)}%`;
    if (zoomInBtn) zoomInBtn.disabled = zoom >= ZOOM_MAX;
    if (zoomOutBtn) zoomOutBtn.disabled = zoom <= ZOOM_MIN;
  }

  // Event bindings
  if (frontBtn) frontBtn.addEventListener("click", () => setActiveView("front"));
  if (backBtn) backBtn.addEventListener("click", () => setActiveView("back"));

  if (zoomInBtn) {
    zoomInBtn.addEventListener("click", () => {
      if (zoom < ZOOM_MAX) {
        zoom = Math.min(ZOOM_MAX, +(zoom + ZOOM_STEP).toFixed(2));
        updateZoom();
      }
    });
  }

  if (zoomOutBtn) {
    zoomOutBtn.addEventListener("click", () => {
      if (zoom > ZOOM_MIN) {
        zoom = Math.max(ZOOM_MIN, +(zoom - ZOOM_STEP).toFixed(2));
        updateZoom();
      }
    });
  }

  updateZoom();

  cacheSvgNodes(cardFront, "front");
  cacheSvgNodes(cardBack, "back");
  // Normalize embedded SVG text nodes: some uploaded template SVGs may have
  // tspan/x/text-anchor values that cause visual misalignment when inserted
  // into our editor. Fix common issues by ensuring each <text> has an explicit
  // x and text-anchor and that its child <tspan> elements inherit the same x
  // so browser rendering lines up with our computed bounding boxes.
  (function normalizeAndScaleSvgTemplates() {
    function getSvgScale(svg) {
      // Use viewBox and width/height to determine scale
      const vb = svg.getAttribute('viewBox');
      const w = parseFloat(svg.getAttribute('width')) || 500;
      const h = parseFloat(svg.getAttribute('height')) || 700;
      if (!vb) return 1;
      const parts = vb.trim().split(/\s+/).map(Number);
      if (parts.length === 4 && parts[2] && parts[3]) {
        // scale = rendered width / viewBox width
        return w / parts[2];
      }
      return 1;
    }
    function scaleCanvasToSvg(card, svg) {
      // Set canvas size to match SVG template
      if (!svg || !card) return;
      const w = parseFloat(svg.getAttribute('width')) || 500;
      const h = parseFloat(svg.getAttribute('height')) || 700;
      card.style.width = w + 'px';
      card.style.height = h + 'px';
      card.parentNode.style.width = w + 'px';
      card.parentNode.style.height = h + 'px';
    }
    function normalizeTextNodes(svg) {
      // Map all text/tspan x/y and font metrics to SVG viewBox coordinates and sync overlays
      const vb = svg.getAttribute('viewBox');
      let vbX = 0, vbY = 0, vbW = 500, vbH = 700;
      if (vb) {
        const parts = vb.trim().split(/\s+/).map(Number);
        if (parts.length === 4) {
          vbX = parts[0]; vbY = parts[1]; vbW = parts[2]; vbH = parts[3];
        }
      }
      const nodes = svg.querySelectorAll('text');
      nodes.forEach(node => {
        if (node.hasAttribute('data-text-node')) return;
        // Use SVG's own x/y, font-family, font-size, letter-spacing, anchor, baseline
        let x = parseFloat(node.getAttribute('x'));
        let y = parseFloat(node.getAttribute('y'));
        if (!isFinite(x) || x < vbX || x > vbX + vbW) x = vbX + vbW / 2;
        if (!isFinite(y) || y < vbY || y > vbY + vbH) y = vbY + vbH / 2;
        node.setAttribute('x', String(x));
        node.setAttribute('y', String(y));
        node.dataset.originalX = String(x);
        node.dataset.originalY = String(y);
        // Respect original anchor and baseline if present
        const anchor = node.getAttribute('text-anchor') || 'middle';
        node.setAttribute('text-anchor', anchor);
        const baseline = node.getAttribute('dominant-baseline') || 'middle';
        node.setAttribute('dominant-baseline', baseline);
        // Sync font metrics
        const fontSize = node.getAttribute('font-size') || window.getComputedStyle(node).fontSize || '16';
        node.setAttribute('font-size', fontSize);
        const letterSpacing = node.getAttribute('letter-spacing') || window.getComputedStyle(node).letterSpacing || '';
        if (letterSpacing) node.setAttribute('letter-spacing', letterSpacing);
        const fontFamily = node.getAttribute('font-family') || (node.style && node.style.fontFamily) || window.getComputedStyle(node).fontFamily || '';
        if (fontFamily) node.setAttribute('style', `font-family: ${fontFamily};`);
        // Center all tspans and sync font metrics
        const tspans = node.querySelectorAll('tspan');
        if (tspans.length > 0) {
          tspans.forEach((tspan, i) => {
            tspan.setAttribute('x', String(x));
            if (i === 0) tspan.setAttribute('y', String(y));
            if (fontSize) tspan.setAttribute('font-size', fontSize);
            if (letterSpacing) tspan.setAttribute('letter-spacing', letterSpacing);
            if (fontFamily) tspan.setAttribute('style', `font-family: ${fontFamily};`);
          });
        }
        // Sync to input overlays if present
        const nodeKey = node.getAttribute('id') || node.getAttribute('name') || '';
        if (nodeKey) {
          const input = document.querySelector(`input[data-text-node="${nodeKey}"]`);
          if (input) {
            input.dataset.leftPercent = ((x - vbX) / vbW * 100).toFixed(2);
            input.dataset.topPercent = ((y - vbY) / vbH * 100).toFixed(2);
            input.dataset.fontSize = fontSize;
            input.dataset.letterSpacing = letterSpacing;
            input.dataset.fontFamily = fontFamily;
            input.style.fontFamily = fontFamily;
            input.style.fontSize = fontSize + 'px';
            if (letterSpacing) input.style.letterSpacing = letterSpacing;
          }
        }
      });
    }
    function runNormalizationAndScaling() {
      ['front','back'].forEach(side => {
        const card = side === 'front' ? cardFront : cardBack;
        const svg = getSvgRoot(side);
        if (!svg || !card) return;
        scaleCanvasToSvg(card, svg);
        normalizeTextNodes(svg);
      });
      cacheSvgNodes(cardFront, 'front');
      cacheSvgNodes(cardBack, 'back');
      refreshPositions();
      // populate panel inputs now that SVG nodes are cached
      try { populateTextFieldsFromSvg(); } catch (e) {}
      try { updatePreviewFromSvg('front'); updatePreviewFromSvg('back'); } catch(e) {}
    }
    if (window.document.fonts && window.document.fonts.ready) {
      window.document.fonts.ready.then(runNormalizationAndScaling);
    } else {
      setTimeout(runNormalizationAndScaling, 200);
    }
  })();
  if (allowImageEditing) {
    // Try to apply images. If SVG <image> can't render for any reason, ensure the card shows the server-provided default as a background so the user sees a preview.
    applyImage("front", imageState.front.currentSrc, { skipPreview: false });
    applyImage("back", imageState.back.currentSrc, { skipPreview: false });

    // Immediate visual fallback: if the card has a default image URL, apply it as background so the canvas is never empty.
    if (shouldDisplayFallbackImages) {
      try {
        ['front','back'].forEach(side => {
          const card = side === 'front' ? cardFront : cardBack;
          if (!card) return;
          const defaultSrc = card.dataset.defaultImage || '';
          const current = card.dataset.currentImage || '';
          if (defaultSrc && !current) {
            try {
              card.classList.add('background-fallback');
              card.style.backgroundImage = `url("${defaultSrc}")`;
              const previewImg = document.querySelector(`[data-image-preview="${side}"]`);
              if (previewImg && !previewImg.getAttribute('src')) previewImg.src = defaultSrc;
              updateDebugPanel({ event: 'immediate-background-fallback', side, defaultSrc });
            } catch (e) { console.warn('background fallback apply failed', e); }
          }
        });
      } catch (e) {}
    }
  }
  initializeTextFields();
  registerTextMetaSubmitHandlers();

  // wire undo/redo buttons
  try {
    const undoBtn = document.querySelector('.undo-btn');
    const redoBtn = document.querySelector('.redo-btn');
    if (undoBtn) undoBtn.addEventListener('click', (e) => { e.preventDefault(); undo(); });
    if (redoBtn) redoBtn.addEventListener('click', (e) => { e.preventDefault(); redo(); });
    updateUndoRedoButtons();
  } catch (e) {}

  if (addTextBtn && textFieldsContainer) {
    addTextBtn.addEventListener("click", () => {
      const wrapper = document.createElement("div");
      wrapper.classList.add("text-field");
      wrapper.dataset.cardSide = currentView;

      const input = document.createElement("input");
      input.type = "text";
      input.value = "New Text";
      input.dataset.cardSide = currentView;
      input.dataset.textNode = `custom-${Date.now()}`;
      input.dataset.topPercent = TEXT_BASE_TOP;
      input.dataset.leftPercent = 50;
      input.dataset.align = "center";
    input.dataset.defaultValue = "New Text";
    input.placeholder = "New Text";

      const delBtn = document.createElement("button");
      delBtn.classList.add("delete-text");
      delBtn.type = "button";
  delBtn.setAttribute("aria-label", "Delete text field");
  delBtn.innerHTML = '<i class="fa-solid fa-trash-can" aria-hidden="true"></i>';

      wrapper.appendChild(input);
      wrapper.appendChild(delBtn);
      textFieldsContainer.appendChild(wrapper);

      setupTextField(wrapper);
      // create the SVG node for this input so it immediately appears on the canvas
      const createdNode = ensureSvgNodeForInput(input);
      if (createdNode) {
        // set some initial visible text and attributes
        setSvgNodeText(createdNode, input.value || input.dataset.defaultValue || 'New Text');
        // center on view
        const svg = getSvgRoot(currentView);
        if (svg) {
          const metrics = parseViewBox(svg);
          const x = metrics.x + (metrics.width || 500) * 0.5;
          const y = metrics.y + (metrics.height || 700) * 0.5;
          updateNodePosition(createdNode, currentView, x, y);
        }
        // prepare and focus
        prepareSvgNode(createdNode, currentView);
        selectedElementNode = createdNode;
        selectedElementSide = currentView;
        // show bounding box and open inline editor for quick edit
        createBoundingBox(createdNode, currentView);
  try { populateToolbarForNode(createdNode, currentView); } catch (e) {}
        // begin history capture for creation
        beginHistoryCapture(createdNode, currentView);
        endHistoryCapture(createdNode, currentView);
        try {
          // open inline editor for immediate editing
          startInlineEditor(createdNode, currentView);
        } catch (e) {}
      }

      refreshPositions();

      try {
        input.focus({ preventScroll: true });
      } catch (err) {
        input.focus();
      }
    });
  }

  sideButtons.forEach(btn => {
    const panelName = btn.dataset.panel || "text";
    btn.addEventListener("click", () => {
      sideButtons.forEach(sideBtn => sideBtn.classList.remove("active"));
      btn.classList.add("active");
      showPanel(panelName);
    });
  });

  if (allowImageEditing && canReplaceImages) {
    imageInputs.forEach(input => {
      const side = input.dataset.imageInput;
      input.addEventListener("change", () => {
        const file = input.files && input.files[0];
        if (!file) return;

        if (!file.type.startsWith("image/")) {
          input.value = "";
          alert("Please choose an image file.");
          return;
        }

        if (file.size > MAX_IMAGE_SIZE) {
          input.value = "";
          alert("Image is too large. Please upload a file smaller than 5MB.");
          return;
        }

        const reader = new FileReader();
        reader.onload = event => {
          const result = event.target?.result;
          if (typeof result === "string") {
            applyImage(side, result);
          }
        };
        reader.onerror = () => {
          console.error("Unable to read selected image file.");
          alert("Unable to read the selected image file. Please try another image.");
        };
        reader.readAsDataURL(file);
      });
    });

    resetImageButtons.forEach(btn => {
      const side = btn.dataset.resetImage;
      btn.addEventListener("click", () => resetImage(side));
    });
  }

  setActiveView("front");
  showPanel(sideButtons[0]?.dataset?.panel || "text");
  // global cleanup: if a pointerup/cancel occurs outside expected handlers, ensure no wrapper remains in rotating state
  document.addEventListener('pointerup', () => {
    try {
      document.querySelectorAll('g[data-iw-wrapper].is-rotating').forEach(w => {
        try { w.classList.remove('is-rotating'); } catch(e) {}
      });
    } catch(e) {}
  });
  document.addEventListener('pointercancel', () => {
    try {
      document.querySelectorAll('g[data-iw-wrapper].is-rotating').forEach(w => {
        try { w.classList.remove('is-rotating'); } catch(e) {}
      });
    } catch(e) {}
  });

  /* Images sidebar: upload + recent preview handling */
  (function wireImagesSidebar() {
    const panel = document.getElementById('imagesPanel');
    if (!panel) return;
    if (!canReplaceImages) {
      // Show a readonly notice when background swapping is disabled for this template.
  const disabledMessage = document.createElement('div');
  disabledMessage.className = 'images-locked-message';
  disabledMessage.textContent = 'Image replacement is disabled for this template.';
  disabledMessage.style.padding = '16px';
  disabledMessage.style.textAlign = 'center';
  disabledMessage.style.color = '#4b5563';
      const body = panel.querySelector('.images-body');
      if (body) {
        body.innerHTML = '';
        body.appendChild(disabledMessage);
      }
      panel.querySelectorAll('button, input').forEach(el => {
        el.disabled = true;
        el.setAttribute('aria-disabled', 'true');
      });
      return;
    }
  const tabs = panel.querySelectorAll('.images-tab');
  const fileInput = document.getElementById('imagesFileInput');
  const btnUpload = document.getElementById('btnUploadFiles');
  const recentGrid = document.getElementById('recentUploads');
    const STORAGE_KEY = '__iw_recent_images_v1';

    function switchTab(target) {
      tabs.forEach(t => {
        const panelName = t.dataset.tab;
        const pane = panel.querySelector(`.images-panel[data-panel="${panelName}"]`);
        const active = t === target;
        t.classList.toggle('active', active);
        if (pane) pane.style.display = active ? '' : 'none';
        t.setAttribute('aria-selected', active ? 'true' : 'false');
      });
    }

    tabs.forEach(t => t.addEventListener('click', (e) => { e.stopPropagation(); switchTab(t); }));

    function loadStored() {
      try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) return [];
        const arr = JSON.parse(raw);
        if (!Array.isArray(arr)) return [];
        return arr;
      } catch (e) { return []; }
    }

    function storeList(list) {
      try { localStorage.setItem(STORAGE_KEY, JSON.stringify(list.slice(0, 40))); } catch(e) {}
    }

    function renderRecent(list) {
      if (!recentGrid) return;
      recentGrid.innerHTML = '';
      (list || []).forEach(item => {
        const wrapper = document.createElement('div');
        wrapper.className = 'thumb-wrapper';

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'thumb';
        btn.setAttribute('aria-label', item.name || 'Uploaded image');
        const img = document.createElement('img');
        img.alt = item.name || 'Uploaded image';
        img.src = item.data;
        btn.appendChild(img);
        btn.addEventListener('click', () => {
          // on click, apply image to current view as background via existing applyImage
          try { applyImage(currentView || 'front', item.data); } catch(e) { console.debug('applyImage failed', e); }
        });

        const del = document.createElement('button');
        del.type = 'button';
        del.className = 'thumb-delete';
        del.setAttribute('aria-label', 'Delete uploaded image');
  del.innerHTML = '<i class="fa-solid fa-trash-can" aria-hidden="true"></i>';
        del.addEventListener('click', (ev) => {
          ev.stopPropagation();
          try {
            // remove from stored list by matching data URL
            const stored = loadStored();
            const filtered = (stored || []).filter(s => s.data !== item.data);
            storeList(filtered);
            renderRecent(filtered);
          } catch (e) { console.error('Failed to delete recent image', e); }
        });

        wrapper.appendChild(btn);
        wrapper.appendChild(del);
        recentGrid.appendChild(wrapper);
      });
    }

    // initialize from storage
    try { renderRecent(loadStored()); } catch(e){}

    function handleFiles(files) {
      if (!files || files.length === 0) return;
      const stored = loadStored();
      Array.from(files).forEach(f => {
        const reader = new FileReader();
        reader.onload = (ev) => {
          const data = ev.target && ev.target.result;
          if (!data) return;
          // push to head of stored list
          stored.unshift({ name: f.name || '', type: f.type || '', data });
          // dedupe by exact data URL
          const uniq = [];
          const seen = new Set();
          for (const it of stored) {
            if (!seen.has(it.data)) { uniq.push(it); seen.add(it.data); }
          }
          storeList(uniq);
          renderRecent(uniq);
        };
        // read as data URL for previews (and for applyImage)
        try { reader.readAsDataURL(f); } catch (e) { console.error('readFile failed', e); }
      });
    }

    if (btnUpload && fileInput) {
      btnUpload.addEventListener('click', (e) => { e.preventDefault(); fileInput.click(); });
    }

    if (fileInput) {
      fileInput.addEventListener('change', (e) => {
        const files = fileInput.files;
        handleFiles(files);
        // clear input so same file can be selected again
        try { fileInput.value = ''; } catch(e) {}
      });
    }

    /* Discover search (Unsplash) wiring */
    try {
      const discoverInput = panel.querySelector('#discoverSearchInput');
      const discoverBtn = panel.querySelector('#discoverSearchBtn');
      const resultsGrid = panel.querySelector('#discoverResults');
      const UNSPLASH_KEY = 'iFpUZ_6aTnLGz0Voz0MYlprq9i_RBl83ux9DzV6EMOs';
  let searchTimer = null;
  let currentQuery = '';
  let currentPage = 1;
  let totalPages = 1;

      function renderDiscoverResults(items, append = false) {
        if (!resultsGrid) return;
        if (!append) resultsGrid.innerHTML = '';
        if (!items || items.length === 0) {
          const el = document.createElement('div');
          el.className = 'discover-empty';
          el.textContent = 'No results';
          resultsGrid.appendChild(el);
          return;
        }
        items.forEach(it => {
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'thumb';
          btn.style.padding = '0';
          btn.style.border = 'none';
          btn.style.background = 'transparent';
          const img = document.createElement('img');
          img.alt = it.alt_description || it.description || 'Result image';
          img.src = it.urls && (it.urls.small || it.urls.thumb) || '';
          img.style.width = '100%';
          img.style.height = 'auto';
          img.loading = 'lazy';
          btn.appendChild(img);
          btn.addEventListener('click', () => {
            try { applyImage(currentView || 'front', it.urls && it.urls.full || it.urls && it.urls.regular || it.urls && it.urls.small); } catch(e) { console.debug('applyImage failed', e); }
          });
          resultsGrid.appendChild(btn);
        });
        // pagination controlled by infinite scroll; no Load more button
      }

      const spinner = panel.querySelector('#discoverSpinner');
      function showSpinner() { try { if (spinner) { spinner.style.display = ''; spinner.setAttribute('aria-hidden', 'false'); } } catch(e) {} }
      function hideSpinner() { try { if (spinner) { spinner.style.display = 'none'; spinner.setAttribute('aria-hidden', 'true'); } } catch(e) {} }

      async function doDiscoverSearch(q, page = 1, append = false) {
        if (!q || !q.trim()) { renderDiscoverResults([]); return; }
        currentQuery = q;
        currentPage = Number(page) || 1;
        try {
          showSpinner();
          const per = 18; // page size
          const url = `https://api.unsplash.com/search/photos?query=${encodeURIComponent(q)}&per_page=${per}&page=${currentPage}`;
          const res = await fetch(url, { headers: { Authorization: `Client-ID ${UNSPLASH_KEY}` } });
          if (!res.ok) throw new Error('Unsplash fetch failed ' + res.status);
          const data = await res.json();
          const items = data.results || [];
          const total = Number(data.total || 0);
          totalPages = Math.ceil(total / per) || 1;
          renderDiscoverResults(items, append);
          hideSpinner();
        } catch (err) {
          console.warn('Discover search failed', err);
          hideSpinner();
          renderDiscoverResults([]);
        }
      }

      if (discoverBtn && discoverInput) {
        discoverBtn.addEventListener('click', (e) => { e.preventDefault(); doDiscoverSearch(discoverInput.value, 1, false); });
        discoverInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); doDiscoverSearch(discoverInput.value, 1, false); } });
        discoverInput.addEventListener('input', (e) => { clearTimeout(searchTimer); searchTimer = setTimeout(() => { doDiscoverSearch(discoverInput.value, 1, false); }, 450); });
        // Load more button removed; infinite scroll handles loading more pages
        // infinite scroll: when user scrolls near bottom of resultsGrid, load next page
        (function attachInfiniteScroll() {
          if (!resultsGrid) return;
          let isLoading = false;
          let lastRun = 0;
          resultsGrid.addEventListener('scroll', (ev) => {
            const now = Date.now();
            if (now - lastRun < 200) return; // throttle a bit tighter
            lastRun = now;
            if (isLoading) return;
            const el = ev.target;
            const scrollTop = el.scrollTop;
            const clientH = el.clientHeight;
            const scrollH = el.scrollHeight;
            // load next page when user scrolls past 60% of the content (aggressive load)
            const ratio = (scrollTop + clientH) / Math.max(1, scrollH);
            if (ratio >= 0.6) {
              if (!currentQuery) return;
              if (currentPage >= totalPages) return;
              isLoading = true;
              currentPage += 1;
              doDiscoverSearch(currentQuery, currentPage, true).then(() => { isLoading = false; }).catch(() => { isLoading = false; });
            }
          });
        })();
      }
    } catch (e) { /* non-fatal */ }
  })();
  initializeTextEditorPanel();
});
