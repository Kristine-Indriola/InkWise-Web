import React from 'react';

import { useBuilderStore } from '../../state/BuilderStore';

const MIN_ZOOM = 0.25;
const MAX_ZOOM = 3;
const STEP = 0.1;
const DPI = 96;

const TEMPLATE_SIZES = [
  { label: 'A10 invitation (5.88" × 9.25")', width: 5.88, height: 9.25 },
  { label: 'A9 invitation (5.63" × 8.63")', width: 5.63, height: 8.63 },
  { label: 'A8 invitation (5.38" × 7.88")', width: 5.38, height: 7.88 },
  { label: 'A7 invitation (5.13" × 7")', width: 5.13, height: 7 },
  { label: 'A6 invitation (4.5" × 6.25")', width: 4.5, height: 6.25 },
  { label: 'A2 invitation (4.25" × 5.5")', width: 4.25, height: 5.5 },
  { label: 'A1 invitation (3.5" × 4.88")', width: 3.5, height: 4.88 },
  { label: 'Square Large invitation (6.75" × 6.75")', width: 6.75, height: 6.75 },
  { label: 'A10 Envelope (9.5" × 6.5")', width: 9.5, height: 6.5 },
  { label: 'A9 Envelope (9" × 6")', width: 9, height: 6 },
  { label: 'A8 Envelope (8" × 5.5")', width: 8, height: 5.5 },
  { label: 'A7 Envelope (7.25" × 5.25")', width: 7.25, height: 5.25 },
  { label: 'A6 Envelope (6.5" × 4.75")', width: 6.5, height: 4.75 },
  { label: 'A2 Envelope (5.75" × 4.375")', width: 5.75, height: 4.375 },
  { label: 'A1 Envelope (5" × 3.625")', width: 5, height: 3.625 },
  { label: 'Square Large Envelope (7" × 7")', width: 7, height: 7 },
];

const FOLD_TYPES = [
  { value: 'bifold', label: 'Bi-Fold' },
  { value: 'gatefold', label: 'Gate-Fold' },
  { value: 'zfold', label: 'Z-Fold' },
];

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

  const handleSizeChange = (event) => {
    const index = parseInt(event.target.value, 10);
    if (!isNaN(index) && TEMPLATE_SIZES[index] && activePage) {
      const size = TEMPLATE_SIZES[index];
      const widthPx = Math.round(size.width * DPI);
      const heightPx = Math.round(size.height * DPI);
      dispatch({ type: 'UPDATE_PAGE_PROPS', pageId: activePage.id, props: { width: widthPx, height: heightPx } });
      // Update template dimensions and sizes
      dispatch({ type: 'UPDATE_TEMPLATE_PROPS', props: { 
        width_inch: size.width, 
        height_inch: size.height,
        sizes: [size] // Store the selected size in the sizes array
      } });
    }
  };

  const handleFoldTypeChange = (event) => {
    const foldType = event.target.value;
    dispatch({ type: 'UPDATE_TEMPLATE_PROPS', props: { fold_type: foldType || null } });
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
          <label className="canvas-toolbar__label">Fold Type</label>
          <select className="canvas-toolbar__select" onChange={handleFoldTypeChange} value={state.template?.fold_type || ''} aria-label="Fold type">
            <option value="">No Fold</option>
            {FOLD_TYPES.map((fold) => (
              <option key={fold.value} value={fold.value}>
                {fold.label}
              </option>
            ))}
          </select>
        </div>
        <div className="canvas-toolbar__page-size">
          <label className="canvas-toolbar__label">Size</label>
          <select className="canvas-toolbar__select" onChange={handleSizeChange} aria-label="Template size">
            <option value="">Select size</option>
            {TEMPLATE_SIZES.map((size, index) => (
              <option key={index} value={index}>
                {size.label}
              </option>
            ))}
          </select>
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
