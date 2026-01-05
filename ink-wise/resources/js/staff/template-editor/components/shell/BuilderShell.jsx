import React, { useCallback, useEffect, useRef, useState } from 'react';
import { toJpeg, toPng, toSvg } from 'html-to-image';

import { useBuilderStore } from '../../state/BuilderStore';
import { ToolSidebar } from '../panels/ToolSidebar';
import { InspectorPanel } from '../panels/InspectorPanel';
import { CanvasToolbar } from '../canvas/CanvasToolbar';
import { CanvasViewport } from '../canvas/CanvasViewport';
import { BuilderTopBar } from './BuilderTopBar';
import { BuilderStatusBar } from './BuilderStatusBar';
import { BuilderHotkeys } from './BuilderHotkeys';
import { PreviewModal } from '../modals/PreviewModal';
import { serializeDesign } from '../../utils/serializeDesign';
import { derivePageLabel, normalizePageTypeValue } from '../../utils/pageFactory';
import { BuilderErrorBoundary } from './BuilderErrorBoundary';

const MAX_DEVICE_PIXEL_RATIO = 2.5; // allow higher density captures for crisper exports
const PREVIEW_MAX_EDGE = 1800; // slightly smaller edge to keep payloads leaner
const PREVIEW_JPEG_QUALITY = 0.9; // balance fidelity with payload size
const PREVIEW_MIN_JPEG_QUALITY = 0.82;
const PREVIEW_MAX_BYTES = 3_200_000; // individual preview budget
const PREVIEW_TOTAL_BUDGET = 5_000_000; // combined preview payload budget
const MANUAL_SAVE_PAYLOAD_BUDGET = 7_500_000; // safety budget for full save payloads

const waitForNextFrame = () => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(() => requestAnimationFrame(() => requestAnimationFrame(resolve)))));

async function captureCanvasRaster(canvas, pixelRatio, backgroundColor = '#ffffff') {
  if (!canvas) {
    return null;
  }

  try {
    // Prefer lossless PNG first for maximum fidelity.
    return await toPng(canvas, {
      cacheBust: true,
      pixelRatio,
      quality: PREVIEW_JPEG_QUALITY,
      backgroundColor,
    });
  } catch (pngError) {
    console.warn('[InkWise Builder] PNG preview capture failed, falling back to JPEG.', pngError);
    try {
      return await toJpeg(canvas, {
        cacheBust: true,
        pixelRatio,
        quality: PREVIEW_JPEG_QUALITY,
        backgroundColor,
      });
    } catch (jpegError) {
      console.warn('[InkWise Builder] JPEG preview capture failed.', jpegError);
      return null;
    }
  }
}

function derivePreviewKey(page, index) {
  const candidates = [
    page?.pageType,
    page?.metadata?.pageType,
    page?.metadata?.side,
    page?.metadata?.sideLabel,
    page?.name,
  ];

  for (const candidate of candidates) {
    const normalized = normalizePageTypeValue(candidate);
    if (normalized) {
      return normalized;
    }
  }

  if (index === 0) {
    return 'front';
  }
  if (index === 1) {
    return 'back';
  }
  return `page-${index + 1}`;
}

function derivePreviewLabel(page, index, totalPages) {
  if (typeof page?.metadata?.sideLabel === 'string' && page.metadata.sideLabel.trim() !== '') {
    return page.metadata.sideLabel.trim();
  }

  const normalized = normalizePageTypeValue(
    page?.pageType ?? page?.metadata?.pageType ?? page?.metadata?.side ?? page?.name ?? null,
  );

  if (normalized) {
    return derivePageLabel(normalized, index, totalPages);
  }

  if (typeof page?.name === 'string' && page.name.trim() !== '') {
    return page.name.trim();
  }

  return `Page ${index + 1}`;
}

function estimateBase64Bytes(dataUrl) {
  if (typeof dataUrl !== 'string') {
    return 0;
  }
  const commaIndex = dataUrl.indexOf(',');
  const base64 = commaIndex !== -1 ? dataUrl.slice(commaIndex + 1) : dataUrl;
  return Math.ceil((base64.length * 3) / 4);
}

