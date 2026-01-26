import React, { useEffect, useRef, useState } from 'react';
import './template-editor.css';

function TemplateEditor({ bootstrap = {} }) {
  const frontContainerRef = useRef(null);
  const backContainerRef = useRef(null);
  const frontEditorRef = useRef(null);
  const backEditorRef = useRef(null);
  const [status, setStatus] = useState({ front: 'idle', back: 'idle' });
  const [sizes, setSizes] = useState({
    front: { width: null, height: null, unit: 'px' },
    back: { width: null, height: null, unit: 'px' },
  });

  const formatSizeLabel = (value, unit) => {
    if (!value) return '';
    const asNumber = parseFloat(value);
    if (Number.isFinite(asNumber)) {
      if (unit === 'px' || unit === 'PX' || unit === 'Px') {
        const inches = asNumber / 96;
        return `${inches.toFixed(2)} in`;
      }
      if (unit === 'in' || unit === 'IN' || unit === 'In') {
        return `${asNumber} in`;
      }
      return `${value}${unit || ''}`;
    }
    return `${value}${unit || ''}`;
  };

  useEffect(() => {
    // Read mount dataset for canvas sizes and presence of back side
    try {
      const mountDs = typeof document !== 'undefined'
        ? (document.getElementById('template-editor-root')?.dataset || {})
        : {};
      const frontW = mountDs.frontCanvasWidth || mountDs.frontCanvaswidth || null;
      const frontH = mountDs.frontCanvasHeight || mountDs.frontCanvasheight || null;
      const frontUnit = mountDs.frontCanvasUnit || mountDs.frontCanvasunit || 'px';
      const backW = mountDs.backCanvasWidth || mountDs.backCanvaswidth || null;
      const backH = mountDs.backCanvasHeight || mountDs.backCanvasheight || null;
      const backUnit = mountDs.backCanvasUnit || mountDs.backCanvasunit || 'px';
      setSizes({
        front: { width: frontW, height: frontH, unit: frontUnit },
        back: { width: backW, height: backH, unit: backUnit },
      });
      var hasBack = (mountDs.hasBack === 'true' || mountDs.hasBack === true || mountDs.hasback === 'true');
    } catch (e) {
      // ignore
    }

    const toPx = (value, unit) => {
      if (!value) return null;
      const n = parseFloat(value);
      if (!Number.isFinite(n)) return null;
      if (!unit || unit.toLowerCase() === 'px') return n;
      if (unit.toLowerCase() === 'in') return Math.round(n * 96);
      // default: assume px
      return n;
    };

    const parseNumeric = (v) => {
      if (v == null) return null;
      const s = String(v).trim();
      if (s === '') return null;
      // number with px
      const pxMatch = s.match(/^([0-9.]+)px$/i);
      if (pxMatch) return parseFloat(pxMatch[1]);
      // number with in
      const inMatch = s.match(/^([0-9.]+)in$/i);
      if (inMatch) return Math.round(parseFloat(inMatch[1]) * 96);
      // plain number
      const n = parseFloat(s);
      return Number.isFinite(n) ? n : null;
    };

    const applySizingToSvg = (svgEl) => {
      if (!svgEl) return;
      try {
        // prefer explicit width/height attributes
        let w = parseNumeric(svgEl.getAttribute('width'));
        let h = parseNumeric(svgEl.getAttribute('height'));
        // fallback to style
        if ((!w || !h) && svgEl.style) {
          w = w || parseNumeric(svgEl.style.width);
          h = h || parseNumeric(svgEl.style.height);
        }
        // fallback to viewBox
        if ((!w || !h) && svgEl.getAttribute('viewBox')) {
          const vb = svgEl.getAttribute('viewBox').split(/\s+/).map(Number);
          if (vb && vb.length === 4) {
            w = w || Math.round(vb[2]);
            h = h || Math.round(vb[3]);
          }
        }
        // if still missing, try data attributes on parent
        if ((!w || !h) && svgEl.dataset) {
          const pw = svgEl.dataset.canvasWidth || svgEl.dataset.width;
          const ph = svgEl.dataset.canvasHeight || svgEl.dataset.height;
          w = w || (pw ? toPx(pw, svgEl.dataset.canvasUnit || 'px') : null);
          h = h || (ph ? toPx(ph, svgEl.dataset.canvasUnit || 'px') : null);
        }
        if (w && h) {
          svgEl.setAttribute('width', `${w}`);
          svgEl.setAttribute('height', `${h}`);
          svgEl.style.width = `${w}px`;
          svgEl.style.height = `${h}px`;
          // ensure container (parentElement) matches
          try {
            const parent = svgEl.parentElement;
            if (parent && parent.classList && parent.classList.contains('canvas-stage')) {
              parent.style.width = `${w}px`;
              parent.style.height = `${h}px`;
              parent.style.maxWidth = 'none';
            }
          } catch (e) {}
        }
      } catch (e) {
        // ignore
      }
    };

    const SvgEditorClass = typeof window !== 'undefined' ? window.SvgTemplateEditor : null;
    if (!SvgEditorClass) {
      console.warn('[TemplateEditor] SvgTemplateEditor not found on window');
      return undefined;
    }

    let mounted = true;

    async function initCanvas(container, side, editorRef) {
      if (!container || !mounted) return;

      // If a path is provided in bootstrap, try to fetch the svg markup and inject it
      const template = bootstrap.template || {};
      let svgPath = side === 'front'
        ? (template.svg_path || template.front_svg_path || template.front_svg)
        : (template.back_svg_path || template.back_svg);

      // Fallback to data attribute on container
      if (!svgPath) {
        const dataAttr = side === 'front' ? 'data-front-svg' : 'data-back-svg';
        svgPath = container.getAttribute(dataAttr);
      }

      if (svgPath && container.innerHTML.trim() === '') {
        try {
          const res = await fetch(svgPath, { credentials: 'same-origin' });
          if (res.ok) {
            const text = await res.text();
            // Replace container contents with the fetched SVG markup
            container.innerHTML = text;
          }
        } catch (err) {
          console.warn('[TemplateEditor] failed to fetch svg:', err);
        }
      }

      // Find the svg element (container may itself be the svg)
      const svgEl = (container.tagName && container.tagName.toLowerCase() === 'svg')
        ? container
        : container.querySelector('svg');
      if (!svgEl) return;

      // Set container and svg to true pixel dimensions from mount dataset
      try {
        const mountDs = typeof document !== 'undefined'
          ? (document.getElementById('template-editor-root')?.dataset || {})
          : {};
        const widthVal = side === 'front' ? mountDs.frontCanvasWidth : mountDs.backCanvasWidth;
        const heightVal = side === 'front' ? mountDs.frontCanvasHeight : mountDs.backCanvasHeight;
        const unit = side === 'front' ? (mountDs.frontCanvasUnit || 'px') : (mountDs.backCanvasUnit || 'px');
        const wPx = toPx(widthVal, unit);
        const hPx = toPx(heightVal, unit);
        if (wPx && hPx) {
          // apply inline sizing so CSS doesn't override to 100%
          try {
            container.style.width = `${wPx}px`;
            container.style.height = `${hPx}px`;
            container.style.maxWidth = 'none';
          } catch (e) {}
          try {
            svgEl.setAttribute('width', `${wPx}`);
            svgEl.setAttribute('height', `${hPx}`);
            svgEl.style.width = `${wPx}px`;
            svgEl.style.height = `${hPx}px`;
          } catch (e) {}
        }
      } catch (e) {
        // ignore sizing errors
      }

      // Apply viewBox from mount dataset if available and missing on SVG
      try {
        const mountDs = typeof document !== 'undefined'
          ? (document.getElementById('template-editor-root')?.dataset || {})
          : {};
        const width = side === 'front' ? mountDs.frontCanvasWidth : mountDs.backCanvasWidth;
        const height = side === 'front' ? mountDs.frontCanvasHeight : mountDs.backCanvasHeight;
        if (width && height && !svgEl.getAttribute('viewBox')) {
          svgEl.setAttribute('viewBox', `0 0 ${width} ${height}`);
        }
      } catch (e) {
        // ignore dataset read errors
      }

      try {
        // instantiate the existing SvgTemplateEditor (keeps parity with legacy editor)
        editorRef.current = new SvgEditorClass(svgEl, {
          onImageChange: () => {
            setStatus(s => ({ ...s, [side]: 'dirty' }));
            if (typeof window !== 'undefined') {
              window.dispatchEvent(new CustomEvent('inkwise:canvas-changed', { detail: { side } }));
            }
          },
          onTextChange: () => {
            setStatus(s => ({ ...s, [side]: 'dirty' }));
            if (typeof window !== 'undefined') {
              window.dispatchEvent(new CustomEvent('inkwise:canvas-changed', { detail: { side } }));
            }
          },
        });
        // Add bounding boxes to any text nodes and make them draggable
        try {
          const inst = editorRef.current;
          const textNodes = Array.from(svgEl.querySelectorAll('text'));
          textNodes.forEach((txt) => {
            try {
              // createBoundingBox may return the created group/element
              const bboxEl = (typeof inst.createBoundingBox === 'function') ? inst.createBoundingBox(txt) : null;
              // prefer making the bbox draggable; fall back to text node
              const draggableTarget = bboxEl || txt;
              if (bboxEl) {
                txt._boundingBox = bboxEl;
                bboxEl._targetElement = txt;
                // Make handles resizable for the text
                const handles = bboxEl.querySelectorAll('.resize-handle');
                handles.forEach(handle => inst.makeResizable(txt, handle));
              }
              if (draggableTarget && typeof inst.makeDraggable === 'function') {
                inst.makeDraggable(draggableTarget);
                try {
                  // give a visual affordance and disable native touch actions
                  if (draggableTarget.style) {
                    draggableTarget.style.cursor = 'move';
                    draggableTarget.style.touchAction = 'none';
                    try { draggableTarget.style.pointerEvents = 'all'; } catch (e) {}
                  }
                  if (draggableTarget.setAttribute) {
                    draggableTarget.setAttribute('data-inkwise-draggable', 'true');
                    try { draggableTarget.setAttribute('pointer-events', 'all'); } catch (e) {}
                  }
                  if (draggableTarget.classList) {
                    try { draggableTarget.classList.add('inkwise-draggable'); } catch (e) {}
                  }
                } catch (e) { /* ignore style errors */ }
              }
            } catch (e) {
              // ignore per-node errors
            }
          });
        } catch (e) {
          // ignore bounding box setup errors
        }

        // Populate text field list in sidebar
        const textFieldListId = side === 'front' ? 'frontTextFieldList' : 'backTextFieldList';
        const textFieldList = document.getElementById(textFieldListId);
        if (textFieldList) {
          textFieldList.innerHTML = ''; // clear existing
          const textNodes = Array.from(svgEl.querySelectorAll('text'));
          textNodes.forEach((txt, index) => {
            const li = document.createElement('li');
            li.className = 'text-field-item';
            const label = document.createElement('label');
            label.textContent = `${side === 'front' ? 'Front' : 'Back'} Text ${index + 1}`;
            const input = document.createElement('input');
            input.type = 'text';
            input.value = txt.textContent || '';
            input.addEventListener('input', (e) => {
              txt.textContent = e.target.value;
              // Trigger text change callback
              if (editorRef.current && typeof editorRef.current.options.onTextChange === 'function') {
                editorRef.current.options.onTextChange();
              }
            });
            input.addEventListener('focus', () => {
              if (typeof window !== 'undefined') {
                const event = new CustomEvent('inkwise:active-element', { detail: { type: 'text' } });
                window.dispatchEvent(event);
              }
            });
            input.addEventListener('blur', () => {
              if (typeof window !== 'undefined') {
                const event = new CustomEvent('inkwise:active-element', { detail: { type: null } });
                window.dispatchEvent(event);
              }
            });
            li.appendChild(label);
            li.appendChild(input);
            textFieldList.appendChild(li);
          });
        }
      } catch (e) {
        console.error('[TemplateEditor] failed to create SvgTemplateEditor', e);
      }
    }

    // Initialize front always; init back only if template has a back side
    void initCanvas(frontContainerRef.current, 'front', frontEditorRef);
    if (typeof hasBack !== 'undefined' ? hasBack : (bootstrap.template?.has_back ?? false)) {
      void initCanvas(backContainerRef.current, 'back', backEditorRef);
    }

    // --- Add simple drag + text-detection handlers on the canvas containers
    const isTextLike = (node) => {
      if (!node) return false;
      if (node.nodeName && node.nodeName.toLowerCase() === 'text') return true;
      if (node.classList && node.classList.contains('canvas-layer__text')) return true;
      if (node.closest) {
        const txt = node.closest('.canvas-layer__text, [data-inkwise-text]');
        if (txt) return true;
      }
      return false;
    };

    const isImageLike = (node) => {
      if (!node) return false;
      if (node.nodeName && (node.nodeName.toLowerCase() === 'image' || node.nodeName.toLowerCase() === 'img')) return true;
      if (node.classList && (node.classList.contains('canvas-layer__image') || node.classList.contains('svg-changeable-image') || node.classList.contains('changeable-image'))) return true;
      if (node.closest) {
        const img = node.closest('.canvas-layer__image, .svg-changeable-image, .changeable-image');
        if (img) return true;
      }
      return false;
    };

    const parseTranslate = (transform) => {
      const m = /translate\(([-0-9.]+)[ ,]([-0-9.]+)\)/.exec(transform || '');
      if (m) return { x: parseFloat(m[1]), y: parseFloat(m[2]) };
      return { x: 0, y: 0 };
    };

    const applyTranslate = (el, x, y) => {
      try {
        const existing = el.getAttribute('transform') || '';
        const withoutTranslate = existing.replace(/translate\([^)]*\)/, '').trim();
        const translate = `translate(${x} ${y})`;
        const newTransform = (translate + ' ' + withoutTranslate).trim();
        el.setAttribute('transform', newTransform);
      } catch (e) {
        // fallback: try CSS transform (may not apply to svg nodes)
        try { el.style.transform = `translate(${x}px, ${y}px)`; } catch (e2) {}
      }
    };

    const setupDragHandlers = (container) => {
      if (!container) return null;
      let active = null;

      const onPointerMove = (ev) => {
        if (!active) return;
        ev.preventDefault();
        const dx = ev.clientX - active.startX;
        const dy = ev.clientY - active.startY;
        const tx = active.startTx + dx;
        const ty = active.startTy + dy;
        active.moved = active.moved || (Math.abs(dx) > 2 || Math.abs(dy) > 2);
        applyTranslate(active.targetEl, tx, ty);
      };

      const onPointerUp = (ev) => {
        if (!active) return;
        // If this was a click (no significant move) and it was an image, dispatch image-clicked
        try {
          if (!active.moved && isImageLike(active.targetEl)) {
            const detail = { node: active.targetEl, side: container === frontContainerRef.current ? 'front' : 'back' };
            const event = new CustomEvent('inkwise-image-clicked', { detail });
            window.dispatchEvent(event);
          }
        } catch (e) {}

        window.removeEventListener('pointermove', onPointerMove);
        window.removeEventListener('pointerup', onPointerUp);
        active = null;
      };

      const onPointerDown = (ev) => {
        const picked = (ev.target && ev.target.closest && ev.target.closest('g, text, image, img, [data-canvas-layer]')) || ev.target;
        if (!picked) return;

        if (isTextLike(picked) || isImageLike(picked)) {
          ev.preventDefault();
          const transformAttr = (picked.getAttribute && picked.getAttribute('transform')) || '';
          const tr = parseTranslate(transformAttr);
          active = {
            pointerId: ev.pointerId,
            startX: ev.clientX,
            startY: ev.clientY,
            startTx: tr.x,
            startTy: tr.y,
            targetEl: picked,
            moved: false,
          };

          if (isTextLike(picked)) {
            // dispatch event so toolbar can react
            const detail = { node: picked, side: container === frontContainerRef.current ? 'front' : 'back' };
            const event = new CustomEvent('inkwise-text-selected', { detail });
            window.dispatchEvent(event);
          }

          window.addEventListener('pointermove', onPointerMove, { passive: false });
          window.addEventListener('pointerup', onPointerUp, { passive: false });
        }
      };

      container.addEventListener('pointerdown', onPointerDown);

      return () => {
        container.removeEventListener('pointerdown', onPointerDown);
        window.removeEventListener('pointermove', onPointerMove);
        window.removeEventListener('pointerup', onPointerUp);
      };
    };

    const frontCleanup = setupDragHandlers(frontContainerRef.current);
    const backCleanup = setupDragHandlers(backContainerRef.current);

    return () => {
      mounted = false;
      // attempt cleanup if editors expose destroy
      [frontEditorRef.current, backEditorRef.current].forEach((inst) => {
        if (!inst) return;
        try {
          if (typeof inst.destroy === 'function') inst.destroy();
        } catch (e) { /* ignore */ }
      });
      if (frontCleanup) frontCleanup();
      if (backCleanup) backCleanup();
    };
  }, [bootstrap]);

  // Apply sizing to any other SVGs shown in the studio page (marked or inside mount)
  useEffect(() => {
    try {
      const selector = '#template-editor-root svg, [data-svg-editor="true"]';
      const nodes = Array.from(document.querySelectorAll(selector));
      nodes.forEach((s) => applySizingToSvg(s));
    } catch (e) { /* ignore */ }
  }, [sizes]);

  // Keep ruler height in sync with actual rendered canvas
  useEffect(() => {
    const updateRulers = () => {
      try {
        const frontStage = frontContainerRef.current;
        const backStage = backContainerRef.current;
        const frontRuler = frontStage?.parentElement?.querySelector('.ruler-vertical');
        const backRuler = backStage?.parentElement?.querySelector('.ruler-vertical');
        if (frontRuler && frontStage) {
          frontRuler.style.height = `${frontStage.clientHeight}px`;
        }
        if (backRuler && backStage) {
          backRuler.style.height = `${backStage.clientHeight}px`;
        }
        // update horizontal rulers
        const frontH = frontStage?.parentElement?.querySelector('.ruler-horizontal');
        const backH = backStage?.parentElement?.querySelector('.ruler-horizontal');
        if (frontH && frontStage) {
          frontH.style.width = `${frontStage.clientWidth}px`;
        }
        if (backH && backStage) {
          backH.style.width = `${backStage.clientWidth}px`;
        }
      } catch (e) { /* ignore */ }
    };

    updateRulers();
    window.addEventListener('resize', updateRulers);
    const ro = new ResizeObserver(updateRulers);
    if (frontContainerRef.current) ro.observe(frontContainerRef.current);
    if (backContainerRef.current) ro.observe(backContainerRef.current);

    return () => {
      window.removeEventListener('resize', updateRulers);
      try { ro.disconnect(); } catch (e) {}
    };
  }, []);

  // --- Wire uploads modal so uploaded images can be inserted into the SVG canvas (supports side selection, drag/drop, canvas drop)
  useEffect(() => {
    const RECENT_UPLOADS_KEY = 'inkwise_recent_uploads';
    const MAX_RECENT_UPLOADS = 12;

    const uploadButton = document.getElementById('upload-button');
    const imageInput = document.getElementById('image-upload');
    const recentUploadsGrid = document.getElementById('recentUploadsGrid');
    const uploadsDropZone = document.getElementById('uploadsDropZone');
    const uploadSideSelect = document.getElementById('upload-side-select');

    // Resolve selected side from toggle buttons (preferred) or the legacy select as fallback
    const getSide = (override) => {
      if (override) return override;
      try {
        const btnFront = document.getElementById('upload-side-front');
        const btnBack = document.getElementById('upload-side-back');
        if (btnFront && btnBack) {
          return btnBack.getAttribute('aria-pressed') === 'true' ? 'back' : 'front';
        }
        if (uploadSideSelect) return uploadSideSelect.value === 'back' ? 'back' : 'front';
      } catch (e) { /* ignore */ }
      return 'front';
    };

    // Set selected side UI (buttons + canvas highlighting)
    const setSelectedSide = (side) => {
      try {
        const btnFront = document.getElementById('upload-side-front');
        const btnBack = document.getElementById('upload-side-back');
        if (btnFront && btnBack) {
          btnFront.classList.toggle('active', side === 'front');
          btnBack.classList.toggle('active', side === 'back');
          btnFront.setAttribute('aria-pressed', side === 'front' ? 'true' : 'false');
          btnBack.setAttribute('aria-pressed', side === 'back' ? 'true' : 'false');
        }
        // update legacy select for compatibility
        if (uploadSideSelect) uploadSideSelect.value = (side === 'back') ? 'back' : 'front';

        // highlight canvas columns
        try {
          const frontCol = frontContainerRef.current && frontContainerRef.current.closest('.template-canvas-column');
          const backCol = backContainerRef.current && backContainerRef.current.closest('.template-canvas-column');
          if (frontCol) frontCol.classList.toggle('upload-selected', side === 'front');
          if (backCol) backCol.classList.toggle('upload-selected', side === 'back');
        } catch (e) {}
      } catch (e) {}
    };

    // Wire side toggle buttons + canvas header clicks
    let sideBtnFront = null, sideBtnBack = null, onSideButtonClick = null;
    let frontHeader = null, backHeader = null, onFrontHeaderClick = null, onBackHeaderClick = null;

    try {
      sideBtnFront = document.getElementById('upload-side-front');
      sideBtnBack = document.getElementById('upload-side-back');
      onSideButtonClick = (ev) => {
        const s = (ev && ev.currentTarget && ev.currentTarget.dataset && ev.currentTarget.dataset.side) ? ev.currentTarget.dataset.side : (ev.currentTarget && ev.currentTarget.id === 'upload-side-back' ? 'back' : 'front');
        setSelectedSide(s);
      };
      if (sideBtnFront) sideBtnFront.addEventListener('click', onSideButtonClick);
      if (sideBtnBack) sideBtnBack.addEventListener('click', onSideButtonClick);

      const frontCol = frontContainerRef.current && frontContainerRef.current.closest('.template-canvas-column');
      const backCol = backContainerRef.current && backContainerRef.current.closest('.template-canvas-column');
      frontHeader = frontCol && frontCol.querySelector('.canvas-header');
      backHeader = backCol && backCol.querySelector('.canvas-header');
      onFrontHeaderClick = () => setSelectedSide('front');
      onBackHeaderClick = () => setSelectedSide('back');
      if (frontHeader) frontHeader.addEventListener('click', onFrontHeaderClick);
      if (backHeader) backHeader.addEventListener('click', onBackHeaderClick);

      // initialize UI to default
      setSelectedSide(getSide());
    } catch (e) { /* ignore */ }

    const getRecentUploads = () => {
      try { const raw = localStorage.getItem(RECENT_UPLOADS_KEY); return raw ? JSON.parse(raw) : []; } catch (e) { return []; }
    };

    const saveRecentUpload = (dataUrl, filename, side = 'front') => {
      const uploads = getRecentUploads();
      const entry = { id: Date.now(), dataUrl, filename, side, timestamp: new Date().toISOString() };
      const filtered = uploads.filter(u => u.filename !== filename || u.dataUrl !== dataUrl);
      filtered.unshift(entry);
      const limited = filtered.slice(0, MAX_RECENT_UPLOADS);
      try { localStorage.setItem(RECENT_UPLOADS_KEY, JSON.stringify(limited)); } catch (e) { try { localStorage.setItem(RECENT_UPLOADS_KEY, JSON.stringify(limited.slice(0, Math.floor(MAX_RECENT_UPLOADS/2)))); } catch (e2) {} }
      renderRecentUploads();
    };

    const removeRecentUploadById = (id) => {
      try {
        const uploads = getRecentUploads().filter(u => String(u.id) !== String(id));
        localStorage.setItem(RECENT_UPLOADS_KEY, JSON.stringify(uploads));
        renderRecentUploads();
      } catch (e) {}
    };

    const findSvgForSide = (side) => {
      const container = side === 'back' ? backContainerRef.current : frontContainerRef.current;
      if (!container) return null;
      return (container.tagName && container.tagName.toLowerCase() === 'svg') ? container : container.querySelector('svg');
    };

    const clientToSvgCoords = (svgEl, clientX, clientY) => {
      try {
        if (!svgEl) return null;
        const pt = svgEl.createSVGPoint();
        pt.x = clientX;
        pt.y = clientY;
        const ctm = svgEl.getScreenCTM && svgEl.getScreenCTM();
        if (ctm && typeof ctm.inverse === 'function') {
          const inv = ctm.inverse();
          const p = pt.matrixTransform(inv);
          return { x: p.x, y: p.y };
        }
        // Fallback: map using bounding box proportions
        const rect = svgEl.getBoundingClientRect();
        const svgW = parseFloat(svgEl.getAttribute('width')) || rect.width;
        const svgH = parseFloat(svgEl.getAttribute('height')) || rect.height;
        const x = (clientX - rect.left) * (svgW / rect.width);
        const y = (clientY - rect.top) * (svgH / rect.height);
        return { x, y };
      } catch (e) { return null; }
    };

    const insertImageIntoSvg = (svgEl, dataUrl, side = 'front', position = null) => {
      if (!svgEl) return null;
      let imageEl = svgEl.querySelector('image');
      if (!imageEl) {
        // Create new
        const viewBox = svgEl.getAttribute('viewBox');
        let centerX = 200, centerY = 200, defaultSize = 150;
        if (viewBox) {
          const parts = viewBox.split(/[ ,\s]+/).map(Number);
          if (parts.length === 4 && parts.every(Number.isFinite)) {
            centerX = parts[0] + parts[2] / 2;
            centerY = parts[1] + parts[3] / 2;
            defaultSize = Math.min(parts[2], parts[3]) * 0.3;
          }
        } else {
          const w = parseFloat(svgEl.getAttribute('width')) || svgEl.clientWidth || 400;
          const h = parseFloat(svgEl.getAttribute('height')) || svgEl.clientHeight || 400;
          centerX = w / 2;
          centerY = h / 2;
          defaultSize = Math.min(w, h) * 0.3;
        }

        const newImageKey = `uploaded_${Date.now()}`;
        imageEl = document.createElementNS('http://www.w3.org/2000/svg', 'image');
        imageEl.setAttribute('id', newImageKey);
        imageEl.setAttribute('data-preview-node', newImageKey);
        imageEl.setAttribute('width', String(defaultSize));
        imageEl.setAttribute('height', String(defaultSize));
        imageEl.setAttribute('preserveAspectRatio', 'xMidYMid meet');
        svgEl.appendChild(imageEl);
      }

      // Update href
      imageEl.setAttribute('href', dataUrl);
      imageEl.setAttributeNS('http://www.w3.org/1999/xlink', 'href', dataUrl);

      // Update position if provided
      if (position && typeof position.x === 'number' && typeof position.y === 'number') {
        const size = parseFloat(imageEl.getAttribute('width')) || 150;
        const xPos = position.x - size / 2;
        const yPos = position.y - size / 2;
        imageEl.setAttribute('x', String(xPos));
        imageEl.setAttribute('y', String(yPos));
      }

      const editorInstance = (side === 'back') ? backEditorRef.current : frontEditorRef.current;
      try {
        if (editorInstance && typeof editorInstance.createBoundingBox === 'function') {
          // Remove existing bounding box if any
          if (imageEl._boundingBox && imageEl._boundingBox.parentNode) {
            imageEl._boundingBox.parentNode.removeChild(imageEl._boundingBox);
          }
          const bbox = editorInstance.createBoundingBox(imageEl);
          if (bbox) {
            imageEl._boundingBox = bbox;
            bbox._targetElement = imageEl;
            const handles = bbox.querySelectorAll('.resize-handle');
            handles.forEach(h => { try { editorInstance.makeResizable(imageEl, h); } catch (e) {} });
            try { editorInstance.makeDraggable(bbox); } catch (e) { try { editorInstance.makeDraggable(imageEl); } catch (e2) {} }
          } else {
            try { editorInstance.makeDraggable(imageEl); } catch (e) {}
          }
        }
      } catch (e) { /* ignore */ }

      try {
        if (typeof window !== 'undefined') window.dispatchEvent(new CustomEvent('inkwise:canvas-changed', { detail: { side } }));
        if (side === 'back') setStatus(s => ({ ...s, back: 'dirty' })); else setStatus(s => ({ ...s, front: 'dirty' }));
      } catch (e) {}

      return imageEl;
    };

    const applyUploadedImage = (dataUrl, filename, side = 'front', position = null) => {
      if (!dataUrl) return null;
      saveRecentUpload(dataUrl, filename || `upload-${Date.now()}`, side);
      const svgEl = findSvgForSide(side);
      let inserted = null;
      if (svgEl) {
        inserted = insertImageIntoSvg(svgEl, dataUrl, side, position);
      } else {
        const frontSvg = findSvgForSide('front'); if (frontSvg) inserted = insertImageIntoSvg(frontSvg, dataUrl, 'front', position);
      }
      try {
        if (inserted && inserted.id) showConfirmation(side, filename, inserted.id);
      } catch (e) {}
      return inserted;
    };

    const handleFile = (file, side = getSide(), position = null) => {
      if (!file) return;
      if (!file.type || !file.type.startsWith('image/')) { alert('Only image files are supported.'); return; }
      const reader = new FileReader();
      reader.onload = (ev) => { const dataUrl = ev?.target?.result; if (!dataUrl) return; applyUploadedImage(dataUrl, file.name, side, position); };
      reader.readAsDataURL(file);
    };

    const handleFilesList = (fileList, side = getSide(), positionForFirst = null) => {
      if (!fileList) return;
      const files = Array.from(fileList);
      files.forEach((f, i) => handleFile(f, side, i === 0 ? positionForFirst : null));
    };

    // preview and confirmation UI elements
    let previewEl = null;
    const ensurePreviewEl = () => {
      if (previewEl) return previewEl;
      previewEl = document.createElement('div');
      previewEl.id = 'upload-preview';
      previewEl.setAttribute('aria-hidden', 'true');
      previewEl.style.position = 'fixed';
      previewEl.style.pointerEvents = 'none';
      previewEl.style.zIndex = '5000';
      previewEl.style.display = 'none';
      previewEl.style.transition = 'opacity 120ms ease';
      previewEl.style.opacity = '0';
      previewEl.innerHTML = '<img alt="Preview" style="display:block;max-width:320px;max-height:320px;border-radius:8px;box-shadow:0 10px 30px rgba(0,0,0,0.25)" />';
      document.body.appendChild(previewEl);
      return previewEl;
    };

    const showPreview = (src, clientX, clientY) => {
      try {
        const el = ensurePreviewEl();
        const img = el.querySelector('img');
        img.src = src;
        el.style.display = 'block';
        const x = Math.min(window.innerWidth - 340, clientX + 12);
        const y = Math.min(window.innerHeight - 340, clientY + 12);
        el.style.left = `${x}px`;
        el.style.top = `${y}px`;
        requestAnimationFrame(() => { el.style.opacity = '1'; });
      } catch (e) {}
    };
    const hidePreview = () => { try { if (!previewEl) return; previewEl.style.opacity = '0'; setTimeout(() => { if (previewEl) previewEl.style.display = 'none'; }, 150); } catch (e) {} };

    const showConfirmation = (side, filename, insertedElementId) => {
      try {
        const id = `upload-confirm-${Date.now()}`;
        const wrapper = document.createElement('div');
        wrapper.className = 'upload-confirmation';
        wrapper.id = id;
        wrapper.style.position = 'fixed';
        wrapper.style.right = '16px';
        wrapper.style.bottom = '16px';
        wrapper.style.background = '#111827';
        wrapper.style.color = '#fff';
        wrapper.style.padding = '10px 12px';
        wrapper.style.borderRadius = '8px';
        wrapper.style.boxShadow = '0 8px 30px rgba(2,6,23,0.4)';
        wrapper.style.zIndex = '6000';
        wrapper.style.display = 'flex';
        wrapper.style.alignItems = 'center';
        wrapper.style.gap = '10px';

        const text = document.createElement('span');
        text.textContent = `Inserted image to ${side === 'back' ? 'Back' : 'Front'}` + (filename ? ` — ${filename}` : '');
        text.style.fontSize = '13px';
        wrapper.appendChild(text);

        if (insertedElementId) {
          const undo = document.createElement('button');
          undo.textContent = 'Undo';
          undo.style.background = 'transparent';
          undo.style.border = '1px solid rgba(255,255,255,0.12)';
          undo.style.color = '#fff';
          undo.style.padding = '6px 8px';
          undo.style.borderRadius = '6px';
          undo.style.cursor = 'pointer';
          undo.addEventListener('click', () => {
            try { const el = document.getElementById(insertedElementId); if (el && el.parentNode) el.parentNode.removeChild(el); } catch (e) {}
            try { if (wrapper && wrapper.parentNode) wrapper.parentNode.removeChild(wrapper); } catch (e) {}
          });
          wrapper.appendChild(undo);
        }

        document.body.appendChild(wrapper);
        setTimeout(() => { try { if (wrapper && wrapper.parentNode) wrapper.parentNode.removeChild(wrapper); } catch (e) {} }, 4000);
      } catch (e) {}
    };

    const renderRecentUploads = () => {
      if (!recentUploadsGrid) return;
      const uploads = getRecentUploads();
      if (!uploads || uploads.length === 0) {
        recentUploadsGrid.innerHTML = `\n          <div class="no-recent-uploads">\n            <p>No recent uploads found. Upload some images above to see them here.</p>\n          </div>\n        `;
        return;
      }

      recentUploadsGrid.innerHTML = '';
      uploads.forEach((u) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'recent-upload-item';
        wrapper.dataset.uploadId = u.id;
        wrapper.dataset.imageUrl = u.dataUrl;
        wrapper.dataset.side = u.side || 'front';

        const img = document.createElement('img');
        img.src = u.dataUrl;
        img.alt = u.filename || '';
        img.loading = 'lazy';
        img.style.width = '64px';
        img.style.height = '64px';
        img.style.objectFit = 'cover';
        img.style.cursor = 'pointer';
        img.title = 'Insert image to canvas';
        img.setAttribute('tabindex', '0');
        img.setAttribute('aria-label', 'Insert image to canvas');
        img.addEventListener('click', () => { applyUploadedImage(u.dataUrl, u.filename, u.side || 'front'); });
        img.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); applyUploadedImage(u.dataUrl, u.filename, u.side || 'front'); } });
        img.addEventListener('mouseenter', (ev) => { try { showPreview(u.dataUrl, ev.clientX, ev.clientY); } catch (e) {} });
        img.addEventListener('mousemove', (ev) => { try { showPreview(u.dataUrl, ev.clientX, ev.clientY); } catch (e) {} });
        img.addEventListener('mouseleave', () => { hidePreview(); });

        const removeBtn = document.createElement('button');
        removeBtn.className = 'recent-upload-remove';
        removeBtn.title = 'Remove upload';
        removeBtn.textContent = '✕';
        removeBtn.style.marginLeft = '6px';
        removeBtn.addEventListener('click', () => { removeRecentUploadById(u.id); });

        wrapper.appendChild(img);
        wrapper.appendChild(removeBtn);
        recentUploadsGrid.appendChild(wrapper);
      });
    };

    // attach handlers
    const onUploadClick = () => { if (imageInput) imageInput.click(); };
    const onImageChange = (ev) => { const files = ev.target && ev.target.files ? ev.target.files : null; if (files && files.length) { handleFilesList(files, getSide()); ev.target.value = ''; } };

    const dropHighlightClass = 'uploads-dropzone--active';

    const onUploadsDrop = (ev) => {
      ev.preventDefault();
      ev.stopPropagation();
      try { uploadsDropZone && uploadsDropZone.classList.remove(dropHighlightClass); } catch (e) {}
      const files = ev.dataTransfer && ev.dataTransfer.files ? ev.dataTransfer.files : null;
      if (files && files.length) { handleFilesList(files, getSide()); return; }
      // attempt urls
      try {
        const uri = ev.dataTransfer && (ev.dataTransfer.getData('text/uri-list') || ev.dataTransfer.getData('text/plain'));
        if (uri) {
          // fetch remote image as blob then read as dataURL
          fetch(uri).then(r => r.blob()).then(b => {
            const f = new File([b], (uri.split('/').pop() || 'remote-image'), { type: b.type });
            handleFile(f, getSide());
          }).catch(() => {});
        }
      } catch (e) {}
    };

    const onDragOverZone = (ev) => { ev.preventDefault(); ev.stopPropagation(); try { uploadsDropZone && uploadsDropZone.classList.add(dropHighlightClass); } catch (e) {} };
    const onDragLeaveZone = (ev) => { try { uploadsDropZone && uploadsDropZone.classList.remove(dropHighlightClass); } catch (e) {} };

    if (uploadsDropZone) {
      // click intentionally disabled to avoid acting like a button; drag/drop remains active
      uploadsDropZone.addEventListener('dragover', onDragOverZone);
      uploadsDropZone.addEventListener('dragenter', onDragOverZone);
      uploadsDropZone.addEventListener('dragleave', onDragLeaveZone);
      uploadsDropZone.addEventListener('drop', onUploadsDrop);
    }

    // allow dropping directly onto canvas containers
    const canvasDropHandler = (container, side) => {
      const onCanvasDragOver = (ev) => { ev.preventDefault(); ev.dataTransfer.dropEffect = 'copy'; container.style.outline = '2px dashed #2563eb'; };
      const onCanvasDragLeave = (ev) => { try { container.style.outline = ''; } catch (e) {} };
      const onCanvasDrop = (ev) => {
        ev.preventDefault();
        try { container.style.outline = ''; } catch (e) {}
        const files = ev.dataTransfer && ev.dataTransfer.files ? ev.dataTransfer.files : null;
        const svgEl = findSvgForSide(side);
        let position = null;
        if (svgEl) {
          position = clientToSvgCoords(svgEl, ev.clientX, ev.clientY);
        }
        if (files && files.length) {
          handleFilesList(files, side, position);
          return;
        }
        try {
          const uri = ev.dataTransfer && (ev.dataTransfer.getData('text/uri-list') || ev.dataTransfer.getData('text/plain'));
          if (uri && svgEl) {
            fetch(uri).then(r => r.blob()).then(b => {
              const f = new File([b], (uri.split('/').pop() || 'remote-image'), { type: b.type });
              handleFile(f, side, position);
            }).catch(() => {});
          }
        } catch (e) {}
      };
      container.addEventListener('dragover', onCanvasDragOver);
      container.addEventListener('dragleave', onCanvasDragLeave);
      container.addEventListener('drop', onCanvasDrop);
      return () => {
        container.removeEventListener('dragover', onCanvasDragOver);
        container.removeEventListener('dragleave', onCanvasDragLeave);
        container.removeEventListener('drop', onCanvasDrop);
      };
    };

    const frontContainer = frontContainerRef.current;
    const backContainer = backContainerRef.current;
    const frontCanvasCleanup = frontContainer ? canvasDropHandler(frontContainer, 'front') : null;
    const backCanvasCleanup = backContainer ? canvasDropHandler(backContainer, 'back') : null;

    if (uploadButton) uploadButton.addEventListener('click', onUploadClick);
    if (imageInput) imageInput.addEventListener('change', onImageChange);

    renderRecentUploads();

    return () => {
      if (uploadButton) uploadButton.removeEventListener('click', onUploadClick);
      if (imageInput) imageInput.removeEventListener('change', onImageChange);
      if (uploadsDropZone) {
        // click removal omitted (click not attached)
        uploadsDropZone.removeEventListener('dragover', onDragOverZone);
        uploadsDropZone.removeEventListener('dragenter', onDragOverZone);
        uploadsDropZone.removeEventListener('dragleave', onDragLeaveZone);
        uploadsDropZone.removeEventListener('drop', onUploadsDrop);
      }
      if (frontCanvasCleanup) frontCanvasCleanup();
      if (backCanvasCleanup) backCanvasCleanup();

      // remove side toggle and header listeners if they exist
      try {
        const sFront = document.getElementById('upload-side-front');
        const sBack = document.getElementById('upload-side-back');
        if (sFront && typeof onSideButtonClick === 'function') sFront.removeEventListener('click', onSideButtonClick);
        if (sBack && typeof onSideButtonClick === 'function') sBack.removeEventListener('click', onSideButtonClick);
        if (frontHeader && typeof onFrontHeaderClick === 'function') frontHeader.removeEventListener('click', onFrontHeaderClick);
        if (backHeader && typeof onBackHeaderClick === 'function') backHeader.removeEventListener('click', onBackHeaderClick);
      } catch (e) {}

      // cleanup preview element
      try { if (previewEl && previewEl.parentNode) previewEl.parentNode.removeChild(previewEl); previewEl = null; } catch (e) {}
    };
  }, []);

  const handleSave = (side) => {
    const inst = side === 'front' ? frontEditorRef.current : backEditorRef.current;
    const templateId = bootstrap.template?.id ?? null;
    if (inst && typeof inst.saveSvg === 'function') {
      void inst.saveSvg(templateId, side).then(() => {
        setStatus(s => ({ ...s, [side]: 'saved' }));
      }).catch((e) => {
        console.error('[TemplateEditor] save failed', e);
        setStatus(s => ({ ...s, [side]: 'error' }));
      });
    }
  };

  return (
    <div className="template-editor two-column">
      <div className="template-canvas-column">
        <div className="canvas-header">Front Canvas</div>
          <div className="canvas-with-ruler">
            <div className="ruler-vertical" aria-hidden="true">
              <div className="ruler-line"></div>
              <div className="ruler-label">{sizes.front.height ? formatSizeLabel(sizes.front.height, sizes.front.unit) : ''}</div>
            </div>
            <div className="guide-vertical" aria-hidden="true"></div>
            <div className="canvas-body">
              <div className="canvas-stage" id="template-editor-container-front" ref={frontContainerRef}>
                {/* SVG markup will be injected here or an existing <svg> will be used */}
              </div>
              <div className="ruler-horizontal" aria-hidden="true">
                <div className="ruler-line-horizontal"></div>
                <div className="ruler-label-horizontal">
                  {formatSizeLabel(sizes.front.width, sizes.front.unit)}
                </div>
              </div>
            </div>
          </div>
        
      </div>
      <div className="template-canvas-column">
        <div className="canvas-header">Back Canvas</div>
          <div className="canvas-with-ruler">
            <div className="ruler-vertical" aria-hidden="true">
              <div className="ruler-line"></div>
              <div className="ruler-label">{sizes.back.height ? formatSizeLabel(sizes.back.height, sizes.back.unit) : ''}</div>
            </div>
            <div className="guide-vertical" aria-hidden="true"></div>
            <div className="canvas-body">
              <div className="canvas-stage" id="template-editor-container-back" ref={backContainerRef}>
              </div>
              <div className="ruler-horizontal" aria-hidden="true">
                <div className="ruler-line-horizontal"></div>
                <div className="ruler-label-horizontal">
                  {formatSizeLabel(sizes.back.width, sizes.back.unit)}
                </div>
              </div>
            </div>
          </div>
        
      </div>
    </div>
  );
}

export default TemplateEditor;
