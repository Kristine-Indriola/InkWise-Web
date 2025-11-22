import React, { useCallback, useEffect, useRef, useState } from 'react';
import { toPng, toSvg } from 'html-to-image';

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
  }, [state.pages, state.template?.name, state.zoom, state.panX, state.panY, state.activePageId, routes?.autosave, csrfToken]);

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

    dispatch({ type: 'SHOW_PREVIEW_MODAL' });

    setIsSavingTemplate(true);
    setSaveTemplateError(null);

    const bodyEl = typeof document !== 'undefined' ? document.body : null;
    const pixelRatio = typeof window !== 'undefined' ? Math.max(window.devicePixelRatio || 1, 2) : 2;

    try {
      bodyEl?.classList.add('builder-exporting');

      const designSnapshot = serializeDesign(state);

      const pngDataUrl = await toPng(canvasRef.current, {
        cacheBust: true,
        pixelRatio,
        quality: 1,
      });

      const svgDataUrl = await toSvg(canvasRef.current, {
        cacheBust: true,
      });

      const response = await fetch(saveTemplateRoute, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin',
        body: JSON.stringify({
          design: designSnapshot,
          preview_image: pngDataUrl,
          svg_markup: svgDataUrl,
          template_name: state.template?.name ?? null,
        }),
      });

      if (!response.ok) {
        const message = await response.text();
        throw new Error(message || 'Failed to save template');
      }

      const data = await response.json().catch(() => ({}));
      const savedAt = new Date().toISOString();
      setLastTemplateSavedAt(savedAt);

      const redirectTarget = data?.redirect || routes?.index || '/staff/templates';
      window.location.href = redirectTarget;
    } catch (error) {
      console.error('[InkWise Builder] Template save failed:', error);
      setSaveTemplateError(error.message || 'Failed to save template');
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
