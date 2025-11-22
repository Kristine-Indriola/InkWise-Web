import SvgTemplateEditor from './svg-template-editor.jsx';
import { createAutosaveController } from './autosave';

export function initializeCustomerStudioLegacy() {
  if (typeof window !== 'undefined') {
    if (window.__inkwiseCustomerStudioInitialized) {
      return;
    }
    window.__inkwiseCustomerStudioInitialized = true;
  }
  const init = () => {
    const inputs = document.querySelectorAll('[data-preview-target]');
    const cardBg = document.querySelector('.preview-card-bg');
    const previewSvg = document.getElementById('preview-svg');
    const thumbButtons = document.querySelectorAll('[data-card-thumb]');
    const viewButtons = document.querySelectorAll('[data-card-view]');
    const extraPreviewContainer = document.getElementById('extraPreviewContainer');
    const textFieldList = document.getElementById('textFieldList');
    const addFieldBtn = document.querySelector('[data-add-text-field]');
    const navButtons = document.querySelectorAll('.sidenav-btn');
    const previewStage = document.querySelector('.canvas-stage');
    const measureWidthEl = document.querySelector('.canvas-measure-horizontal .measure-value');
    const measureHeightEl = document.querySelector('.canvas-measure-vertical .measure-value');
    const canvasWrapper = document.querySelector('.preview-canvas-wrapper');
    let customIndex = 1;

    const bootstrapPayload = (typeof window !== 'undefined' && window.inkwiseStudioBootstrap)
      ? window.inkwiseStudioBootstrap
      : {};

    const routes = bootstrapPayload?.routes ?? {};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      || document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      || null;
    const statusLabelEl = document.querySelector('.topbar-status-label');
    const statusDotEl = document.querySelector('.topbar-status-dot');

    const parseNullableNumber = (candidate) => {
      if (candidate === null || candidate === undefined || candidate === '') {
        return null;
      }
      const value = Number(candidate);
      return Number.isFinite(value) ? value : null;
    };

    const svgToDataUri = (svgMarkup) => {
      if (!svgMarkup || typeof svgMarkup !== 'string') {
        return null;
      }
      try {
        const normalized = svgMarkup.startsWith('<?xml') ? svgMarkup : `<?xml version="1.0" encoding="UTF-8"?>${svgMarkup}`;
        return `data:image/svg+xml;base64,${window.btoa(unescape(encodeURIComponent(normalized)))}`;
      } catch (error) {
        console.warn('[InkWise Studio] Failed to encode SVG preview.', error);
        return null;
      }
    };

    const extractBackgroundUrl = (side) => {
      if (!cardBg) {
        return null;
      }
      const computed = window.getComputedStyle(cardBg);
      const background = computed?.backgroundImage || '';
      const match = background.match(/url\((['"]?)(.*?)\1\)/i);
      if (match && match[2]) {
        return match[2];
      }
      const datasetKey = side ? `${side}Image` : null;
      if (datasetKey && cardBg.dataset?.[datasetKey]) {
        return cardBg.dataset[datasetKey];
      }
      return cardBg.dataset?.frontImage || null;
    };

    const collectTextEntries = () => {
      if (!textFieldList) {
        return [];
      }

      return Array.from(textFieldList.querySelectorAll('input[data-preview-target]')).map((input) => {
        const wrapper = input.closest('.text-field-item');
        const label = wrapper?.querySelector('.text-field-label')?.textContent?.trim()
          || input.dataset.previewTarget
          || '';
        return {
          key: input.dataset.previewTarget || '',
          label,
          value: input.value || '',
          defaultValue: input.dataset.defaultValue || '',
        };
      });
    };

    const parseNumericDimension = (value) => {
      if (typeof value === 'number') {
        return Number.isFinite(value) ? value : Number.NaN;
      }

      if (typeof value === 'string') {
        const trimmed = value.trim();
        if (!trimmed) {
          return Number.NaN;
        }

        const numericMatch = trimmed.match(/^[-+]?\d*\.?\d+(?:[eE][-+]?\d+)?/);
        if (!numericMatch) {
          return Number.NaN;
        }

        return parseFloat(numericMatch[0]);
      }

      return Number.NaN;
    };

    const normalizeCanvasSettings = (candidate) => {
      if (!candidate) {
        return null;
      }

      if (typeof candidate === 'string') {
        const trimmed = candidate.trim();
        if (!trimmed) {
          return null;
        }

        try {
          const parsedValue = JSON.parse(trimmed);
          return normalizeCanvasSettings(parsedValue);
        } catch (error) {
          return null;
        }
      }

      if (typeof candidate !== 'object') {
        return null;
      }

      const bag = Array.isArray(candidate)
        ? candidate.reduce((acc, item) => {
            if (item && typeof item === 'object') {
              return Object.assign(acc, item);
            }
            return acc;
          }, {})
        : Object.assign({}, candidate);

      const widthValue = bag.width ?? bag.Width ?? bag.canvasWidth ?? bag.w ?? bag.maxWidth ?? bag.naturalWidth;
      const heightValue = bag.height ?? bag.Height ?? bag.canvasHeight ?? bag.h ?? bag.maxHeight ?? bag.naturalHeight;

      const width = parseNumericDimension(widthValue);
      const height = parseNumericDimension(heightValue);

      if (!Number.isFinite(width) || width <= 0 || !Number.isFinite(height) || height <= 0) {
        return null;
      }

      const shapeRaw = bag.shape ?? bag.Shape ?? bag.canvasShape ?? null;
      const unitRaw = bag.unit ?? bag.Unit ?? bag.canvasUnit ?? null;

      return {
        width,
        height,
        shape: typeof shapeRaw === 'string' && shapeRaw.trim() !== '' ? shapeRaw.trim() : null,
        unit: typeof unitRaw === 'string' && unitRaw.trim() !== '' ? unitRaw.trim() : 'px',
      };
    };

    const convertToInches = (value, unit = 'px') => {
      if (!Number.isFinite(value)) {
        return Number.NaN;
      }

      const normalizedUnit = (unit || 'px').toString().toLowerCase();

      switch (normalizedUnit) {
        case 'in':
        case 'inch':
        case 'inches':
          return value;
        case 'mm':
          return value / 25.4;
        case 'cm':
          return value / 2.54;
        case 'pt':
          return value / 72;
        default:
          return value / 96;
      }
    };

    const datasetCanvas = normalizeCanvasSettings(canvasWrapper?.dataset);
    const bootstrapCanvas = normalizeCanvasSettings(bootstrapPayload?.template?.canvas ?? null);
    let templateCanvasPreference = bootstrapCanvas || datasetCanvas || null;

    const MAX_DISPLAY_WIDTH = 720;
    const MAX_DISPLAY_HEIGHT = 920;
    const rasterDimensionCache = new Map();

    let overlayLayer = null;
    const overlayDataByNode = new Map();
    const overlayDataByElement = new Map();
    let overlaySyncFrame = null;
    let activeInteraction = null;
    let currentSvgRoot = null;
    let svgTemplateEditorInstance = null;
    let openTextModalAndFocusRef = () => {};
    let currentSide = 'front';
    let autosave = null;

    const collectDesignSnapshot = () => {
      if (!cardBg) {
        return null;
      }

      const timestamp = new Date();
      const activeSide = currentSide || 'front';
      const svgNode = currentSvgRoot;

      let serializedSvg = null;
      if (svgNode) {
        try {
          const serializer = new XMLSerializer();
          serializedSvg = serializer.serializeToString(svgNode);
          if (serializedSvg && !/xmlns=/i.test(serializedSvg)) {
            serializedSvg = serializedSvg.replace('<svg', '<svg xmlns="http://www.w3.org/2000/svg"');
          }
        } catch (error) {
          console.warn('[InkWise Studio] Unable to serialize current SVG.', error);
          serializedSvg = null;
        }
      }

      const previewImage = serializedSvg ? svgToDataUri(serializedSvg) : extractBackgroundUrl(activeSide);

      const texts = collectTextEntries();
      const placeholderLabels = texts
        .filter((entry) => {
          const value = (entry.value || '').trim();
          const defaultValue = (entry.defaultValue || '').trim();
          if (value === '') {
            return true;
          }
          if (defaultValue !== '' && value === defaultValue) {
            return true;
          }
          return false;
        })
        .map((entry) => entry.label || entry.key)
        .map((label) => (label || '').trim())
        .filter((label, index, array) => label !== '' && array.indexOf(label) === index);

      const imageEntries = [];
      if (svgNode) {
        const seen = new Set();
        svgNode.querySelectorAll('image,img,[data-changeable="image"]').forEach((node) => {
          const href = node.getAttribute('href')
            || node.getAttribute('xlink:href')
            || node.getAttributeNS('http://www.w3.org/1999/xlink', 'href')
            || node.getAttribute('data-src');
          if (!href) {
            return;
          }
          const key = node.getAttribute('data-preview-node') || node.id || `image-${imageEntries.length + 1}`;
          if (seen.has(key)) {
            return;
          }
          seen.add(key);
          imageEntries.push({
            key,
            href,
            x: node.getAttribute('x') || null,
            y: node.getAttribute('y') || null,
            width: node.getAttribute('width') || null,
            height: node.getAttribute('height') || null,
          });
        });
      }

      const canvasMeta = cardBg ? {
        width: parseNullableNumber(cardBg.dataset.canvasWidth),
        height: parseNullableNumber(cardBg.dataset.canvasHeight),
        unit: cardBg.dataset.canvasUnit || null,
        shape: cardBg.dataset.canvasShape || null,
      } : null;

      const previewImages = previewImage ? [previewImage] : [];

      return {
        design: {
          updated_at: timestamp.toISOString(),
          sides: {
            [activeSide]: {
              svg: serializedSvg,
              preview: previewImage,
            },
          },
          texts,
          images: imageEntries,
          canvas: canvasMeta,
        },
        preview: {
          image: previewImage,
          images: previewImages,
        },
        placeholders: placeholderLabels,
      };
    };

    try {
      autosave = createAutosaveController({
        collectSnapshot: collectDesignSnapshot,
        routes,
        csrfToken,
        statusLabel: statusLabelEl,
        statusDot: statusDotEl,
      });
    } catch (autosaveError) {
      console.warn('[InkWise Studio] Autosave controller failed to initialize.', autosaveError);
      autosave = null;
    }

    const proceedButton = document.querySelector('[data-action="proceed-review"]');
    if (proceedButton) {
      const destination = proceedButton.dataset.destination
        || routes.review
        || proceedButton.getAttribute('href')
        || window.location.href;

      proceedButton.addEventListener('click', async (event) => {
        event.preventDefault();
        if (proceedButton.dataset.navigating === '1') {
          return;
        }

        proceedButton.dataset.navigating = '1';
        proceedButton.disabled = true;

        const navigate = () => {
          window.location.href = destination;
        };

        if (!autosave) {
          navigate();
          return;
        }

        try {
          await autosave.flush('navigate');
          navigate();
        } catch (navigationError) {
          console.error('[InkWise Studio] Unable to save before navigating.', navigationError);
          autosave.notifyError();
          proceedButton.disabled = false;
          proceedButton.dataset.navigating = '0';
        }
      });
    }

    const requestImageReplacement = (node) => {
      const input = document.createElement('input');
      input.type = 'file';
      input.accept = 'image/*';
      input.onchange = (evt) => {
        const file = evt.target.files && evt.target.files[0];
        if (!file) {
          return;
        }
        const reader = new FileReader();
        reader.onload = (ev) => {
          const dataUrl = ev.target?.result;
          if (!dataUrl) {
            return;
          }
          if (node instanceof SVGImageElement) {
            node.setAttributeNS('http://www.w3.org/1999/xlink', 'href', dataUrl);
            node.setAttribute('href', dataUrl);
          } else if (node instanceof HTMLImageElement) {
            node.src = dataUrl;
          }
          scheduleOverlaySync();
          autosave?.schedule('image-replace');
        };
        reader.readAsDataURL(file);
      };
      input.click();
    };

    const ensureOverlayLayer = () => {
      if (!cardBg) {
        return null;
      }

      if (!overlayLayer || !overlayLayer.isConnected) {
        overlayLayer = document.createElement('div');
        overlayLayer.className = 'canvas-overlay-layer';
        cardBg.appendChild(overlayLayer);
      }

      return overlayLayer;
    };

    const clearOverlays = () => {
      overlayDataByNode.forEach(({ overlay }) => {
        if (overlay && overlay.parentNode) {
          overlay.parentNode.removeChild(overlay);
        }
      });
      overlayDataByNode.clear();
      overlayDataByElement.clear();
      if (overlayLayer) {
        overlayLayer.innerHTML = '';
      }
    };

    const parseFirstNumeric = (value, fallback) => {
      if (value === null || value === undefined) {
        return fallback;
      }
      const parts = value.toString().split(/[\s,]+/).filter(Boolean);
      if (parts.length === 0) {
        return fallback;
      }
      const numeric = parseFloat(parts[0]);
      return Number.isFinite(numeric) ? numeric : fallback;
    };

    const syncOverlayForNode = (node) => {
      if (!cardBg) {
        return;
      }
      const data = overlayDataByNode.get(node);
      if (!data || !data.overlay) {
        return;
      }
      const overlay = data.overlay;
      const canvasRect = cardBg.getBoundingClientRect();
      const nodeRect = node.getBoundingClientRect();

      if (canvasRect.width === 0 || canvasRect.height === 0) {
        return;
      }

      const minSize = data.type === 'text' ? 18 : 12;
      const targetWidth = Math.max(minSize, nodeRect.width);
      const targetHeight = Math.max(minSize, nodeRect.height);

      overlay.style.left = `${nodeRect.left - canvasRect.left}px`;
      overlay.style.top = `${nodeRect.top - canvasRect.top}px`;
      overlay.style.width = `${targetWidth}px`;
      overlay.style.height = `${targetHeight}px`;
    };

    const syncAllOverlays = () => {
      overlayDataByNode.forEach((_data, node) => {
        syncOverlayForNode(node);
      });
    };

    const scheduleOverlaySync = () => {
      if (overlayDataByNode.size === 0) {
        return;
      }
      if (overlaySyncFrame) {
        cancelAnimationFrame(overlaySyncFrame);
      }
      overlaySyncFrame = window.requestAnimationFrame(() => {
        overlaySyncFrame = null;
        syncAllOverlays();
      });
    };

    const settleOverlaySync = (remaining = 3) => {
      if (remaining <= 0) {
        return;
      }
      requestAnimationFrame(() => {
        scheduleOverlaySync();
        settleOverlaySync(remaining - 1);
      });
    };

    window.addEventListener('resize', scheduleOverlaySync);
    document.addEventListener('scroll', scheduleOverlaySync, true);
    window.addEventListener('load', scheduleOverlaySync);

    const toSvgPoint = (clientX, clientY) => {
      const svg = currentSvgRoot || previewSvg;
      if (!svg || typeof svg.createSVGPoint !== 'function') {
        return null;
      }
      const point = svg.createSVGPoint();
      point.x = clientX;
      point.y = clientY;
      const matrix = svg.getScreenCTM();
      if (!matrix) {
        return null;
      }
      return point.matrixTransform(matrix.inverse());
    };

    const createInteractionState = (data, handle, event) => {
      const pointer = toSvgPoint(event.clientX, event.clientY);
      if (!pointer) {
        return null;
      }

      const { node } = data;
      const bbox = node.getBBox ? node.getBBox() : { x: 0, y: 0, width: 0, height: 0 };

      const state = {
        data,
        overlay: data.overlay,
        node,
        type: data.type,
        handle,
        mode: handle ? 'resize' : 'move',
        pointerId: event.pointerId,
        startPoint: pointer,
        startClient: { x: event.clientX, y: event.clientY },
        initialBBox: bbox,
        moved: false,
      };

      if (data.type === 'text') {
        state.fontSize = parseFloat(node.getAttribute('font-size'))
          || parseFloat(window.getComputedStyle(node).fontSize)
          || 16;
        state.x = parseFirstNumeric(node.getAttribute('x'), bbox.x);
        const fallbackY = bbox.y + bbox.height;
        state.y = parseFirstNumeric(node.getAttribute('y'), fallbackY);
      } else {
        state.x = parseFirstNumeric(node.getAttribute('x'), bbox.x);
        state.y = parseFirstNumeric(node.getAttribute('y'), bbox.y);
        state.width = parseFirstNumeric(node.getAttribute('width'), Math.max(1, bbox.width));
        state.height = parseFirstNumeric(node.getAttribute('height'), Math.max(1, bbox.height));
      }

      return state;
    };

    const applyMove = (state, dx, dy) => {
      if (state.type === 'text') {
        const newX = state.x + dx;
        const newY = state.y + dy;
        if (Number.isFinite(newX)) {
          state.node.setAttribute('x', newX);
        }
        if (Number.isFinite(newY)) {
          state.node.setAttribute('y', newY);
        }
      } else {
        state.node.setAttribute('x', state.x + dx);
        state.node.setAttribute('y', state.y + dy);
      }
    };

    const applyResize = (state, dx, dy) => {
      if (state.type === 'image') {
        let newX = state.x;
        let newY = state.y;
        let newWidth = state.width;
        let newHeight = state.height;

        if (state.handle.includes('e')) {
          newWidth = state.width + dx;
        }
        if (state.handle.includes('w')) {
          newWidth = state.width - dx;
          newX = state.x + dx;
        }
        if (state.handle.includes('s')) {
          newHeight = state.height + dy;
        }
        if (state.handle.includes('n')) {
          newHeight = state.height - dy;
          newY = state.y + dy;
        }

        const MIN_SIZE = 4;
        if (newWidth < MIN_SIZE) {
          newX += newWidth - MIN_SIZE;
          newWidth = MIN_SIZE;
        }
        if (newHeight < MIN_SIZE) {
          newY += newHeight - MIN_SIZE;
          newHeight = MIN_SIZE;
        }

        state.node.setAttribute('x', newX);
        state.node.setAttribute('y', newY);
        state.node.setAttribute('width', newWidth);
        state.node.setAttribute('height', newHeight);
      } else if (state.type === 'text') {
        const initialWidth = Math.max(1, state.initialBBox.width);
        const initialHeight = Math.max(1, state.initialBBox.height);

        let widthDelta = 0;
        let heightDelta = 0;

        if (state.handle.includes('e')) {
          widthDelta = dx;
        } else if (state.handle.includes('w')) {
          widthDelta = -dx;
        }

        if (state.handle.includes('s')) {
          heightDelta = dy;
        } else if (state.handle.includes('n')) {
          heightDelta = -dy;
        }

        const newWidth = Math.max(4, initialWidth + widthDelta);
        const newHeight = Math.max(4, initialHeight + heightDelta);
        const scaleX = newWidth / initialWidth;
        const scaleY = newHeight / initialHeight;
        const scale = Math.max(scaleX, scaleY);
        const newFontSize = Math.max(6, state.fontSize * scale);
        state.node.setAttribute('font-size', newFontSize);
      }
    };

    const handleOverlayPointerMove = (event) => {
      if (!activeInteraction || event.pointerId !== activeInteraction.pointerId) {
        return;
      }

      const pointer = toSvgPoint(event.clientX, event.clientY);
      if (!pointer) {
        return;
      }

      const dx = pointer.x - activeInteraction.startPoint.x;
      const dy = pointer.y - activeInteraction.startPoint.y;

      if (!activeInteraction.moved && (Math.abs(dx) > 0.5 || Math.abs(dy) > 0.5)) {
        activeInteraction.moved = true;
      }

      if (activeInteraction.mode === 'move') {
        applyMove(activeInteraction, dx, dy);
      } else {
        applyResize(activeInteraction, dx, dy);
      }

      scheduleOverlaySync();
    };

    const handleOverlayPointerUp = (event) => {
      if (!activeInteraction || event.pointerId !== activeInteraction.pointerId) {
        return;
      }

      try {
        activeInteraction.overlay.releasePointerCapture(activeInteraction.pointerId);
      } catch (captureError) {
        // ignore pointer capture errors
      }

      activeInteraction.overlay.classList.remove('editor-overlay--active');
      document.removeEventListener('pointermove', handleOverlayPointerMove);
      document.removeEventListener('pointerup', handleOverlayPointerUp);
      document.removeEventListener('pointercancel', handleOverlayPointerUp);

      const interaction = activeInteraction;
      activeInteraction = null;

      scheduleOverlaySync();

      if (!interaction.moved) {
        if (interaction.type === 'text') {
          openTextModalAndFocusRef(interaction.data.previewKey);
        } else if (interaction.type === 'image') {
          requestImageReplacement(interaction.node);
        }
      } else {
        autosave?.schedule('element-transform');
      }
    };

    const handleOverlayPointerDown = (event) => {
      if (!event.isPrimary || event.button !== 0) {
        return;
      }

      const overlay = event.target.closest('.editor-overlay');
      if (!overlay) {
        return;
      }

      const data = overlayDataByElement.get(overlay);
      if (!data) {
        return;
      }

      const state = createInteractionState(data, event.target.dataset.handle || null, event);
      if (!state) {
        return;
      }

      event.preventDefault();

      activeInteraction = state;
      overlay.classList.add('editor-overlay--active');

      try {
        overlay.setPointerCapture(event.pointerId);
      } catch (captureError) {
        // ignore pointer capture errors
      }

      document.addEventListener('pointermove', handleOverlayPointerMove);
      document.addEventListener('pointerup', handleOverlayPointerUp);
      document.addEventListener('pointercancel', handleOverlayPointerUp);
    };

    const createOverlayForNode = (node) => {
      const layer = ensureOverlayLayer();
      if (!layer) {
        return null;
      }

      let data = overlayDataByNode.get(node);
      if (data) {
        return data;
      }

      const nodeName = typeof node.tagName === 'string' ? node.tagName.toLowerCase() : '';
      const type = nodeName === 'image' || nodeName === 'img' ? 'image' : 'text';
      const previewKey = node.getAttribute('data-preview-node') || '';

      const overlay = document.createElement('div');
      overlay.className = `editor-overlay editor-overlay--${type}`;
      overlay.dataset.previewNode = previewKey;
      overlay.tabIndex = 0;
      overlay.style.touchAction = 'none';

      const handles = ['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'];
      handles.forEach((handle) => {
        const handleEl = document.createElement('div');
        handleEl.className = `editor-overlay__handle editor-overlay__handle--${handle}`;
        handleEl.dataset.handle = handle;
        overlay.appendChild(handleEl);
      });

      overlay.addEventListener('pointerdown', handleOverlayPointerDown);
      overlay.addEventListener('dblclick', () => {
        if (type === 'text') {
          openTextModalAndFocusRef(previewKey);
        } else if (type === 'image') {
          requestImageReplacement(node);
        }
      });
      overlay.addEventListener('keydown', (evt) => {
        if (evt.key === 'Enter') {
          evt.preventDefault();
          if (type === 'text') {
            openTextModalAndFocusRef(previewKey);
          } else if (type === 'image') {
            requestImageReplacement(node);
          }
        }
      });

      layer.appendChild(overlay);

      data = { overlay, node, type, previewKey };
      overlayDataByNode.set(node, data);
      overlayDataByElement.set(overlay, data);

      scheduleOverlaySync();

      return data;
    };

    const updateCanvasDimensions = (intrinsicWidth, intrinsicHeight, options = {}) => {
      if (!cardBg) {
        return;
      }

      const wrapper = cardBg.closest('.preview-canvas-wrapper');
      const width = Number(intrinsicWidth);
      const height = Number(intrinsicHeight);
      const persist = options.persist === undefined ? true : Boolean(options.persist);
      const unit = typeof options.unit === 'string' && options.unit ? options.unit : (templateCanvasPreference?.unit ?? 'px');
      const shape = typeof options.shape === 'string' && options.shape ? options.shape : (templateCanvasPreference?.shape ?? null);

      if (!wrapper || !Number.isFinite(width) || !Number.isFinite(height) || width <= 0 || height <= 0) {
        return;
      }

      const widthScale = MAX_DISPLAY_WIDTH / width;
      const heightScale = MAX_DISPLAY_HEIGHT / height;
      const scale = Math.min(1, widthScale, heightScale);

      const displayWidth = Math.max(1, Math.round(width * scale));
      const displayHeight = Math.max(1, Math.round(height * scale));

      wrapper.style.setProperty('--canvas-width', `${displayWidth}px`);
      wrapper.style.setProperty('--canvas-height', `${displayHeight}px`);
      wrapper.dataset.sourceWidth = width.toString();
      wrapper.dataset.sourceHeight = height.toString();
      wrapper.dataset.displayScale = scale.toString();
      wrapper.dataset.canvasWidth = width.toString();
      wrapper.dataset.canvasHeight = height.toString();
      wrapper.dataset.canvasUnit = unit;
      if (shape) {
        wrapper.dataset.canvasShape = shape;
      }

      cardBg.dataset.canvasWidth = width.toString();
      cardBg.dataset.canvasHeight = height.toString();
      cardBg.dataset.canvasUnit = unit;
      if (shape) {
        cardBg.dataset.canvasShape = shape;
      }

      if (previewStage) {
        previewStage.style.setProperty('--stage-width', `${displayWidth + 112}px`);
      }

      if (persist) {
        templateCanvasPreference = {
          width,
          height,
          unit,
          shape,
        };
      }

      scheduleOverlaySync();
    };

    const updateMeasureLabels = (widthValue, heightValue, unitLabel, fractionDigits = 0) => {
      if (measureWidthEl) {
        if (Number.isFinite(widthValue)) {
          measureWidthEl.textContent = `${widthValue.toFixed(fractionDigits)}${unitLabel}`;
        } else {
          measureWidthEl.textContent = '—';
        }
      }
      if (measureHeightEl) {
        if (Number.isFinite(heightValue)) {
          measureHeightEl.textContent = `${heightValue.toFixed(fractionDigits)}${unitLabel}`;
        } else {
          measureHeightEl.textContent = '—';
        }
      }
    };

    if (templateCanvasPreference) {
      updateCanvasDimensions(templateCanvasPreference.width, templateCanvasPreference.height, {
        unit: templateCanvasPreference.unit,
        shape: templateCanvasPreference.shape,
      });
      updateMeasureLabels(
        convertToInches(templateCanvasPreference.width, templateCanvasPreference.unit),
        convertToInches(templateCanvasPreference.height, templateCanvasPreference.unit),
        'in',
        2,
      );
    }

    const parseSvgLength = (value) => {
      if (typeof value !== 'string' || !value.trim()) {
        return Number.NaN;
      }
      const trimmed = value.trim();
      const numericMatch = trimmed.match(/^([-+]?[0-9]*\.?[0-9]+)/);
      if (!numericMatch) {
        return Number.NaN;
      }
      const numeric = parseFloat(numericMatch[1]);
      if (!Number.isFinite(numeric)) {
        return Number.NaN;
      }
      const unitMatch = trimmed.match(/([a-z%]+)$/i);
      const unit = unitMatch ? unitMatch[1].toLowerCase() : '';
      switch (unit) {
        case '':
        case 'px':
          return numeric;
        case 'mm':
          return numeric * (96 / 25.4);
        case 'cm':
          return numeric * (96 / 2.54);
        case 'in':
          return numeric * 96;
        case 'pt':
          return numeric * (96 / 72);
        default:
          return numeric;
      }
    };

    const loadRasterDimensions = (source) => {
      if (!source) {
        return;
      }

      if (rasterDimensionCache.has(source)) {
        const cached = rasterDimensionCache.get(source);
        if (cached) {
          const cacheUnit = cached.unit || 'px';
          updateCanvasDimensions(cached.width, cached.height, {
            unit: cacheUnit,
            shape: templateCanvasPreference?.shape,
          });
          updateMeasureLabels(
            convertToInches(cached.width, cacheUnit),
            convertToInches(cached.height, cacheUnit),
            'in',
            2,
          );
        }
        return;
      }

      const img = new Image();
      img.crossOrigin = 'anonymous';
      img.onload = () => {
        const dims = { width: img.naturalWidth, height: img.naturalHeight, unit: 'px' };
        rasterDimensionCache.set(source, dims);
        updateCanvasDimensions(dims.width, dims.height, {
          unit: dims.unit,
          shape: templateCanvasPreference?.shape,
        });
        updateMeasureLabels(
          convertToInches(dims.width, dims.unit),
          convertToInches(dims.height, dims.unit),
          'in',
          2,
        );
        scheduleOverlaySync();
      };
      img.onerror = () => {
        rasterDimensionCache.set(source, null);
      };
      img.src = source;
    };

    const modals = {
      text: document.getElementById('text-modal'),
      uploads: document.getElementById('uploads-modal'),
      graphics: document.getElementById('graphics-modal'),
      template: document.getElementById('template-modal'),
      color: document.getElementById('color-modal'),
      qr: document.getElementById('qr-modal'),
      background: document.getElementById('background-modal'),
      product: document.getElementById('product-modal'),
      tables: document.getElementById('tables-modal'),
    };

    const closeButtons = document.querySelectorAll('[data-modal-close]');

    const resolvePreviewNodes = (rootElement) => {
      if (!rootElement) {
        return [];
      }

      const existing = Array.from(rootElement.querySelectorAll('[data-preview-node]'));
      if (existing.length > 0) {
        return existing;
      }

      const fallbackNodes = [];
      const layers = rootElement.querySelectorAll('.canvas-layer[data-layer-id]');

      layers.forEach((layer) => {
        const layerId = layer.getAttribute('data-layer-id');
        if (!layerId) {
          return;
        }

        const cleanedLabel = (layer.getAttribute('aria-label') || layerId).replace(/\s+layer$/i, '') || layerId;

        const textEl = layer.querySelector('.canvas-layer__text');
        if (textEl) {
          if (!textEl.hasAttribute('data-preview-node')) {
            textEl.setAttribute('data-preview-node', layerId);
          }
          if (!textEl.dataset.previewLabel) {
            textEl.dataset.previewLabel = cleanedLabel;
          }
          if (!textEl.dataset.defaultText) {
            const trimmed = (textEl.textContent || '').trim();
            if (trimmed) {
              textEl.dataset.defaultText = trimmed;
            }
          }
          fallbackNodes.push(textEl);
        }

        const imageEl = layer.querySelector('img, image');
        if (imageEl) {
          const imageKey = `${layerId}-image`;
          if (!imageEl.hasAttribute('data-preview-node')) {
            imageEl.setAttribute('data-preview-node', imageKey);
          }
          if (!imageEl.dataset.previewLabel) {
            imageEl.dataset.previewLabel = cleanedLabel;
          }
          fallbackNodes.push(imageEl);
        }

        if (!textEl && !imageEl) {
          if (!layer.hasAttribute('data-preview-node')) {
            layer.setAttribute('data-preview-node', layerId);
          }
          if (!layer.dataset.previewLabel) {
            layer.dataset.previewLabel = cleanedLabel;
          }
          if (!layer.dataset.defaultText) {
            const trimmed = (layer.textContent || '').trim();
            if (trimmed) {
              layer.dataset.defaultText = trimmed;
            }
          }
          fallbackNodes.push(layer);
        }
      });

      // Add data-preview-node to all image and text elements that don't have it
      const allImages = rootElement.querySelectorAll('image, img');
      allImages.forEach((img, index) => {
        if (!img.hasAttribute('data-preview-node')) {
          img.setAttribute('data-preview-node', `auto-image-${index}`);
        }
      });

      const allTexts = rootElement.querySelectorAll('text, tspan');
      allTexts.forEach((txt, index) => {
        if (!txt.hasAttribute('data-preview-node')) {
          txt.setAttribute('data-preview-node', `auto-text-${index}`);
        }
      });

      // Return all elements with data-preview-node
      return Array.from(rootElement.querySelectorAll('[data-preview-node]'));
    };

    const hideModal = (modal) => {
      if (!modal) {
        return;
      }
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      const section = modal.dataset.section;
      if (section) {
        const navBtn = document.querySelector(`.sidenav-btn[data-nav="${section}"]`);
        navBtn?.classList.remove('active');
      }
    };

    const hideAllModals = () => {
      Object.values(modals).forEach((modal) => hideModal(modal));
    };

    const showModal = (section) => {
      const modal = modals[section];
      if (!modal) {
        return;
      }
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      const focusTarget = modal.querySelector('input:not([type="hidden"])');
      focusTarget?.focus({ preventScroll: true });
    };

    if (navButtons.length) {
      navButtons.forEach((button) => {
        button.addEventListener('click', () => {
          const section = button.dataset.nav || 'text';
          const targetModal = modals[section];
          const isOpen = targetModal?.classList.contains('is-open');

          hideAllModals();
          navButtons.forEach((btn) => btn.classList.remove('active'));

          if (!isOpen) {
            showModal(section);
            button.classList.add('active');
          }
        });
      });
    }

    closeButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        const modal = btn.closest('.modal');
        hideModal(modal);
      });
    });

    window.addEventListener('click', (event) => {
      if (event.target.classList.contains('modal') && event.target.classList.contains('is-open')) {
        hideModal(event.target);
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        const openModal = document.querySelector('.modal.is-open');
        if (openModal) {
          hideModal(openModal);
        }
      }
    });

    hideAllModals();
    showModal('text');
    const defaultButton = document.querySelector('.sidenav-btn[data-nav="text"]');
    defaultButton?.classList.add('active');

    const initInput = (input) => {
      const targetName = input.dataset.previewTarget;
      if (!targetName) {
        return;
      }
      const previewNode = document.querySelector(`[data-preview-node="${targetName}"]`);
      const defaultText = previewNode ? previewNode.dataset.defaultText || previewNode.textContent : '';

      const applyValue = () => {
        if (!previewNode) {
          return;
        }
        const trimmed = input.value.trim();
        previewNode.textContent = trimmed || defaultText || '';
        scheduleOverlaySync();
      };

      if (!input.dataset.defaultValue) {
        input.dataset.defaultValue = defaultText || '';
      }

      input.addEventListener('input', () => {
        applyValue();
        autosave?.schedule('text-change');
      });
      applyValue();
    };

    inputs.forEach(initInput);

    // Create a custom text field (reusable when binding SVG text that has no form field)
    const createCustomField = (previewKey, value = '') => {
      const wrapper = document.createElement('div');
      wrapper.className = 'text-field-item';

      const label = document.createElement('span');
      label.className = 'text-field-label';
      label.textContent = 'Custom';
      wrapper.appendChild(label);

      const input = document.createElement('input');
      input.type = 'text';
      input.className = 'text-field-input';
      input.placeholder = 'Add your text';
      input.dataset.previewTarget = previewKey;
      input.value = value;
      input.dataset.defaultValue = value || '';
      wrapper.appendChild(input);

      const delBtn = document.createElement('button');
      delBtn.type = 'button';
      delBtn.className = 'text-field-delete';
      delBtn.setAttribute('aria-label', 'Delete field');
      delBtn.innerHTML = '<i class="fa-regular fa-trash-can"></i>';
      wrapper.appendChild(delBtn);

      textFieldList.appendChild(wrapper);

      const pill = document.createElement('div');
      pill.className = 'pill';
      pill.dataset.previewNode = previewKey;
      pill.dataset.defaultText = value || 'CUSTOM TEXT';
      pill.textContent = value || 'CUSTOM TEXT';
      extraPreviewContainer.appendChild(pill);

      initInput(input);
      attachDeleteHandler(wrapper);
      autosave?.schedule('add-text-field');
      return input;
    };

    const hideSvgPreview = () => {
      if (!previewSvg) {
        return;
      }
      clearOverlays();
      currentSvgRoot = null;
      svgTemplateEditorInstance = null;
      previewSvg.innerHTML = '';
      previewSvg.style.display = 'none';
    };

    const isSvgSource = (value) => {
      if (typeof value !== 'string') {
        return false;
      }
      const trimmed = value.trim();
      if (!trimmed) {
        return false;
      }
      if (trimmed.startsWith('data:image/svg+xml')) {
        return true;
      }
      return /\.svg($|\?)/i.test(trimmed);
    };

    // Load an SVG file and bind any text/image elements (with data-preview-node) into the UI and make them editable
    const loadSVGAndBind = async (url) => {
      if (!previewSvg || !url) {
        hideSvgPreview();
        return false;
      }

      try {
        let svgText = '';
        if (url.startsWith('data:image/svg+xml')) {
          const [, dataPart = ''] = url.split(',', 2);
          try {
            svgText = decodeURIComponent(dataPart);
          } catch (decodeError) {
            svgText = dataPart;
          }
        } else {
          const res = await fetch(url, { cache: 'no-store' });
          if (!res.ok) {
            console.warn('Failed to fetch SVG:', res.status);
            hideSvgPreview();
            return false;
          }
          svgText = await res.text();
        }

        if (!svgText) {
          hideSvgPreview();
          return false;
        }

        previewSvg.innerHTML = svgText;
        previewSvg.style.display = 'block';
        previewSvg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
        previewSvg.setAttribute('width', '100%');
        previewSvg.setAttribute('height', '100%');
        previewSvg.style.width = '100%';
        previewSvg.style.height = '100%';
        previewSvg.style.maxWidth = '100%';
        previewSvg.style.maxHeight = '100%';

        const nestedSvg = previewSvg.querySelector('svg');
        if (nestedSvg) {
          nestedSvg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
          nestedSvg.setAttribute('width', '100%');
          nestedSvg.setAttribute('height', '100%');
          nestedSvg.style.width = '100%';
          nestedSvg.style.height = '100%';
          nestedSvg.style.maxWidth = '100%';
          nestedSvg.style.maxHeight = '100%';
          currentSvgRoot = nestedSvg;
        } else {
          currentSvgRoot = previewSvg;
        }

        if (currentSvgRoot) {
          currentSvgRoot.setAttribute('data-svg-editor', 'true');
          if (!currentSvgRoot.__inkwiseSvgTemplateEditor) {
            currentSvgRoot.__inkwiseSvgTemplateEditor = new SvgTemplateEditor(currentSvgRoot);
          }
          svgTemplateEditorInstance = currentSvgRoot.__inkwiseSvgTemplateEditor;
        }

        // Parse viewBox to adjust canvas size
        const parsed = new DOMParser().parseFromString(svgText, 'image/svg+xml');
        const tempSvg = parsed.documentElement;
        let intrinsicWidth = parseSvgLength(tempSvg?.getAttribute('width'));
        let intrinsicHeight = parseSvgLength(tempSvg?.getAttribute('height'));

        if (!Number.isFinite(intrinsicWidth) || !Number.isFinite(intrinsicHeight)) {
          const viewBox = tempSvg?.getAttribute('viewBox');
          if (viewBox) {
            const numbers = viewBox.split(/[\s,]+/).map(Number);
            const vbWidth = numbers[2];
            const vbHeight = numbers[3];
            if (!Number.isNaN(vbWidth) && !Number.isNaN(vbHeight) && vbWidth > 0 && vbHeight > 0) {
              intrinsicWidth = vbWidth;
              intrinsicHeight = vbHeight;
            }
          }
        }

        let targetWidth = Number.isFinite(intrinsicWidth) ? intrinsicWidth : Number.NaN;
        let targetHeight = Number.isFinite(intrinsicHeight) ? intrinsicHeight : Number.NaN;

        if (templateCanvasPreference && Number.isFinite(templateCanvasPreference.width) && Number.isFinite(templateCanvasPreference.height)) {
          const preferredRatio = templateCanvasPreference.width / templateCanvasPreference.height;
          const svgRatio = Number.isFinite(targetWidth) && Number.isFinite(targetHeight)
            ? targetWidth / targetHeight
            : preferredRatio;
          const ratioDifference = Math.abs(svgRatio - preferredRatio) / preferredRatio;
          const widthDifference = Number.isFinite(targetWidth)
            ? Math.abs(targetWidth - templateCanvasPreference.width) / templateCanvasPreference.width
            : Number.POSITIVE_INFINITY;
          const heightDifference = Number.isFinite(targetHeight)
            ? Math.abs(targetHeight - templateCanvasPreference.height) / templateCanvasPreference.height
            : Number.POSITIVE_INFINITY;

          const shouldUsePreferred = !Number.isFinite(targetWidth)
            || !Number.isFinite(targetHeight)
            || ratioDifference > 0.02
            || (widthDifference > 0.05 && heightDifference > 0.05);

          if (shouldUsePreferred) {
            targetWidth = templateCanvasPreference.width;
            targetHeight = templateCanvasPreference.height;
          }
        }

        if (Number.isFinite(targetWidth) && Number.isFinite(targetHeight)) {
          const unitForCanvas = templateCanvasPreference?.unit ?? 'px';
          updateCanvasDimensions(targetWidth, targetHeight, {
            unit: unitForCanvas,
            shape: templateCanvasPreference?.shape,
          });
          updateMeasureLabels(
            convertToInches(targetWidth, unitForCanvas),
            convertToInches(targetHeight, unitForCanvas),
            'in',
            2,
          );
        }

        // Find all elements with data-preview-node
        const nodes = resolvePreviewNodes(previewSvg);

        clearOverlays();

        // Rebuild the form controls for text fields based on SVG nodes.
        // If there are no text nodes, show the default placeholder fields.
        if (textFieldList) {
          textFieldList.innerHTML = '';

          const createFieldItem = (previewKey, value = '', labelText = '') => {
            const wrapper = document.createElement('div');
            wrapper.className = 'text-field-item';

            const label = document.createElement('span');
            label.className = 'text-field-label';
            label.textContent = labelText || previewKey;
            wrapper.appendChild(label);

            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'text-field-input';
            input.dataset.previewTarget = previewKey;
            input.value = value || '';
            input.dataset.defaultValue = value || '';
            wrapper.appendChild(input);

            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'text-field-delete';
            delBtn.setAttribute('aria-label', 'Delete field');
            delBtn.innerHTML = '<i class="fa-regular fa-trash-can"></i>';
            wrapper.appendChild(delBtn);

            textFieldList.appendChild(wrapper);
            initInput(input);
            attachDeleteHandler(wrapper);
            return input;
          };

          const renderDefaultFields = () => {
            const defaults = [
              ['date', 'Date'],
              ['heading', 'Heading'],
              ['accent', 'Accent'],
              ['subheading', 'Subheading'],
              ['names', 'Names'],
              ['location', 'Location'],
            ];
            defaults.forEach(([k, label]) => createFieldItem(k, '', label));
          };

          if (nodes.length > 0) {
            let createdTextField = false;

            nodes.forEach((node) => {
              const key = node.getAttribute('data-preview-node');
              if (!key) {
                return;
              }

              const nodeName = typeof node.tagName === 'string' ? node.tagName.toLowerCase() : '';
              const isImageNode = nodeName === 'image' || nodeName === 'img';
              const isTextNode = !isImageNode;

              if (isTextNode) {
                createdTextField = true;
                const labelText = node.dataset?.previewLabel || key;
                const defaultValue = node.dataset?.defaultText || node.textContent || '';
                createFieldItem(key, defaultValue, labelText);
              }
            });

            if (!createdTextField) {
              renderDefaultFields();
            }
          } else {
            renderDefaultFields();
          }
        }

        nodes.forEach((node) => {
          const key = node.getAttribute('data-preview-node');
          if (!key) return;

          const nodeName = typeof node.tagName === 'string' ? node.tagName.toLowerCase() : '';
          const isImageNode = nodeName === 'image' || nodeName === 'img';
          const isTextNode = !isImageNode;

          if (isImageNode) {
            node.setAttribute('data-changeable', 'image');
          }

          // Defer overlay creation until after layout is calculated
          const overlayData = createOverlayForNode(node);
          if (overlayData) {
            syncOverlayForNode(node);
          }

          if (isTextNode) {
            // Make text editable
            node.setAttribute('contenteditable', 'true');
            node.style.cursor = 'text';
            node.style.userSelect = 'text';
            node.addEventListener('input', () => {
              const input = document.querySelector(`#textFieldList input[data-preview-target="${key}"]`);
              if (input) {
                input.value = node.textContent;
              }
              node.dataset.currentText = node.textContent || '';
              scheduleOverlaySync();
              autosave?.schedule('text-edit');
            });
            node.addEventListener('click', () => {
              openTextModalAndFocus(key);
            });
          } else if (isImageNode) {
            // Make image replaceable
            node.style.cursor = 'pointer';
            node.addEventListener('click', () => {
              requestImageReplacement(node);
            });
          }

          // Sync from input to SVG
          const input = document.querySelector(`#textFieldList input[data-preview-target="${key}"]`);
          if (input && isTextNode) {
            // avoid adding duplicate listeners
            input.addEventListener('input', () => {
              node.textContent = input.value;
              node.dataset.currentText = input.value;
              autosave?.schedule('text-change');
            });
            // initialize SVG text from input if present
            if (input.value && input.value.trim() !== '') {
              node.textContent = input.value;
            } else {
              // otherwise keep existing node text (or blank)
              input.value = node.textContent || '';
            }
          }
        });

        // Schedule overlay sync after all nodes are processed
        requestAnimationFrame(() => scheduleOverlaySync());
        settleOverlaySync(10);

        return true;
      } catch (e) {
        console.warn('Could not load SVG for binding:', e);
        hideSvgPreview();
        return false;
      }
    };

    // Attach delete handlers to existing text-field-items (initial fields)
    const attachDeleteHandler = (wrapper) => {
      if (!wrapper) return;
      const delBtn = wrapper.querySelector('.text-field-delete');
      const input = wrapper.querySelector('input[data-preview-target]');
      if (!delBtn || !input) return;

      // If the delete button is disabled (built-in field), do nothing
      if (delBtn.disabled || delBtn.getAttribute('aria-disabled') === 'true') {
        return;
      }

      // Clear any previous handlers to avoid duplicates
      delBtn.replaceWith(delBtn.cloneNode(true));
      const newBtn = wrapper.querySelector('.text-field-delete');

      newBtn.addEventListener('click', () => {
        const previewKey = input.dataset.previewTarget;

        // Remove the form control
        wrapper.remove();

        // Remove the corresponding preview pill if present
        if (extraPreviewContainer) {
          const pill = extraPreviewContainer.querySelector(`[data-preview-node="${previewKey}"]`);
          pill?.remove();
        }

        autosave?.schedule('remove-text-field');
      });
    };

    // Initialize delete handlers for any existing items (Blade-rendered)
    const existingItems = document.querySelectorAll('#textFieldList .text-field-item');
    existingItems.forEach((w) => attachDeleteHandler(w));

    // Helper: open text modal and focus a specific input by preview name
    const openTextModalAndFocus = (previewName) => {
      if (!previewName) return;

      // Open the text modal and mark nav active
      hideAllModals();
      showModal('text');
      navButtons.forEach((btn) => btn.classList.remove('active'));
      const textNav = document.querySelector('.sidenav-btn[data-nav="text"]');
      textNav?.classList.add('active');

      // Focus matching input inside the modal (if present)
      setTimeout(() => {
        const targetInput = document.querySelector(`#textFieldList input[data-preview-target="${previewName}"]`);
        if (targetInput) {
          try {
            targetInput.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          } catch (e) {
            const parent = document.getElementById('textFieldList');
            if (parent) parent.scrollTop = targetInput.offsetTop - 12;
          }
          targetInput.focus({ preventScroll: false });
          return;
        }

        // If no matching input exists, focus the add button so user can create a binding
        const addBtn = document.querySelector('[data-add-text-field]');
        addBtn?.focus({ preventScroll: false });
      }, 60);
    };

    openTextModalAndFocusRef = openTextModalAndFocus;

    const setActiveCard = async (side) => {
      currentSide = side;
      if (!cardBg) {
        return;
      }

      let image = '';
      let svgCandidate = '';
      let isUploaded = false;

      if (side === 'front' && uploadedFrontImage) {
        image = uploadedFrontImage;
        svgCandidate = uploadedFrontImage;
        isUploaded = true;
      } else if (side === 'back' && uploadedBackImage) {
        image = uploadedBackImage;
        svgCandidate = uploadedBackImage;
        isUploaded = true;
      } else {
        svgCandidate = cardBg.dataset[`${side}Svg`] || '';
        image = cardBg.dataset[`${side}Image`] || '';
      }

      let usingSvg = false;
      if (svgCandidate && isSvgSource(svgCandidate)) {
        const rendered = await loadSVGAndBind(svgCandidate);
        if (rendered) {
          usingSvg = true;
          cardBg.style.backgroundImage = 'none';
          cardBg.style.backgroundSize = '';
        }
      }

      if (!usingSvg) {
        const rasterSource = image || cardBg.dataset[`${side}Image`] || '';
        if (window?.console) {
          console.debug('[InkWise Studio] Using raster preview for', side, rasterSource || '<empty>');
        }
        hideSvgPreview();
        cardBg.style.backgroundImage = rasterSource ? `url('${rasterSource}')` : '';
        cardBg.style.backgroundSize = '';
        cardBg.style.backgroundPosition = '';
        cardBg.style.backgroundRepeat = '';
        if (rasterSource) {
          loadRasterDimensions(rasterSource);
        }
        clearOverlays();
        currentSvgRoot = null;
      }

      viewButtons.forEach((btn) => {
        btn.classList.toggle('active', btn.dataset.cardView === side);
      });
      thumbButtons.forEach((thumb) => {
        const isActiveThumb = thumb.dataset.cardThumb === side;
        thumb.classList.toggle('active', isActiveThumb);
        thumb.setAttribute('aria-pressed', isActiveThumb ? 'true' : 'false');
      });
    };

    viewButtons.forEach((btn) => {
      btn.addEventListener('click', () => {
        void setActiveCard(btn.dataset.cardView);
      });
    });

    thumbButtons.forEach((thumb) => {
      thumb.addEventListener('click', () => {
        void setActiveCard(thumb.dataset.cardThumb);
      });
    });

    const initialActivation = setActiveCard('front');
    if (initialActivation && typeof initialActivation.then === 'function') {
      initialActivation
        .catch(() => {})
        .then(() => {
          settleOverlaySync(6);
          autosave?.schedule('initial-load');
        });
    } else {
      settleOverlaySync(6);
      autosave?.schedule('initial-load');
    }

    // Delegate clicks inside the preview area: clicking any element with
    // data-preview-node will open the Text modal and focus the corresponding field.
    const previewArea = document.querySelector('.preview-overlay');
    if (previewArea) {
      previewArea.addEventListener('click', (ev) => {
        const node = ev.target.closest('[data-preview-node]');
        if (node) {
          ev.stopPropagation();
          const name = node.dataset.previewNode;
          if (name) openTextModalAndFocus(name);
        }
      });
    }

    if (addFieldBtn && textFieldList && extraPreviewContainer) {
      addFieldBtn.addEventListener('click', () => {
        const previewKey = `custom_${Date.now()}_${customIndex}`;
        customIndex += 1;

        const wrapper = document.createElement('div');
        wrapper.className = 'text-field-item';

        const label = document.createElement('span');
        label.className = 'text-field-label';
        label.textContent = 'Custom';
        wrapper.appendChild(label);

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'text-field-input';
        input.placeholder = 'Add your text';
        input.dataset.previewTarget = previewKey;
        wrapper.appendChild(input);

        // create delete button for the new custom field (enabled)
        const delBtn = document.createElement('button');
        delBtn.type = 'button';
        delBtn.className = 'text-field-delete';
        delBtn.setAttribute('aria-label', 'Delete field');
        delBtn.innerHTML = '<i class="fa-regular fa-trash-can"></i>';
        wrapper.appendChild(delBtn);

        textFieldList.appendChild(wrapper);

        const pill = document.createElement('div');
        pill.className = 'pill';
        pill.dataset.previewNode = previewKey;
        pill.dataset.defaultText = 'CUSTOM TEXT';
        pill.textContent = 'CUSTOM TEXT';
        extraPreviewContainer.appendChild(pill);

        initInput(input);

        // attach delete handler to the newly created wrapper
        attachDeleteHandler(wrapper);

        // Give the browser a moment to render and layout the new item, then
        // if the field list is overflowing, scroll it to show the new field.
        setTimeout(() => {
          try {
            if (textFieldList.scrollHeight > textFieldList.clientHeight) {
              textFieldList.scrollTo({ top: textFieldList.scrollHeight, behavior: 'smooth' });
            }
          } catch (e) {
            // ignore scroll errors on older browsers
            textFieldList.scrollTop = textFieldList.scrollHeight;
          }

          // focus the new input and ensure it is visible; preventScroll:false
          input.focus({ preventScroll: false });
        }, 60);
      });
    }

    let uploadedFrontImage = null;
    let uploadedBackImage = null;
    currentSide = 'front';

    // Image dragging functionality removed for inline SVG editing

    // Handle image uploads
    const imageInput = document.getElementById('image-upload');
    const uploadButton = document.getElementById('upload-button');
    const quickUploadButton = document.getElementById('quick-upload-button');
    const recentUploadsGrid = document.getElementById('recentUploadsGrid');
    const quickRecentUploads = document.getElementById('quickRecentUploads');

    // Recently uploaded images storage
    const RECENT_UPLOADS_KEY = 'inkwise_recent_uploads';
    const MAX_RECENT_UPLOADS = 12;

    const getRecentUploads = () => {
      try {
        const stored = localStorage.getItem(RECENT_UPLOADS_KEY);
        return stored ? JSON.parse(stored) : [];
      } catch (e) {
        return [];
      }
    };

    const saveRecentUpload = (dataUrl, filename, side) => {
      const uploads = getRecentUploads();
      const newUpload = {
        id: Date.now(),
        dataUrl,
        filename,
        side,
        timestamp: new Date().toISOString(),
      };

      // Remove duplicates based on filename
      const filtered = uploads.filter((upload) => upload.filename !== filename);

      // Add new upload to the beginning
      filtered.unshift(newUpload);

      // Keep only the most recent uploads
      const limited = filtered.slice(0, MAX_RECENT_UPLOADS);

      try {
        localStorage.setItem(RECENT_UPLOADS_KEY, JSON.stringify(limited));
      } catch (e) {
        // If storage is full, clear old items and try again
        const cleared = limited.slice(0, Math.floor(MAX_RECENT_UPLOADS / 2));
        localStorage.setItem(RECENT_UPLOADS_KEY, JSON.stringify(cleared));
      }

      renderRecentUploads();
      renderQuickRecentUploads();
    };

    const applyUploadedImage = (dataUrl, filename, side = 'front') => {
      if (!dataUrl) {
        return;
      }

      saveRecentUpload(dataUrl, filename || `upload-${Date.now()}`, side);

      if (side === 'front') {
        uploadedFrontImage = dataUrl;
      } else if (side === 'back') {
        uploadedBackImage = dataUrl;
      }

      if (currentSide === side) {
        void setActiveCard(side);
      }

      autosave?.schedule('image-upload');
    };

    const renderRecentUploads = () => {
      if (!recentUploadsGrid) return;

      const uploads = getRecentUploads();

      if (uploads.length === 0) {
        recentUploadsGrid.innerHTML = `
                <div class="no-recent-uploads">
                    <p>No recent uploads found. Upload some images above to see them here.</p>
                </div>
            `;
        return;
      }

      const uploadItems = uploads.map((upload) => `
            <div class="recent-upload-item" data-upload-id="${upload.id}" data-image-url="${upload.dataUrl}" data-side="${upload.side}">
                <img src="${upload.dataUrl}" alt="${upload.filename}" loading="lazy">
                <div class="upload-info">
                    <span>${upload.filename}</span>
                </div>
            </div>
        `).join('');

      recentUploadsGrid.innerHTML = uploadItems;

      // Add click handlers for recent upload items
      const uploadItemsElements = recentUploadsGrid.querySelectorAll('.recent-upload-item');
      uploadItemsElements.forEach((item) => {
        item.addEventListener('click', () => {
          const imageUrl = item.dataset.imageUrl;
          const side = item.dataset.side;

          // Remove selected class from all items
          uploadItemsElements.forEach((el) => el.classList.remove('selected'));
          // Add selected class to clicked item
          item.classList.add('selected');

          // Apply the image to the appropriate side
          if (side === 'front') {
            uploadedFrontImage = imageUrl;
          } else {
            uploadedBackImage = imageUrl;
          }

          if (currentSide === side) {
            void setActiveCard(side);
          }

          // Show a brief success indication
          item.style.transform = 'scale(0.95)';
          setTimeout(() => {
            item.style.transform = '';
          }, 150);

          autosave?.schedule('image-select');
        });
      });
    };

    const renderQuickRecentUploads = () => {
      if (!quickRecentUploads) return;

      const uploads = getRecentUploads();
      const recentUploads = uploads.slice(0, 5); // Show only the 5 most recent

      if (recentUploads.length === 0) {
        quickRecentUploads.innerHTML = '';
        return;
      }

      const quickUploadItems = recentUploads.map((upload) => `
            <div class="quick-recent-upload-item" data-upload-id="${upload.id}" data-image-url="${upload.dataUrl}" data-side="${upload.side}" title="${upload.filename}">
                <img src="${upload.dataUrl}" alt="${upload.filename}" loading="lazy">
            </div>
        `).join('');

      quickRecentUploads.innerHTML = quickUploadItems;

      // Add click handlers for quick recent upload items
      const quickUploadItemsElements = quickRecentUploads.querySelectorAll('.quick-recent-upload-item');
      quickUploadItemsElements.forEach((item) => {
        item.addEventListener('click', () => {
          const imageUrl = item.dataset.imageUrl;
          const side = item.dataset.side;

          // Remove selected class from all items
          quickUploadItemsElements.forEach((el) => el.classList.remove('selected'));
          // Add selected class to clicked item
          item.classList.add('selected');

          // Apply the image to the appropriate side
          if (side === 'front') {
            uploadedFrontImage = imageUrl;
          } else {
            uploadedBackImage = imageUrl;
          }

          if (currentSide === side) {
            void setActiveCard(side);
          }

          // Show a brief success indication
          item.style.transform = 'scale(0.9)';
          setTimeout(() => {
            item.style.transform = '';
          }, 150);

          autosave?.schedule('image-select');
        });
      });
    };

    const handleImageUpload = (input) => {
      const file = input.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = (e) => {
        const dataUrl = e.target.result;
        applyUploadedImage(dataUrl, file.name, 'front');
      };
      reader.readAsDataURL(file);
    };

    if (imageInput) {
      imageInput.addEventListener('change', () => handleImageUpload(imageInput));
    }

    if (uploadButton) {
      uploadButton.addEventListener('click', () => {
        imageInput.click();
      });
    }

    if (quickUploadButton) {
      quickUploadButton.addEventListener('click', () => {
        imageInput.click();
      });
    }

    const handleClipboardPaste = (event) => {
      const items = event.clipboardData?.items;
      if (!items || items.length === 0) {
        return;
      }

      for (const item of items) {
        if (!item || typeof item.type !== 'string') {
          continue;
        }
        if (!item.type.startsWith('image/')) {
          continue;
        }

        const file = item.getAsFile();
        if (!file) {
          continue;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
          const dataUrl = e.target.result;
          const filename = file.name || `pasted-${Date.now()}.png`;
          applyUploadedImage(dataUrl, filename, currentSide || 'front');
        };
        reader.readAsDataURL(file);
        event.preventDefault();
        break;
      }
    };

    // Initialize recent uploads display
    renderRecentUploads();
    renderQuickRecentUploads();
    document.addEventListener('paste', handleClipboardPaste);

    const tooltipPairs = document.querySelectorAll('.pill-with-tooltip');
    const previewCanvas = document.querySelector('.preview-canvas-wrapper');
    const previewGuides = document.querySelector('.preview-guides');

    const updateHighlight = (buttonId, isActive) => {
      if (buttonId === 'safety-pill') {
        previewCanvas?.classList.toggle('highlight-safety', isActive);
      }

      if (buttonId === 'bleed-pill') {
        previewGuides?.classList.toggle('highlight-bleed', isActive);
      }
    };

    const closeTooltipPair = (pair) => {
      const btn = pair?.querySelector('.canvas-pill');
      const tooltip = pair?.querySelector('.tooltip');
      if (!btn || !tooltip) {
        return;
      }
      pair.classList.remove('is-active');
      btn.setAttribute('aria-expanded', 'false');
      tooltip.setAttribute('aria-hidden', 'true');
      updateHighlight(btn.id, false);
    };

    tooltipPairs.forEach((pair) => {
      const button = pair.querySelector('.canvas-pill');
      const tooltip = pair.querySelector('.tooltip');

      if (!button || !tooltip) {
        return;
      }

      button.addEventListener('click', (event) => {
        event.preventDefault();
        const shouldActivate = !pair.classList.contains('is-active');

        tooltipPairs.forEach((otherPair) => {
          if (otherPair !== pair) {
            closeTooltipPair(otherPair);
          }
        });

        if (shouldActivate) {
          pair.classList.add('is-active');
          button.setAttribute('aria-expanded', 'true');
          tooltip.setAttribute('aria-hidden', 'false');
          updateHighlight(button.id, true);
        } else {
          closeTooltipPair(pair);
        }
      });

      button.addEventListener('mouseenter', () => {
        tooltip.setAttribute('aria-hidden', 'false');
      });

      button.addEventListener('focus', () => {
        tooltip.setAttribute('aria-hidden', 'false');
      });

      pair.addEventListener('mouseleave', () => {
        if (!pair.classList.contains('is-active')) {
          tooltip.setAttribute('aria-hidden', 'true');
        }
      });

      button.addEventListener('blur', () => {
        if (!pair.contains(document.activeElement) && !pair.classList.contains('is-active')) {
          tooltip.setAttribute('aria-hidden', 'true');
        }
      });

      button.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          closeTooltipPair(pair);
          button.blur();
        }
      });
    });

    document.addEventListener('click', (event) => {
      const pair = event.target.closest('.pill-with-tooltip');
      if (!pair) {
        tooltipPairs.forEach((item) => closeTooltipPair(item));
      }
    });

    // Zoom functionality
    const zoomSelect = document.getElementById('canvas-zoom-select');
    const zoomDisplay = document.getElementById('canvas-zoom-display');
    const zoomButtons = document.querySelectorAll('[data-zoom-step]');
    const zoomCanvasWrapper = document.querySelector('.preview-canvas-wrapper');
    const canvasStage = previewStage;

    const zoomLevels = [0.25, 0.5, 0.75, 1, 1.5, 2, 3];

    if (zoomSelect && zoomCanvasWrapper && canvasStage) {
      const applyZoom = (scale) => {
        canvasStage.style.setProperty('--canvas-scale', scale.toString());
        // ensure wrapper remains unscaled so hit zones stay aligned
        zoomCanvasWrapper.style.removeProperty('transform');
        zoomSelect.value = scale.toString();
        if (zoomDisplay) {
          zoomDisplay.textContent = `${Math.round(scale * 100)}%`;
        }
        scheduleOverlaySync();
      };

      const stepZoom = (direction) => {
        const currentScale = parseFloat(zoomSelect.value) || 1;
        const currentIndex = zoomLevels.indexOf(currentScale);
        if (currentIndex === -1) {
          return;
        }

        let targetIndex = currentIndex;
        if (direction === 'up' && currentIndex < zoomLevels.length - 1) {
          targetIndex = currentIndex + 1;
        }
        if (direction === 'down' && currentIndex > 0) {
          targetIndex = currentIndex - 1;
        }

        if (targetIndex !== currentIndex) {
          applyZoom(zoomLevels[targetIndex]);
        }
      };

      zoomSelect.addEventListener('change', (event) => {
        const scale = parseFloat(event.target.value) || 1;
        applyZoom(scale);
      });

      zoomButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
          const direction = btn.dataset.zoomStep;
          if (!direction) {
            return;
          }
          stepZoom(direction === 'up' ? 'up' : 'down');
        });
      });

      // Add mouse wheel and touchpad zoom
      if (canvasStage) {
        canvasStage.addEventListener('wheel', (event) => {
          event.preventDefault();
          const currentScale = parseFloat(zoomSelect.value) || 1;
          let newScale = currentScale;

          if (event.deltaY > 0) {
            const currentIndex = zoomLevels.indexOf(currentScale);
            if (currentIndex > 0) {
              newScale = zoomLevels[currentIndex - 1];
            }
          } else {
            const currentIndex = zoomLevels.indexOf(currentScale);
            if (currentIndex < zoomLevels.length - 1) {
              newScale = zoomLevels[currentIndex + 1];
            }
          }

          if (newScale !== currentScale) {
            applyZoom(newScale);
          }
        }, { passive: false });
      }

      applyZoom(parseFloat(zoomSelect.value) || 1);
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
}
