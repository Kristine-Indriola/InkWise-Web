import React, { useCallback, useEffect, useRef, useState } from 'react';
import { toPng } from 'html-to-image';

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

export function BuilderShell() {
  const { state, routes, csrfToken, dispatch } = useBuilderStore();
  const activePage = state.pages.find((page) => page.id === state.activePageId) ?? state.pages[0];
  const [isSidebarHidden, setIsSidebarHidden] = useState(false);
  const [autosaveStatus, setAutosaveStatus] = useState('idle');
  const [lastSavedAt, setLastSavedAt] = useState(state.template?.updated_at ?? null);
  const [isSavingPreview, setIsSavingPreview] = useState(false);
  const [previewSaveError, setPreviewSaveError] = useState(null);
  const [lastPreviewSavedAt, setLastPreviewSavedAt] = useState(null);
  const pendingSaveRef = useRef(null);
  const lastSnapshotRef = useRef(null);
  const controllerRef = useRef(null);
  const initialRenderRef = useRef(true);
  const canvasRef = useRef(null);

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

  const saveCanvasRoute = routes?.saveCanvas;

  const handleSaveTemplate = useCallback(async () => {
    if (!saveCanvasRoute) {
      setPreviewSaveError('Save route is unavailable.');
      return;
    }
    if (!csrfToken) {
      setPreviewSaveError('Missing CSRF token.');
      return;
    }
    if (!canvasRef.current) {
      setPreviewSaveError('Canvas not ready yet.');
      return;
    }
    if (isSavingPreview) {
      return;
    }

    setIsSavingPreview(true);
    setPreviewSaveError(null);

    const bodyEl = typeof document !== 'undefined' ? document.body : null;
    const pixelRatio = typeof window !== 'undefined' ? Math.max(window.devicePixelRatio || 1, 2) : 2;

    try {
      bodyEl?.classList.add('builder-exporting');

      const dataUrl = await toPng(canvasRef.current, {
        cacheBust: true,
        pixelRatio,
        quality: 1,
      });

      const response = await fetch(saveCanvasRoute, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin',
        body: JSON.stringify({ canvas_image: dataUrl }),
      });

      if (!response.ok) {
        const message = await response.text();
        throw new Error(message || 'Failed to save preview');
      }

      await response.json().catch(() => null);
      setLastPreviewSavedAt(new Date().toISOString());
    } catch (error) {
      console.error('[InkWise Builder] Preview save failed:', error);
      setPreviewSaveError(error.message || 'Failed to save preview');
    } finally {
      bodyEl?.classList.remove('builder-exporting');
      setIsSavingPreview(false);
    }
  }, [saveCanvasRoute, csrfToken, isSavingPreview]);

  return (
    <div className="builder-shell" role="application" aria-label="InkWise template builder">
      <BuilderHotkeys />
      <BuilderTopBar
        autosaveStatus={autosaveStatus}
        lastSavedAt={lastSavedAt}
        onSaveTemplate={handleSaveTemplate}
        isSavingPreview={isSavingPreview}
        lastPreviewSavedAt={lastPreviewSavedAt}
        previewSaveError={previewSaveError}
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
  );
}
