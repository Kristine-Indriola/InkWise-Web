import React from 'react';

import { useBuilderStore } from '../../state/BuilderStore';

export function LayersPanel() {
  const { state, dispatch } = useBuilderStore();
  const activePage = state.pages.find((page) => page.id === state.activePageId) ?? state.pages[0];

  if (!activePage) {
    return null;
  }

  const layers = [...(activePage.nodes || [])].reverse();

  const handleSelect = (layerId) => {
    dispatch({ type: 'SELECT_LAYER', layerId });
  };

  const handleToggle = (layerId, key) => {
    const layer = activePage.nodes.find((item) => item.id === layerId);
    if (!layer) {
      return;
    }
    dispatch({
      type: 'UPDATE_LAYER_PROPS',
      pageId: activePage.id,
      layerId,
      props: { [key]: !layer[key] },
    });
  };

  const handleReorder = (layerId, direction) => {
    dispatch({ type: 'REORDER_LAYER', pageId: activePage.id, layerId, direction });
  };

  const handleDuplicate = (layerId) => {
    dispatch({ type: 'DUPLICATE_LAYER', pageId: activePage.id, layerId });
  };

  const handleDelete = (layerId) => {
    const target = activePage.nodes.find((item) => item.id === layerId);
  const confirmed = window.confirm(`Delete "${target?.name ?? 'layer'}"? You can undo this action if needed.`);
    if (!confirmed) {
      return;
    }
    dispatch({ type: 'REMOVE_LAYER', pageId: activePage.id, layerId });
  };

  const renderLayer = (layer) => {
    const isSelected = layer.id === state.selectedLayerId;

    return (
      <div
        key={layer.id}
        className={`layers-panel__item ${isSelected ? 'is-active' : ''}`}
        role="button"
        tabIndex={0}
        onClick={() => handleSelect(layer.id)}
        onKeyDown={(event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            handleSelect(layer.id);
          }
        }}
      >
        <div className="layers-panel__name" title={layer.name}>
          <span className="layers-panel__type" aria-hidden="true">{layer.type?.[0]?.toUpperCase() ?? 'L'}</span>
          <span>{layer.name}</span>
        </div>
        <div className="layers-panel__actions" aria-hidden="true">
          <button
            type="button"
            className={`layers-panel__action ${layer.visible === false ? 'is-inactive' : ''}`}
            onClick={(event) => {
              event.stopPropagation();
              handleToggle(layer.id, 'visible');
            }}
            title={layer.visible === false ? 'Show layer' : 'Hide layer'}
          >
            {layer.visible === false ? '👁️‍🗨️' : '👁️'}
          </button>
          <button
            type="button"
            className={`layers-panel__action ${layer.locked ? 'is-active' : ''}`}
            onClick={(event) => {
              event.stopPropagation();
              handleToggle(layer.id, 'locked');
            }}
            title={layer.locked ? 'Unlock layer' : 'Lock layer'}
          >
            {layer.locked ? '🔒' : '🔓'}
          </button>
        </div>
        <div className="layers-panel__item-controls" aria-hidden="true">
          <button
            type="button"
            className="layers-panel__control"
            title="Bring layer forward"
            onClick={(event) => {
              event.stopPropagation();
              handleReorder(layer.id, 'FORWARD');
            }}
          >
            ↑
          </button>
          <button
            type="button"
            className="layers-panel__control"
            title="Send layer backward"
            onClick={(event) => {
              event.stopPropagation();
              handleReorder(layer.id, 'BACKWARD');
            }}
          >
            ↓
          </button>
          <button
            type="button"
            className="layers-panel__control"
            title="Duplicate layer"
            onClick={(event) => {
              event.stopPropagation();
              handleDuplicate(layer.id);
            }}
          >
            ⧉
          </button>
          <button
            type="button"
            className="layers-panel__control is-danger"
            title="Delete layer"
            onClick={(event) => {
              event.stopPropagation();
              handleDelete(layer.id);
            }}
          >
            ✕
          </button>
        </div>
      </div>
    );
  };

  return (
    <section className="layers-panel" aria-label="Layers">
      <header className="layers-panel__header">
        <h2>Layers</h2>
        <span className="layers-panel__meta">{layers.length} items</span>
      </header>
      <div className="layers-panel__list" role="list">
        {layers.length === 0 && (
          <div className="layers-panel__empty">No layers yet. Add text, shapes, or images to populate this list.</div>
        )}
        {layers.map(renderLayer)}
      </div>
    </section>
  );
}