function estimateJsonBytes(value) {
  try {
    const encoder = new TextEncoder();
    return encoder.encode(JSON.stringify(value)).length;
  } catch (err) {
    console.warn('[InkWise Builder] Failed to estimate JSON payload size.', err);
    try {
      return JSON.stringify(value).length;
    } catch (stringifyError) {
      console.warn('[InkWise Builder] Stringify fallback also failed.', stringifyError);
      return 0;
    }
  }
}

function getPreviewPriority(entry) {
  if (!entry) {
    return 0;
  }

  const key = entry.key ?? '';
  if (key === 'front') {
    return 400;
  }
  if (key === 'back') {
    return 360;
  }

  const order = typeof entry.meta?.order === 'number' ? entry.meta.order : 0;
  // Prefer earlier pages and smaller assets to keep payload compact.
  return 300 - order * 2 - Math.round((entry.bytes || 0) / 200_000);
}

function loadImageElement(dataUrl) {
  return new Promise((resolve, reject) => {
    const image = new Image();
    image.onload = () => resolve(image);
    image.onerror = (err) => reject(err);
    image.src = dataUrl;
  });
}

function renderCompressedImage(image, scale, quality) {
  const targetWidth = Math.max(1, Math.round(image.width * scale));
  const targetHeight = Math.max(1, Math.round(image.height * scale));

  const canvas = document.createElement('canvas');
  canvas.width = targetWidth;
  canvas.height = targetHeight;

  const ctx = canvas.getContext('2d');
  if (!ctx) {
    return null;
  }

  ctx.drawImage(image, 0, 0, targetWidth, targetHeight);
  return canvas.toDataURL('image/jpeg', quality);
}

async function compressPreviewImage(dataUrl, options = {}) {
  if (!dataUrl || typeof document === 'undefined') {
    return dataUrl;
  }

  const {
    maxEdge = PREVIEW_MAX_EDGE,
    quality = PREVIEW_JPEG_QUALITY,
    maxBytes = PREVIEW_MAX_BYTES,
  } = options;

  try {
    const image = await loadImageElement(dataUrl);
    const longestEdge = Math.max(image.width, image.height);
    let scale = longestEdge > maxEdge && maxEdge > 0 ? maxEdge / longestEdge : 1;
    let currentQuality = quality;
    const minScale = 0.4;
    const isPng = dataUrl.startsWith('data:image/png');

    let output;

    if (isPng) {
      const pngCanvas = document.createElement('canvas');
      pngCanvas.width = Math.max(1, Math.round(image.width * scale));
      pngCanvas.height = Math.max(1, Math.round(image.height * scale));
      const ctx = pngCanvas.getContext('2d');
      if (ctx) {
        ctx.drawImage(image, 0, 0, pngCanvas.width, pngCanvas.height);
        output = pngCanvas.toDataURL('image/png');
      } else {
        output = dataUrl;
      }

      if (estimateBase64Bytes(output) <= maxBytes) {
        return output;
      }

      output = renderCompressedImage(image, scale, currentQuality) || output;
    } else {
      output = renderCompressedImage(image, scale, currentQuality) || dataUrl;
    }

    while (estimateBase64Bytes(output) > maxBytes && (scale > minScale || currentQuality > PREVIEW_MIN_JPEG_QUALITY)) {
      if (scale > minScale) {
        scale = Math.max(minScale, scale * 0.9);
      }
      if (currentQuality > PREVIEW_MIN_JPEG_QUALITY) {
        currentQuality = Math.max(PREVIEW_MIN_JPEG_QUALITY, currentQuality - 0.04);
      }

      const attempt = renderCompressedImage(image, scale, currentQuality);
      if (!attempt) {
        break;
      }
      output = attempt;
    }

    return output;
  } catch (err) {
    console.warn('[InkWise Builder] Failed to compress preview image.', err);
    return dataUrl;
  }
}

function extractSvgMarkup(dataUrl) {
  if (typeof dataUrl !== 'string') {
    return null;
  }

  if (!dataUrl.startsWith('data:image/svg+xml')) {
    return dataUrl;
  }

  const commaIndex = dataUrl.indexOf(',');
  if (commaIndex === -1) {
    return dataUrl;
  }

  const encodedPayload = dataUrl.slice(commaIndex + 1);

  try {
    return decodeURIComponent(encodedPayload);
  } catch (err) {
    console.warn('[InkWise Builder] Failed to decode SVG markup payload.', err);
    return encodedPayload;
  }
}

