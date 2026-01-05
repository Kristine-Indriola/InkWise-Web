let pageIncrement = 0;
let layerIncrement = 0;

const SHAPE_COLORS = ['#2563eb', '#dc2626', '#059669', '#f59e0b', '#7c3aed'];

const PAGE_TYPE_ALIASES = {
  'front': 'front',
  'front-side': 'front',
  'front-cover': 'front',
  'cover': 'front',
  'back': 'back',
  'back-side': 'back',
  'back-cover': 'back',
  'reverse': 'back',
  'rear': 'back',
  'inside': 'inside',
  'interior': 'inside',
  'inside-left': 'inside-left',
  'inside-right': 'inside-right',
  'inside-spread': 'inside',
  'spread': 'inside',
  'outside': 'front',
};

const PAGE_TYPE_LABEL_MAP = {
  front: 'Front Side',
  back: 'Back Side',
  inside: 'Inside Spread',
  'inside-left': 'Inside Left',
  'inside-right': 'Inside Right',
};

function fallbackPageType(index, total) {
  if (total <= 1) {
    return 'front';
  }
  if (index === 0) {
    return 'front';
  }
  if (index === 1) {
    return 'back';
  }
  return `page-${index + 1}`;
}

export function normalizePageTypeValue(value) {
  if (value === undefined || value === null) {
    return null;
  }

  const raw = String(value).trim();
  if (!raw) {
    return null;
  }

  const compact = raw.toLowerCase().replace(/[_\s]+/g, '-');
  if (PAGE_TYPE_ALIASES[compact]) {
    return PAGE_TYPE_ALIASES[compact];
  }

  if (/^page-\d+$/.test(compact)) {
    return compact;
  }

  return compact;
}

export function derivePageLabel(pageType, index = 0, total = 1) {
  const normalized = normalizePageTypeValue(pageType);

  if (!normalized) {
    return `Page ${index + 1}`;
  }

  if (PAGE_TYPE_LABEL_MAP[normalized]) {
    return PAGE_TYPE_LABEL_MAP[normalized];
  }

  if (/^(front|back|inside)(-\d+)$/.test(normalized)) {
    const [base, suffix] = normalized.split('-');
    const human = PAGE_TYPE_LABEL_MAP[base] ?? base.charAt(0).toUpperCase() + base.slice(1);
    return `${human} (${suffix.replace('-', '#')})`;
  }

  if (/^inside-(left|right)$/.test(normalized)) {
    const [, side] = normalized.split('-');
    const human = side.charAt(0).toUpperCase() + side.slice(1);
    return `Inside ${human}`;
  }

  if (/^page-\d+$/.test(normalized)) {
    const pageNumber = normalized.split('-')[1];
    return `Page ${pageNumber}`;
  }

  if (normalized === 'spread') {
    return 'Inside Spread';
  }

  if (normalized === 'cover') {
    return 'Front Side';
  }

  if (normalized === 'rear') {
    return 'Back Side';
  }

  // Fall back to title case for any other custom page types
  return normalized
    .split('-')
    .map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1))
    .join(' ');
}

function resolvePageName(currentName, generatedName, fallbackIndex) {
  if (typeof currentName === 'string' && currentName.trim() !== '') {
    const trimmed = currentName.trim();
    const looksAuto = /^Page\s+\d+$/i.test(trimmed);
    if (!looksAuto) {
      return trimmed;
    }
  }

  return generatedName || `Page ${fallbackIndex + 1}`;
}

function normalizeNode(node, index, page) {
  const id = node?.id ?? `layer-${layerIncrement === 0 ? index : layerIncrement}`;
  layerIncrement += 1;

  const type = node?.type ?? node?.kind ?? 'shape';
  const frame = normalizeFrame(node?.frame || node?.metadata?.frame || null, page, type, index);
  const resolvedName = node?.name ?? `Layer ${index + 1}`;
  const baseMetadata = { ...(node?.metadata ?? {}) };
  const labelFallback = baseMetadata.previewLabel ?? resolvedName;
  const metadata = {
    ...baseMetadata,
    previewKey: baseMetadata.previewKey ?? id,
    previewLabel: labelFallback,
  };

  return {
    id,
    name: resolvedName,
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
    metadata,
    editable: node?.editable !== undefined ? Boolean(node.editable) : true,
    replaceable: node?.replaceable !== undefined ? Boolean(node.replaceable) : type === 'image',
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

export function createPage(page, fallbackIndex = 0, totalPages = 1) {
  const id = page?.id ?? `page-${pageIncrement === 0 ? fallbackIndex : pageIncrement}`;
  pageIncrement += 1;

  const nodes = Array.isArray(page?.nodes)
    ? page.nodes.map((node, index) => normalizeNode(node, index, page))
    : [];

  const metadata = { ...(page?.metadata ?? {}) };
  const typeCandidates = [
    page?.pageType,
    metadata.pageType,
    metadata.side,
    metadata.role,
    metadata.sideLabel,
    page?.name,
  ];

  let pageType = null;
  for (const candidate of typeCandidates) {
    const normalized = normalizePageTypeValue(candidate);
    if (normalized) {
      pageType = normalized;
      break;
    }
  }

  if (!pageType) {
    pageType = fallbackPageType(fallbackIndex, totalPages);
  }

  const derivedLabel = derivePageLabel(pageType, fallbackIndex, totalPages);
  const generatedName = metadata.generatedName ?? derivedLabel;

  metadata.pageType = pageType;
  metadata.side = metadata.side ?? pageType;
  metadata.sideLabel = metadata.sideLabel ?? derivedLabel;
  metadata.generatedName = generatedName;

  return {
    id,
    name: resolvePageName(page?.name, generatedName, fallbackIndex),
    width: page?.width ?? 400,
    height: page?.height ?? 400,
    background: page?.background ?? '#ffffff',
    safeZone: page?.safeZone ?? null,
    bleed: page?.bleed ?? null,
    nodes,
    pageType,
    metadata,
  };
}

export function createLayer(type = 'shape', page = null, overrides = {}) {
  const id = overrides.id ?? `layer-${layerIncrement}`;
  layerIncrement += 1;

  const frameOverride = overrides.frame ? { ...overrides.frame } : null;
  const frame = frameOverride || (page ? normalizeFrame(null, page, type, page.nodes?.length ?? 0) : null);
  const baseFill = overrides.fill ?? defaultFill(type);
  const resolvedName = overrides.name ?? defaultLayerName(type);
  const baseMetadata = { ...(overrides.metadata ?? {}) };
  const metadata = {
    ...baseMetadata,
    previewKey: baseMetadata.previewKey ?? id,
    previewLabel: baseMetadata.previewLabel ?? resolvedName,
  };

  return {
    id,
    name: resolvedName,
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
    metadata,
    editable: overrides.editable ?? true,
    replaceable: overrides.replaceable ?? (type === 'image'),
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
