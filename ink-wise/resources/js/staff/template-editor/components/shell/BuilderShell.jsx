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
import { BuilderErrorBoundary } from './BuilderErrorBoundary';

const MAX_DEVICE_PIXEL_RATIO = 1.2;
const PREVIEW_MAX_EDGE = 960;
const PREVIEW_JPEG_QUALITY = 0.62;
const PREVIEW_MAX_BYTES = 1_500_000;

function estimateBase64Bytes(dataUrl) {
  if (typeof dataUrl !== 'string') {
    return 0;
  }
  const commaIndex = dataUrl.indexOf(',');
  const base64 = commaIndex !== -1 ? dataUrl.slice(commaIndex + 1) : dataUrl;
  return Math.ceil((base64.length * 3) / 4);
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
    let output = renderCompressedImage(image, scale, currentQuality) || dataUrl;

    while (estimateBase64Bytes(output) > maxBytes && scale > 0.3) {
      scale = Math.max(0.3, scale * 0.85);
      currentQuality = Math.max(0.4, currentQuality - 0.08);
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

  const handleSaveTemplate = useCallback(async () => {
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
    const pixelRatio = typeof window !== 'undefined'
      ? Math.min(Math.max(window.devicePixelRatio || 1, 1), MAX_DEVICE_PIXEL_RATIO)
      : MAX_DEVICE_PIXEL_RATIO;

    try {
      dispatch({ type: 'SHOW_PREVIEW_MODAL' });
      bodyEl?.classList.add('builder-exporting');

      const designSnapshot = serializeDesign(state);
      let pngDataUrl = null;
      let svgDataUrl = null;

      try {
        pngDataUrl = await toJpeg(canvasRef.current, {
          cacheBust: true,
          pixelRatio,
          quality: PREVIEW_JPEG_QUALITY,
          backgroundColor: '#ffffff',
        });
      } catch (jpegError) {
        console.warn('[InkWise Builder] JPEG preview capture failed, falling back to PNG.', jpegError);
        try {
          pngDataUrl = await toPng(canvasRef.current, {
            cacheBust: true,
            pixelRatio,
            quality: PREVIEW_JPEG_QUALITY,
          });
        } catch (pngError) {
          console.warn('[InkWise Builder] PNG preview capture failed.', pngError);
        }
      }

      try {
        svgDataUrl = await toSvg(canvasRef.current, {
          cacheBust: true,
          filter: (node) => !node.classList?.contains('canvas-layer__resize-handle'),
        });
      } catch (captureError) {
        console.warn('[InkWise Builder] SVG snapshot capture failed.', captureError);
      }

      const previewImage = await compressPreviewImage(pngDataUrl);
      const svgMarkup = extractSvgMarkup(svgDataUrl);
      const svgPayload = encodeSvgMarkup(svgMarkup);

      const shouldIncludePreview = previewImage && estimateBase64Bytes(previewImage) <= PREVIEW_MAX_BYTES;

      const payload = {
        design: designSnapshot,
        template_name: state.template?.name ?? null,
      };

      if (shouldIncludePreview) {
        payload.preview_image = previewImage;
      }

      if (svgPayload) {
        payload.svg_markup = svgPayload;
      }

      const response = await fetch(saveTemplateRoute, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
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