function sanitizeSvgMarkup(markup) {
  if (typeof markup !== 'string') {
    return markup;
  }

  return markup
    .replace(/<!--.*?-->/gs, '')
    .replace(/\s*\n+\s*/g, ' ')
    .replace(/\s{2,}/g, ' ')
    .trim();
}

function encodeSvgMarkup(markup) {
  if (!markup || typeof markup !== 'string') {
    return markup;
  }

  const sanitized = sanitizeSvgMarkup(markup);

  try {
    if (typeof window !== 'undefined' && typeof window.btoa === 'function') {
      const encoded = window.btoa(unescape(encodeURIComponent(sanitized)));
      return `data:image/svg+xml;base64,${encoded}`;
    }
  } catch (err) {
    console.warn('[InkWise Builder] Failed to encode SVG markup payload.', err);
  }

  return sanitized;
}

export function BuilderShell() {
  const { state, routes, csrfToken, dispatch } = useBuilderStore();
  const activePage = state.pages.find((page) => page.id === state.activePageId) ?? state.pages[0];
  const [isSidebarHidden, setIsSidebarHidden] = useState(false);
  const [autosaveStatus, setAutosaveStatus] = useState('idle');
  const [lastSavedAt, setLastSavedAt] = useState(state.template?.updated_at ?? null);
  const [isSavingTemplate, setIsSavingTemplate] = useState(false);
  const [saveTemplateError, setSaveTemplateError] = useState(null);
  const [lastTemplateSavedAt, setLastTemplateSavedAt] = useState(null);
  const pendingSaveRef = useRef(null);
  const lastSnapshotRef = useRef(null);
  const controllerRef = useRef(null);
  const initialRenderRef = useRef(true);
  const canvasRef = useRef(null);

  const handleBoundaryReset = useCallback(() => {
    if (typeof window !== 'undefined') {
      window.location.reload();
    }
  }, []);

  const toggleSidebar = () => {
    setIsSidebarHidden(!isSidebarHidden);
  };

  useEffect(() => {
    return () => {
      if (pendingSaveRef.current) {
        clearTimeout(pendingSaveRef.current);
        pendingSaveRef.current = null;
      }
      if (controllerRef.current) {
        controllerRef.current.abort();
      }
    };
  }, []);

  useEffect(() => {
    if (!routes?.autosave || !csrfToken) {
      return undefined;
    }

    const designSnapshot = serializeDesign(state);
    const serialized = JSON.stringify(designSnapshot);

    if (initialRenderRef.current) {
      initialRenderRef.current = false;
      lastSnapshotRef.current = serialized;
      return undefined;
    }

    if (serialized === lastSnapshotRef.current) {
      return undefined;
    }

    lastSnapshotRef.current = serialized;

    if (pendingSaveRef.current) {
      clearTimeout(pendingSaveRef.current);
    }

    setAutosaveStatus('dirty');

    pendingSaveRef.current = setTimeout(() => {
      if (!routes?.autosave || !csrfToken) {
        return;
      }

      if (controllerRef.current) {
        controllerRef.current.abort();
      }

      const controller = new AbortController();
      controllerRef.current = controller;

      setAutosaveStatus('saving');

      fetch(routes.autosave, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          design: designSnapshot,
          canvas: designSnapshot.canvas,
          template_name: state.template?.name ?? null,
        }),
        signal: controller.signal,
      })
        .then(async (response) => {
          if (!response.ok) {
            const message = await response.text();
            throw new Error(message || 'Autosave request failed');
          }
          return response.json();
        })
        .then((data) => {
          setAutosaveStatus('saved');
          if (data?.saved_at) {
            setLastSavedAt(data.saved_at);
          }
          if (controllerRef.current === controller) {
            controllerRef.current = null;
          }
        })
        .catch((error) => {
          if (controller.signal.aborted) {
            return;
          }
          if (controllerRef.current === controller) {
            controllerRef.current = null;
          }
          console.error('[InkWise Builder] Autosave failed:', error);
          setAutosaveStatus('error');
          // Allow retry on the next state change
          lastSnapshotRef.current = null;
        });
    }, 1500);

    return () => {
      if (pendingSaveRef.current) {
        clearTimeout(pendingSaveRef.current);
        pendingSaveRef.current = null;
      }
    };
  }, [state.pages, state.activePageId, state.zoom, state.panX, state.panY, state.template?.name, routes?.autosave, csrfToken]);

  const saveTemplateRoute = routes?.saveTemplate ?? routes?.saveCanvas;

  const handleSaveTemplate = useCallback(async (options = {}) => {
    if (!saveTemplateRoute) {
      setSaveTemplateError('Save route is unavailable.');
      return;
    }
    if (!csrfToken) {
      setSaveTemplateError('Missing CSRF token.');
      return;
    }
    if (!canvasRef.current) {
      setSaveTemplateError('Canvas not ready yet.');
      return;
    }
    if (isSavingTemplate) {
      return;
    }

    setIsSavingTemplate(true);
    setSaveTemplateError(null);

    const bodyEl = typeof document !== 'undefined' ? document.body : null;
    const requestedPageId = options?.pageId ?? null;
    const pixelRatio = typeof window !== 'undefined'
      ? Math.min(Math.max(window.devicePixelRatio || 1, 1), MAX_DEVICE_PIXEL_RATIO)
      : MAX_DEVICE_PIXEL_RATIO;

    try {
      dispatch({ type: 'SHOW_PREVIEW_MODAL' });
      bodyEl?.classList.add('builder-exporting');

      // Allow layout/styles to flush before snapshotting so export-only CSS applies.
      await waitForNextFrame();

      const designSnapshot = serializeDesign(state);
      const allPages = Array.isArray(state.pages) ? state.pages : [];
      const selectedPages = requestedPageId
        ? allPages.filter((page) => page.id === requestedPageId)
        : allPages;
      const fallbackActivePage = allPages.find((page) => page.id === state.activePageId) ?? null;

      let pagesToCapture = (selectedPages.length > 0 ? selectedPages : allPages).filter(Boolean);
      if (pagesToCapture.length === 0 && fallbackActivePage) {
        pagesToCapture = [fallbackActivePage];
      }

      const totalPages = allPages.length > 0 ? allPages.length : pagesToCapture.length;

      const pendingPreviewEntries = [];
      const seenPreviewKeys = new Set();
      let previewImages = {};
      let previewImagesMeta = {};
      let previewPayloadTrimmed = false;
      let primaryPreviewCandidate = null;
      let svgDataUrl = null;

      const originalActivePageId = state.activePageId;
      const originalSelectedLayerId = state.selectedLayerId;
      let currentActivePageId = originalActivePageId;

      // Temporarily deselect any layer to hide bounding boxes during capture
      dispatch({ type: 'SELECT_LAYER', layerId: null });
      await waitForNextFrame();

      const ensurePageActive = async (pageId) => {
        if (!pageId || pageId === currentActivePageId) {
          return;
        }
        dispatch({ type: 'SELECT_PAGE', pageId });
        currentActivePageId = pageId;
        // Ensure no layer is selected when switching pages during export
        dispatch({ type: 'SELECT_LAYER', layerId: null });
        await waitForNextFrame();
      };

      for (let index = 0; index < pagesToCapture.length; index += 1) {
        const page = pagesToCapture[index];
        if (!page) {
          continue;
        }

        await ensurePageActive(page.id);
        await waitForNextFrame();

        const backgroundColor = page?.background || '#ffffff';
        const rasterDataUrl = await captureCanvasRaster(canvasRef.current, pixelRatio, backgroundColor);
        if (!rasterDataUrl) {
          continue;
        }

        const compressedImage = await compressPreviewImage(rasterDataUrl);
        if (!compressedImage) {
          continue;
        }

        const keyBase = derivePreviewKey(page, index);
        const safeKeyBase = typeof keyBase === 'string' && keyBase.trim() !== '' ? keyBase : null;
        const fallbackKey = `page-${index + 1}`;

        let uniqueKey = safeKeyBase ?? fallbackKey;
        let suffix = 2;
        while (seenPreviewKeys.has(uniqueKey)) {
          uniqueKey = safeKeyBase ? `${safeKeyBase}-${suffix}` : `${fallbackKey}-${suffix}`;
          suffix += 1;
        }
        seenPreviewKeys.add(uniqueKey);

        const entryMeta = {
          label: derivePreviewLabel(page, index, totalPages || pagesToCapture.length || 1),
          pageId: page.id,
          pageType: safeKeyBase ?? uniqueKey,
          order: index,
        };

        pendingPreviewEntries.push({
          key: uniqueKey,
          data: compressedImage,
          meta: entryMeta,
          bytes: estimateBase64Bytes(compressedImage),
        });

        if (!primaryPreviewCandidate) {
          primaryPreviewCandidate = compressedImage;
        }
        if (uniqueKey === 'front') {
          primaryPreviewCandidate = compressedImage;
        }

        if (!svgDataUrl) {
          try {
            svgDataUrl = await toSvg(canvasRef.current, {
              cacheBust: true,
              filter: (node) => !node.classList?.contains('canvas-layer__resize-handle'),
            });
          } catch (captureError) {
            console.warn('[InkWise Builder] SVG snapshot capture failed.', captureError);
          }
        }
      }

      await ensurePageActive(originalActivePageId);

      const originalLayerStillExists = allPages.some((page) => (
        page.id === originalActivePageId
        && Array.isArray(page.nodes)
        && page.nodes.some((node) => node.id === originalSelectedLayerId)
      ));

      if (originalLayerStillExists && originalSelectedLayerId) {
        dispatch({ type: 'SELECT_LAYER', layerId: originalSelectedLayerId });
        await waitForNextFrame();
      }

      if (pendingPreviewEntries.length > 0) {
        const sortedEntries = [...pendingPreviewEntries].sort((a, b) => {
          const priorityDiff = getPreviewPriority(b) - getPreviewPriority(a);
          if (priorityDiff !== 0) {
            return priorityDiff;
          }
          return (a.bytes || 0) - (b.bytes || 0);
        });

        let remainingBudget = PREVIEW_TOTAL_BUDGET;
        const selectedEntries = [];

        for (const entry of sortedEntries) {
          const entryBytes = entry.bytes || 0;
          if (selectedEntries.length === 0 || entryBytes <= remainingBudget) {
            selectedEntries.push(entry);
            remainingBudget = Math.max(remainingBudget - entryBytes, 0);
          }
        }

        if (selectedEntries.length < pendingPreviewEntries.length) {
          previewPayloadTrimmed = true;
          console.warn('[InkWise Builder] Trimmed preview payload to avoid oversize POST.', {
            totalEntries: pendingPreviewEntries.length,
            selectedEntries: selectedEntries.length,
            remainingBudget,
          });
        }

        previewImages = {};
        previewImagesMeta = {};
        for (const entry of selectedEntries) {
          previewImages[entry.key] = entry.data;
          previewImagesMeta[entry.key] = entry.meta;
        }
      } else {
        previewImages = {};
        previewImagesMeta = {};
      }

      const previewImage = previewImages.front ?? primaryPreviewCandidate ?? null;
      const svgMarkup = extractSvgMarkup(svgDataUrl);
      const svgPayload = encodeSvgMarkup(svgMarkup);

      const shouldIncludePreview = previewImage && estimateBase64Bytes(previewImage) <= PREVIEW_MAX_BYTES;

      const payload = {
        design: designSnapshot,
        template_name: state.template?.name ?? null,
      };

      if (requestedPageId) {
        payload.save_context = {
          scope: 'page',
          page_id: requestedPageId,
        };
      }

      if (shouldIncludePreview) {
        payload.preview_image = previewImage;
      }

      if (Object.keys(previewImages).length > 0) {
        payload.preview_images = previewImages;
        payload.preview_images_meta = previewImagesMeta;
        if (previewPayloadTrimmed) {
          payload.preview_images_truncated = true;
        }
      } else if (previewPayloadTrimmed) {
        payload.preview_images_truncated = true;
      }

      if (svgPayload) {
        payload.svg_markup = svgPayload;
      }

      let estimatedPayloadBytes = estimateJsonBytes(payload);

      if (estimatedPayloadBytes > MANUAL_SAVE_PAYLOAD_BUDGET && payload.preview_images) {
        console.warn('[InkWise Builder] Dropping secondary previews to respect payload budget.', {
          estimatedPayloadBytes,
          budget: MANUAL_SAVE_PAYLOAD_BUDGET,
        });
        payload.preview_images_truncated = true;
        delete payload.preview_images;
        delete payload.preview_images_meta;
        estimatedPayloadBytes = estimateJsonBytes(payload);
      }

      if (estimatedPayloadBytes > MANUAL_SAVE_PAYLOAD_BUDGET && payload.preview_image) {
        console.warn('[InkWise Builder] Dropping primary preview image to reduce payload.', {
          estimatedPayloadBytes,
          budget: MANUAL_SAVE_PAYLOAD_BUDGET,
        });
        delete payload.preview_image;
        estimatedPayloadBytes = estimateJsonBytes(payload);
      }

      if (estimatedPayloadBytes > MANUAL_SAVE_PAYLOAD_BUDGET && payload.svg_markup) {
        console.warn('[InkWise Builder] Dropping SVG markup to keep payload within limits.', {
          estimatedPayloadBytes,
          budget: MANUAL_SAVE_PAYLOAD_BUDGET,
        });
        delete payload.svg_markup;
        estimatedPayloadBytes = estimateJsonBytes(payload);
      }

      const requestBody = JSON.stringify(payload);

      const response = await fetch(saveTemplateRoute, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin',
        body: requestBody,
      });

      if (!response.ok) {
        let errorMessage = 'Failed to save template';
        const contentType = response.headers.get('Content-Type') || '';

        if (contentType.includes('application/json')) {
          try {
            const errorPayload = await response.clone().json();
            if (typeof errorPayload?.message === 'string' && errorPayload.message.trim() !== '') {
              errorMessage = errorPayload.message.trim();
            } else if (errorPayload?.errors && typeof errorPayload.errors === 'object') {
              const firstField = Object.values(errorPayload.errors)[0];
              if (Array.isArray(firstField) && typeof firstField[0] === 'string') {
                errorMessage = firstField[0];
              }
            }
          } catch (jsonError) {
            console.warn('[InkWise Builder] Failed to parse error response JSON.', jsonError);
          }
        } else {
          const textMessage = await response.text();
          if (textMessage && textMessage.trim().length > 0) {
            errorMessage = textMessage.trim();
          }
        }

        throw new Error(errorMessage);
      }

      const data = await response.json().catch(() => ({}));
      const savedAt = new Date().toISOString();
      setLastTemplateSavedAt(savedAt);

      const redirectTarget = data?.redirect || routes?.index || '/staff/templates';
      window.location.href = redirectTarget;
    } catch (error) {
      console.error('[InkWise Builder] Template save failed:', error);
      setSaveTemplateError(error.message || 'Failed to save template');
      dispatch({ type: 'HIDE_PREVIEW_MODAL' });
    } finally {
      bodyEl?.classList.remove('builder-exporting');
      setIsSavingTemplate(false);
    }
  }, [saveTemplateRoute, csrfToken, isSavingTemplate, state, routes, dispatch]);

  return (
    <BuilderErrorBoundary onReset={handleBoundaryReset} templateId={state.template?.id}>
      <div className="builder-shell" role="application" aria-label="InkWise template builder">
        <BuilderHotkeys />
        <BuilderTopBar
          autosaveStatus={autosaveStatus}
          lastSavedAt={lastSavedAt}
          onSaveTemplate={handleSaveTemplate}
          isSavingTemplate={isSavingTemplate}
          lastManualSaveAt={lastTemplateSavedAt}
          saveError={saveTemplateError}
        />
        <div className="builder-workspace" style={{ gridTemplateColumns: isSidebarHidden ? '60px minmax(0, 1fr) 340px' : '600px minmax(0, 1fr) 340px' }}>
          <ToolSidebar isSidebarHidden={isSidebarHidden} onToggleSidebar={toggleSidebar} />
          <main className="builder-canvas-column" aria-live="polite">
            <div className="builder-canvas-header">
              <CanvasToolbar />
            </div>
            <CanvasViewport page={activePage} canvasRef={canvasRef} />
            <BuilderStatusBar />
          </main>
          <aside className="builder-right-column" aria-label="Inspector panels">
            <InspectorPanel />
          </aside>
        </div>
        <PreviewModal
          isOpen={state.showPreviewModal}
          onClose={() => dispatch({ type: 'HIDE_PREVIEW_MODAL' })}
        />
      </div>
    </BuilderErrorBoundary>
  );
}
