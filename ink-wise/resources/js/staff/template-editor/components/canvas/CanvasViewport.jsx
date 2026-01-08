import React, { useMemo, useRef, useState, useEffect } from 'react';
import PropTypes from 'prop-types';

import { useBuilderStore } from '../../state/BuilderStore';
import { getShapeVisualStyles } from '../../utils/pageShapes';

// Helper functions to calculate fold panels
function calculateFoldPanels(foldType, widthInch, heightInch, dpi = 96) {
  if (!foldType || !widthInch || !heightInch) {
    return [{ width: widthInch * dpi, height: heightInch * dpi }];
  }

  const widthPx = widthInch * dpi;
  const heightPx = heightInch * dpi;

  switch (foldType) {
    case 'bifold':
      // Two panels side by side
      return [
        { width: widthPx, height: heightPx },
        { width: widthPx, height: heightPx },
      ];
    case 'gatefold':
      // Left and right panels half width, center full width
      return [
        { width: widthPx / 2, height: heightPx },
        { width: widthPx, height: heightPx },
        { width: widthPx / 2, height: heightPx },
      ];
    case 'zfold':
      // Three equal panels
      return [
        { width: widthPx, height: heightPx },
        { width: widthPx, height: heightPx },
        { width: widthPx, height: heightPx },
      ];
    default:
      return [{ width: widthPx, height: heightPx }];
  }
}

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

const DEFAULT_IMAGE_FRAME_PLACEHOLDER_FILL = 'rgba(148, 163, 184, 0.16)';
const DEFAULT_IMAGE_FRAME_PLACEHOLDER_STROKE = 'rgba(148, 163, 184, 0.28)';

function frameContainsPoint(frame, x, y) {
  if (!frame) {
    return false;
  }

  const withinX = x >= frame.x && x <= frame.x + frame.width;
  const withinY = y >= frame.y && y <= frame.y + frame.height;
  return withinX && withinY;
}

