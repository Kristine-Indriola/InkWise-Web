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
    // Also handle SVG text node click
    if (e.target && e.target.closest && e.target.closest('svg [data-text-node]')) {
      showTextToolbar(e.target);
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
  const textToolbar = document.getElementById("textToolbar");

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
      const activeEl = document.activeElement;
      if (!activeEl) {
        hideTextToolbar();
        return;
      }
      if (textToolbar.contains(activeEl)) return;
      if (activeEl.closest?.(".text-field")) return;
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
    if (active && active.matches && active.matches('input[data-text-node]')) {
      active.style.color = color;
      // update SVG node fill
      const side = getFieldSide(active);
      const key = getTextNodeKey(active);
      const node = svgTextCache[side]?.get(key);
      if (node) node.setAttribute('fill', color);
    } else if (inlineEditorNode) {
      inlineEditorNode.setAttribute('fill', color);
    }
    // update toolbar color input value
    const sw = document.querySelector('.toolbar-color-swatch');
    if (sw) { sw.style.background = color; sw.dataset.color = color; }
  }

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
  const fontSizeToggle = document.getElementById('toolbarFontSizeToggle');
  const fontSizeDropdown = document.querySelector('.font-size-dropdown');

  function applyFontSizeToActive(size) {
    if (!size) return;
    const active = document.activeElement;
    if (active && active.matches && active.matches('input[data-text-node]')) {
      active.style.fontSize = size + 'px';
      try { active.dataset.fontSize = String(size); } catch(e){}
      const side = getFieldSide(active);
      const nodeKey = getTextNodeKey(active);
      const node = svgTextCache[side]?.get(nodeKey);
      if (node) node.setAttribute('font-size', String(size));
      if (node) try { node.dataset.fontSize = String(size); } catch(e){}
    } else if (inlineEditorNode) {
      // if inline editor open, apply to svg node directly
      inlineEditorNode.setAttribute('font-size', String(size));
      try { inlineEditorNode.dataset.fontSize = String(size); } catch(e){}
    }
  }

  if (fontSizeToggle && fontSizeDropdown) {
    fontSizeToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      const isOpen = fontSizeDropdown.getAttribute('aria-hidden') === 'false';
      fontSizeDropdown.setAttribute('aria-hidden', isOpen ? 'true' : 'false');
    });

    fontSizeDropdown.addEventListener('click', (e) => {
      const btn = e.target.closest('.font-size-option');
      if (!btn) return;
      const val = Number(btn.textContent.trim());
      fontSizeInput.value = String(val);
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

  document.addEventListener("pointerdown", event => {
    if (!textToolbar) return;
    if (textToolbar.contains(event.target)) return;
    if (
      event.target.closest?.(".text-field") ||
      event.target.closest?.(".canvas [data-text-node]")
    ) {
      showTextToolbar(event.target);
      return;
    }
    hideTextToolbar();
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

  let inlineEditor = null;
  let inlineEditorNode = null;
  let inlineEditorSide = null;
  // Enable image debug panel during troubleshooting to surface hrefs/load status
  const DEBUG_IMAGES = true;
  // debug panel element
  let __iw_debug_panel = null;

  function ensureDebugPanel() {
    if (!DEBUG_IMAGES) return null;
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
    if (!DEBUG_IMAGES) return;
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
    if (imageLayers[side]) {
      return imageLayers[side];
    }

    const card = side === 'front' ? cardFront : cardBack;
    if (!card) return null;

    const svg = getSvgRoot(side);
    if (!svg) return null;

    let layer = svg.querySelector(`image[data-editable-image="${side}"]`);
    if (!layer) {
      layer = document.createElementNS(SVG_NS, 'image');
      layer.setAttribute('data-editable-image', side);
      layer.setAttribute('x', '0');
      layer.setAttribute('y', '0');
      layer.setAttribute('width', '100%');
      layer.setAttribute('height', '100%');
      layer.setAttribute('preserveAspectRatio', 'xMidYMid slice');
      layer.style.display = 'none';

      // insert the new image before the first editable text node so text stays on top
      const firstTextNode = Array.from(svg.children).find(node => node.hasAttribute && node.hasAttribute('data-text-node'));
      if (firstTextNode) {
        svg.insertBefore(layer, firstTextNode);
      } else {
        svg.appendChild(layer);
      }
    }

    imageLayers[side] = layer;
    try {
      const href = layer.getAttribute && (layer.getAttribute('href') || layer.getAttributeNS(XLINK_NS, 'href') || (layer.href && layer.href.baseVal));
      console.debug('[ensureImageLayer] side:', side, 'layer:', layer, 'href:', href);
      updateDebugPanel({ event: 'ensureImageLayer', side, href });
    } catch (e) {}
    return layer;
  }

  function ensureFallbackImage(side) {
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
    if (!element) return;
    try {
      const isImg = element.tagName && element.tagName.toLowerCase() === 'img';
      console.debug('[setImageElementSource] element:', element, 'src:', src, 'isImg:', isImg);

      if (isImg) {
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
          try { element.style.removeProperty('display'); } catch (e) {}
          element.style.visibility = 'visible';
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

  // If the target is an external SVG, fetch it and use a blob URL to avoid embedding quirks
  const isSvg = typeof normalizedDisplaySrc === 'string' && /\.svg(\?|$)/i.test(normalizedDisplaySrc);
  if (isSvg && normalizedDisplaySrc) {
    // revoke previous blob if any
    try { if (imageBlobUrls[side]) { URL.revokeObjectURL(imageBlobUrls[side]); imageBlobUrls[side] = null; } } catch(e){}
    // attempt to fetch the SVG text and create a blob URL
    fetch(normalizedDisplaySrc, { credentials: 'same-origin' }).then(res => {
      if (!res.ok) throw new Error('fetch failed ' + res.status + ' for ' + normalizedDisplaySrc);
      return res.text();
    }).then(svgText => {
      try {
        const blob = new Blob([svgText], { type: 'image/svg+xml' });
        const blobUrl = URL.createObjectURL(blob);
        imageBlobUrls[side] = blobUrl;
        // set blob URL on svg <image> and fallback <img>
        setImageElementSource(layer, blobUrl);
        const fallbackLayer = ensureFallbackImage(side);
        if (fallbackLayer) setImageElementSource(fallbackLayer, blobUrl);
        updateDebugPanel({ event: 'applyImage', side, fetched: true, blobUrl });
      } catch (e) {
        console.warn('[applyImage] failed to inline svg, falling back to direct href', e);
        setImageElementSource(layer, normalizedDisplaySrc);
        const fallbackLayer = ensureFallbackImage(side);
        if (fallbackLayer) setImageElementSource(fallbackLayer, normalizedDisplaySrc);
      }
    }).catch(err => {
      console.warn('[applyImage] fetch svg failed, using direct src', err);
      setImageElementSource(layer, normalizedDisplaySrc || displaySrc);
      const fallbackLayer = ensureFallbackImage(side);
      if (fallbackLayer) setImageElementSource(fallbackLayer, normalizedDisplaySrc || displaySrc);
    });
  } else {
    setImageElementSource(layer, normalizedDisplaySrc || displaySrc);
    const fallbackLayer = ensureFallbackImage(side);
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

  function resetImage(side) {
    if (!imageState[side]) return;
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

  function closeInlineEditor(commit = true) {
    if (!inlineEditor) return;
    const editor = inlineEditor;
    const node = inlineEditorNode;
    const side = inlineEditorSide;
    const nodeKey = node?.getAttribute("data-text-node") || "";

    if (commit && node) {
      const value = editor.value;
      setSvgNodeText(node, value);
      const safeKey = escapeAttr(nodeKey);
      const input = textFieldsContainer?.querySelector(`input[data-text-node="${safeKey}"][data-card-side="${side}"]`);
      if (input && input.value !== value) {
        input.value = value;
        input.dispatchEvent(new Event("input", { bubbles: true }));
        input.dispatchEvent(new Event("change", { bubbles: true }));
      }
    }

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
    scheduleToolbarHide();
  }

  function startInlineEditor(node, side) {
    if (!node) return;
    const nodeKey = node.getAttribute("data-text-node") || "";
    if (!nodeKey || !textFieldsContainer) return;

    if (currentView !== side) {
      setActiveView(side);
    }

    const safeKey = escapeAttr(nodeKey);
    const input = textFieldsContainer.querySelector(`input[data-text-node="${safeKey}"][data-card-side="${side}"]`);
    if (input) {
      requestAnimationFrame(() => {
        try {
          input.focus({ preventScroll: true });
        } catch (err) {
          input.focus();
        }
        input.select?.();
        showTextToolbar(node);
      });
    }
  }

  function prepareSvgNode(node, side) {
    const nodeKey = node.getAttribute("data-text-node");
    if (!nodeKey) return;
    if (node.dataset.prepared === side) {
      svgTextCache[side].set(nodeKey, node);
      return;
    }
    svgTextCache[side].set(nodeKey, node);

    if (!node.dataset.originalX && node.hasAttribute("x")) {
      node.dataset.originalX = node.getAttribute("x");
    }
    if (!node.dataset.originalY && node.hasAttribute("y")) {
      node.dataset.originalY = node.getAttribute("y");
    }

    node.setAttribute("contenteditable", "true");
    node.setAttribute("role", "textbox");
    node.setAttribute("spellcheck", "false");
    if (!node.hasAttribute("aria-label")) {
      node.setAttribute("aria-label", "Edit canvas text");
    }

    node.addEventListener("focus", () => {
      if (currentView !== side) setActiveView(side);
      node.classList.add("svg-text-focus");
      syncFieldHighlight(nodeKey, side, true);
    });

    node.addEventListener("blur", () => {
      node.classList.remove("svg-text-focus");
      syncFieldHighlight(nodeKey, side, false);
    });

    node.addEventListener("keydown", (event) => {
      if (event.key === "Enter" && !event.shiftKey) {
        event.preventDefault();
        startInlineEditor(node, side);
      } else if (event.key === "Backspace" || event.key === "Delete") {
        event.preventDefault();
        startInlineEditor(node, side);
      } else if (!event.ctrlKey && !event.metaKey && !event.altKey && event.key.length === 1) {
        event.preventDefault();
        startInlineEditor(node, side);
      }
    });

    node.addEventListener("input", () => handleSvgNodeInput(node, side));

    node.addEventListener("pointerdown", (event) => {
      event.preventDefault();
      event.stopPropagation();
      startInlineEditor(node, side);
    });

    node.dataset.prepared = side;
  }

  function cacheSvgNodes(cardElement, side) {
    if (!cardElement) return;
    const nodes = cardElement.querySelectorAll("[data-text-node]");
    nodes.forEach(node => prepareSvgNode(node, side));
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
    const safeKey = escapeAttr(nodeKey);
    const input = textFieldsContainer?.querySelector(`input[data-text-node="${safeKey}"][data-card-side="${side}"]`);
    const value = getSvgNodeValue(node);

    if (input && input.value !== value) {
      input.value = value;
      input.dispatchEvent(new Event("input", { bubbles: true }));
      input.dispatchEvent(new Event("change", { bubbles: true }));
    }
    // update the thumbnail preview for this side to reflect the changed SVG text
    try { updatePreviewFromSvg(side); } catch (e) { console.debug('preview-from-svg failed', e); }
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
    applyPositionToText(text, input, side);
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
      updateSvgNodeFromInput(input, input.value);
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
  // Try to apply images. If SVG <image> can't render for any reason, ensure the card shows the server-provided default as a background so the user sees a preview.
  applyImage("front", imageState.front.currentSrc, { skipPreview: false });
  applyImage("back", imageState.back.currentSrc, { skipPreview: false });

  // Immediate visual fallback: if the card has a default image URL, apply it as background so the canvas is never empty.
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
          // also update preview panel image if present
          const previewImg = document.querySelector(`[data-image-preview="${side}"]`);
          if (previewImg && !previewImg.getAttribute('src')) previewImg.src = defaultSrc;
          updateDebugPanel({ event: 'immediate-background-fallback', side, defaultSrc });
        } catch (e) { console.warn('background fallback apply failed', e); }
      }
    });
  } catch (e) {}
  initializeTextFields();
  registerTextMetaSubmitHandlers();

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
      delBtn.textContent = "🗑";

      wrapper.appendChild(input);
      wrapper.appendChild(delBtn);
      textFieldsContainer.appendChild(wrapper);

      setupTextField(wrapper);
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

  setActiveView("front");
  showPanel(sideButtons[0]?.dataset?.panel || "text");
});
