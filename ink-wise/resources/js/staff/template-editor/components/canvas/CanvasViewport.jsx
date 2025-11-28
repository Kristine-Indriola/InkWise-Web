import React, { useMemo, useRef, useState } from 'react';
import PropTypes from 'prop-types';

import { useBuilderStore } from '../../state/BuilderStore';

// Helper functions to constrain layers within safe zone
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

function constrainFrameToSafeZone(frame, page, safeInsets) {
  const minX = safeInsets.left;
  const maxX = page.width - safeInsets.right - frame.width;
  const minY = safeInsets.top;
  const maxY = page.height - safeInsets.bottom - frame.height;

  return {
    ...frame,
    x: Math.max(minX, Math.min(maxX, frame.x)),
    y: Math.max(minY, Math.min(maxY, frame.y)),
  };
}

const BACKGROUND_KEYWORDS = ['background', 'page background', 'page-background', 'bg', 'backdrop', 'base layer'];

function collectStringTokens(value, bucket) {
  if (value === null || value === undefined) {
    return;
  }

  if (typeof value === 'string') {
    bucket.push(value.toLowerCase());
    return;
  }

  if (Array.isArray(value)) {
    value.forEach((entry) => collectStringTokens(entry, bucket));
    return;
  }

  if (typeof value === 'object') {
    Object.values(value).forEach((entry) => collectStringTokens(entry, bucket));
  }
}

function resolveLayerPriority(layer) {
  if (!layer || typeof layer !== 'object') {
    return 1;
  }

  const metadata = layer.metadata ?? {};
  const normalizedName = typeof layer.name === 'string' ? layer.name.toLowerCase() : '';
  const metadataTokens = [];
  collectStringTokens(metadata, metadataTokens);

  const hasBackgroundFlag = BACKGROUND_KEYWORDS.some((keyword) => {
    if (!keyword) {
      return false;
    }

    if (normalizedName.includes(keyword)) {
      return true;
    }

    return metadataTokens.some((value) => value.includes(keyword));
  });

  if (hasBackgroundFlag || layer.type === 'background') {
    return 0;
  }

  return 1;
}

function resolveExplicitStackIndex(layer) {
  const metadata = layer?.metadata ?? {};

  const candidateKeys = ['zIndex', 'z_index', 'stackIndex', 'stack_index', 'order', 'sortOrder', 'sort_order'];

  for (const key of candidateKeys) {
    if (Object.prototype.hasOwnProperty.call(metadata, key)) {
      const value = metadata[key];
      if (typeof value === 'number' && Number.isFinite(value)) {
        return value;
      }
      if (typeof value === 'string') {
        const parsed = Number.parseFloat(value);
        if (Number.isFinite(parsed)) {
          return parsed;
        }
      }
    }
  }

  return null;
}

