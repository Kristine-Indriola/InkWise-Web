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
    const SVG_NS = 'http://www.w3.org/2000/svg';
    const measureWidthEl = document.querySelector('.canvas-measure-horizontal .measure-value');
    const measureHeightEl = document.querySelector('.canvas-measure-vertical .measure-value');
    const canvasWrapper = document.querySelector('.preview-canvas-wrapper');
    let customIndex = 1;

    const imageClassTokens = new Set([
      'canvas-layer__image',
      'changeable-image',
      'svg-changeable-image',
      'inkwise-image',
    ]);

    const readDatasetTokens = (node) => {
      if (!node || !node.dataset) {
        return [];
      }
      const keys = ['changeable', 'editableType', 'elementType', 'layerType', 'previewType', 'fieldType'];
      return keys
        .map((key) => node.dataset[key])
        .filter((value) => typeof value === 'string' && value.trim() !== '')
        .map((value) => value.trim().toLowerCase());
    };

    const findImageContentNode = (node) => {
      if (!node || typeof node.querySelector !== 'function') {
        return null;
      }
      return node.querySelector('[data-changeable="image"], [data-editable-image], .canvas-layer__image, img, image');
    };

    const nodeRepresentsImage = (node) => {
      if (!node) {
        return false;
      }

      const tagName = typeof node.tagName === 'string' ? node.tagName.toLowerCase() : '';
      if (tagName === 'image' || tagName === 'img') {
        return true;
      }

      const datasetHints = readDatasetTokens(node);
      if (datasetHints.some((hint) => hint === 'image' || hint === 'photo' || hint === 'graphic')) {
        return true;
      }

      if (typeof node.hasAttribute === 'function') {
        if (node.hasAttribute('data-editable-image') || node.hasAttribute('data-changeable-image')) {
          return true;
        }
      }

      if (node.classList) {
        for (const token of imageClassTokens) {
          if (node.classList.contains(token)) {
            return true;
          }
        }
      }

      if (tagName === 'foreignobject') {
        const inner = findImageContentNode(node);
        if (inner) {
          return true;
        }
      }

      return false;
    };

    const nodeRepresentsText = (node) => {
      if (!node || nodeRepresentsImage(node)) {
        return false;
      }
      const tagName = typeof node.tagName === 'string' ? node.tagName.toLowerCase() : '';
      if (tagName === 'style' || tagName === 'script' || tagName === 'defs' || tagName === 'title' || tagName === 'metadata') {
        return false;
      }
      if (tagName === 'text' || tagName === 'tspan') {
        return true;
      }

      const datasetHints = readDatasetTokens(node);
      if (datasetHints.some((hint) => hint.includes('text') || hint === 'names' || hint === 'heading')) {
        return true;
      }

      const rawText = (node.textContent || '').trim();
      if (!rawText) {
        return false;
      }
      const looksLikeCss = /\{[^}]*\}|;/.test(rawText) && rawText.length > 80;
      if (looksLikeCss) {
        return false;
      }

      return true;
    };

    const readNodeTextValue = (node) => {
      if (!node) {
        return '';
      }
      const tspans = node.querySelectorAll('tspan');
      if (tspans && tspans.length > 0) {
        return sanitizeTextValue(Array.from(tspans).map((span) => span.textContent || '').join('\n'));
      }
      return sanitizeTextValue(node.textContent || '');
    };

    const writeNodeTextValue = (node, value) => {
      if (!node) {
        return;
      }
      const cleanValue = sanitizeTextValue(value);
      const tspans = node.querySelectorAll('tspan');
      if (tspans && tspans.length > 0) {
        const parts = String(cleanValue ?? '').split(/\r?\n/);
        tspans.forEach((span, index) => {
          span.textContent = parts[index] ?? '';
        });
      } else {
        node.textContent = cleanValue ?? '';
      }
      if (node.dataset) {
        node.dataset.currentText = cleanValue ?? '';
      }
    };


    const resolveImageLikeNode = (node) => {
      if (!node) {
        return null;
      }
      if (nodeRepresentsImage(node)) {
        return node;
      }
      if (typeof node.closest === 'function') {
        const ancestor = node.closest('[data-preview-node]');
        if (ancestor && ancestor !== node && nodeRepresentsImage(ancestor)) {
          return ancestor;
        }
      }
      const fallback = findImageContentNode(node);
      if (fallback) {
        return fallback;
      }
      return null;
    };

    const applyImageSourceToNode = (node, dataUrl) => {
      if (!node || !dataUrl) {
        return false;
      }

      const tagName = typeof node.tagName === 'string' ? node.tagName.toLowerCase() : '';
      if (tagName === 'image') {
        node.setAttributeNS('http://www.w3.org/1999/xlink', 'href', dataUrl);
        node.setAttribute('href', dataUrl);
        node.dataset.src = dataUrl;
        return true;
      }
      if (tagName === 'img') {
        node.setAttribute('src', dataUrl);
        node.dataset.src = dataUrl;
        return true;
      }
      if (tagName === 'foreignobject') {
        const inner = findImageContentNode(node);
        if (inner && inner !== node) {
          return applyImageSourceToNode(inner, dataUrl);
        }
        return false;
      }

      if (node.style) {
        node.style.backgroundImage = `url('${dataUrl}')`;
      }
      if (node.dataset) {
        node.dataset.src = dataUrl;
        node.dataset.replacedImage = 'true';
      }
      return true;
    };

    const ensureSolidBackground = (root) => {
      if (!root || typeof root.insertBefore !== 'function') {
        return;
      }

      const existing = root.querySelector('[data-inkwise-bg="true"]');
      if (existing) {
        return;
      }

      const bg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
      bg.setAttribute('x', '0');
      bg.setAttribute('y', '0');
      bg.setAttribute('width', '100%');
      bg.setAttribute('height', '100%');
      bg.setAttribute('fill', '#ffffff');
      bg.setAttribute('data-inkwise-bg', 'true');
      root.insertBefore(bg, root.firstChild);
    };

    const bootstrapPayload = (typeof window !== 'undefined' && window.inkwiseStudioBootstrap)
      ? window.inkwiseStudioBootstrap
      : {};

    console.log('[InkWise Studio] Bootstrap payload:', bootstrapPayload);
    const routes = bootstrapPayload?.routes ?? {};
    console.log('[InkWise Studio] Routes:', routes);
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

    const applyDesignData = (designData) => {
      if (!designData || !currentSvgRoot) {
        return;
      }

      // Apply text customizations
      if (designData.texts && Array.isArray(designData.texts)) {
        designData.texts.forEach((textEntry) => {
          const { key, value, fontFamily, fontSize, color } = textEntry;
          const node = currentSvgRoot.querySelector(`[data-preview-node="${key}"]`);
          if (node) {
            // Apply text value
            if (value !== undefined) {
              writeNodeTextValue(node, value);
              // Also update the input field
              const input = document.querySelector(`input[data-preview-target="${key}"]`);
              if (input) {
                input.value = value;
              }
            }

            // Apply font family
            if (fontFamily) {
              node.setAttribute('font-family', fontFamily);
              node.style.fontFamily = fontFamily;
              try { node.dataset.fontFamily = fontFamily; } catch(e){}
            }

            // Apply font size
            if (fontSize) {
              node.setAttribute('font-size', fontSize);
              node.style.fontSize = fontSize;
              try { node.dataset.fontSize = fontSize; } catch(e){}
            }

            // Apply color
            if (color) {
              node.setAttribute('fill', color);
              node.style.fill = color;
              try { node.dataset.color = color; } catch(e){}
            }
          }
        });
      }

      // Apply image customizations
      if (designData.images && Array.isArray(designData.images)) {
        designData.images.forEach((imageEntry) => {
          const { key, href, x, y, width, height } = imageEntry;
          const node = currentSvgRoot.querySelector(`[data-preview-node="${key}"]`);
          if (node) {
            if (href) {
              node.setAttribute('href', href);
              node.setAttribute('xlink:href', href);
            }
            if (x !== undefined) node.setAttribute('x', x);
            if (y !== undefined) node.setAttribute('y', y);
            if (width !== undefined) node.setAttribute('width', width);
            if (height !== undefined) node.setAttribute('height', height);
          }
        });
      }
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

        // Get font properties from the corresponding SVG node
        const key = input.dataset.previewTarget || '';
        const svgNode = currentSvgRoot?.querySelector(`[data-preview-node="${key}"]`);
        let fontFamily = null;
        let fontSize = null;
        let color = null;

        if (svgNode) {
          fontFamily = svgNode.getAttribute('font-family') || svgNode.style.fontFamily || svgNode.dataset.fontFamily;
          fontSize = svgNode.getAttribute('font-size') || svgNode.style.fontSize || svgNode.dataset.fontSize;
          color = svgNode.getAttribute('fill') || svgNode.style.fill || svgNode.dataset.color;
        }

        return {
          key,
          label,
          value: input.value || '',
          defaultValue: input.dataset.defaultValue || '',
          fontFamily,
          fontSize,
          color,
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
    let uploadedFrontImage = null;
    let uploadedBackImage = null;
    let autosave = null;
    let autosaveHeartbeatTimer = null;
    let activeTextKey = null;
    let activeImageKey = null;
    let backgroundSelected = false;
    let activeCropSession = null;

    const SNAP_SIZE = 8;
    const SNAP_THRESHOLD = 4;

    const sanitizeTextValue = (raw) => {
      const value = (raw || '').toString();
      const trimmed = value.trim();

      const looksLikeCss = /::before|\{[^}]*\}|;/.test(trimmed) && trimmed.length > 120;
      if (looksLikeCss) {
        return '';
      }

      return trimmed;
    };

    const sideIsAvailable = (side) => {
      if (!side || side === 'front') {
        return true;
      }

      if (side !== 'back') {
        return true;
      }

      if (!cardBg) {
        return false;
      }

      if (uploadedBackImage) {
        return true;
      }

      const dataset = cardBg.dataset || {};
      if (dataset.hasBack === 'true') {
        return true;
      }

      return Boolean(dataset.backImage || dataset.backSvg);
    };

    /**
     * Convert an image URL to a base64 data URL using canvas
     * @param {string} url - The image URL to convert
     * @returns {Promise<string|null>} - The base64 data URL or null on failure
     */
    const imageUrlToBase64 = async (url) => {
      if (!url || typeof url !== 'string') {
        return null;
      }
      // Already a data URL
      if (url.startsWith('data:')) {
        return url;
      }

      // Try canvas approach first (works for already-loaded images)
      try {
        const img = new Image();
        img.crossOrigin = 'anonymous';

        const loaded = await new Promise((resolve) => {
          img.onload = () => resolve(true);
          img.onerror = () => resolve(false);
          // Add cache-busting to force reload with CORS headers
          const separator = url.includes('?') ? '&' : '?';
          img.src = url + separator + '_t=' + Date.now();
        });

        if (loaded && img.naturalWidth > 0 && img.naturalHeight > 0) {
          const canvas = document.createElement('canvas');
          canvas.width = img.naturalWidth;
          canvas.height = img.naturalHeight;
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0);
          try {
            const dataUrl = canvas.toDataURL('image/png');
            if (dataUrl && dataUrl !== 'data:,') {
              return dataUrl;
            }
          } catch (canvasError) {
            console.warn('[InkWise Studio] Canvas tainted, trying fetch approach:', url);
          }
        }
      } catch (canvasAttemptError) {
        console.warn('[InkWise Studio] Canvas approach failed:', url, canvasAttemptError);
      }

      // Fallback to fetch approach
      try {
        const response = await fetch(url, { credentials: 'include', mode: 'cors' });
        if (!response.ok) {
          console.warn('[InkWise Studio] Failed to fetch image for base64 conversion:', url, response.status);
          return null;
        }
        const blob = await response.blob();
        return new Promise((resolve) => {
          const reader = new FileReader();
          reader.onloadend = () => resolve(reader.result);
          reader.onerror = () => resolve(null);
          reader.readAsDataURL(blob);
        });
      } catch (fetchError) {
        console.warn('[InkWise Studio] Fetch approach also failed:', url, fetchError);
      }

      // Last resort: try without CORS
      try {
        const response = await fetch(url, { mode: 'no-cors' });
        const blob = await response.blob();
        if (blob.size > 0) {
          return new Promise((resolve) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result);
            reader.onerror = () => resolve(null);
            reader.readAsDataURL(blob);
          });
        }
      } catch (noCorsError) {
        console.warn('[InkWise Studio] No-cors fetch also failed:', url, noCorsError);
      }

      return null;
    };

    /**
     * Clean up SVG clone for export by removing UI elements and embedding images
     * @param {SVGElement} clone - The cloned SVG element to clean
     * @returns {Promise<void>}
     */
    const cleanSvgForExport = async (clone) => {
      // Remove UI elements: bounding boxes, resize handles, selection indicators
      const uiSelectors = [
        '.svg-bounding-box',
        '.resize-handle',
        '.selection-indicator',
        '.inkwise-selection',
        '.inkwise-overlay',
        '[data-ui-element="true"]',
        '[data-temp-element="true"]',
        'g.svg-bounding-box',
      ];
      uiSelectors.forEach((selector) => {
        clone.querySelectorAll(selector).forEach((el) => el.remove());
      });

      // Remove contenteditable and cursor styles from text elements
      clone.querySelectorAll('text').forEach((textEl) => {
        textEl.removeAttribute('contenteditable');
        textEl.style.cursor = '';
        textEl.style.userSelect = '';
      });

      // Remove cursor and pointer-events styles from images
      clone.querySelectorAll('image').forEach((imgEl) => {
        imgEl.style.cursor = '';
        imgEl.style.pointerEvents = '';
      });

      /**
       * Normalize URL to use current origin and correct path structure
       * Fixes: localhost vs 127.0.0.1 mismatch
       * Fixes: /InkWise-Web/ink-wise/public/storage/ -> /storage/
       */
      const normalizeUrlToCurrentOrigin = (url) => {
        if (!url || url.startsWith('data:')) {
          return url;
        }
        try {
          const parsed = new URL(url, window.location.origin);
          
          // Fix incorrect path structure: /InkWise-Web/ink-wise/public/storage/ -> /storage/
          // This handles URLs like: http://localhost/InkWise-Web/ink-wise/public/storage/customer/...
          const pathPatterns = [
            /\/InkWise-Web\/ink-wise\/public\/storage\//gi,
            /\/ink-wise\/public\/storage\//gi,
            /\/public\/storage\//gi,
          ];
          let pathname = parsed.pathname;
          for (const pattern of pathPatterns) {
            if (pattern.test(pathname)) {
              pathname = pathname.replace(pattern, '/storage/');
              break;
            }
          }
          parsed.pathname = pathname;
          
          // If URL is from localhost or 127.0.0.1, normalize to current origin
          if (parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1') {
            parsed.hostname = window.location.hostname;
            parsed.port = window.location.port;
            parsed.protocol = window.location.protocol;
          }
          return parsed.href;
        } catch (e) {
          return url;
        }
      };

      // Convert external image URLs to base64 data URLs
      const imageElements = clone.querySelectorAll('image');
      console.log('[InkWise Studio] Found', imageElements.length, 'image elements to convert');
      const conversionPromises = Array.from(imageElements).map(async (imgEl) => {
        let href = imgEl.getAttribute('href')
          || imgEl.getAttribute('xlink:href')
          || imgEl.getAttributeNS('http://www.w3.org/1999/xlink', 'href');

        console.log('[InkWise Studio] Processing image with href:', href);

        if (!href || href.startsWith('data:')) {
          console.log('[InkWise Studio] Image already embedded or no href, skipping');
          return; // Already embedded or no href
        }

        // Normalize the URL to use current origin
        const normalizedHref = normalizeUrlToCurrentOrigin(href);
        console.log('[InkWise Studio] Normalized URL:', normalizedHref);

        const base64 = await imageUrlToBase64(normalizedHref);
        if (base64) {
          console.log('[InkWise Studio] Successfully converted image to base64, length:', base64.length);
          
          // Remove ALL existing href-related attributes first to ensure clean state
          imgEl.removeAttribute('href');
          imgEl.removeAttribute('xlink:href');
          imgEl.removeAttributeNS('http://www.w3.org/1999/xlink', 'href');
          imgEl.removeAttribute('data-src');
          
          // Now set all href attributes to base64 to ensure complete coverage
          imgEl.setAttribute('href', base64);
          imgEl.setAttribute('xlink:href', base64);  // Direct attribute for serialization
          imgEl.setAttributeNS('http://www.w3.org/1999/xlink', 'href', base64);  // Namespace-aware
          
          console.log('[InkWise Studio] Image href attributes updated to base64');
        } else {
          console.warn('[InkWise Studio] Failed to embed image, keeping original URL:', normalizedHref);
        }
      });

      await Promise.all(conversionPromises);
      console.log('[InkWise Studio] All image conversions completed');
    };

    const collectDesignSnapshot = async (reason, side = null) => {
      if (!cardBg) {
        return null;
      }

      const timestamp = new Date();
      const activeSide = side || currentSide || 'front';
      let svgNode = currentSvgRoot;

      // If collecting for a different side than current, load that side's SVG
      if (side && side !== currentSide) {
        const sideSvgCandidate = cardBg.dataset[`${side}Svg`] || '';
        if (sideSvgCandidate && isSvgSource(sideSvgCandidate)) {
          try {
            // Parse the SVG without displaying it
            let svgText = '';
            if (sideSvgCandidate.startsWith('data:image/svg+xml')) {
              const commaIndex = sideSvgCandidate.indexOf(',');
              const meta = commaIndex >= 0 ? sideSvgCandidate.slice(0, commaIndex) : sideSvgCandidate;
              const dataPart = commaIndex >= 0 ? sideSvgCandidate.slice(commaIndex + 1) : '';
              const isBase64 = /;base64/i.test(meta);

              if (isBase64) {
                try {
                  const normalized = dataPart.replace(/\s+/g, '');
                  svgText = typeof window !== 'undefined' && typeof window.atob === 'function'
                    ? window.atob(normalized)
                    : atob(normalized);
                } catch (base64Error) {
                  try {
                    svgText = decodeURIComponent(dataPart);
                  } catch (decodeError) {
                    console.warn('[InkWise Studio] Failed to decode inline SVG data URI.', base64Error, decodeError);
                    svgText = '';
                  }
                }
              } else {
                try {
                  svgText = decodeURIComponent(dataPart);
                } catch (decodeError) {
                  svgText = dataPart;
                }
              }
            } else {
              const res = await fetch(sideSvgCandidate, { cache: 'no-store' });
              if (res.ok) {
                svgText = await res.text();
              }
            }

            if (svgText) {
              const parser = new DOMParser();
              const doc = parser.parseFromString(svgText, 'image/svg+xml');
              const parsedSvg = doc.documentElement;
              if (parsedSvg && parsedSvg.tagName === 'svg') {
                svgNode = parsedSvg;
              }
            }
          } catch (loadError) {
            console.warn('[InkWise Studio] Failed to load SVG for side:', side, loadError);
          }
        }
      }

      let serializedSvg = null;
      if (svgNode) {
        try {
          // Clone the SVG to manipulate it before serialization without affecting the UI
          const clone = svgNode.cloneNode(true);

          // Restore intrinsic dimensions if available from data attributes
          const canvasWidth = cardBg?.dataset?.canvasWidth;
          const canvasHeight = cardBg?.dataset?.canvasHeight;
          if (canvasWidth && canvasHeight) {
            clone.setAttribute('width', canvasWidth);
            clone.setAttribute('height', canvasHeight);
            clone.style.width = canvasWidth + 'px';
            clone.style.height = canvasHeight + 'px';
          }
          clone.setAttribute('preserveAspectRatio', 'xMidYMid meet');

          // Clean up the SVG for export: remove UI elements and embed images as base64
          await cleanSvgForExport(clone);

          // Inject font styles and basic SVG resets to ensure fidelity outside the studio
          let styleEl = clone.querySelector('style#inkwise-export-styles');
          if (!styleEl) {
            styleEl = document.createElementNS(SVG_NS, 'style');
            styleEl.id = 'inkwise-export-styles';
            clone.insertBefore(styleEl, clone.firstChild);
          }
          styleEl.textContent = `
            @import url('https://fonts.googleapis.com/css2?family=Great+Vibes&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');
            text { white-space: pre; }
          `;

          const serializer = new XMLSerializer();
          serializedSvg = serializer.serializeToString(clone);
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
          placeholders: placeholderLabels,
        },
        preview: {
          image: previewImage,
          images: previewImages,
        },
        svg_markup: serializedSvg,
        placeholders: placeholderLabels,
        product_id: bootstrapPayload?.product?.id ?? null,
      };
    };

    const persistPreviewToSession = (result = {}, snapshot = {}) => {
      const summary = (result && result.summary) ? result.summary : {};
      const fromSummary = Array.isArray(summary.previewImages)
        ? summary.previewImages.filter(Boolean)
        : (Array.isArray(summary.preview_images) ? summary.preview_images.filter(Boolean) : []);
      const fromSnapshot = Array.isArray(snapshot?.preview?.images)
        ? snapshot.preview.images.filter(Boolean)
        : [];

      const previewImages = fromSummary.length ? fromSummary : fromSnapshot;
      const primary = summary.previewImage
        || summary.preview_image
        || summary.preview
        || snapshot?.preview?.image
        || previewImages[0]
        || null;

      if (!primary && !previewImages.length) {
        return; // nothing useful to persist
      }

      const payload = {
        templateName: summary.productName || summary.templateName || document.title || 'Saved template',
        previewImage: primary || '',
        previewImages: previewImages.length ? previewImages : (primary ? [primary] : []),
        metadata: {
          template: { name: summary.productName || summary.templateName || 'Saved template' },
        },
      };

      try { window.sessionStorage.setItem('inkwise-finalstep', JSON.stringify(payload)); } catch (e) { /* ignore */ }
      try {
        const short = { id: null, name: payload.templateName, preview: payload.previewImage };
        window.sessionStorage.setItem('inkwise-saved-template', JSON.stringify(short));
        window.savedCustomerTemplate = short;
      } catch (e) { /* ignore */ }
    };

    const loadAutosave = async (side) => {
      try {
        const response = await fetch('/design/load-autosave?side=' + encodeURIComponent(side), {
          method: 'GET',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Content-Type': 'application/json',
          },
        });
        if (!response.ok) {
          throw new Error('Failed to load autosave data');
        }
        const data = await response.json();
        return data;
      } catch (error) {
        console.warn('[InkWise Studio] Failed to load autosave data for side:', side, error);
        return null;
      }
    };

    const scheduleAutosave = (reason) => {
      if (autosave) {
        autosave.schedule(reason);
      } else if (typeof window !== 'undefined' && window.inkwiseAutosaveController) {
        window.inkwiseAutosaveController.schedule(reason);
      }
    };

    try {
      autosave = createAutosaveController({
        collectSnapshot: collectSnapshotWithPersist,
        routes,
        csrfToken,
        statusLabel: statusLabelEl,
        statusDot: statusDotEl,
        debounce: 0, // Save immediately on change
        onAfterSave: (result, snapshot) => {
          try { persistPreviewToSession(result, snapshot); } catch (e) { /* non-fatal */ }
        },
      });
      // Make autosave available globally for text inputs that were initialized before autosave
      if (typeof window !== 'undefined') {
        window.inkwiseAutosaveController = autosave;
      }
      console.log('[InkWise Studio] Autosave initialized', autosave);

      const flushAutosave = (reason) => {
        if (!autosave) {
          return;
        }
        try {
          void autosave.flush(reason);
        } catch (flushError) {
          console.debug('[InkWise Studio] Autosave flush skipped.', flushError);
        }
      };

      const handleVisibilityChange = () => {
        if (document.visibilityState === 'hidden') {
          flushAutosave('visibility-hidden');
        }
      };

      const handlePageHide = () => {
        flushAutosave('page-hide');
      };

      document.addEventListener('visibilitychange', handleVisibilityChange);
      window.addEventListener('pagehide', handlePageHide);
      window.addEventListener('beforeunload', handlePageHide);

      autosaveHeartbeatTimer = window.setInterval(() => {
        flushAutosave('heartbeat');
      }, 60000);

      window.addEventListener('unload', () => {
        if (autosaveHeartbeatTimer) {
          window.clearInterval(autosaveHeartbeatTimer);
          autosaveHeartbeatTimer = null;
        }
        document.removeEventListener('visibilitychange', handleVisibilityChange);
        window.removeEventListener('pagehide', handlePageHide);
        window.removeEventListener('beforeunload', handlePageHide);
      });

      // Periodic autosave every 10 seconds as fallback
      setInterval(() => {
        if (autosave) {
          console.log('[InkWise Studio] Periodic autosave trigger');
          autosave.schedule('periodic');
        }
      }, 10000);
    } catch (autosaveError) {
      console.warn('[InkWise Studio] Autosave controller failed to initialize.', autosaveError);
      autosave = null;
    }

    const buildReviewSavePayload = async () => {
      console.log('[InkWise Studio] Building review save payload...');
      console.log('[InkWise Studio] Bootstrap payload:', bootstrapPayload);
      console.log('[InkWise Studio] Template in bootstrap:', bootstrapPayload?.template);

      const snapshot = await collectDesignSnapshot('proceed-review');

      const design = snapshot?.design || {};
      const sides = design.sides || {};
      const firstSideKey = Object.keys(sides)[0];
      const sideData = firstSideKey ? sides[firstSideKey] : null;

      const safeDesignJson = JSON.parse(JSON.stringify(design || {}));

      // Get the SVG markup from the snapshot
      const svgMarkup = sideData?.svg || snapshot?.svg_markup || null;

      const previewImage = sideData?.preview
        || snapshot?.preview?.image
        || extractBackgroundUrl(firstSideKey || 'front')
        || null;
      const previewImages = (Array.isArray(snapshot?.preview?.images) ? snapshot.preview.images : [])
        .filter((val) => typeof val === 'string' && val.trim() !== '');
      if (previewImage && !previewImages.length) {
        previewImages.push(previewImage);
      }

      const canvasMeta = safeDesignJson.canvas || {};
      const canvasWidth = canvasMeta.width ?? (cardBg ? parseNullableNumber(cardBg.dataset.canvasWidth) : null);
      const canvasHeight = canvasMeta.height ?? (cardBg ? parseNullableNumber(cardBg.dataset.canvasHeight) : null);
      const backgroundColor = (() => {
        try {
          if (!cardBg) return null;
          const cs = window.getComputedStyle(cardBg);
          return cs?.backgroundColor || cardBg.dataset?.backgroundColor || cardBg.style?.backgroundColor || null;
        } catch (_) {
          return null;
        }
      })();

      const payload = {
        design_svg: svgMarkup, // Include the SVG for proper preview rendering
        design_json: safeDesignJson,
        preview_image: previewImage,
        preview_images: previewImages,
        canvas_width: canvasWidth,
        canvas_height: canvasHeight,
        background_color: backgroundColor,
        template_id: bootstrapPayload?.template?.id ?? bootstrapPayload?.template_id ?? null,
        order_item_id: bootstrapPayload?.orderSummary?.order_item_id
          ?? bootstrapPayload?.orderSummary?.orderItemId
          ?? bootstrapPayload?.orderSummary?.item_id
          ?? null,
      };

      console.log('[InkWise Studio] Built payload:', payload);
      console.log('[InkWise Studio] Template ID:', payload.template_id);

      return payload;
    };

    const buildReviewSavePayloadForSide = async (snapshot, side) => {
      const design = snapshot?.design || {};
      const sides = design.sides || {};
      const sideData = sides[side] || null;

      const safeDesignJson = JSON.parse(JSON.stringify(design || {}));

      // Get the SVG markup from the snapshot
      const svgMarkup = sideData?.svg || snapshot?.svg_markup || null;

      const previewImage = sideData?.preview
        || snapshot?.preview?.image
        || extractBackgroundUrl(side)
        || null;
      const previewImages = (Array.isArray(snapshot?.preview?.images) ? snapshot.preview.images : [])
        .filter((val) => typeof val === 'string' && val.trim() !== '');
      if (previewImage && !previewImages.length) {
        previewImages.push(previewImage);
      }

      const canvasMeta = safeDesignJson.canvas || {};
      const canvasWidth = canvasMeta.width ?? (cardBg ? parseNullableNumber(cardBg.dataset.canvasWidth) : null);
      const canvasHeight = canvasMeta.height ?? (cardBg ? parseNullableNumber(cardBg.dataset.canvasHeight) : null);
      const backgroundColor = (() => {
        try {
          if (!cardBg) return null;
          const cs = window.getComputedStyle(cardBg);
          return cs?.backgroundColor || cardBg.dataset?.backgroundColor || cardBg.style?.backgroundColor || null;
        } catch (_) {
          return null;
        }
      })();

      return {
        design_svg: svgMarkup,
        design_json: safeDesignJson,
        preview_image: previewImage,
        preview_images: previewImages,
        canvas_width: canvasWidth,
        canvas_height: canvasHeight,
        background_color: backgroundColor,
      };
    };

    const proceedButton = document.querySelector('[data-action="proceed-review"]');
    if (proceedButton) {
      const destination = proceedButton.dataset.destination
        || routes.review
        || proceedButton.getAttribute('href')
        || window.location.href;

      const reviewSaveUrl = routes.saveReview || routes.reviewSave || routes.review_design || routes.reviewDesign || null;

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

        const revertState = () => {
          proceedButton.disabled = false;
          proceedButton.dataset.navigating = '0';
        };

        try {
          console.log('[InkWise Studio] Proceeding to review...');
          if (autosave) {
            await autosave.flush('navigate');
          }

          const payload = await buildReviewSavePayload();
          console.log('[InkWise Studio] Payload built:', payload);
          if (!payload.template_id) {
            console.error('[InkWise Studio] Missing template_id in payload:', payload);
            throw new Error('Missing template identifier.');
          }
          if (!reviewSaveUrl) {
            throw new Error('Save endpoint unavailable.');
          }

          const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          };
          if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
          }

          const response = await fetch(reviewSaveUrl, {
            method: 'POST',
            headers,
            credentials: 'same-origin',
            body: JSON.stringify(payload),
          });

          if (!response.ok) {
            const text = await response.text().catch(() => '');
            throw new Error(`Save failed (${response.status}): ${text}`);
          }

          // Save back design to REVIEW2 folder
          try {
            const backSnapshot = await collectDesignSnapshot('proceed-review', 'back');
            if (backSnapshot) {
              const backPayload = await buildReviewSavePayloadForSide(backSnapshot, 'back');
              await fetch('/design/save-to-review', {
                method: 'POST',
                headers,
                credentials: 'same-origin',
                body: JSON.stringify(backPayload),
              });
            }
          } catch (reviewSaveError) {
            console.warn('[InkWise Studio] Failed to save to REVIEW2', reviewSaveError);
            // Don't block navigation for this
          }

          navigate();
        } catch (navigationError) {
          console.error('[InkWise Studio] Unable to save before navigating.', navigationError);
          if (autosave) {
            autosave.notifyError();
          }
          alert('We could not save your latest design. Please try again before continuing.');
          revertState();
        }
      });
    }

    const saveTemplateButton = document.getElementById('save-template-btn');
    if (saveTemplateButton) {
      saveTemplateButton.addEventListener('click', async (event) => {
        event.preventDefault();
        if (saveTemplateButton.disabled) {
          return;
        }

        // Prompt for template name
        const templateName = prompt('Enter a name for your template:');
        if (!templateName || templateName.trim() === '') {
          return;
        }

        saveTemplateButton.disabled = true;
        saveTemplateButton.textContent = 'Saving…';

        try {
          // Flush any pending autosave
          if (autosave) {
            await autosave.flush('save-template');
          }

          // Collect current design snapshot first
          const currentSnapshot = await collectDesignSnapshot('save-template');
          const designPayload = Object.assign({}, currentSnapshot && currentSnapshot.design ? currentSnapshot.design : {});
          
          // Check if template has back side and collect the other side if needed
          const hasBackSide = cardBg && cardBg.dataset.hasBack === 'true';
          const currentSide = currentSide || 'front';
          const otherSide = currentSide === 'front' ? 'back' : 'front';
          
          if (hasBackSide && !designPayload.sides[otherSide]) {
            const otherSnapshot = await collectDesignSnapshot('save-template', otherSide);
            if (otherSnapshot && otherSnapshot.design && otherSnapshot.design.sides && otherSnapshot.design.sides[otherSide]) {
              designPayload.sides = designPayload.sides || {};
              designPayload.sides[otherSide] = otherSnapshot.design.sides[otherSide];
            }
          }
          
          const snapshotPlaceholders = currentSnapshot && Array.isArray(currentSnapshot.placeholders) ? currentSnapshot.placeholders : [];
          designPayload.placeholders = snapshotPlaceholders;
          if (currentSnapshot && currentSnapshot.product_id) {
            designPayload.product_id = currentSnapshot.product_id;
          }

          const previewImage = currentSnapshot && currentSnapshot.preview ? currentSnapshot.preview.image || null : null;
          const previewImages = currentSnapshot && currentSnapshot.preview && Array.isArray(currentSnapshot.preview.images)
            ? currentSnapshot.preview.images.filter(Boolean)
            : [];
          const requestBody = {
            template_name: templateName.trim(),
            design: designPayload,
            preview_image: previewImage,
            preview_images: previewImages,
            placeholders: snapshotPlaceholders,
            svg_markup: currentSnapshot.svg_markup,
          };

          const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          };
          if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
          }

          // Send to server
          const response = await fetch(routes.saveTemplate, {
            method: 'POST',
            headers,
            credentials: 'same-origin',
            body: JSON.stringify(requestBody),
          });

          if (!response.ok) {
            throw new Error(`Server responded with ${response.status}`);
          }

          const result = await response.json();
          alert(`Template "${result.template_name}" saved successfully!`);

        } catch (error) {
          console.error('[InkWise Studio] Failed to save template.', error);
          alert('Failed to save template. Please try again.');
        } finally {
          saveTemplateButton.disabled = false;
          saveTemplateButton.textContent = 'Save Template';
        }
      });
    }

    const requestImageReplacement = (node) => {
      const targetNode = resolveImageLikeNode(node);
      if (!targetNode) {
        return;
      }
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
          const updated = applyImageSourceToNode(targetNode, dataUrl);
          if (updated) {
            scheduleOverlaySync();
            scheduleAutosave('image-replace');
          }
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
      activeTextKey = null;
      activeImageKey = null;
      updateOverlaySelectionState();
      document.body.classList.remove('text-toolbar-visible');
      broadcastActiveElementChange();
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

    const applyResize = (state, dx, dy, preserveAspectRatio = false) => {
      if (state.type === 'image') {
        let newX = state.x;
        let newY = state.y;
        let newWidth = state.width;
        let newHeight = state.height;

        // Check if this is a corner handle for aspect ratio preservation
        const isCorner = ['nw', 'ne', 'sw', 'se'].some(c => state.handle === c);
        const shouldPreserveRatio = preserveAspectRatio || isCorner;
        const aspectRatio = state.width / state.height;

        if (shouldPreserveRatio && isCorner) {
          // Use the larger delta to maintain aspect ratio
          const absDx = Math.abs(dx);
          const absDy = Math.abs(dy);
          
          if (absDx > absDy) {
            // Width change is dominant
            if (state.handle.includes('e')) {
              newWidth = state.width + dx;
            } else if (state.handle.includes('w')) {
              newWidth = state.width - dx;
              newX = state.x + dx;
            }
            newHeight = newWidth / aspectRatio;
            if (state.handle.includes('n')) {
              newY = state.y + (state.height - newHeight);
            }
          } else {
            // Height change is dominant
            if (state.handle.includes('s')) {
              newHeight = state.height + dy;
            } else if (state.handle.includes('n')) {
              newHeight = state.height - dy;
              newY = state.y + dy;
            }
            newWidth = newHeight * aspectRatio;
            if (state.handle.includes('w')) {
              newX = state.x + (state.width - newWidth);
            }
          }
        } else {
          // Non-corner handles or no aspect ratio preservation
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
        scheduleAutosave('element-transform');
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

    if (overlay.dataset.cropSessionActive === 'true') {
      return;
    }

      const data = overlayDataByElement.get(overlay);
      if (!data) {
        return;
      }

      if (data.type === 'text') {
        setActiveTextKey(data.previewKey || null);
      } else if (data.type === 'image') {
        setActiveImageKey(data.previewKey || null);
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

      const type = nodeRepresentsImage(node) ? 'image' : 'text';
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

      if (node.dataset.locked === 'true') {
        overlay.classList.add('editor-overlay--locked');
        overlay.style.pointerEvents = 'none';
      }

      updateOverlaySelectionState();

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

      if (previewSvg) {
        previewSvg.setAttribute('viewBox', `0 0 ${width} ${height}`);
        previewSvg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
      }

      if (currentSvgRoot && !currentSvgRoot.getAttribute('viewBox')) {
        currentSvgRoot.setAttribute('viewBox', `0 0 ${width} ${height}`);
        currentSvgRoot.setAttribute('preserveAspectRatio', 'xMidYMid meet');
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

    const hasTemplateCanvas = () => {
      return Boolean(
        templateCanvasPreference
        && Number.isFinite(templateCanvasPreference.width)
        && templateCanvasPreference.width > 0
        && Number.isFinite(templateCanvasPreference.height)
        && templateCanvasPreference.height > 0
      );
    };

    const loadRasterDimensions = (source) => {
      if (!source) {
        return;
      }

      if (hasTemplateCanvas()) {
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

      const collected = new Map();
      let autoNodeCounter = 0;

      const ensureUniqueKey = (desiredKey, node, fallbackPrefix = 'node') => {
        let baseKey = (desiredKey || '').toString().trim();
        if (!baseKey) {
          baseKey = `${fallbackPrefix}-${autoNodeCounter += 1}`;
        }

        let finalKey = baseKey;
        let attempt = 2;
        while (collected.has(finalKey) && collected.get(finalKey) !== node) {
          finalKey = `${baseKey}-${attempt}`;
          attempt += 1;
        }

        return finalKey;
      };

      const registerNode = (node, key, label = null, defaultText = null, type = 'node') => {
        if (!node) {
          return;
        }
        const finalKey = ensureUniqueKey(key, node, type);
        node.setAttribute('data-preview-node', finalKey);
        if (label && !node.dataset.previewLabel) {
          node.dataset.previewLabel = label;
        }
        if (defaultText && !node.dataset.defaultText) {
          node.dataset.defaultText = defaultText;
        }
        collected.set(finalKey, node);
        return finalKey;
      };

      const existing = rootElement.querySelectorAll('[data-preview-node]');
      existing.forEach((node) => {
        const key = node.getAttribute('data-preview-node');
        if (!key) {
          return;
        }
        registerNode(node, key, node.dataset?.previewLabel || null, node.dataset?.defaultText || null, 'node');
      });

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
        const key = img.getAttribute('data-preview-node') || `auto-image-${index + 1}`;
        registerNode(img, key, img.dataset?.previewLabel || null, null, 'image');
      });

      const allTexts = rootElement.querySelectorAll('text, tspan');
      allTexts.forEach((txt, index) => {
        const rawDefault = (txt.dataset?.defaultText || txt.textContent || '').trim() || null;
        const key = txt.getAttribute('data-preview-node') || `auto-text-${index + 1}`;
        registerNode(txt, key, txt.dataset?.previewLabel || null, rawDefault, 'text');
      });

      if (fallbackNodes.length > 0) {
        fallbackNodes.forEach((node, idx) => {
          const key = node.getAttribute('data-preview-node') || `fallback-${idx + 1}`;
          registerNode(node, key, node.dataset?.previewLabel || null, node.dataset?.defaultText || null, 'node');
        });
      }

      const prioritized = [];
      rootElement.querySelectorAll('text[data-preview-node], tspan[data-preview-node]').forEach((node) => {
        const key = node.getAttribute('data-preview-node');
        if (key && collected.has(key)) {
          const resolved = collected.get(key);
          if (resolved && !prioritized.includes(resolved)) {
            prioritized.push(resolved);
          }
        }
      });

      if (prioritized.length > 0) {
        return prioritized;
      }

      return Array.from(collected.values());
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
        if (section === 'background') {
          // closing background modal should clear background selection
          if (backgroundSelected) {
            backgroundSelected = false;
            syncToolbarVisibility();
            broadcastActiveElementChange();
          }
        }
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
      if (section === 'background') {
        // mark background as the active selection so toolbar can operate on it
        setActiveBackground();
      }
      if (section === 'graphics') {
        // Auto-expand the Image category with scrolling for more images
        const imageButton = document.querySelector('.graphics-category-button[data-category="image"]');
        if (imageButton) {
          handleGraphicsCategoryClick(imageButton);
        } else {
          collapseGraphicsCategory();
          showGraphicsPlaceholder();
        }
      }
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

    // Insert graphic into template function
    async function insertGraphicIntoTemplate(item) {
      if (!item || !item.fullUrl) {
        console.error('[InkWise Studio] Invalid graphic item:', item);
        return;
      }

      // Check if background modal is open and background is selected
      const backgroundModal = document.getElementById('background-modal');
      const isBackgroundMode = backgroundModal && backgroundModal.classList.contains('is-open') && backgroundSelected;

      if (isBackgroundMode) {
        // Apply graphic as background image
        try {
          const response = await fetch(item.fullUrl);
          if (!response.ok) {
            throw new Error(`Failed to fetch background image: ${response.status}`);
          }
          const blob = await response.blob();
          const dataUrl = await blobToDataUrl(blob);

          // Apply as background image
          applyBackgroundImage(dataUrl);

          console.log('[InkWise Studio] Applied graphic as background image');
        } catch (error) {
          console.error('[InkWise Studio] Failed to apply background graphic:', error);
          alert('Failed to apply background image. Please try again.');
        }
        return;
      }

      const svgRoot = getSvgRoot(currentSide);
      if (!svgRoot) {
        console.error('[InkWise Studio] No SVG root found for side:', currentSide);
        return;
      }

      try {
        // Generate unique ID for the graphic element
        const graphicId = `graphic-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        const dataPreviewNode = `graphic-${graphicId}`;

        // Determine if this is an SVG graphic (from Iconify) or raster image
        const isSvgGraphic = item.fullUrl.includes('.svg') || item.id.startsWith('iconify-');

        if (isSvgGraphic) {
          // For SVG graphics (icons), fetch and inline the SVG content
          const response = await fetch(item.fullUrl);
          if (!response.ok) {
            throw new Error(`Failed to fetch SVG: ${response.status}`);
          }
          const svgText = await response.text();

          // Create a group element to contain the SVG graphic
          const graphicGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
          graphicGroup.setAttribute('data-preview-node', dataPreviewNode);
          graphicGroup.setAttribute('data-preview-label', item.alt || 'Graphic');
          graphicGroup.setAttribute('class', 'inserted-graphic');
          graphicGroup.setAttribute('data-editable-image', 'true'); // Mark as editable image
          graphicGroup.setAttribute('transform', 'translate(100, 100) scale(0.5)'); // Default position and size

          // Parse and insert SVG content
          const parser = new DOMParser();
          const svgDoc = parser.parseFromString(svgText, 'image/svg+xml');
          const svgElement = svgDoc.documentElement;

          // Copy all child elements from the fetched SVG
          while (svgElement.firstChild) {
            graphicGroup.appendChild(svgElement.firstChild);
          }

          // Add the graphic to the SVG
          svgRoot.appendChild(graphicGroup);

          // Create overlay for editing
          const overlayData = createOverlayForNode(graphicGroup);
          if (overlayData) {
            // Make the graphic editable by adding click handler for crop/resize
            graphicGroup.style.cursor = 'pointer';
            graphicGroup.addEventListener('click', (e) => {
              e.stopPropagation();
              // Trigger crop mode for the graphic
              teardownActiveCropSession();
              const session = createCropSession(overlayData.overlay, graphicGroup);
              if (session) {
                activeCropSession = session;
              }
            });
          }

          console.log('[InkWise Studio] Inserted SVG graphic:', dataPreviewNode);
        } else {
          // For raster images, create an image element
          const imageElement = document.createElementNS('http://www.w3.org/2000/svg', 'image');
          imageElement.setAttribute('data-preview-node', dataPreviewNode);
          imageElement.setAttribute('data-preview-label', item.alt || 'Graphic');
          imageElement.setAttribute('class', 'inserted-graphic');
          imageElement.setAttribute('data-editable-image', 'true'); // Mark as editable image
          imageElement.setAttribute('x', '100');
          imageElement.setAttribute('y', '100');
          imageElement.setAttribute('width', '200');
          imageElement.setAttribute('height', '200');
          imageElement.setAttribute('preserveAspectRatio', 'xMidYMid meet');
          imageElement.setAttributeNS('http://www.w3.org/1999/xlink', 'href', item.fullUrl);

          // Add the image to the SVG
          svgRoot.appendChild(imageElement);

          // Create overlay for editing
          const overlayData = createOverlayForNode(imageElement);
          if (overlayData) {
            // Make the graphic editable by adding click handler for crop/resize
            imageElement.style.cursor = 'pointer';
            imageElement.addEventListener('click', (e) => {
              e.stopPropagation();
              // Trigger crop mode for the graphic
              teardownActiveCropSession();
              const session = createCropSession(overlayData.overlay, imageElement);
              if (session) {
                activeCropSession = session;
              }
            });
          }

          console.log('[InkWise Studio] Inserted raster graphic:', dataPreviewNode);
        }

        // Trigger preview update
        updatePreview(currentSide);

        // Show success message
        console.log('[InkWise Studio] Graphic inserted successfully');

      } catch (error) {
        console.error('[InkWise Studio] Failed to insert graphic:', error);
        alert('Failed to insert graphic. Please try again.');
      }
    }

    // Graphics panel functionality
    const graphicsCategoriesContainer = document.querySelector('.graphics-categories-labels');
    const graphicsSamplesContainer = document.getElementById('graphics-browser-samples');
    const graphicsCategoryButtons = document.querySelectorAll('.graphics-category-button');
    const graphicsSearchForm = document.getElementById('graphics-search-form');
    const graphicsSearchInput = document.getElementById('graphics-search-input');

    const PIXABAY_API_KEY = '53250708-d3f88461e75cb0c2c5366a181';
    const UNSPLASH_API_KEY = 'iFpUZ_6aTnLGz0Voz0MYlprq9i_RBl83ux9DzV6EMOs';
    const FLATICON_API_KEY = 'FPSX2c99579cb6ea5314189561ca375a1648';

    const GRAPHICS_CATEGORY_CONFIG = {
      shapes: {
        provider: 'iconify',
        baseUrl: 'https://api.iconify.design',
        perPage: 36,
        query: 'circle',
        prefix: '',
        label: 'Shapes',
        searchPlaceholder: 'Search shapes',
      },
      image: {
        provider: 'unsplash',
        apiKey: UNSPLASH_API_KEY,
        perPage: 12,
        query: 'wedding invitation design',
        label: 'Images',
        searchPlaceholder: 'Search images',
      },
      icons: {
        provider: 'flaticon',
        apiKey: FLATICON_API_KEY,
        perPage: 48,
        query: 'wedding icon',
        label: 'Icons',
        searchPlaceholder: 'Search icons',
      },
      illustrations: {
        provider: 'pixabay',
        apiKey: PIXABAY_API_KEY,
        perPage: 18,
        query: 'wedding illustration',
        imageType: 'illustration',
        label: 'Illustrations',
        searchPlaceholder: 'Search illustrations',
      },
      patterns: {
        provider: 'pixabay',
        apiKey: PIXABAY_API_KEY,
        perPage: 18,
        query: 'seamless pattern',
        imageType: 'vector',
        label: 'Patterns',
        searchPlaceholder: 'Search patterns',
      },
    };

    let activeGraphicsButton = null;
    const graphicsQueryState = new Map();
    let graphicsRequestSerial = 0;

    function resolveGraphicsQuery(config, searchTerm) {
      const trimmed = (searchTerm || '').trim();
      if (trimmed) {
        return trimmed;
      }
      return config.query || config.defaultQuery || '';
    }

    function showGraphicsPlaceholder() {
      if (!graphicsSamplesContainer) return;
      graphicsSamplesContainer.innerHTML = '';
      graphicsSamplesContainer.dataset.category = '';
      graphicsSamplesContainer.dataset.page = '0';
      graphicsSamplesContainer.dataset.loading = '0';
      graphicsSamplesContainer.dataset.hasMore = '0';
      graphicsSamplesContainer.dataset.searchTerm = '';
      graphicsSamplesContainer.dataset.requestToken = '';
    }

    function collapseGraphicsCategory() {
      if (!graphicsCategoriesContainer || !graphicsSamplesContainer) {
        return;
      }
      const rows = graphicsCategoriesContainer.querySelectorAll('.category-row');
      rows.forEach((row) => {
        row.style.display = 'flex';
        row.dataset.expanded = '0';
      });
      graphicsCategoryButtons.forEach((button) => {
        button.setAttribute('aria-expanded', 'false');
        button.classList.remove('is-open');
      });
      activeGraphicsButton = null;
      graphicsSamplesContainer.scrollTop = 0;
      graphicsSamplesContainer.innerHTML = '';
      graphicsSamplesContainer.dataset.category = '';
      graphicsSamplesContainer.dataset.page = '0';
      graphicsSamplesContainer.dataset.loading = '0';
      graphicsSamplesContainer.dataset.hasMore = '0';
      graphicsSamplesContainer.dataset.searchTerm = '';
      graphicsSamplesContainer.dataset.requestToken = '';
      if (graphicsSearchForm) {
        graphicsSearchForm.classList.add('is-hidden');
        graphicsSearchForm.dataset.category = '';
      }
      if (graphicsSearchInput) {
        graphicsSearchInput.value = '';
        graphicsSearchInput.blur();
      }
    }

    function getCategoryConfig(category) {
      return GRAPHICS_CATEGORY_CONFIG[category] || null;
    }

    async function handleGraphicsCategoryClick(button) {
      const category = button.dataset.category;
      if (!category || !graphicsSamplesContainer || !graphicsCategoriesContainer) {
        return;
      }

      const isExpanded = button.getAttribute('aria-expanded') === 'true';
      if (isExpanded) {
        collapseGraphicsCategory();
        showGraphicsPlaceholder();
        return;
      }

      const config = getCategoryConfig(category);
      if (!config) {
        console.warn(`[InkWise Studio] No graphics configuration found for category "${category}".`);
        return;
      }

      collapseGraphicsCategory();

      const savedQuery = graphicsQueryState.get(category) || '';
      const labelFromConfig = config.label || '';
      const labelFromDom = button.closest('.category-row')?.querySelector('.category-label')?.textContent?.trim();
      const resolvedLabel = labelFromConfig || labelFromDom || category;
      const placeholder = config.searchPlaceholder || `Search ${resolvedLabel.toLowerCase()}`;

      if (graphicsSearchForm) {
        graphicsSearchForm.classList.remove('is-hidden');
        graphicsSearchForm.dataset.category = category;
      }
      if (graphicsSearchInput) {
        graphicsSearchInput.placeholder = placeholder;
        graphicsSearchInput.value = savedQuery;
      }

      // Update UI state
      button.setAttribute('aria-expanded', 'true');
      button.classList.add('is-open');
      activeGraphicsButton = button;

      const rows = graphicsCategoriesContainer.querySelectorAll('.category-row');
      rows.forEach((row) => {
        if (row.dataset.categoryRow === category) {
          row.style.display = 'flex';
          row.dataset.expanded = '1';
        } else {
          row.style.display = 'none';
        }
      });

      graphicsSamplesContainer.innerHTML = '';
      graphicsSamplesContainer.dataset.category = category;
      graphicsSamplesContainer.dataset.page = '0';
      graphicsSamplesContainer.dataset.loading = '0';
      graphicsSamplesContainer.dataset.hasMore = '1';
      graphicsSamplesContainer.dataset.searchTerm = savedQuery;
      graphicsSamplesContainer.scrollTop = 0;
      const requestToken = String(++graphicsRequestSerial);
      graphicsSamplesContainer.dataset.requestToken = requestToken;

      renderGraphicsLoading();
      await loadGraphicsCategoryPage(category, 1, savedQuery, requestToken);
    }

    function renderGraphicsLoading() {
      if (!graphicsSamplesContainer) return;
      const existing = graphicsSamplesContainer.querySelector('.graphics-loading');
      if (existing) {
        existing.style.display = 'flex';
        return;
      }
      const loader = document.createElement('div');
      loader.className = 'graphics-loading';
      loader.textContent = 'Loading assets…';
      graphicsSamplesContainer.appendChild(loader);
    }

    function removeGraphicsLoading() {
      if (!graphicsSamplesContainer) return;
      const existing = graphicsSamplesContainer.querySelector('.graphics-loading');
      if (existing) {
        existing.remove();
      }
    }

    function renderGraphicsError(message) {
      if (!graphicsSamplesContainer) return;
      graphicsSamplesContainer.innerHTML = '';
      const error = document.createElement('div');
      error.className = 'graphics-error';
      error.textContent = message || 'Unable to load assets at the moment.';
      graphicsSamplesContainer.appendChild(error);
    }

    function appendGraphicsItems(items) {
      if (!graphicsSamplesContainer || !Array.isArray(items)) {
        return;
      }
      const fragment = document.createDocumentFragment();
      items.forEach((item) => {
        if (!item || !item.thumbUrl) {
          return;
        }
        const wrapper = document.createElement('div');
        wrapper.className = 'graphics-sample';
        wrapper.title = item.alt || 'Graphic sample';
        const img = document.createElement('img');
        img.src = item.thumbUrl;
        img.alt = item.alt || 'Graphic sample';
        img.loading = 'lazy';
        wrapper.appendChild(img);
        if (item.label) {
          const label = document.createElement('span');
          label.className = 'sample-label';
          label.textContent = item.label;
          wrapper.appendChild(label);
        }
        wrapper.addEventListener('click', async () => {
          console.log('Selected asset', item);
          try {
            await insertGraphicIntoTemplate(item);
          } catch (error) {
            console.error('[InkWise Studio] Failed to insert graphic:', error);
          }
        });
        fragment.appendChild(wrapper);
      });
      removeGraphicsLoading();
      graphicsSamplesContainer.appendChild(fragment);
    }

    async function loadGraphicsCategoryPage(category, page, searchTermOverride, requestTokenOverride) {
      if (!graphicsSamplesContainer) {
        return;
      }
      const config = getCategoryConfig(category);
      if (!config) {
        return;
      }

      const activeToken = graphicsSamplesContainer.dataset.requestToken || '';
      const requestToken = requestTokenOverride || activeToken || String(++graphicsRequestSerial);

      if (graphicsSamplesContainer.dataset.loading === '1' && requestToken === activeToken) {
        return;
      }

      graphicsSamplesContainer.dataset.loading = '1';

      renderGraphicsLoading();

      try {
        const effectiveSearchTerm = typeof searchTermOverride === 'string'
          ? searchTermOverride
          : graphicsSamplesContainer.dataset.searchTerm || '';
        const items = await fetchGraphicsItems(config, page, effectiveSearchTerm);
        if (graphicsSamplesContainer.dataset.requestToken !== requestToken) {
          return;
        }
        graphicsSamplesContainer.dataset.loading = '0';
        removeGraphicsLoading();

        if (!items.length && page === 1) {
          renderGraphicsError('No assets found for this category right now. Try again in a moment.');
          graphicsSamplesContainer.dataset.hasMore = '0';
          return;
        }

        appendGraphicsItems(items);
        graphicsSamplesContainer.dataset.page = String(page);

        const perPage = config.perPage || 12;
        graphicsSamplesContainer.dataset.hasMore = items.length >= perPage ? '1' : '0';
      } catch (error) {
        console.error('[InkWise Studio] Failed to load graphics assets:', error);
        if (graphicsSamplesContainer.dataset.requestToken !== requestToken) {
          return;
        }
        graphicsSamplesContainer.dataset.loading = '0';
        renderGraphicsError('We ran into a problem loading assets. Please try again.');
        graphicsSamplesContainer.dataset.hasMore = '0';
      }
    }

    async function fetchGraphicsItems(config, page, searchTerm) {
      switch (config.provider) {
        case 'unsplash':
          return fetchUnsplashItems(config, page, searchTerm);
        case 'pixabay':
          return fetchPixabayItems(config, page, searchTerm);
        case 'flaticon':
          return fetchFlaticonItems(config, page, searchTerm);
        case 'iconify':
          return fetchIconifyItems(config, page, searchTerm);
        default:
          console.warn('[InkWise Studio] Unknown graphics provider:', config.provider);
          return [];
      }
    }

    async function fetchUnsplashItems(config, page, searchTerm) {
      const perPage = config.perPage || 12;
      const query = resolveGraphicsQuery(config, searchTerm);
      const baseUrl = query ? 'https://api.unsplash.com/search/photos' : 'https://api.unsplash.com/photos';
      const params = new URLSearchParams({
        per_page: String(perPage),
        page: String(page),
        client_id: config.apiKey,
      });
      if (query) {
        params.set('query', query);
        params.set('orientation', 'portrait');
      }
      const url = `${baseUrl}?${params.toString()}`;
      const response = await fetch(url);
      if (!response.ok) {
        throw new Error(`Unsplash API error: ${response.status}`);
      }
      const payload = await response.json();
      const results = Array.isArray(payload)
        ? payload
        : Array.isArray(payload.results)
          ? payload.results
          : [];
      return results
        .map((item) => {
          const urls = item.urls || {};
          return {
            id: `unsplash-${item.id}`,
            thumbUrl: urls.small || urls.thumb || urls.regular || '',
            fullUrl: urls.full || urls.regular || urls.raw || '',
            alt: item.alt_description || item.description || item.slug || 'Unsplash image',
          };
        })
        .filter((item) => Boolean(item.thumbUrl));
    }

    async function fetchPixabayItems(config, page, searchTerm) {
      const perPage = config.perPage || 18;
      const params = new URLSearchParams({
        key: config.apiKey,
        q: resolveGraphicsQuery(config, searchTerm),
        per_page: String(perPage),
        page: String(page),
        safesearch: 'true',
        order: 'popular',
      });
      if (config.imageType) {
        params.set('image_type', config.imageType);
      }
      if (config.category) {
        params.set('category', config.category);
      }
      const response = await fetch(`https://pixabay.com/api/?${params.toString()}`);
      if (!response.ok) {
        throw new Error(`Pixabay API error: ${response.status}`);
      }
      const payload = await response.json();
      const hits = Array.isArray(payload?.hits) ? payload.hits : [];
      return hits
        .map((hit) => ({
          id: `pixabay-${hit.id}`,
          thumbUrl: hit.previewURL || hit.webformatURL || '',
          fullUrl: hit.largeImageURL || hit.webformatURL || '',
          alt: hit.tags || 'Pixabay asset',
          label: hit.user ? `@${hit.user}` : null,
        }))
        .filter((item) => Boolean(item.thumbUrl));
    }

    async function fetchFlaticonItems(config, page, searchTerm) {
      const perPage = config.perPage || 48;
      const params = new URLSearchParams({
        q: resolveGraphicsQuery(config, searchTerm) || 'icon',
        limit: String(perPage),
        page: String(page),
      });
      params.set('apikey', config.apiKey);
      const response = await fetch(`https://api.flaticon.com/v3/search/icons?${params.toString()}`, {
        headers: {
          Accept: 'application/json',
          apikey: config.apiKey,
          Authorization: `Bearer ${config.apiKey}`,
        },
      });
      if (!response.ok) {
        throw new Error(`Flaticon API error: ${response.status}`);
      }
      const payload = await response.json();
      const rawItems = Array.isArray(payload?.data)
        ? payload.data
        : Array.isArray(payload?.icons)
          ? payload.icons
          : Array.isArray(payload?.result)
            ? payload.result
            : [];
      return rawItems
        .map((icon) => {
          const images = icon?.images || icon;
          const png = images?.png || {};
          const svg = images?.svg || images?.['svg'] || null;
          const thumb = png['128'] || png['256'] || svg || images?.['64'] || null;
          const full = png['512'] || png['256'] || svg || thumb;
          if (!thumb) {
            return null;
          }
          return {
            id: `flaticon-${icon?.id || icon?.icon_id || icon?.id_icon || Math.random().toString(36).slice(2)}`,
            thumbUrl: thumb,
            fullUrl: full,
            alt: icon?.description || (Array.isArray(icon?.tags) ? icon.tags.join(', ') : 'Icon asset'),
            label: icon?.uploader?.name || icon?.category || null,
          };
        })
        .filter(Boolean);
    }

    async function fetchIconifyItems(config, page, searchTerm) {
      const perPage = config.perPage || 36;
      const offset = Math.max(0, (page - 1) * perPage);
      const params = new URLSearchParams({
        query: resolveGraphicsQuery(config, searchTerm) || 'shape',
        limit: String(perPage),
        offset: String(offset),
      });
      if (config.prefix) {
        params.set('prefix', config.prefix);
      }

      const baseUrl = (config.baseUrl || 'https://api.iconify.design').replace(/\/$/, '');
      const response = await fetch(`${baseUrl}/search?${params.toString()}`, {
        headers: {
          Accept: 'application/json',
        },
      });
      if (!response.ok) {
        throw new Error(`Iconify API error: ${response.status}`);
      }

      const payload = await response.json();
      const icons = Array.isArray(payload?.icons) ? payload.icons : [];
      const inferredPrefix = Array.isArray(payload?.prefixes) && payload.prefixes.length === 1
        ? payload.prefixes[0]
        : payload?.prefix || config.prefix || '';

      return icons
        .map((entry) => {
          if (typeof entry === 'string') {
            const fullName = entry.includes(':') ? entry : inferredPrefix ? `${inferredPrefix}:${entry}` : entry;
            if (!fullName) {
              return null;
            }
            const encoded = fullName
              .split(':')
              .map((part) => encodeURIComponent(part))
              .join(':');
            const display = fullName.split(':').pop() || fullName;
            return {
              id: `iconify-${fullName.replace(/[^a-zA-Z0-9:]/g, '-')}-${page}`,
              thumbUrl: `${baseUrl}/${encoded}.svg?height=120&width=120`,
              fullUrl: `${baseUrl}/${encoded}.svg`,
              alt: display.replace(/[-_]/g, ' '),
              label: display.replace(/[-_]/g, ' '),
            };
          }

          if (entry && typeof entry === 'object') {
            const name = entry.name || entry.icon || entry.id || '';
            if (!name) {
              return null;
            }
            const prefix = entry.prefix || inferredPrefix;
            const fullName = prefix ? `${prefix}:${name}` : name;
            const encoded = fullName
              .split(':')
              .map((part) => encodeURIComponent(part))
              .join(':');
            const display = entry.label || name;
            return {
              id: `iconify-${fullName.replace(/[^a-zA-Z0-9:]/g, '-')}-${page}`,
              thumbUrl: `${baseUrl}/${encoded}.svg?height=120&width=120`,
              fullUrl: `${baseUrl}/${encoded}.svg`,
              alt: display.replace(/[-_]/g, ' '),
              label: display.replace(/[-_]/g, ' '),
            };
          }

          return null;
        })
        .filter((item) => Boolean(item?.thumbUrl));
    }

    async function handleGraphicsScroll() {
      if (!graphicsSamplesContainer) {
        return;
      }
      const category = graphicsSamplesContainer.dataset.category;
      if (!category) {
        return;
      }
      if (graphicsSamplesContainer.dataset.loading === '1') {
        return;
      }
      if (graphicsSamplesContainer.dataset.hasMore === '0') {
        return;
      }
      const scrollBottom = graphicsSamplesContainer.scrollTop + graphicsSamplesContainer.clientHeight;
      if (scrollBottom >= graphicsSamplesContainer.scrollHeight - 180) {
        const nextPage = Number(graphicsSamplesContainer.dataset.page || '1') + 1;
        const activeSearch = graphicsSamplesContainer.dataset.searchTerm || '';
        await loadGraphicsCategoryPage(category, nextPage, activeSearch);
      }
    }

    graphicsCategoryButtons.forEach((button) => {
      button.addEventListener('click', () => handleGraphicsCategoryClick(button));
    });

    if (graphicsSamplesContainer) {
      graphicsSamplesContainer.addEventListener('scroll', () => {
        handleGraphicsScroll().catch((error) => {
          console.error('[InkWise Studio] graphics scroll handler error:', error);
        });
      });

      showGraphicsPlaceholder();
    }

    if (graphicsSearchForm && graphicsSearchInput) {
      graphicsSearchForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!graphicsSamplesContainer) {
          return;
        }
        const category = graphicsSamplesContainer.dataset.category;
        if (!category || !getCategoryConfig(category)) {
          return;
        }

        const searchTerm = graphicsSearchInput.value.trim();
        graphicsQueryState.set(category, searchTerm);
        graphicsSamplesContainer.scrollTop = 0;
        graphicsSamplesContainer.innerHTML = '';
        graphicsSamplesContainer.dataset.page = '0';
        graphicsSamplesContainer.dataset.hasMore = '1';
        graphicsSamplesContainer.dataset.searchTerm = searchTerm;
        graphicsSamplesContainer.dataset.loading = '0';
        const requestToken = String(++graphicsRequestSerial);
        graphicsSamplesContainer.dataset.requestToken = requestToken;

        try {
          await loadGraphicsCategoryPage(category, 1, searchTerm, requestToken);
        } catch (error) {
          console.error('[InkWise Studio] graphics search failed:', error);
        }
      });
    }

    const getPreviewInputForKey = (key) => {
      if (!key || !textFieldList) {
        return null;
      }
      return textFieldList.querySelector(`input[data-preview-target="${key}"]`);
    };

    const getPreviewPillForKey = (key) => {
      if (!key || !extraPreviewContainer) {
        return null;
      }
      return extraPreviewContainer.querySelector(`[data-preview-node="${key}"]`);
    };

    const ensureCropStyles = (() => {
      let applied = false;
      return () => {
        if (applied || typeof document === 'undefined') {
          return;
        }
        const style = document.createElement('style');
        style.id = 'inkwise-crop-overlay-styles';
        style.textContent = `
.crop-overlay {
  position: absolute;
  inset: 0;
  z-index: 12;
}

.crop-overlay__scrim {
  position: absolute;
  background: rgba(15, 23, 42, 0.35);
  backdrop-filter: blur(10px);
  pointer-events: none;
  transition: transform 0.18s ease, opacity 0.18s ease;
}

.crop-frame {
  position: absolute;
  border-radius: 18px;
  border: 1.5px solid rgba(255, 255, 255, 0.92);
  box-shadow: 0 18px 36px rgba(15, 23, 42, 0.3);
  pointer-events: auto;
  cursor: grab;
  background: rgba(15, 23, 42, 0.08);
  transition: box-shadow 0.24s ease, border-color 0.24s ease;
}

.crop-frame:active {
  cursor: grabbing;
  box-shadow: 0 24px 48px rgba(15, 23, 42, 0.34);
}

.crop-frame::after {
  content: '';
  position: absolute;
  inset: 0;
  border: 1px solid rgba(255, 255, 255, 0.45);
  border-radius: inherit;
  pointer-events: none;
  mix-blend-mode: screen;
}

.crop-frame__corner {
  position: absolute;
  width: 28px;
  height: 28px;
  pointer-events: auto;
}

.crop-frame__corner .corner-arm {
  position: absolute;
  background: rgba(255, 255, 255, 0.98);
  box-shadow: 0 6px 14px rgba(15, 23, 42, 0.28);
  border-radius: 999px;
  pointer-events: none;
}

.crop-frame__corner .corner-arm--horizontal {
  width: 18px;
  height: 3px;
}

.crop-frame__corner .corner-arm--vertical {
  width: 3px;
  height: 18px;
}

.crop-frame__corner--top-left {
  top: -4px;
  left: -4px;
}

.crop-frame__corner--top-right {
  top: -4px;
  right: -4px;
}

.crop-frame__corner--bottom-left {
  bottom: -4px;
  left: -4px;
}

.crop-frame__corner--bottom-right {
  bottom: -4px;
  right: -4px;
}

.crop-frame__corner--top-left .corner-arm--horizontal,
.crop-frame__corner--bottom-left .corner-arm--horizontal {
  top: 0;
  left: 0;
}

.crop-frame__corner--top-left .corner-arm--vertical,
.crop-frame__corner--top-right .corner-arm--vertical {
  top: 0;
}

.crop-frame__corner--top-right .corner-arm--horizontal,
.crop-frame__corner--bottom-right .corner-arm--horizontal {
  top: 0;
  right: 0;
}

.crop-frame__corner--bottom-left .corner-arm--horizontal,
.crop-frame__corner--bottom-right .corner-arm--horizontal {
  bottom: 0;
}

.crop-frame__corner--bottom-left .corner-arm--vertical,
.crop-frame__corner--bottom-right .corner-arm--vertical {
  bottom: 0;
}

.crop-frame__corner--top-right .corner-arm--vertical,
.crop-frame__corner--bottom-right .corner-arm--vertical {
  right: 0;
}

.crop-frame__corner--top-left .corner-arm--vertical,
.crop-frame__corner--bottom-left .corner-arm--vertical {
  left: 0;
}

.crop-frame__edge-handle {
  position: absolute;
  width: 16px;
  height: 16px;
  border-radius: 999px;
  border: 2px solid rgba(255, 255, 255, 0.92);
  background: rgba(15, 23, 42, 0.55);
  box-shadow: 0 8px 20px rgba(15, 23, 42, 0.32);
  pointer-events: none;
}

.crop-frame__edge-handle--top {
  top: -10px;
  left: 50%;
  transform: translate(-50%, 0);
}

.crop-frame__edge-handle--right {
  right: -10px;
  top: 50%;
  transform: translate(0, -50%);
}

.crop-frame__edge-handle--bottom {
  bottom: -10px;
  left: 50%;
  transform: translate(-50%, 0);
}

.crop-frame__edge-handle--left {
  left: -10px;
  top: 50%;
  transform: translate(0, -50%);
}

.editor-overlay--crop-active {
  border-color: transparent !important;
  box-shadow: none !important;
  background: transparent !important;
}

.editor-overlay--crop-active .editor-overlay__handle {
  display: none !important;
}

.editor-overlay--crop-active::after {
  display: none !important;
}

.editor-overlay--hidden-for-crop {
  opacity: 0;
  pointer-events: none;
}

.crop-overlay__controls {
  position: absolute;
  left: 50%;
  bottom: 24px;
  transform: translateX(-50%);
  display: flex;
  gap: 12px;
  pointer-events: auto;
  z-index: 5;
}

.crop-overlay__button {
  border: none;
  border-radius: 999px;
  padding: 9px 20px;
  font-size: 13px;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #ffffff;
  cursor: pointer;
  background: rgba(15, 23, 42, 0.82);
  box-shadow: 0 16px 36px rgba(15, 23, 42, 0.35);
  transition: background 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
}

.crop-overlay__button:hover {
  background: rgba(15, 23, 42, 0.92);
  transform: translateY(-1px);
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.4);
}

.crop-overlay__button:active {
  transform: translateY(0);
  box-shadow: 0 12px 28px rgba(15, 23, 42, 0.34);
}

.crop-overlay__button:focus-visible {
  outline: 2px solid rgba(96, 165, 250, 0.95);
  outline-offset: 2px;
}

.crop-overlay__button--cancel {
  background: rgba(148, 163, 184, 0.74);
  color: rgba(15, 23, 42, 0.92);
}

.crop-overlay__button--cancel:hover {
  background: rgba(226, 232, 240, 0.92);
  color: rgba(15, 23, 42, 0.95);
}

.crop-overlay__button--apply {
  background: linear-gradient(135deg, rgba(14, 165, 233, 0.92), rgba(59, 130, 246, 0.9));
}

.crop-overlay__button--apply:hover {
  background: linear-gradient(135deg, rgba(14, 165, 233, 1), rgba(37, 99, 235, 0.98));
}

@media (prefers-reduced-motion: reduce) {
  .crop-overlay__scrim,
  .crop-frame {
    transition: none;
  }
}
        `;
        document.head.appendChild(style);
        applied = true;
      };
    })();

    const teardownActiveCropSession = () => {
      if (activeCropSession) {
        activeCropSession.teardown();
        activeCropSession = null;
      }
    };

    const createCropSession = (overlay, node) => {
      if (!overlay || !node) {
        return null;
      }

      ensureCropStyles();

      if (getComputedStyle(overlay).position === 'static') {
        overlay.style.position = 'relative';
      }

      const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

      const recordSourceBounds = () => {
        if (node.dataset.cropSourceWidth && node.dataset.cropSourceHeight) {
          return;
        }
        let bbox = null;
        try {
          bbox = node.getBBox();
        } catch (error) {
          bbox = null;
        }
        const widthCandidate = bbox && Number.isFinite(bbox.width) && bbox.width > 0
          ? bbox.width
          : parseFloat(node.getAttribute('width')) || 0;
        const heightCandidate = bbox && Number.isFinite(bbox.height) && bbox.height > 0
          ? bbox.height
          : parseFloat(node.getAttribute('height')) || 0;
        const xCandidate = bbox && Number.isFinite(bbox.x)
          ? bbox.x
          : parseFloat(node.getAttribute('x')) || 0;
        const yCandidate = bbox && Number.isFinite(bbox.y)
          ? bbox.y
          : parseFloat(node.getAttribute('y')) || 0;
        if (widthCandidate > 0 && heightCandidate > 0) {
          node.dataset.cropSourceWidth = widthCandidate;
          node.dataset.cropSourceHeight = heightCandidate;
          node.dataset.cropSourceX = xCandidate;
          node.dataset.cropSourceY = yCandidate;
        }
      };

      recordSourceBounds();

      const cropOverlay = document.createElement('div');
      cropOverlay.className = 'crop-overlay';

      const initialState = (() => {
        const rectString = typeof node.dataset.cropRect === 'string' ? node.dataset.cropRect : '';
        const clipPathAttr = node.getAttribute('clip-path') || '';
        const clipPathStyle = (node.style && typeof node.style.clipPath === 'string') ? node.style.clipPath : '';
        const webkitClipPathStyle = (node.style && typeof node.style.webkitClipPath === 'string') ? node.style.webkitClipPath : '';
        const clipIdFromDataset = node.dataset.cropClipId || '';
        const clipIdFromAttr = (() => {
          const match = clipPathAttr.match(/^url\(#(.+)\)$/);
          return match ? match[1] : '';
        })();
        const clipId = clipIdFromDataset || clipIdFromAttr;
        let clipRect = null;
        if (clipId) {
          const defs = ensureSvgDefs(node.ownerSVGElement || null);
          const clipPath = defs?.querySelector(`#${clipId}`);
          const rect = clipPath?.querySelector('rect');
          if (rect) {
            clipRect = {
              x: rect.getAttribute('x') || '',
              y: rect.getAttribute('y') || '',
              width: rect.getAttribute('width') || '',
              height: rect.getAttribute('height') || '',
            };
          }
        }
        return {
          rectString,
          clipPathAttr,
          clipId,
          clipRect,
          clipPathStyle,
          webkitClipPathStyle,
        };
      })();

      const suppressedOverlays = [];

      const scrimTop = document.createElement('div');
      scrimTop.className = 'crop-overlay__scrim crop-overlay__scrim--top';
      const scrimRight = document.createElement('div');
      scrimRight.className = 'crop-overlay__scrim crop-overlay__scrim--right';
      const scrimBottom = document.createElement('div');
      scrimBottom.className = 'crop-overlay__scrim crop-overlay__scrim--bottom';
      const scrimLeft = document.createElement('div');
      scrimLeft.className = 'crop-overlay__scrim crop-overlay__scrim--left';

      const scrims = [scrimTop, scrimRight, scrimBottom, scrimLeft];
      scrims.forEach((element) => {
        element.style.pointerEvents = 'none';
        cropOverlay.appendChild(element);
      });

      const frame = document.createElement('div');
      frame.className = 'crop-frame';
      cropOverlay.appendChild(frame);

      const controls = document.createElement('div');
      controls.className = 'crop-overlay__controls';
      const cancelButton = document.createElement('button');
      cancelButton.type = 'button';
      cancelButton.className = 'crop-overlay__button crop-overlay__button--cancel';
      cancelButton.textContent = 'Cancel';
      const applyButton = document.createElement('button');
      applyButton.type = 'button';
      applyButton.className = 'crop-overlay__button crop-overlay__button--apply';
      applyButton.textContent = 'Crop';
      controls.appendChild(cancelButton);
      controls.appendChild(applyButton);
      cropOverlay.appendChild(controls);

      const cornerNames = ['top-left', 'top-right', 'bottom-left', 'bottom-right'];
      const cornerHandleElements = [];
      const cornerCursors = {
        'top-left': 'nwse-resize',
        'bottom-right': 'nwse-resize',
        'top-right': 'nesw-resize',
        'bottom-left': 'nesw-resize',
      };
      cornerNames.forEach((name) => {
        const corner = document.createElement('div');
        corner.className = `crop-frame__corner crop-frame__corner--${name}`;
        corner.dataset.corner = name;
        corner.style.pointerEvents = 'auto';
        corner.style.cursor = cornerCursors[name] || 'nwse-resize';
        const horizontal = document.createElement('span');
        horizontal.className = 'corner-arm corner-arm--horizontal';
        horizontal.style.pointerEvents = 'none';
        const vertical = document.createElement('span');
        vertical.className = 'corner-arm corner-arm--vertical';
        vertical.style.pointerEvents = 'none';
        corner.appendChild(horizontal);
        corner.appendChild(vertical);
        frame.appendChild(corner);
        cornerHandleElements.push(corner);
      });

      const edgeNames = ['top', 'right', 'bottom', 'left'];
      const edgeHandleElements = [];
      edgeNames.forEach((name) => {
        const handle = document.createElement('div');
        handle.className = `crop-frame__edge-handle crop-frame__edge-handle--${name}`;
        handle.dataset.edge = name;
        handle.style.pointerEvents = 'auto';
        handle.style.cursor = (name === 'top' || name === 'bottom') ? 'ns-resize' : 'ew-resize';
        frame.appendChild(handle);
        edgeHandleElements.push(handle);
      });

      overlay.appendChild(cropOverlay);

      let overlayRect = overlay.getBoundingClientRect();
      let overlayWidth = overlayRect.width || overlay.offsetWidth;
      let overlayHeight = overlayRect.height || overlay.offsetHeight;
      let lastOverlayWidth = overlayWidth;
      let lastOverlayHeight = overlayHeight;
      if (!overlayWidth || !overlayHeight) {
        cropOverlay.remove();
        return null;
      }

      overlayDataByNode.forEach((entry) => {
        if (!entry || !entry.overlay) {
          return;
        }
        if (entry.overlay === overlay) {
          return;
        }
        if (!entry.overlay.classList.contains('editor-overlay--hidden-for-crop')) {
          entry.overlay.classList.add('editor-overlay--hidden-for-crop');
          suppressedOverlays.push(entry.overlay);
        }
      });

      overlay.classList.add('editor-overlay--crop-active');
      overlay.dataset.cropSessionActive = 'true';
      scheduleOverlaySync();

      cropOverlay.style.pointerEvents = 'none';
      frame.style.pointerEvents = 'auto';

      const storedRect = (() => {
        const raw = node.dataset.cropRect;
        if (!raw) {
          return null;
        }
        const parts = raw.split(',').map((part) => Number(part));
        if (parts.length !== 4 || parts.some((value) => !Number.isFinite(value))) {
          return null;
        }
        return {
          left: clamp(parts[0], 0, 1),
          top: clamp(parts[1], 0, 1),
          width: clamp(parts[2], 0, 1),
          height: clamp(parts[3], 0, 1),
        };
      })();

      const sourceWidth = parseFloat(node.dataset.cropSourceWidth) || 0;
      const sourceHeight = parseFloat(node.dataset.cropSourceHeight) || 0;

      let minHeight;
      let minWidth;

      const recalcMinimums = () => {
        const baseMin = 48;
        minWidth = Math.min(overlayWidth, Math.max(baseMin, overlayWidth * 0.08));
        minHeight = Math.min(overlayHeight, Math.max(baseMin, overlayHeight * 0.08));
      };

      recalcMinimums();

      const frameRect = {
        left: 0,
        top: 0,
        width: 0,
        height: 0,
      };

      if (storedRect && storedRect.width > 0 && storedRect.height > 0) {
        const widthPx = storedRect.width * overlayWidth;
        const heightPx = storedRect.height * overlayHeight;
        frameRect.width = clamp(widthPx, minWidth, overlayWidth);
        frameRect.height = clamp(heightPx, minHeight, overlayHeight);
        frameRect.left = clamp(storedRect.left * overlayWidth, 0, overlayWidth - frameRect.width);
        frameRect.top = clamp(storedRect.top * overlayHeight, 0, overlayHeight - frameRect.height);
      } else {
        const ratio = (sourceWidth > 0 && sourceHeight > 0)
          ? (sourceWidth / sourceHeight)
          : (overlayWidth > 0 && overlayHeight > 0 ? overlayWidth / overlayHeight : 1);
        let targetWidth;
        let targetHeight;
        if (ratio >= 1) {
          targetWidth = overlayWidth * 0.85;
          targetHeight = targetWidth / ratio;
        } else {
          targetHeight = overlayHeight * 0.85;
          targetWidth = targetHeight * ratio;
        }
        frameRect.width = clamp(targetWidth, minWidth, overlayWidth);
        frameRect.height = clamp(targetHeight, minHeight, overlayHeight);
        frameRect.left = Math.max(0, (overlayWidth - frameRect.width) / 2);
        frameRect.top = Math.max(0, (overlayHeight - frameRect.height) / 2);
      }

      frameRect.width = clamp(frameRect.width, minWidth, overlayWidth);
      frameRect.height = clamp(frameRect.height, minHeight, overlayHeight);
      frameRect.left = clamp(frameRect.left, 0, overlayWidth - frameRect.width);
      frameRect.top = clamp(frameRect.top, 0, overlayHeight - frameRect.height);

      const updateScrims = () => {
        const right = frameRect.left + frameRect.width;
        const bottom = frameRect.top + frameRect.height;

        scrimTop.style.top = '0px';
        scrimTop.style.left = '0px';
        scrimTop.style.width = '100%';
        scrimTop.style.height = `${Math.max(0, frameRect.top)}px`;
        scrimTop.style.display = frameRect.top > 0 ? 'block' : 'none';

        scrimBottom.style.top = `${bottom}px`;
        scrimBottom.style.left = '0px';
        scrimBottom.style.width = '100%';
        scrimBottom.style.height = `${Math.max(0, overlayHeight - bottom)}px`;
        scrimBottom.style.display = bottom < overlayHeight ? 'block' : 'none';

        scrimLeft.style.top = `${frameRect.top}px`;
        scrimLeft.style.left = '0px';
        scrimLeft.style.width = `${Math.max(0, frameRect.left)}px`;
        scrimLeft.style.height = `${frameRect.height}px`;
        scrimLeft.style.display = frameRect.left > 0 ? 'block' : 'none';

        scrimRight.style.top = `${frameRect.top}px`;
        scrimRight.style.left = `${right}px`;
        scrimRight.style.width = `${Math.max(0, overlayWidth - right)}px`;
        scrimRight.style.height = `${frameRect.height}px`;
        scrimRight.style.display = right < overlayWidth ? 'block' : 'none';
      };

      const updateClipPath = (leftRatio, topRatio, widthRatio, heightRatio) => {
        const svg = node.ownerSVGElement;
        if (!svg) {
          return;
        }

        const sourceWidth = parseFloat(node.dataset.cropSourceWidth) || 0;
        const sourceHeight = parseFloat(node.dataset.cropSourceHeight) || 0;
        const sourceX = parseFloat(node.dataset.cropSourceX) || 0;
        const sourceY = parseFloat(node.dataset.cropSourceY) || 0;

        let widthBasis = sourceWidth;
        let heightBasis = sourceHeight;
        let xBasis = sourceX;
        let yBasis = sourceY;

        if (!widthBasis || !heightBasis) {
          let bbox = null;
          try {
            bbox = node.getBBox();
          } catch (error) {
            bbox = null;
          }
          widthBasis = bbox && Number.isFinite(bbox.width) && bbox.width > 0
            ? bbox.width
            : parseFloat(node.getAttribute('width')) || 0;
          heightBasis = bbox && Number.isFinite(bbox.height) && bbox.height > 0
            ? bbox.height
            : parseFloat(node.getAttribute('height')) || 0;
          xBasis = bbox && Number.isFinite(bbox.x)
            ? bbox.x
            : parseFloat(node.getAttribute('x')) || 0;
          yBasis = bbox && Number.isFinite(bbox.y)
            ? bbox.y
            : parseFloat(node.getAttribute('y')) || 0;
        }

        if (!widthBasis || !heightBasis) {
          return;
        }

        const isHtmlImage = node instanceof HTMLImageElement;
        if (isHtmlImage) {
          const insetTop = clamp(topRatio, 0, 1) * 100;
          const insetLeft = clamp(leftRatio, 0, 1) * 100;
          const insetBottom = clamp(1 - (topRatio + heightRatio), 0, 1) * 100;
          const insetRight = clamp(1 - (leftRatio + widthRatio), 0, 1) * 100;
          const clipValue = `inset(${insetTop}% ${insetRight}% ${insetBottom}% ${insetLeft}%)`;
          node.style.clipPath = clipValue;
          node.style.webkitClipPath = clipValue;
          delete node.dataset.cropClipId;
          node.dataset.cropCssClip = clipValue;
          return;
        }

        const defs = ensureSvgDefs(svg);
        if (!defs) {
          return;
        }

        const clipId = node.dataset.cropClipId || `inkwiseCrop_${Date.now()}_${Math.floor(Math.random() * 1000)}`;
        let clipPath = defs.querySelector(`#${clipId}`);
        if (!clipPath) {
          clipPath = document.createElementNS(SVG_NS, 'clipPath');
          clipPath.id = clipId;
          clipPath.setAttribute('clipPathUnits', 'userSpaceOnUse');
          defs.appendChild(clipPath);
        }

        let rect = clipPath.querySelector('rect');
        if (!rect) {
          rect = document.createElementNS(SVG_NS, 'rect');
          clipPath.appendChild(rect);
        }

        const rectWidth = widthBasis * widthRatio;
        const rectHeight = heightBasis * heightRatio;
        const rectX = xBasis + widthBasis * leftRatio;
        const rectY = yBasis + heightBasis * topRatio;

        rect.setAttribute('x', rectX);
        rect.setAttribute('y', rectY);
        rect.setAttribute('width', rectWidth);
        rect.setAttribute('height', rectHeight);

        node.dataset.cropClipId = clipId;
        node.setAttribute('clip-path', `url(#${clipId})`);
        node.style.removeProperty('clip-path');
        node.style.removeProperty('-webkit-clip-path');
        delete node.dataset.cropCssClip;
      };

      const persistCropRect = () => {
        if (!overlayWidth || !overlayHeight) {
          return;
        }
        const previousRect = node.dataset.cropRect || '';
        const hadClip = typeof node.getAttribute('clip-path') === 'string' && node.getAttribute('clip-path').length > 0;
        const svg = node.ownerSVGElement;
        const rawLeft = frameRect.left / overlayWidth;
        const rawTop = frameRect.top / overlayHeight;
        const rawWidth = frameRect.width / overlayWidth;
        const rawHeight = frameRect.height / overlayHeight;

        const leftRatio = clamp(rawLeft, 0, 1);
        const topRatio = clamp(rawTop, 0, 1);
        const widthRatio = clamp(rawWidth, 0, 1 - leftRatio);
        const heightRatio = clamp(rawHeight, 0, 1 - topRatio);
        const rightRatio = leftRatio + widthRatio;
        const bottomRatio = topRatio + heightRatio;

        const nearlyFull = leftRatio <= 0.001 && topRatio <= 0.001 && rightRatio >= 0.999 && bottomRatio >= 0.999;

        if (nearlyFull) {
          const clipId = node.dataset.cropClipId;
          if (clipId) {
            const defs = ensureSvgDefs(svg);
            const existing = defs?.querySelector(`#${clipId}`);
            existing?.remove();
            delete node.dataset.cropClipId;
          }
          node.removeAttribute('clip-path');
          node.style.removeProperty('clip-path');
          node.style.removeProperty('-webkit-clip-path');
          delete node.dataset.cropCssClip;
          delete node.dataset.cropRect;
          if (previousRect || hadClip) {
            autosave?.schedule('image-crop');
            scheduleOverlaySync();
          }
          return;
        }

        const nextRect = [leftRatio, topRatio, widthRatio, heightRatio]
          .map((value) => value.toFixed(6))
          .join(',');

        node.dataset.cropRect = nextRect;

        updateClipPath(leftRatio, topRatio, widthRatio, heightRatio);

        if (previousRect !== nextRect) {
          autosave?.schedule('image-crop');
          scheduleOverlaySync();
        }
      };

      const restoreInitialState = () => {
        const beforeRect = node.dataset.cropRect || '';
        const beforeClipAttr = node.getAttribute('clip-path') || '';
        const beforeClipId = node.dataset.cropClipId || '';

        const svg = node.ownerSVGElement;
        const currentClipId = node.dataset.cropClipId;
        if (currentClipId && currentClipId !== initialState.clipId) {
          const defs = ensureSvgDefs(svg);
          defs?.querySelector(`#${currentClipId}`)?.remove();
        }

        if (initialState.rectString) {
          node.dataset.cropRect = initialState.rectString;
          if (initialState.clipId) {
            node.dataset.cropClipId = initialState.clipId;
          } else {
            delete node.dataset.cropClipId;
          }
          const parts = initialState.rectString.split(',').map((value) => Number(value));
          if (parts.length === 4 && parts.every((value) => Number.isFinite(value))) {
            updateClipPath(parts[0], parts[1], parts[2], parts[3]);
          }
          if (initialState.clipPathAttr) {
            node.setAttribute('clip-path', initialState.clipPathAttr);
          } else {
            node.removeAttribute('clip-path');
          }
          if (initialState.clipPathStyle) {
            node.style.clipPath = initialState.clipPathStyle;
            node.dataset.cropCssClip = initialState.clipPathStyle;
          } else {
            node.style.removeProperty('clip-path');
            delete node.dataset.cropCssClip;
          }
          if (initialState.webkitClipPathStyle) {
            node.style.webkitClipPath = initialState.webkitClipPathStyle;
          } else {
            node.style.removeProperty('-webkit-clip-path');
          }
        } else {
          delete node.dataset.cropRect;
          if (initialState.clipId) {
            node.dataset.cropClipId = initialState.clipId;
            if (initialState.clipRect) {
              const defs = ensureSvgDefs(svg);
              if (defs) {
                let clipPath = defs.querySelector(`#${initialState.clipId}`);
                if (!clipPath) {
                  clipPath = document.createElementNS(SVG_NS, 'clipPath');
                  clipPath.id = initialState.clipId;
                  clipPath.setAttribute('clipPathUnits', 'userSpaceOnUse');
                  defs.appendChild(clipPath);
                }
                let rect = clipPath.querySelector('rect');
                if (!rect) {
                  rect = document.createElementNS(SVG_NS, 'rect');
                  clipPath.appendChild(rect);
                }
                rect.setAttribute('x', initialState.clipRect.x);
                rect.setAttribute('y', initialState.clipRect.y);
                rect.setAttribute('width', initialState.clipRect.width);
                rect.setAttribute('height', initialState.clipRect.height);
              }
            }
            if (initialState.clipPathAttr) {
              node.setAttribute('clip-path', initialState.clipPathAttr);
            }
          } else {
            delete node.dataset.cropClipId;
            if (initialState.clipPathAttr) {
              node.setAttribute('clip-path', initialState.clipPathAttr);
            } else {
              node.removeAttribute('clip-path');
            }
          }
          if (initialState.clipPathStyle) {
            node.style.clipPath = initialState.clipPathStyle;
            node.dataset.cropCssClip = initialState.clipPathStyle;
          } else {
            node.style.removeProperty('clip-path');
            delete node.dataset.cropCssClip;
          }
          if (initialState.webkitClipPathStyle) {
            node.style.webkitClipPath = initialState.webkitClipPathStyle;
          } else {
            node.style.removeProperty('-webkit-clip-path');
          }
        }

        const afterRect = node.dataset.cropRect || '';
        const afterClipAttr = node.getAttribute('clip-path') || '';
        const afterClipId = node.dataset.cropClipId || '';

        if (beforeRect !== afterRect || beforeClipAttr !== afterClipAttr || beforeClipId !== afterClipId) {
          autosave?.schedule('image-crop');
          scheduleOverlaySync();
        }
      };

      const applyFrameRect = () => {
        frame.style.left = `${frameRect.left}px`;
        frame.style.top = `${frameRect.top}px`;
        frame.style.width = `${frameRect.width}px`;
        frame.style.height = `${frameRect.height}px`;
        updateScrims();
        persistCropRect();
      };

      applyFrameRect();

      let dragState = null;
      let resizeState = null;

      const getSafetyInset = () => {
        const width = overlayWidth || overlayRect?.width || cardBg?.clientWidth || 0;
        const height = overlayHeight || overlayRect?.height || cardBg?.clientHeight || 0;
        const base = Math.min(width, height);
        return Math.max(8, Math.round(base * 0.02));
      };

      const snapCoordinate = (value, limit) => {
        const candidates = [];
        const gridSnap = Math.round(value / SNAP_SIZE) * SNAP_SIZE;
        candidates.push(gridSnap);

        // center lines for easy alignment
        if (Number.isFinite(limit) && limit > 0) {
          candidates.push(limit / 2);
        }

        let best = value;
        let bestDelta = Number.POSITIVE_INFINITY;
        candidates.forEach((candidate) => {
          const delta = Math.abs(candidate - value);
          if (delta < bestDelta && delta <= SNAP_THRESHOLD) {
            best = candidate;
            bestDelta = delta;
          }
        });

        return best;
      };

      const onPointerDown = (event) => {
        if (event.button !== 0) {
          return;
        }
        if (event.target !== frame) {
          return;
        }
        event.preventDefault();
        overlayRect = overlay.getBoundingClientRect();
        const widthLimit = overlayRect.width || overlayWidth;
        const heightLimit = overlayRect.height || overlayHeight;

        dragState = {
          pointerId: event.pointerId,
          startX: event.clientX,
          startY: event.clientY,
          widthLimit,
          heightLimit,
          startLeft: frameRect.left,
          startTop: frameRect.top,
        };
        frame.setPointerCapture(event.pointerId);
      };

      const onPointerMove = (event) => {
        if (!dragState || event.pointerId !== dragState.pointerId) {
          return;
        }
        event.preventDefault();
        const dx = event.clientX - dragState.startX;
        const dy = event.clientY - dragState.startY;

        const inset = getSafetyInset();
        const maxLeft = Math.max(0, dragState.widthLimit - frameRect.width - inset * 2);
        const maxTop = Math.max(0, dragState.heightLimit - frameRect.height - inset * 2);

        const rawLeft = dragState.startLeft + dx;
        const rawTop = dragState.startTop + dy;

        const snappedLeft = snapCoordinate(rawLeft, dragState.widthLimit);
        const snappedTop = snapCoordinate(rawTop, dragState.heightLimit);

        frameRect.left = clamp(snappedLeft, inset, inset + maxLeft);
        frameRect.top = clamp(snappedTop, inset, inset + maxTop);
        applyFrameRect();
      };

      const onPointerUp = (event) => {
        if (!dragState || event.pointerId !== dragState.pointerId) {
          return;
        }
        try {
          frame.releasePointerCapture(event.pointerId);
        } catch (error) {
          // ignore release errors
        }
        dragState = null;
      };

      frame.addEventListener('pointerdown', onPointerDown);
      frame.addEventListener('pointermove', onPointerMove);
      frame.addEventListener('pointerup', onPointerUp);
      frame.addEventListener('pointercancel', onPointerUp);
      const onPointerLost = () => {
        dragState = null;
      };
      frame.addEventListener('lostpointercapture', onPointerLost);

      const onEdgePointerDown = (event) => {
        if (event.button !== 0) {
          return;
        }
        event.preventDefault();
        event.stopPropagation();
        overlayRect = overlay.getBoundingClientRect();
        overlayWidth = overlayRect.width || overlay.offsetWidth || overlayWidth;
        overlayHeight = overlayRect.height || overlay.offsetHeight || overlayHeight;
        recalcMinimums();

        resizeState = {
          pointerId: event.pointerId,
          mode: 'edge',
          edge: event.currentTarget.dataset.edge,
          anchor: {
            left: frameRect.left,
            top: frameRect.top,
            right: frameRect.left + frameRect.width,
            bottom: frameRect.top + frameRect.height,
          },
          target: event.currentTarget,
        };
        event.currentTarget.setPointerCapture(event.pointerId);
      };

      const onEdgePointerMove = (event) => {
        if (!resizeState || event.pointerId !== resizeState.pointerId || resizeState.mode !== 'edge') {
          return;
        }
        event.preventDefault();
        overlayRect = overlay.getBoundingClientRect();
        overlayWidth = overlayRect.width || overlay.offsetWidth || overlayWidth;
        overlayHeight = overlayRect.height || overlay.offsetHeight || overlayHeight;
        recalcMinimums();

        const localX = event.clientX - overlayRect.left;
        const localY = event.clientY - overlayRect.top;
        const anchor = resizeState.anchor;
        const inset = getSafetyInset();

        let newLeft = anchor.left;
        let newTop = anchor.top;
        let newWidth = clamp(anchor.right - anchor.left, minWidth, overlayWidth);
        let newHeight = clamp(anchor.bottom - anchor.top, minHeight, overlayHeight);

        if (resizeState.edge === 'top') {
          const limitTop = Math.min(anchor.bottom - minHeight, overlayHeight - minHeight);
          const nextTop = clamp(localY, 0, Math.max(0, limitTop));
          newTop = nextTop;
          newHeight = Math.max(minHeight, anchor.bottom - nextTop);
        } else if (resizeState.edge === 'bottom') {
          const minBottom = anchor.top + minHeight;
          const nextBottom = clamp(localY, minBottom, overlayHeight);
          newTop = anchor.top;
          newHeight = Math.max(minHeight, nextBottom - anchor.top);
        } else if (resizeState.edge === 'left') {
          const limitLeft = Math.min(anchor.right - minWidth, overlayWidth - minWidth);
          const nextLeft = clamp(localX, 0, Math.max(0, limitLeft));
          newLeft = nextLeft;
          newWidth = Math.max(minWidth, anchor.right - nextLeft);
        } else if (resizeState.edge === 'right') {
          const minRight = anchor.left + minWidth;
          const nextRight = clamp(localX, minRight, overlayWidth);
          newLeft = anchor.left;
          newWidth = Math.max(minWidth, nextRight - anchor.left);
        }

        newWidth = clamp(newWidth, minWidth, overlayWidth);
        newHeight = clamp(newHeight, minHeight, overlayHeight);
        newLeft = snapCoordinate(clamp(newLeft, inset, overlayWidth - inset - newWidth), overlayWidth);
        newTop = snapCoordinate(clamp(newTop, inset, overlayHeight - inset - newHeight), overlayHeight);

        frameRect.left = newLeft;
        frameRect.top = newTop;
        frameRect.width = newWidth;
        frameRect.height = newHeight;
        applyFrameRect();
      };

      const onEdgePointerUp = (event) => {
        if (!resizeState || event.pointerId !== resizeState.pointerId) {
          return;
        }
        try {
          resizeState.target?.releasePointerCapture(event.pointerId);
        } catch (error) {
          // ignore pointer capture release errors
        }
        resizeState = null;
        applyFrameRect();
      };

      const onEdgePointerLost = () => {
        resizeState = null;
      };

      const onCornerPointerDown = (event) => {
        if (event.button !== 0) {
          return;
        }
        event.preventDefault();
        event.stopPropagation();
        overlayRect = overlay.getBoundingClientRect();
        overlayWidth = overlayRect.width || overlay.offsetWidth || overlayWidth;
        overlayHeight = overlayRect.height || overlay.offsetHeight || overlayHeight;
        recalcMinimums();

        resizeState = {
          pointerId: event.pointerId,
          mode: 'corner',
          corner: event.currentTarget.dataset.corner,
          anchor: {
            left: frameRect.left,
            top: frameRect.top,
            right: frameRect.left + frameRect.width,
            bottom: frameRect.top + frameRect.height,
          },
          target: event.currentTarget,
        };
        event.currentTarget.setPointerCapture(event.pointerId);
      };

      const onCornerPointerMove = (event) => {
        if (!resizeState || event.pointerId !== resizeState.pointerId || resizeState.mode !== 'corner') {
          return;
        }
        event.preventDefault();
        overlayRect = overlay.getBoundingClientRect();
        overlayWidth = overlayRect.width || overlay.offsetWidth || overlayWidth;
        overlayHeight = overlayRect.height || overlay.offsetHeight || overlayHeight;
        recalcMinimums();

        const localX = event.clientX - overlayRect.left;
        const localY = event.clientY - overlayRect.top;
        const anchor = resizeState.anchor;
        const inset = getSafetyInset();

        let newLeft = anchor.left;
        let newTop = anchor.top;
        let newWidth = clamp(anchor.right - anchor.left, minWidth, overlayWidth);
        let newHeight = clamp(anchor.bottom - anchor.top, minHeight, overlayHeight);

        switch (resizeState.corner) {
          case 'top-left': {
            const candidateLeft = clamp(localX, 0, anchor.right - minWidth);
            const candidateTop = clamp(localY, 0, anchor.bottom - minHeight);
            newLeft = candidateLeft;
            newTop = candidateTop;
            newWidth = Math.max(minWidth, anchor.right - candidateLeft);
            newHeight = Math.max(minHeight, anchor.bottom - candidateTop);
            break;
          }
          case 'top-right': {
            const candidateRight = clamp(localX, anchor.left + minWidth, overlayWidth);
            const candidateTop = clamp(localY, 0, anchor.bottom - minHeight);
            newLeft = anchor.left;
            newTop = candidateTop;
            newWidth = Math.max(minWidth, candidateRight - anchor.left);
            newHeight = Math.max(minHeight, anchor.bottom - candidateTop);
            break;
          }
          case 'bottom-left': {
            const candidateLeft = clamp(localX, 0, anchor.right - minWidth);
            const candidateBottom = clamp(localY, anchor.top + minHeight, overlayHeight);
            newLeft = candidateLeft;
            newTop = anchor.top;
            newWidth = Math.max(minWidth, anchor.right - candidateLeft);
            newHeight = Math.max(minHeight, candidateBottom - anchor.top);
            break;
          }
          case 'bottom-right': {
            const candidateRight = clamp(localX, anchor.left + minWidth, overlayWidth);
            const candidateBottom = clamp(localY, anchor.top + minHeight, overlayHeight);
            newLeft = anchor.left;
            newTop = anchor.top;
            newWidth = Math.max(minWidth, candidateRight - anchor.left);
            newHeight = Math.max(minHeight, candidateBottom - anchor.top);
            break;
          }
          default:
            return;
        }

        newWidth = clamp(newWidth, minWidth, overlayWidth);
        newHeight = clamp(newHeight, minHeight, overlayHeight);
        newLeft = snapCoordinate(clamp(newLeft, inset, overlayWidth - inset - newWidth), overlayWidth);
        newTop = snapCoordinate(clamp(newTop, inset, overlayHeight - inset - newHeight), overlayHeight);

        frameRect.left = newLeft;
        frameRect.top = newTop;
        frameRect.width = newWidth;
        frameRect.height = newHeight;
        applyFrameRect();
      };

      const onCornerPointerUp = (event) => {
        if (!resizeState || event.pointerId !== resizeState.pointerId) {
          return;
        }
        try {
          resizeState.target?.releasePointerCapture(event.pointerId);
        } catch (error) {
          // ignore release errors
        }
        resizeState = null;
        applyFrameRect();
      };

      const onCornerPointerLost = () => {
        resizeState = null;
      };

      edgeHandleElements.forEach((handle) => {
        handle.addEventListener('pointerdown', onEdgePointerDown);
        handle.addEventListener('pointermove', onEdgePointerMove);
        handle.addEventListener('pointerup', onEdgePointerUp);
        handle.addEventListener('pointercancel', onEdgePointerUp);
        handle.addEventListener('lostpointercapture', onEdgePointerLost);
      });

      cornerHandleElements.forEach((handle) => {
        handle.addEventListener('pointerdown', onCornerPointerDown);
        handle.addEventListener('pointermove', onCornerPointerMove);
        handle.addEventListener('pointerup', onCornerPointerUp);
        handle.addEventListener('pointercancel', onCornerPointerUp);
        handle.addEventListener('lostpointercapture', onCornerPointerLost);
      });

      const onCancelClick = (event) => {
        event.preventDefault();
        event.stopPropagation();
        restoreInitialState();
        teardownActiveCropSession();
      };

      const onApplyClick = (event) => {
        event.preventDefault();
        event.stopPropagation();
        applyFrameRect();
        // Keep session open for further cropping
      };

      cancelButton.addEventListener('click', onCancelClick);
      applyButton.addEventListener('click', onApplyClick);

      const onKeyDown = (event) => {
        if (event.key === 'Escape') {
          event.preventDefault();
          restoreInitialState();
          teardownActiveCropSession();
        }
        if (event.key === 'Enter' && (event.metaKey || event.ctrlKey)) {
          event.preventDefault();
          applyFrameRect();
          teardownActiveCropSession();
        }
      };

      document.addEventListener('keydown', onKeyDown);

      const handleResize = () => {
        overlayRect = overlay.getBoundingClientRect();
        overlayWidth = overlayRect.width || overlay.offsetWidth || overlayWidth;
        overlayHeight = overlayRect.height || overlay.offsetHeight || overlayHeight;
        if (!overlayWidth || !overlayHeight) {
          return;
        }
        recalcMinimums();

        const storedRectString = node.dataset.cropRect || null;
        if (storedRectString) {
          const parts = storedRectString.split(',').map((value) => Number(value));
          if (parts.length === 4 && parts.every((value) => Number.isFinite(value))) {
            const nextWidth = clamp(parts[2] * overlayWidth, minWidth, overlayWidth);
            const nextHeight = clamp(parts[3] * overlayHeight, minHeight, overlayHeight);
            const nextLeft = clamp(parts[0] * overlayWidth, 0, overlayWidth - nextWidth);
            const nextTop = clamp(parts[1] * overlayHeight, 0, overlayHeight - nextHeight);
            frameRect.left = nextLeft;
            frameRect.top = nextTop;
            frameRect.width = nextWidth;
            frameRect.height = nextHeight;
          }
        } else {
          const widthRatio = lastOverlayWidth ? frameRect.width / lastOverlayWidth : 1;
          const heightRatio = lastOverlayHeight ? frameRect.height / lastOverlayHeight : 1;
          const leftRatio = lastOverlayWidth ? frameRect.left / lastOverlayWidth : 0;
          const topRatio = lastOverlayHeight ? frameRect.top / lastOverlayHeight : 0;

          frameRect.width = clamp(widthRatio * overlayWidth, minWidth, overlayWidth);
          frameRect.height = clamp(heightRatio * overlayHeight, minHeight, overlayHeight);
          frameRect.left = clamp(leftRatio * overlayWidth, 0, overlayWidth - frameRect.width);
          frameRect.top = clamp(topRatio * overlayHeight, 0, overlayHeight - frameRect.height);
        }

        lastOverlayWidth = overlayWidth;
        lastOverlayHeight = overlayHeight;
        applyFrameRect();
      };

      window.addEventListener('resize', handleResize);

      return {
        overlay,
        refresh: handleResize,
        teardown: () => {
          if (dragState && typeof dragState.pointerId !== 'undefined') {
            try {
              frame.releasePointerCapture(dragState.pointerId);
            } catch (error) {
              // ignore release errors
            }
            dragState = null;
          }
          if (resizeState && typeof resizeState.pointerId !== 'undefined') {
            try {
              resizeState.target?.releasePointerCapture(resizeState.pointerId);
            } catch (error) {
              // ignore release errors
            }
            resizeState = null;
          }
          window.removeEventListener('resize', handleResize);
          frame.removeEventListener('pointerdown', onPointerDown);
          frame.removeEventListener('pointermove', onPointerMove);
          frame.removeEventListener('pointerup', onPointerUp);
          frame.removeEventListener('pointercancel', onPointerUp);
          frame.removeEventListener('lostpointercapture', onPointerLost);
          edgeHandleElements.forEach((handle) => {
            handle.removeEventListener('pointerdown', onEdgePointerDown);
            handle.removeEventListener('pointermove', onEdgePointerMove);
            handle.removeEventListener('pointerup', onEdgePointerUp);
            handle.removeEventListener('pointercancel', onEdgePointerUp);
            handle.removeEventListener('lostpointercapture', onEdgePointerLost);
          });
          cornerHandleElements.forEach((handle) => {
            handle.removeEventListener('pointerdown', onCornerPointerDown);
            handle.removeEventListener('pointermove', onCornerPointerMove);
            handle.removeEventListener('pointerup', onCornerPointerUp);
            handle.removeEventListener('pointercancel', onCornerPointerUp);
            handle.removeEventListener('lostpointercapture', onCornerPointerLost);
          });
          cancelButton.removeEventListener('click', onCancelClick);
          applyButton.removeEventListener('click', onApplyClick);
          document.removeEventListener('keydown', onKeyDown);
          overlay.classList.remove('editor-overlay--crop-active');
          delete overlay.dataset.cropSessionActive;
          suppressedOverlays.forEach((item) => item.classList.remove('editor-overlay--hidden-for-crop'));
          suppressedOverlays.length = 0;
          scheduleOverlaySync();
          if (cropOverlay.parentNode === overlay) {
            overlay.removeChild(cropOverlay);
          } else {
            cropOverlay.remove();
          }
        },
      };
    };

    const syncToolbarVisibility = () => {
      if (activeTextKey || activeImageKey || backgroundSelected) {
        document.body.classList.add('text-toolbar-visible');
      } else {
        document.body.classList.remove('text-toolbar-visible');
      }
    };

    const updateOverlaySelectionState = () => {
      overlayDataByNode.forEach(({ overlay, type }) => {
        if (!overlay) {
          return;
        }
        const overlayKey = overlay.dataset?.previewNode || '';
        const isTextSelected = Boolean(activeTextKey) && overlayKey === activeTextKey && type === 'text';
        const isImageSelected = Boolean(activeImageKey) && overlayKey === activeImageKey && type === 'image';
        overlay.classList.toggle('editor-overlay--selected', isTextSelected || isImageSelected);
      });
    };

    const getActiveElementType = () => {
      if (backgroundSelected) {
        return 'background';
      }
      if (activeImageKey) {
        return 'image';
      }
      if (activeTextKey) {
        return 'text';
      }
      return null;
    };

    const rgbStringToHex = (val) => {
      if (typeof val !== 'string') return null;
      const m = val.match(/rgba?\s*\((\d{1,3}),\s*(\d{1,3}),\s*(\d{1,3})/i);
      if (!m) return null;
      const r = Number(m[1]);
      const g = Number(m[2]);
      const b = Number(m[3]);
      if ([r, g, b].some((n) => Number.isNaN(n) || n < 0 || n > 255)) return null;
      const toHex = (n) => n.toString(16).padStart(2, '0');
      return `#${toHex(r)}${toHex(g)}${toHex(b)}`.toUpperCase();
    };

    function resolveActiveSelectionColor(type) {
      try {
        if (type === 'background') {
          if (!cardBg) return null;
          const cs = window.getComputedStyle(cardBg);
          const bg = cs.backgroundColor || cardBg.style.backgroundColor || '';
          return normalizeHexColor(bg) || rgbStringToHex(bg) || null;
        }
        if (type === 'text') {
          const nodes = getActiveTextNodes();
          if (!nodes.length) return null;
          const node = nodes[0];
          const fill = node.getAttribute('fill') || node.style.fill || node.dataset.color || '';
          return normalizeHexColor(fill) || rgbStringToHex(fill) || null;
        }
        if (type === 'image') {
          const nodes = getActiveImageNodes();
          if (!nodes.length) return null;
          const node = nodes[0];
          const tint = node.dataset?.tintColor || '';
          return normalizeHexColor(tint) || null;
        }
      } catch (e) {
        return null;
      }
      return null;
    }

    const broadcastActiveElementChange = () => {
      if (typeof window === 'undefined') {
        return;
      }
      const type = getActiveElementType();
      const key = type === 'text' ? activeTextKey : type === 'image' ? activeImageKey : null;
      const color = resolveActiveSelectionColor(type);
      try {
        const event = new CustomEvent('inkwise:active-element', { detail: { type, key, color } });
        window.dispatchEvent(event);
      } catch (error) {
        if (typeof document !== 'undefined' && document.createEvent) {
          const legacyEvent = document.createEvent('CustomEvent');
          legacyEvent.initCustomEvent('inkwise:active-element', true, true, { type, key, color });
          window.dispatchEvent(legacyEvent);
        }
      }
    };

    const setActiveTextKey = (key) => {
      const normalized = (typeof key === 'string' && key.trim() !== '') ? key.trim() : null;
      if (activeTextKey === normalized && !activeImageKey) {
        return;
      }
      teardownActiveCropSession();
      activeTextKey = normalized;
      if (normalized) {
        activeImageKey = null;
        backgroundSelected = false;
      }
      updateOverlaySelectionState();
      syncToolbarVisibility();
      broadcastActiveElementChange();
    };

    const setActiveImageKey = (key) => {
      const normalized = (typeof key === 'string' && key.trim() !== '') ? key.trim() : null;
      if (activeImageKey === normalized && !activeTextKey) {
        return;
      }
      if (activeImageKey !== normalized) {
        teardownActiveCropSession();
      }
      activeImageKey = normalized;
      if (normalized) {
        activeTextKey = null;
        backgroundSelected = false;
      }
      updateOverlaySelectionState();
      syncToolbarVisibility();
      broadcastActiveElementChange();
    };

    const setActiveBackground = () => {
      if (backgroundSelected) {
        return;
      }
      teardownActiveCropSession();
      backgroundSelected = true;
      activeTextKey = null;
      activeImageKey = null;
      updateOverlaySelectionState();
      syncToolbarVisibility();
      broadcastActiveElementChange();
    };

    const getActiveTextNodes = () => {
      if (!activeTextKey) {
        return [];
      }
      const root = currentSvgRoot || previewSvg || document;
      if (!root) {
        return [];
      }
      const selector = `[data-preview-node="${activeTextKey}"]`;
      const collected = new Set();
      root.querySelectorAll(selector).forEach((candidate) => {
        if (!candidate || nodeRepresentsImage(candidate)) {
          return;
        }
        const tag = (candidate.tagName || '').toLowerCase();
        if (tag === 'tspan' || tag === 'g') {
          const parentText = candidate.closest('text');
          if (parentText) {
            collected.add(parentText);
            return;
          }
        }
        collected.add(candidate);
      });
      return Array.from(collected);
    };

    const getActiveImageNodes = () => {
      if (!activeImageKey) {
        return [];
      }
      const root = currentSvgRoot || previewSvg || document;
      if (!root) {
        return [];
      }
      const selector = `[data-preview-node="${activeImageKey}"]`;
      let collected = new Set();

      // First try the specified root
      root.querySelectorAll(selector).forEach((candidate) => {
        const target = resolveImageLikeNode(candidate);
        if (target) {
          collected.add(target);
        }
      });

      // If nothing found in root, try the entire document
      if (collected.size === 0 && root !== document) {
        document.querySelectorAll(selector).forEach((candidate) => {
          const target = resolveImageLikeNode(candidate);
          if (target) {
            collected.add(target);
          }
        });
      }

      return Array.from(collected);
    };

    const getActiveInputElement = () => getPreviewInputForKey(activeTextKey);

    const clampNumber = (value, min, max) => {
      const numeric = Number(value);
      if (!Number.isFinite(numeric)) {
        return min;
      }
      return Math.min(Math.max(numeric, min), max);
    };

    const normalizeHexColor = (value) => {
      if (typeof value !== 'string') {
        return null;
      }
      let hex = value.trim();
      if (!hex) {
        return null;
      }
      if (!hex.startsWith('#')) {
        hex = `#${hex}`;
      }
      if (/^#([0-9a-f]{3})$/i.test(hex)) {
        hex = `#${hex[1]}${hex[1]}${hex[2]}${hex[2]}${hex[3]}${hex[3]}`;
      }
      if (!/^#([0-9a-f]{6})$/i.test(hex)) {
        return null;
      }
      return hex.toUpperCase();
    };

    const updateNodeTransform = (node) => {
      if (!node || typeof node.getBBox !== 'function') {
        return;
      }
      const transforms = [];
      const rotationRaw = Number(node.dataset?.rotation || node.getAttribute('data-rotation') || 0);
      if (Number.isFinite(rotationRaw) && rotationRaw !== 0) {
        try {
          const bbox = node.getBBox();
          const cx = bbox.x + bbox.width / 2;
          const cy = bbox.y + bbox.height / 2;
          transforms.push(`rotate(${rotationRaw} ${cx} ${cy})`);
        } catch (error) {
          transforms.push(`rotate(${rotationRaw})`);
        }
      }
      const shapeEffect = node.dataset?.effectShape;
      if (shapeEffect === 'curve') {
        transforms.push('skewX(-8) skewY(4)');
      }
      const combined = transforms.join(' ').trim();
      if (combined) {
        node.setAttribute('transform', combined);
      } else {
        node.removeAttribute('transform');
      }
    };

    const applyToActiveTextNodes = (mutator, options = {}) => {
      const nodes = getActiveTextNodes();
      if (!nodes.length) {
        return false;
      }
      nodes.forEach((node) => {
        try {
          mutator(node);
        } catch (error) {
          console.warn('[InkWise Studio] Toolbar mutation failed.', error);
        }
      });
      if (!options.skipTransformUpdate) {
        nodes.forEach((node) => {
          try {
            updateNodeTransform(node);
          } catch (error) {
            // ignore transform update errors
          }
        });
      }
      if (!options.skipOverlaySync) {
        scheduleOverlaySync();
      }
      if (options.autosaveReason !== false) {
        const reason = typeof options.autosaveReason === 'string' && options.autosaveReason.trim() !== ''
          ? options.autosaveReason
          : 'text-style';
        autosave?.schedule(reason);
      }
      return true;
    };

    const applyToActiveImageNodes = (mutator, options = {}) => {
      const nodes = getActiveImageNodes();
      if (!nodes.length) {
        return false;
      }
      nodes.forEach((node) => {
        try {
          mutator(node);
        } catch (error) {
          console.warn('[InkWise Studio] Image mutation failed.', error);
        }
      });
      if (!options.skipTransformUpdate) {
        nodes.forEach((node) => {
          try {
            updateNodeTransform(node);
          } catch (error) {
            // ignore transform update errors
          }
        });
      }
      if (!options.skipOverlaySync) {
        scheduleOverlaySync();
      }
      if (options.autosaveReason !== false) {
        const reason = typeof options.autosaveReason === 'string' && options.autosaveReason.trim() !== ''
          ? options.autosaveReason
          : 'image-style';
        autosave?.schedule(reason);
      }
      return true;
    };

    const ensureSvgDefs = (fallbackSvg = null) => {
      let rootSvg = currentSvgRoot;
      if (!rootSvg && fallbackSvg) {
        rootSvg = fallbackSvg;
      }
      if (!rootSvg) {
        return null;
      }
      if (typeof rootSvg.tagName === 'string' && rootSvg.tagName.toLowerCase() !== 'svg') {
        const closest = rootSvg.closest?.('svg');
        if (closest) {
          rootSvg = closest;
        } else if (fallbackSvg && fallbackSvg !== rootSvg) {
          rootSvg = fallbackSvg;
        }
      }
      if (!rootSvg) {
        return null;
      }
      let defs = rootSvg.querySelector('defs[data-inkwise-runtime="true"]');
      if (!defs) {
        defs = document.createElementNS(SVG_NS, 'defs');
        defs.setAttribute('data-inkwise-runtime', 'true');
        rootSvg.insertBefore(defs, rootSvg.firstChild || null);
      }
      return defs;
    };

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

      input.addEventListener('focus', () => {
        setActiveTextKey(targetName);
      });

      input.addEventListener('input', () => {
        applyValue();
        scheduleAutosave('text-change');
      });
      applyValue();
    };

    inputs.forEach(initInput);

    const createCustomField = (previewKey, value = '') => {
      const sideForField = normalizeSideKey(currentSide);
      const sanitizedValue = sanitizeTextValue(value);
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
      input.dataset.templateSide = sideForField;
      input.value = sanitizedValue;
      input.dataset.defaultValue = sanitizedValue || '';
      setStoredTextValue(sideForField, previewKey, sanitizedValue);
      captureDefaultTextValue(sideForField, previewKey, sanitizedValue);
      wrapper.appendChild(input);

      const delBtn = document.createElement('button');
      delBtn.type = 'button';
      delBtn.className = 'text-field-delete';
      delBtn.setAttribute('aria-label', 'Delete field');
      delBtn.innerHTML = '<i class="fa-regular fa-trash-can"></i>';
      wrapper.appendChild(delBtn);

      textFieldList.appendChild(wrapper);

      if (extraPreviewContainer) {
        const pill = document.createElement('div');
        pill.className = 'pill';
        pill.dataset.previewNode = previewKey;
        pill.dataset.defaultText = value || 'CUSTOM TEXT';
        pill.textContent = value || 'CUSTOM TEXT';
        extraPreviewContainer.appendChild(pill);
      }

      initInput(input);
      attachDeleteHandler(wrapper);
      scheduleAutosave('add-text-field');
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

    const normalizeSvgTextVisibility = (root) => {
      if (!root) return;

      const findAncestorFill = (node) => {
        let current = node?.parentNode || null;
        while (current) {
          const attrFill = typeof current.getAttribute === 'function' ? current.getAttribute('fill') : null;
          const styleFill = current.style?.fill;
          const dataFill = current.dataset?.color || current.dataset?.defaultColor || null;
          const candidate = attrFill || styleFill || dataFill;
          if (candidate && candidate !== 'none' && candidate !== 'transparent') {
            return candidate;
          }
          current = current.parentNode || null;
        }
        return null;
      };

      const texts = root.querySelectorAll('text, tspan');
      texts.forEach((node) => {
        node.removeAttribute('opacity');
        node.style.opacity = '1';
        if (node.style && node.style.mixBlendMode) {
          node.style.mixBlendMode = 'normal';
        }

        const rawAttributeFill = node.getAttribute('fill');
        const inlineFill = node.style?.fill;
        const datasetFill = node.dataset?.color;
        let resolvedFill = rawAttributeFill && rawAttributeFill !== 'none' ? rawAttributeFill : null;

        if ((!resolvedFill || resolvedFill === 'transparent') && inlineFill && inlineFill !== 'none') {
          resolvedFill = inlineFill;
        }

        if ((!resolvedFill || resolvedFill === 'transparent') && datasetFill && datasetFill !== 'none') {
          resolvedFill = datasetFill;
        }

        if ((!resolvedFill || resolvedFill === 'transparent') && typeof window !== 'undefined' && typeof window.getComputedStyle === 'function') {
          try {
            const computedFill = window.getComputedStyle(node).fill;
            if (computedFill && computedFill !== 'none' && computedFill !== 'transparent') {
              resolvedFill = computedFill;
            }
          } catch (error) {
            // Swallow computed style failures (e.g. detached nodes)
          }
        }

        if (!resolvedFill || resolvedFill === 'transparent' || resolvedFill === 'none') {
          const inheritedFill = findAncestorFill(node);
          if (inheritedFill && inheritedFill !== 'none' && inheritedFill !== 'transparent') {
            resolvedFill = inheritedFill;
          }
        }

        const normalizedFill = normalizeHexColor(resolvedFill || '')
          || rgbStringToHex(resolvedFill || '')
          || (resolvedFill && resolvedFill !== 'none' && resolvedFill !== 'transparent' ? resolvedFill : null);

        const fallbackFill = '#111111';
        const finalFill = normalizedFill || fallbackFill;

        node.setAttribute('fill', finalFill);
        if (node.style) {
          node.style.fill = finalFill;
          node.style.fillOpacity = '1';
          node.style.strokeOpacity = '1';
        }

        if (node.dataset) {
          node.dataset.color = finalFill;
          if (normalizedFill) {
            node.dataset.defaultColor = normalizedFill;
          }
        }

        node.removeAttribute('fill-opacity');
        node.removeAttribute('stroke-opacity');
        if (!node.getAttribute('stroke')) {
          node.setAttribute('stroke', 'none');
        }
        if (!node.getAttribute('paint-order')) {
          node.setAttribute('paint-order', 'stroke fill');
        }
        // Normalize font-family to match loaded Google Fonts
        const fontFamily = node.getAttribute('font-family') || node.style.fontFamily;
        if (fontFamily) {
          const normalized = fontFamily.toLowerCase().replace(/[^a-z]/g, '');
          if (normalized.includes('greatvibes')) {
            node.setAttribute('font-family', 'Great Vibes');
          } else if (normalized.includes('poppins')) {
            node.setAttribute('font-family', 'Poppins');
          }
        }
      });
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
          const commaIndex = url.indexOf(',');
          const meta = commaIndex >= 0 ? url.slice(0, commaIndex) : url;
          const dataPart = commaIndex >= 0 ? url.slice(commaIndex + 1) : '';
          const isBase64 = /;base64/i.test(meta);

          if (isBase64) {
            try {
              const normalized = dataPart.replace(/\s+/g, '');
              svgText = typeof window !== 'undefined' && typeof window.atob === 'function'
                ? window.atob(normalized)
                : atob(normalized);
            } catch (base64Error) {
              try {
                svgText = decodeURIComponent(dataPart);
              } catch (decodeError) {
                console.warn('[InkWise Studio] Failed to decode inline SVG data URI.', base64Error, decodeError);
                svgText = '';
              }
            }
          } else {
            try {
              svgText = decodeURIComponent(dataPart);
            } catch (decodeError) {
              svgText = dataPart;
            }
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
        
        // Hide loading state
        const loadingEl = document.getElementById('canvas-loading');
        if (loadingEl) {
            loadingEl.style.display = 'none';
        }
        
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
          ensureSolidBackground(currentSvgRoot);
          normalizeSvgTextVisibility(currentSvgRoot);
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
            const seenKeys = new Set();
            const seenLabelAndValue = new Set();

            nodes.forEach((node) => {
              const key = node.getAttribute('data-preview-node');
              if (!key) {
                return;
              }

              if (seenKeys.has(key)) {
                return;
              }
              seenKeys.add(key);

              const isImageNode = nodeRepresentsImage(node);
              const isTextNode = nodeRepresentsText(node);

              if (isTextNode) {
                createdTextField = true;
                const labelText = node.dataset?.previewLabel || key;
                const defaultValue = node.dataset?.defaultText || readNodeTextValue(node) || '';

                // Avoid rendering duplicate entries when the label/value combo repeats (e.g., "dave text").
                const normalizedLabel = (labelText || '').toString().trim().toLowerCase();
                const normalizedValue = (defaultValue || '').toString().trim().toLowerCase();
                const labelValueKey = `${normalizedLabel}|${normalizedValue}`;
                if (normalizedLabel && seenLabelAndValue.has(labelValueKey)) {
                  return;
                }

                if (normalizedLabel) {
                  seenLabelAndValue.add(labelValueKey);
                }
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

        // Apply design data from order summary
        const designData = bootstrapPayload?.orderSummary?.metadata?.design;
        if (designData) {
          applyDesignData(designData);
        }

        nodes.forEach((node) => {
          const key = node.getAttribute('data-preview-node');
          if (!key) return;

          const isImageNode = nodeRepresentsImage(node);
          const isTextNode = nodeRepresentsText(node);

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
                input.value = readNodeTextValue(node);
              }
              node.dataset.currentText = readNodeTextValue(node) || '';
              scheduleOverlaySync();
              autosave?.schedule('text-edit');
            });
            node.addEventListener('click', () => {
              setActiveTextKey(key);
              openTextModalAndFocus(key);
            });
          } else if (isImageNode) {
            // Make image replaceable
            node.style.cursor = 'pointer';
            node.addEventListener('click', () => {
              if (node.dataset.locked === 'true') {
                return;
              }
              setActiveImageKey(key);
              requestImageReplacement(node);
            });
          }

          // Sync from input to SVG
          const input = document.querySelector(`#textFieldList input[data-preview-target="${key}"]`);
          if (input && isTextNode) {
            // avoid adding duplicate listeners
            input.addEventListener('input', () => {
              writeNodeTextValue(node, input.value);
              autosave?.schedule('text-change');
            });
            // initialize SVG text from input if present
            if (input.value && input.value.trim() !== '') {
              writeNodeTextValue(node, input.value);
            } else {
              // otherwise keep existing node text (or blank)
              input.value = readNodeTextValue(node) || '';
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

    const exportCurrentCanvasAsPng = async (options = {}) => {
      const {
        scale = 3,
        filename = 'inkwise-template.png',
        download = true,
      } = options;

      const root = currentSvgRoot || previewSvg;
      if (!root) {
        throw new Error('No active canvas to export.');
      }

      ensureSolidBackground(root);

      const serializer = new XMLSerializer();
      const markup = serializer.serializeToString(root);

      const parseNumber = (value) => {
        const num = Number(value);
        return Number.isFinite(num) ? num : null;
      };

      const viewBox = (root.getAttribute('viewBox') || '').split(/[\s,]+/).map(parseNumber);
      const vbWidth = viewBox.length === 4 ? viewBox[2] : null;
      const vbHeight = viewBox.length === 4 ? viewBox[3] : null;

      const baseWidth = parseNumber(cardBg?.dataset?.canvasWidth)
        || parseNumber(canvasWrapper?.dataset?.canvasWidth)
        || vbWidth
        || 1080;
      const baseHeight = parseNumber(cardBg?.dataset?.canvasHeight)
        || parseNumber(canvasWrapper?.dataset?.canvasHeight)
        || vbHeight
        || 1527;

      const exportWidth = Math.max(1, Math.round(baseWidth * Math.max(1, scale)));
      const exportHeight = Math.max(1, Math.round(baseHeight * Math.max(1, scale)));

      const img = new Image();
      img.crossOrigin = 'anonymous';
      img.src = `data:image/svg+xml;charset=utf-8,${encodeURIComponent(markup)}`;

      await new Promise((resolve, reject) => {
        img.onload = resolve;
        img.onerror = reject;
      });

      const canvas = document.createElement('canvas');
      canvas.width = exportWidth;
      canvas.height = exportHeight;
      const ctx = canvas.getContext('2d');
      ctx.fillStyle = '#ffffff';
      ctx.fillRect(0, 0, exportWidth, exportHeight);
      ctx.drawImage(img, 0, 0, exportWidth, exportHeight);

      const dataUrl = canvas.toDataURL('image/png', 1.0);

      if (download) {
        const link = document.createElement('a');
        link.href = dataUrl;
        link.download = filename;
        link.click();
      }

      return { dataUrl, width: exportWidth, height: exportHeight };
    };

    if (typeof window !== 'undefined') {
      window.inkwiseExportCurrentSide = (opts = {}) => exportCurrentCanvasAsPng(opts);
    }

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

        // Remove the corresponding SVG text element if present
        if (currentSvgRoot) {
          const svgText = currentSvgRoot.querySelector(`[data-preview-node="${previewKey}"]`);
          svgText?.remove();
        }

        deleteStoredTextValue(normalizeSideKey(currentSide), previewKey);
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
      if (!sideIsAvailable(side)) {
        return;
      }

      // Flush autosave for current side before switching
      if (autosave && currentSide !== side) {
        try {
          await autosave.flush('side-switch');
        } catch (flushError) {
          console.debug('[InkWise Studio] Autosave flush failed during side switch.', flushError);
        }
      }

      currentSide = side;
      if (!cardBg) {
        return;
      }

      // Load autosaved data for this side if available
      let autosaveData = null;
      try {
        autosaveData = await loadAutosave(side);
      } catch (loadError) {
        console.debug('[InkWise Studio] No autosave data for side:', side);
      }

      let image = '';
      let svgCandidate = '';
      let isUploaded = false;

      if (autosaveData?.png) {
        image = autosaveData.png;
        svgCandidate = autosaveData.svg || '';
        isUploaded = true;
      } else {
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
      }

      let usingSvg = false;
      if (svgCandidate && isSvgSource(svgCandidate)) {
        const rendered = await loadSVGAndBind(svgCandidate);
        if (rendered) {
          usingSvg = true;
          cardBg.style.backgroundImage = 'none';
          cardBg.style.backgroundSize = '';
          normalizeSvgTextVisibility(currentSvgRoot);
        }
      }

      if (!usingSvg) {
        const rasterSource = image || cardBg.dataset[`${side}Image`] || '';
        if (window?.console) {
          console.debug('[InkWise Studio] Using raster preview for', side, rasterSource || '<empty>');
        }
        hideSvgPreview();
        cardBg.style.backgroundImage = rasterSource ? `url('${rasterSource}')` : '';
        cardBg.style.backgroundSize = rasterSource ? 'cover' : '';
        cardBg.style.backgroundPosition = rasterSource ? 'center center' : '';
        cardBg.style.backgroundRepeat = rasterSource ? 'no-repeat' : '';
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
          scheduleAutosave('initial-load');
        });
    } else {
      settleOverlaySync(6);
      scheduleAutosave('initial-load');
    }

    // Delegate clicks inside the preview area: clicking any element with
    // data-preview-node will open the Text modal and focus the corresponding field.
    const previewArea = document.querySelector('.preview-overlay') || document.querySelector('.preview-canvas-wrapper');
    if (previewArea) {
      previewArea.addEventListener('click', (ev) => {
        const node = ev.target.closest('[data-preview-node]');
        if (node) {
          ev.stopPropagation();
          const name = node.dataset.previewNode;
          if (name) {
            if (nodeRepresentsImage(node)) {
              setActiveImageKey(name);
            } else {
              setActiveTextKey(name);
              openTextModalAndFocus(name);
            }
          }
        }
      });
    }

    if (addFieldBtn && textFieldList) {
      addFieldBtn.addEventListener('click', () => {
        console.log('[InkWise Studio] New Text Field button clicked');
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

        // Create pill if container exists
        if (extraPreviewContainer) {
          const pill = document.createElement('div');
          pill.className = 'pill';
          pill.dataset.previewNode = previewKey;
          pill.dataset.defaultText = 'CUSTOM TEXT';
          pill.textContent = 'CUSTOM TEXT';
          extraPreviewContainer.appendChild(pill);
        }

        // Create SVG text element on canvas
        let textElement = null;
        console.log('[InkWise Studio] currentSvgRoot:', currentSvgRoot);
        if (currentSvgRoot) {
          console.log('[InkWise Studio] Creating SVG text element');
          textElement = document.createElementNS('http://www.w3.org/2000/svg', 'text');
          textElement.setAttribute('id', previewKey);
          textElement.setAttribute('data-preview-node', previewKey);
          textElement.setAttribute('data-default-text', 'CUSTOM TEXT');
          textElement.setAttribute('x', '50%');
          textElement.setAttribute('y', '50%');
          textElement.setAttribute('text-anchor', 'middle');
          textElement.setAttribute('dominant-baseline', 'middle');
          textElement.setAttribute('font-family', 'Arial, sans-serif');
          textElement.setAttribute('font-size', '24');
          textElement.setAttribute('fill', '#000000');
          textElement.textContent = 'CUSTOM TEXT';
          textElement.setAttribute('contenteditable', 'true');
          textElement.style.cursor = 'text';
          textElement.style.userSelect = 'text';

          textElement.addEventListener('input', () => {
            if (input) {
              input.value = textElement.textContent;
            }
            textElement.dataset.currentText = textElement.textContent || '';
            scheduleOverlaySync();
            autosave?.schedule('text-edit');
          });

          textElement.addEventListener('click', () => {
            setActiveTextKey(previewKey);
            openTextModalAndFocus(previewKey);
          });

          currentSvgRoot.appendChild(textElement);

          // Create overlay for the new text element
          const overlayData = createOverlayForNode(textElement);
          if (overlayData) {
            syncOverlayForNode(textElement);
          }
          console.log('[InkWise Studio] SVG text element created and appended');
        } else {
          console.log('[InkWise Studio] No currentSvgRoot found, skipping SVG text creation');
        }

        initInput(input);

        // Set up bidirectional sync
        input.addEventListener('input', () => {
          if (textElement) {
            textElement.textContent = input.value;
            textElement.dataset.currentText = input.value;
          }
          autosave?.schedule('text-change');
        });

        setActiveTextKey(previewKey);

        // Initialize input value from SVG if present
        if (textElement && textElement.textContent && textElement.textContent.trim() !== '') {
          input.value = textElement.textContent;
        } else {
          input.value = 'CUSTOM TEXT';
          if (textElement) {
            textElement.textContent = 'CUSTOM TEXT';
          }
        }

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

    uploadedFrontImage = null;
    uploadedBackImage = null;
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

      // Always save to recent uploads
      saveRecentUpload(dataUrl, filename || `upload-${Date.now()}`, side);

      // If an image element is currently selected, update only that element
      if (activeImageKey) {
        const success = applyToActiveImageNodes((node) => {
          node.setAttribute('href', dataUrl);
          node.setAttributeNS('http://www.w3.org/1999/xlink', 'href', dataUrl);
        }, { autosaveReason: 'image-upload' });

        if (success) {
          // Image replaced in selected element, done
          return;
        }
      }

      // No image element selected - add as a new image element to the canvas
      if (currentSvgRoot) {
        const svgEl = currentSvgRoot.tagName?.toLowerCase() === 'svg'
          ? currentSvgRoot
          : currentSvgRoot.closest('svg');

        if (svgEl) {
          const viewBox = svgEl.getAttribute('viewBox');
          let centerX = 200;
          let centerY = 200;
          let defaultSize = 150;

          if (viewBox) {
            const parts = viewBox.split(/[\s,]+/).map(Number);
            if (parts.length === 4 && parts.every(Number.isFinite)) {
              centerX = parts[0] + parts[2] / 2;
              centerY = parts[1] + parts[3] / 2;
              defaultSize = Math.min(parts[2], parts[3]) * 0.3;
            }
          }

          const newImageKey = `uploaded_${Date.now()}`;
          const imageEl = document.createElementNS('http://www.w3.org/2000/svg', 'image');
          imageEl.setAttribute('id', newImageKey);
          imageEl.setAttribute('data-preview-node', newImageKey);
          imageEl.setAttribute('href', dataUrl);
          imageEl.setAttributeNS('http://www.w3.org/1999/xlink', 'href', dataUrl);
          imageEl.setAttribute('x', String(centerX - defaultSize / 2));
          imageEl.setAttribute('y', String(centerY - defaultSize / 2));
          imageEl.setAttribute('width', String(defaultSize));
          imageEl.setAttribute('height', String(defaultSize));
          imageEl.setAttribute('preserveAspectRatio', 'xMidYMid meet');

          svgEl.appendChild(imageEl);

          // Create overlay so the image is draggable and resizable
          const overlayData = createOverlayForNode(imageEl);
          if (overlayData) {
            syncOverlayForNode(imageEl);
          }

          setActiveImageKey(newImageKey);
          autosave?.schedule('image-upload');
          return;
        }
      }

      // Fallback: legacy behavior for raster-only previews
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

          // Remove selected class from all items
          uploadItemsElements.forEach((el) => el.classList.remove('selected'));
          // Add selected class to clicked item
          item.classList.add('selected');

          // If an image element is selected, update only that element
          if (activeImageKey) {
            const success = applyToActiveImageNodes((node) => {
              node.setAttribute('href', imageUrl);
              node.setAttributeNS('http://www.w3.org/1999/xlink', 'href', imageUrl);
            }, { autosaveReason: 'image-select' });

            if (success) {
              // Show a brief success indication
              item.style.transform = 'scale(0.95)';
              setTimeout(() => {
                item.style.transform = '';
              }, 150);
              return;
            }
          }

          // No image element selected - add as a new image element to the canvas
          if (currentSvgRoot) {
            const svgEl = currentSvgRoot.tagName?.toLowerCase() === 'svg'
              ? currentSvgRoot
              : currentSvgRoot.closest('svg');

            if (svgEl) {
              const viewBox = svgEl.getAttribute('viewBox');
              let centerX = 200;
              let centerY = 200;
              let defaultSize = 150;

              if (viewBox) {
                const parts = viewBox.split(/[\s,]+/).map(Number);
                if (parts.length === 4 && parts.every(Number.isFinite)) {
                  centerX = parts[0] + parts[2] / 2;
                  centerY = parts[1] + parts[3] / 2;
                  defaultSize = Math.min(parts[2], parts[3]) * 0.3;
                }
              }

              const newImageKey = `uploaded_${Date.now()}`;
              const imageEl = document.createElementNS('http://www.w3.org/2000/svg', 'image');
              imageEl.setAttribute('id', newImageKey);
              imageEl.setAttribute('data-preview-node', newImageKey);
              imageEl.setAttribute('href', imageUrl);
              imageEl.setAttributeNS('http://www.w3.org/1999/xlink', 'href', imageUrl);
              imageEl.setAttribute('x', String(centerX - defaultSize / 2));
              imageEl.setAttribute('y', String(centerY - defaultSize / 2));
              imageEl.setAttribute('width', String(defaultSize));
              imageEl.setAttribute('height', String(defaultSize));
              imageEl.setAttribute('preserveAspectRatio', 'xMidYMid meet');

              svgEl.appendChild(imageEl);

              // Create overlay so the image is draggable and resizable
              const overlayData = createOverlayForNode(imageEl);
              if (overlayData) {
                syncOverlayForNode(imageEl);
              }

              setActiveImageKey(newImageKey);
              autosave?.schedule('image-select');

              // Show a brief success indication
              item.style.transform = 'scale(0.95)';
              setTimeout(() => {
                item.style.transform = '';
              }, 150);
              return;
            }
          }

          // Fallback: legacy behavior for raster-only previews
          const side = item.dataset.side;
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

          // Remove selected class from all items
          quickUploadItemsElements.forEach((el) => el.classList.remove('selected'));
          // Add selected class to clicked item
          item.classList.add('selected');

          // If an image element is selected, update only that element
          if (activeImageKey) {
            const success = applyToActiveImageNodes((node) => {
              node.setAttribute('href', imageUrl);
              node.setAttributeNS('http://www.w3.org/1999/xlink', 'href', imageUrl);
            }, { autosaveReason: 'image-select' });

            if (success) {
              // Show a brief success indication
              item.style.transform = 'scale(0.9)';
              setTimeout(() => {
                item.style.transform = '';
              }, 150);
              return;
            }
          }

          // No image element selected - add as a new image element to the canvas
          if (currentSvgRoot) {
            const svgEl = currentSvgRoot.tagName?.toLowerCase() === 'svg'
              ? currentSvgRoot
              : currentSvgRoot.closest('svg');

            if (svgEl) {
              const viewBox = svgEl.getAttribute('viewBox');
              let centerX = 200;
              let centerY = 200;
              let defaultSize = 150;

              if (viewBox) {
                const parts = viewBox.split(/[\s,]+/).map(Number);
                if (parts.length === 4 && parts.every(Number.isFinite)) {
                  centerX = parts[0] + parts[2] / 2;
                  centerY = parts[1] + parts[3] / 2;
                  defaultSize = Math.min(parts[2], parts[3]) * 0.3;
                }
              }

              const newImageKey = `uploaded_${Date.now()}`;
              const imageEl = document.createElementNS('http://www.w3.org/2000/svg', 'image');
              imageEl.setAttribute('id', newImageKey);
              imageEl.setAttribute('data-preview-node', newImageKey);
              imageEl.setAttribute('href', imageUrl);
              imageEl.setAttributeNS('http://www.w3.org/1999/xlink', 'href', imageUrl);
              imageEl.setAttribute('x', String(centerX - defaultSize / 2));
              imageEl.setAttribute('y', String(centerY - defaultSize / 2));
              imageEl.setAttribute('width', String(defaultSize));
              imageEl.setAttribute('height', String(defaultSize));
              imageEl.setAttribute('preserveAspectRatio', 'xMidYMid meet');

              svgEl.appendChild(imageEl);

              // Create overlay so the image is draggable and resizable
              const overlayData = createOverlayForNode(imageEl);
              if (overlayData) {
                syncOverlayForNode(imageEl);
              }

              setActiveImageKey(newImageKey);
              autosave?.schedule('image-select');

              // Show a brief success indication
              item.style.transform = 'scale(0.9)';
              setTimeout(() => {
                item.style.transform = '';
              }, 150);
              return;
            }
          }

          // Fallback: legacy behavior for raster-only previews
          const side = item.dataset.side;
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

    const applyFontFamily = (family) => {
      if (!family) {
        return false;
      }
      const success = applyToActiveTextNodes((node) => {
        node.setAttribute('font-family', family);
        node.style.fontFamily = family;
      }, { autosaveReason: 'text-font', skipTransformUpdate: true });
      if (success) {
        const input = getActiveInputElement();
        if (input) {
          input.dataset.fontFamily = family;
        }
      }
      return success;
    };

    const applyFontSize = (size) => {
      const numeric = clampNumber(size, 6, 400);
      const success = applyToActiveTextNodes((node) => {
        node.setAttribute('font-size', numeric);
        node.style.fontSize = `${numeric}px`;
      }, { autosaveReason: 'text-size' });
      if (success) {
        const input = getActiveInputElement();
        if (input) {
          input.dataset.fontSize = String(numeric);
        }
      }
      return success;
    };

    const applyImageScaleFromSize = (size) => {
      const numeric = clampNumber(size, 8, 400);
      const BASE_SIZE = 39;
      return applyToActiveImageNodes((node) => {
        const bbox = typeof node.getBBox === 'function' ? node.getBBox() : null;
        const widthAttr = parseFloat(node.getAttribute('width'));
        const heightAttr = parseFloat(node.getAttribute('height'));
        const baseWidth = Number(node.dataset.baseWidth) || (Number.isFinite(widthAttr) ? widthAttr : bbox?.width || 1);
        const baseHeight = Number(node.dataset.baseHeight) || (Number.isFinite(heightAttr) ? heightAttr : bbox?.height || 1);

        if (!node.dataset.baseWidth) {
          node.dataset.baseWidth = baseWidth;
        }
        if (!node.dataset.baseHeight) {
          node.dataset.baseHeight = baseHeight;
        }

        const scale = numeric / BASE_SIZE;
        const width = Math.max(1, baseWidth * scale);
        const height = Math.max(1, baseHeight * scale);
        node.setAttribute('width', width);
        node.setAttribute('height', height);
      }, { autosaveReason: 'image-scale' });
    };

    const applyColorToText = (color) => {
      const hex = normalizeHexColor(color);
      if (!hex) {
        return false;
      }
      const success = applyToActiveTextNodes((node) => {
        node.setAttribute('fill', hex);
        node.style.fill = hex;
      }, { autosaveReason: 'text-color', skipTransformUpdate: true });
      if (success) {
        const input = getActiveInputElement();
        if (input) {
          input.dataset.color = hex;
        }
      }
      return success;
    };

    const applyBackgroundColor = (color) => {
      const hex = normalizeHexColor(color);
      if (!hex) {
        return false;
      }
      if (!cardBg) return false;
      try {
        cardBg.style.backgroundColor = hex;
        // Keep any background-image but record color for autosave/export
        cardBg.dataset.backgroundColor = hex;
        autosave?.schedule('background-color');
        return true;
      } catch (e) {
        console.warn('[InkWise Studio] Failed to apply background color', e);
        return false;
      }
    };

    const applyBackgroundImage = (imageUrl) => {
      if (!imageUrl) {
        return false;
      }
      if (!cardBg) return false;
      try {
        cardBg.style.backgroundImage = `url(${imageUrl})`;
        cardBg.style.backgroundSize = 'cover';
        cardBg.style.backgroundPosition = 'center';
        cardBg.style.backgroundRepeat = 'no-repeat';
        // Record background image for autosave/export
        cardBg.dataset.backgroundImage = imageUrl;
        autosave?.schedule('background-image');
        return true;
      } catch (e) {
        console.warn('[InkWise Studio] Failed to apply background image', e);
        return false;
      }
    };

    const blobToDataUrl = (blob) => {
      return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(blob);
      });
    };

    const applyImageTint = (color) => {
      const hex = normalizeHexColor(color);
      if (!hex) {
        return false;
      }
      return applyToActiveImageNodes((node) => {
        if (hex === '#FFFFFF') {
          const previousFilter = node.dataset.tintFilterId;
          if (previousFilter) {
            const defs = ensureSvgDefs(node.ownerSVGElement || null);
            const filter = defs?.querySelector(`#${previousFilter}`);
            filter?.remove();
            delete node.dataset.tintFilterId;
          }
          node.removeAttribute('filter');
          // remove recorded tint color when clearing
          try { delete node.dataset.tintColor; } catch (e) {}
          return;
        }

        const defs = ensureSvgDefs(node.ownerSVGElement || null);
        if (!defs) {
          return;
        }

        const filterId = node.dataset.tintFilterId || `inkwiseImageTint_${Date.now()}_${Math.floor(Math.random() * 1000)}`;
        const r = parseInt(hex.slice(1, 3), 16) / 255;
        const g = parseInt(hex.slice(3, 5), 16) / 255;
        const b = parseInt(hex.slice(5, 7), 16) / 255;

        let filter = defs.querySelector(`#${filterId}`);
        if (!filter) {
          filter = document.createElementNS(SVG_NS, 'filter');
          filter.id = filterId;
          filter.setAttribute('color-interpolation-filters', 'sRGB');

          const colorMatrix = document.createElementNS(SVG_NS, 'feColorMatrix');
          colorMatrix.setAttribute('type', 'matrix');
          colorMatrix.setAttribute('values', '0.2126 0.7152 0.0722 0 0  0.2126 0.7152 0.0722 0 0  0.2126 0.7152 0.0722 0 0  0 0 0 1 0');
          filter.appendChild(colorMatrix);

          const transfer = document.createElementNS(SVG_NS, 'feComponentTransfer');
          const funcR = document.createElementNS(SVG_NS, 'feFuncR');
          funcR.setAttribute('type', 'linear');
          transfer.appendChild(funcR);
          const funcG = document.createElementNS(SVG_NS, 'feFuncG');
          funcG.setAttribute('type', 'linear');
          transfer.appendChild(funcG);
          const funcB = document.createElementNS(SVG_NS, 'feFuncB');
          funcB.setAttribute('type', 'linear');
          transfer.appendChild(funcB);
          const funcA = document.createElementNS(SVG_NS, 'feFuncA');
          funcA.setAttribute('type', 'identity');
          transfer.appendChild(funcA);

          filter.appendChild(transfer);
          defs.appendChild(filter);
        }

        const funcREl = filter.querySelector('feFuncR');
        const funcGEl = filter.querySelector('feFuncG');
        const funcBEl = filter.querySelector('feFuncB');
        if (funcREl) {
          funcREl.setAttribute('slope', r.toString());
        }
        if (funcGEl) {
          funcGEl.setAttribute('slope', g.toString());
        }
        if (funcBEl) {
          funcBEl.setAttribute('slope', b.toString());
        }

        node.dataset.tintFilterId = filterId;
        node.setAttribute('filter', `url(#${filterId})`);
        // remember the applied tint color for later queries
        try { node.dataset.tintColor = hex; } catch (e) {}
      }, { autosaveReason: 'image-color', skipTransformUpdate: true });
    };

    const toggleFontWeight = () => applyToActiveTextNodes((node) => {
      const currentAttr = node.getAttribute('font-weight') || node.style.fontWeight || '';
      const numeric = Number(currentAttr);
      const isBold = currentAttr === 'bold' || numeric >= 600;
      if (isBold) {
        node.setAttribute('font-weight', '400');
        node.style.fontWeight = '400';
      } else {
        node.setAttribute('font-weight', '700');
        node.style.fontWeight = '700';
      }
    }, { autosaveReason: 'text-style', skipTransformUpdate: true });

    const toggleFontItalic = () => applyToActiveTextNodes((node) => {
      const current = node.getAttribute('font-style') || node.style.fontStyle || 'normal';
      if (current === 'italic' || current === 'oblique') {
        node.setAttribute('font-style', 'normal');
        node.style.fontStyle = 'normal';
      } else {
        node.setAttribute('font-style', 'italic');
        node.style.fontStyle = 'italic';
      }
    }, { autosaveReason: 'text-style', skipTransformUpdate: true });

    const toggleTextDecoration = (decoration) => applyToActiveTextNodes((node) => {
      const existing = node.style.textDecoration || node.getAttribute('text-decoration') || '';
      const parts = existing.split(/\s+/).filter(Boolean);
      const active = new Set(parts);
      if (active.has(decoration)) {
        active.delete(decoration);
      } else {
        active.add(decoration);
      }
      const value = Array.from(active).join(' ');
      if (value) {
        node.style.textDecoration = value;
        node.setAttribute('text-decoration', value);
      } else {
        node.style.textDecoration = '';
        node.removeAttribute('text-decoration');
      }
    }, { autosaveReason: 'text-style', skipTransformUpdate: true });

    const applyAlignment = (command) => {
      const anchorMap = {
        'align-left': 'start',
        'align-center': 'middle',
        'align-right': 'end',
        'align-justify': 'start',
      };
      const alignValue = {
        'align-left': 'left',
        'align-center': 'center',
        'align-right': 'right',
        'align-justify': 'justify',
      };
      const anchor = anchorMap[command] || 'start';
      const align = alignValue[command] || 'left';
      const success = applyToActiveTextNodes((node) => {
        node.setAttribute('text-anchor', anchor);
        const x = node.getAttribute('x');
        if (x) {
          node.querySelectorAll('tspan').forEach((span) => span.setAttribute('x', x));
        }
        node.style.textAlign = align;
      }, { autosaveReason: 'text-align', skipTransformUpdate: true });
      if (success) {
        const input = getActiveInputElement();
        if (input) {
          input.dataset.align = align;
        }
      }
      return success;
    };

    const applyTextTransform = (mode) => {
      let lastValue = null;
      const success = applyToActiveTextNodes((node) => {
        const text = node.textContent || '';
        let transformed = text;
        if (mode === 'uppercase') {
          transformed = text.toUpperCase();
        } else if (mode === 'lowercase') {
          transformed = text.toLowerCase();
        }
        node.textContent = transformed;
        node.dataset.currentText = transformed;
        lastValue = transformed;
      }, { autosaveReason: 'text-transform', skipTransformUpdate: true });
      if (success) {
        const input = getActiveInputElement();
        if (input && lastValue !== null) {
          input.value = lastValue;
        }
      }
      return success;
    };

    const toggleBulletList = () => {
      const input = getActiveInputElement();
      return applyToActiveTextNodes((node) => {
        const text = node.textContent || '';
        const lines = text.split(/\r?\n/);
        const currentlyBulleted = node.dataset.listType === 'bullet'
          || lines.every((line) => line.trim().startsWith('•'));
        const nextText = currentlyBulleted
          ? lines.map((line) => line.replace(/^\s*•\s*/u, '')).join('\n')
          : lines.map((line) => {
              const trimmed = line.trim();
              return trimmed ? `• ${trimmed}` : '•';
            }).join('\n');
        if (currentlyBulleted) {
          delete node.dataset.listType;
        } else {
          node.dataset.listType = 'bullet';
        }
        node.textContent = nextText;
        node.dataset.currentText = nextText;
        if (input && input.dataset.previewTarget === activeTextKey) {
          input.value = nextText;
        }
      }, { autosaveReason: 'text-list', skipTransformUpdate: true });
    };

    const applyLineSpacing = (value) => {
      const numeric = clampNumber(value, 0.5, 5);
      return applyToActiveTextNodes((node) => {
        node.dataset.lineHeight = numeric;
        node.style.lineHeight = numeric;
      }, { autosaveReason: 'text-spacing', skipTransformUpdate: true });
    };

    const applyLetterSpacing = (value) => {
      if (value === undefined || value === null) {
        return false;
      }
      const numeric = Number(value);
      if (!Number.isFinite(numeric)) {
        return false;
      }
      return applyToActiveTextNodes((node) => {
        if (Math.abs(numeric) < 0.0001) {
          node.style.letterSpacing = '';
          node.removeAttribute('letter-spacing');
          delete node.dataset.letterSpacing;
        } else {
          const emValue = `${numeric.toFixed(2)}em`;
          node.style.letterSpacing = emValue;
          node.setAttribute('letter-spacing', emValue);
          node.dataset.letterSpacing = numeric.toFixed(2);
        }
      }, { autosaveReason: 'text-spacing', skipTransformUpdate: true });
    };

    const applyEffectStyle = (variant) => {
      const style = typeof variant === 'string' ? variant : 'none';
      return applyToActiveTextNodes((node) => {
        switch (style) {
          case 'shadow':
            node.dataset.effectStyle = 'shadow';
            node.style.filter = 'drop-shadow(2px 4px 8px rgba(15, 23, 42, 0.35))';
            node.style.stroke = '';
            node.style.strokeWidth = '';
            node.style.paintOrder = '';
            break;
          case 'highlight':
            node.dataset.effectStyle = 'highlight';
            node.style.filter = 'none';
            node.style.paintOrder = 'stroke fill';
            node.style.stroke = 'rgba(253, 224, 71, 0.85)';
            node.style.strokeWidth = 6;
            break;
          case 'glitch':
            node.dataset.effectStyle = 'glitch';
            node.style.filter = 'drop-shadow(-2px 0 rgba(236, 72, 153, 0.5)) drop-shadow(2px 0 rgba(56, 189, 248, 0.5))';
            node.style.stroke = '';
            node.style.strokeWidth = '';
            node.style.paintOrder = '';
            break;
          case 'echo':
            node.dataset.effectStyle = 'echo';
            node.style.filter = 'drop-shadow(3px 3px rgba(15, 23, 42, 0.4))';
            node.style.stroke = '';
            node.style.strokeWidth = '';
            node.style.paintOrder = '';
            break;
          default:
            delete node.dataset.effectStyle;
            node.style.filter = '';
            node.style.stroke = '';
            node.style.strokeWidth = '';
            node.style.paintOrder = '';
            break;
        }
      }, { autosaveReason: 'text-effect', skipTransformUpdate: true });
    };

    const applyEffectShape = (variant) => {
      const mode = variant === 'curve' ? 'curve' : 'none';
      return applyToActiveTextNodes((node) => {
        if (mode === 'curve') {
          node.dataset.effectShape = 'curve';
        } else {
          delete node.dataset.effectShape;
        }
      }, { autosaveReason: 'text-effect', skipTransformUpdate: false });
    };

    const applyTextOpacity = (value) => {
      const numeric = clampNumber(value, 0, 100) / 100;
      return applyToActiveTextNodes((node) => {
        node.style.opacity = numeric;
        node.setAttribute('opacity', numeric);
      }, { autosaveReason: 'text-opacity', skipTransformUpdate: true });
    };

    const applyImageOpacity = (value) => {
      const numeric = clampNumber(value, 0, 100) / 100;
      return applyToActiveImageNodes((node) => {
        node.style.opacity = numeric;
        node.setAttribute('opacity', numeric);
      }, { autosaveReason: 'image-opacity', skipTransformUpdate: true });
    };

    const applyTextRotation = (value) => {
      const numeric = clampNumber(value, -180, 180);
      return applyToActiveTextNodes((node) => {
        if (Math.abs(numeric) < 0.0001) {
          delete node.dataset.rotation;
          node.removeAttribute('data-rotation');
        } else {
          node.dataset.rotation = numeric;
          node.setAttribute('data-rotation', numeric);
        }
      }, { autosaveReason: 'text-rotation', skipTransformUpdate: false });
    };

    const applyImageRotation = (value) => {
      const numeric = clampNumber(value, -180, 180);
      return applyToActiveImageNodes((node) => {
        if (Math.abs(numeric) < 0.0001) {
          delete node.dataset.rotation;
          node.removeAttribute('data-rotation');
        } else {
          node.dataset.rotation = numeric;
          node.setAttribute('data-rotation', numeric);
        }
      }, { autosaveReason: 'image-rotation', skipTransformUpdate: false });
    };

    const generateUniquePreviewKey = (baseKey = 'text') => {
      const sanitized = baseKey.replace(/[^a-z0-9_-]+/gi, '') || 'text';
      let attempt = `${sanitized}_${Date.now()}`;
      let counter = 1;
      while (document.querySelector(`[data-preview-node="${attempt}"]`)) {
        attempt = `${sanitized}_${Date.now()}_${counter}`;
        counter += 1;
      }
      return attempt;
    };

    const duplicateActiveTextNode = () => {
      const nodes = getActiveTextNodes();
      if (nodes.length === 0) {
        return false;
      }
      const source = nodes[0];
      if (!source || !source.parentNode) {
        return false;
      }
      const newKey = generateUniquePreviewKey(activeTextKey || 'text');
      const clone = source.cloneNode(true);
      clone.setAttribute('data-preview-node', newKey);
      clone.dataset.previewNode = newKey;
      clone.id = newKey;
      clone.dataset.defaultText = clone.dataset.defaultText || clone.textContent || '';
      source.parentNode.appendChild(clone);
      updateNodeTransform(clone);
      const overlayData = createOverlayForNode(clone);
      if (overlayData) {
        syncOverlayForNode(clone);
      }
      const input = createCustomField(newKey, clone.textContent || '');
      if (input) {
        input.value = clone.textContent || '';
        setTimeout(() => {
          try {
            input.focus({ preventScroll: false });
          } catch (error) {
            // ignore focus failures
          }
        }, 0);
      }
      setActiveTextKey(newKey);
      scheduleOverlaySync();
      autosave?.schedule('duplicate-text-node');
      return true;
    };

    const deleteActiveTextNode = () => {
      const nodes = getActiveTextNodes();
      if (!nodes.length) {
        return false;
      }
      const key = activeTextKey;
      nodes.forEach((node) => {
        const overlay = overlayDataByNode.get(node)?.overlay;
        if (overlay) {
          overlay.remove();
          overlayDataByElement.delete(overlay);
        }
        overlayDataByNode.delete(node);
        node.remove();
      });
      if (key) {
        const input = getPreviewInputForKey(key);
        const wrapper = input?.closest('.text-field-item');
        if (wrapper) {
          wrapper.remove();
        }
        const pill = getPreviewPillForKey(key);
        pill?.remove();
      }
      setActiveTextKey(null);
      scheduleOverlaySync();
      autosave?.schedule('remove-text-node');
      return true;
    };

    const duplicateActiveImageNode = () => {
      const nodes = getActiveImageNodes();
      if (!nodes.length) {
        return false;
      }
      const source = nodes[0];
      if (!source || !source.parentNode) {
        return false;
      }
      const newKey = generateUniquePreviewKey(activeImageKey || 'image');
      const clone = source.cloneNode(true);
      clone.setAttribute('data-preview-node', newKey);
      clone.dataset.previewNode = newKey;
      clone.id = newKey;
      const baseX = parseFloat(source.getAttribute('x')) || 0;
      const baseY = parseFloat(source.getAttribute('y')) || 0;
      clone.setAttribute('x', baseX + 10);
      clone.setAttribute('y', baseY + 10);
      if (clone.dataset.tintFilterId) {
        delete clone.dataset.tintFilterId;
        clone.removeAttribute('filter');
      }
      source.parentNode.appendChild(clone);
      updateNodeTransform(clone);
      const overlayData = createOverlayForNode(clone);
      if (overlayData) {
        syncOverlayForNode(clone);
      }
      setActiveImageKey(newKey);
      scheduleOverlaySync();
      autosave?.schedule('duplicate-image-node');
      return true;
    };

    const deleteActiveImageNode = () => {
      const nodes = getActiveImageNodes();
      if (!nodes.length) {
        return false;
      }
      nodes.forEach((node) => {
        const overlay = overlayDataByNode.get(node)?.overlay;
        if (overlay) {
          overlay.remove();
          overlayDataByElement.delete(overlay);
        }
        overlayDataByNode.delete(node);
        node.remove();
      });
      setActiveImageKey(null);
      scheduleOverlaySync();
      autosave?.schedule('remove-image-node');
      return true;
    };

    const toggleLockActiveElement = () => {
      const type = getActiveElementType();
      if (type === 'text') {
        const success = applyToActiveTextNodes((node) => {
          const currentlyLocked = node.dataset.locked === 'true';
          const next = !currentlyLocked;
          if (next) {
            node.dataset.locked = 'true';
          } else {
            delete node.dataset.locked;
          }
          const overlay = overlayDataByNode.get(node)?.overlay;
          if (overlay) {
            overlay.classList.toggle('editor-overlay--locked', next);
            overlay.style.pointerEvents = next ? 'none' : '';
          }
        }, { autosaveReason: 'text-lock', skipTransformUpdate: true, skipOverlaySync: true });
        if (success) {
          scheduleOverlaySync();
        }
        return success;
      }

      if (type === 'image') {
        const success = applyToActiveImageNodes((node) => {
          const currentlyLocked = node.dataset.locked === 'true';
          const next = !currentlyLocked;
          if (next) {
            node.dataset.locked = 'true';
          } else {
            delete node.dataset.locked;
          }
          const overlay = overlayDataByNode.get(node)?.overlay;
          if (overlay) {
            overlay.classList.toggle('editor-overlay--locked', next);
            overlay.style.pointerEvents = next ? 'none' : '';
          }
        }, { autosaveReason: 'image-lock', skipTransformUpdate: true, skipOverlaySync: true });
        if (success) {
          scheduleOverlaySync();
        }
        return success;
      }

      return false;
    };

    const copyActiveTextNode = () => {
      const nodes = getActiveTextNodes();
      if (!nodes.length) {
        return false;
      }
      const text = nodes[0].textContent || '';
      if (!text) {
        return false;
      }
      if (navigator?.clipboard?.writeText) {
        navigator.clipboard.writeText(text).catch((error) => {
          console.warn('[InkWise Studio] Clipboard write failed.', error);
        });
        return true;
      }
      try {
        const temp = document.createElement('textarea');
        temp.value = text;
        temp.style.position = 'fixed';
        temp.style.opacity = '0';
        document.body.appendChild(temp);
        temp.focus();
        temp.select();
        document.execCommand('copy');
        temp.remove();
        return true;
      } catch (error) {
        console.warn('[InkWise Studio] Clipboard fallback failed.', error);
        return false;
      }
    };

    const copyActiveImageNode = () => {
      const nodes = getActiveImageNodes();
      if (!nodes.length) {
        return false;
      }
      const node = nodes[0];
      const href = node.getAttribute('href')
        || node.getAttribute('xlink:href')
        || node.getAttributeNS('http://www.w3.org/1999/xlink', 'href')
        || node.dataset.src
        || '';
      if (!href) {
        return false;
      }
      if (navigator?.clipboard?.writeText) {
        navigator.clipboard.writeText(href).catch((error) => {
          console.warn('[InkWise Studio] Clipboard write failed for image.', error);
        });
        return true;
      }
      try {
        const temp = document.createElement('textarea');
        temp.value = href;
        temp.style.position = 'fixed';
        temp.style.opacity = '0';
        document.body.appendChild(temp);
        temp.focus();
        temp.select();
        document.execCommand('copy');
        temp.remove();
        return true;
      } catch (error) {
        console.warn('[InkWise Studio] Clipboard fallback failed for image.', error);
        return false;
      }
    };

    const copyActiveElement = () => {
      const type = getActiveElementType();
      if (type === 'text') {
        return copyActiveTextNode();
      }
      if (type === 'image') {
        return copyActiveImageNode();
      }
      return false;
    };

    const duplicateActiveElement = () => {
      const type = getActiveElementType();
      if (type === 'text') {
        return duplicateActiveTextNode();
      }
      if (type === 'image') {
        return duplicateActiveImageNode();
      }
      return false;
    };

    const deleteActiveElement = () => {
      const type = getActiveElementType();
      if (type === 'text') {
        return deleteActiveTextNode();
      }
      if (type === 'image') {
        return deleteActiveImageNode();
      }
      return false;
    };

    const applyLayerCommandForActiveElement = (command) => {
      const type = getActiveElementType();
      if (type === 'text') {
        return applyTextLayerCommand(command);
      }
      if (type === 'image') {
        return applyImageLayerCommand(command);
      }
      return false;
    };

    const applyOpacityForActiveElement = (value) => {
      const type = getActiveElementType();
      if (type === 'text') {
        return applyTextOpacity(value);
      }
      if (type === 'image') {
        return applyImageOpacity(value);
      }
      return false;
    };

    const applyRotationForActiveElement = (value) => {
      const type = getActiveElementType();
      if (type === 'text') {
        return applyTextRotation(value);
      }
      if (type === 'image') {
        return applyImageRotation(value);
      }
      return false;
    };

    const applyColorForActiveElement = (value) => {
      const type = getActiveElementType();
      if (type === 'text') {
        return applyColorToText(value);
      }
      if (type === 'image') {
        return applyImageTint(value);
      }
      if (type === 'background') {
        return applyBackgroundColor(value);
      }
      return false;
    };

    const applySizeForActiveElement = (value) => {
      const type = getActiveElementType();
      if (type === 'text') {
        return applyFontSize(value);
      }
      if (type === 'image') {
        return applyImageScaleFromSize(value);
      }
      return false;
    };

    const applyTextLayerCommand = (command) => {
      const nodes = getActiveTextNodes();
      if (!nodes.length) {
        return false;
      }
      const node = nodes[0];
      const parent = node.parentNode;
      if (!parent) {
        return false;
      }
      const elements = Array.from(parent.children);
      const index = elements.indexOf(node);
      if (index === -1) {
        return false;
      }
      if (command === 'bring-to-front') {
        parent.appendChild(node);
      } else if (command === 'send-to-back') {
        parent.insertBefore(node, elements[0]);
      } else if (command === 'bring-forward') {
        const nextIndex = Math.min(elements.length - 1, index + 1);
        if (nextIndex !== index) {
          const reference = elements[nextIndex + 1] || null;
          parent.insertBefore(node, reference);
        }
      } else if (command === 'send-backward') {
        const prevIndex = Math.max(0, index - 1);
        if (prevIndex !== index) {
          const reference = elements[prevIndex];
          parent.insertBefore(node, reference);
        }
      } else {
        return false;
      }
      scheduleOverlaySync();
      autosave?.schedule('text-layer');
      return true;
    };

    const applyImageLayerCommand = (command) => {
      const nodes = getActiveImageNodes();
      if (!nodes.length) {
        return false;
      }
      const node = nodes[0];
      const parent = node.parentNode;
      if (!parent) {
        return false;
      }
      const elements = Array.from(parent.children);
      const index = elements.indexOf(node);
      if (index === -1) {
        return false;
      }
      if (command === 'bring-to-front') {
        parent.appendChild(node);
      } else if (command === 'send-to-back') {
        parent.insertBefore(node, elements[0]);
      } else if (command === 'bring-forward') {
        const nextIndex = Math.min(elements.length - 1, index + 1);
        if (nextIndex !== index) {
          const reference = elements[nextIndex + 1] || null;
          parent.insertBefore(node, reference);
        }
      } else if (command === 'send-backward') {
        const prevIndex = Math.max(0, index - 1);
        if (prevIndex !== index) {
          const reference = elements[prevIndex];
          parent.insertBefore(node, reference);
        }
      } else {
        return false;
      }
      scheduleOverlaySync();
      autosave?.schedule('image-layer');
      return true;
    };

    const toolbarBridge = {
      setFontFamily: (family) => {
        if (getActiveElementType() !== 'text') {
          return false;
        }
        return applyFontFamily(family);
      },
      setFontSize: (value) => applySizeForActiveElement(value),
      setColor: (value) => applyColorForActiveElement(value),
      getActiveKey: () => activeTextKey,
      getSelection: () => {
        const type = getActiveElementType();
        return {
          type,
          key: type === 'text' ? activeTextKey : type === 'image' ? activeImageKey : null,
          textKey: activeTextKey,
          imageKey: activeImageKey,
        };
      },
      dispatch(action, value) {
        const activeType = getActiveElementType();
        switch (action) {
          case 'bold':
            if (activeType === 'text') {
              toggleFontWeight();
            }
            break;
          case 'italic':
            if (activeType === 'text') {
              toggleFontItalic();
            }
            break;
          case 'underline':
            if (activeType === 'text') {
              toggleTextDecoration('underline');
            }
            break;
          case 'strikethrough':
            if (activeType === 'text') {
              toggleTextDecoration('line-through');
            }
            break;
          case 'align-left':
          case 'align-center':
          case 'align-right':
          case 'align-justify':
            if (activeType === 'text') {
              applyAlignment(action);
            }
            break;
          case 'list-bullets':
            if (activeType === 'text') {
              toggleBulletList();
            }
            break;
          case 'line-spacing':
            if (activeType === 'text') {
              applyLineSpacing(value);
            }
            break;
          case 'letter-spacing':
            if (activeType === 'text') {
              applyLetterSpacing(value);
            }
            break;
          case 'uppercase':
          case 'lowercase':
            if (activeType === 'text') {
              applyTextTransform(action);
            }
            break;
          case 'effect-style':
            if (activeType === 'text') {
              applyEffectStyle(value);
            }
            break;
          case 'effect-shape':
            if (activeType === 'text') {
              applyEffectShape(value);
            }
            break;
          case 'opacity':
            applyOpacityForActiveElement(value);
            break;
          case 'rotation':
            applyRotationForActiveElement(value);
            break;
          case 'layer':
            applyLayerCommandForActiveElement(value);
            break;
          case 'more':
            if (value === 'duplicate') {
              duplicateActiveElement();
            } else if (value === 'delete') {
              deleteActiveElement();
            } else if (value === 'lock') {
              toggleLockActiveElement();
            } else if (value === 'copy') {
              copyActiveElement();
            }
            break;
          case 'replace-image':
            const replaceInput = document.createElement('input');
            replaceInput.type = 'file';
            replaceInput.accept = 'image/*';
            replaceInput.onchange = (e) => {
              const file = e.target.files[0];
              if (file) {
                const reader = new FileReader();
                reader.onload = (event) => {
                  const dataUrl = event.target.result;
                  applyToActiveImageNodes((node) => {
                    node.setAttribute('href', dataUrl);
                    node.setAttributeNS('http://www.w3.org/1999/xlink', 'href', dataUrl);
                  });
                  autosave?.schedule('image-replace');
                };
                reader.readAsDataURL(file);
              }
            };
            replaceInput.click();
            break;
          case 'crop': {
            const nodes = getActiveImageNodes();
            if (!nodes.length) {
              break;
            }
            const overlayEntry = overlayDataByNode.get(nodes[0]);
            const overlay = overlayEntry?.overlay;
            if (!overlay) {
              break;
            }
            ensureCropStyles();
            if (activeCropSession?.overlay === overlay) {
              activeCropSession.refresh();
            } else {
              teardownActiveCropSession();
              const session = createCropSession(overlay, nodes[0]);
              if (session) {
                activeCropSession = session;
              }
            }
            break;
          }
          default:
            console.debug('[InkWise Studio] Unhandled toolbar action', action, value);
            break;
        }
      },
    };

    if (typeof window !== 'undefined') {
      window.inkwiseToolbar = toolbarBridge;
    }

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

    // Show text toolbar when text fields are focused
    document.addEventListener('focusin', (e) => {
      if (e.target.matches('#textFieldList input')) {
        document.body.classList.add('text-toolbar-visible');
      }
    });

    document.addEventListener('focusout', (e) => {
      if (e.target.matches('#textFieldList input')) {
        setTimeout(() => {
          const active = document.activeElement;
          const inputFocused = active && typeof active.matches === 'function' && active.matches('#textFieldList input');
          const toolbarFocused = active && typeof active.closest === 'function' && active.closest('.studio-react-widgets');
          if (!inputFocused && !toolbarFocused) {
            if (activeTextKey || activeImageKey || backgroundSelected) {
              syncToolbarVisibility();
            } else {
              document.body.classList.remove('text-toolbar-visible');
            }
          }
        }, 100);
      }
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
}
