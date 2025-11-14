import React from 'react';

import { useBuilderStore } from '../../state/BuilderStore';

export function BuilderStatusBar() {
  const { state } = useBuilderStore();
  const activePage = state.pages.find((page) => page.id === state.activePageId) ?? state.pages[0];
  const layers = Array.isArray(activePage?.nodes) ? activePage.nodes : [];
  const selectedLayer = layers.find((layer) => layer.id === state.selectedLayerId) ?? null;

  const zoomPercent = Math.round((state.zoom ?? 1) * 100);
  const undoCount = state.history.undoStack.length;
  const redoCount = state.history.redoStack.length;
  const layerCount = layers.length;

  return (
    <footer className="builder-statusbar" role="contentinfo" aria-live="polite">
      <div className="builder-statusbar__segment">
        Page: {activePage.name} ({activePage.width} × {activePage.height}px)
      </div>
      <div className="builder-statusbar__segment">Layers: {layerCount}</div>
      <div className="builder-statusbar__segment">Zoom: {zoomPercent}%</div>
      <div className="builder-statusbar__segment">
        History: {undoCount} undo • {redoCount} redo
      </div>
    </footer>
  );
}
