import React, { useMemo, useState, useRef, useEffect, useCallback } from 'react';

import { useBuilderStore } from '../../state/BuilderStore';
import { createLayer } from '../../utils/pageFactory';
import generateFontPresets from '../../utils/fontPresetGenerator';
import { LayersPanel } from './LayersPanel';

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

function clamp(value, min = 0, max = 1) {
  return Math.min(max, Math.max(min, value));
}

function hexToHsl(hex) {
  if (typeof hex !== 'string') {
    return { h: 0, s: 0, l: 0 };
  }
  let normalized = hex.trim().replace('#', '').toLowerCase();
  if (normalized.length === 3) {
    normalized = normalized.split('').map((ch) => ch + ch).join('');
  }
  if (!/^[0-9a-f]{6}$/.test(normalized)) {
    normalized = 'ffffff';
  }
  const r = parseInt(normalized.slice(0, 2), 16) / 255;
  const g = parseInt(normalized.slice(2, 4), 16) / 255;
  const b = parseInt(normalized.slice(4, 6), 16) / 255;

  const max = Math.max(r, g, b);
  const min = Math.min(r, g, b);
  const delta = max - min;
  let h = 0;
  if (delta !== 0) {
    if (max === r) {
      h = ((g - b) / delta) % 6;
    } else if (max === g) {
      h = (b - r) / delta + 2;
    } else {
      h = (r - g) / delta + 4;
    }
    h = Math.round(h * 60);
    if (h < 0) {
      h += 360;
    }
  }

  const l = (max + min) / 2;
  const s = delta === 0 ? 0 : delta / (1 - Math.abs(2 * l - 1));
  return {
    h,
    s: Math.round(s * 100),
    l: Math.round(l * 100),
  };
}

function hexToRgb(hex) {
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
  return result ? `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}` : '255,255,255';
}

function hslToHex(h, s, l) {
  h /= 360;
  s /= 100;
  l /= 100;
  const a = s * Math.min(l, 1 - l);
  const f = n => {
    const k = (n + h * 12) % 12;
    const color = l - a * Math.max(Math.min(k - 3, 9 - k, 1), -1);
    return Math.round(255 * color).toString(16).padStart(2, '0');
  };
  return `#${f(0)}${f(8)}${f(4)}`.toUpperCase();
}

const TOOL_SECTIONS = [
  { id: 'text', label: 'Text', description: 'Add headings, body copy, and typography styles.', icon: 'fa-solid fa-t' },
  { id: 'images', label: 'Upload', description: 'Upload customer photos or choose from brand assets.', icon: 'fa-solid fa-cloud-arrow-up' },
  { id: 'frames', label: 'Frames', description: 'Insert vector shapes, lines, and frames.', icon: 'fas fa-images' },
  { id: 'shapes', label: 'Shape', description: 'Add basic geometric shapes.', icon: 'fa-solid fa-shapes' },
  { id: 'photos', label: 'Photos', description: 'Add photos and images.', icon: 'fa-solid fa-image' },
  { id: 'icons', label: 'Icons', description: 'Insert icons and symbols.', icon: 'fa-solid fa-icons' },
  { id: 'draw', label: 'Draw', description: 'Draw shapes and lines.', icon: 'fa-solid fa-pencil' },
  { id: 'background', label: 'Background', description: 'Set background.', icon: 'fa-solid fa-palette' },
  { id: 'colors', label: 'Colors', description: 'Generate color palettes.', icon: 'fa-solid fa-palette' },
  { id: 'layers', label: 'Layers', description: 'Manage layers.', icon: 'fa-solid fa-layer-group' },
  { id: 'quotes', label: 'Quotes', description: 'Add quotes.', icon: 'fa-solid fa-quote-left' },
];


const SHAPE_VARIANT_NORMALIZATION = {
  rectangle: { variant: 'rectangle', mask: 'rectangle' },
  square: { variant: 'rectangle', mask: 'rectangle' },
  circle: { variant: 'circle', mask: 'circle' },
  ellipse: { variant: 'circle', mask: 'circle' },
  frame: { variant: 'frame', mask: 'frame' },
  layout: { variant: 'layout', mask: 'layout' },
  polygon: { variant: 'polygon', mask: 'polygon' },
  triangle: { variant: 'polygon', mask: 'triangle' },
  diamond: { variant: 'polygon', mask: 'diamond' },
  hexagon: { variant: 'polygon', mask: 'hexagon' },
  octagon: { variant: 'polygon', mask: 'octagon' },
  pentagon: { variant: 'polygon', mask: 'pentagon' },
  parallelogram: { variant: 'polygon', mask: 'parallelogram' },
  trapezoid: { variant: 'polygon', mask: 'trapezoid' },
  rhombus: { variant: 'polygon', mask: 'rhombus' },
  star: { variant: 'polygon', mask: 'star' },
  shield: { variant: 'polygon', mask: 'shield' },
  badge: { variant: 'polygon', mask: 'badge' },
  'triangle-equilateral': { variant: 'polygon', mask: 'triangle-equilateral' },
  'triangle-right': { variant: 'polygon', mask: 'triangle-right' },
  'triangle-isosceles': { variant: 'polygon', mask: 'triangle-isosceles' },
  'triangle-scalene': { variant: 'polygon', mask: 'triangle-scalene' },
  heart: { variant: 'organic', mask: 'heart' },
  'cloud-shape': { variant: 'organic', mask: 'cloud-shape' },
  flower: { variant: 'organic', mask: 'flower' },
  butterfly: { variant: 'organic', mask: 'butterfly' },
  leaf: { variant: 'organic', mask: 'leaf' },
  balloon: { variant: 'organic', mask: 'balloon' },
  crown: { variant: 'organic', mask: 'crown' },
  'puzzle-piece': { variant: 'organic', mask: 'puzzle-piece' },
  'ribbon-banner': { variant: 'organic', mask: 'ribbon-banner' },
  blob: { variant: 'organic', mask: 'blob' },
  wave: { variant: 'organic', mask: 'wave' },
  sun: { variant: 'organic', mask: 'sun' },
  moon: { variant: 'organic', mask: 'moon' },
  'tag-shape': { variant: 'tag', mask: 'tag-shape' },
  'ticket-shape': { variant: 'tag', mask: 'ticket-shape' },
  'polaroid-frame': { variant: 'frame', mask: 'polaroid-frame' },
  'film-strip-frame': { variant: 'frame', mask: 'film-strip-frame' },
  'torn-paper-frame': { variant: 'frame', mask: 'torn-paper-frame' },
  'curved-corner-frame': { variant: 'frame', mask: 'curved-corner-frame' },
  'scalloped-frame': { variant: 'frame', mask: 'scalloped-frame' },
  'collage-frame': { variant: 'frame', mask: 'collage-frame' },
  'arch-shape': { variant: 'frame', mask: 'arch-shape' },
  'camera-frame': { variant: 'frame', mask: 'camera-frame' },
  'phone-frame': { variant: 'frame', mask: 'phone-frame' },
  'ribbon-frame': { variant: 'frame', mask: 'ribbon-frame' },
};

const DEFAULT_SHAPE_PLACEHOLDER_FILL = 'rgba(148, 163, 184, 0.16)';
const DEFAULT_SHAPE_PLACEHOLDER_STROKE = 'rgba(148, 163, 184, 0.28)';

const SHAPE_THUMB_FILL = '#374151';
const SHAPE_THUMB_STROKE = '#111827';
const SHAPE_THUMB_ACCENT = '#4b5563';
const SHAPE_DEFAULT_FILL = '#f8fafc';
const SHAPE_DEFAULT_STROKE = 'rgba(15, 23, 42, 0.18)';
const SHAPE_DEFAULT_SHADOW = '0 12px 24px rgba(15, 23, 42, 0.12)';
const SHAPE_DEFAULT_HIGHLIGHT = 'inset 0 1px 0 rgba(255, 255, 255, 0.55)';

function createSvgThumb(content) {
  const svg = `<svg width="64" height="64" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">${content}</svg>`;
  return `data:image/svg+xml,${encodeURIComponent(svg)}`;
}

function createRectThumb(rx = 0, ry = rx) {
  return createSvgThumb(`<rect x="8" y="8" width="48" height="48" rx="${rx}" ry="${ry}" fill="${SHAPE_THUMB_FILL}" stroke="${SHAPE_THUMB_STROKE}" stroke-width="2" />`);
}

function createCircleThumb(r = 24) {
  return createSvgThumb(`<circle cx="32" cy="32" r="${r}" fill="${SHAPE_THUMB_FILL}" stroke="${SHAPE_THUMB_STROKE}" stroke-width="2" />`);
}

function createEllipseThumb(rx = 24, ry = 18) {
  return createSvgThumb(`<ellipse cx="32" cy="32" rx="${rx}" ry="${ry}" fill="${SHAPE_THUMB_FILL}" stroke="${SHAPE_THUMB_STROKE}" stroke-width="2" />`);
}

function createPolygonThumb(points) {
  return createSvgThumb(`<polygon points="${points}" fill="${SHAPE_THUMB_FILL}" stroke="${SHAPE_THUMB_STROKE}" stroke-width="2" stroke-linejoin="round" />`);
}

function createPathThumb(d) {
  return createSvgThumb(`<path d="${d}" fill="${SHAPE_THUMB_FILL}" stroke="${SHAPE_THUMB_STROKE}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />`);
}

function createMultiThumb(content) {
  return createSvgThumb(content);
}

const DEFAULT_SHAPE_LIBRARY = [
  {
    id: 'rectangle',
    label: 'Rectangle',
    variant: 'rectangle',
    maskVariant: 'rectangle',
    thumb: createRectThumb(0),
    tags: ['rectangle', 'box', 'quad'],
  },
  {
    id: 'square',
    label: 'Square',
    variant: 'rectangle',
    maskVariant: 'rectangle',
    thumb: createRectThumb(0),
    tags: ['square', 'box', 'even'],
  },
  {
    id: 'circle',
    label: 'Circle',
    variant: 'circle',
    maskVariant: 'circle',
    thumb: createCircleThumb(24),
    tags: ['circle', 'round'],
  },
  {
    id: 'oval',
    label: 'Oval',
    variant: 'circle',
    maskVariant: 'circle',
    thumb: createEllipseThumb(24, 18),
    tags: ['oval', 'ellipse'],
  },
  {
    id: 'rounded-rectangle',
    label: 'Rounded Rectangle',
    variant: 'rectangle',
    maskVariant: 'rectangle',
    thumb: createRectThumb(12),
    borderRadius: 24,
    tags: ['rounded', 'rectangle', 'soft'],
  },
  {
    id: 'arch-shape',
    label: 'Rounded Arch Frame',
    variant: 'frame',
    maskVariant: 'arch-shape',
    thumb: createPathThumb('M20 58 V30 A12 12 0 0 1 44 30 V58 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      maskVariant: 'arch-shape',
      dropShadow: SHAPE_DEFAULT_SHADOW,
      highlightInset: SHAPE_DEFAULT_HIGHLIGHT,
      isImageFrame: true,
    },
    tags: ['arch', 'rounded', 'frame'],
  },
  {
    id: 'triangle',
    label: 'Triangle',
    variant: 'polygon',
    maskVariant: 'triangle',
    thumb: createPolygonThumb('32 10 10 54 54 54'),
    tags: ['triangle', 'polygon'],
  },
  {
    id: 'diamond',
    label: 'Diamond',
    variant: 'polygon',
    maskVariant: 'diamond',
    thumb: createPolygonThumb('32 8 56 32 32 56 8 32'),
    tags: ['diamond', 'rhombus'],
  },
  {
    id: 'hexagon',
    label: 'Hexagon',
    variant: 'polygon',
    maskVariant: 'hexagon',
    thumb: createPolygonThumb('20 10 44 10 56 32 44 54 20 54 8 32'),
    tags: ['hexagon', 'polygon'],
  },
  {
    id: 'octagon',
    label: 'Octagon',
    variant: 'polygon',
    maskVariant: 'octagon',
    thumb: createPolygonThumb('22 8 42 8 56 22 56 42 42 56 22 56 8 42 8 22'),
    tags: ['octagon', 'polygon'],
  },
  {
    id: 'pentagon',
    label: 'Pentagon',
    variant: 'polygon',
    maskVariant: 'pentagon',
    thumb: createPolygonThumb('32 8 56 26 46 56 18 56 8 26'),
    tags: ['pentagon', 'polygon'],
  },
  {
    id: 'parallelogram',
    label: 'Parallelogram',
    variant: 'polygon',
    maskVariant: 'parallelogram',
    thumb: createPolygonThumb('18 12 56 8 46 56 8 60'),
    tags: ['parallelogram', 'slanted', 'quadrilateral'],
  },
  {
    id: 'trapezoid',
    label: 'Trapezoid',
    variant: 'polygon',
    maskVariant: 'trapezoid',
    thumb: createPolygonThumb('16 12 48 12 56 52 8 52'),
    tags: ['trapezoid', 'quadrilateral'],
  },
  {
    id: 'rhombus',
    label: 'Rhombus',
    variant: 'polygon',
    maskVariant: 'rhombus',
    thumb: createPolygonThumb('32 8 56 32 32 56 8 32'),
    tags: ['rhombus', 'diamond'],
  },
  {
    id: 'heart',
    label: 'Heart',
    variant: 'organic',
    maskVariant: 'heart',
    thumb: createPathThumb('M32 50 L14 30 C6 20 12 8 24 12 C28 14 32 18 32 18 C32 18 36 14 40 12 C52 8 58 20 50 30 Z'),
    tags: ['heart', 'love'],
  },
  {
    id: 'cloud',
    label: 'Cloud',
    variant: 'organic',
    maskVariant: 'cloud-shape',
    thumb: createPathThumb('M18 38 C12 38 8 34 8 28 C8 22 12 18 18 18 C20 10 28 6 36 10 C40 6 46 6 50 10 C56 10 60 14 60 20 C60 26 56 30 52 30 H18 Z'),
    tags: ['cloud', 'organic'],
  },
  {
    id: 'star',
    label: 'Star',
    variant: 'polygon',
    maskVariant: 'star',
    thumb: createPolygonThumb('32 10 39 26 56 26 42 36 48 52 32 42 16 52 22 36 8 26 25 26'),
    tags: ['star', 'badge'],
  },
  {
    id: 'flower',
    label: 'Flower / Daisy Badge',
    variant: 'organic',
    maskVariant: 'flower',
    thumb: createPathThumb('M32 8 C36 8 40 11 42 16 C48 14 54 18 56 24 C58 30 56 36 52 38 C52 44 48 50 42 52 C40 56 36 58 32 58 C28 58 24 56 22 52 C16 50 12 44 12 38 C8 36 6 30 8 24 C10 18 16 14 22 16 C24 11 28 8 32 8 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      maskVariant: 'flower',
      dropShadow: SHAPE_DEFAULT_SHADOW,
      highlightInset: SHAPE_DEFAULT_HIGHLIGHT,
    },
    tags: ['flower', 'badge', 'organic'],
  },
  {
    id: 'badge',
    label: 'Badge Shape',
    variant: 'polygon',
    maskVariant: 'badge',
    thumb: createPolygonThumb('12 10 50 10 56 6 62 18 62 46 56 58 50 54 12 54 6 58 2 46 2 18 8 6'),
    tags: ['badge', 'emblem'],
  },
  {
    id: 'blob',
    label: 'Blob / Organic Shape',
    variant: 'organic',
    maskVariant: 'blob',
    thumb: createPathThumb('M16 18 C20 8 38 6 50 14 C60 22 60 38 48 50 C36 62 18 58 10 44 C6 34 8 24 16 18 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      maskVariant: 'blob',
      dropShadow: SHAPE_DEFAULT_SHADOW,
      highlightInset: SHAPE_DEFAULT_HIGHLIGHT,
    },
    tags: ['blob', 'organic'],
  },
  {
    id: 'wave',
    label: 'Wave / Curved Frame',
    variant: 'organic',
    maskVariant: 'wave',
    thumb: createPathThumb('M8 48 V22 C16 12 28 12 32 20 C40 32 52 32 56 22 V48 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      maskVariant: 'wave',
      dropShadow: SHAPE_DEFAULT_SHADOW,
      highlightInset: SHAPE_DEFAULT_HIGHLIGHT,
    },
    tags: ['wave', 'curved', 'frame'],
  },
  {
    id: 'polaroid',
    label: 'Polaroid Frame',
    variant: 'frame',
    maskVariant: 'polaroid-frame',
    thumb: createPathThumb('M12 10 H52 V44 H40 V56 H24 V44 H12 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      maskVariant: 'polaroid-frame',
      dropShadow: SHAPE_DEFAULT_SHADOW,
      highlightInset: SHAPE_DEFAULT_HIGHLIGHT,
      isImageFrame: true,
    },
    tags: ['polaroid', 'frame'],
  },
  {
    id: 'film-strip',
    label: 'Film Strip Frame',
    variant: 'frame',
    maskVariant: 'film-strip-frame',
    thumb: createPathThumb('M12 12 H52 V52 H12 Z M18 18 V46 M26 18 V46 M38 18 V46 M46 18 V46'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      maskVariant: 'film-strip-frame',
      dropShadow: SHAPE_DEFAULT_SHADOW,
      highlightInset: SHAPE_DEFAULT_HIGHLIGHT,
      isImageFrame: true,
    },
    tags: ['film', 'strip', 'frame'],
  },
  {
    id: 'torn-paper',
    label: 'Torn Paper Edge',
    variant: 'frame',
    maskVariant: 'torn-paper-frame',
    thumb: createPathThumb('M10 14 L18 8 L26 18 L36 10 L46 20 L56 12 L58 20 L58 50 L46 46 L36 54 L26 48 L18 54 L10 46 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      maskVariant: 'torn-paper-frame',
      dropShadow: SHAPE_DEFAULT_SHADOW,
      highlightInset: 'inset 0 2px 4px rgba(255, 255, 255, 0.35)',
    },
    tags: ['torn', 'paper', 'frame'],
  },
  {
    id: 'curved-corner',
    label: 'Curved Corner Frame',
    variant: 'frame',
    maskVariant: 'curved-corner-frame',
    thumb: createPathThumb('M16 12 Q12 12 12 20 V52 Q12 60 20 60 H44 Q52 60 52 52 V24 Q52 16 44 16 H32 Q24 16 24 12 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      maskVariant: 'curved-corner-frame',
      dropShadow: SHAPE_DEFAULT_SHADOW,
      highlightInset: SHAPE_DEFAULT_HIGHLIGHT,
    },
    tags: ['curved', 'corner', 'frame'],
  },
  {
    id: 'scalloped',
    label: 'Scalloped Edge Frame',
    variant: 'frame',
    maskVariant: 'scalloped-frame',
    thumb: createPathThumb('M16 12 C18 8 24 8 26 12 C28 16 30 16 32 12 C34 8 38 8 40 12 C42 16 46 16 48 12 C50 8 56 8 56 12 V52 C54 56 50 56 48 52 C46 48 42 48 40 52 C38 56 34 56 32 52 C30 48 28 48 26 52 C24 56 18 56 16 52 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      maskVariant: 'scalloped-frame',
      dropShadow: SHAPE_DEFAULT_SHADOW,
      highlightInset: SHAPE_DEFAULT_HIGHLIGHT,
    },
    tags: ['scalloped', 'frame'],
  },
  {
    id: 'collage',
    label: 'Photo Collage Frames',
    variant: 'frame',
    maskVariant: 'collage-frame',
    thumb: createMultiThumb(`<g stroke="${SHAPE_THUMB_STROKE}" stroke-width="2" fill="${SHAPE_THUMB_FILL}" stroke-linejoin="round"><rect x="10" y="12" width="18" height="18" rx="4" /><rect x="32" y="12" width="20" height="16" rx="4" /><rect x="20" y="34" width="28" height="18" rx="6" /></g>`),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      maskVariant: 'collage-frame',
      dropShadow: SHAPE_DEFAULT_SHADOW,
      highlightInset: SHAPE_DEFAULT_HIGHLIGHT,
      isImageFrame: true,
    },
    tags: ['collage', 'frame', 'grid'],
  },
  {
    id: 'camera',
    label: 'Camera Frame',
    variant: 'frame',
    maskVariant: 'camera-frame',
    thumb: createPathThumb('M16 22 H24 L28 16 H40 L44 22 H52 V50 H12 V22 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      maskVariant: 'camera-frame',
      dropShadow: SHAPE_DEFAULT_SHADOW,
      highlightInset: SHAPE_DEFAULT_HIGHLIGHT,
      isImageFrame: true,
    },
    tags: ['camera', 'frame'],
  },
  {
    id: 'phone',
    label: 'Phone Screen Frame',
    variant: 'frame',
    maskVariant: 'phone-frame',
    thumb: createPathThumb('M22 10 H42 C46 10 48 12 48 16 V50 C48 54 46 56 42 56 H22 C18 56 16 54 16 50 V16 C16 12 18 10 22 10 Z'),
    borderRadius: 18,
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      maskVariant: 'phone-frame',
      dropShadow: SHAPE_DEFAULT_SHADOW,
      highlightInset: SHAPE_DEFAULT_HIGHLIGHT,
      isImageFrame: true,
    },
    tags: ['phone', 'screen', 'frame'],
  },
  {
    id: 'sun',
    label: 'Sun',
    variant: 'organic',
    maskVariant: 'sun',
    thumb: createMultiThumb(`<circle cx="32" cy="32" r="16" fill="${SHAPE_THUMB_FILL}" stroke="${SHAPE_THUMB_STROKE}" stroke-width="2" /><g stroke="${SHAPE_THUMB_STROKE}" stroke-width="2" stroke-linecap="round"><line x1="32" y1="8" x2="32" y2="2" /><line x1="32" y1="62" x2="32" y2="56" /><line x1="8" y1="32" x2="2" y2="32" /><line x1="62" y1="32" x2="56" y2="32" /><line x1="14" y1="14" x2="8" y2="8" /><line x1="50" y1="50" x2="56" y2="56" /><line x1="14" y1="50" x2="8" y2="56" /><line x1="50" y1="14" x2="56" y2="8" /></g>`),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      maskVariant: 'sun',
      dropShadow: SHAPE_DEFAULT_SHADOW,
      highlightInset: SHAPE_DEFAULT_HIGHLIGHT,
    },
    tags: ['sun', 'solar'],
  },
  {
    id: 'moon',
    label: 'Moon',
    variant: 'organic',
    maskVariant: 'moon',
    thumb: createPathThumb('M44 10 A22 22 0 1 1 20 54 A16 16 0 1 0 44 10 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      maskVariant: 'moon',
      dropShadow: SHAPE_DEFAULT_SHADOW,
      highlightInset: SHAPE_DEFAULT_HIGHLIGHT,
    },
    tags: ['moon', 'night', 'crescent'],
  },
  {
    id: 'butterfly',
    label: 'Butterfly',
    variant: 'organic',
    maskVariant: 'butterfly',
    thumb: createPathThumb('M32 32 C38 24 44 12 54 16 C62 20 58 32 48 34 C58 40 60 52 48 52 C42 52 36 42 32 36 C28 42 22 52 16 52 C4 52 6 40 16 34 C6 32 2 20 10 16 C20 12 26 24 32 32 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      maskVariant: 'butterfly',
      dropShadow: SHAPE_DEFAULT_SHADOW,
      highlightInset: SHAPE_DEFAULT_HIGHLIGHT,
    },
    tags: ['butterfly', 'organic'],
  },
  {
    id: 'leaf',
    label: 'Leaf',
    variant: 'organic',
    maskVariant: 'leaf',
    thumb: createPathThumb('M32 8 C46 16 56 30 54 44 C52 58 40 60 28 56 C16 52 8 40 10 26 C12 12 22 8 32 8 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      maskVariant: 'leaf',
      dropShadow: SHAPE_DEFAULT_SHADOW,
      highlightInset: SHAPE_DEFAULT_HIGHLIGHT,
    },
    tags: ['leaf', 'nature'],
  },
  {
    id: 'gift-tag',
    label: 'Gift Tag',
    variant: 'tag',
    maskVariant: 'tag-shape',
    thumb: createPolygonThumb('12 12 44 12 58 32 44 52 12 52 6 42 6 22'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      maskVariant: 'tag-shape',
      dropShadow: SHAPE_DEFAULT_SHADOW,
      highlightInset: SHAPE_DEFAULT_HIGHLIGHT,
    },
    tags: ['gift', 'tag', 'label'],
  },
  {
    id: 'ribbon-frame',
    label: 'Ribbon Frame',
    variant: 'frame',
    maskVariant: 'ribbon-frame',
    thumb: createPathThumb('M10 22 L22 12 H42 L54 22 V46 H42 V54 H22 V46 H10 Z'),
    tags: ['ribbon', 'banner', 'frame'],
  },
];

