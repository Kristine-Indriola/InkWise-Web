import React, { useMemo, useState } from 'react';

import { useBuilderStore } from '../../state/BuilderStore';

const GROUP_DEFINITIONS = {
  frame: {
    label: 'Frame layers (Canvas)',
    description: 'Canvas container that holds all images, text, color fills, and shapes.',
    actionLabel: 'Add frame layer',
  },
  text: {
    label: 'Text layers',
    description: 'Copy that can be tailored per customer',
    actionLabel: 'Add text layer',
  },
  image: {
    label: 'Image layers',
    description: 'Photos or graphics that can be swapped',
    actionLabel: 'Add image layer',
  },
  shape: {
    label: 'Shape layers',
    description: 'Decorative elements and frames',
    actionLabel: 'Add shape layer',
  },
};
const GROUP_ORDER = ['frame', 'text', 'image', 'shape'];

const categorizeLayerType = (layer) => {
  if (layer?.metadata?.isCanvasFrame || (layer?.type || '').toLowerCase() === 'frame' || (layer?.type || '').toLowerCase() === 'background') {
    return 'frame';
  }
  if (layer?.metadata?.isImageFrame) {
    return 'image';
  }

  const normalized = (layer?.type || '').toLowerCase();
  if (normalized === 'text') {
    return 'text';
  }
  if (normalized === 'image' || normalized === 'photo' || normalized === 'graphic') {
    return 'image';
  }
  if (normalized === 'shape' || normalized === 'vector' || normalized === 'icon') {
    return 'shape';
  }
  return 'other';
};


