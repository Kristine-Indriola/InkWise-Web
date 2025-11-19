import React from 'react';
import PropTypes from 'prop-types';

import { useBuilderStore } from '../../state/BuilderStore';

function resolveInsets(zone) {
  if (!zone) {
    return { top: 0, right: 0, bottom: 0, left: 0 };
  }

  const toNumber = (value) => {
    if (typeof value === 'number') return value;
    if (typeof value === 'string') {
      const parsed = parseFloat(value);
      return Number.isNaN(parsed) ? 0 : parsed;
    }
    return 0;
  };

  const fallback = toNumber(zone.margin ?? zone.all ?? 0);

  return {
    top: toNumber(zone.top ?? fallback),
    right: toNumber(zone.right ?? fallback),
    bottom: toNumber(zone.bottom ?? fallback),
    left: toNumber(zone.left ?? fallback),
  };
}

export function PreviewModal({ isOpen, onClose }) {
  const { state } = useBuilderStore();

  if (!isOpen || !state.pages || state.pages.length === 0) {
    return null;
  }

  const activePage = state.pages.find(page => page.id === state.activePageId) || state.pages[0];

  // Helper function to get shape styling based on variant
  const getShapeStyling = (shape) => {
    if (!shape) return {};

    const { variant, id, borderRadius } = shape;

    switch (variant) {
      case 'circle':
        return {
          borderRadius: '50%',
          overflow: 'hidden',
        };

      case 'polygon':
        const polygonClips = {
          'triangle': 'polygon(50% 0%, 0% 100%, 100% 100%)',
          'diamond': 'polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%)',
          'hexagon': 'polygon(30% 0%, 70% 0%, 100% 50%, 70% 100%, 30% 100%, 0% 50%)',
          'octagon': 'polygon(30% 0%, 70% 0%, 100% 30%, 100% 70%, 70% 100%, 30% 100%, 0% 70%, 0% 30%)',
          'star': 'polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 91%, 50% 70%, 21% 91%, 32% 57%, 2% 35%, 39% 35%)',
          'shield': 'polygon(50% 0%, 100% 20%, 100% 80%, 50% 100%, 0% 80%, 0% 20%)',
        };
        return {
          clipPath: polygonClips[id] || 'none',
          overflow: 'hidden',
        };

      case 'organic':
        const organicClips = {
          'heart': 'path("M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z")',
          'cloud-shape': 'ellipse(60% 40% at 50% 50%)',
          'flower': 'polygon(50% 0%, 65% 35%, 100% 50%, 65% 65%, 50% 100%, 35% 65%, 0% 50%, 35% 35%)',
          'butterfly': 'polygon(50% 0%, 70% 20%, 90% 40%, 70% 60%, 50% 80%, 30% 60%, 10% 40%, 30% 20%)',
          'leaf': 'polygon(50% 0%, 100% 30%, 90% 70%, 50% 100%, 10% 70%, 0% 30%)',
          'balloon': 'ellipse(50% 60% at 50% 50%)',
          'crown': 'polygon(50% 0%, 60% 25%, 85% 25%, 75% 50%, 95% 50%, 80% 75%, 100% 75%, 85% 100%, 15% 100%, 0% 75%, 20% 75%, 5% 50%, 25% 50%, 15% 25%, 40% 25%)',
          'puzzle-piece': 'polygon(0% 0%, 70% 0%, 70% 30%, 100% 30%, 100% 70%, 70% 70%, 70% 100%, 30% 100%, 30% 70%, 0% 70%)',
          'ribbon-banner': 'polygon(0% 0%, 10% 50%, 0% 100%, 100% 100%, 90% 50%, 100% 0%)',
        };
        return {
          clipPath: organicClips[id] || 'none',
          overflow: 'hidden',
        };

      case 'frame':
        // Frames are handled with borders, no special clipping needed
        return {
          borderRadius: typeof borderRadius === 'number' ? `${borderRadius}px` : borderRadius || 0,
          overflow: 'hidden',
        };

      case 'tag':
        const tagClips = {
          'tag-shape': 'polygon(0% 0%, 85% 0%, 100% 50%, 85% 100%, 0% 100%)',
          'ticket-shape': 'polygon(0% 0%, 85% 0%, 100% 50%, 85% 100%, 0% 100%)',
        };
        return {
          clipPath: tagClips[id] || 'none',
          overflow: 'hidden',
        };

      case 'layout':
        // Layout shapes are typically represented as borders/frames
        return {
          borderRadius: typeof borderRadius === 'number' ? `${borderRadius}px` : borderRadius || 0,
          overflow: 'hidden',
        };

      case 'rectangle':
      default:
        return {
          borderRadius: typeof borderRadius === 'number' ? `${borderRadius}px` : borderRadius || 0,
          overflow: 'hidden',
        };
    }
  };

  const visibleLayers = Array.isArray(activePage.nodes) ? activePage.nodes : [];

  // Sort layers by side property for proper z-index stacking
  const sortedLayers = [...visibleLayers].sort((a, b) => {
    const sideA = a.side || 'middle';
    const sideB = b.side || 'middle';

    // Define rendering order: back -> middle -> front
    const order = { 'back': 0, 'middle': 1, 'front': 2 };

    return order[sideA] - order[sideB];
  });

  const pageShape = activePage.shape ?? null;

  return (
    <div className="preview-modal-overlay" onClick={onClose}>
      <div className="preview-modal" onClick={(e) => e.stopPropagation()}>
        <div className="preview-modal__header">
          <h2>Template Preview</h2>
          <button
            type="button"
            className="preview-modal__close"
            onClick={onClose}
            aria-label="Close preview"
          >
            ×
          </button>
        </div>
        <div className="preview-modal__content">
          <div
            className="preview-page"
            style={{
              width: activePage.width,
              height: activePage.height,
              background: activePage.background || '#ffffff',
              ...getShapeStyling(pageShape),
              boxShadow: '0 4px 20px rgba(0, 0, 0, 0.15)',
              borderRadius: '8px',
              margin: '0 auto',
            }}
          >
            {sortedLayers.map((layer) => {
              if (layer.visible === false) return null;

              const frame = layer.frame;
              if (!frame) return null;

              const isText = layer.type === 'text';
              const isImage = layer.type === 'image';
              const metadata = layer?.metadata ?? {};
              const objectFitMode = typeof metadata.objectFit === 'string' ? metadata.objectFit : 'cover';
              const rawScale = Number(metadata.imageScale);
              const imageScale = Number.isFinite(rawScale) ? Math.max(0.25, Math.min(4, rawScale)) : 1;
              const rawOffsetX = Number(metadata.imageOffsetX);
              const rawOffsetY = Number(metadata.imageOffsetY);
              const imageOffsetX = Number.isFinite(rawOffsetX) ? Math.max(-500, Math.min(500, rawOffsetX)) : 0;
              const imageOffsetY = Number.isFinite(rawOffsetY) ? Math.max(-500, Math.min(500, rawOffsetY)) : 0;
              const flipHorizontal = Boolean(metadata.flipHorizontal);
              const flipVertical = Boolean(metadata.flipVertical);
              const scaleX = flipHorizontal ? -imageScale : imageScale;
              const scaleY = flipVertical ? -imageScale : imageScale;

              const layerStyle = {
                position: 'absolute',
                left: frame.x,
                top: frame.y,
                width: frame.width,
                height: frame.height,
                opacity: layer.opacity ?? 1,
                borderRadius: layer.borderRadius ?? 0,
                transform: frame.rotation ? `rotate(${frame.rotation}deg)` : undefined,
                transformOrigin: 'center',
                overflow: 'hidden',
              };

              return (
                <div
                  key={layer.id}
                  style={layerStyle}
                >
                  {isText && (
                    <div
                      style={{
                        width: '100%',
                        height: '100%',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: layer.textAlign === 'left' ? 'flex-start' :
                                       layer.textAlign === 'right' ? 'flex-end' : 'center',
                        color: layer.fill || '#0f172a',
                        fontSize: layer.fontSize ? `${layer.fontSize}px` : '16px',
                        fontFamily: layer.fontFamily || 'Arial, sans-serif',
                        fontWeight: layer.fontWeight ?? 'normal',
                        textAlign: layer.textAlign ?? 'center',
                        padding: '8px',
                        boxSizing: 'border-box',
                        wordWrap: 'break-word',
                        overflowWrap: 'break-word',
                      }}
                    >
                      {layer.content || 'Add your text'}
                    </div>
                  )}
                  {isImage && layer.content && typeof layer.content === 'string' && (layer.content.startsWith('data:') || layer.content.startsWith('blob:')) && (
                    <img
                      src={layer.content}
                      alt={layer.name || 'Template image'}
                      style={{
                        width: '100%',
                        height: '100%',
                        objectFit: objectFitMode,
                        borderRadius: layer.borderRadius ?? 0,
                        display: 'block',
                        transform: `translate(${imageOffsetX}px, ${imageOffsetY}px) scale(${scaleX}, ${scaleY})`,
                        transformOrigin: 'center',
                      }}
                    />
                  )}
                  {layer.type === 'shape' && !isText && !isImage && (
                    <div
                      style={{
                        width: '100%',
                        height: '100%',
                        backgroundColor: layer.fill || 'rgba(37, 99, 235, 0.12)',
                        borderRadius: layer.borderRadius ?? 0,
                        ...getShapeStyling(layer.shape),
                      }}
                    />
                  )}
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </div>
  );
}

PreviewModal.propTypes = {
  isOpen: PropTypes.bool.isRequired,
  onClose: PropTypes.func.isRequired,
};