const NATURAL_SHAPE_LIBRARY = [
  {
    id: 'natural-circle',
    label: 'Circle',
    variant: 'circle',
    maskVariant: 'circle',
    thumb: createCircleThumb(24),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['circle', 'round'],
  },
  {
    id: 'natural-ellipse',
    label: 'Ellipse',
    variant: 'circle',
    maskVariant: 'circle',
    thumb: createEllipseThumb(24, 18),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['ellipse', 'oval'],
  },
  {
    id: 'natural-square',
    label: 'Square',
    variant: 'rectangle',
    maskVariant: 'rectangle',
    thumb: createRectThumb(0),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['square', 'box', 'even'],
  },
  {
    id: 'natural-rectangle',
    label: 'Rectangle',
    variant: 'rectangle',
    maskVariant: 'rectangle',
    thumb: createRectThumb(0),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['rectangle', 'box', 'quad'],
  },
  {
    id: 'natural-rounded-rectangle',
    label: 'Rounded Rectangle',
    variant: 'rectangle',
    maskVariant: 'rectangle',
    thumb: createRectThumb(12),
    borderRadius: 24,
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['rounded', 'rectangle', 'soft'],
  },
  {
    id: 'natural-triangle-equilateral',
    label: 'Equilateral Triangle',
    variant: 'polygon',
    maskVariant: 'triangle-equilateral',
    thumb: createPolygonThumb('32 10 10 54 54 54'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['triangle', 'equilateral', 'polygon'],
  },
  {
    id: 'natural-triangle-right',
    label: 'Right Triangle',
    variant: 'polygon',
    maskVariant: 'triangle-right',
    thumb: createPolygonThumb('12 12 52 52 12 52'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['triangle', 'right', 'polygon'],
  },
  {
    id: 'natural-triangle-isosceles',
    label: 'Isosceles Triangle',
    variant: 'polygon',
    maskVariant: 'triangle-isosceles',
    thumb: createPolygonThumb('32 8 12 56 52 56'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['triangle', 'isosceles', 'polygon'],
  },
  {
    id: 'natural-triangle-scalene',
    label: 'Scalene Triangle',
    variant: 'polygon',
    maskVariant: 'triangle-scalene',
    thumb: createPolygonThumb('16 12 54 44 12 56'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['triangle', 'scalene', 'polygon'],
  },
  {
    id: 'natural-pentagon',
    label: 'Pentagon',
    variant: 'polygon',
    maskVariant: 'pentagon',
    thumb: createPolygonThumb('32 8 56 26 46 56 18 56 8 26'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['pentagon', 'polygon', '5-sided'],
  },
  {
    id: 'natural-hexagon',
    label: 'Hexagon',
    variant: 'polygon',
    maskVariant: 'hexagon',
    thumb: createPolygonThumb('20 10 44 10 56 32 44 54 20 54 8 32'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['hexagon', 'polygon', '6-sided'],
  },
  {
    id: 'natural-heptagon',
    label: 'Heptagon',
    variant: 'polygon',
    maskVariant: 'heptagon',
    thumb: createPolygonThumb('32 6 52 16 56 36 44 54 20 54 8 36 12 16'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['heptagon', 'polygon', '7-sided'],
  },
  {
    id: 'natural-octagon',
    label: 'Octagon',
    variant: 'polygon',
    maskVariant: 'octagon',
    thumb: createPolygonThumb('22 8 42 8 56 22 56 42 42 56 22 56 8 42 8 22'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['octagon', 'polygon', '8-sided'],
  },
  {
    id: 'natural-star-5',
    label: 'Star (5-pointed)',
    variant: 'polygon',
    maskVariant: 'star',
    thumb: createPolygonThumb('32 8 38 26 56 26 42 38 48 56 32 44 16 56 22 38 8 26 26 26'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['star', '5-pointed', 'polygon'],
  },
  {
    id: 'natural-star-6',
    label: 'Star (6-pointed)',
    variant: 'polygon',
    maskVariant: 'hexagram',
    thumb: createPolygonThumb('32 6 42 18 54 18 48 30 54 42 42 36 32 48 22 36 6 42 12 30 6 18 18 18'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['star', '6-pointed', 'hexagram', 'polygon'],
  },
  {
    id: 'natural-star-8',
    label: 'Star (8-pointed)',
    variant: 'polygon',
    maskVariant: 'octagram',
    thumb: createPolygonThumb('32 4 40 12 52 12 48 20 56 28 48 36 52 44 40 44 32 52 24 44 12 44 16 36 8 28 16 20 12 12 24 12'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['star', '8-pointed', 'octagram', 'polygon'],
  },
  {
    id: 'natural-heart',
    label: 'Heart',
    variant: 'organic',
    maskVariant: 'heart',
    thumb: createPathThumb('M16 28 C16 22 12 18 8 18 C4 18 0 22 0 28 C0 32 4 36 8 40 L16 46 L24 40 C28 36 32 32 32 28 C32 22 28 18 24 18 C20 18 16 22 16 28 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['heart', 'love', 'organic'],
  },
  {
    id: 'natural-diamond',
    label: 'Diamond (Rhombus)',
    variant: 'polygon',
    maskVariant: 'diamond',
    thumb: createPolygonThumb('32 8 56 32 32 56 8 32'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['diamond', 'rhombus', 'polygon'],
  },
  {
    id: 'natural-parallelogram',
    label: 'Parallelogram',
    variant: 'polygon',
    maskVariant: 'parallelogram',
    thumb: createPolygonThumb('16 12 48 12 56 52 8 52'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['parallelogram', 'polygon', 'slanted'],
  },
  {
    id: 'natural-trapezoid',
    label: 'Trapezoid',
    variant: 'polygon',
    maskVariant: 'trapezoid',
    thumb: createPolygonThumb('18 12 46 12 56 52 8 52'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['trapezoid', 'polygon', 'trapezium'],
  },
  {
    id: 'natural-crescent',
    label: 'Crescent',
    variant: 'organic',
    maskVariant: 'crescent',
    thumb: createPathThumb('M32 8 A24 24 0 1 1 8 32 A16 16 0 1 0 32 8 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['crescent', 'moon', 'organic'],
  },
  {
    id: 'natural-arrow-right',
    label: 'Arrow (right)',
    variant: 'polygon',
    maskVariant: 'arrow-right',
    thumb: createPolygonThumb('8 16 40 16 40 8 56 24 40 40 40 32 8 32'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['arrow', 'right', 'direction', 'polygon'],
  },
  {
    id: 'natural-arrow-left',
    label: 'Arrow (left)',
    variant: 'polygon',
    maskVariant: 'arrow-left',
    thumb: createPolygonThumb('48 16 16 16 16 8 0 24 16 40 16 32 48 32'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['arrow', 'left', 'direction', 'polygon'],
  },
  {
    id: 'natural-arrow-up',
    label: 'Arrow (up)',
    variant: 'polygon',
    maskVariant: 'arrow-up',
    thumb: createPolygonThumb('16 48 16 16 8 16 24 0 40 16 32 16 32 48'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['arrow', 'up', 'direction', 'polygon'],
  },
  {
    id: 'natural-arrow-down',
    label: 'Arrow (down)',
    variant: 'polygon',
    maskVariant: 'arrow-down',
    thumb: createPolygonThumb('16 8 16 40 8 40 24 56 40 40 32 40 32 8'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['arrow', 'down', 'direction', 'polygon'],
  },
  {
    id: 'natural-cross-plus',
    label: 'Cross (plus sign)',
    variant: 'polygon',
    maskVariant: 'cross-plus',
    thumb: createPolygonThumb('24 8 40 8 40 24 56 24 56 40 40 40 40 56 24 56 24 40 8 40 8 24 24 24'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['cross', 'plus', 'add', 'polygon'],
  },
  {
    id: 'natural-cross-x',
    label: 'Cross (X shape)',
    variant: 'polygon',
    maskVariant: 'cross-x',
    thumb: createPolygonThumb('16 12 24 20 12 28 20 36 16 44 24 36 32 44 40 36 44 28 32 20 40 12 32 20'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['cross', 'x', 'multiply', 'polygon'],
  },
  {
    id: 'natural-cloud',
    label: 'Cloud shape',
    variant: 'organic',
    maskVariant: 'cloud-shape',
    thumb: createPathThumb('M44 32 C44 24 38 18 30 18 C26 18 22 20 20 24 C18 20 14 18 10 18 C4 18 0 24 0 30 C0 36 4 40 10 40 L34 40 C40 40 44 36 44 32 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['cloud', 'weather', 'organic'],
  },
  {
    id: 'natural-speech-bubble-rounded',
    label: 'Speech bubble (rounded rectangle)',
    variant: 'organic',
    maskVariant: 'speech-bubble-rounded',
    thumb: createPathThumb('M8 16 L8 40 Q8 48 16 48 L40 48 Q48 48 48 40 L48 16 Q48 8 40 8 L32 8 L28 0 L24 8 L16 8 Q8 8 8 16 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['speech', 'bubble', 'chat', 'rounded', 'organic'],
  },
  {
    id: 'natural-speech-bubble-oval',
    label: 'Speech bubble (oval)',
    variant: 'organic',
    maskVariant: 'speech-bubble-oval',
    thumb: createPathThumb('M16 8 Q8 8 8 16 L8 32 Q8 40 16 40 L32 40 Q40 40 40 32 L40 16 Q40 8 32 8 L24 8 L28 0 L20 0 L24 8 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['speech', 'bubble', 'chat', 'oval', 'organic'],
  },
  {
    id: 'natural-polygon-custom',
    label: 'Polygon (custom n-sides)',
    variant: 'polygon',
    maskVariant: 'polygon-custom',
    thumb: createPolygonThumb('32 4 56 16 52 44 32 56 12 44 8 16'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['polygon', 'custom', 'n-sides', 'variable'],
  },
  {
    id: 'natural-spiral',
    label: 'Spiral',
    variant: 'organic',
    maskVariant: 'spiral',
    thumb: createPathThumb('M32 32 Q40 32 40 24 Q40 16 48 16 Q56 16 56 24 Q56 32 48 32 Q40 32 40 40 Q40 48 32 48 Q24 48 24 40 Q24 32 32 32 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['spiral', 'coil', 'organic'],
  },
  {
    id: 'natural-wave',
    label: 'Wave',
    variant: 'organic',
    maskVariant: 'wave',
    thumb: createPathThumb('M0 32 Q16 16 32 32 T64 32 L64 64 L0 64 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['wave', 'sine', 'organic'],
  },
  {
    id: 'natural-chevron',
    label: 'Chevron',
    variant: 'polygon',
    maskVariant: 'chevron',
    thumb: createPolygonThumb('8 16 24 8 40 16 56 8 56 24 40 32 24 24 8 32 8 16'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['chevron', 'zigzag', 'polygon'],
  },
  {
    id: 'natural-hexagram',
    label: 'Hexagram (Star of David shape)',
    variant: 'polygon',
    maskVariant: 'hexagram',
    thumb: createPolygonThumb('32 6 42 18 54 18 48 30 54 42 42 36 32 48 22 36 6 42 12 30 6 18 18 18'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['hexagram', 'star of david', 'jewish', 'polygon'],
  },
  {
    id: 'natural-kite',
    label: 'Kite shape',
    variant: 'polygon',
    maskVariant: 'kite',
    thumb: createPolygonThumb('32 8 48 32 32 56 16 32'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['kite', 'diamond', 'polygon'],
  },
  {
    id: 'natural-teardrop',
    label: 'Teardrop',
    variant: 'organic',
    maskVariant: 'teardrop',
    thumb: createPathThumb('M32 8 Q48 8 48 24 Q48 40 32 56 Q16 40 16 24 Q16 8 32 8 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['teardrop', 'drop', 'organic'],
  },
  {
    id: 'natural-lightning',
    label: 'Lightning bolt',
    variant: 'polygon',
    maskVariant: 'lightning',
    thumb: createPolygonThumb('16 8 24 8 20 20 32 20 16 40 24 32 12 32 20 20 8 20'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['lightning', 'bolt', 'thunder', 'polygon'],
  },
  {
    id: 'natural-banner-ribbon',
    label: 'Banner/Ribbon shape',
    variant: 'organic',
    maskVariant: 'ribbon-banner',
    thumb: createPolygonThumb('8 16 24 8 40 16 56 24 40 32 24 24 8 24'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['banner', 'ribbon', 'organic'],
  },
  {
    id: 'natural-gear',
    label: 'Gear shape',
    variant: 'organic',
    maskVariant: 'gear',
    thumb: createPathThumb('M32 4 L36 12 L44 8 L40 16 L48 20 L40 24 L44 32 L36 28 L32 36 L28 28 L20 32 L24 24 L16 20 L24 16 L20 8 L28 12 Z'),
    fill: SHAPE_DEFAULT_FILL,
    stroke: SHAPE_DEFAULT_STROKE,
    metadata: {
      isImageFrame: false,
      allowImageFill: false,
      allowColorFill: true,
    },
    tags: ['gear', 'cog', 'mechanical', 'organic'],
  },
];

function buildShapeResult(preset) {
  const base = {
    id: `default-shape-${preset.id}`,
    thumbUrl: preset.thumb,
    previewUrl: preset.thumb,
    downloadUrl: preset.thumb,
    description: preset.label,
    provider: 'default',
    providerLabel: '',
    credit: '',
    variant: preset.variant,
    maskVariant: preset.maskVariant,
    placeholderFill: DEFAULT_SHAPE_PLACEHOLDER_FILL,
    placeholderStroke: DEFAULT_SHAPE_PLACEHOLDER_STROKE,
  };

  if (preset.fill !== undefined) {
    base.fill = preset.fill;
  }

  if (preset.stroke !== undefined) {
    base.stroke = preset.stroke;
  }

  if (preset.borderRadius !== undefined) {
    base.borderRadius = preset.borderRadius;
  }

  if (preset.metadata) {
    base.metadata = { ...preset.metadata };
  }

  if (preset.tags) {
    base.tags = [...preset.tags];
  }

  return base;
}

function getDefaultShapeResults() {
  return DEFAULT_SHAPE_LIBRARY.map((preset) => buildShapeResult(preset));
}

function getNaturalShapeResults() {
  return NATURAL_SHAPE_LIBRARY.map((preset) => buildShapeResult(preset));
}

function searchDefaultShapes(query, limit = DEFAULT_SHAPE_LIBRARY.length) {
  const normalized = (query || '').trim().toLowerCase();
  if (!normalized) {
    return getDefaultShapeResults().slice(0, limit);
  }

  const matches = DEFAULT_SHAPE_LIBRARY.filter((preset) => {
    const haystack = [preset.label, ...(preset.tags || [])]
      .filter(Boolean)
      .map((value) => value.toLowerCase());
    return haystack.some((value) => value.includes(normalized));
  });

  const selected = matches.length > 0 ? matches : DEFAULT_SHAPE_LIBRARY;
  return selected.slice(0, limit).map((preset) => buildShapeResult(preset));
}
const PHOTO_FILTERS = [
  { id: 'all', label: 'All images', value: null },
  { id: 'photos', label: 'Photos', value: 'photos' },
  { id: 'illustrations', label: 'Illustrations', value: 'illustrations' },
  { id: 'vectors', label: 'Vectors', value: 'vectors' },
  { id: '3d', label: '3D Models', value: '3d' },
  { id: 'gifs', label: 'GIFs', value: 'gifs' },
];

const PHOTO_PROVIDERS = [
  { id: 'unsplash', label: 'Unsplash' },
  { id: 'pixabay', label: 'Pixabay' },
];

const PROVIDER_LABELS = {
  unsplash: 'Unsplash',
  pixabay: 'Pixabay',
};

const PIXABAY_API_KEY = '53250708-d3f88461e75cb0c2c5366a181';

const FLATICON_API_KEY = 'FPSX2c99579cb6ea5314189561ca375a1648';

const ITEMS_PER_PAGE = {
  unsplash: 20,
  pixabay: 30,
  flaticon: 50,
};

const PIXABAY_FILTER_RULES = {
  all: { imageType: 'all' },
  photos: { imageType: 'photo' },
  illustrations: { imageType: 'illustration' },
  vectors: { imageType: 'vector' },
  '3d': { querySuffix: '3d model render', category: 'computer' },
  gifs: { querySuffix: 'animated gif loop', order: 'popular' },
};

const normalizeUnsplashResults = (results = []) => (
  results
    .filter(Boolean)
    .map((photo) => ({
      id: `unsplash-${photo.id}`,
      thumbUrl: photo?.urls?.thumb ?? photo?.urls?.small ?? photo?.urls?.regular,
      previewUrl: photo?.urls?.small ?? photo?.urls?.regular ?? photo?.urls?.full,
      downloadUrl: photo?.urls?.regular ?? photo?.urls?.full ?? photo?.urls?.small,
      description: photo?.alt_description ?? photo?.description ?? 'Unsplash photo',
      provider: 'unsplash',
      providerLabel: PROVIDER_LABELS.unsplash,
      credit: photo?.user?.name ? `Photo by ${photo.user.name} on Unsplash` : 'Unsplash',
      raw: photo,
    }))
);

const normalizePixabayResults = (hits = []) => (
  hits
    .filter(Boolean)
    .map((hit) => ({
      id: `pixabay-${hit.id}`,
      thumbUrl: hit?.previewURL ?? hit?.webformatURL,
      previewUrl: hit?.webformatURL ?? hit?.largeImageURL ?? hit?.previewURL,
      downloadUrl: hit?.largeImageURL ?? hit?.webformatURL ?? hit?.previewURL,
      description: hit?.tags ?? 'Pixabay image',
      provider: 'pixabay',
      providerLabel: PROVIDER_LABELS.pixabay,
      credit: hit?.user ? `${hit.user} on Pixabay` : 'Pixabay',
      raw: hit,
    }))
);

export function ToolSidebar({ isSidebarHidden, onToggleSidebar }) {
  const [activeTool, setActiveTool] = useState('layers');
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState([]);
  const [isSearching, setIsSearching] = useState(false);
  const [currentPage, setCurrentPage] = useState(1);
  const [isLoadingMore, setIsLoadingMore] = useState(false);
  const [hasMore, setHasMore] = useState(true);
  const [selectedFilter, setSelectedFilter] = useState('all');
  // Photos: always search both Unsplash and Pixabay together (no provider toggle needed)
  const photoProvider = 'combined';
  const currentPageRef = useRef(1);
  const activeSearchQueryRef = useRef('');
  const hasTriggeredSearchRef = useRef(false);

  // Color palette state
  const [currentPaletteSets, setCurrentPaletteSets] = useState([]);
  const [isGeneratingPalette, setIsGeneratingPalette] = useState(false);
  const [paletteError, setPaletteError] = useState('');
  const [paletteQuery, setPaletteQuery] = useState('');
  const [paletteStatus, setPaletteStatus] = useState('');
  const [isLoadingMorePalettes, setIsLoadingMorePalettes] = useState(false);
  const paletteListRef = useRef(null);
  const curatedPaletteMatchesRef = useRef([]);
  const curatedPaletteCursorRef = useRef(0);
  const [activeGradientTheme, setActiveGradientTheme] = useState('birthday');
  const [backgroundColorInput, setBackgroundColorInput] = useState('#ffffff');
  const [isColorModalOpen, setIsColorModalOpen] = useState(false);
  const [colorPickerTab, setColorPickerTab] = useState('gradient');
  const [solidPickerColor, setSolidPickerColor] = useState('#ffffff');
  const [solidHexDraft, setSolidHexDraft] = useState('#ffffff');
  const [gradientColorStops, setGradientColorStops] = useState(['#C97A8C', '#A855F7']);
  const [gradientInputs, setGradientInputs] = useState(['#C97A8C', '#A855F7']);
  const [selectedGradientStyle, setSelectedGradientStyle] = useState('linear');
  const [gradientAngle, setGradientAngle] = useState(120);
  const [activeGradientStop, setActiveGradientStop] = useState(0);
  const [paletteContext, setPaletteContext] = useState('layers');
  const [hueSliderValue, setHueSliderValue] = useState(0);
  const [backgroundOpacity, setBackgroundOpacity] = useState(100);
  const colorModalRef = useRef(null);

  // Color picker and history state
  const [colorHistory, setColorHistory] = useState(['#FFFFFF', '#000000', '#EF4444', '#22C55E', '#3B82F6']);

  const rememberColor = useCallback((color) => {
    if (typeof color !== 'string') {
      return;
    }
    const match = color.trim().match(/^#([0-9a-f]{6})$/i);
    if (!match) {
      return;
    }
    const normalized = `#${match[1].toUpperCase()}`;
    setColorHistory((prev) => {
      const filtered = prev.filter((entry) => entry !== normalized);
      return [normalized, ...filtered].slice(0, 8);
    });
  }, []);
  const CURATED_PALETTE_LIBRARY = useMemo(() => ([
    {
      id: 'birthday-pop',
      label: 'Birthday Pop',
      keywords: ['birthday', 'party', 'celebration', 'fun', 'kids'],
      colors: ['#FDE68A', '#F97316', '#EA580C', '#B45309', '#78350F'],
    },
    {
      id: 'corporate-cool',
      label: 'Corporate Cool',
      keywords: ['corporate', 'business', 'office', 'modern', 'tech'],
      colors: ['#E0E7FF', '#6366F1', '#312E81', '#0F172A', '#8B5CF6'],
    },
    {
      id: 'wedding-rose',
      label: 'Wedding Rose',
      keywords: ['wedding', 'romantic', 'rose', 'love', 'pastel', 'pink'],
      colors: ['#FDECF3', '#FBCFE8', '#F472B6', '#DB2777', '#831843'],
    },
    {
      id: 'baptism-serene',
      label: 'Baptism Serene',
      keywords: ['baptism', 'faith', 'calm', 'serene', 'baby'],
      colors: ['#E0F2FE', '#CFFAFE', '#A5F3FC', '#67E8F9', '#22D3EE'],
    },
    {
      id: 'organic-fern',
      label: 'Organic Fern',
      keywords: ['organic', 'earth', 'nature', 'green', 'eco'],
      colors: ['#F0FDF4', '#A7F3D0', '#34D399', '#10B981', '#065F46'],
    },
    {
      id: 'citrus-splash',
      label: 'Citrus Splash',
      keywords: ['citrus', 'summer', 'tropical', 'bright', 'sunny'],
      colors: ['#FFF7ED', '#FDE68A', '#FCD34D', '#F59E0B', '#B45309'],
    },
    {
      id: 'tropical-punch',
      label: 'Tropical Punch',
      keywords: ['tropical', 'beach', 'island', 'vacation', 'sunset'],
      colors: ['#FFEDD5', '#FDBA74', '#FB7185', '#F472B6', '#C084FC'],
    },
    {
      id: 'pastel-dream',
      label: 'Pastel Dream',
      keywords: ['pastel', 'soft', 'gentle', 'dreamy', 'baby', 'pink'],
      colors: ['#F3E8FF', '#FDE68A', '#C7D2FE', '#BFDBFE', '#FBCFE8'],
    },
    {
      id: 'luxe-midnight',
      label: 'Luxe Midnight',
      keywords: ['luxury', 'midnight', 'event', 'formal', 'black tie'],
      colors: ['#0F172A', '#1E293B', '#475569', '#64748B', '#FACC15'],
    },
    {
      id: 'neon-future',
      label: 'Neon Future',
      keywords: ['neon', 'retro', 'future', 'nightlife', 'electric'],
      colors: ['#0F172A', '#0EA5E9', '#22D3EE', '#F472B6', '#F97316'],
    },
    {
      id: 'pink-blush',
      label: 'Pink Blush',
      keywords: ['pink', 'blush', 'soft', 'romantic', 'pastel'],
      colors: ['#FCE7F3', '#FBCFE8', '#F472B6', '#DB2777', '#831843'],
    },
    {
      id: 'hot-pink',
      label: 'Hot Pink',
      keywords: ['pink', 'hot', 'bright', 'vibrant', 'energetic'],
      colors: ['#FCE7F3', '#F472B6', '#EC4899', '#BE185D', '#831843'],
    },
    {
      id: 'dusty-pink',
      label: 'Dusty Pink',
      keywords: ['pink', 'dusty', 'muted', 'soft', 'vintage'],
      colors: ['#FDF2F8', '#FCE7F3', '#FBCFE8', '#F9A8D4', '#F472B6'],
    },
    {
      id: 'neon-pink',
      label: 'Neon Pink',
      keywords: ['pink', 'neon', 'bright', 'electric', 'modern'],
      colors: ['#FCE7F3', '#F472B6', '#EC4899', '#BE185D', '#9D174D'],
    },
    {
      id: 'pink-sunset',
      label: 'Pink Sunset',
      keywords: ['pink', 'sunset', 'warm', 'evening', 'romantic'],
      colors: ['#FDE2E2', '#FCA5A5', '#F87171', '#EF4444', '#DC2626'],
    },
  ]), []);
  const LINEAR_GRADIENT_LIBRARY = useMemo(() => ([]), []);
  const QUICK_PICK_SWATCHES = useMemo(() => (
    ['#ffffff', '#000000', '#1f2937', '#8f384b', '#c97a8c', '#f9c5d1']
  ), []);
  const DEFAULT_GRADIENTS = useMemo(() => ([
    'linear-gradient(90deg, #FF73B9, #FFE66D)',
    'linear-gradient(90deg, #6ECFFF, #D2B7FF)',
    'linear-gradient(90deg, #A6F6C1, #FFB89A)',
    'linear-gradient(90deg, #FF4FA7, #9C5CFF)',
    'linear-gradient(90deg, #FF9C42, #FF6FAE)',
    'linear-gradient(90deg, #4BB3FF, #A7FF57)',
    'linear-gradient(90deg, #FF7A6E, #FFF07A)',
    'linear-gradient(90deg, #54F3DA, #FF7EC7)',
    'linear-gradient(90deg, #A46BFF, #6CFCFF)',
    'linear-gradient(90deg, #FFB5E8, #BDE0FE)',
    'linear-gradient(90deg, #0A1A3F, #1E4FFF)',
    'linear-gradient(90deg, #232323, #C8C8C8)',
    'linear-gradient(90deg, #0F6D72, #7C9AAF)',
    'linear-gradient(90deg, #002B55, #6EB8FF)',
    'linear-gradient(90deg, #000000, #5A5A5A)',
    'linear-gradient(90deg, #054B41, #7CE7E0)',
    'linear-gradient(90deg, #485767, #5C8BC6)',
    'linear-gradient(90deg, #1B1F5F, #ADB5C1)',
    'linear-gradient(90deg, #0C112B, #64748B)',
    'linear-gradient(90deg, #4C5C68, #FFFFFF)',
    'linear-gradient(90deg, #A3D8FF, #FFFFFF)',
    'linear-gradient(90deg, #F9C6D3, #FFF8E7)',
    'linear-gradient(90deg, #C6F7DF, #FFFFFF)',
    'linear-gradient(90deg, #D8C9FF, #CAE9FF)',
    'linear-gradient(90deg, #F3E3C9, #FFFDF4)',
    'linear-gradient(90deg, #FFEEA9, #FFD6C5)',
    'linear-gradient(90deg, #C8FAF4, #B3E5FF)',
    'linear-gradient(90deg, #E5D4FF, #FFFFFF)',
    'linear-gradient(90deg, #C6E8FF, #C9FDE6)',
    'linear-gradient(90deg, #FFD5E3, #EBD6FF)',
    'linear-gradient(90deg, #F9E7CF, #FFFDF4)',
    'linear-gradient(90deg, #E8B7A5, #FAD6D6)',
    'linear-gradient(90deg, #EAC56C, #FFFFFF)',
    'linear-gradient(90deg, #FFF1DD, #FFD7E5)',
    'linear-gradient(90deg, #8CAAC8, #DDE3EA)',
    'linear-gradient(90deg, #C9A7B8, #F5CCD6)',
    'linear-gradient(90deg, #FFFFFF, #F6E9C7)',
    'linear-gradient(90deg, #FFFBE7, #E9DCC3)',
    'linear-gradient(90deg, #FFD9C4, #F8CDB8)',
    'linear-gradient(90deg, #D5DCE3, #FFFFFF)',
  ]), []);
  const DEFAULT_SOLID_COLORS = useMemo(() => ([
    '#FFFFFF', '#000000', '#F8F9FA', '#E9ECEF', '#DEE2E6',
    '#CED4DA', '#ADB5BD', '#6C757D', '#495057', '#343A40',
    '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
    '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9',
    '#F8C471', '#82E0AA', '#F1948A', '#85C1E9', '#D7BDE2',
    '#AED6F1', '#A3E4D7', '#F9E79F', '#D2B4DE', '#A9DFBF',
    '#FAD7A0', '#ABEBC6', '#F5B7B1', '#AED6F1', '#D7BDE2',
    '#A9CCE3', '#A2D9CE', '#F7DC6F', '#C39BD3', '#7DCEA0',
    '#F8C471', '#58D68D', '#EC7063', '#5DADE2', '#AF7AC5'
  ]), []);
  const DEFAULT_QUOTES = useMemo(() => ([
    "Celebrate this moment with us—your presence makes it complete.",
    "Join us as we turn today into a memory we'll treasure forever.",
    "A special day becomes sweeter when shared with the people we love.",
    "Let's gather, celebrate, and create moments that last a lifetime.",
    "Because every joy is greater when shared—come celebrate with us.",
    "Together is the best place to be—join us on our special day.",
    "Your presence is our greatest gift—come and celebrate this milestone.",
    "A day of love, joy, and gratitude—made brighter with you there.",
    "Celebrate with us as we mark a new chapter filled with hope and happiness.",
    "You are warmly invited to witness a moment we will cherish forever."
  ]), []);
  const gradientStyleOptions = useMemo(() => ([
    {
      id: 'linear',
      label: 'Linear',
      description: 'Directional blend',
      icon: 'fa-solid fa-arrows-left-right',
      supportsAngle: true,
      generator: (stops, angle = 135) => `linear-gradient(${angle}deg, ${stops.join(', ')})`,
    },
    {
      id: 'radial',
      label: 'Radial',
      description: 'Soft center glow',
      icon: 'fa-solid fa-circle-dot',
      supportsAngle: false,
      generator: (stops) => `radial-gradient(circle at center, ${stops.join(', ')})`,
    },
    {
      id: 'conic',
      label: 'Angle',
      description: 'Sweeping burst',
      icon: 'fa-solid fa-rotate',
      supportsAngle: true,
      generator: (stops, angle = 0) => `conic-gradient(from ${angle}deg, ${stops.join(', ')})`,
    },
  ]), []);
  const normalizeColorForInput = useCallback((value) => {
    if (typeof value !== 'string') {
      return '#ffffff';
    }
    const trimmed = value.trim();
    if (trimmed.startsWith('linear-gradient')) {
      return '#ffffff';
    }
    if (/^#([0-9a-f]{3,4})$/i.test(trimmed)) {
      const hex = trimmed.slice(1);
      const expanded = hex.split('').map((ch) => ch + ch).join('').slice(0, 6);
      return `#${expanded}`;
    }
    if (/^#([0-9a-f]{6})([0-9a-f]{2})?$/i.test(trimmed)) {
      return trimmed.slice(0, 7);
    }
    return '#ffffff';
  }, []);
  const buildGradientValue = useCallback((styleId, stops, angle = 120, opacity = 1) => {
    const palette = (Array.isArray(stops) && stops.length ? stops : ['#f472b6', '#9333ea']).map((stop) => {
      const normalized = normalizeColorForInput(stop);
      const alpha = clamp(opacity, 0, 1);
      return alpha < 1 ? `rgba(${hexToRgb(normalized)}, ${alpha})` : normalized;
    });
    const fallback = gradientStyleOptions[0];
    const target = gradientStyleOptions.find((option) => option.id === styleId) ?? fallback;
    return target.generator(palette, angle);
  }, [gradientStyleOptions, normalizeColorForInput]);
  const gradientPreviewValue = useMemo(
    () => buildGradientValue(selectedGradientStyle, gradientColorStops, gradientAngle, backgroundOpacity / 100),
    [backgroundOpacity, buildGradientValue, gradientAngle, gradientColorStops, selectedGradientStyle],
  );
  const selectedGradientDefinition = useMemo(
    () => gradientStyleOptions.find((option) => option.id === selectedGradientStyle) ?? gradientStyleOptions[0],
    [gradientStyleOptions, selectedGradientStyle],
  );
  const inlineShadeBackground = useMemo(
    () => `linear-gradient(0deg, rgba(0, 0, 0, 1), transparent), linear-gradient(90deg, rgba(${hexToRgb(solidPickerColor)}, ${backgroundOpacity / 100}), ${solidPickerColor})`,
    [solidPickerColor, backgroundOpacity],
  );
  const PALETTES_PER_BATCH = 5;

  const getCuratedPalettesByQuery = useCallback((query) => {
    const normalized = query?.trim().toLowerCase();
    if (!normalized) {
      return [];
    }

    return CURATED_PALETTE_LIBRARY
      .filter((entry) => {
        const labelMatch = entry.label.toLowerCase().includes(normalized);
        const keywordMatch = entry.keywords?.some((keyword) => keyword.includes(normalized));
        return labelMatch || keywordMatch;
      })
      .map((entry) => ({
        id: `curated-${entry.id}`,
        label: entry.label,
        colors: [...entry.colors],
        source: 'curated',
      }));
  }, [CURATED_PALETTE_LIBRARY]);

  const resetCuratedPaletteQueue = useCallback((normalizedQuery) => {
    curatedPaletteMatchesRef.current = normalizedQuery ? getCuratedPalettesByQuery(normalizedQuery) : [];
    curatedPaletteCursorRef.current = 0;
  }, [getCuratedPalettesByQuery]);

  const takeCuratedPalettes = useCallback((count) => {
    if (!curatedPaletteMatchesRef.current?.length) {
      return [];
    }
    const start = curatedPaletteCursorRef.current;
    const slice = curatedPaletteMatchesRef.current.slice(start, start + count);
    curatedPaletteCursorRef.current = start + slice.length;
    return slice;
  }, []);

  // Prevent scroll propagation from palette results to parent containers
  useEffect(() => {
    const paletteContainer = paletteListRef.current || document.querySelector('.builder-sidebar__palette-scroll');
    if (!paletteContainer) return;

    const preventScrollPropagation = (e) => {
      const { scrollTop, scrollHeight, clientHeight } = e.target;
      const isAtTop = scrollTop === 0;
      const isAtBottom = scrollTop + clientHeight >= scrollHeight;

      if ((isAtTop && e.deltaY < 0) || (isAtBottom && e.deltaY > 0)) {
        return;
      }

      e.stopPropagation();
    };

    paletteContainer.addEventListener('wheel', preventScrollPropagation, { passive: true });
    paletteContainer.addEventListener('touchmove', preventScrollPropagation, { passive: true });

    return () => {
      paletteContainer.removeEventListener('wheel', preventScrollPropagation);
      paletteContainer.removeEventListener('touchmove', preventScrollPropagation);
    };
  }, []);

  const pickRandomCuratedPalette = useCallback(() => {
    if (!CURATED_PALETTE_LIBRARY?.length) {
      return null;
    }
    const randomIndex = Math.floor(Math.random() * CURATED_PALETTE_LIBRARY.length);
    const entry = CURATED_PALETTE_LIBRARY[randomIndex];
    return entry
      ? {
          id: `curated-${entry.id}-${Math.random().toString(36).slice(2, 7)}`,
          label: entry.label,
          colors: [...entry.colors],
          source: 'curated',
        }
      : null;
  }, [CURATED_PALETTE_LIBRARY]);

  const fetchColormindPalette = useCallback(async (endpoint) => {
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ model: 'default' }),
    });

    if (!response.ok) {
      throw new Error(`Colormind responded with ${response.status}`);
    }

    const data = await response.json();
    if (!data?.result || !Array.isArray(data.result)) {
      throw new Error('Colormind response missing result array');
    }

    return data.result.map((rgb) => {
      if (!Array.isArray(rgb) || rgb.length !== 3) {
        return '#000000';
      }
      const [r, g, b] = rgb.map((v) => Math.max(0, Math.min(255, Number(v) || 0)));
      return `rgb(${r}, ${g}, ${b})`;
    });
  }, []);

  const fetchPaletteBatch = useCallback(async ({ normalizedQuery, startIndex = 0 }) => {
    const endpoints = [
      'https://colormind.io/api/',
      'https://corsproxy.io/?https://colormind.io/api/',
    ];

    const paletteSets = [];
    let usedFallback = false;

    const curatedSlice = takeCuratedPalettes(PALETTES_PER_BATCH);
    if (curatedSlice.length) {
      paletteSets.push(...curatedSlice);
    }

    while (paletteSets.length < PALETTES_PER_BATCH) {
      let palette = null;
      for (let i = 0; i < endpoints.length; i++) {
        try {
          palette = await fetchColormindPalette(endpoints[i]);
          if (palette && palette.length) {
            break;
          }
        } catch (err) {
          if (i === endpoints.length - 1) {
            console.warn('Colormind endpoint failed', err);
          }
        }
      }

      if (!palette || !palette.length) {
        usedFallback = true;
        const fallback = pickRandomCuratedPalette();
        paletteSets.push({
          id: fallback?.id ?? `fallback-${startIndex + paletteSets.length}`,
          label: fallback?.label ?? 'Designer Fallback',
          colors: fallback?.colors ?? ['#e2e8f0', '#cbd5f5', '#a5b4fc', '#818cf8', '#4c1d95'],
          source: 'curated',
        });
        continue;
      }

      const paletteNumber = startIndex + paletteSets.length;
      paletteSets.push({
        id: `colormind-${Date.now()}-${paletteNumber}-${Math.random().toString(36).slice(2, 6)}`,
        label: normalizedQuery ? `AI Blend ${paletteNumber + 1}` : `Colormind ${paletteNumber + 1}`,
        colors: palette,
        source: 'colormind',
      });
    }

    if (!paletteSets.length) {
      const fallback = pickRandomCuratedPalette();
      paletteSets.push({
        id: fallback?.id ?? `fallback-${Math.random().toString(36).slice(2, 6)}`,
        label: fallback?.label ?? 'Designer Fallback',
        colors: fallback?.colors ?? ['#e2e8f0', '#cbd5f5', '#a5b4fc', '#818cf8', '#4c1d95'],
        source: 'curated',
      });
    }

    return { paletteSets, usedFallback };
  }, [fetchColormindPalette, pickRandomCuratedPalette, takeCuratedPalettes]);

  // Icon search state
  const [iconSearchQuery, setIconSearchQuery] = useState('');
  const [iconSearchResults, setIconSearchResults] = useState([]);
  const [isSearchingIcons, setIsSearchingIcons] = useState(false);
  const [iconCurrentPage, setIconCurrentPage] = useState(1);
  const [isLoadingMoreIcons, setIsLoadingMoreIcons] = useState(false);
  const [hasMoreIcons, setHasMoreIcons] = useState(true);
  const iconCurrentPageRef = useRef(1);
  const activeIconSearchQueryRef = useRef('');
  const hasTriggeredIconSearchRef = useRef(false);
  const [fontSearchResults, setFontSearchResults] = useState([]);
  const [fontSearchQuery, setFontSearchQuery] = useState('');
  const [isSearchingFonts, setIsSearchingFonts] = useState(false);
  const [fontCurrentPage, setFontCurrentPage] = useState(1);
  const [isLoadingMoreFonts, setIsLoadingMoreFonts] = useState(false);
  const [hasMoreFonts, setHasMoreFonts] = useState(true);
  const fontCurrentPageRef = useRef(1);
  const activeFontSearchQueryRef = useRef('');
  const hasTriggeredFontSearchRef = useRef(false);

  // Quote search state
  const [quoteSearchQuery, setQuoteSearchQuery] = useState('');
  const [quoteSearchResults, setQuoteSearchResults] = useState([]);
  const [isSearchingQuotes, setIsSearchingQuotes] = useState(false);
  const [quoteCurrentPage, setQuoteCurrentPage] = useState(1);
  const [isLoadingMoreQuotes, setIsLoadingMoreQuotes] = useState(false);
  const [hasMoreQuotes, setHasMoreQuotes] = useState(true);
  const quoteCurrentPageRef = useRef(1);
  const activeQuoteSearchQueryRef = useRef('');
  const hasTriggeredQuoteSearchRef = useRef(false);

  // Styled text presets (loaded from Google Fonts list + sample text)
  const [styledPresets, setStyledPresets] = useState([]);
  const [styledPage, setStyledPage] = useState(1);
  const [isLoadingStyledPresets, setIsLoadingStyledPresets] = useState(false);
  const [hasMoreStyledPresets, setHasMoreStyledPresets] = useState(true);
  const styledPerPage = 12;
  const styledContainerRef = useRef(null);
  const styledObserverRef = useRef(null);

  // Use the provided Google API key (from your message)
  const GOOGLE_FONTS_API_KEY = 'AIzaSyBRCDdZjTcR4brOsHV_OBsDO11We11BVi0';

  const curatedFontCombos = [
    {
      id: 'combo-aurora-atelier',
      label: 'Aurora Atelier',
      description: 'Playfair Display headline with Source Sans Pro narrative subcopy.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Aurora Atelier',
          family: 'Playfair Display',
          fallback: 'serif',
          fontSize: 44,
          fontWeight: '600',
          transform: 'uppercase',
          letterSpacing: 2,
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Visual storytelling & editorial craft',
          family: 'Source Sans Pro',
          fallback: 'sans-serif',
          fontSize: 18,
          fontWeight: '400',
          letterSpacing: 0.6,
          offsetY: 58,
        },
      ],
    },
    {
      id: 'combo-lumina-forge',
      label: 'Lumina Forge',
      description: 'DM Serif Display hero matched with crisp Inter labels.',
      align: 'left',
      layers: [
        {
          role: 'heading',
          content: 'Lumina Forge',
          family: 'DM Serif Display',
          fallback: 'serif',
          fontSize: 42,
          fontWeight: '600',
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Product strategy & venture labs',
          family: 'Inter',
          fallback: 'sans-serif',
          fontSize: 17,
          fontWeight: '500',
          letterSpacing: 0.5,
          offsetY: 56,
        },
      ],
    },
    {
      id: 'combo-saffron-harbor',
      label: 'Saffron Harbor',
      description: 'Cormorant Garamond headline with welcoming Nunito Sans body.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Saffron Harbor',
          family: 'Cormorant Garamond',
          fallback: 'serif',
          fontSize: 48,
          fontWeight: '500',
          letterSpacing: 1,
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Artisanal markets & seaside residencies',
          family: 'Nunito Sans',
          fallback: 'sans-serif',
          fontSize: 19,
          fontWeight: '400',
          offsetY: 64,
        },
      ],
    },
    {
      id: 'combo-meridian-labs',
      label: 'Meridian Labs',
      description: 'Libre Baskerville deck titles with Manrope research notes.',
      align: 'left',
      layers: [
        {
          role: 'heading',
          content: 'Meridian Labs',
          family: 'Libre Baskerville',
          fallback: 'serif',
          fontSize: 40,
          fontWeight: '700',
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Insight decks • Research memos',
          family: 'Manrope',
          fallback: 'sans-serif',
          fontSize: 18,
          fontWeight: '500',
          offsetY: 58,
        },
      ],
    },
    {
      id: 'combo-celestial-grid',
      label: 'Celestial Grid',
      description: 'Cinzel monograms paired with Space Grotesk captions.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Celestial Grid',
          family: 'Cinzel',
          fallback: 'serif',
          fontSize: 46,
          fontWeight: '600',
          letterSpacing: 1.2,
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Futurist observatory narratives',
          family: 'Space Grotesk',
          fallback: 'sans-serif',
          fontSize: 17,
          fontWeight: '500',
          offsetY: 60,
        },
      ],
    },
    {
      id: 'combo-radiant-market',
      label: 'Radiant Market',
      description: 'Abril Fatface display balanced by Work Sans merchandising notes.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Radiant Market',
          family: 'Abril Fatface',
          fallback: 'serif',
          fontSize: 50,
          fontWeight: '600',
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Seasonal florals & gifting studio',
          family: 'Work Sans',
          fallback: 'sans-serif',
          fontSize: 18,
          fontWeight: '400',
          offsetY: 66,
        },
      ],
    },
    {
      id: 'combo-atelier-north',
      label: 'Atelier North',
      description: 'Lora type paired with crisp Poppins service line.',
      align: 'left',
      layers: [
        {
          role: 'heading',
          content: 'Atelier North',
          family: 'Lora',
          fallback: 'serif',
          fontSize: 42,
          fontWeight: '600',
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Boutique identity consultancy',
          family: 'Poppins',
          fallback: 'sans-serif',
          fontSize: 18,
          fontWeight: '500',
          offsetY: 60,
        },
      ],
    },
    {
      id: 'combo-solstice-audio',
      label: 'Solstice Audio',
      description: 'Volkhov warmth with Mulish modernity.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Solstice Audio',
          family: 'Volkhov',
          fallback: 'serif',
          fontSize: 44,
          fontWeight: '700',
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Soundscapes for modern retreats',
          family: 'Mulish',
          fallback: 'sans-serif',
          fontSize: 17,
          fontWeight: '500',
          letterSpacing: 0.4,
          offsetY: 62,
        },
      ],
    },
    {
      id: 'combo-harvest-lane',
      label: 'Harvest Lane',
      description: 'Cardo editorial serif with Questrial market notes.',
      align: 'left',
      layers: [
        {
          role: 'heading',
          content: 'Harvest Lane',
          family: 'Cardo',
          fallback: 'serif',
          fontSize: 43,
          fontWeight: '600',
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Slow food residencies & journals',
          family: 'Questrial',
          fallback: 'sans-serif',
          fontSize: 18,
          fontWeight: '400',
          offsetY: 60,
        },
      ],
    },
    {
      id: 'combo-paragon-museum',
      label: 'Paragon Museum',
      description: 'Marcellus title with IBM Plex Sans itinerary details.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Paragon Museum',
          family: 'Marcellus',
          fallback: 'serif',
          fontSize: 45,
          fontWeight: '600',
          letterSpacing: 1,
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Annual symposium schedule',
          family: 'IBM Plex Sans',
          fallback: 'sans-serif',
          fontSize: 18,
          fontWeight: '500',
          offsetY: 60,
        },
        {
          role: 'detail',
          content: 'Tickets • Archives • Tours',
          family: 'IBM Plex Sans',
          fallback: 'sans-serif',
          fontSize: 14,
          fontWeight: '400',
          letterSpacing: 1,
          offsetY: 110,
        },
      ],
    },
    {
      id: 'combo-velvet-horizon',
      label: 'Velvet Horizon',
      description: 'Rosarivo curves with airy Raleway notes.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Velvet Horizon',
          family: 'Rosarivo',
          fallback: 'serif',
          fontSize: 46,
          fontWeight: '600',
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Sunset editorials & travel essays',
          family: 'Raleway',
          fallback: 'sans-serif',
          fontSize: 17,
          fontWeight: '500',
          offsetY: 62,
        },
      ],
    },
    {
      id: 'combo-meteora-studio',
      label: 'Meteora Studio',
      description: 'Prata elegance with grounded Figtree descriptors.',
      align: 'left',
      layers: [
        {
          role: 'heading',
          content: 'Meteora Studio',
          family: 'Prata',
          fallback: 'serif',
          fontSize: 44,
          fontWeight: '600',
          letterSpacing: 1.2,
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Speculative interiors & lighting labs',
          family: 'Figtree',
          fallback: 'sans-serif',
          fontSize: 18,
          fontWeight: '500',
          offsetY: 60,
        },
      ],
    },
    {
      id: 'combo-ember-studio',
      label: 'Ember Studio',
      description: 'Spectral prose headlines with Karla annotations.',
      align: 'left',
      layers: [
        {
          role: 'heading',
          content: 'Ember Studio',
          family: 'Spectral',
          fallback: 'serif',
          fontSize: 42,
          fontWeight: '600',
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Culinary films & sonic diaries',
          family: 'Karla',
          fallback: 'sans-serif',
          fontSize: 18,
          fontWeight: '500',
          offsetY: 60,
        },
      ],
    },
    {
      id: 'combo-glasshouse',
      label: 'Glasshouse',
      description: 'Gloock statement paired with Montserrat details.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Glasshouse',
          family: 'Gloock',
          fallback: 'serif',
          fontSize: 48,
          fontWeight: '600',
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Greenhouse culture & creative labs',
          family: 'Montserrat',
          fallback: 'sans-serif',
          fontSize: 18,
          fontWeight: '500',
          offsetY: 64,
        },
      ],
    },
    {
      id: 'combo-nova-lounge',
      label: 'Nova Lounge',
      description: 'Noto Serif Display paired with Urbanist lounge notes.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Nova Lounge',
          family: 'Noto Serif Display',
          fallback: 'serif',
          fontSize: 46,
          fontWeight: '600',
          letterSpacing: 1,
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Midnight tastings & listening rooms',
          family: 'Urbanist',
          fallback: 'sans-serif',
          fontSize: 18,
          fontWeight: '400',
          offsetY: 62,
        },
      ],
    },
    {
      id: 'combo-terrace-agency',
      label: 'Terrace Agency',
      description: 'Quattrocento headlines with DM Sans service info.',
      align: 'left',
      layers: [
        {
          role: 'heading',
          content: 'Terrace Agency',
          family: 'Quattrocento',
          fallback: 'serif',
          fontSize: 41,
          fontWeight: '700',
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Landscape narratives & brand sites',
          family: 'DM Sans',
          fallback: 'sans-serif',
          fontSize: 18,
          fontWeight: '500',
          offsetY: 58,
        },
      ],
    },
    {
      id: 'combo-lucid-canvas',
      label: 'Lucid Canvas',
      description: 'Ysabeau contrasts with approachable Cabin body.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Lucid Canvas',
          family: 'Ysabeau',
          fallback: 'serif',
          fontSize: 45,
          fontWeight: '600',
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Immersive art fair programming',
          family: 'Cabin',
          fallback: 'sans-serif',
          fontSize: 18,
          fontWeight: '500',
          offsetY: 62,
        },
      ],
    },
    {
      id: 'combo-citrine-summit',
      label: 'Citrine Summit',
      description: 'Crimson Pro titles with Open Sans agenda text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Citrine Summit',
          family: 'Crimson Pro',
          fallback: 'serif',
          fontSize: 43,
          fontWeight: '600',
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Sustainability briefings & labs',
          family: 'Open Sans',
          fallback: 'sans-serif',
          fontSize: 18,
          fontWeight: '500',
          offsetY: 60,
        },
      ],
    },
    {
      id: 'combo-frame-factory',
      label: 'Frame Factory',
      description: 'Fraunces curves with minimalist Outfit copy.',
      align: 'left',
      layers: [
        {
          role: 'heading',
          content: 'Frame Factory',
          family: 'Fraunces',
          fallback: 'serif',
          fontSize: 42,
          fontWeight: '600',
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Gallery systems & product drops',
          family: 'Outfit',
          fallback: 'sans-serif',
          fontSize: 18,
          fontWeight: '500',
          offsetY: 58,
        },
      ],
    },
    {
      id: 'combo-indigo-manor',
      label: 'Indigo Manor',
      description: 'EB Garamond editorial voice with Hind Madurai support.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Indigo Manor',
          family: 'EB Garamond',
          fallback: 'serif',
          fontSize: 45,
          fontWeight: '600',
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Cultural residences & salons',
          family: 'Hind Madurai',
          fallback: 'sans-serif',
          fontSize: 18,
          fontWeight: '500',
          offsetY: 62,
        },
      ],
    },
    {
      id: 'combo-marble-coast',
      label: 'Marble Coast',
      description: 'Tenor Sans structure with Gentium Plus narrative.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Marble Coast',
          family: 'Tenor Sans',
          fallback: 'sans-serif',
          fontSize: 42,
          fontWeight: '600',
          letterSpacing: 0.8,
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Coastal architecture dossiers',
          family: 'Gentium Plus',
          fallback: 'serif',
          fontSize: 18,
          fontWeight: '500',
          offsetY: 58,
        },
      ],
    },
    {
      id: 'combo-orchid-bureau',
      label: 'Orchid Bureau',
      description: 'Newsreader gravitas with Heebo clarity.',
      align: 'left',
      layers: [
        {
          role: 'heading',
          content: 'Orchid Bureau',
          family: 'Newsreader',
          fallback: 'serif',
          fontSize: 43,
          fontWeight: '600',
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Trend reports • Field interviews',
          family: 'Heebo',
          fallback: 'sans-serif',
          fontSize: 18,
          fontWeight: '500',
          offsetY: 58,
        },
      ],
    },
    {
      id: 'combo-lux-atelier',
      label: 'Lux Atelier',
      description: 'Baskervville elegance with utilitarian Lato captions.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Lux Atelier',
          family: 'Baskervville',
          fallback: 'serif',
          fontSize: 44,
          fontWeight: '600',
          letterSpacing: 1,
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Made-to-measure wardrobe plans',
          family: 'Lato',
          fallback: 'sans-serif',
          fontSize: 18,
          fontWeight: '500',
          offsetY: 60,
        },
      ],
    },
    {
      id: 'combo-cobalt-grove',
      label: 'Cobalt Grove',
      description: 'Zilla Slab hero text with Rubik supporting copy.',
      align: 'left',
      layers: [
        {
          role: 'heading',
          content: 'Cobalt Grove',
          family: 'Zilla Slab',
          fallback: 'serif',
          fontSize: 42,
          fontWeight: '700',
          offsetY: 0,
        },
        {
          role: 'subheading',
          content: 'Botanical research & residency notes',
          family: 'Rubik',
          fallback: 'sans-serif',
          fontSize: 18,
          fontWeight: '500',
          offsetY: 58,
        },
      ],
    },
    {
      id: 'combo-lobster-spark-joy',
      label: 'Lobster',
      description: 'Lobster script font with "Spark Joy" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Spark Joy',
          family: 'Lobster',
          fallback: 'cursive',
          fontSize: 48,
          fontWeight: '400',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-fredoka-one-party-mode',
      label: 'Fredoka One',
      description: 'Fredoka One rounded font with "Party Mode" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Party Mode',
          family: 'Fredoka One',
          fallback: 'sans-serif',
          fontSize: 44,
          fontWeight: '400',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-pacifico-sweet-vibes',
      label: 'Pacifico',
      description: 'Pacifico brush script with "Sweet Vibes" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Sweet Vibes',
          family: 'Pacifico',
          fallback: 'cursive',
          fontSize: 46,
          fontWeight: '400',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-baloo-two-happy-day',
      label: 'Baloo 2',
      description: 'Baloo 2 playful font with "Happy Day" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Happy Day',
          family: 'Baloo 2',
          fallback: 'cursive',
          fontSize: 45,
          fontWeight: '600',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-poppins-bold-fun-time',
      label: 'Poppins Bold',
      description: 'Poppins Bold with "Fun Time" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Fun Time',
          family: 'Poppins',
          fallback: 'sans-serif',
          fontSize: 42,
          fontWeight: '700',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-anton-big-wish',
      label: 'Anton',
      description: 'Anton condensed font with "Big Wish" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Big Wish',
          family: 'Anton',
          fallback: 'sans-serif',
          fontSize: 44,
          fontWeight: '400',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-gochi-hand-playful-mood',
      label: 'Gochi Hand',
      description: 'Gochi Hand handwritten font with "Playful Mood" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Playful Mood',
          family: 'Gochi Hand',
          fallback: 'cursive',
          fontSize: 42,
          fontWeight: '400',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-bangers-color-pop',
      label: 'Bangers',
      description: 'Bangers comic font with "Color Pop" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Color Pop',
          family: 'Bangers',
          fallback: 'cursive',
          fontSize: 46,
          fontWeight: '400',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-chewy-bright-fun',
      label: 'Chewy',
      description: 'Chewy rounded font with "Bright Fun" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Bright Fun',
          family: 'Chewy',
          fallback: 'cursive',
          fontSize: 44,
          fontWeight: '400',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-rubik-extrabold-joy-burst',
      label: 'Rubik ExtraBold',
      description: 'Rubik ExtraBold with "Joy Burst" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Joy Burst',
          family: 'Rubik',
          fallback: 'sans-serif',
          fontSize: 43,
          fontWeight: '800',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-montserrat-prime-edge',
      label: 'Montserrat SemiBold',
      description: 'Montserrat SemiBold with "Prime Edge" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Prime Edge',
          family: 'Montserrat',
          fallback: 'sans-serif',
          fontSize: 42,
          fontWeight: '600',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-helvetica-core-vision',
      label: 'Helvetica Neue',
      description: 'Helvetica Neue with "Core Vision" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Core Vision',
          family: 'Helvetica Neue',
          fallback: 'sans-serif',
          fontSize: 40,
          fontWeight: '600',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-lato-solid-mark',
      label: 'Lato Bold',
      description: 'Lato Bold with "Solid Mark" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Solid Mark',
          family: 'Lato',
          fallback: 'sans-serif',
          fontSize: 41,
          fontWeight: '700',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-source-sans-blue-line',
      label: 'Source Sans Pro',
      description: 'Source Sans Pro with "Blue Line" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Blue Line',
          family: 'Source Sans Pro',
          fallback: 'sans-serif',
          fontSize: 40,
          fontWeight: '600',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-poppins-medium-clean-form',
      label: 'Poppins Medium',
      description: 'Poppins Medium with "Clean Form" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Clean Form',
          family: 'Poppins',
          fallback: 'sans-serif',
          fontSize: 40,
          fontWeight: '500',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-roboto-condensed-sharp-fit',
      label: 'Roboto Condensed',
      description: 'Roboto Condensed with "Sharp Fit" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Sharp Fit',
          family: 'Roboto Condensed',
          fallback: 'sans-serif',
          fontSize: 41,
          fontWeight: '600',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-open-sans-pure-focus',
      label: 'Open Sans',
      description: 'Open Sans with "Pure Focus" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Pure Focus',
          family: 'Open Sans',
          fallback: 'sans-serif',
          fontSize: 40,
          fontWeight: '600',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-oswald-bold-scope',
      label: 'Oswald',
      description: 'Oswald with "Bold Scope" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Bold Scope',
          family: 'Oswald',
          fallback: 'sans-serif',
          fontSize: 43,
          fontWeight: '700',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-nunito-sans-next-phase',
      label: 'Nunito Sans',
      description: 'Nunito Sans with "Next Phase" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Next Phase',
          family: 'Nunito Sans',
          fallback: 'sans-serif',
          fontSize: 40,
          fontWeight: '600',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-inter-true-value',
      label: 'Inter SemiBold',
      description: 'Inter SemiBold with "True Value" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'True Value',
          family: 'Inter',
          fallback: 'sans-serif',
          fontSize: 40,
          fontWeight: '600',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-quicksand-pure-grace',
      label: 'Quicksand Light',
      description: 'Quicksand Light with "Pure Grace" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Pure Grace',
          family: 'Quicksand',
          fallback: 'sans-serif',
          fontSize: 39,
          fontWeight: '300',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-great-vibes-little-bless',
      label: 'Great Vibes',
      description: 'Great Vibes script with "Little Bless" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Little Bless',
          family: 'Great Vibes',
          fallback: 'cursive',
          fontSize: 48,
          fontWeight: '400',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-parisienne-soft-light',
      label: 'Parisienne',
      description: 'Parisienne script with "Soft Light" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Soft Light',
          family: 'Parisienne',
          fallback: 'cursive',
          fontSize: 47,
          fontWeight: '400',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-nunito-gentle-love',
      label: 'Nunito Light',
      description: 'Nunito Light with "Gentle Love" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Gentle Love',
          family: 'Nunito',
          fallback: 'sans-serif',
          fontSize: 39,
          fontWeight: '300',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-cormorant-garamond-sacred-hope',
      label: 'Cormorant Garamond',
      description: 'Cormorant Garamond with "Sacred Hope" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Sacred Hope',
          family: 'Cormorant Garamond',
          fallback: 'serif',
          fontSize: 44,
          fontWeight: '600',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-playfair-display-new-faith',
      label: 'Playfair Display',
      description: 'Playfair Display with "New Faith" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'New Faith',
          family: 'Playfair Display',
          fallback: 'serif',
          fontSize: 44,
          fontWeight: '600',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-allura-sweet-peace',
      label: 'Allura',
      description: 'Allura script with "Sweet Peace" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Sweet Peace',
          family: 'Allura',
          fallback: 'cursive',
          fontSize: 47,
          fontWeight: '400',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-dm-sans-calm-flow',
      label: 'DM Sans',
      description: 'DM Sans with "Calm Flow" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Calm Flow',
          family: 'DM Sans',
          fallback: 'sans-serif',
          fontSize: 40,
          fontWeight: '500',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-satisfy-angel-glow',
      label: 'Satisfy',
      description: 'Satisfy script with "Angel Glow" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Angel Glow',
          family: 'Satisfy',
          fallback: 'cursive',
          fontSize: 47,
          fontWeight: '400',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-karla-tender-joy',
      label: 'Karla',
      description: 'Karla with "Tender Joy" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Tender Joy',
          family: 'Karla',
          fallback: 'sans-serif',
          fontSize: 40,
          fontWeight: '600',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-cinzel-eternal-love',
      label: 'Cinzel',
      description: 'Cinzel with "Eternal Love" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Eternal Love',
          family: 'Cinzel',
          fallback: 'serif',
          fontSize: 44,
          fontWeight: '600',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-cormorant-infant-golden-vow',
      label: 'Cormorant Infant',
      description: 'Cormorant Infant with "Golden Vow" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Golden Vow',
          family: 'Cormorant Infant',
          fallback: 'serif',
          fontSize: 44,
          fontWeight: '600',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-playfair-display-sweet-promise',
      label: 'Playfair Display Italic',
      description: 'Playfair Display italic styling with "Sweet Promise" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Sweet Promise',
          family: 'Playfair Display',
          fallback: 'serif',
          fontSize: 43,
          fontWeight: '500',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-great-vibes-forever-us',
      label: 'Great Vibes Duo',
      description: 'Great Vibes script with "Forever Us" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Forever Us',
          family: 'Great Vibes',
          fallback: 'cursive',
          fontSize: 48,
          fontWeight: '400',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-alex-brush-soft-romance',
      label: 'Alex Brush',
      description: 'Alex Brush script with "Soft Romance" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Soft Romance',
          family: 'Alex Brush',
          fallback: 'cursive',
          fontSize: 47,
          fontWeight: '400',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-montserrat-light-true-bond',
      label: 'Montserrat Light',
      description: 'Montserrat Light with "True Bond" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'True Bond',
          family: 'Montserrat',
          fallback: 'sans-serif',
          fontSize: 40,
          fontWeight: '300',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-bodoni-moda-classic-heart',
      label: 'Bodoni Moda',
      description: 'Bodoni Moda with "Classic Heart" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Classic Heart',
          family: 'Bodoni Moda',
          fallback: 'serif',
          fontSize: 44,
          fontWeight: '600',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-parisienne-pure-bliss',
      label: 'Parisienne Delight',
      description: 'Parisienne script with "Pure Bliss" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Pure Bliss',
          family: 'Parisienne',
          fallback: 'cursive',
          fontSize: 47,
          fontWeight: '400',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-cormorant-upright-duo-dream',
      label: 'Cormorant Upright',
      description: 'Cormorant Upright with "Duo Dream" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Duo Dream',
          family: 'Cormorant Upright',
          fallback: 'serif',
          fontSize: 44,
          fontWeight: '600',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-libre-baskerville-ever-after',
      label: 'Libre Baskerville Italic',
      description: 'Libre Baskerville italic styling with "Ever After" text.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Ever After',
          family: 'Libre Baskerville',
          fallback: 'serif',
          fontSize: 43,
          fontWeight: '500',
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-bright-joy',
      label: 'Bright Joy',
      description: 'Comic Neue Bold pops with cheerful energy.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Bright Joy',
          family: 'Comic Neue',
          fallback: 'sans-serif',
          fontSize: 56,
          fontWeight: '700',
          letterSpacing: 1.2,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-fun-burst',
      label: 'Fun Burst',
      description: 'Amatic SC Bold delivers tall, joyful headlines.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Fun Burst',
          family: 'Amatic SC',
          fallback: 'cursive',
          fontSize: 60,
          fontWeight: '700',
          letterSpacing: 2,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-party-pop',
      label: 'Party Pop',
      description: 'Luckiest Guy makes party invites feel loud and bold.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Party Pop',
          family: 'Luckiest Guy',
          fallback: 'sans-serif',
          fontSize: 54,
          fontWeight: '700',
          letterSpacing: 0.8,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-happy-glow',
      label: 'Happy Glow',
      description: 'Kalam Bold handwriting with warm friendly charm.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Happy Glow',
          family: 'Kalam',
          fallback: 'cursive',
          fontSize: 52,
          fontWeight: '700',
          letterSpacing: 0.6,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-sugar-fun',
      label: 'Sugar Fun',
      description: 'Bubblegum Sans delivers soft rounded sweetness.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Sugar Fun',
          family: 'Bubblegum Sans',
          fallback: 'cursive',
          fontSize: 50,
          fontWeight: '400',
          letterSpacing: 0.6,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-big-cheer',
      label: 'Big Cheer',
      description: 'Shrikhand brings oversized celebratory impact.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Big Cheer',
          family: 'Shrikhand',
          fallback: 'cursive',
          fontSize: 58,
          fontWeight: '400',
          letterSpacing: 1,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-color-wave',
      label: 'Color Wave',
      description: 'Jua keeps bold all-caps lettering friendly and bright.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Color Wave',
          family: 'Jua',
          fallback: 'sans-serif',
          fontSize: 52,
          fontWeight: '400',
          letterSpacing: 1,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-cute-spark',
      label: 'Cute Spark',
      description: 'DynaPuff bursts with bubbly motion and bounce.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Cute Spark',
          family: 'DynaPuff',
          fallback: 'sans-serif',
          fontSize: 54,
          fontWeight: '700',
          letterSpacing: 1,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-fun-scribble',
      label: 'Fun Scribble',
      description: 'Cabin Sketch channels playful pencil energy.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Fun Scribble',
          family: 'Cabin Sketch',
          fallback: 'cursive',
          fontSize: 56,
          fontWeight: '700',
          letterSpacing: 1.4,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-mega-fun',
      label: 'Mega Fun',
      description: 'Titan One makes every headline feel like a billboard.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Mega Fun',
          family: 'Titan One',
          fallback: 'cursive',
          fontSize: 55,
          fontWeight: '400',
          letterSpacing: 1,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-core-line',
      label: 'Core Line',
      description: 'Futura PT brings geometric corporate clarity.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Core Line',
          family: 'Futura PT',
          fallback: 'sans-serif',
          fontSize: 46,
          fontWeight: '600',
          letterSpacing: 1,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-prime-grid',
      label: 'Prime Grid',
      description: 'Avenir Next keeps tech-forward layouts refined.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Prime Grid',
          family: 'Avenir Next',
          fallback: 'sans-serif',
          fontSize: 44,
          fontWeight: '600',
          letterSpacing: 0.8,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-clean-slate',
      label: 'Clean Slate',
      description: 'Work Sans Medium introduces crisp editorial tone.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Clean Slate',
          family: 'Work Sans',
          fallback: 'sans-serif',
          fontSize: 44,
          fontWeight: '500',
          letterSpacing: 0.6,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-lead-focus',
      label: 'Lead Focus',
      description: 'Proxima Nova stays neutral for product decks.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Lead Focus',
          family: 'Proxima Nova',
          fallback: 'sans-serif',
          fontSize: 44,
          fontWeight: '600',
          letterSpacing: 0.6,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-firm-base',
      label: 'Firm Base',
      description: 'IBM Plex Sans looks confident and precise.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Firm Base',
          family: 'IBM Plex Sans',
          fallback: 'sans-serif',
          fontSize: 44,
          fontWeight: '600',
          letterSpacing: 0.6,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-true-form',
      label: 'True Form',
      description: 'Barlow Semi Condensed adds agile modern structure.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'True Form',
          family: 'Barlow Semi Condensed',
          fallback: 'sans-serif',
          fontSize: 44,
          fontWeight: '600',
          letterSpacing: 0.6,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-sharp-flow',
      label: 'Sharp Flow',
      description: 'Sora balances futuristic branding and clarity.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Sharp Flow',
          family: 'Sora',
          fallback: 'sans-serif',
          fontSize: 44,
          fontWeight: '600',
          letterSpacing: 0.8,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-next-line',
      label: 'Next Line',
      description: 'Metropolis delivers clean presentation headers.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Next Line',
          family: 'Metropolis',
          fallback: 'sans-serif',
          fontSize: 44,
          fontWeight: '600',
          letterSpacing: 0.8,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'combo-clear-frame',
      label: 'Clear Frame',
      description: 'Urbanist keeps product UI notes legible and fresh.',
      align: 'center',
      layers: [
        {
          role: 'heading',
          content: 'Clear Frame',
          family: 'Urbanist',
          fallback: 'sans-serif',
          fontSize: 44,
          fontWeight: '600',
          letterSpacing: 0.6,
          offsetY: 0,
        },
      ],
    },
    {
      id: 'default-user',
      thumbUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/user.svg',
      previewUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/user.svg',
      downloadUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/user.svg',
      description: 'User',
      provider: 'default',
      providerLabel: 'Simple Icons',
      credit: 'Simple Icons',
    },
  ];
  const sampleStyledTexts = [
    'Life is an ADVENTURE',
    "Congratulations! You're a Big Brother",
    'MARKETING PROPOSAL',
    'SALE',
    'MINIMALISM',
    'Operations Manager',
    'CREATIVE DESIGN',
    'MODERN ARTISTRY',
    'PROFESSIONAL SERVICES',
    'DIGITAL INNOVATION',
    'BRAND IDENTITY',
    'VISUAL STORYTELLING',
    'GRAPHIC DESIGN',
    'TYPOGRAPHY MATTERS',
    'DESIGN EXCELLENCE',
    'CREATIVE SOLUTIONS',
    'ART DIRECTION',
    'VISUAL IMPACT',
    'DESIGN THINKING',
    'CREATIVE PROCESS',
    'BRAND STORY',
    'VISUAL LANGUAGE',
    'DESIGN SYSTEM',
    'CREATIVE AGENCY',
    'VISUAL DESIGN',
    'TYPEFACE DESIGN',
    'GRAPHIC ARTS',
    'DESIGN STUDIO',
    'CREATIVE BRIEF',
    'VISUAL CONCEPT',
  ];

  const loadStyledPresets = useCallback((page = 1) => {
    if (isLoadingStyledPresets || !hasMoreStyledPresets) return;
    setIsLoadingStyledPresets(true);
    try {
      const start = (page - 1) * styledPerPage;
      const slice = curatedFontCombos.slice(start, start + styledPerPage);

      if (slice.length === 0) {
        setHasMoreStyledPresets(false);
        return;
      }

      setStyledPresets((prev) => [...prev, ...slice]);
      setStyledPage(page + 1);
      setHasMoreStyledPresets(start + styledPerPage < curatedFontCombos.length);
    } finally {
      setIsLoadingStyledPresets(false);
    }
  }, [hasMoreStyledPresets, isLoadingStyledPresets, styledPerPage]);

  // Initialize styled presets when text tool becomes active
  useEffect(() => {
    if (activeTool === 'text' && styledPresets.length === 0) {
      loadStyledPresets(1);
    }
  }, [activeTool, styledPresets.length, loadStyledPresets]);

  // IntersectionObserver for styled presets infinite scroll
  useEffect(() => {
    const container = styledContainerRef.current || document.querySelector('.text-panel__styled-presets');
    const loadingIndicator = container?.querySelector('.styled-loading-indicator');
    if (!container || !loadingIndicator || !hasMoreStyledPresets) return;

    const observer = new IntersectionObserver((entries) => {
      const [entry] = entries;
      if (entry.isIntersecting && !isLoadingStyledPresets && hasMoreStyledPresets) {
        loadStyledPresets(styledPage);
      }
    }, { root: container, rootMargin: '50px', threshold: 0.1 });

    observer.observe(loadingIndicator);
    styledObserverRef.current = observer;

    return () => {
      try { styledObserverRef.current?.disconnect(); } catch (e) { /* ignore */ }
    };
  }, [styledPage, isLoadingStyledPresets, hasMoreStyledPresets, loadStyledPresets]);

  // Shape search state
  const [shapeSearchQuery, setShapeSearchQuery] = useState('');
  const [shapeSearchResults, setShapeSearchResults] = useState(() => getDefaultShapeResults());
  const [isSearchingShapes, setIsSearchingShapes] = useState(false);
  const [shapeCurrentPage, setShapeCurrentPage] = useState(1);
  const [isLoadingMoreShapes, setIsLoadingMoreShapes] = useState(false);
  const [hasMoreShapes, setHasMoreShapes] = useState(false);
  const shapeCurrentPageRef = useRef(1);
  const activeShapeSearchQueryRef = useRef('');
  const hasTriggeredShapeSearchRef = useRef(false);

  const loadedFontsRef = useRef(new Set());
  const { state, dispatch } = useBuilderStore();
  const fileInputRef = useRef(null);
  const searchInputRef = useRef(null);
  const textPanelCanvasRef = useRef(null);

  const activePage = useMemo(
    () => state.pages.find((page) => page.id === state.activePageId) ?? state.pages[0],
    [state.pages, state.activePageId],
  );

  const safeInsets = useMemo(() => resolveInsets(activePage?.safeZone), [activePage?.safeZone]);
  const activeProviderLabel = 'Unsplash + Pixabay';
  const activeGradientThemeConfig = useMemo(() => (
    LINEAR_GRADIENT_LIBRARY.find((theme) => theme.id === activeGradientTheme) ?? LINEAR_GRADIENT_LIBRARY[0]
  ), [LINEAR_GRADIENT_LIBRARY, activeGradientTheme]);
  useEffect(() => {
    setBackgroundColorInput(normalizeColorForInput(activePage?.background ?? '#ffffff'));
  }, [activePage?.background, normalizeColorForInput]);

  useEffect(() => {
    setSolidPickerColor(backgroundColorInput);
  }, [backgroundColorInput]);

  useEffect(() => {
    setSolidHexDraft(solidPickerColor.toUpperCase());
  }, [solidPickerColor]);

  useEffect(() => {
    setGradientInputs(gradientColorStops.map((stop) => {
      if (typeof stop !== 'string') {
        return '#FFFFFF';
      }
      const trimmed = stop.trim();
      const prefixed = trimmed.startsWith('#') ? trimmed : `#${trimmed}`;
      return prefixed.substring(0, 7).toUpperCase();
    }));
  }, [gradientColorStops]);

  useEffect(() => {
    const { h } = hexToHsl(solidPickerColor);
    setHueSliderValue(h);
  }, [solidPickerColor]);

  useEffect(() => {
    if (!isColorModalOpen) {
      return undefined;
    }

    const handleClickOutside = (event) => {
      const trigger = event.target?.closest?.('.builder-sidebar__color-button');
      if (colorModalRef.current && !colorModalRef.current.contains(event.target) && !trigger) {
        setIsColorModalOpen(false);
      }
    };

    const handleKeyDown = (event) => {
      if (event.key === 'Escape') {
        setIsColorModalOpen(false);
      }
    };

    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
      document.removeEventListener('keydown', handleKeyDown);
    };
  }, [isColorModalOpen]);





  const handleAddText = (preset) => {
    if (!activePage) {
      return;
    }

    const layer = createLayer('text', activePage, {
      name: preset.name,
      content: preset.content,
      fontSize: preset.fontSize,
      textAlign: preset.align ?? 'center',
    });

    if (layer.frame) {
      layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
    }

    dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
  };

  // Generate many short presets (1-2 words) for categories like birthday, corporate, baptism, wedding
  useEffect(() => {
    try {
      const generated = generateFontPresets(curatedFontCombos, { perCategory: 24 });
      // prefer generated presets if styledPresets hasn't been customized elsewhere
      if (generated && generated.length) {
        setStyledPresets(generated);
      }
    } catch (err) {
      console.warn('Failed to generate font presets', err);
    }
  }, []);

  const handleAddShape = (shapeInput, imageDataUrl = null) => {
    if (!activePage) {
      return;
    }

    const shapeConfig = typeof shapeInput === 'string' || !shapeInput
      ? { variant: shapeInput || 'rectangle' }
      : shapeInput;

    const rawVariant = shapeConfig.variant ?? 'rectangle';
    const normalization = SHAPE_VARIANT_NORMALIZATION[rawVariant] || {};
    const normalizedVariant = normalization.variant ?? rawVariant;
    const fallbackLabelSource = shapeConfig.maskVariant || normalization.mask || rawVariant || 'shape';
    const friendlyLabel = typeof fallbackLabelSource === 'string'
      ? fallbackLabelSource
        .replace(/[-_]+/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
      : 'Shape';
    const name = shapeConfig.description || shapeConfig.label || shapeConfig.name || friendlyLabel || 'Shape';

    let borderRadius = shapeConfig.borderRadius;
    if (borderRadius === undefined || borderRadius === null) {
      if (normalizedVariant === 'circle') {
        borderRadius = 9999;
      } else if (normalizedVariant === 'rectangle') {
        borderRadius = 16;
      } else {
        borderRadius = 0;
      }
    }

    const layer = createLayer('shape', activePage, {
      name,
      variant: normalizedVariant,
      borderRadius,
      fill: shapeConfig.fill ?? shapeConfig.placeholderFill ?? undefined,
      stroke: shapeConfig.stroke ?? shapeConfig.placeholderStroke ?? undefined,
      metadata: { ...(shapeConfig.metadata ?? {}) },
    });

    const baseMetadata = { ...(layer.metadata ?? {}) };
    const maskVariant = shapeConfig.maskVariant
      ?? baseMetadata.maskVariant
      ?? normalization.mask
      ?? normalizedVariant;
    const resolvedIsImageFrame = (baseMetadata.isImageFrame ?? shapeConfig.isImageFrame ?? normalization.isImageFrame);
    const isImageFrame = resolvedIsImageFrame === undefined ? true : resolvedIsImageFrame;

    if (isImageFrame) {
      const placeholderFill = shapeConfig.placeholderFill
        ?? baseMetadata.placeholderFill
        ?? layer.fill
        ?? DEFAULT_SHAPE_PLACEHOLDER_FILL;
      const placeholderStroke = shapeConfig.placeholderStroke
        ?? baseMetadata.placeholderStroke
        ?? layer.stroke
        ?? DEFAULT_SHAPE_PLACEHOLDER_STROKE;
      const placeholderLabel = shapeConfig.placeholderLabel
        ?? baseMetadata.placeholderLabel
        ?? 'Add image';
      const placeholderIcon = shapeConfig.placeholderIcon
        ?? baseMetadata.placeholderIcon
        ?? 'fa-solid fa-image';

      layer.metadata = {
        ...baseMetadata,
        placeholderFill,
        placeholderStroke,
        placeholderLabel,
        placeholderIcon,
        maskVariant,
        isImageFrame: true,
        objectFit: baseMetadata.objectFit ?? 'cover',
        imageScale: Number.isFinite(baseMetadata.imageScale) ? baseMetadata.imageScale : 1,
        imageOffsetX: Number.isFinite(baseMetadata.imageOffsetX) ? baseMetadata.imageOffsetX : 0,
        imageOffsetY: Number.isFinite(baseMetadata.imageOffsetY) ? baseMetadata.imageOffsetY : 0,
        originalVariant: rawVariant,
      };

      layer.fill = placeholderFill;
      layer.stroke = placeholderStroke;
    } else {
      layer.metadata = {
        ...baseMetadata,
        maskVariant,
        isImageFrame: false,
        allowImageFill: baseMetadata.allowImageFill ?? false,
        allowColorFill: baseMetadata.allowColorFill ?? true,
        originalVariant: rawVariant,
      };

      if (layer.fill === undefined) {
        layer.fill = DEFAULT_SHAPE_PLACEHOLDER_FILL;
      }

      if (layer.stroke === undefined) {
        layer.stroke = DEFAULT_SHAPE_PLACEHOLDER_STROKE;
      }
    }

    layer.shape = {
      id: maskVariant,
      variant: normalizedVariant,
      borderRadius,
    };

    if (normalizedVariant === 'circle') {
      layer.frame = {
        ...layer.frame,
        width: Math.min(activePage.width * 0.3, layer.frame?.width ?? 280),
        height: Math.min(activePage.width * 0.3, layer.frame?.height ?? 280),
      };
    }

    const resolvedImage = typeof imageDataUrl === 'string' && imageDataUrl
      ? imageDataUrl
      : typeof shapeConfig.imageDataUrl === 'string' && shapeConfig.imageDataUrl
        ? shapeConfig.imageDataUrl
        : typeof shapeConfig.content === 'string' && shapeConfig.content
          ? shapeConfig.content
          : null;

    if (resolvedImage && isImageFrame) {
      layer.content = resolvedImage;
      layer.fill = 'transparent';
      layer.stroke = 'transparent';
    } else {
      layer.content = '';
    }

    if (layer.frame) {
      layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
    }

    dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
  };

  const handleAddImagePlaceholder = () => {
    if (!activePage) {
      return;
    }

    // Trigger file input for image upload
    fileInputRef.current?.click();
  };

  const handleFileSelect = async (event) => {
    const file = event.target.files[0];
    if (!file || !activePage) {
      console.log('No file selected or no active page');
      return;
    }

    console.log('File selected:', file.name, 'Size:', file.size, 'Type:', file.type);

    // Check if file is an image
    if (!file.type.startsWith('image/')) {
      alert('Please select an image file.');
      return;
    }

    // Check file size (limit to 10MB to prevent memory issues)
    const maxSize = 10 * 1024 * 1024; // 10MB
    if (file.size > maxSize) {
      alert('Image file is too large. Please select an image smaller than 10MB.');
      return;
    }


    // Create a FileReader to read the image and create a data URL for the canvas
    const reader = new FileReader();
    reader.onload = async (e) => {
      try {
        const imageUrl = e.target.result;
        console.log('Image loaded, data URL length:', imageUrl?.length ?? 0);

        // Validate that we got a valid data URL
        if (!imageUrl || !imageUrl.startsWith('data:image/')) {
          console.error('Invalid data URL:', imageUrl);
          alert('Failed to read the image file. Please try again.');
          return;
        }

        // Measure image natural size so we can create a larger frame on the canvas
        const img = new Image();
        img.onload = async () => {
          try {
            const naturalW = img.naturalWidth || img.width;
            const naturalH = img.naturalHeight || img.height;
            const maxW = Math.round(activePage.width * 0.85);
            const maxH = Math.round(activePage.height * 0.85);
            const scale = Math.min(1, maxW / naturalW, maxH / naturalH);
            const width = Math.max(1, Math.round(naturalW * scale));
            const height = Math.max(1, Math.round(naturalH * scale));
            const x = Math.round((activePage.width - width) / 2);
            const y = Math.round((activePage.height - height) / 2);

            // Create image layer with the uploaded image (use data URL for canvas)
            // Default to a "Fill" treatment like Canva so the entire frame is covered
            const layer = createLayer('image', activePage, {
              name: file.name || 'Uploaded image',
              content: imageUrl,
              metadata: { objectFit: 'cover', imageScale: 1, imageOffsetX: 0, imageOffsetY: 0 },
            });

            // Override frame to make the image larger on canvas
            layer.frame = { x, y, width, height, rotation: 0 };

            if (layer.frame) {
              layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
            }

            try {
              dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
              console.log('Layer dispatched to store');
            } catch (dispatchError) {
              console.error('Error dispatching layer:', dispatchError);
              alert('Failed to add image to canvas. Please try again.');
            }

            // Persist the file to IndexedDB and add to recent images for the sidebar
            try {
              const dbModule = await import('../../utils/recentImagesDB');
              const id = `recent-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
              await dbModule.saveImage(id, file.name, file);
              dbModule.pruneOld(10).catch(() => {});
              const objectUrl = URL.createObjectURL(file);
              dispatch({ type: 'ADD_RECENTLY_UPLOADED_IMAGE', dataUrl: objectUrl, fileName: file.name, id });
            } catch (err) {
              console.error('Failed to persist or dispatch recent image from sidebar:', err);
              // Fallback: do nothing — layer was already added
            }
          } catch (err) {
            console.error('Error creating layer from uploaded image:', err);
            alert('An error occurred while processing the image. Please try again.');
          }
        };
        img.onerror = () => {
          console.error('Failed to load image for sizing');
          alert('Uploaded image could not be loaded. Try a different file.');
        };
        img.src = imageUrl;
      } catch (error) {
        console.error('Error processing uploaded image:', error);
        alert('An error occurred while processing the image. Please try again.');
      }
    };

    reader.onerror = () => {
      console.error('Error reading file');
      alert('Failed to read the image file. Please try again.');
    };

    console.log('Starting to read file as data URL');
    reader.readAsDataURL(file);

    // Reset the input so the same file can be selected again
    event.target.value = '';
  };

  const handleUseRecentFromSidebar = (image) => {
    if (!activePage) return;
      try {
      // Measure the image to size it larger in the canvas
      const img = new Image();
      img.onload = () => {
        const naturalW = img.naturalWidth || img.width;
        const naturalH = img.naturalHeight || img.height;
        const maxW = Math.round(activePage.width * 0.85);
        const maxH = Math.round(activePage.height * 0.85);
        const scale = Math.min(1, maxW / naturalW, maxH / naturalH);
        const width = Math.max(1, Math.round(naturalW * scale));
        const height = Math.max(1, Math.round(naturalH * scale));
        const x = Math.round((activePage.width - width) / 2);
        const y = Math.round((activePage.height - height) / 2);

        const layer = createLayer('image', activePage, {
          name: image.fileName || 'Uploaded image',
          content: image.dataUrl,
          metadata: { objectFit: 'cover', imageScale: 1, imageOffsetX: 0, imageOffsetY: 0 },
        });
        layer.frame = { x, y, width, height, rotation: 0 };

        if (layer.frame) {
          layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
        }

        dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
      };
      img.onerror = () => {
        // fallback: create a default-sized layer
        const layer = createLayer('image', activePage, {
          name: image.fileName || 'Uploaded image',
          content: image.dataUrl,
          metadata: { objectFit: 'cover', imageScale: 1, imageOffsetX: 0, imageOffsetY: 0 },
        });
        dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
      };
      img.src = image.dataUrl;
    } catch (err) {
      console.error('Failed to add recent image to canvas:', err);
      alert('Could not add image to canvas. Check console for details.');
    }
  };

  const fetchPhotos = useCallback(async (query, pageNumber) => {
    const trimmedQuery = query?.trim();
    if (!trimmedQuery) {
      return { items: [], hasMore: false };
    }

    // Fetch Unsplash and Pixabay in parallel, then merge results.
    const perPageUnsplash = ITEMS_PER_PAGE.unsplash;
    const filterParam = selectedFilter !== 'all' ? `&content_filter=${selectedFilter}` : '';
    const unsplashPromise = fetch(`https://api.unsplash.com/search/photos?query=${encodeURIComponent(trimmedQuery)}&client_id=iFpUZ_6aTnLGz0Voz0MYlprq9i_RBl83ux9DzV6EMOs&per_page=${perPageUnsplash}&page=${pageNumber}${filterParam}`)
      .then(async (response) => {
        if (!response.ok) throw new Error(`Unsplash API error: ${response.status}`);
        const data = await response.json();
        const formatted = normalizeUnsplashResults(data.results || []);
        const totalPages = data.total_pages ?? (formatted.length === perPageUnsplash ? pageNumber + 1 : pageNumber);
        return { items: formatted, hasMore: pageNumber < totalPages && formatted.length > 0 };
      })
      .catch((err) => {
        console.warn('Unsplash fetch failed', err);
        return { items: [], hasMore: false };
      });

    const perPagePixabay = ITEMS_PER_PAGE.pixabay;
    const pixabayRule = PIXABAY_FILTER_RULES[selectedFilter] ?? PIXABAY_FILTER_RULES.all;
    const queryPieces = [trimmedQuery, pixabayRule?.querySuffix].filter(Boolean);
    const combinedQuery = queryPieces.join(' ');
    const params = new URLSearchParams({
      key: PIXABAY_API_KEY,
      q: combinedQuery || trimmedQuery,
      safesearch: 'true',
      page: String(pageNumber),
      per_page: String(perPagePixabay),
    });

    if (pixabayRule?.imageType && pixabayRule.imageType !== 'all') {
      params.set('image_type', pixabayRule.imageType);
    }
    if (pixabayRule?.category) {
      params.set('category', pixabayRule.category);
    }
    if (pixabayRule?.order) {
      params.set('order', pixabayRule.order);
    }

    const pixabayPromise = fetch(`https://pixabay.com/api/?${params.toString()}`)
      .then(async (response) => {
        if (!response.ok) throw new Error(`Pixabay API error: ${response.status}`);
        const data = await response.json();
        const formatted = normalizePixabayResults(data.hits || []);
        const cappedTotal = Math.min(data.totalHits ?? 0, 500);
        const fetchedCount = (pageNumber - 1) * perPagePixabay + formatted.length;
        return { items: formatted, hasMore: cappedTotal > 0 && fetchedCount < cappedTotal };
      })
      .catch((err) => {
        console.warn('Pixabay fetch failed', err);
        return { items: [], hasMore: false };
      });

    const [unsplashResult, pixabayResult] = await Promise.all([unsplashPromise, pixabayPromise]);
    const mergedItems = [...unsplashResult.items, ...pixabayResult.items];
    const hasMoreCombined = (unsplashResult.hasMore || pixabayResult.hasMore) && mergedItems.length > 0;

    return {
      items: mergedItems,
      hasMore: hasMoreCombined,
    };
  }, [selectedFilter]);

  const handleSearch = useCallback(async (rawQuery) => {
    const trimmedQuery = (rawQuery ?? '').trim();
    if (!trimmedQuery) return;
    hasTriggeredSearchRef.current = true;
    activeSearchQueryRef.current = trimmedQuery;
    setIsSearching(true);
    setCurrentPage(1);
    currentPageRef.current = 1;
    setHasMore(true);
    try {
      const { items, hasMore: nextHasMore } = await fetchPhotos(trimmedQuery, 1);
      setSearchResults(items);
      const nextPage = items.length > 0 ? 2 : 1;
      setCurrentPage(nextPage);
      currentPageRef.current = nextPage;
      setHasMore(nextHasMore);
    } catch (error) {
      console.error('Search failed:', error);
      alert('Search failed. Please try again.');
      setSearchResults([]);
      setHasMore(false);
    } finally {
      setIsSearching(false);
    }
  }, [fetchPhotos]);

  const loadMoreImages = useCallback(async () => {
    if (isLoadingMore || !hasMore) {
      return;
    }
    const activeQuery = activeSearchQueryRef.current.trim();
    if (!activeQuery) {
      return;
    }
    setIsLoadingMore(true);
    try {
      const { items, hasMore: nextHasMore } = await fetchPhotos(activeQuery, currentPageRef.current);
      if (items.length > 0) {
        setSearchResults((prev) => [...prev, ...items]);
        const nextPage = currentPageRef.current + 1;
        setCurrentPage(nextPage);
        currentPageRef.current = nextPage;
      }
      setHasMore(nextHasMore && items.length > 0);
    } catch (error) {
      console.error('Load more failed:', error);
      alert('Failed to load more images. Please try again.');
      setHasMore(false);
    } finally {
      setIsLoadingMore(false);
    }
  }, [fetchPhotos, hasMore, isLoadingMore]);

  const loadMoreImagesRef = useRef(loadMoreImages);

  useEffect(() => {
    loadMoreImagesRef.current = loadMoreImages;
  }, [loadMoreImages]);

  const fetchIcons = useCallback(async (query, pageNumber) => {
    const trimmedQuery = query?.trim();
    if (!trimmedQuery) {
      return { items: [], hasMore: false };
    }

    const perPage = ITEMS_PER_PAGE.flaticon;
    console.log('Fetching icons:', { query: trimmedQuery, pageNumber, perPage });

    // Try reliable icon APIs
    const apis = [
      {
        name: 'Iconify',
        searchUrl: `https://api.iconify.design/search?query=${encodeURIComponent(trimmedQuery)}&limit=${perPage}&start=${(pageNumber - 1) * perPage}`,
        noAuth: true,
        type: 'iconify'
      },
      {
        name: 'Simple Icons Search',
        searchUrl: `https://api.github.com/search/code?q=${encodeURIComponent(trimmedQuery)}+repo:simple-icons/simple-icons&per_page=${Math.min(perPage, 30)}&page=${pageNumber}`,
        noAuth: true,
        type: 'github'
      }
    ];

    for (const api of apis) {
      try {
        console.log(`Trying ${api.name} API...`);

        const searchResponse = await fetch(api.searchUrl);

        if (!searchResponse.ok) {
          const errorText = await searchResponse.text();
          console.log(`${api.name} search failed:`, searchResponse.status, errorText);
          continue; // Try next API
        }

        const searchData = await searchResponse.json();
        console.log(`${api.name} search successful:`, searchData);

        // Format results based on API
        let icons = [];
        let totalItems = 0;

        if (api.name === 'Iconify') {
          icons = searchData?.icons || [];
          totalItems = searchData?.total || 0;

          // Iconify returns a lot of results, limit to reasonable amount for UX
          const maxResults = 1000; // Prevent loading too many icons
          totalItems = Math.min(totalItems, maxResults);
        } else if (api.name === 'Simple Icons Search') {
          // GitHub search results
          icons = searchData.items || [];
          totalItems = searchData.total_count || 0;
        }

        const formattedResults = icons.map((icon, index) => {
          let thumbUrl = '';
          let previewUrl = '';
          let downloadUrl = '';
          let description = '';
          let credit = '';

          if (api.name === 'Iconify') {
            // Iconify returns icon names like "mdi:home" or "fa:heart"
            const [prefix, name] = icon.split(':');
            thumbUrl = `https://api.iconify.design/${icon}.svg?width=64&height=64`;
            previewUrl = `https://api.iconify.design/${icon}.svg?width=128&height=128`;
            downloadUrl = `https://api.iconify.design/${icon}.svg`;
            description = name.replace(/([A-Z])/g, ' $1').trim() || icon;
            credit = `Iconify (${prefix})`;
          } else if (api.name === 'Simple Icons Search') {
            // GitHub code search returns items with path or name
            let iconName = '';
            if (icon.path) {
              iconName = icon.path.split('/').pop().replace('.svg', '');
            }
            iconName = iconName || icon.name || icon.path?.split('/').pop() || `icon-${index}`;
            // simple-icons package uses lowercased names with no spaces
            const normalized = String(iconName).toLowerCase().replace(/[^a-z0-9]/g, '');
            thumbUrl = `https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/${encodeURIComponent(normalized)}.svg`;
            previewUrl = thumbUrl;
            downloadUrl = thumbUrl;
            description = iconName.replace(/([A-Z])/g, ' $1').trim() || 'Simple Icon';
            credit = 'Simple Icons';
          }

          return {
            id: `${api.name.toLowerCase()}-${icon}-${index}`,
            thumbUrl,
            previewUrl,
            downloadUrl,
            description,
            provider: api.name.toLowerCase(),
            providerLabel: api.name,
            credit,
            raw: icon,
          };
        }).filter(icon => icon.thumbUrl); // Only include icons with valid URLs

        if (formattedResults.length > 0) {
          console.log(`Returning ${formattedResults.length} results from ${api.name}`);
          const totalPages = Math.ceil(totalItems / perPage);
          const hasMoreResults = pageNumber < totalPages && formattedResults.length === perPage && totalItems > (pageNumber * perPage);

          console.log(`Pagination info: page ${pageNumber}/${totalPages}, hasMore: ${hasMoreResults}, totalItems: ${totalItems}`);

          return {
            items: formattedResults,
            hasMore: hasMoreResults,
          };
        } else {
          console.log(`${api.name} returned no valid results`);
        }

      } catch (error) {
        console.log(`${api.name} API error:`, error.message);
        continue; // Try next API
      }
    }

    // If all APIs fail, provide fallback icons that always work
    console.log('All APIs failed, providing fallback icons');
    const commonIcons = [
      { name: 'heart', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/heart.svg', tags: ['love', 'favorite', 'like'] },
      { name: 'star', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/star.svg', tags: ['favorite', 'rating', 'bookmark'] },
      { name: 'home', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/home.svg', tags: ['house', 'building', 'property'] },
      { name: 'user', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/user.svg', tags: ['person', 'profile', 'account'] },
      { name: 'search', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/search.svg', tags: ['find', 'magnify', 'lookup'] },
      { name: 'settings', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/settings.svg', tags: ['gear', 'config', 'preferences'] },
      { name: 'mail', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/mail.svg', tags: ['email', 'message', 'envelope'] },
      { name: 'phone', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/phone.svg', tags: ['call', 'contact', 'telephone'] },
      { name: 'camera', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/camera.svg', tags: ['photo', 'picture', 'image'] },
      { name: 'music', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/music.svg', tags: ['audio', 'sound', 'note'] },
      { name: 'play', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/play.svg', tags: ['start', 'begin', 'media'] },
      { name: 'pause', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/pause.svg', tags: ['stop', 'wait', 'media'] },
      { name: 'arrowright', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/arrowright.svg', tags: ['next', 'forward', 'direction'] },
      { name: 'arrowleft', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/arrowleft.svg', tags: ['back', 'previous', 'direction'] },
      { name: 'check', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/check.svg', tags: ['yes', 'confirm', 'tick'] },
      { name: 'close', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/close.svg', tags: ['x', 'cancel', 'exit'] },
      { name: 'plus', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/plus.svg', tags: ['add', 'new', 'create'] },
      { name: 'minus', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/minus.svg', tags: ['remove', 'subtract', 'delete'] },
      { name: 'menu', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/menu.svg', tags: ['hamburger', 'navigation', 'list'] },
      { name: 'calendar', url: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/calendar.svg', tags: ['date', 'schedule', 'event'] },
    ];

    // Filter fallback icons based on search query
    const filteredFallbacks = commonIcons.filter(icon =>
      icon.name.toLowerCase().includes(trimmedQuery.toLowerCase()) ||
      icon.tags.some(tag => tag.toLowerCase().includes(trimmedQuery.toLowerCase()))
    );

    const formattedFallbacks = filteredFallbacks.slice(0, Math.min(perPage, 20)).map((icon, index) => ({
      id: `fallback-${index}`,
      thumbUrl: icon.url,
      previewUrl: icon.url,
      downloadUrl: icon.url,
      description: icon.name.replace(/([A-Z])/g, ' $1').trim(),
      provider: 'fallback',
      providerLabel: 'Simple Icons',
      credit: 'Simple Icons',
      raw: icon,
    }));

    console.log(`Returning ${formattedFallbacks.length} filtered fallback icons for query "${trimmedQuery}"`);
    return {
      items: formattedFallbacks,
      hasMore: false,
    };
  }, []);

  const fetchShapes = useCallback(async (query, pageNumber) => {
    const trimmedQuery = query?.trim();
    if (!trimmedQuery) {
      return { items: [], hasMore: false };
    }

    const perPage = ITEMS_PER_PAGE.flaticon;
    console.log('Fetching shapes:', { query: trimmedQuery, pageNumber, perPage });

    // Try reliable icon APIs
    const apis = [
      {
        name: 'Iconify',
        searchUrl: `https://api.iconify.design/search?query=${encodeURIComponent(trimmedQuery)}&limit=${perPage}&start=${(pageNumber - 1) * perPage}`,
        noAuth: true,
        type: 'iconify'
      },
      {
        name: 'Simple Icons Search',
        searchUrl: `https://api.github.com/search/code?q=${encodeURIComponent(trimmedQuery)}+repo:simple-icons/simple-icons&per_page=${Math.min(perPage, 30)}&page=${pageNumber}`,
        noAuth: true,
        type: 'github'
      }
    ];

    for (const api of apis) {
      try {
        console.log(`Trying ${api.name} API for shapes...`);

        const searchResponse = await fetch(api.searchUrl);

        if (!searchResponse.ok) {
          const errorText = await searchResponse.text();
          console.log(`${api.name} search failed:`, searchResponse.status, errorText);
          continue; // Try next API
        }

        const searchData = await searchResponse.json();
        console.log(`${api.name} search successful:`, searchData);

        // Format results based on API
        let shapes = [];
        let totalItems = 0;

        if (api.name === 'Iconify') {
          shapes = searchData?.icons || [];
          totalItems = searchData?.total || 0;

          // Iconify returns a lot of results, limit to reasonable amount for UX
          const maxResults = 1000; // Prevent loading too many shapes
          totalItems = Math.min(totalItems, maxResults);
        } else if (api.name === 'Simple Icons Search') {
          // GitHub search results
          shapes = searchData.items || [];
          totalItems = searchData.total_count || 0;
        }

        const formattedResults = shapes.map((shape, index) => {
          let thumbUrl = '';
          let previewUrl = '';
          let downloadUrl = '';
          let description = '';
          let credit = '';

          if (api.name === 'Iconify') {
            // Iconify returns icon names like "mdi:home" or "fa:heart"
            const [prefix, name] = shape.split(':');
            thumbUrl = `https://api.iconify.design/${shape}.svg?width=64&height=64`;
            previewUrl = `https://api.iconify.design/${shape}.svg?width=128&height=128`;
            downloadUrl = `https://api.iconify.design/${shape}.svg`;
            description = name.replace(/([A-Z])/g, ' $1').trim() || shape;
            credit = `Iconify (${prefix})`;
          } else if (api.name === 'Simple Icons Search') {
            // GitHub code search returns items with path or name
            let shapeName = '';
            if (shape.path) {
              shapeName = shape.path.split('/').pop().replace('.svg', '');
            }
            shapeName = shapeName || shape.name || shape.path?.split('/').pop() || `shape-${index}`;
            // simple-icons package uses lowercased names with no spaces
            const normalized = String(shapeName).toLowerCase().replace(/[^a-z0-9]/g, '');
            thumbUrl = `https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/${encodeURIComponent(normalized)}.svg`;
            previewUrl = thumbUrl;
            downloadUrl = thumbUrl;
            description = shapeName.replace(/([A-Z])/g, ' $1').trim() || 'Shape';
            credit = 'Simple Icons';
          }

          return {
            id: `${api.name.toLowerCase()}-${shape}-${index}`,
            thumbUrl,
            previewUrl,
            downloadUrl,
            description,
            provider: api.name.toLowerCase(),
            providerLabel: api.name,
            credit,
            raw: shape,
          };
        }).filter(shape => shape.thumbUrl); // Only include shapes with valid URLs

        if (formattedResults.length > 0) {
          console.log(`Returning ${formattedResults.length} results from ${api.name}`);
          const totalPages = Math.ceil(totalItems / perPage);
          const hasMoreResults = pageNumber < totalPages && formattedResults.length === perPage && totalItems > (pageNumber * perPage);

          console.log(`Pagination info: page ${pageNumber}/${totalPages}, hasMore: ${hasMoreResults}, totalItems: ${totalItems}`);

          return {
            items: formattedResults,
            hasMore: hasMoreResults,
          };
        } else {
          console.log(`${api.name} returned no valid results`);
        }

      } catch (error) {
        console.log(`${api.name} API error:`, error.message);
        continue; // Try next API
      }
    }

    // If all APIs fail, provide fallback shapes that always work
    console.log('All APIs failed, providing fallback shapes');
    const commonShapes = [
      {
        name: 'square',
        variant: 'rectangle',
        maskVariant: 'rectangle',
        url: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQ4IiBoZWlnaHQ9IjQ4IiB4PSI4IiB5PSI4IiBmaWxsPSIjMzc0MTUxIiBzdHJva2U9IiMxYTFhMWEiIHN0cm9rZS13aWR0aD0iMiIvPgo8L3N2Zz4=',
        tags: ['rectangle', 'square', 'box'],
        placeholderFill: DEFAULT_SHAPE_PLACEHOLDER_FILL,
        placeholderStroke: DEFAULT_SHAPE_PLACEHOLDER_STROKE,
      },
      {
        name: 'circle',
        variant: 'circle',
        maskVariant: 'circle',
        url: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGNpcmNsZSBjeD0iMzIiIGN5PSIzMiIgcj0iMjQiIGZpbGw9IiMzNzQxNTEiIHN0cm9rZT0iIzFhMWEwYSIgc3Ryb2tlLXdpZHRoPSIyIi8+Cjwvc3ZnPg==',
        tags: ['circle', 'round', 'oval'],
        placeholderFill: DEFAULT_SHAPE_PLACEHOLDER_FILL,
        placeholderStroke: DEFAULT_SHAPE_PLACEHOLDER_STROKE,
      },
      {
        name: 'triangle',
        variant: 'polygon',
        maskVariant: 'triangle',
        url: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIzMiw4IDgsNTYgNTYsNTYiIGZpbGw9IiMzNzQxNTEiIHN0cm9rZT0iIzFhMWEwYSIgc3Ryb2tlLXdpZHRoPSIyIi8+Cjwvc3ZnPg==',
        tags: ['triangle', 'polygon', 'geometric'],
        placeholderFill: DEFAULT_SHAPE_PLACEHOLDER_FILL,
        placeholderStroke: DEFAULT_SHAPE_PLACEHOLDER_STROKE,
      },
      {
        name: 'star',
        variant: 'polygon',
        maskVariant: 'star',
        url: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIyMCwxNiAzMiwzNiA0MiwzNiA0OCwzNiA0OCwxNiAzOCwxNiAzOCwyNiAyMCwyNiIgZmlsbD0iIzM3NDE1MSIgc3Ryb2tlPSIjMWExYTBhIiBzdHJva2Utd2lkdGg9IjIiLz4KPC9zdmc+',
        tags: ['star', 'rating', 'favorite'],
        placeholderFill: DEFAULT_SHAPE_PLACEHOLDER_FILL,
        placeholderStroke: DEFAULT_SHAPE_PLACEHOLDER_STROKE,
      },
      {
        name: 'hexagon',
        variant: 'polygon',
        maskVariant: 'hexagon',
        url: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIxNiwyMCA4LDM2IDI0LDUyIDQwLDUyIDQ4LDM2IDQwLDIwIiBmaWxsPSIjMzc0MTUxIiBzdHJva2U9IiMxYTFhMWEiIHN0cm9rZS13aWR0aD0iMiIvPgo8L3N2Zz4=',
        tags: ['hexagon', 'polygon', 'geometric'],
        placeholderFill: DEFAULT_SHAPE_PLACEHOLDER_FILL,
        placeholderStroke: DEFAULT_SHAPE_PLACEHOLDER_STROKE,
      },
      {
        name: 'diamond',
        variant: 'polygon',
        maskVariant: 'diamond',
        url: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjQiIGhlaWdodD0iNjQiIHZpZXdCb3g9IjAgMCA2NCA2NCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBvbHlnb24gcG9pbnRzPSIzMiwyMCAyMCw4IDQ0LDg1MiA0NCIgZmlsbD0iIzM3NDE1MSIgc3Ryb2tlPSIjMWExYTBhIiBzdHJva2Utd2lkdGg9IjIiLz4KPC9zdmc+',
        tags: ['diamond', 'rhombus', 'geometric'],
        placeholderFill: DEFAULT_SHAPE_PLACEHOLDER_FILL,
        placeholderStroke: DEFAULT_SHAPE_PLACEHOLDER_STROKE,
      },
    ];

    // Filter fallback shapes based on search query
    const filteredFallbacks = commonShapes.filter(shape =>
      shape.name.toLowerCase().includes(trimmedQuery.toLowerCase()) ||
      shape.tags.some(tag => tag.toLowerCase().includes(trimmedQuery.toLowerCase()))
    );

    const formattedFallbacks = filteredFallbacks.slice(0, Math.min(perPage, 20)).map((shape, index) => ({
      id: `fallback-shape-${index}`,
      thumbUrl: shape.url,
      previewUrl: shape.url,
      downloadUrl: shape.url,
      description: shape.name.replace(/([A-Z])/g, ' $1').trim(),
      provider: 'fallback',
      providerLabel: 'Basic Shapes',
      credit: 'Basic Shapes',
      variant: shape.variant,
      maskVariant: shape.maskVariant,
      placeholderFill: shape.placeholderFill,
      placeholderStroke: shape.placeholderStroke,
      raw: shape,
    }));

    console.log(`Returning ${formattedFallbacks.length} filtered fallback shapes for query "${trimmedQuery}"`);
    return {
      items: formattedFallbacks,
      hasMore: false,
    };
  }, []);

  const handleShapeSearch = useCallback(async (rawQuery) => {
    const trimmedQuery = (rawQuery ?? '').trim();

    const defaultMatches = searchDefaultShapes(trimmedQuery);
    setShapeSearchResults(defaultMatches);

    // If empty query, reset to default shapes
    if (!trimmedQuery) {
      hasTriggeredShapeSearchRef.current = false;
      setShapeCurrentPage(1);
      shapeCurrentPageRef.current = 1;
      setHasMoreShapes(false);
      return;
    }

    hasTriggeredShapeSearchRef.current = true;
    activeShapeSearchQueryRef.current = trimmedQuery;
    setIsSearchingShapes(true);
    setShapeCurrentPage(1);
    shapeCurrentPageRef.current = 1;
    setHasMoreShapes(true);
    try {
      const { items, hasMore: nextHasMore } = await fetchShapes(trimmedQuery, 1);
      if (items.length > 0) {
        setShapeSearchResults((prev) => {
          const seen = new Set(prev.map((shape) => shape.id));
          const merged = [...prev];
          for (const item of items) {
            if (!seen.has(item.id)) {
              merged.push(item);
            }
          }
          return merged;
        });
      }
      const nextPage = items.length > 0 ? 2 : 1;
      setShapeCurrentPage(nextPage);
      shapeCurrentPageRef.current = nextPage;
      setHasMoreShapes(nextHasMore);
    } catch (error) {
      console.error('Shape search failed:', error);
      // Even on error, default matches still displayed; optional warning for debugging
      console.error('Showing default shape results due to provider failure.');
      setHasMoreShapes(false);
    } finally {
      setIsSearchingShapes(false);
    }
  }, [fetchShapes]);

  const loadMoreShapes = useCallback(async () => {
    if (isLoadingMoreShapes || !hasMoreShapes) {
      return;
    }
    const activeQuery = activeShapeSearchQueryRef.current.trim();
    if (!activeQuery) {
      return;
    }
    setIsLoadingMoreShapes(true);
    try {
      const { items, hasMore: nextHasMore } = await fetchShapes(activeQuery, shapeCurrentPageRef.current);
      if (items.length > 0) {
        setShapeSearchResults((prev) => {
          const seen = new Set(prev.map((shape) => shape.id));
          const merged = [...prev];
          for (const item of items) {
            if (!seen.has(item.id)) {
              merged.push(item);
            }
          }
          return merged;
        });
        const nextPage = shapeCurrentPageRef.current + 1;
        setShapeCurrentPage(nextPage);
        shapeCurrentPageRef.current = nextPage;
      }
      setHasMoreShapes(nextHasMore && items.length > 0);
    } catch (error) {
      console.error('Load more shapes failed:', error);
      alert('Unable to load more shapes. Using available shapes.');
      setHasMoreShapes(false);
    } finally {
      setIsLoadingMoreShapes(false);
    }
  }, [fetchShapes, hasMoreShapes, isLoadingMoreShapes]);

  const loadMoreShapesRef = useRef(loadMoreShapes);

  useEffect(() => {
    loadMoreShapesRef.current = loadMoreShapes;
  }, [loadMoreShapes]);

  // IntersectionObserver for shape infinite scroll
  useEffect(() => {
    if (shapeSearchResults.length === 0 || !hasMoreShapes) {
      return;
    }

    const loadingIndicator = document.querySelector('.shape-loading-indicator');
    const scrollContainer = document.querySelector('.builder-sidebar__shape-results');
    
    if (!loadingIndicator || !scrollContainer) {
      console.log('Shape loading indicator or scroll container not found');
      return;
    }

    console.log('Setting up IntersectionObserver for shape loading indicator');

    const observer = new IntersectionObserver(
      (entries) => {
        const [entry] = entries;
        console.log('Shape IntersectionObserver triggered:', {
          isIntersecting: entry.isIntersecting,
          isLoadingMoreShapes,
          hasMoreShapes,
          shapeSearchResultsLength: shapeSearchResults.length
        });

        if (entry.isIntersecting && !isLoadingMoreShapes && hasMoreShapes) {
          console.log('Loading more shapes via IntersectionObserver...');
          loadMoreShapesRef.current();
        }
      },
      {
        root: scrollContainer, // Use the scrollable container as root
        rootMargin: '50px',
        threshold: 0.1,
      }
    );

    observer.observe(loadingIndicator);

    return () => {
      console.log('Cleaning up shape IntersectionObserver');
      observer.disconnect();
    };
  }, [shapeSearchResults.length, hasMoreShapes, isLoadingMoreShapes]);

  // Prevent scroll propagation from shape results to parent containers
  useEffect(() => {
    const shapeResultsContainer = document.querySelector('.builder-sidebar__shape-results');
    if (!shapeResultsContainer) return;

    const preventScrollPropagation = (e) => {
      const { scrollTop, scrollHeight, clientHeight } = e.target;
      const isAtTop = scrollTop === 0;
      const isAtBottom = scrollTop + clientHeight >= scrollHeight;

      // Allow scroll propagation only if at boundaries and scrolling in the same direction
      if ((isAtTop && e.deltaY < 0) || (isAtBottom && e.deltaY > 0)) {
        return;
      }

      // Prevent the scroll event from bubbling up
      e.stopPropagation();
    };

    shapeResultsContainer.addEventListener('wheel', preventScrollPropagation, { passive: true });
    shapeResultsContainer.addEventListener('touchmove', preventScrollPropagation, { passive: true });

    return () => {
      shapeResultsContainer.removeEventListener('wheel', preventScrollPropagation);
      shapeResultsContainer.removeEventListener('touchmove', preventScrollPropagation);
    };
  }, []);

  // Manual load more function for button click
  const handleLoadMoreShapes = useCallback(() => {
    if (!isLoadingMoreShapes && hasMoreShapes) {
      loadMoreShapesRef.current();
    }
  }, [isLoadingMoreShapes, hasMoreShapes]);

  const handleIconSearch = useCallback(async (rawQuery) => {
    const trimmedQuery = (rawQuery ?? '').trim();

    // If empty query, reset to default icons
    if (!trimmedQuery) {
      setIconSearchResults([
        {
          id: 'default-heart',
          thumbUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/heart.svg',
          previewUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/heart.svg',
          downloadUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/heart.svg',
          description: 'Heart',
          provider: 'default',
          providerLabel: 'Simple Icons',
          credit: 'Simple Icons',
        },
        {
          id: 'default-star',
          thumbUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/star.svg',
          previewUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/star.svg',
          downloadUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/star.svg',
          description: 'Star',
          provider: 'default',
          providerLabel: 'Simple Icons',
          credit: 'Simple Icons',
        },
        {
          id: 'default-home',
          thumbUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/home.svg',
          previewUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/home.svg',
          downloadUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/home.svg',
          description: 'Home',
          provider: 'default',
          providerLabel: 'Simple Icons',
          credit: 'Simple Icons',
        },
        {
          id: 'default-user',
          thumbUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/user.svg',
          previewUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/user.svg',
          downloadUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/user.svg',
          description: 'User',
          provider: 'default',
          providerLabel: 'Simple Icons',
          credit: 'Simple Icons',
        },
        {
          id: 'default-search',
          thumbUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/search.svg',
          previewUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/search.svg',
          downloadUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/search.svg',
          description: 'Search',
          provider: 'default',
          providerLabel: 'Simple Icons',
          credit: 'Simple Icons',
        },
        {
          id: 'default-settings',
          thumbUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/settings.svg',
          previewUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/settings.svg',
          downloadUrl: 'https://cdn.jsdelivr.net/npm/simple-icons@v9/icons/settings.svg',
          description: 'Settings',
          provider: 'default',
          providerLabel: 'Simple Icons',
          credit: 'Simple Icons',
        },
      ]);
      hasTriggeredIconSearchRef.current = false;
      setIconCurrentPage(1);
      iconCurrentPageRef.current = 1;
      setHasMoreIcons(false);
      return;
    }

    hasTriggeredIconSearchRef.current = true;
    activeIconSearchQueryRef.current = trimmedQuery;
    setIsSearchingIcons(true);
    setIconCurrentPage(1);
    iconCurrentPageRef.current = 1;
    setHasMoreIcons(true);
    try {
      const { items, hasMore: nextHasMore } = await fetchIcons(trimmedQuery, 1);
      setIconSearchResults(items);
      const nextPage = items.length > 0 ? 2 : 1;
      setIconCurrentPage(nextPage);
      iconCurrentPageRef.current = nextPage;
      setHasMoreIcons(nextHasMore);
    } catch (error) {
      console.error('Icon search failed:', error);
      // Even on error, we should have fallback icons, but let's show a warning
      alert('Some icon providers are unavailable. Showing fallback icons instead.');
      // Don't clear results - fetchIcons should always return fallback icons
      setHasMoreIcons(false);
    } finally {
      setIsSearchingIcons(false);
    }
  }, [fetchIcons]);

  const loadMoreIcons = useCallback(async () => {
    if (isLoadingMoreIcons || !hasMoreIcons) {
      return;
    }
    const activeQuery = activeIconSearchQueryRef.current.trim();
    if (!activeQuery) {
      return;
    }
    setIsLoadingMoreIcons(true);
    try {
      const { items, hasMore: nextHasMore } = await fetchIcons(activeQuery, iconCurrentPageRef.current);
      if (items.length > 0) {
        setIconSearchResults((prev) => [...prev, ...items]);
        const nextPage = iconCurrentPageRef.current + 1;
        setIconCurrentPage(nextPage);
        iconCurrentPageRef.current = nextPage;
      }
      setHasMoreIcons(nextHasMore && items.length > 0);
    } catch (error) {
      console.error('Load more icons failed:', error);
      alert('Unable to load more icons. Using available icons.');
      setHasMoreIcons(false);
    } finally {
      setIsLoadingMoreIcons(false);
    }
  }, [fetchIcons, hasMoreIcons, isLoadingMoreIcons]);

  const loadMoreIconsRef = useRef(loadMoreIcons);

  useEffect(() => {
    loadMoreIconsRef.current = loadMoreIcons;
  }, [loadMoreIcons]);

  // IntersectionObserver for icon infinite scroll
  useEffect(() => {
    if (iconSearchResults.length === 0 || !hasMoreIcons) {
      return;
    }

    const loadingIndicator = document.querySelector('.icon-loading-indicator');
    const scrollContainer = document.querySelector('.builder-sidebar__icon-results');
    
    if (!loadingIndicator || !scrollContainer) {
      console.log('Icon loading indicator or scroll container not found');
      return;
    }

    console.log('Setting up IntersectionObserver for icon loading indicator');

    const observer = new IntersectionObserver(
      (entries) => {
        const [entry] = entries;
        console.log('Icon IntersectionObserver triggered:', {
          isIntersecting: entry.isIntersecting,
          isLoadingMoreIcons,
          hasMoreIcons,
          iconSearchResultsLength: iconSearchResults.length
        });

        if (entry.isIntersecting && !isLoadingMoreIcons && hasMoreIcons) {
          console.log('Loading more icons via IntersectionObserver...');
          loadMoreIconsRef.current();
        }
      },
      {
        root: scrollContainer, // Use the scrollable container as root
        rootMargin: '50px',
        threshold: 0.1,
      }
    );

    observer.observe(loadingIndicator);

    return () => {
      console.log('Cleaning up icon IntersectionObserver');
      observer.disconnect();
    };
  }, [iconSearchResults.length, hasMoreIcons, isLoadingMoreIcons]);

  // Prevent scroll propagation from icon results to parent containers
  useEffect(() => {
    const iconResultsContainer = document.querySelector('.builder-sidebar__icon-results');
    if (!iconResultsContainer) return;

    const preventScrollPropagation = (e) => {
      const { scrollTop, scrollHeight, clientHeight } = e.target;
      const isAtTop = scrollTop === 0;
      const isAtBottom = scrollTop + clientHeight >= scrollHeight;

      // Allow scroll propagation only if at boundaries and scrolling in the same direction
      if ((isAtTop && e.deltaY < 0) || (isAtBottom && e.deltaY > 0)) {
        return;
      }

      // Prevent the scroll event from bubbling up
      e.stopPropagation();
    };

    iconResultsContainer.addEventListener('wheel', preventScrollPropagation, { passive: true });
    iconResultsContainer.addEventListener('touchmove', preventScrollPropagation, { passive: true });

    return () => {
      iconResultsContainer.removeEventListener('wheel', preventScrollPropagation);
      iconResultsContainer.removeEventListener('touchmove', preventScrollPropagation);
    };
  }, []);

  // Manual load more function for button click
  const handleLoadMoreIcons = useCallback(() => {
    if (!isLoadingMoreIcons && hasMoreIcons) {
      loadMoreIconsRef.current();
    }
  }, [isLoadingMoreIcons, hasMoreIcons]);

  const handleUseShape = async (shape) => {
    if (!activePage) return;

    // For shapes, we can either use the shape variant directly or load the icon
    if (shape.variant) {
      // Use the built-in shape variant - now supports image content
      handleAddShape(shape);
    } else {
      // Handle shape as an icon/image
      const sourceUrl = shape?.downloadUrl || shape?.previewUrl || shape?.thumbUrl;
      if (!sourceUrl) {
        alert('Unable to load the selected shape. Please choose another one.');
        return;
      }

      try {
        const response = await fetch(sourceUrl);
        if (!response.ok) {
          throw new Error(`Network error: ${response.status}`);
        }
        const blob = await response.blob();
        const reader = new FileReader();
        reader.onload = () => {
          const dataUrl = reader.result;
          const img = new Image();
          img.onload = () => {
            const naturalW = img.naturalWidth || img.width;
            const naturalH = img.naturalHeight || img.height;
            // Shapes should be smaller than photos, max 200px
            const maxW = Math.round(activePage.width * 0.2);
            const maxH = Math.round(activePage.height * 0.2);
            const scale = Math.min(1, maxW / naturalW, maxH / naturalH);
            const width = Math.max(1, Math.round(naturalW * scale));
            const height = Math.max(1, Math.round(naturalH * scale));
            const x = Math.round((activePage.width - width) / 2);
            const y = Math.round((activePage.height - height) / 2);
            const layer = createLayer('image', activePage, {
              name: shape?.description || 'Shape',
              content: dataUrl,
              metadata: {
                objectFit: 'contain',
                imageScale: 1,
                imageOffsetX: 0,
                imageOffsetY: 0,
                attribution: shape?.credit,
                isShape: true,
              },
            });
            layer.frame = { x, y, width, height, rotation: 0 };
            if (layer.frame) {
              layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
            }
            dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
          };
          img.src = dataUrl;
        };
        reader.readAsDataURL(blob);
      } catch (error) {
        console.error('Failed to load shape:', error);
        alert('Failed to load shape. Please try again.');
      }
    }
  };

  const handleShapeWithImage = async (variant) => {
    if (!activePage) return;

    // Trigger file input for image selection
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = async (event) => {
      const file = event.target.files[0];
      if (!file) return;

      // Check file size (limit to 10MB)
      const maxSize = 10 * 1024 * 1024;
      if (file.size > maxSize) {
        alert('Image file is too large. Please select an image smaller than 10MB.');
        return;
      }

      // Read the image file
      const reader = new FileReader();
      reader.onload = async (e) => {
        try {
          const imageUrl = e.target.result;

          // Validate the data URL
          if (!imageUrl || !imageUrl.startsWith('data:image/')) {
            alert('Failed to read the image file. Please try again.');
            return;
          }

          // Create shape with image content
          const normalization = SHAPE_VARIANT_NORMALIZATION[variant] || {};
          handleAddShape({
            variant,
            maskVariant: normalization.mask,
          }, imageUrl);

          // Persist the file to IndexedDB for recent images
          try {
            const dbModule = await import('../../utils/recentImagesDB');
            const id = `shape-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
            await dbModule.saveImage(id, file.name, file);
            dbModule.pruneOld(10).catch(() => {});
            const objectUrl = URL.createObjectURL(file);
            dispatch({ type: 'ADD_RECENTLY_UPLOADED_IMAGE', dataUrl: objectUrl, fileName: file.name, id });
          } catch (err) {
            console.error('Failed to persist shape image:', err);
          }
        } catch (error) {
          console.error('Error processing image for shape:', error);
          alert('An error occurred while processing the image. Please try again.');
        }
      };

      reader.onerror = () => {
        alert('Failed to read the image file. Please try again.');
      };

      reader.readAsDataURL(file);
    };

    input.click();
  };

  const generateColorPalette = useCallback(async ({ append = false } = {}) => {
    const displayQuery = paletteQuery.trim();
    const normalizedQuery = displayQuery.toLowerCase();

    if (append) {
      if (!currentPaletteSets.length || isGeneratingPalette || isLoadingMorePalettes) {
        return;
      }
      setIsLoadingMorePalettes(true);
      setPaletteStatus('Loading more palettes...');
    } else {
      resetCuratedPaletteQueue(normalizedQuery);
      setIsGeneratingPalette(true);
      setPaletteError('');
      setPaletteStatus('Generating palettes...');
    }

    try {
      const { paletteSets, usedFallback } = await fetchPaletteBatch({
        normalizedQuery,
        startIndex: append ? currentPaletteSets.length : 0,
      });

      setCurrentPaletteSets((prev) => (append ? [...prev, ...paletteSets] : paletteSets));

      if (!append) {
        setPaletteError('');
      }

      const statusMessage = displayQuery
        ? `Showing palettes for “${displayQuery}”`
        : '';
      setPaletteStatus(statusMessage);
    } catch (error) {
      console.error('Failed to generate color palettes:', error);
      if (append) {
        setPaletteError('Unable to load more palettes right now. Scroll again to retry.');
      } else {
        setPaletteError('Colormind is unavailable. Showing designer-picked palettes.');
        const fallbackSets = Array.from({ length: PALETTES_PER_BATCH }, () => pickRandomCuratedPalette() || {
          id: `fallback-${Math.random().toString(36).slice(2, 6)}`,
          label: 'Designer Fallback',
          colors: ['#e2e8f0', '#cbd5f5', '#a5b4fc', '#818cf8', '#4c1d95'],
          source: 'curated',
        });
        setCurrentPaletteSets(fallbackSets);
        setPaletteStatus(displayQuery ? `Showing curated palettes for “${displayQuery}”.` : 'Showing curated palettes.');
      }
    } finally {
      if (append) {
        setIsLoadingMorePalettes(false);
      } else {
        setIsGeneratingPalette(false);
      }
    }
  }, [
    paletteQuery,
    currentPaletteSets.length,
    fetchPaletteBatch,
    isGeneratingPalette,
    isLoadingMorePalettes,
    pickRandomCuratedPalette,
    resetCuratedPaletteQueue,
  ]);

  useEffect(() => {
    const listEl = paletteListRef.current;
    if (!listEl) {
      return undefined;
    }

    const handleScroll = () => {
      const threshold = 80;
      if (listEl.scrollTop + listEl.clientHeight >= listEl.scrollHeight - threshold) {
        generateColorPalette({ append: true });
      }
    };

    const handleWheel = (event) => {
      const atTop = listEl.scrollTop <= 0 && event.deltaY < 0;
      const atBottom = listEl.scrollTop + listEl.clientHeight >= listEl.scrollHeight - 1 && event.deltaY > 0;
      if (atTop || atBottom) {
        event.preventDefault();
        event.stopPropagation();
      }
    };

    listEl.addEventListener('scroll', handleScroll);
    listEl.addEventListener('wheel', handleWheel, { passive: false });

    return () => {
      listEl.removeEventListener('scroll', handleScroll);
      listEl.removeEventListener('wheel', handleWheel);
    };
  }, [generateColorPalette, currentPaletteSets.length, activeTool]);

  const applyColorToSelection = useCallback((color) => {
    if (!activePage) {
      return;
    }

    const targetLayerId = state.selectedLayerId;
    if (!targetLayerId) {
      setPaletteError('Select a layer on the canvas, then click a color swatch.');
      return;
    }

    const targetLayer = activePage.nodes?.find((node) => node.id === targetLayerId);
    if (!targetLayer) {
      setPaletteError('Select a layer on the canvas, then click a color swatch.');
      return;
    }

    dispatch({
      type: 'UPDATE_LAYER_PROPS',
      pageId: activePage.id,
      layerId: targetLayerId,
      props: { fill: color },
    });

    rememberColor(color);

    setPaletteError('');
  }, [activePage, dispatch, rememberColor, state.selectedLayerId]);

  const applyColorToBackground = useCallback((color) => {
    if (!activePage) {
      return;
    }

    dispatch({
      type: 'UPDATE_PAGE_PROPS',
      pageId: activePage.id,
      props: { background: color },
    });
    setPaletteError('');
  }, [activePage, dispatch]);

  const handleSolidColorImmediateApply = useCallback((value, alpha = 1) => {
    if (!value) {
      return;
    }
    const normalized = normalizeColorForInput(value);
    setBackgroundColorInput(normalized);
    setSolidPickerColor(normalized);
    setSolidHexDraft(normalized.toUpperCase());
    rememberColor(normalized);
    const colorWithAlpha = alpha < 1 ? `rgba(${hexToRgb(normalized)}, ${alpha})` : normalized;
    applyColorToBackground(colorWithAlpha);
  }, [applyColorToBackground, normalizeColorForInput, rememberColor]);

  const handleSolidHexInput = useCallback((rawValue) => {
    if (!rawValue) {
      setSolidHexDraft('');
      return;
    }
    const nextValue = (rawValue.startsWith('#') ? rawValue : `#${rawValue}`).toUpperCase();
    setSolidHexDraft(nextValue);
    if (/^#([0-9A-F]{3}|[0-9A-F]{6})$/.test(nextValue)) {
      handleSolidColorImmediateApply(nextValue, backgroundOpacity / 100);
    }
  }, [backgroundOpacity, handleSolidColorImmediateApply]);

  const handleHueSliderChange = useCallback((nextHue) => {
    const numericHue = Number(nextHue);
    setHueSliderValue(numericHue);
    const updatedHex = hslToHex(numericHue, 100, 50);
    setSolidPickerColor(updatedHex);
    setSolidHexDraft(updatedHex);
    handleSolidColorImmediateApply(updatedHex, backgroundOpacity / 100);
  }, [backgroundOpacity, handleSolidColorImmediateApply]);

  const handleColorTabChange = useCallback((tab) => {
    setColorPickerTab(tab);
    if (tab === 'gradient') {
      applyColorToBackground(buildGradientValue(selectedGradientStyle, gradientColorStops, gradientAngle, backgroundOpacity / 100));
      return;
    }
    handleSolidColorImmediateApply(solidPickerColor, backgroundOpacity / 100);
  }, [applyColorToBackground, backgroundOpacity, buildGradientValue, gradientAngle, gradientColorStops, handleSolidColorImmediateApply, selectedGradientStyle, solidPickerColor]);

  const handleGradientColorChange = useCallback((index, value) => {
    const normalized = normalizeColorForInput(value);
    setGradientColorStops((prevStops) => {
      const nextStops = [...prevStops];
      nextStops[index] = normalized;
      if (colorPickerTab === 'gradient') {
        applyColorToBackground(
          buildGradientValue(selectedGradientStyle, nextStops, gradientAngle, backgroundOpacity / 100),
        );
      }
      return nextStops;
    });
    rememberColor(normalized);
  }, [applyColorToBackground, backgroundOpacity, buildGradientValue, colorPickerTab, gradientAngle, normalizeColorForInput, rememberColor, selectedGradientStyle]);

  const handleGradientStyleSelect = useCallback((styleId) => {
    setSelectedGradientStyle(styleId);
    setColorPickerTab('gradient');
    applyColorToBackground(buildGradientValue(styleId, gradientColorStops, gradientAngle, backgroundOpacity / 100));
  }, [applyColorToBackground, backgroundOpacity, buildGradientValue, gradientAngle, gradientColorStops]);

  const handleAddGradientStop = useCallback(() => {
    setGradientColorStops((prevStops) => {
      if (prevStops.length >= 3) {
        return prevStops;
      }
      const duplicate = prevStops[prevStops.length - 1] ?? '#ffffff';
      return [...prevStops, duplicate];
    });
  }, []);

  const handleGradientInputChange = useCallback((index, rawValue) => {
    const cleaned = (rawValue ?? '').replace(/[^0-9a-fA-F]/g, '').slice(0, 6);
    const sanitized = `#${cleaned}`.toUpperCase();
    setGradientInputs((prev) => {
      const next = [...prev];
      next[index] = sanitized;
      return next;
    });
    if (/^#([0-9A-F]{6})$/.test(sanitized)) {
      handleGradientColorChange(index, sanitized);
    }
  }, [handleGradientColorChange]);

  const handleGradientAngleChange = useCallback((nextAngle) => {
    const numeric = Math.max(0, Math.min(360, Number(nextAngle)));
    setGradientAngle(numeric);
    if (colorPickerTab === 'gradient') {
      applyColorToBackground(
        buildGradientValue(selectedGradientStyle, gradientColorStops, numeric, backgroundOpacity / 100),
      );
    }
  }, [applyColorToBackground, backgroundOpacity, buildGradientValue, colorPickerTab, gradientColorStops, selectedGradientStyle]);

  const handleBackgroundOpacityChange = useCallback((nextValue) => {
    const numeric = Math.max(0, Math.min(100, Number(nextValue)));
    setBackgroundOpacity(numeric);
    if (colorPickerTab === 'gradient') {
      applyColorToBackground(
        buildGradientValue(selectedGradientStyle, gradientColorStops, gradientAngle, numeric / 100),
      );
    } else {
      handleSolidColorImmediateApply(solidPickerColor, numeric / 100);
    }
  }, [applyColorToBackground, buildGradientValue, colorPickerTab, gradientAngle, gradientColorStops, handleSolidColorImmediateApply, selectedGradientStyle, solidPickerColor]);

  const handleRecentColorClick = useCallback((color) => {
    if (colorPickerTab === 'solid') {
      handleSolidColorImmediateApply(color, backgroundOpacity / 100);
      return;
    }
    handleGradientColorChange(activeGradientStop, color);
  }, [activeGradientStop, backgroundOpacity, colorPickerTab, handleGradientColorChange, handleSolidColorImmediateApply]);

  const handleApplyBackground = useCallback(() => {
    if (colorPickerTab === 'gradient') {
      applyColorToBackground(
        buildGradientValue(selectedGradientStyle, gradientColorStops, gradientAngle, backgroundOpacity / 100),
      );
      gradientColorStops.forEach((stop) => rememberColor(stop));
      return;
    }
    handleSolidColorImmediateApply(solidPickerColor, backgroundOpacity / 100);
  }, [applyColorToBackground, backgroundOpacity, buildGradientValue, colorPickerTab, gradientAngle, gradientColorStops, handleSolidColorImmediateApply, rememberColor, selectedGradientStyle, solidPickerColor]);

  const handleDefaultGradientClick = useCallback((gradient) => {
    const match = gradient.match(/linear-gradient\(90deg, ([^,]+), ([^)]+)\)/);
    if (!match) return;
    const color1 = match[1].trim();
    const color2 = match[2].trim();
    setGradientColorStops([color1, color2]);
    setSelectedGradientStyle('linear');
    setGradientAngle(90);
    setBackgroundOpacity(100);
    setColorPickerTab('gradient');
    handleApplyBackground();
  }, [handleApplyBackground]);

  const handleDefaultSolidColorClick = useCallback((color) => {
    setSolidPickerColor(color);
    setSolidHexDraft(color);
    handleSolidColorImmediateApply(color, backgroundOpacity / 100);
  }, [backgroundOpacity, handleSolidColorImmediateApply]);

  const toggleColorModal = useCallback(() => {
    setIsColorModalOpen((prev) => !prev);
  }, []);

  const closeColorModal = useCallback(() => {
    setIsColorModalOpen(false);
  }, []);

  const renderPaletteGenerator = ({
    placeholder = 'Search palette type (e.g. birthday, pastel, wedding)',
    buttonLabel = 'Generate Colors',
    helperInstruction = 'Click a swatch to color the selected layer.',
    onApply = applyColorToSelection,
  } = {}) => (
    <>
      <form
        className="builder-sidebar__search palette-search"
        onSubmit={(event) => {
          event.preventDefault();
          generateColorPalette();
        }}
      >
        <input
          type="text"
          placeholder={placeholder}
          value={paletteQuery}
          onChange={(e) => setPaletteQuery(e.target.value)}
        />
        <button type="submit" disabled={isGeneratingPalette}>
          {isGeneratingPalette ? 'Generating...' : buttonLabel}
        </button>
      </form>
      {currentPaletteSets.length > 0 && (
        <div
          className="builder-sidebar__palette-list builder-sidebar__palette-scroll"
          ref={paletteListRef}
          role="region"
          aria-label="Generated palettes"
          tabIndex={0}
          style={{ maxHeight: '320px', overflowY: 'auto', paddingRight: '8px' }}
        >
          {currentPaletteSets.map((paletteSet, paletteIndex) => (
            <div key={paletteSet.id ?? `palette-${paletteIndex}`} className="builder-sidebar__palette">
              <div className="builder-sidebar__palette-header">
                <h3>{paletteSet.label ?? `Palette ${paletteIndex + 1}`}</h3>
                <span>
                  {paletteSet.source === 'colormind' ? 'AI generated palette' : 'Curated palette'} • {helperInstruction}
                </span>
              </div>
              <div className="builder-sidebar__palette-colors">
                {paletteSet.colors?.map((color, colorIndex) => (
                  <div className="builder-sidebar__palette-item" key={`${paletteIndex}-${colorIndex}`}>
                    <button
                      type="button"
                      className="builder-sidebar__palette-swatch"
                      style={{ backgroundColor: color }}
                      title={`Apply ${color}`}
                      onClick={() => onApply(color)}
                    ></button>
                    <span className="builder-sidebar__palette-code">{color?.toUpperCase?.() ?? color}</span>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      )}
      {paletteStatus && (
        <div className="builder-sidebar__hint" style={{ color: '#0f172a', fontWeight: 600 }}>
          {paletteStatus}
        </div>
      )}
      {paletteError && (
        <div className="builder-sidebar__hint" style={{ color: '#b45309', fontWeight: 600 }}>
          {paletteError}
        </div>
      )}
    </>
  );

  const ensureFontLoaded = useCallback((fontDescriptor = {}) => {
    const { family, weights } = fontDescriptor;
    if (!family) {
      return;
    }

    const normalizedWeights = Array.isArray(weights) && weights.length > 0
      ? Array.from(new Set(weights.map((weight) => {
        if (typeof weight === 'number') {
          return String(weight);
        }
        if (typeof weight === 'string') {
          const numericMatch = weight.match(/\d+/);
          if (numericMatch) {
            return numericMatch[0];
          }
          if (weight.toLowerCase().includes('bold')) {
            return '700';
          }
          return '400';
        }
        return '400';
      })))
      : ['400'];

    const weightsParam = normalizedWeights.length > 0 ? normalizedWeights.join(';') : '400';
    const fontKey = `${family}-${weightsParam}`;

    if (loadedFontsRef.current.has(fontKey)) {
      return;
    }

    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = `https://fonts.googleapis.com/css2?family=${encodeURIComponent(family)}:wght@${weightsParam}&display=swap`;
    document.head.appendChild(link);
    loadedFontsRef.current.add(fontKey);
  }, []);

  const handleApplyFontCombo = useCallback((combo) => {
    if (!activePage || !combo?.layers?.length) {
      return;
    }

    combo.layers.forEach((layerDef) => {
      ensureFontLoaded({ family: layerDef.family, weights: [layerDef.fontWeight ?? '400'] });
    });

    combo.layers.forEach((layerDef, index) => {
      const layer = createLayer('text', activePage, {
        name: `${combo.label ?? 'Font Combo'} ${layerDef.role ?? index + 1}`,
        content: layerDef.content,
        fontSize: layerDef.fontSize ?? 32,
        fontWeight: layerDef.fontWeight ?? '400',
        fontFamily: `${layerDef.family}, ${layerDef.fallback ?? 'sans-serif'}`,
        textAlign: layerDef.align ?? combo.align ?? 'center',
        textTransform: layerDef.transform ?? 'none',
        letterSpacing: layerDef.letterSpacing ?? 0,
      });

      if (layer.frame) {
        const baseOffset = layerDef.offsetY ?? index * 64;
        layer.frame = {
          ...layer.frame,
          width: layerDef.width ?? layer.frame.width,
          height: layerDef.height ?? layer.frame.height,
          x: layer.frame.x,
          y: layer.frame.y + baseOffset,
        };
        layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
      }

      dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
    });
  }, [activePage, dispatch, ensureFontLoaded, safeInsets]);

  useEffect(() => {
    styledPresets.forEach((preset) => {
      preset.layers?.forEach((layer) => {
        ensureFontLoaded({ family: layer.family, weights: [layer.fontWeight ?? '400'] });
      });
    });
  }, [styledPresets, ensureFontLoaded]);

  const handleUseIcon = async (icon) => {
    if (!activePage) return;

    const sourceUrl = icon?.downloadUrl || icon?.previewUrl || icon?.thumbUrl;
    if (!sourceUrl) {
      alert('Unable to load the selected icon. Please choose another one.');
      return;
    }

    try {
      const response = await fetch(sourceUrl);
      if (!response.ok) {
        throw new Error(`Network error: ${response.status}`);
      }
      const blob = await response.blob();
      const reader = new FileReader();
      reader.onload = () => {
        const dataUrl = reader.result;
        const img = new Image();
        img.onload = () => {
          const naturalW = img.naturalWidth || img.width;
          const naturalH = img.naturalHeight || img.height;
          // Icons should be smaller than photos, max 150px
          const maxW = Math.round(activePage.width * 0.15);
          const maxH = Math.round(activePage.height * 0.15);
          const scale = Math.min(1, maxW / naturalW, maxH / naturalH);
          const width = Math.max(1, Math.round(naturalW * scale));
          const height = Math.max(1, Math.round(naturalH * scale));
          const x = Math.round((activePage.width - width) / 2);
          const y = Math.round((activePage.height - height) / 2);
          const layer = createLayer('image', activePage, {
            name: icon?.description || 'Icon',
            content: dataUrl,
            metadata: {
              objectFit: 'contain',
              imageScale: 1,
              imageOffsetX: 0,
              imageOffsetY: 0,
              attribution: icon?.credit,
              isIcon: true,
            },
          });
          layer.frame = { x, y, width, height, rotation: 0 };
          if (layer.frame) {
            layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
          }
          dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
        };
        img.src = dataUrl;
      };
      reader.readAsDataURL(blob);
    } catch (error) {
      console.error('Failed to load icon:', error);
      alert('Failed to load icon. Please try again.');
    }
  };

  const fetchFonts = useCallback(async (query, pageNumber) => {
    const trimmedQuery = query?.trim();
    if (!trimmedQuery) {
      return { items: [], hasMore: false };
    }

    const perPage = 20; // Google Fonts API returns up to 100, but we'll paginate
    const startIndex = (pageNumber - 1) * perPage;

    try {
      // Use Google Fonts API
      const apiKey = process.env.REACT_APP_GOOGLE_FONTS_API_KEY || 'AIzaSyB8AzfLkq8VwHq5t5n5n5n5n5n5n5n5n5'; // Placeholder - replace with actual key
      const response = await fetch(`https://www.googleapis.com/webfonts/v1/webfonts?key=${apiKey}&sort=popularity`);

      if (!response.ok) {
        throw new Error(`Google Fonts API error: ${response.status}`);
      }

      const data = await response.json();
      const allFonts = data.items || [];

      // Filter fonts based on search query
      const filteredFonts = allFonts.filter(font =>
        font.family.toLowerCase().includes(trimmedQuery.toLowerCase()) ||
        (font.category && font.category.toLowerCase().includes(trimmedQuery.toLowerCase()))
      );

      // Paginate the results
      const start = startIndex;
      const end = start + perPage;
      const paginatedFonts = filteredFonts.slice(start, end);

      const formattedResults = paginatedFonts.map((font) => ({
        id: `font-${font.family.replace(/\s+/g, '-').toLowerCase()}`,
        family: font.family,
        category: font.category,
        variants: font.variants,
        subsets: font.subsets,
        version: font.version,
        lastModified: font.lastModified,
        files: font.files,
        provider: 'google-fonts',
        providerLabel: 'Google Fonts',
        credit: 'Google Fonts',
      }));

      return {
        items: formattedResults,
        hasMore: end < filteredFonts.length,
      };
    } catch (error) {
      console.error('Google Fonts API failed:', error);
      // Fallback to some popular fonts
      const fallbackFonts = [
        { family: 'Inter', category: 'sans-serif', variants: ['400', '500', '600', '700'] },
        { family: 'Roboto', category: 'sans-serif', variants: ['300', '400', '500', '700'] },
        { family: 'Open Sans', category: 'sans-serif', variants: ['300', '400', '600', '700'] },
        { family: 'Lato', category: 'sans-serif', variants: ['300', '400', '700'] },
        { family: 'Montserrat', category: 'sans-serif', variants: ['400', '500', '600', '700'] },
        { family: 'Poppins', category: 'sans-serif', variants: ['300', '400', '500', '600', '700'] },
        { family: 'Nunito', category: 'sans-serif', variants: ['300', '400', '600', '700'] },
        { family: 'Playfair Display', category: 'serif', variants: ['400', '700'] },
        { family: 'Merriweather', category: 'serif', variants: ['300', '400', '700'] },
        { family: 'Crimson Text', category: 'serif', variants: ['400', '600'] },
      ];

      const filteredFallbacks = fallbackFonts.filter(font =>
        font.family.toLowerCase().includes(trimmedQuery.toLowerCase()) ||
        font.category.toLowerCase().includes(trimmedQuery.toLowerCase())
      );

      const start = startIndex;
      const end = start + perPage;
      const paginatedFallbacks = filteredFallbacks.slice(start, end);

      const formattedFallbacks = paginatedFallbacks.map((font) => ({
        id: `font-${font.family.replace(/\s+/g, '-').toLowerCase()}`,
        family: font.family,
        category: font.category,
        variants: font.variants,
        subsets: ['latin'],
        provider: 'fallback',
        providerLabel: 'System Fonts',
        credit: 'System Fonts',
      }));

      return {
        items: formattedFallbacks,
        hasMore: end < filteredFallbacks.length,
      };
    }
  }, []);

  const handleFontSearch = useCallback(async (rawQuery) => {
    const trimmedQuery = (rawQuery ?? '').trim();

    // If empty query, reset to default fonts
    if (!trimmedQuery) {
      setFontSearchResults([]);
      hasTriggeredFontSearchRef.current = false;
      setFontCurrentPage(1);
      fontCurrentPageRef.current = 1;
      setHasMoreFonts(false);
      return;
    }

    hasTriggeredFontSearchRef.current = true;
    activeFontSearchQueryRef.current = trimmedQuery;
    setIsSearchingFonts(true);
    setFontCurrentPage(1);
    fontCurrentPageRef.current = 1;
    setHasMoreFonts(true);
    try {
      const { items, hasMore: nextHasMore } = await fetchFonts(trimmedQuery, 1);
      setFontSearchResults(items);
      const nextPage = items.length > 0 ? 2 : 1;
      setFontCurrentPage(nextPage);
      fontCurrentPageRef.current = nextPage;
      setHasMoreFonts(nextHasMore);
    } catch (error) {
      console.error('Font search failed:', error);
      alert('Some font providers are unavailable. Showing fallback fonts instead.');
      setHasMoreFonts(false);
    } finally {
      setIsSearchingFonts(false);
    }
  }, [fetchFonts]);

  const loadMoreFonts = useCallback(async () => {
    if (isLoadingMoreFonts || !hasMoreFonts) {
      return;
    }
    const activeQuery = activeFontSearchQueryRef.current.trim();
    if (!activeQuery) {
      return;
    }
    setIsLoadingMoreFonts(true);
    try {
      const { items, hasMore: nextHasMore } = await fetchFonts(activeQuery, fontCurrentPageRef.current);
      if (items.length > 0) {
        setFontSearchResults((prev) => [...prev, ...items]);
        const nextPage = fontCurrentPageRef.current + 1;
        setFontCurrentPage(nextPage);
        fontCurrentPageRef.current = nextPage;
      }
      setHasMoreFonts(nextHasMore && items.length > 0);
    } catch (error) {
      console.error('Load more fonts failed:', error);
      alert('Unable to load more fonts. Using available fonts.');
      setHasMoreFonts(false);
    } finally {
      setIsLoadingMoreFonts(false);
    }
  }, [fetchFonts, hasMoreFonts, isLoadingMoreFonts]);

  const loadMoreFontsRef = useRef(loadMoreFonts);

  useEffect(() => {
    loadMoreFontsRef.current = loadMoreFonts;
  }, [loadMoreFonts]);

  // IntersectionObserver for font infinite scroll
  useEffect(() => {
    if (fontSearchResults.length === 0 || !hasMoreFonts) {
      return;
    }

    const loadingIndicator = document.querySelector('.font-loading-indicator');
    const scrollContainer = document.querySelector('.builder-sidebar__font-results');
    
    if (!loadingIndicator || !scrollContainer) {
      console.log('Font loading indicator or scroll container not found');
      return;
    }

    console.log('Setting up IntersectionObserver for font loading indicator');

    const observer = new IntersectionObserver(
      (entries) => {
        const [entry] = entries;
        console.log('Font IntersectionObserver triggered:', {
          isIntersecting: entry.isIntersecting,
          isLoadingMoreFonts,
          hasMoreFonts,
          fontSearchResultsLength: fontSearchResults.length
        });

        if (entry.isIntersecting && !isLoadingMoreFonts && hasMoreFonts) {
          console.log('Loading more fonts via IntersectionObserver...');
          loadMoreFontsRef.current();
        }
      },
      {
        root: scrollContainer, // Use the scrollable container as root
        rootMargin: '50px',
        threshold: 0.1,
      }
    );

    observer.observe(loadingIndicator);

    return () => {
      console.log('Cleaning up font IntersectionObserver');
      observer.disconnect();
    };
  }, [fontSearchResults.length, hasMoreFonts, isLoadingMoreFonts]);

  // Prevent scroll propagation from font results to parent containers
  useEffect(() => {
    const fontResultsContainer = document.querySelector('.builder-sidebar__font-results');
    if (!fontResultsContainer) return;

    const preventScrollPropagation = (e) => {
      const { scrollTop, scrollHeight, clientHeight } = e.target;
      const isAtTop = scrollTop === 0;
      const isAtBottom = scrollTop + clientHeight >= scrollHeight;

      // Allow scroll propagation only if at boundaries and scrolling in the same direction
      if ((isAtTop && e.deltaY < 0) || (isAtBottom && e.deltaY > 0)) {
        return;
      }

      // Prevent the scroll event from bubbling up
      e.stopPropagation();
    };

    fontResultsContainer.addEventListener('wheel', preventScrollPropagation, { passive: true });
    fontResultsContainer.addEventListener('touchmove', preventScrollPropagation, { passive: true });

    return () => {
      fontResultsContainer.removeEventListener('wheel', preventScrollPropagation);
      fontResultsContainer.removeEventListener('touchmove', preventScrollPropagation);
    };
  }, []);

  // Manual load more function for button click
  const handleLoadMoreFonts = useCallback(() => {
    if (!isLoadingMoreFonts && hasMoreFonts) {
      loadMoreFontsRef.current();
    }
  }, [isLoadingMoreFonts, hasMoreFonts]);

  const handleUseFont = (font) => {
    if (!activePage) return;

    ensureFontLoaded({ family: font.family, weights: font.variants });

    // Create text layer with the selected font
    const layer = createLayer('text', activePage, {
      name: `${font.family} Text`,
      content: 'Sample text with new font',
      fontSize: 32,
      fontFamily: `${font.family}, ${font.category}`,
      fontWeight: '400',
    });

    if (layer.frame) {
      layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
    }

    dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
  };

  const handleQuoteSearch = useCallback(async (rawQuery) => {
    const trimmedQuery = (rawQuery ?? '').trim();

    // If empty query, fetch random quotes from API instead of showing defaults
    if (!trimmedQuery) {
      hasTriggeredQuoteSearchRef.current = true;
      setIsSearchingQuotes(true);
      setQuoteCurrentPage(1);
      quoteCurrentPageRef.current = 1;
      setHasMoreQuotes(true);
      try {
        // Call ZenQuotes API for random quotes (free, no auth required)
        const response = await fetch('https://zenquotes.io/api/random/6');

        if (!response.ok) {
          throw new Error(`API request failed: ${response.status}`);
        }

        const data = await response.json();

        // Transform API response to match our quote format
        let quotes = [];
        if (Array.isArray(data)) {
          quotes = data.map((quote, index) => ({
            id: `random-quote-${quoteCurrentPageRef.current}-${index}`,
            content: quote.q || '',
            author: quote.a || 'Unknown',
            category: 'inspiration',
          })).filter(quote => quote.content.trim() !== '');
        }

        // If no results from API, fall back to local quotes
        if (quotes.length === 0) {
          const fallbackQuotes = [
            {
              id: 'fallback-quote-1',
              content: 'The only way to do great work is to love what you do.',
              author: 'Steve Jobs',
              category: 'motivation',
            },
            {
              id: 'fallback-quote-2',
              content: 'Believe you can and you\'re halfway there.',
              author: 'Theodore Roosevelt',
              category: 'inspiration',
            },
            {
              id: 'fallback-quote-3',
              content: 'The future belongs to those who believe in the beauty of their dreams.',
              author: 'Eleanor Roosevelt',
              category: 'dreams',
            },
            {
              id: 'fallback-quote-4',
              content: 'You miss 100% of the shots you don\'t take.',
              author: 'Wayne Gretzky',
              category: 'opportunity',
            },
            {
              id: 'fallback-quote-5',
              content: 'The best way to predict the future is to create it.',
              author: 'Peter Drucker',
              category: 'future',
            },
            {
              id: 'fallback-quote-6',
              content: 'Keep your face always toward the sunshine—and shadows will fall behind you.',
              author: 'Walt Whitman',
              category: 'positivity',
            },
          ];
          quotes = fallbackQuotes;
        }

        setQuoteSearchResults((prev) => [...prev, ...quotes]);
        const nextPage = quotes.length > 0 ? 2 : 1;
        setQuoteCurrentPage(nextPage);
        quoteCurrentPageRef.current = nextPage;
        setHasMoreQuotes(true); // Allow loading more quotes
      } catch (error) {
        console.error('Quote generation failed:', error);
        // Show fallback quotes on error
        const fallbackQuotes = [
          {
            id: 'error-fallback-1',
            content: 'The only way to do great work is to love what you do.',
            author: 'Steve Jobs',
            category: 'motivation',
          },
          {
            id: 'error-fallback-2',
            content: 'Believe you can and you\'re halfway there.',
            author: 'Theodore Roosevelt',
            category: 'inspiration',
          },
          {
            id: 'error-fallback-3',
            content: 'The future belongs to those who believe in the beauty of their dreams.',
            author: 'Eleanor Roosevelt',
            category: 'dreams',
          },
        ];
        setQuoteSearchResults((prev) => [...prev, ...fallbackQuotes]);
        setHasMoreQuotes(true);
      } finally {
        setIsSearchingQuotes(false);
      }
      return;
    }

    hasTriggeredQuoteSearchRef.current = true;
    activeQuoteSearchQueryRef.current = trimmedQuery;
    setIsSearchingQuotes(true);
    setQuoteCurrentPage(1);
    quoteCurrentPageRef.current = 1;
    setHasMoreQuotes(false); // ZenQuotes doesn't support search or pagination
    try {
      // ZenQuotes doesn't support search, so we'll show a message and fall back to local quotes
      alert('Quote search is not currently supported. Use "Generate Quotes" for random inspirational quotes.');

      // Filter fallback quotes based on search query
      const fallbackQuotes = [
        {
          id: 'search-quote-1',
          content: 'Success is not final, failure is not fatal: It is the courage to continue that counts.',
          author: 'Winston Churchill',
          category: 'perseverance',
        },
        {
          id: 'search-quote-2',
          content: 'The only impossible journey is the one you never begin.',
          author: 'Tony Robbins',
          category: 'journey',
        },
        {
          id: 'search-quote-3',
          content: 'Your time is limited, so don\'t waste it living someone else\'s life.',
          author: 'Steve Jobs',
          category: 'life',
        },
        {
          id: 'search-quote-4',
          content: 'The way to get started is to quit talking and begin doing.',
          author: 'Walt Disney',
          category: 'action',
        },
        {
          id: 'search-quote-5',
          content: 'Don\'t watch the clock; do what it does. Keep going.',
          author: 'Sam Levenson',
          category: 'persistence',
        },
      ];

      const filteredQuotes = fallbackQuotes.filter(quote =>
        quote.content.toLowerCase().includes(trimmedQuery.toLowerCase()) ||
        quote.author.toLowerCase().includes(trimmedQuery.toLowerCase()) ||
        quote.category.toLowerCase().includes(trimmedQuery.toLowerCase())
      );

      setQuoteSearchResults(filteredQuotes.length > 0 ? filteredQuotes : fallbackQuotes);
    } catch (error) {
      console.error('Quote search failed:', error);
      setHasMoreQuotes(false);
    } finally {
      setIsSearchingQuotes(false);
    }
  }, []);

  const loadMoreQuotes = useCallback(async () => {
    if (isLoadingMoreQuotes || !hasMoreQuotes) {
      return;
    }
    const activeQuery = activeQuoteSearchQueryRef.current.trim();
    if (!activeQuery) {
      // For random quotes, fetch more from ZenQuotes
      setIsLoadingMoreQuotes(true);
      try {
        const response = await fetch('https://zenquotes.io/api/random/6');

        if (!response.ok) {
          throw new Error(`API request failed: ${response.status}`);
        }

        const data = await response.json();

        // Transform API response to match our quote format
        let newQuotes = [];
        if (Array.isArray(data)) {
          newQuotes = data.map((quote, index) => ({
            id: `random-quote-more-${Date.now()}-${index}`,
            content: quote.q || '',
            author: quote.a || 'Unknown',
            category: 'inspiration',
          })).filter(quote => quote.content.trim() !== '');
        }

        // If no new results from API, add fallback quotes
        if (newQuotes.length === 0) {
          const additionalQuotes = [
            {
              id: `additional-quote-${Date.now()}-1`,
              content: 'The only limit to our realization of tomorrow will be our doubts of today.',
              author: 'Franklin D. Roosevelt',
              category: 'doubts',
            },
            {
              id: `additional-quote-${Date.now()}-2`,
              content: 'What lies behind us and what lies before us are tiny matters compared to what lies within us.',
              author: 'Ralph Waldo Emerson',
              category: 'inner strength',
            },
          ];
          newQuotes = additionalQuotes;
        }

        setQuoteSearchResults((prev) => [...prev, ...newQuotes]);
        setHasMoreQuotes(true); // Allow loading more quotes
      } catch (error) {
        console.error('Load more quotes failed:', error);
        setHasMoreQuotes(true); // Allow retrying to load more quotes
      } finally {
        setIsLoadingMoreQuotes(false);
      }
    } else {
      // For search queries, just show a message since search isn't supported
      alert('Quote search is not currently supported. Use "Generate Quotes" for random inspirational quotes.');
    }
  }, [hasMoreQuotes, isLoadingMoreQuotes]);

  const loadMoreQuotesRef = useRef(loadMoreQuotes);

  useEffect(() => {
    loadMoreQuotesRef.current = loadMoreQuotes;
  }, [loadMoreQuotes]);

  // IntersectionObserver for quote infinite scroll
  useEffect(() => {
    if (quoteSearchResults.length === 0 || !hasMoreQuotes) {
      return;
    }

    const loadingIndicator = document.querySelector('.quote-loading-indicator');
    const scrollContainer = document.querySelector('.builder-sidebar__quote-results');
    
    if (!loadingIndicator || !scrollContainer) {
      console.log('Quote loading indicator or scroll container not found');
      return;
    }

    console.log('Setting up IntersectionObserver for quote loading indicator');

    const observer = new IntersectionObserver(
      (entries) => {
        const [entry] = entries;
        console.log('Quote IntersectionObserver triggered:', {
          isIntersecting: entry.isIntersecting,
          isLoadingMoreQuotes,
          hasMoreQuotes,
          quoteSearchResultsLength: quoteSearchResults.length
        });

        if (entry.isIntersecting && !isLoadingMoreQuotes && hasMoreQuotes) {
          console.log('Loading more quotes via IntersectionObserver...');
          loadMoreQuotesRef.current();
        }
      },
      {
        root: scrollContainer, // Use the scrollable container as root
        rootMargin: '50px',
        threshold: 0.1,
      }
    );

    observer.observe(loadingIndicator);

    return () => {
      console.log('Cleaning up quote IntersectionObserver');
      observer.disconnect();
    };
  }, [quoteSearchResults.length, hasMoreQuotes, isLoadingMoreQuotes]);

  // Prevent scroll propagation from quote results to parent containers
  useEffect(() => {
    const quoteResultsContainer = document.querySelector('.builder-sidebar__quote-results');
    if (!quoteResultsContainer) return;

    const preventScrollPropagation = (e) => {
      const { scrollTop, scrollHeight, clientHeight } = e.target;
      const isAtTop = scrollTop === 0;
      const isAtBottom = scrollTop + clientHeight >= scrollHeight;

      // Allow scroll propagation only if at boundaries and scrolling in the same direction
      if ((isAtTop && e.deltaY < 0) || (isAtBottom && e.deltaY > 0)) {
        return;
      }

      // Prevent the scroll event from bubbling up
      e.stopPropagation();
    };

    quoteResultsContainer.addEventListener('wheel', preventScrollPropagation, { passive: true });
    quoteResultsContainer.addEventListener('touchmove', preventScrollPropagation, { passive: true });

    return () => {
      quoteResultsContainer.removeEventListener('wheel', preventScrollPropagation);
      quoteResultsContainer.removeEventListener('touchmove', preventScrollPropagation);
    };
  }, []);

  // Manual load more function for button click
  const handleLoadMoreQuotes = useCallback(() => {
    if (!isLoadingMoreQuotes && hasMoreQuotes) {
      loadMoreQuotesRef.current();
    }
  }, [isLoadingMoreQuotes, hasMoreQuotes]);

  const handleUseQuote = (quote) => {
    if (!activePage) return;

    // Create text layer with the quote
    const layer = createLayer('text', activePage, {
      name: 'Quote',
      content: `"${quote.content}"\n\n— ${quote.author}`,
      fontSize: 28,
      fontFamily: 'Georgia, serif',
      fontWeight: '400',
      textAlign: 'center',
    });

    if (layer.frame) {
      layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
    }

    dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
  };

  const handleUseDefaultQuote = (quote) => {
    if (!activePage) return;

    // Create text layer with the default quote
    const layer = createLayer('text', activePage, {
      name: 'Quote',
      content: quote,
      fontSize: 28,
      fontFamily: 'Georgia, serif',
      fontWeight: '400',
      textAlign: 'center',
    });

    if (layer.frame) {
      layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
    }

    dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
  };







  // Trigger new search when filter or provider changes and there's an existing search query
  useEffect(() => {
    if (!hasTriggeredSearchRef.current || !activeSearchQueryRef.current) {
      return;
    }
    handleSearch(activeSearchQueryRef.current);
  }, [handleSearch, selectedFilter]);

  // IntersectionObserver for infinite scroll
  useEffect(() => {
    if (searchResults.length === 0 || !hasMore) {
      return;
    }

    const loadingIndicator = document.querySelector('.loading-indicator');
    if (!loadingIndicator) {
      console.log('Loading indicator not found');
      return;
    }

    console.log('Setting up IntersectionObserver for loading indicator');

    const observer = new IntersectionObserver(
      (entries) => {
        const [entry] = entries;
        console.log('IntersectionObserver triggered:', { isIntersecting: entry.isIntersecting, isLoadingMore, hasMore });

        if (entry.isIntersecting && !isLoadingMore && hasMore && searchQuery.trim()) {
          console.log('Loading more images via IntersectionObserver...');
          loadMoreImagesRef.current();
        }
      },
      {
        root: document.querySelector('.builder-sidebar__search-results'),
        rootMargin: '100px',
        threshold: 0.1,
      }
    );

    observer.observe(loadingIndicator);

    return () => {
      console.log('Cleaning up IntersectionObserver');
      observer.disconnect();
    };
  }, [searchResults.length, hasMore, isLoadingMore, searchQuery]);

  // Prevent scroll propagation from search results to parent containers
  useEffect(() => {
    const searchResultsContainer = document.querySelector('.builder-sidebar__search-results');
    if (!searchResultsContainer) return;

    const preventScrollPropagation = (e) => {
      const { scrollTop, scrollHeight, clientHeight } = e.target;
      const isAtTop = scrollTop === 0;
      const isAtBottom = scrollTop + clientHeight >= scrollHeight;

      // Allow scroll propagation only if at boundaries and scrolling in the same direction
      if ((isAtTop && e.deltaY < 0) || (isAtBottom && e.deltaY > 0)) {
        return;
      }

      // Prevent the scroll event from bubbling up
      e.stopPropagation();
    };

    searchResultsContainer.addEventListener('wheel', preventScrollPropagation, { passive: true });
    searchResultsContainer.addEventListener('touchmove', preventScrollPropagation, { passive: true });

    return () => {
      searchResultsContainer.removeEventListener('wheel', preventScrollPropagation);
      searchResultsContainer.removeEventListener('touchmove', preventScrollPropagation);
    };
  }, []);

  const handleUsePhotoResult = async (photo) => {
    if (!activePage) return;

    const sourceUrl = photo?.downloadUrl || photo?.previewUrl || photo?.thumbUrl;
    if (!sourceUrl) {
      alert('Unable to load the selected image. Please choose another one.');
      return;
    }

    try {
      const response = await fetch(sourceUrl);
      if (!response.ok) {
        throw new Error(`Network error: ${response.status}`);
      }
      const blob = await response.blob();
      const reader = new FileReader();
      reader.onload = () => {
        const dataUrl = reader.result;
        const img = new Image();
        img.onload = () => {
          const naturalW = img.naturalWidth || img.width;
          const naturalH = img.naturalHeight || img.height;
          const maxW = Math.round(activePage.width * 0.85);
          const maxH = Math.round(activePage.height * 0.85);
          const scale = Math.min(1, maxW / naturalW, maxH / naturalH);
          const width = Math.max(1, Math.round(naturalW * scale));
          const height = Math.max(1, Math.round(naturalH * scale));
          const x = Math.round((activePage.width - width) / 2);
          const y = Math.round((activePage.height - height) / 2);
          const layer = createLayer('image', activePage, {
            name: photo?.description || `${photo?.providerLabel ?? 'Stock'} image`,
            content: dataUrl,
            metadata: {
              objectFit: 'cover',
              imageScale: 1,
              imageOffsetX: 0,
              imageOffsetY: 0,
              attribution: photo?.credit,
            },
          });
          layer.frame = { x, y, width, height, rotation: 0 };
          if (layer.frame) {
            layer.frame = constrainFrameToSafeZone(layer.frame, activePage, safeInsets);
          }
          dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
        };
        img.src = dataUrl;
      };
      reader.readAsDataURL(blob);
    } catch (error) {
      console.error('Failed to load photo:', error);
      alert('Failed to load photo. Please try again.');
    }
  };

  const renderToolContent = () => {
    if (!activePage) {
      return (
        <div className="builder-sidebar__empty-state">Create a page to start designing.</div>
      );
    }

    switch (activeTool) {
      case 'text':
        return (
          <div className="builder-sidebar__content builder-sidebar__content--text">
            <div className="builder-sidebar__header">
              <div>
                <h2>Text</h2>
                <p>Add quick headings, stylized blocks, or browse the full font library.</p>
              </div>
              <button
                type="button"
                className="builder-sidebar-toggle"
                onClick={onToggleSidebar}
                aria-label="Hide sidebar"
                title="Hide sidebar"
              >
                <i className="fas fa-chevron-left" aria-hidden="true"></i>
              </button>
            </div>
            <div className="text-panel">
              <div className="text-panel__canvas" ref={textPanelCanvasRef}>
                <div className="text-panel__quick" style={{ marginBottom: '1rem' }}>
                  <div className="text-panel__quick-actions" style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
                    <button
                      type="button"
                      className="text-panel__quick-btn"
                      style={{
                        borderRadius: '0.5rem',
                        border: '1px solid rgba(37, 99, 235, 0.25)',
                        background: 'rgba(37, 99, 235, 0.08)',
                        color: 'var(--builder-text)',
                        padding: '0.4rem 0.8rem',
                        fontSize: '0.85rem',
                        cursor: 'pointer',
                        transition: 'background 0.15s ease',
                        width: '100%'
                      }}
                      onClick={() => handleAddText({ name: 'Heading', content: 'Add header', fontSize: 52 })}
                      onMouseEnter={(e) => e.target.style.background = 'rgba(37, 99, 235, 0.15)'}
                      onMouseLeave={(e) => e.target.style.background = 'rgba(37, 99, 235, 0.08)'}
                    >
                      add header
                    </button>
                    <button
                      type="button"
                      className="text-panel__quick-btn"
                      style={{
                        borderRadius: '0.5rem',
                        border: '1px solid rgba(37, 99, 235, 0.25)',
                        background: 'rgba(37, 99, 235, 0.08)',
                        color: 'var(--builder-text)',
                        padding: '0.4rem 0.8rem',
                        fontSize: '0.85rem',
                        cursor: 'pointer',
                        transition: 'background 0.15s ease',
                        width: '100%'
                      }}
                      onClick={() => handleAddText({ name: 'Subheading', content: 'Add sub header', fontSize: 34 })}
                      onMouseEnter={(e) => e.target.style.background = 'rgba(37, 99, 235, 0.15)'}
                      onMouseLeave={(e) => e.target.style.background = 'rgba(37, 99, 235, 0.08)'}
                    >
                      add sub header
                    </button>
                    <button
                      type="button"
                      className="text-panel__quick-btn"
                      style={{
                        borderRadius: '0.5rem',
                        border: '1px solid rgba(37, 99, 235, 0.25)',
                        background: 'rgba(37, 99, 235, 0.08)',
                        color: 'var(--builder-text)',
                        padding: '0.4rem 0.8rem',
                        fontSize: '0.85rem',
                        cursor: 'pointer',
                        transition: 'background 0.15s ease',
                        width: '100%'
                      }}
                      onClick={() => handleAddText({ name: 'Body text', content: 'Add body text', fontSize: 26, align: 'left' })}
                      onMouseEnter={(e) => e.target.style.background = 'rgba(37, 99, 235, 0.15)'}
                      onMouseLeave={(e) => e.target.style.background = 'rgba(37, 99, 235, 0.08)'}
                    >
                      add body text
                    </button>
                  </div>
                </div>

                {/* Ready-made styled text presets (visual previews) */}
                <div className="text-panel__styled-presets" ref={styledContainerRef}>
                  <div className="text-panel__styled-grid">
                    {styledPresets.map((preset) => (
                      <button
                        key={preset.id}
                        type="button"
                        className="styled-text-preset"
                        onClick={() => handleApplyFontCombo(preset)}
                        title={`Add styled combo: ${preset.label}`}
                      >
                        <div className="styled-text-preview multi-layer">
                          {preset.layers?.map((layer, layerIndex) => (
                            <div
                              key={`${preset.id}-${layer.role ?? layerIndex}`}
                              className="styled-text-preview__line"
                              style={{
                                fontFamily: `${layer.family}, ${layer.fallback ?? 'sans-serif'}`,
                                fontSize: `${(layer.fontSize ?? 28) * 0.6}px`,
                                fontWeight: layer.fontWeight ?? '400',
                                textAlign: layer.align ?? preset.align ?? 'center',
                                textTransform: layer.transform ?? 'none',
                                letterSpacing: `${layer.letterSpacing ?? 0}px`,
                              }}
                            >
                              {layer.content}
                            </div>
                          ))}
                          {preset.description && (
                            <div className="styled-text-preview__description">
                              {preset.description}
                            </div>
                          )}
                        </div>
                      </button>
                    ))}
                  </div>
                  <div className="styled-loading-indicator">
                    {(isLoadingStyledPresets || hasMoreStyledPresets) && (
                      <span>
                        {isLoadingStyledPresets
                          ? 'Loading styled presets...'
                          : 'Scroll for more presets'}
                      </span>
                    )}
                  </div>
                </div>
                {fontSearchResults.length > 0 ? (
                  <div className="builder-sidebar__font-results">
                    {fontSearchResults.map((font) => (
                      <button
                        key={font.id}
                        type="button"
                        className="search-result-thumb font-result-thumb"
                        onClick={() => handleUseFont(font)}
                        title={`${font.family} (${font.category}) - ${font.providerLabel}`}
                      >
                        <div
                          className="font-preview"
                          style={{
                            fontFamily: `${font.family}, ${font.category}`,
                            fontSize: '16px',
                            fontWeight: '400',
                            textAlign: 'center',
                            padding: '8px',
                            backgroundColor: '#fff',
                            border: '1px solid #e5e7eb',
                            borderRadius: '4px',
                            minHeight: '48px',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            whiteSpace: 'nowrap',
                            overflow: 'hidden',
                            textOverflow: 'ellipsis'
                          }}
                        >
                          {font.family}
                        </div>
                        <span className="search-result-provider-tag">{font.providerLabel}</span>
                      </button>
                    ))}
                    {hasMoreFonts && (
                      <>
                        <div className="font-loading-indicator">
                          <div className={`spinner ${isLoadingMoreFonts ? 'loading' : ''}`}></div>
                          <span>
                            {isLoadingMoreFonts
                              ? 'Loading more fonts...'
                              : 'Scroll for more fonts'}
                          </span>
                        </div>
                        <div className="load-more-container">
                          <button
                            type="button"
                            className="load-more-btn"
                            onClick={handleLoadMoreFonts}
                            disabled={isLoadingMoreFonts}
                          >
                            {isLoadingMoreFonts ? 'Loading...' : 'Load More Fonts'}
                          </button>
                        </div>
                      </>
                    )}
                  </div>
                ) : hasTriggeredFontSearchRef.current ? (
                  <div className="builder-sidebar__empty-state">
                    {isSearchingFonts ? 'Searching for fonts...' : 'No fonts found. Try a different search term.'}
                  </div>
                ) : null}
              </div>
            </div>
          </div>
        );
      case 'frames':
        return (
          <div className="builder-sidebar__content">
            <div className="builder-sidebar__header">
              <h2>Frames</h2>
              <button
                type="button"
                className="builder-sidebar-toggle"
                onClick={onToggleSidebar}
                aria-label="Hide sidebar"
                title="Hide sidebar"
              >
                <i className="fas fa-chevron-left" aria-hidden="true"></i>
              </button>
            </div>
            <p>Choose from a variety of frames and shapes.</p>
            <div className="builder-sidebar__provider-note">
              Built-in frames for your designs.
            </div>
            {!hasTriggeredShapeSearchRef.current && (
              <div className="builder-sidebar__hint" style={{ marginBottom: '10px', fontSize: '12px', color: '#666' }}>
                Available frames:
              </div>
            )}
            {shapeSearchResults.length > 0 ? (
              <div className="builder-sidebar__shape-results">
                {shapeSearchResults.map((shape) => (
                  <button
                    key={shape.id}
                    type="button"
                    className="search-result-thumb"
                    onClick={() => handleUseShape(shape)}
                  >
                    <img
                      src={shape.thumbUrl}
                      alt=""
                      style={{ width: 64, height: 64, objectFit: 'contain', backgroundColor: '#fff' }}
                      onError={(e) => {
                        // Replace broken/missing shape image with a tiny inline SVG placeholder
                        try {
                          e.target.onerror = null;
                          const label = (shape.description || 'sh').toUpperCase().slice(0, 2);
                          const fallbackSvg = `<svg xmlns='http://www.w3.org/2000/svg' width='64' height='64'><rect width='100%' height='100%' fill='%23f3f4f6'/><text x='50%' y='50%' font-size='14' dominant-baseline='middle' text-anchor='middle' fill='%236b7280' font-family='Arial,Helvetica,sans-serif'>${label}</text></svg>`;
                          e.target.src = 'data:image/svg+xml;utf8,' + encodeURIComponent(fallbackSvg);
                        } catch (err) {
                          /* ignore fallback errors */
                        }
                      }}
                    />
                    {shape.providerLabel !== 'Iconify' && (
                      <span className="search-result-provider-tag">{shape.providerLabel}</span>
                    )}
                  </button>
                ))}
              </div>
            ) : hasTriggeredShapeSearchRef.current ? (
              <div className="builder-sidebar__empty-state">
                {isSearchingShapes ? 'Searching for shapes...' : 'No shapes found. Try a different search term.'}
              </div>
            ) : null}
          </div>
        );
      case 'shapes':
        return (
          <div className="builder-sidebar__content">
            <div className="builder-sidebar__header">
              <h2>Shape</h2>
              <button
                type="button"
                className="builder-sidebar-toggle"
                onClick={onToggleSidebar}
                aria-label="Hide sidebar"
                title="Hide sidebar"
              >
                <i className="fas fa-chevron-left" aria-hidden="true"></i>
              </button>
            </div>
            <p>Add basic geometric shapes to your design.</p>
            <div className="builder-sidebar__shape-results">
              {getNaturalShapeResults().map((shape) => (
                <button
                  key={shape.id}
                  type="button"
                  className="search-result-thumb"
                  onClick={() => handleUseShape(shape)}
                >
                  <img
                    src={shape.thumbUrl}
                    alt=""
                    style={{ width: 64, height: 64, objectFit: 'contain', backgroundColor: '#fff' }}
                    onError={(e) => {
                      // Replace broken/missing shape image with a tiny inline SVG placeholder
                      try {
                        e.target.onerror = null;
                        const label = (shape.description || 'sh').toUpperCase().slice(0, 2);
                        const fallbackSvg = `<svg xmlns='http://www.w3.org/2000/svg' width='64' height='64'><rect width='100%' height='100%' fill='%23f3f4f6'/><text x='50%' y='50%' font-size='14' dominant-baseline='middle' text-anchor='middle' fill='%236b7280' font-family='Arial,Helvetica,sans-serif'>${label}</text></svg>`;
                        e.target.src = 'data:image/svg+xml;utf8,' + encodeURIComponent(fallbackSvg);
                      } catch (err) {
                        /* ignore fallback errors */
                      }
                    }}
                  />
                </button>
              ))}
            </div>
          </div>
        );
      case 'images': {
        const recentImages = Array.isArray(state.recentlyUploadedImages) ? state.recentlyUploadedImages : [];
        const recentCount = recentImages.length;
        return (
          <div className="builder-sidebar__content">
            <div className="builder-sidebar__header">
              <h2>Upload</h2>
              <button
                type="button"
                className="builder-sidebar-toggle"
                onClick={onToggleSidebar}
                aria-label="Hide sidebar"
                title="Hide sidebar"
              >
                <i className="fas fa-chevron-left" aria-hidden="true"></i>
              </button>
            </div>

            <div className="builder-upload-card">
              <div className="builder-upload-card__icon" aria-hidden="true">
                <i className="fa-solid fa-cloud-arrow-up"></i>
              </div>
              <div className="builder-upload-card__body">
                <h3 className="builder-upload-card__title">Add photos &amp; artwork</h3>
                <div className="builder-upload-card__actions">
                  <button
                    type="button"
                    className="tool-action-btn builder-upload-card__button"
                    onClick={handleAddImagePlaceholder}
                  >
                    Upload image
                  </button>
                </div>
                <ul className="builder-upload-card__guidelines">
                  <li>Supports PNG, JPG, and SVG</li>
                  <li>Recommended up to 10&nbsp;MB per file</li>
                </ul>
              </div>
            </div>

            <div className="builder-upload-divider" role="presentation"></div>

            <div className="builder-sidebar__recently-uploaded">
              <div className="builder-sidebar__section-heading">
                <h3>Recent uploads</h3>
                {recentCount > 0 && (
                  <span className="builder-sidebar__section-count" aria-label={`${recentCount} recent uploads`}>
                    {recentCount}
                  </span>
                )}
              </div>
              <p className="builder-sidebar__section-help">Click any image to place it back onto the canvas.</p>
              <div className="builder-sidebar__recent-items">
                {recentCount > 0 ? (
                  <div className="builder-recent-upload-grid">
                    {recentImages.map((image) => (
                      <div key={image.id} className="builder-recent-upload-grid__item">
                        <button
                          type="button"
                          className="builder-recent-upload-grid__button"
                          onClick={() => handleUseRecentFromSidebar(image)}
                          title={image.fileName || 'Uploaded image'}
                          aria-label={`Use recent upload ${image.fileName || 'image'}`}
                        >
                          <img
                            src={image.dataUrl}
                            alt={image.fileName || 'Uploaded image'}
                            className="builder-recent-upload-grid__thumb"
                            onError={(e) => { e.target.style.visibility = 'hidden'; }}
                          />
                        </button>
                        <div className="builder-recent-upload-grid__footer">
                          <span className="builder-recent-upload-grid__name" title={image.fileName || 'Uploaded image'}>
                            {image.fileName || 'Uploaded image'}
                          </span>
                          <button
                            type="button"
                            className="builder-recent-upload-grid__delete"
                            title="Delete recent upload"
                            aria-label={`Delete recent upload ${image.fileName || 'image'}`}
                            onClick={async (evt) => {
                              evt.stopPropagation();
                              try {
                                const dbModule = await import('../../utils/recentImagesDB');
                                if (image.dataUrl && image.dataUrl.startsWith('blob:')) {
                                  try { URL.revokeObjectURL(image.dataUrl); } catch (e) { /* ignore */ }
                                }
                                await dbModule.deleteImage(image.id);
                                dispatch({ type: 'DELETE_RECENTLY_UPLOADED_IMAGE', id: image.id });
                              } catch (err) {
                                console.error('Failed to delete recent image', err);
                                alert('Failed to delete recent image. See console for details.');
                              }
                            }}
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
                              <path d="M3 6h18" />
                              <path d="M8 6v14a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6" />
                              <line x1="10" y1="11" x2="10" y2="17" />
                              <line x1="14" y1="11" x2="14" y2="17" />
                              <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />
                            </svg>
                          </button>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  <div className="builder-sidebar__empty-state builder-upload-empty">
                    <i className="fa-solid fa-image" aria-hidden="true"></i>
                    <p>No uploads yet. Use the button above to add your first image.</p>
                  </div>
                )}
              </div>
            </div>

            <div className="builder-sidebar__hint">Image uploads will connect to the asset library in a future release.</div>
          </div>
        );
      }
      case 'photos':
        return (
          <div className="builder-sidebar__content">
            <div className="builder-sidebar__header">
              <h2>Photos</h2>
              <button
                type="button"
                className="builder-sidebar-toggle"
                onClick={onToggleSidebar}
                aria-label="Hide sidebar"
                title="Hide sidebar"
              >
                <i className="fas fa-chevron-left" aria-hidden="true"></i>
              </button>
            </div>
            <p>Searches Unsplash and Pixabay together automatically.</p>
            <div className="builder-sidebar__provider-note">
              Showing combined results from Unsplash + Pixabay. Please display attribution when publishing your design.
            </div>
            <div className="builder-sidebar__filters" role="group" aria-label="Filter results">
              {PHOTO_FILTERS.map((filter) => (
                <button
                  key={filter.id}
                  type="button"
                  className={`filter-btn ${selectedFilter === filter.id ? 'active' : ''}`}
                  onClick={() => setSelectedFilter(filter.id)}
                >
                  {filter.label}
                </button>
              ))}
            </div>
            <div className="builder-sidebar__search">
              <input
                type="text"
                placeholder="Search for photos..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && handleSearch(e.target.value)}
              />
              <button type="button" onClick={() => handleSearch(searchQuery)} disabled={isSearching}>
                {isSearching ? 'Searching...' : 'Search'}
              </button>
            </div>
            {searchResults.length > 0 && (
              <div className="builder-sidebar__search-results">
                {searchResults.map((photo) => (
                  <button
                    key={photo.id}
                    type="button"
                    className="search-result-thumb"
                    onClick={() => handleUsePhotoResult(photo)}
                    title={`${photo.description} (${photo.providerLabel})`}
                  >
                    <img
                      src={photo.thumbUrl}
                      alt={photo.description}
                      style={{ width: 96, height: 96, objectFit: 'cover', backgroundColor: '#f3f4f6' }}
                      onError={(e) => {
                        // Replace broken/missing photo image with a placeholder
                        try {
                          e.target.onerror = null;
                          const label = (photo.description || 'img').toUpperCase().slice(0, 3);
                          const fallbackSvg = `<svg xmlns='http://www.w3.org/2000/svg' width='96' height='96'><rect width='100%' height='100%' fill='%23e5e7eb'/><text x='50%' y='50%' font-size='12' dominant-baseline='middle' text-anchor='middle' fill='%236b7280' font-family='Arial,Helvetica,sans-serif'>${label}</text></svg>`;
                          e.target.src = 'data:image/svg+xml;utf8,' + encodeURIComponent(fallbackSvg);
                        } catch (err) {
                          /* ignore fallback errors */
                        }
                      }}
                    />
                  </button>
                ))}
                {hasMore && (
                  <div className="loading-indicator">
                    <div className={`spinner ${isLoadingMore ? 'loading' : ''}`}></div>
                    <span>
                      {isLoadingMore
                        ? `Loading more ${activeProviderLabel} images...`
                        : `Scroll for more ${activeProviderLabel} images`}
                    </span>
                  </div>
                )}
              </div>
            )}
          </div>
        );
      case 'background':
        return (
          <div className="builder-sidebar__content">
            <div className="builder-sidebar__header">
              <h2>Background</h2>
              <button
                type="button"
                className="builder-sidebar-toggle"
                onClick={onToggleSidebar}
                aria-label="Hide sidebar"
                title="Hide sidebar"
              >
                <i className="fas fa-chevron-left" aria-hidden="true"></i>
              </button>
            </div>
            <div className="background-panel">
              <p className="background-panel__subtitle">
                Define a clean canvas foundation with precise color, gradient, and opacity controls.
              </p>

              <div className="background-panel__toggle" role="group" aria-label="Background mode">
                {['solid', 'gradient'].map((mode) => (
                  <button
                    key={mode}
                    type="button"
                    className={`background-panel__toggle-btn ${colorPickerTab === mode ? 'is-active' : ''}`}
                    onClick={() => handleColorTabChange(mode)}
                    aria-pressed={colorPickerTab === mode}
                  >
                    {mode === 'solid' ? 'Solid' : 'Gradient'}
                  </button>
                ))}
              </div>

              <div className="background-panel__preview-card">
                {colorPickerTab === 'solid' ? (
                  <input
                    type="range"
                    min="0"
                    max="360"
                    value={hueSliderValue}
                    onChange={(e) => handleHueSliderChange(e.target.value)}
                    className="background-panel__spectrum-slider"
                    aria-label="Color spectrum slider"
                  />
                ) : (
                  <>
                    <div
                      className="background-panel__preview"
                      style={{ background: gradientPreviewValue }}
                    ></div>
                    <div className="background-panel__preview-info">
                      <span>Gradient preview</span>
                      <span>
                        {gradientInputs[0]} → {gradientInputs[1]}
                      </span>
                    </div>
                  </>
                )}
              </div>

              {colorPickerTab === 'gradient' ? (
                <>
                  <div className="background-panel__section">
                    <div className="background-panel__color-grid">
                      {gradientInputs.slice(0, 2).map((value, index) => (
                        <div key={`gradient-color-${index}`} className="background-panel__color-field">
                          <label htmlFor={`gradient-color-input-${index}`}>
                            {index === 0 ? 'Start color' : 'End color'}
                          </label>
                          <div className="background-panel__color-input">
                            <input
                              id={`gradient-color-input-${index}`}
                              type="text"
                              value={value}
                              onFocus={() => setActiveGradientStop(index)}
                              onChange={(e) => handleGradientInputChange(index, e.target.value)}
                              aria-label={`Hex value for ${index === 0 ? 'start' : 'end'} color`}
                            />
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>

                  <div className="background-panel__section">
                    <span className="background-panel__label">Direction</span>
                    <div className="background-panel__direction-tabs">
                      {gradientStyleOptions.map((option) => (
                        <button
                          key={option.id}
                          type="button"
                          className={`background-panel__direction-btn ${selectedGradientStyle === option.id ? 'is-active' : ''}`}
                          onClick={() => handleGradientStyleSelect(option.id)}
                        >
                          <span className="background-panel__direction-btn-label">
                            <i className={option.icon} aria-hidden="true"></i>
                            {option.label}
                          </span>
                          <small>{option.description}</small>
                        </button>
                      ))}
                    </div>
                    {selectedGradientDefinition?.supportsAngle && (
                      <div className="background-panel__slider-block">
                        <div className="background-panel__slider-header">
                          <span>Angle</span>
                          <span>{gradientAngle}°</span>
                        </div>
                        <input
                          type="range"
                          min="0"
                          max="360"
                          value={gradientAngle}
                          onChange={(e) => handleGradientAngleChange(e.target.value)}
                          className="background-panel__slider"
                          aria-label="Gradient angle"
                        />
                      </div>
                    )}
                  </div>
                </>
              ) : (
                <div className="background-panel__section">
                  <div className="background-panel__color-field">
                    <label htmlFor="solid-color-hex">Hex value</label>
                    <div className="background-panel__color-input">
                      <input
                        id="solid-color-hex"
                        type="text"
                        value={solidHexDraft}
                        onChange={(e) => handleSolidHexInput(e.target.value)}
                        aria-label="Solid hex color"
                      />
                    </div>
                  </div>
                </div>
              )}

              <div className="background-panel__section">
                <span className="background-panel__label">Opacity</span>
                <div className="background-panel__slider-block">
                  <div className="background-panel__slider-header">
                    <span>Background opacity</span>
                    <span>{backgroundOpacity}%</span>
                  </div>
                  <input
                    type="range"
                    min="0"
                    max="100"
                    value={backgroundOpacity}
                    onChange={(e) => handleBackgroundOpacityChange(e.target.value)}
                    className="background-panel__slider background-panel__slider--accent"
                    aria-label="Background opacity"
                  />
                </div>
              </div>

              <div className="background-panel__section">
                <span className="background-panel__label">Recent colors</span>
                {colorHistory.length ? (
                  <div className="background-panel__swatches" role="list">
                    {colorHistory.slice(0, 8).map((color) => (
                      <button
                        key={color}
                        type="button"
                        className="background-panel__swatch"
                        style={{ background: color }}
                        title={color}
                        onClick={() => handleRecentColorClick(color)}
                        aria-label={`Apply ${color} to ${colorPickerTab === 'solid' ? 'solid fill' : 'gradient stop'}`}
                      ></button>
                    ))}
                  </div>
                ) : (
                  <div className="background-panel__recent-empty">
                    Pick colors to populate your quick history.
                  </div>
                )}
              </div>

              {colorPickerTab === 'gradient' ? (
                <div className="background-panel__section">
                  <span className="background-panel__label">Default gradients</span>
                  <div className="background-panel__swatches" role="list">
                    {DEFAULT_GRADIENTS.map((gradient, index) => (
                      <button
                        key={index}
                        type="button"
                        className="background-panel__swatch"
                        style={{ background: gradient }}
                        title={gradient}
                        onClick={() => handleDefaultGradientClick(gradient)}
                        aria-label={`Apply default gradient ${index + 1}`}
                      ></button>
                    ))}
                  </div>
                </div>
              ) : (
                <div className="background-panel__section">
                  <span className="background-panel__label">Default solid colors</span>
                  <div className="background-panel__swatches" role="list">
                    {DEFAULT_SOLID_COLORS.map((color, index) => (
                      <button
                        key={index}
                        type="button"
                        className="background-panel__swatch"
                        style={{ background: color }}
                        title={color}
                        onClick={() => handleDefaultSolidColorClick(color)}
                        aria-label={`Apply default solid color ${color}`}
                      ></button>
                    ))}
                  </div>
                </div>
              )}
            </div>
          </div>
        );
      case 'colors':
        return (
          <div className="builder-sidebar__content">
            <div className="builder-sidebar__header">
              <h2>Palette Color</h2>
              <button
                type="button"
                className="builder-sidebar-toggle"
                onClick={onToggleSidebar}
                aria-label="Hide sidebar"
                title="Hide sidebar"
              >
                <i className="fas fa-chevron-left" aria-hidden="true"></i>
              </button>
            </div>
            <p>Generate beautiful color palettes and access color tools for your designs.</p>

            {renderPaletteGenerator({
              buttonLabel: 'Generate Colors',
              helperInstruction: 'Click a swatch to color the selected layer.',
              onApply: applyColorToSelection,
              placeholder: 'Search palette type (e.g. birthday, pastel, wedding)',
            })}
          </div>
        );
      case 'quotes':
        return (
          <div className="builder-sidebar__content">
            <div className="builder-sidebar__header">
              <h2>Quotes</h2>
              <button
                type="button"
                className="builder-sidebar-toggle"
                onClick={onToggleSidebar}
                aria-label="Hide sidebar"
                title="Hide sidebar"
              >
                <i className="fas fa-chevron-left" aria-hidden="true"></i>
              </button>
            </div>
            <p>Generate inspirational quotes to add to your design.</p>
            <div className="builder-sidebar__provider-note">
              Quotes powered by ZenQuotes API. Please display attribution when publishing your design.
            </div>
            <div className="builder-sidebar__tool-actions">
              <button type="button" className="tool-action-btn" onClick={() => handleQuoteSearch('')} disabled={isSearchingQuotes}>
                {isSearchingQuotes ? 'Generating...' : (quoteSearchResults.length > 0 ? 'Load More Quotes' : 'Generate Quotes')}
              </button>
            </div>
            {!hasTriggeredQuoteSearchRef.current && (
              <div className="builder-sidebar__section">
                <div className="builder-sidebar__quote-list">
                  {DEFAULT_QUOTES.map((quote, index) => (
                    <button
                      key={index}
                      type="button"
                      className="quote-result-item"
                      onClick={() => handleUseDefaultQuote(quote)}
                      title={quote}
                    >
                      <div className="quote-content">
                        <blockquote>"{quote}"</blockquote>
                      </div>
                    </button>
                  ))}
                </div>
              </div>
            )}
            {!hasTriggeredQuoteSearchRef.current && (
              <div className="builder-sidebar__hint" style={{ marginBottom: '10px', fontSize: '12px', color: '#666' }}>
                Click "Generate Quotes" to get inspirational quotes:
              </div>
            )}
            {quoteSearchResults.length > 0 ? (
              <div className="builder-sidebar__quote-results">
                {quoteSearchResults.map((quote) => (
                  <button
                    key={quote.id}
                    type="button"
                    className="quote-result-item"
                    onClick={() => handleUseQuote(quote)}
                    title={`"${quote.content}" — ${quote.author}`}
                  >
                    <div className="quote-content">
                      <blockquote>"{quote.content}"</blockquote>
                      <cite>— {quote.author}</cite>
                    </div>
                  </button>
                ))}
                {hasMoreQuotes && (
                  <>
                    <div className="quote-loading-indicator">
                      <div className={`spinner ${isLoadingMoreQuotes ? 'loading' : ''}`}></div>
                      <span>
                        {isLoadingMoreQuotes
                          ? 'Loading more quotes...'
                          : 'Scroll for more quotes'}
                      </span>
                    </div>
                    <div className="load-more-container">
                      <button
                        type="button"
                        className="load-more-btn"
                        onClick={handleLoadMoreQuotes}
                        disabled={isLoadingMoreQuotes}
                      >
                        {isLoadingMoreQuotes ? 'Loading...' : 'Load More Quotes'}
                      </button>
                    </div>
                  </>
                )}
              </div>
            ) : hasTriggeredQuoteSearchRef.current ? (
              <div className="builder-sidebar__empty-state">
                {isSearchingQuotes ? 'Generating quotes...' : 'No quotes available. Try generating again.'}
              </div>
            ) : null}
          </div>
        );
      case 'icons':
        return (
          <div className="builder-sidebar__content">
            <div className="builder-sidebar__header">
              <h2>Icons</h2>
              <button
                type="button"
                className="builder-sidebar-toggle"
                onClick={onToggleSidebar}
                aria-label="Hide sidebar"
                title="Hide sidebar"
              >
                <i className="fas fa-chevron-left" aria-hidden="true"></i>
              </button>
            </div>
            <p>Search for icons from multiple providers.</p>
            <div className="builder-sidebar__provider-note">
              Icons from Simple Icons, IconFinder, Iconify, and Flaticon. Please display attribution when publishing your design.
            </div>
            <div className="builder-sidebar__search">
              <input
                ref={(input) => { searchInputRef.current = input; }}
                type="text"
                placeholder="Search for icons..."
                value={iconSearchQuery}
                onChange={(e) => setIconSearchQuery(e.target.value)}
                onKeyDown={(e) => e.key === 'Enter' && handleIconSearch(e.target.value)}
              />
              <button type="button" onClick={() => handleIconSearch(searchInputRef.current?.value || iconSearchQuery)} disabled={isSearchingIcons}>
                {isSearchingIcons ? 'Searching...' : 'Search'}
              </button>
            </div>
            {iconSearchResults.length > 0 ? (
              <div className="builder-sidebar__icon-results">
                {iconSearchResults.map((icon) => (
                  <button
                    key={icon.id}
                    type="button"
                    className="search-result-thumb"
                    onClick={() => handleUseIcon(icon)}
                    title={`${icon.description} (${icon.providerLabel})`}
                  >
                    <img
                      src={icon.thumbUrl}
                      alt={icon.description}
                      style={{ width: 64, height: 64, objectFit: 'contain', backgroundColor: '#fff' }}
                      onError={(e) => {
                        // Replace broken/missing icon image with a tiny inline SVG placeholder
                        try {
                          e.target.onerror = null;
                          const label = (icon.description || 'ic').toUpperCase().slice(0, 2);
                          const fallbackSvg = `<svg xmlns='http://www.w3.org/2000/svg' width='64' height='64'><rect width='100%' height='100%' fill='%23f3f4f6'/><text x='50%' y='50%' font-size='14' dominant-baseline='middle' text-anchor='middle' fill='%236b7280' font-family='Arial,Helvetica,sans-serif'>${label}</text></svg>`;
                          e.target.src = 'data:image/svg+xml;utf8,' + encodeURIComponent(fallbackSvg);
                        } catch (err) {
                          /* ignore fallback errors */
                        }
                      }}
                    />
                    {icon.providerLabel !== 'Iconify' && (
                      <span className="search-result-provider-tag">{icon.providerLabel}</span>
                    )}
                  </button>
                ))}
                {hasMoreIcons && (
                  <>
                    <div className="icon-loading-indicator">
                      <div className={`spinner ${isLoadingMoreIcons ? 'loading' : ''}`}></div>
                      <span>
                        {isLoadingMoreIcons
                          ? 'Loading more icons...'
                          : ''}
                      </span>
                    </div>
                    <div className="load-more-container">
                      <button
                        type="button"
                        className="load-more-btn"
                        onClick={handleLoadMoreIcons}
                        disabled={isLoadingMoreIcons}
                      >
                        {isLoadingMoreIcons ? 'Loading...' : ''}
                      </button>
                    </div>
                  </>
                )}
              </div>
            ) : hasTriggeredIconSearchRef.current ? (
              <div className="builder-sidebar__empty-state">
                {isSearchingIcons ? 'Searching for icons...' : 'No icons found. Try a different search term.'}
              </div>
            ) : null}
          </div>
        );
      case 'layers':
        return (
          <div className="builder-sidebar__content builder-sidebar__content--layers">
            <div className="builder-sidebar__header">
              <h2>Layers</h2>
              <button
                type="button"
                className="builder-sidebar-toggle"
                onClick={onToggleSidebar}
                aria-label="Hide sidebar"
                title="Hide sidebar"
              >
                <i className="fas fa-chevron-left" aria-hidden="true"></i>
              </button>
            </div>
            <LayersPanel />
          </div>
        );
      default:
        return (
          <div className="builder-sidebar__content">
            <div className="builder-sidebar__header">
              <h2>{TOOL_SECTIONS.find((tool) => tool.id === activeTool)?.label ?? 'Tools'}</h2>
              <button
                type="button"
                className="builder-sidebar-toggle"
                onClick={onToggleSidebar}
                aria-label="Hide sidebar"
                title="Hide sidebar"
              >
                <i className="fas fa-chevron-left" aria-hidden="true"></i>
              </button>
            </div>
            <p>Additional creative resources will appear here soon.</p>
            <div className="builder-sidebar__empty-state">
              Tool-specific controls will render here as the builder matures.
            </div>
          </div>
        );
    }
  };

  return (
    <>
      <nav className={`builder-sidebar ${isSidebarHidden ? 'is-collapsed' : ''}`} aria-label="Primary design tools">
        <div className="builder-sidebar__tabs" role="tablist" aria-orientation="vertical">
          {TOOL_SECTIONS.map((tool) => (
            <button
              key={tool.id}
              type="button"
              role="tab"
              aria-selected={activeTool === tool.id}
              className={`builder-sidebar__tab ${activeTool === tool.id ? 'is-active' : ''}`}
              onClick={() => setActiveTool(tool.id)}
            >
              <i className={tool.icon} aria-hidden="true"></i>
              <span>{tool.label}</span>
            </button>
          ))}
          {isSidebarHidden && (
            <button
              type="button"
              className="builder-sidebar__expand-toggle"
              onClick={onToggleSidebar}
              aria-label="Expand sidebar"
              title="Expand sidebar"
            >
              <i className="fas fa-chevron-right" aria-hidden="true"></i>
            </button>
          )}
        </div>
        {!isSidebarHidden && (
          <div className="builder-sidebar__panel" role="tabpanel" aria-live="polite">
            {renderToolContent()}
          </div>
        )}
      </nav>
      <input
        type="file"
        ref={fileInputRef}
        onChange={handleFileSelect}
        accept="image/*"
        style={{ display: 'none' }}
      />
    </>
  );
}
