import React from 'react';

import { useBuilderStore } from '../../state/BuilderStore';

const MIN_ZOOM = 0.25;
const MAX_ZOOM = 3;
const STEP = 0.1;

export function CanvasToolbar() {
  const { state, dispatch } = useBuilderStore();
  const zoomPercent = Math.round(state.zoom * 100);
  const activePage = state.pages.find((page) => page.id === state.activePageId) ?? state.pages[0];
  const hasSelection = Boolean(state.selectedLayerId);

  const updateZoom = (value) => {
    dispatch({ type: 'UPDATE_ZOOM', value });
  };

  const handleZoomOut = () => updateZoom(state.zoom - STEP);
  const handleZoomIn = () => updateZoom(state.zoom + STEP);
  const handleReset = () => updateZoom(1);

  const handleWidthChange = (event) => {
    const width = parseInt(event.target.value, 10);
    if (!isNaN(width) && width > 0 && activePage) {
      dispatch({ type: 'UPDATE_PAGE_PROPS', pageId: activePage.id, props: { width } });
    }
  };

  const handleHeightChange = (event) => {
    const height = parseInt(event.target.value, 10);
    if (!isNaN(height) && height > 0 && activePage) {
      dispatch({ type: 'UPDATE_PAGE_PROPS', pageId: activePage.id, props: { height } });
    }
  };

  const alignSelection = (mode) => {
    if (!hasSelection) {
      return;
    }
    dispatch({ type: 'ALIGN_SELECTED_LAYER', alignment: mode });
  };

  return (
    <div className="canvas-toolbar" role="toolbar" aria-label="Canvas controls">
      <div className="canvas-toolbar__group">
        <div className="canvas-toolbar__page-size">
          <label className="canvas-toolbar__label">Page</label>
          <div className="canvas-toolbar__dimensions">
            <input
              type="number"
              className="canvas-toolbar__input"
              value={activePage?.width ?? 400}
              onChange={handleWidthChange}
              min="1"
              max="5000"
              aria-label="Page width"
            />
            <span className="canvas-toolbar__separator">×</span>
            <input
              type="number"
              className="canvas-toolbar__input"
              value={activePage?.height ?? 400}
              onChange={handleHeightChange}
              min="1"
              max="5000"
              aria-label="Page height"
            />
            <span className="canvas-toolbar__unit">px</span>
          </div>
        </div>
      </div>
      <div className="canvas-toolbar__group">
        <button type="button" onClick={handleZoomOut} className="builder-btn" aria-label="Zoom out">
          −
        </button>
        <input
          className="canvas-toolbar__slider"
          type="range"
          min={MIN_ZOOM * 100}
          max={MAX_ZOOM * 100}
          step={STEP * 100}
          value={zoomPercent}
          onChange={(event) => updateZoom(Number(event.target.value) / 100)}
          aria-label="Zoom level"
        />
        <button type="button" onClick={handleZoomIn} className="builder-btn" aria-label="Zoom in">
          +
        </button>
      </div>
      <div className="canvas-toolbar__group">
        <span className="canvas-toolbar__label">{zoomPercent}%</span>
        <button type="button" onClick={handleReset} className="builder-btn" aria-label="Reset zoom">
          Reset
        </button>
        <button type="button" className="builder-btn" disabled aria-label="Fit to page">
          Fit
        </button>
      </div>
      <div className="canvas-toolbar__group canvas-toolbar__group--align">
        <span className="canvas-toolbar__label">Align</span>
        <div className="canvas-toolbar__align-controls" role="group" aria-label="Align selection">
          <button
            type="button"
            className="builder-btn builder-btn--ghost"
            disabled={!hasSelection}
            onClick={() => alignSelection('left')}
            aria-label="Align left"
          >
            L
          </button>
          <button
            type="button"
            className="builder-btn builder-btn--ghost"
            disabled={!hasSelection}
            onClick={() => alignSelection('horizontal-center')}
            aria-label="Align center horizontally"
          >
            HC
          </button>
          <button
            type="button"
            className="builder-btn builder-btn--ghost"
            disabled={!hasSelection}
            onClick={() => alignSelection('right')}
            aria-label="Align right"
          >
            R
          </button>
          <button
            type="button"
            className="builder-btn builder-btn--ghost"
            disabled={!hasSelection}
            onClick={() => alignSelection('top')}
            aria-label="Align top"
          >
            T
          </button>
          <button
            type="button"
            className="builder-btn builder-btn--ghost"
            disabled={!hasSelection}
            onClick={() => alignSelection('vertical-center')}
            aria-label="Align middle vertically"
          >
            VC
          </button>
          <button
            type="button"
            className="builder-btn builder-btn--ghost"
            disabled={!hasSelection}
            onClick={() => alignSelection('bottom')}
            aria-label="Align bottom"
          >
            B
          </button>
        </div>
      </div>
    </div>
  );
}