export function LayersPanel() {
  const { state, dispatch } = useBuilderStore();
  const activePage = state.pages.find((page) => page.id === state.activePageId) ?? state.pages[0];

  if (!activePage) {
    return null;
  }
  const [collapsedGroups, setCollapsedGroups] = useState({ frame: false, text: false, image: false, shape: false });

  const nodes = activePage.nodes || [];
  const layers = useMemo(() => [...nodes].reverse(), [nodes]);

  const groupedLayers = useMemo(() => {
    const buckets = { frame: [], text: [], image: [], shape: [] };
    layers.forEach((layer) => {
      const key = categorizeLayerType(layer);
      if (key === 'frame' || key === 'text' || key === 'image' || key === 'shape') {
        buckets[key].push(layer);
      }
    });
    return buckets;
  }, [layers]);

  const handleSelect = (layerId) => {
    dispatch({ type: 'SELECT_LAYER', layerId });
  };

  const handleDuplicate = (layerId) => {
    dispatch({ type: 'DUPLICATE_LAYER', pageId: activePage.id, layerId });
  };

  const handleDelete = (layerId) => {
    const target = nodes.find((item) => item.id === layerId);
    const confirmed = window.confirm(`Delete "${target?.name ?? 'layer'}"? You can undo this action if needed.`);
    if (!confirmed) {
      return;
    }
    dispatch({ type: 'REMOVE_LAYER', pageId: activePage.id, layerId });
  };

  const getLayerOrderLabel = (layerId) => {
    const index = nodes.findIndex((node) => node.id === layerId);
    if (index === -1) {
      return null;
    }
    return nodes.length - index;
  };

  const getLayerNumber = (layerId) => {
    const index = nodes.findIndex((node) => node.id === layerId);
    return index === -1 ? null : index + 1;
  };

  const getTypeLabel = (layer) => {
    const key = categorizeLayerType(layer);
    if (key === 'frame') return 'Frame';
    if (key === 'text') return 'Text';
    if (key === 'image') return 'Image';
    if (key === 'shape') return 'Shape';
    return 'Layer';
  };

  const getDisplayName = (layer, indexInGroup) => {
    const key = categorizeLayerType(layer);
    if (key === 'text') {
      const textValue = (layer.content ?? '').trim();
      if (textValue) {
        return textValue;
      }
      return `Text Layer ${indexInGroup + 1}`;
    }
    if (key === 'image') {
      return `Image Layer ${indexInGroup + 1}`;
    }
    if (key === 'shape') {
      return `Shape Layer ${indexInGroup + 1}`;
    }
    if (key === 'frame') {
      return indexInGroup === 0 ? 'Canvas Frame' : `Frame Layer ${indexInGroup + 1}`;
    }
    return layer.name || `Layer ${indexInGroup + 1}`;
  };

  const toggleGroup = (key) => {
    setCollapsedGroups((current) => ({ ...current, [key]: !current[key] }));
  };

  const renderLayer = (layer, indexInGroup) => {
    const groupKey = categorizeLayerType(layer);
    const isSelected = layer.id === state.selectedLayerId;
    const displayName = getDisplayName(layer, indexInGroup);
    const orderNumber = getLayerOrderLabel(layer.id);
    const layerNumber = getLayerNumber(layer.id);
    const typeLabel = getTypeLabel(layer);

    return (
      <div
        key={layer.id}
        className={`layers-panel__item layers-panel__item--modern layers-panel__item--${groupKey} ${isSelected ? 'is-active' : ''}`}
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
        <div className="layers-panel__row-main">
          <div className="layers-panel__title-group">
            <span className="layers-panel__badge">{typeLabel}</span>
            <span className="layers-panel__label-text" title={displayName}>{displayName}</span>
          </div>
          <div className="layers-panel__actions-simple" aria-hidden="true">
            <button
              type="button"
              className="layers-panel__action-ghost"
              onClick={(event) => {
                event.stopPropagation();
                handleSelect(layer.id);
              }}
              title="Edit layer"
            >
              ✏️
            </button>
            <button
              type="button"
              className="layers-panel__action-ghost"
              onClick={(event) => {
                event.stopPropagation();
                handleDuplicate(layer.id);
              }}
              title="Duplicate layer"
            >
              ⧉
            </button>
            <button
              type="button"
              className="layers-panel__action-ghost is-danger"
              onClick={(event) => {
                event.stopPropagation();
                handleDelete(layer.id);
              }}
              title="Delete layer"
            >
              🗑️
            </button>
          </div>
        </div>
        <div className="layers-panel__meta-row" aria-hidden="true">
          <span className="layers-panel__pill">Order #{orderNumber ?? '—'}</span>
          <span className="layers-panel__pill">Layer #{layerNumber ?? '—'}</span>
          <span className="layers-panel__pill layers-panel__pill--soft">{typeLabel}</span>
        </div>
      </div>
    );
  };

  const renderGroup = (groupKey) => {
    const items = groupedLayers[groupKey] ?? [];
    if (items.length === 0) {
      return null;
    }
    const group = GROUP_DEFINITIONS[groupKey];
    const isCollapsed = collapsedGroups[groupKey] ?? false;

    return (
      <div className="layers-panel__group" key={groupKey}>
        <button
          type="button"
          className="layers-panel__group-toggle"
          onClick={() => toggleGroup(groupKey)}
          aria-expanded={!isCollapsed}
        >
          <div className="layers-panel__group-text">
            <p className="layers-panel__group-title">{group.label}</p>
            <p className="layers-panel__group-description">{group.description}</p>
          </div>
          <div className="layers-panel__group-meta">
            <span className="layers-panel__pill layers-panel__pill--soft">{items.length} items</span>
            <span className="layers-panel__collapse-icon" aria-hidden="true">{isCollapsed ? '▸' : '▾'}</span>
          </div>
        </button>
        {!isCollapsed && (
          <div className="layers-panel__group-list" role="list">
            {items.map((layer, index) => renderLayer(layer, index))}
          </div>
        )}
      </div>
    );
  };

  const totalLayers = layers.length;

  return (
    <section className="layers-panel" aria-label="Layers">
      <header className="layers-panel__header">
        <div>
          <h2>Layers</h2>
          <p className="layers-panel__header-subtitle">Simple, ordered list of text and image layers on the canvas.</p>
        </div>
        <span className="layers-panel__meta">{totalLayers} items</span>
      </header>
      <div className="layers-panel__groups">
        {GROUP_ORDER.map((groupKey) => renderGroup(groupKey))}
      </div>
    </section>
  );
}