export function CanvasViewport({ page, canvasRef }) {
  const { state, dispatch } = useBuilderStore();
  const dragStateRef = useRef(null);
  const panStateRef = useRef(null);
  const resizeStateRef = useRef(null);
  const [draggingLayerId, setDraggingLayerId] = useState(null);
  const [resizingLayerId, setResizingLayerId] = useState(null);

  if (!page) {
    return null;
  }

  const zoom = state.zoom ?? 1;
  const panX = state.panX ?? 0;
  const panY = state.panY ?? 0;
  const selectedLayerId = state.selectedLayerId;

  const handleSelectLayer = (layerId) => {
    dispatch({ type: 'SELECT_LAYER', layerId });
  };

  const beginPan = (event) => {
    if (event.target.closest('.canvas-layer')) {
      return;
    }

    const pointerId = event.pointerId;
    panStateRef.current = {
      pointerId,
      start: { x: event.clientX, y: event.clientY },
      startPan: { x: panX, y: panY },
    };

    try {
      event.currentTarget.setPointerCapture(pointerId);
    } catch (err) {
      // Ignore
    }
    event.preventDefault();
  };

  const handlePanMove = (event) => {
    const panState = panStateRef.current;
    if (!panState || event.pointerId !== panState.pointerId) {
      return;
    }

    const deltaX = event.clientX - panState.start.x;
    const deltaY = event.clientY - panState.start.y;

    const nextPanX = panState.startPan.x + deltaX;
    const nextPanY = panState.startPan.y + deltaY;

    dispatch({
      type: 'UPDATE_PAN',
      panX: nextPanX,
      panY: nextPanY,
    });
  };

  const endPan = (event) => {
    const panState = panStateRef.current;
    if (!panState || event.pointerId !== panState.pointerId) {
      return;
    }

    try {
      event.currentTarget.releasePointerCapture(panState.pointerId);
    } catch (err) {
      // Ignore
    }

    panStateRef.current = null;
  };

  const beginLayerDrag = (event, layer, frame) => {
    if (layer.locked) {
      return;
    }

    dispatch({ type: 'BEGIN_LAYER_TRANSFORM' });
    setDraggingLayerId(layer.id);

    const pointerId = event.pointerId;
    dragStateRef.current = {
      pointerId,
      layerId: layer.id,
      start: { x: event.clientX, y: event.clientY },
      startFrame: { ...frame },
    };

    try {
      event.currentTarget.setPointerCapture(pointerId);
    } catch (err) {
      // Pointer capture might fail in older browsers; safe to ignore.
    }
    event.preventDefault();
  };

  const handlePointerMove = (event) => {
    // Handle drag operations
    const dragState = dragStateRef.current;
    if (dragState && event.pointerId === dragState.pointerId) {
      const deltaX = (event.clientX - dragState.start.x) / zoom;
      const deltaY = (event.clientY - dragState.start.y) / zoom;

      const nextFrame = {
        x: dragState.startFrame.x + deltaX,
        y: dragState.startFrame.y + deltaY,
        width: dragState.startFrame.width,
        height: dragState.startFrame.height,
        rotation: dragState.startFrame.rotation ?? 0,
      };

      const constrainedFrame = constrainFrameToSafeZone(nextFrame, page, safeInsets);

      dispatch({
        type: 'UPDATE_LAYER_FRAME',
        pageId: page.id,
        layerId: dragState.layerId,
        frame: constrainedFrame,
        trackHistory: false,
      });
      return;
    }

    // Handle resize operations
    const resizeState = resizeStateRef.current;
    if (resizeState && event.pointerId === resizeState.pointerId) {
      handleResizeMove(event);
      return;
    }

    // Handle pan operations
    const panState = panStateRef.current;
    if (panState && event.pointerId === panState.pointerId) {
      const deltaX = event.clientX - panState.start.x;
      const deltaY = event.clientY - panState.start.y;

      const nextPanX = panState.startPan.x + deltaX;
      const nextPanY = panState.startPan.y + deltaY;

      dispatch({
        type: 'UPDATE_PAN',
        panX: nextPanX,
        panY: nextPanY,
      });
    }
  };

  const endLayerDrag = (event) => {
    const dragState = dragStateRef.current;
    if (!dragState || event.pointerId !== dragState.pointerId) {
      return;
    }

    try {
      event.currentTarget.releasePointerCapture(dragState.pointerId);
    } catch (err) {
      // Ignore release issues.
    }

    dragStateRef.current = null;
    setDraggingLayerId(null);
  };

  const beginLayerResize = (event, layer, frame, corner) => {
    if (layer.locked) {
      return;
    }

    // Prevent the layer selection from happening
    event.stopPropagation();

    dispatch({ type: 'BEGIN_LAYER_TRANSFORM' });
    setResizingLayerId(layer.id);

    const pointerId = event.pointerId;
    const captureTarget = event.currentTarget;

    resizeStateRef.current = {
      pointerId,
      layerId: layer.id,
      corner,
      start: { x: event.clientX, y: event.clientY },
      startFrame: { ...frame },
      hasMoved: false, // Track if we've moved enough to consider it a resize
      captureTarget,
    };

    try {
      captureTarget.setPointerCapture(pointerId);
    } catch (err) {
      console.warn('Failed to capture pointer for resize:', err);
    }

    event.preventDefault();
  };

  const handleResizeMove = (event) => {
    const resizeState = resizeStateRef.current;
    if (!resizeState || event.pointerId !== resizeState.pointerId) {
      return;
    }

    // Adjust start position to account for handle offset (handles are 8px outside the frame)
    const adjustedStartX = resizeState.start.x + 8;
    const adjustedStartY = resizeState.start.y + 8;

    const deltaX = (event.clientX - adjustedStartX) / zoom;
    const deltaY = (event.clientY - adjustedStartY) / zoom;

    // Check if we've moved enough to consider this a resize operation
    const moveThreshold = 3; // pixels
    const hasMoved = Math.abs(deltaX * zoom) > moveThreshold || Math.abs(deltaY * zoom) > moveThreshold;

    if (!resizeState.hasMoved && hasMoved) {
      resizeState.hasMoved = true;
    }

    // Only update if we've moved enough or if we're already resizing
    if (!resizeState.hasMoved) {
      return;
    }

    let nextFrame = { ...resizeState.startFrame };

    // Calculate new dimensions based on which corner is being dragged
    switch (resizeState.corner) {
      case 'nw': // Northwest - resize from top-left
        nextFrame.width = Math.max(20, resizeState.startFrame.width - deltaX);
        nextFrame.height = Math.max(20, resizeState.startFrame.height - deltaY);
        nextFrame.x = resizeState.startFrame.x + (resizeState.startFrame.width - nextFrame.width);
        nextFrame.y = resizeState.startFrame.y + (resizeState.startFrame.height - nextFrame.height);
        break;
      case 'ne': // Northeast - resize from top-right
        nextFrame.width = Math.max(20, resizeState.startFrame.width + deltaX);
        nextFrame.height = Math.max(20, resizeState.startFrame.height - deltaY);
        nextFrame.y = resizeState.startFrame.y + (resizeState.startFrame.height - nextFrame.height);
        break;
      case 'sw': // Southwest - resize from bottom-left
        nextFrame.width = Math.max(20, resizeState.startFrame.width - deltaX);
        nextFrame.height = Math.max(20, resizeState.startFrame.height + deltaY);
        nextFrame.x = resizeState.startFrame.x + (resizeState.startFrame.width - nextFrame.width);
        break;
      case 'se': // Southeast - resize from bottom-right
        nextFrame.width = Math.max(20, resizeState.startFrame.width + deltaX);
        nextFrame.height = Math.max(20, resizeState.startFrame.height + deltaY);
        break;
    }

    dispatch({
      type: 'UPDATE_LAYER_FRAME',
      pageId: page.id,
      layerId: resizeState.layerId,
      frame: constrainFrameToSafeZone(nextFrame, page, safeInsets),
      trackHistory: false,
    });
  };

  const endLayerResize = (event) => {
    const resizeState = resizeStateRef.current;
    if (!resizeState || event.pointerId !== resizeState.pointerId) {
      return;
    }

    // Release pointer capture from the original handle target
    if (resizeState.captureTarget) {
      try {
        resizeState.captureTarget.releasePointerCapture(resizeState.pointerId);
      } catch (err) {
        console.warn('Failed to release pointer capture:', err);
      }
    }

    // If we didn't move enough, it was just a click - don't commit the transform
    if (!resizeState.hasMoved) {
      dispatch({ type: 'CANCEL_LAYER_TRANSFORM' });
    } else {
      dispatch({ type: 'COMMIT_LAYER_TRANSFORM' });
    }

    resizeStateRef.current = null;
    setResizingLayerId(null);
  };

  const safeInsets = resolveInsets(page.safeZone);
  const bleedInsets = resolveInsets(page.bleed);

  // Page-level shape/mask (set by shape picker when no layer is selected)
  const pageShape = page.shape ?? null;

  const orderedLayers = useMemo(() => {
    if (!Array.isArray(page.nodes)) {
      return [];
    }

    return page.nodes
      .map((layer, index) => ({
        layer,
        originalIndex: index,
        priority: resolveLayerPriority(layer),
        explicitStackIndex: resolveExplicitStackIndex(layer),
      }))
      .sort((a, b) => {
        if (a.priority !== b.priority) {
          return a.priority - b.priority;
        }
        const aStack = Number.isFinite(a.explicitStackIndex)
          ? a.explicitStackIndex
          : a.originalIndex;
        const bStack = Number.isFinite(b.explicitStackIndex)
          ? b.explicitStackIndex
          : b.originalIndex;

        if (aStack !== bStack) {
          return aStack - bStack;
        }

        return a.originalIndex - b.originalIndex;
      });
  }, [page.nodes]);

  const visibleLayers = orderedLayers;

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

  return (
    <section className="canvas-viewport" aria-label="Template canvas">
      <div
        className="canvas-viewport__surface"
        role="img"
        aria-label={`Design surface for ${page.name}`}
        onPointerDown={beginPan}
        onPointerMove={handlePanMove}
        onPointerUp={endPan}
        onPointerCancel={endPan}
      >
        <div
          className="canvas-viewport__stage"
          style={buildStageStyle(zoom, page, panX, panY)}
        >
          <div
            className="canvas-viewport__page"
            style={{
              width: page.width,
              height: page.height,
              background: page.background || '#ffffff',
              // Apply page-level mask/shape if present using the new styling function
              ...getShapeStyling(pageShape),
            }}
            onClick={() => handleSelectLayer(null)}
            data-zoom={zoom}
            ref={canvasRef ?? null}
          >
            <div className="canvas-viewport__grid" aria-hidden="true" />
            <div
              className="canvas-viewport__bleed"
              style={buildInsetStyle(bleedInsets)}
              aria-hidden="true"
            />
            <div
              className="canvas-viewport__safe-zone"
              style={buildInsetStyle(safeInsets)}
              aria-hidden="true"
            />

            {visibleLayers.length === 0 && (
              <div className="canvas-viewport__empty-state">
                <h2>Canvas ready</h2>
                <p>
                  Start by selecting a tool on the left. You can add live text, image placeholders, or vector shapes.
                </p>
              </div>
            )}

            {visibleLayers.map(({ layer, originalIndex, explicitStackIndex }, renderIndex) => {
              const frame = resolveFrame(layer, originalIndex, page);
              const isSelected = layer.id === selectedLayerId;
              const isHidden = layer.visible === false;
              const isLocked = layer.locked === true;
              const isText = layer.type === 'text';
              const isImage = layer.type === 'image';
              const isShape = layer.type === 'shape';
              const isDragging = layer.id === draggingLayerId;
              const metadata = layer?.metadata ?? {};
              const objectFitMode = typeof metadata.objectFit === 'string' ? metadata.objectFit : 'cover';
              const rawScale = Number(metadata.imageScale);
              const imageScale = Number.isFinite(rawScale) ? clampNumber(rawScale, 0.25, 4) : 1;
              const rawOffsetX = Number(metadata.imageOffsetX);
              const rawOffsetY = Number(metadata.imageOffsetY);
              const imageOffsetX = Number.isFinite(rawOffsetX) ? clampNumber(rawOffsetX, -500, 500) : 0;
              const imageOffsetY = Number.isFinite(rawOffsetY) ? clampNumber(rawOffsetY, -500, 500) : 0;
              const flipHorizontal = Boolean(metadata.flipHorizontal);
              const flipVertical = Boolean(metadata.flipVertical);
              const scaleX = flipHorizontal ? -imageScale : imageScale;
              const scaleY = flipVertical ? -imageScale : imageScale;

              const isResizing = layer.id === resizingLayerId;

              const stackHint = Number.isFinite(explicitStackIndex)
                ? explicitStackIndex
                : renderIndex;
              const computedZIndex = Math.max(2, Math.round(10 + stackHint));

              const style = buildLayerStyle(layer, frame, {
                hidden: isHidden,
                opacity: layer.opacity,
                isSelected,
                zIndex: computedZIndex,
              });

              return (
                <div
                  key={layer.id}
                  className={`canvas-layer${isSelected ? ' is-selected' : ''}${isHidden ? ' is-hidden' : ''}${isLocked ? ' is-locked' : ''}${isText ? ' is-text' : ''}${isImage ? ' is-image' : ''}${isDragging ? ' is-dragging' : ''}${isResizing ? ' is-resizing' : ''}`}
                  style={style}
                  role="button"
                  tabIndex={0}
                  onClick={(event) => {
                    event.stopPropagation();
                    handleSelectLayer(layer.id);
                  }}
                  onPointerDown={(event) => {
                    // Don't start dragging if clicking on a resize handle
                    if (event.target.classList.contains('canvas-layer__resize-handle') ||
                        event.target.closest('.canvas-layer__resize-handle')) {
                      return;
                    }
                    handleSelectLayer(layer.id);
                    beginLayerDrag(event, layer, frame);
                  }}
                  onPointerMove={handlePointerMove}
                  onPointerUp={(event) => {
                    endLayerResize(event);
                    endLayerDrag(event);
                  }}
                  onPointerCancel={(event) => {
                    endLayerResize(event);
                    endLayerDrag(event);
                  }}
                  onKeyDown={(event) => {
                    if (event.key === 'Enter' || event.key === ' ') {
                      event.preventDefault();
                      handleSelectLayer(layer.id);
                    }
                  }}
                  aria-label={`${layer.name} layer`}
                  aria-pressed={isSelected}
                  data-layer-id={layer.id}
                  data-preview-node={layer.id}
                  data-changeable={isImage ? 'image' : undefined}
                >
                  {isText && (
                    <div
                      className="canvas-layer__text"
                      data-preview-node={layer.id}
                      style={{
                        color: layer.fill || '#0f172a',
                        fontSize: layer.fontSize ? `${layer.fontSize}px` : undefined,
                          fontFamily: layer.fontFamily,
                          fontWeight: layer.fontWeight ?? undefined,
                          textAlign: layer.textAlign ?? 'center',
                      }}
                    >
                      {layer.content || 'Add your text'}
                    </div>
                  )}
                  {isImage && layer.content && typeof layer.content === 'string' && (layer.content.startsWith('data:') || layer.content.startsWith('blob:')) && (
                    <div style={{ width: '100%', height: '100%', position: 'relative', overflow: 'hidden' }}>
                      <img
                        src={layer.content}
                        alt={layer.name || 'Uploaded image'}
                        className="canvas-layer__image"
                        data-preview-node={layer.id}
                        data-changeable="image"
                        style={{
                          width: '100%',
                          height: '100%',
                          objectFit: objectFitMode,
                          borderRadius: layer.borderRadius ?? 0,
                          display: 'block',
                          transform: `translate(${imageOffsetX}px, ${imageOffsetY}px) scale(${scaleX}, ${scaleY})`,
                          transformOrigin: 'center',
                        }}
                        onError={(e) => {
                          console.error('Failed to load image:', layer.content?.substring(0, 100) + '...');
                          // Hide the broken image and show error message
                          e.target.style.display = 'none';
                          const errorDiv = e.target.parentElement?.querySelector('.image-error');
                          if (errorDiv) {
                            errorDiv.style.display = 'flex';
                          }
                        }}
                        onLoad={(e) => {
                          console.log('Image loaded successfully:', layer.name);
                          // Ensure the image is visible and error is hidden when it loads successfully
                          e.target.style.display = 'block';
                          const errorDiv = e.target.parentElement?.querySelector('.image-error');
                          if (errorDiv) {
                            errorDiv.style.display = 'none';
                          }
                        }}
                      />
                      <div
                        className="image-error"
                        style={{
                          display: 'none',
                          position: 'absolute',
                          top: '50%',
                          left: '50%',
                          transform: 'translate(-50%, -50%)',
                          flexDirection: 'column',
                          alignItems: 'center',
                          justifyContent: 'center',
                          gap: '0.4rem',
                          fontSize: '0.8rem',
                          color: 'rgba(239, 68, 68, 0.8)',
                          textAlign: 'center',
                          padding: '0.5rem',
                          background: 'rgba(255, 255, 255, 0.9)',
                          borderRadius: '4px',
                          border: '1px solid rgba(239, 68, 68, 0.3)',
                        }}
                      >
                        <span>⚠️</span>
                        <p>Failed to load image</p>
                      </div>
                    </div>
                  )}

                  {/* Resize handles for selected images and text */}
                  {isSelected && ((isImage && layer.content) || isText) && (
                    <>
                      {/* Northwest corner */}
                      <div
                        className="canvas-layer__resize-handle canvas-layer__resize-handle--nw"
                        onPointerDown={(e) => {
                          e.stopPropagation();
                          beginLayerResize(e, layer, frame, 'nw');
                        }}
                        onClick={(e) => e.stopPropagation()}
                        style={{
                          position: 'absolute',
                          top: '-8px',
                          left: '-8px',
                          width: '16px',
                          height: '16px',
                          backgroundColor: '#3b82f6',
                          border: '2px solid #ffffff',
                          borderRadius: '3px',
                          cursor: 'nw-resize',
                          zIndex: 1000,
                          boxShadow: '0 2px 4px rgba(0, 0, 0, 0.3)',
                          transition: 'all 0.1s ease',
                          pointerEvents: 'auto',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                        }}
                        onMouseEnter={(e) => {
                          e.currentTarget.style.transform = 'scale(1.2)';
                          e.currentTarget.style.backgroundColor = '#1d4ed8';
                        }}
                        onMouseLeave={(e) => {
                          e.currentTarget.style.transform = 'scale(1)';
                          e.currentTarget.style.backgroundColor = '#3b82f6';
                        }}
                      >
                        <div style={{
                          width: '4px',
                          height: '4px',
                          backgroundColor: '#ffffff',
                          borderRadius: '1px',
                          opacity: 0.8,
                          pointerEvents: 'none',
                        }} />
                      </div>
                      {/* Northeast corner */}
                      <div
                        className="canvas-layer__resize-handle canvas-layer__resize-handle--ne"
                        onPointerDown={(e) => {
                          e.stopPropagation();
                          beginLayerResize(e, layer, frame, 'ne');
                        }}
                        onClick={(e) => e.stopPropagation()}
                        style={{
                          position: 'absolute',
                          top: '-8px',
                          right: '-8px',
                          width: '16px',
                          height: '16px',
                          backgroundColor: '#3b82f6',
                          border: '2px solid #ffffff',
                          borderRadius: '3px',
                          cursor: 'ne-resize',
                          zIndex: 1000,
                          boxShadow: '0 2px 4px rgba(0, 0, 0, 0.3)',
                          transition: 'all 0.1s ease',
                          pointerEvents: 'auto',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                        }}
                        onMouseEnter={(e) => {
                          e.currentTarget.style.transform = 'scale(1.2)';
                          e.currentTarget.style.backgroundColor = '#1d4ed8';
                        }}
                        onMouseLeave={(e) => {
                          e.currentTarget.style.transform = 'scale(1)';
                          e.currentTarget.style.backgroundColor = '#3b82f6';
                        }}
                      >
                        <div style={{
                          width: '4px',
                          height: '4px',
                          backgroundColor: '#ffffff',
                          borderRadius: '1px',
                          opacity: 0.8,
                          pointerEvents: 'none',
                        }} />
                      </div>
                      {/* Southwest corner */}
                      <div
                        className="canvas-layer__resize-handle canvas-layer__resize-handle--sw"
                        onPointerDown={(e) => {
                          e.stopPropagation();
                          beginLayerResize(e, layer, frame, 'sw');
                        }}
                        onClick={(e) => e.stopPropagation()}
                        style={{
                          position: 'absolute',
                          bottom: '-8px',
                          left: '-8px',
                          width: '16px',
                          height: '16px',
                          backgroundColor: '#3b82f6',
                          border: '2px solid #ffffff',
                          borderRadius: '3px',
                          cursor: 'sw-resize',
                          zIndex: 1000,
                          boxShadow: '0 2px 4px rgba(0, 0, 0, 0.3)',
                          transition: 'all 0.1s ease',
                          pointerEvents: 'auto',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                        }}
                        onMouseEnter={(e) => {
                          e.currentTarget.style.transform = 'scale(1.2)';
                          e.currentTarget.style.backgroundColor = '#1d4ed8';
                        }}
                        onMouseLeave={(e) => {
                          e.currentTarget.style.transform = 'scale(1)';
                          e.currentTarget.style.backgroundColor = '#3b82f6';
                        }}
                      >
                        <div style={{
                          width: '4px',
                          height: '4px',
                          backgroundColor: '#ffffff',
                          borderRadius: '1px',
                          opacity: 0.8,
                          pointerEvents: 'none',
                        }} />
                      </div>
                      {/* Southeast corner */}
                      <div
                        className="canvas-layer__resize-handle canvas-layer__resize-handle--se"
                        onPointerDown={(e) => {
                          e.stopPropagation();
                          beginLayerResize(e, layer, frame, 'se');
                        }}
                        onClick={(e) => e.stopPropagation()}
                        style={{
                          position: 'absolute',
                          bottom: '-8px',
                          right: '-8px',
                          width: '16px',
                          height: '16px',
                          backgroundColor: '#3b82f6',
                          border: '2px solid #ffffff',
                          borderRadius: '3px',
                          cursor: 'se-resize',
                          zIndex: 1000,
                          boxShadow: '0 2px 4px rgba(0, 0, 0, 0.3)',
                          transition: 'all 0.1s ease',
                          pointerEvents: 'auto',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'center',
                        }}
                        onMouseEnter={(e) => {
                          e.currentTarget.style.transform = 'scale(1.2)';
                          e.currentTarget.style.backgroundColor = '#1d4ed8';
                        }}
                        onMouseLeave={(e) => {
                          e.currentTarget.style.transform = 'scale(1)';
                          e.currentTarget.style.backgroundColor = '#3b82f6';
                        }}
                      >
                        <div style={{
                          width: '4px',
                          height: '4px',
                          backgroundColor: '#ffffff',
                          borderRadius: '1px',
                          opacity: 0.8,
                          pointerEvents: 'none',
                        }} />
                      </div>
                    </>
                  )}

                  {isShape && !isText && !isImage && (
                    <span className="canvas-layer__label">{layer.name}</span>
                  )}
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </section>
  );
}

CanvasViewport.propTypes = {
  page: PropTypes.shape({
    id: PropTypes.string.isRequired,
    name: PropTypes.string.isRequired,
    width: PropTypes.number.isRequired,
    height: PropTypes.number.isRequired,
    nodes: PropTypes.array,
  }).isRequired,
  canvasRef: PropTypes.shape({ current: PropTypes.any }),
};

CanvasViewport.defaultProps = {
  canvasRef: null,
};

function buildStageStyle(zoom, page, panX, panY) {
  return {
    transform: `translate(${panX}px, ${panY}px) scale(${zoom})`,
    transformOrigin: 'top left',
    width: page.width,
    height: page.height,
  };
}

function buildLayerStyle(layer, frame, { hidden, opacity, isSelected, zIndex }) {
  const baseOpacity = hidden ? 0.35 : Math.max(0.1, opacity ?? 1);

  const style = {
    left: frame.x,
    top: frame.y,
    width: frame.width,
    height: frame.height,
    opacity: baseOpacity,
    borderColor: layer.stroke ?? 'rgba(15, 23, 42, 0.18)',
    borderRadius: layer.borderRadius ?? 0,
    transform: frame.rotation ? `rotate(${frame.rotation}deg)` : undefined,
    transformOrigin: 'center',
  };

  if (Number.isFinite(zIndex)) {
    style.zIndex = zIndex;
  }

  if (layer.type === 'text') {
    style.background = 'transparent';
    style.borderStyle = 'dashed';
    style.borderWidth = 1;
  } else if (layer.type === 'image') {
    if (layer.content) {
      style.background = 'transparent';
      style.borderStyle = 'solid';
      style.borderWidth = 2;
      style.borderColor = isSelected ? '#3b82f6' : 'rgba(148, 163, 184, 0.5)';
      style.padding = 0;
    } else {
      style.background = 'transparent';
      style.borderStyle = 'dashed';
      style.borderWidth = 2;
      style.borderColor = isSelected ? '#3b82f6' : 'rgba(148, 163, 184, 0.3)';
    }
  } else {
    style.background = layer.fill ? applyOpacity(layer.fill, layer.opacity) : 'rgba(37, 99, 235, 0.12)';
    style.borderStyle = 'solid';
    style.borderWidth = 1;
  }

  return style;
}

function buildInsetStyle({ top, right, bottom, left }) {
  return {
    top,
    right,
    bottom,
    left,
  };
}

function resolveFrame(layer, index, page) {
  const { width, height } = page;
  const frame = layer.frame;

  if (frame) {
    return {
      x: frame.x,
      y: frame.y,
      width: frame.width,
      height: frame.height,
      rotation: frame.rotation ?? 0,
    };
  }

  const padding = 48;
  const fallbackWidth = width * 0.6;
  const fallbackHeight = Math.min(160, height * 0.18);
  const offset = index * 32;

  return {
    x: padding + (offset % 120),
    y: padding + offset,
    width: fallbackWidth,
    height: fallbackHeight,
    rotation: 0,
  };
}

function applyOpacity(color, opacity = 1) {
  if (typeof color !== 'string') {
    return color || 'rgba(37, 99, 235, 0.12)';
  }

  if (opacity >= 0.999) {
    return color;
  }

  const rgba = parseColor(color, opacity);
  return rgba || color;
}

function parseColor(color, opacity) {
  if (!color) return null;

  if (color.startsWith('#')) {
    const hex = color.slice(1);
    const bigint = parseInt(hex, 16);
    if (Number.isNaN(bigint)) {
      return null;
    }
    const hasAlpha = hex.length === 8;
    const r = (bigint >> (hasAlpha ? 24 : 16)) & 255;
    const g = (bigint >> (hasAlpha ? 16 : 8)) & 255;
    const b = (bigint >> (hasAlpha ? 8 : 0)) & 255;
    return `rgba(${r}, ${g}, ${b}, ${opacity})`;
  }

  if (color.startsWith('rgb')) {
    const match = color.match(/rgba?\(([^)]+)\)/);
    if (!match) {
      return null;
    }
    const channels = match[1]
      .split(',')
      .map((value) => Number.parseFloat(value.trim()))
      .filter((value, index) => index < 3 && Number.isFinite(value));
    if (channels.length !== 3) {
      return null;
    }
    const [r, g, b] = channels;
    return `rgba(${r}, ${g}, ${b}, ${opacity})`;
  }

  return null;
}

function clampNumber(value, min, max) {
  if (!Number.isFinite(value)) {
    return min;
  }
  if (value < min) {
    return min;
  }
  if (value > max) {
    return max;
  }
  return value;
}
