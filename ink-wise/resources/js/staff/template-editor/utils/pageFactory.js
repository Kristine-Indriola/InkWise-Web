let pageIncrement = 0;
let layerIncrement = 0;

const SHAPE_COLORS = ['#2563eb', '#dc2626', '#059669', '#f59e0b', '#7c3aed'];

function normalizeNode(node, index, page) {
  const id = node?.id ?? `layer-${layerIncrement === 0 ? index : layerIncrement}`;
  layerIncrement += 1;

  const type = node?.type ?? node?.kind ?? 'shape';
  const frame = normalizeFrame(node?.frame || node?.metadata?.frame || null, page, type, index);

  return {
    id,
    name: node?.name ?? `Layer ${index + 1}`,
    type,
    visible: node?.visible !== undefined ? Boolean(node.visible) : true,
    locked: node?.locked !== undefined ? Boolean(node.locked) : false,
    opacity: typeof node?.opacity === 'number' ? node.opacity : 1,
    frame,
    fill: node?.fill ?? node?.color ?? defaultFill(type),
    stroke: node?.stroke ?? node?.border ?? null,
    content: node?.content ?? node?.text ?? '',
    fontSize: typeof node?.fontSize === 'number' ? node.fontSize : 42,
  fontFamily: node?.fontFamily ?? 'Inter, sans-serif',
  // Ensure an explicit fontWeight is present for text layers (defaults to normal 400)
  fontWeight: node?.fontWeight ?? '400',
    textAlign: node?.textAlign ?? 'center',
    borderRadius: typeof node?.borderRadius === 'number' ? node.borderRadius : defaultBorderRadius(type),
    metadata: node?.metadata ?? {},
  };
}

function normalizeFrame(frame, page, type, index = 0) {
  if (!frame) {
    if (!page) {
      return null;
    }

    const padding = 48;
    const fallbackWidth = Math.round(page.width * 0.6);
    const fallbackHeight = Math.min(200, Math.round(page.height * 0.22));
    const offset = index * 32;

    return {
      x: padding + (offset % 120),
      y: padding + offset,
      width: type === 'text' ? Math.max(320, fallbackWidth) : fallbackWidth,
      height: type === 'text' ? Math.max(120, Math.round(page.height * 0.12)) : fallbackHeight,
      rotation: 0,
    };
  }

  const toNumber = (value) => (typeof value === 'number' ? value : parseFloat(value));

  return {
    x: toNumber(frame.x) || 0,
    y: toNumber(frame.y) || 0,
    width: Math.max(1, toNumber(frame.width) || 1),
    height: Math.max(1, toNumber(frame.height) || 1),
    rotation: toNumber(frame.rotation) || 0,
  };
}

export function createPage(page, fallbackIndex = 0) {
  const id = page?.id ?? `page-${pageIncrement === 0 ? fallbackIndex : pageIncrement}`;
  pageIncrement += 1;

  const nodes = Array.isArray(page?.nodes)
    ? page.nodes.map((node, index) => normalizeNode(node, index, page))
    : [];

  return {
    id,
    name: page?.name ?? `Page ${fallbackIndex + 1}`,
    width: page?.width ?? 400,
    height: page?.height ?? 400,
    background: page?.background ?? '#ffffff',
    safeZone: page?.safeZone ?? null,
    bleed: page?.bleed ?? null,
    nodes,
    metadata: page?.metadata ?? {},
  };
}

export function createLayer(type = 'shape', page = null, overrides = {}) {
  const id = overrides.id ?? `layer-${layerIncrement}`;
  layerIncrement += 1;

  const frameOverride = overrides.frame ? { ...overrides.frame } : null;
  const frame = frameOverride || (page ? normalizeFrame(null, page, type, page.nodes?.length ?? 0) : null);
  const baseFill = overrides.fill ?? defaultFill(type);

  return {
    id,
    name: overrides.name ?? defaultLayerName(type),
    type,
    visible: overrides.visible !== undefined ? overrides.visible : true,
    locked: overrides.locked ?? false,
    opacity: typeof overrides.opacity === 'number' ? overrides.opacity : 1,
    frame,
    fill: baseFill,
    stroke: overrides.stroke ?? null,
    content: overrides.content ?? (type === 'text' ? 'Double-click to edit text' : ''),
    fontSize: overrides.fontSize ?? (type === 'text' ? 48 : null),
    fontFamily: overrides.fontFamily ?? 'Inter, sans-serif',
  // default font weight for newly created text layers
  fontWeight: overrides.fontWeight ?? (type === 'text' ? '400' : undefined),
    textAlign: overrides.textAlign ?? 'center',
    borderRadius: overrides.borderRadius ?? defaultBorderRadius(type, overrides.variant),
    variant: overrides.variant ?? (type === 'shape' ? 'rectangle' : null),
    metadata: { ...(overrides.metadata ?? {}) },
  };
}

function defaultLayerName(type) {
  if (type === 'text') {
    return 'Text box';
  }
  if (type === 'image') {
    return 'Image placeholder';
  }
  return 'Shape';
}

function defaultFill(type) {
  switch (type) {
    case 'text':
      return '#0f172a';
    case 'image':
      return 'rgba(148, 163, 184, 0.2)';
    default:
      return SHAPE_COLORS[layerIncrement % SHAPE_COLORS.length];
  }
}

function defaultBorderRadius(type, variant) {
  if (type === 'shape' && (variant === 'circle' || variant === 'ellipse')) {
    return 9999;
  }
  if (type === 'shape') {
    return 16;
  }
  return 0;
}