function framesIntersect(a, b) {
  if (!a || !b) {
    return false;
  }

  return (
    a.x < b.x + b.width &&
    a.x + a.width > b.x &&
    a.y < b.y + b.height &&
    a.y + a.height > b.y
  );
}

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
  const viewportRef = useRef(null);
  const [draggingLayerId, setDraggingLayerId] = useState(null);
  const [resizingLayerId, setResizingLayerId] = useState(null);
  const [hoveredLayerId, setHoveredLayerId] = useState(null);
  const [viewportScale, setViewportScale] = useState(1);

  const zoom = state.zoom ?? 1;
  const panX = state.panX ?? 0;
  const panY = state.panY ?? 0;
  const selectedLayerId = state.selectedLayerId;

  const foldType = state.template?.fold_type;
  const widthInch = state.template?.width_inch || 5;
  const heightInch = state.template?.height_inch || 7;
  const panels = useMemo(() => calculateFoldPanels(foldType, widthInch, heightInch), [foldType, widthInch, heightInch]);

  // Calculate total canvas width for scaling
  const totalCanvasWidth = panels.reduce((sum, panel) => sum + panel.width, 0);
  const canvasHeight = panels[0]?.height || 400;

  useEffect(() => {
    const updateScale = () => {
      if (viewportRef.current) {
        const rect = viewportRef.current.getBoundingClientRect();
        const availableWidth = rect.width - 40; // padding
        const availableHeight = rect.height - 40;
        const scaleX = availableWidth / totalCanvasWidth;
        const scaleY = availableHeight / canvasHeight;
        const newScale = Math.min(scaleX, scaleY, 1); // don't scale up
        setViewportScale(newScale);
      }
    };

    updateScale();
    window.addEventListener('resize', updateScale);
    return () => window.removeEventListener('resize', updateScale);
  }, [totalCanvasWidth, canvasHeight]);

  if (!page) {
    return null;
  }

  const handleSelectLayer = (layerId) => {
    dispatch({ type: 'SELECT_LAYER', layerId });
  };

  const attachImageLayerToShape = (layerId) => {
    const activePage = state.pages.find((item) => item.id === page.id) ?? page;
    if (!activePage || !Array.isArray(activePage.nodes)) {
      return;
    }

    const draggedLayer = activePage.nodes.find((node) => node.id === layerId);
    if (!draggedLayer || draggedLayer.locked || draggedLayer.type !== 'image') {
      return;
    }

    const frame = draggedLayer.frame;
    const content = typeof draggedLayer.content === 'string' ? draggedLayer.content.trim() : '';
    if (!frame || !content) {
      return;
    }

    const dropCenterX = frame.x + (frame.width ?? 0) / 2;
    const dropCenterY = frame.y + (frame.height ?? 0) / 2;

    const candidateShape = [...activePage.nodes].reverse().find((node) => {
      if (!node || node.id === draggedLayer.id) {
        return false;
      }
      if (node.type !== 'shape' || node.locked) {
        return false;
      }
      if (!node.metadata || !node.metadata.isImageFrame) {
        return false;
      }
      if (!node.frame) {
        return false;
      }
      return frameContainsPoint(node.frame, dropCenterX, dropCenterY) || framesIntersect(node.frame, frame);
    });

    if (!candidateShape) {
      return;
    }

    const shapeMetadata = candidateShape.metadata ?? {};

    const nextMetadata = {
      ...shapeMetadata,
      isImageFrame: true,
      maskVariant: shapeMetadata.maskVariant ?? candidateShape.shape?.id ?? candidateShape.variant ?? 'rectangle',
      placeholderFill: shapeMetadata.placeholderFill ?? DEFAULT_IMAGE_FRAME_PLACEHOLDER_FILL,
      placeholderStroke: shapeMetadata.placeholderStroke ?? DEFAULT_IMAGE_FRAME_PLACEHOLDER_STROKE,
      placeholderLabel: shapeMetadata.placeholderLabel ?? 'Add image',
      placeholderIcon: shapeMetadata.placeholderIcon ?? 'fa-solid fa-image',
      objectFit: 'cover',
      imageScale: 1,
      imageOffsetX: 0,
      imageOffsetY: 0,
      flipHorizontal: false,
      flipVertical: false,
      attribution: shapeMetadata.attribution ?? draggedLayer.metadata?.attribution ?? draggedLayer.name ?? '',
    };

    dispatch({
      type: 'UPDATE_LAYER_PROPS',
      pageId: activePage.id,
      layerId: candidateShape.id,
      props: {
        content,
        fill: 'transparent',
        stroke: 'transparent',
        metadata: nextMetadata,
      },
    });

    dispatch({ type: 'REMOVE_LAYER', pageId: activePage.id, layerId: draggedLayer.id });
    dispatch({ type: 'SELECT_LAYER', layerId: candidateShape.id });
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

    if (event.type !== 'pointercancel') {
      attachImageLayerToShape(dragState.layerId);

      // Ensure final frame is stored (commit final position)
      try {
        const activePage = state.pages.find((p) => p.id === page.id) ?? page;
        const updatedLayer = activePage.nodes.find((n) => n.id === dragState.layerId);
        const finalFrame = updatedLayer?.frame ?? dragState.startFrame;

        // If position changed, write a final update (this will persist coordinates)
        const moved = finalFrame && (
          finalFrame.x !== (dragState.startFrame.x) || finalFrame.y !== (dragState.startFrame.y)
        );

        if (moved) {
          dispatch({
            type: 'UPDATE_LAYER_FRAME',
            pageId: activePage.id,
            layerId: dragState.layerId,
            frame: finalFrame,
            // allow history to track the final commit
          });
        }
      } catch (err) {
        // ignore commit errors
      }
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
  const safeBounds = useMemo(() => {
    const usableWidth = page.width - safeInsets.left - safeInsets.right;
    const usableHeight = page.height - safeInsets.top - safeInsets.bottom;
    return {
      left: safeInsets.left,
      right: safeInsets.left + Math.max(0, usableWidth),
      top: safeInsets.top,
      bottom: safeInsets.top + Math.max(0, usableHeight),
    };
  }, [page.width, page.height, safeInsets.left, safeInsets.right, safeInsets.top, safeInsets.bottom]);

  // Page-level shape/mask (set by shape picker when no layer is selected)
  const pageShape = page.shape ?? null;
  const pageShapeStyles = useMemo(() => getShapeVisualStyles(pageShape), [pageShape]);

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


  return (
    <section className="canvas-viewport" aria-label="Template canvas" ref={viewportRef}>
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
          style={buildStageStyle(zoom, { width: totalCanvasWidth, height: canvasHeight }, panX, panY, viewportScale)}
        >
        <div
          className="fold-container"
          style={{
            display: 'flex',
            width: totalCanvasWidth,
            height: canvasHeight,
            background: page.background || '#ffffff',
            transform: viewportScale < 1 ? `scale(${viewportScale})` : undefined,
            transformOrigin: 'top left',
          }}
          onClick={() => handleSelectLayer(null)}
          data-zoom={zoom}
          ref={canvasRef ?? null}
        >
            {panels.map((panel, index) => (
              <div
                key={index}
                className="panel"
                style={{
                  width: panel.width,
                  height: panel.height,
                  borderRight: index < panels.length - 1 ? '2px dashed #94a3b8' : 'none',
                  position: 'relative',
                }}
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

                {visibleLayers.length === 0 && index === 0 && (
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

                  const shapeMaskKey = metadata.maskVariant ?? layer.shape?.id ?? layer.variant;
                  const isShapeImageFrame = isShape && Boolean(metadata.isImageFrame);
                  const rawImageContent = isImage
                    ? layer.content
                    : isShapeImageFrame
                      ? (layer.content || metadata.backgroundImage || '')
                      : '';
                  const trimmedImageContent = typeof rawImageContent === 'string' ? rawImageContent.trim() : '';
                  const hasImageContent = Boolean(
                    trimmedImageContent &&
                    (trimmedImageContent.startsWith('data:') ||
                      trimmedImageContent.startsWith('blob:') ||
                      /^https?:/i.test(trimmedImageContent)),
                  );
                  const imageSource = hasImageContent ? trimmedImageContent : null;
                  const isImageLike = isImage || isShapeImageFrame;
                  const shapeDescriptor = isShape
                    ? layer.shape ?? {
                        id: shapeMaskKey,
                        variant: layer.variant,
                        borderRadius: layer.borderRadius,
                      }
                    : null;
                  const shapeVisualStyles = isShape ? getShapeVisualStyles(shapeDescriptor) : null;

                  if (shapeDescriptor && shapeDescriptor.id === 'arch-shape' && shapeVisualStyles) {
                    const width = Math.max(frame.width, 1);
                    const height = Math.max(frame.height, 1);
                    const rx = width / 2;
                    const ry = Math.max(1, Math.min(height, rx));
                    const arcY = ry;
                    const bottomY = height;
                    const archPath = `path("M0 ${bottomY} L0 ${arcY} A ${rx} ${ry} 0 0 1 ${width} ${arcY} L ${width} ${bottomY} Z")`;
                    shapeVisualStyles.clipPath = archPath;
                    shapeVisualStyles.WebkitClipPath = archPath;
                  }

                  const stackHint = Number.isFinite(explicitStackIndex)
                    ? explicitStackIndex
                    : renderIndex;
                  const computedZIndex = Math.max(2, Math.round(10 + stackHint));

                  const isBackgroundLayer = resolveLayerPriority(layer) === 0;

                  const style = buildLayerStyle(layer, frame, {
                    hidden: isHidden,
                    opacity: layer.opacity,
                    isSelected,
                    zIndex: isBackgroundLayer ? 0 : computedZIndex,
                  });

                  const layerStyle = {
                    ...style,
                    pointerEvents: isBackgroundLayer ? 'none' : style.pointerEvents,
                  };

                  const withinSafeZone =
                    frame.x >= safeBounds.left &&
                    frame.y >= safeBounds.top &&
                    frame.x + frame.width <= safeBounds.right &&
                    frame.y + frame.height <= safeBounds.bottom;

                  const isOutsideSafe = !withinSafeZone;
                  const isHovered = hoveredLayerId === layer.id;
                  const showMetaBadge = isHovered || isSelected;

                  const textMetaParts = [];
                  if (isText) {
                    if (layer.fontFamily) {
                      textMetaParts.push(layer.fontFamily.split(',')[0]);
                    }
                    if (layer.fontSize) {
                      textMetaParts.push(`${Math.round(layer.fontSize)}px`);
                    }
                    if (layer.textAlign) {
                      textMetaParts.push(layer.textAlign);
                    }
                  }

                  const imageMetaParts = [];
                  if (isImageLike && hasImageContent) {
                    const meta = layer.metadata || {};
                    const nativeWidth = meta.naturalWidth || meta.originalWidth;
                    const nativeHeight = meta.naturalHeight || meta.originalHeight;
                    if (Number.isFinite(nativeWidth) && Number.isFinite(nativeHeight)) {
                      imageMetaParts.push(`${nativeWidth}×${nativeHeight}px`);
                    }
                    if (Number.isFinite(meta.dpi)) {
                      imageMetaParts.push(`${Math.round(meta.dpi)} dpi`);
                    }
                    imageMetaParts.push(objectFitMode);
                  }

                  const previewKey = layer.metadata?.previewKey ?? layer.id;
                  const previewLabel = (
                    (typeof layer.metadata?.previewLabel === 'string' && layer.metadata.previewLabel.trim()) ||
                    (typeof layer.name === 'string' && layer.name.trim()) ||
                    'Layer'
                  );

                  const layerClasses = [
                    'canvas-layer',
                    isSelected ? 'is-selected' : '',
                    isHidden ? 'is-hidden' : '',
                    isLocked ? 'is-locked' : '',
                    isText ? 'is-text' : '',
                    isImageLike ? 'is-image' : '',
                    isDragging ? 'is-dragging' : '',
                    isResizing ? 'is-resizing' : '',
                    isHovered ? 'is-hovered' : '',
                    isOutsideSafe ? 'is-outside-safe' : '',
                  ]
                    .filter(Boolean)
                    .join(' ');

                  return (
                    <div
                      key={layer.id}
                      className={`${layerClasses}${isBackgroundLayer ? ' is-background' : ''}`}
                      style={layerStyle}
                      role={isBackgroundLayer ? undefined : 'button'}
                      tabIndex={isBackgroundLayer ? -1 : 0}
                      aria-hidden={isBackgroundLayer || undefined}
                      onClick={(event) => {
                        if (isBackgroundLayer) {
                          return;
                        }
                        event.stopPropagation();
                        handleSelectLayer(layer.id);
                      }}
                      onPointerDown={(event) => {
                        if (isBackgroundLayer) {
                          return;
                        }

                        // Prevent the canvas pan from starting when interacting with a layer
                        try { event.stopPropagation(); } catch (err) { /* ignore */ }

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
                      onPointerEnter={() => setHoveredLayerId(layer.id)}
                      onPointerLeave={() => {
                        setHoveredLayerId((current) => (current === layer.id ? null : current));
                      }}
                      onKeyDown={(event) => {
                        if (isBackgroundLayer) {
                          return;
                        }
                        if (event.key === 'Enter' || event.key === ' ') {
                          event.preventDefault();
                          handleSelectLayer(layer.id);
                        }
                      }}
                      aria-label={`${layer.name} layer`}
                      aria-pressed={isSelected}
                      data-layer-id={layer.id}
                      data-preview-node={layer.id}
                      data-preview-key={previewKey}
                      data-preview-label={previewLabel}
                      data-changeable={isImageLike ? 'image' : undefined}
                      data-safe-state={isOutsideSafe ? 'warning' : 'ok'}
                    >
                      {isText && (
                        <div
                          className="canvas-layer__text"
                          data-preview-node={layer.id}
                          data-preview-key={previewKey}
                          data-preview-label={previewLabel}
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
                      {isImageLike && (
                        <div
                          className={isShapeImageFrame ? `canvas-shape-frame${hasImageContent ? '' : ' is-empty'}` : undefined}
                          data-preview-key={previewKey}
                          data-preview-label={previewLabel}
                          style={{
                            width: '100%',
                            height: '100%',
                            position: 'relative',
                            overflow: 'hidden',
                            background: isShapeImageFrame && !hasImageContent
                              ? layer.fill || 'rgba(148, 163, 184, 0.18)'
                              : 'transparent',
                            ...(isShapeImageFrame && shapeVisualStyles ? shapeVisualStyles : {}),
                          }}
                        >
                          {hasImageContent ? (
                            <>
                              <img
                                src={imageSource}
                                alt={layer.name || 'Shape image'}
                                className="canvas-layer__image"
                                data-preview-node={layer.id}
                                data-preview-key={previewKey}
                                data-preview-label={previewLabel}
                                data-changeable="image"
                                draggable={false}
                                onDragStart={(e) => e.preventDefault()}
                                style={{
                                  width: '100%',
                                  height: '100%',
                                  objectFit: objectFitMode,
                                  borderRadius: isShapeImageFrame ? 0 : layer.borderRadius ?? 0,
                                  display: 'block',
                                  transform: `translate(${imageOffsetX}px, ${imageOffsetY}px) scale(${scaleX}, ${scaleY})`,
                                  transformOrigin: 'center',
                                  boxShadow: isDragging ? '0 8px 24px rgba(59,130,246,0.18)' : undefined,
                                  outline: isDragging ? '2px solid rgba(59,130,246,0.28)' : undefined,
                                }}
                                onError={(e) => {
                                  console.error('Failed to load image:', imageSource?.substring(0, 100) + '...');
                                  e.target.style.display = 'none';
                                  const errorDiv = e.target.parentElement?.querySelector('.image-error');
                                  if (errorDiv) {
                                    errorDiv.style.display = 'flex';
                                  }
                                }}
                                onLoad={(e) => {
                                  console.log('Image loaded successfully:', layer.name);
                                  e.target.style.display = 'block';
                                  const errorDiv = e.target.parentElement?.querySelector('.image-error');
                                  if (errorDiv) {
                                    errorDiv.style.display = 'none';
                                  }
                                }}
                              />
                              {isSelected && (
                                <div className={`canvas-layer__crop-overlay${isResizing ? ' is-active' : ''}`} aria-hidden="true" />
                              )}
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
                            </>
                          ) : (
                            isShapeImageFrame && (
                              <div className="canvas-shape-frame__placeholder">
                                <i className="fa-solid fa-image" aria-hidden="true"></i>
                                <span>Add image</span>
                              </div>
                            )
                          )}
                        </div>
                      )}
                      {isShape && !isShapeImageFrame && (
                        <div
                          className="canvas-shape-frame"
                          style={{
                            width: '100%',
                            height: '100%',
                            background: layer.fill || 'rgba(37, 99, 235, 0.12)',
                            ...(shapeVisualStyles ?? {}),
                          }}
                        />
                      )}

                      {/* Resize handles for selected images, text, and shapes */}
                      {isSelected && ((isImageLike && hasImageContent) || isText || isShape) && (
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

                      {showMetaBadge && (isText || isImageLike) && (
                        <div
                          className={`canvas-layer__meta${isOutsideSafe ? ' has-warning' : ''}`}
                          aria-hidden="true"
                        >
                          <span className="canvas-layer__meta-primary">
                            {isText ? textMetaParts.filter(Boolean).join(' • ') || 'Text layer' : imageMetaParts.filter(Boolean).join(' • ') || 'Image layer'}
                          </span>
                          {isOutsideSafe && (
                            <span className="canvas-layer__meta-warning">Outside safe zone</span>
                          )}
                        </div>
                      )}

                    </div>
                  );
                })}
              </div>
            ))}
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

function buildStageStyle(zoom, dimensions, panX, panY, viewportScale) {
  return {
    transform: `translate(${panX}px, ${panY}px) scale(${zoom})`,
    transformOrigin: 'top left',
    width: dimensions.width,
    height: dimensions.height,
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

  const metadata = layer?.metadata ?? {};
  const isImageLayer = layer.type === 'image';
  const isShapeImageFrame = layer.type === 'shape' && Boolean(metadata.isImageFrame);
  const rawImageContent = isImageLayer
    ? layer.content
    : isShapeImageFrame
      ? (layer.content || metadata.backgroundImage || '')
      : '';
  const trimmedImageContent = typeof rawImageContent === 'string' ? rawImageContent.trim() : '';
  const hasImageContent = Boolean(
    trimmedImageContent &&
    (trimmedImageContent.startsWith('data:') ||
      trimmedImageContent.startsWith('blob:') ||
      /^https?:/i.test(trimmedImageContent)),
  );

  if (Number.isFinite(zIndex)) {
    style.zIndex = zIndex;
  }

  if (layer.type === 'text') {
    style.background = 'transparent';
    style.borderStyle = 'dashed';
    style.borderWidth = 1;
  } else if (isImageLayer || isShapeImageFrame) {
    if (hasImageContent) {
      style.background = 'transparent';
      style.borderStyle = 'solid';
      style.borderWidth = 2;
      style.borderColor = isSelected ? '#3b82f6' : 'rgba(148, 163, 184, 0.5)';
      style.padding = 0;
    } else {
      style.background = isShapeImageFrame ? layer.fill || 'rgba(148, 163, 184, 0.12)' : 'transparent';
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
