import React, { useMemo, useState, useEffect, useRef, useCallback } from 'react';
import { createPortal } from 'react-dom';

import { useBuilderStore } from '../../state/BuilderStore';
import { createLayer } from '../../utils/pageFactory';

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

function centerWithinSafeZone(width, height, page, safeInsets) {
  const availableWidth = Math.max(0, page.width - safeInsets.left - safeInsets.right);
  const availableHeight = Math.max(0, page.height - safeInsets.top - safeInsets.bottom);
  const clampedWidth = Math.min(width, availableWidth);
  const clampedHeight = Math.min(height, availableHeight);
  const x = safeInsets.left + Math.round((availableWidth - clampedWidth) / 2);
  const y = safeInsets.top + Math.round((availableHeight - clampedHeight) / 2);
  return {
    x,
    y,
    width: Math.max(24, clampedWidth),
    height: Math.max(24, clampedHeight),
    rotation: 0,
  };
}

function adjustFrameToSize(frame, width, height, page, safeInsets) {
  const centerX = frame.x + (frame.width / 2);
  const centerY = frame.y + (frame.height / 2);
  const nextFrame = {
    x: Math.round(centerX - width / 2),
    y: Math.round(centerY - height / 2),
    width,
    height,
    rotation: frame.rotation ?? 0,
  };
  return constrainFrameToSafeZone(nextFrame, page, safeInsets);
}

// Organized shape options grouped for the modal
const SHAPE_OPTIONS = [
  // Basic Shapes
  { id: 'rectangle', group: 'basic', label: 'Rectangle', description: 'Standard invitation rectangle', preview: 'rectangle', variant: 'rectangle', borderRadius: 0, getSize: ({ page, safeInsets }) => ({ width: Math.max(140, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.6)), height: Math.max(110, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.36)) }) },
  { id: 'square', group: 'basic', label: 'Square', description: 'Even sides for balanced layouts', preview: 'square', variant: 'rectangle', borderRadius: 8, getSize: ({ page, safeInsets }) => { const available = Math.min(page.width - safeInsets.left - safeInsets.right, page.height - safeInsets.top - safeInsets.bottom); const size = Math.max(120, Math.round(available * 0.55)); return { width: size, height: size }; } },
  { id: 'circle', group: 'basic', label: 'Circle', description: 'Perfect circular badge or avatar', preview: 'circle', variant: 'circle', borderRadius: 9999, getSize: ({ page, safeInsets }) => { const available = Math.min(page.width - safeInsets.left - safeInsets.right, page.height - safeInsets.top - safeInsets.bottom); const size = Math.max(120, Math.round(available * 0.5)); return { width: size, height: size }; } },
  { id: 'oval', group: 'basic', label: 'Oval', description: 'Elongated oval for decorative layouts', preview: 'oval', variant: 'rectangle', borderRadius: 9999, getSize: ({ page, safeInsets }) => { const availableWidth = page.width - safeInsets.left - safeInsets.right; const width = Math.max(200, Math.round(availableWidth * 0.7)); const height = Math.max(110, Math.round(width * 0.44)); return { width, height }; } },
  { id: 'rounded-rectangle', group: 'basic', label: 'Rounded Rectangle', description: 'Rectangle with soft rounded corners', preview: 'rounded-rectangle', variant: 'rectangle', borderRadius: 16, getSize: ({ page, safeInsets }) => ({ width: Math.max(140, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.6)), height: Math.max(100, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.36)) }) },
  { id: 'half-circle', group: 'basic', label: 'Half Circle', description: 'Semi-circle for modern accents', preview: 'half-circle', variant: 'rectangle', borderRadius: 9999, getSize: ({ page, safeInsets }) => ({ width: Math.max(180, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.5)), height: Math.max(90, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.18)) }) },
  { id: 'triangle', group: 'basic', label: 'Triangle', description: 'Simple triangle for pointers or accents', preview: 'triangle', variant: 'polygon', borderRadius: 0, getSize: ({ page, safeInsets }) => ({ width: Math.max(140, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.4)), height: Math.max(120, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.36)) }) },
  { id: 'diamond', group: 'basic', label: 'Diamond (rhombus)', description: 'Rhombus shape for badges', preview: 'diamond', variant: 'polygon', borderRadius: 0, getSize: ({ page, safeInsets }) => ({ width: Math.max(120, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.38)), height: Math.max(120, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.38)) }) },

  // Decorative / Fancy Shapes
  { id: 'scalloped-rectangle', group: 'decorative', label: 'Scalloped Edge Rectangle', description: 'Decorative scalloped border', preview: 'scalloped-rectangle', variant: 'rectangle', borderRadius: 12, getSize: ({ page, safeInsets }) => ({ width: Math.max(180, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.6)), height: Math.max(120, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.36)) }) },
  { id: 'wavy-edge-rectangle', group: 'decorative', label: 'Wavy Edge Rectangle', description: 'Soft wavy edges for a playful look', preview: 'wavy-edge-rectangle', variant: 'rectangle', borderRadius: 10, getSize: ({ page, safeInsets }) => ({ width: Math.max(180, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.6)), height: Math.max(120, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.36)) }) },
  { id: 'curved-edge-square', group: 'decorative', label: 'Curved Edge Square', description: 'Square with curved edges', preview: 'curved-edge-square', variant: 'rectangle', borderRadius: 18, getSize: ({ page, safeInsets }) => { const available = Math.min(page.width - safeInsets.left - safeInsets.right, page.height - safeInsets.top - safeInsets.bottom); const size = Math.max(120, Math.round(available * 0.45)); return { width: size, height: size }; } },
  { id: 'cloud-shape', group: 'decorative', label: 'Cloud Shape', description: 'Soft cloud-like outline', preview: 'cloud-shape', variant: 'organic', borderRadius: 9999, getSize: ({ page, safeInsets }) => ({ width: Math.max(200, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.6)), height: Math.max(130, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.44)) }) },
  { id: 'hexagon', group: 'decorative', label: 'Hexagon', description: 'Geometric hexagon', preview: 'hexagon', variant: 'polygon', borderRadius: 8, getSize: ({ page, safeInsets }) => ({ width: Math.max(140, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.45)), height: Math.max(120, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.42)) }) },
  { id: 'octagon', group: 'decorative', label: 'Octagon', description: 'Eight-sided geometric shape', preview: 'octagon', variant: 'polygon', borderRadius: 6, getSize: ({ page, safeInsets }) => ({ width: Math.max(140, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.45)), height: Math.max(140, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.45)) }) },
  { id: 'heart', group: 'decorative', label: 'Heart', description: 'Romantic heart shape', preview: 'heart', variant: 'organic', borderRadius: 9999, getSize: ({ page, safeInsets }) => { const available = Math.min(page.width - safeInsets.left - safeInsets.right, page.height - safeInsets.top - safeInsets.bottom); const size = Math.max(140, Math.round(available * 0.45)); return { width: size, height: size }; } },
  { id: 'star', group: 'decorative', label: 'Star', description: 'Five-pointed star', preview: 'star', variant: 'polygon', borderRadius: 4, getSize: ({ page, safeInsets }) => ({ width: Math.max(130, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.38)), height: Math.max(130, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.38)) }) },
  { id: 'flower', group: 'decorative', label: 'Flower / Petal Shape', description: 'Petal-style decorative shape', preview: 'flower', variant: 'organic', borderRadius: 9999, getSize: ({ page, safeInsets }) => ({ width: Math.max(150, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.45)), height: Math.max(150, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.45)) }) },
  { id: 'bracket-frame', group: 'decorative', label: 'Bracket Frame', description: 'Classic bracket-style frame', preview: 'bracket-frame', variant: 'frame', borderRadius: 6, getSize: ({ page, safeInsets }) => ({ width: Math.max(180, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.6)), height: Math.max(130, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.4)) }) },
  { id: 'ornate-frame', group: 'decorative', label: 'Ornate Frame', description: 'Vintage ornate border', preview: 'ornate-frame', variant: 'frame', borderRadius: 8, getSize: ({ page, safeInsets }) => ({ width: Math.max(200, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.65)), height: Math.max(150, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.45)) }) },
  { id: 'shield', group: 'decorative', label: 'Shield Shape', description: 'Heraldic shield-style shape', preview: 'shield', variant: 'polygon', borderRadius: 6, getSize: ({ page, safeInsets }) => ({ width: Math.max(150, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.45)), height: Math.max(170, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.5)) }) },
  { id: 'tag-shape', group: 'decorative', label: 'Tag Shape', description: 'Gift-tag with a notch', preview: 'tag-shape', variant: 'tag', borderRadius: 8, getSize: ({ page, safeInsets }) => ({ width: Math.max(140, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.45)), height: Math.max(90, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.28)) }) },
  { id: 'arch-top-rectangle', group: 'decorative', label: 'Arch Top Rectangle', description: 'Rectangle with an arched top', preview: 'arch-top-rectangle', variant: 'rectangle', borderRadius: 18, getSize: ({ page, safeInsets }) => ({ width: Math.max(160, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.55)), height: Math.max(110, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.34)) }) },
  { id: 'half-arch', group: 'decorative', label: 'Half Arch', description: 'Modern half-arch accent', preview: 'half-arch', variant: 'rectangle', borderRadius: 9999, getSize: ({ page, safeInsets }) => ({ width: Math.max(180, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.55)), height: Math.max(90, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.18)) }) },

  // Folded or Layered Shapes
  { id: 'gate-fold', group: 'folded', label: 'Gate Fold', description: 'Double door fold layout', preview: 'gate-fold', variant: 'layout', borderRadius: 0, getSize: ({ page, safeInsets }) => ({ width: Math.max(240, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.75)), height: Math.max(200, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.65)) }) },
  { id: 'tri-fold', group: 'folded', label: 'Tri-Fold', description: 'Three-panel fold', preview: 'tri-fold', variant: 'layout', borderRadius: 0, getSize: ({ page, safeInsets }) => ({ width: Math.max(240, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.75)), height: Math.max(200, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.65)) }) },
  { id: 'z-fold', group: 'folded', label: 'Z-Fold', description: 'Z-style folding panels', preview: 'z-fold', variant: 'layout', borderRadius: 0, getSize: ({ page, safeInsets }) => ({ width: Math.max(240, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.75)), height: Math.max(200, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.65)) }) },
  { id: 'pocket-fold', group: 'folded', label: 'Pocket Fold', description: 'Pocket fold for inserts', preview: 'pocket-fold', variant: 'layout', borderRadius: 4, getSize: ({ page, safeInsets }) => ({ width: Math.max(220, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.7)), height: Math.max(180, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.6)) }) },
  { id: 'tent-fold', group: 'folded', label: 'Tent Fold', description: 'Greeting-card / tent-style', preview: 'tent-fold', variant: 'layout', borderRadius: 4, getSize: ({ page, safeInsets }) => ({ width: Math.max(220, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.7)), height: Math.max(180, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.6)) }) },
  { id: 'layered-rectangle', group: 'folded', label: 'Layered Rectangle', description: 'Stacked rectangle borders for layered look', preview: 'layered-rectangle', variant: 'rectangle', borderRadius: 6, getSize: ({ page, safeInsets }) => ({ width: Math.max(180, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.6)), height: Math.max(120, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.36)) }) },

  // Creative / Themed Shapes
  { id: 'butterfly', group: 'creative', label: 'Butterfly', description: 'Delicate butterfly silhouette', preview: 'butterfly', variant: 'organic', borderRadius: 9999, getSize: ({ page, safeInsets }) => ({ width: Math.max(180, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.5)), height: Math.max(130, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.38)) }) },
  { id: 'leaf', group: 'creative', label: 'Leaf', description: 'Natural leaf motif', preview: 'leaf', variant: 'organic', borderRadius: 9999, getSize: ({ page, safeInsets }) => ({ width: Math.max(160, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.45)), height: Math.max(120, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.36)) }) },
  { id: 'balloon', group: 'creative', label: 'Balloon', description: 'Party balloon outline', preview: 'balloon', variant: 'organic', borderRadius: 9999, getSize: ({ page, safeInsets }) => ({ width: Math.max(120, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.35)), height: Math.max(150, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.45)) }) },
  { id: 'crown', group: 'creative', label: 'Crown', description: 'Royal crown silhouette', preview: 'crown', variant: 'organic', borderRadius: 4, getSize: ({ page, safeInsets }) => ({ width: Math.max(140, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.4)), height: Math.max(90, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.28)) }) },
  { id: 'ticket-shape', group: 'creative', label: 'Ticket Shape', description: 'Event ticket outline', preview: 'ticket-shape', variant: 'tag', borderRadius: 6, getSize: ({ page, safeInsets }) => ({ width: Math.max(160, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.5)), height: Math.max(90, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.28)) }) },
  { id: 'puzzle-piece', group: 'creative', label: 'Puzzle Piece', description: 'Fun puzzle piece shape', preview: 'puzzle-piece', variant: 'organic', borderRadius: 6, getSize: ({ page, safeInsets }) => ({ width: Math.max(140, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.4)), height: Math.max(140, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.4)) }) },
  { id: 'envelope-outline', group: 'creative', label: 'Envelope Outline', description: 'Envelope silhouette', preview: 'envelope-outline', variant: 'rectangle', borderRadius: 6, getSize: ({ page, safeInsets }) => ({ width: Math.max(180, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.55)), height: Math.max(120, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.36)) }) },
  { id: 'ribbon-banner', group: 'creative', label: 'Ribbon Banner', description: 'Decorative ribbon banner', preview: 'ribbon-banner', variant: 'organic', borderRadius: 6, getSize: ({ page, safeInsets }) => ({ width: Math.max(200, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.65)), height: Math.max(80, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.2)) }) },
  { id: 'polaroid-frame', group: 'creative', label: 'Polaroid Frame', description: 'Photo-style Polaroid frame', preview: 'polaroid-frame', variant: 'frame', borderRadius: 6, getSize: ({ page, safeInsets }) => ({ width: Math.max(170, Math.round((page.width - safeInsets.left - safeInsets.right) * 0.5)), height: Math.max(210, Math.round((page.height - safeInsets.top - safeInsets.bottom) * 0.62)) }) },
];

const ALIGNMENT_CONTROLS = [
  {
    id: 'align-left',
    icon: 'fa-solid fa-align-left',
    label: 'Align left',
    apply: (frame) => ({ x: 0 }),
  },
  {
    id: 'align-center-horizontal',
    icon: 'fa-solid fa-align-center',
    label: 'Center horizontally',
    apply: (frame, page) => ({ x: Math.round((page.width - frame.width) / 2) }),
  },
  {
    id: 'align-right',
    icon: 'fa-solid fa-align-right',
    label: 'Align right',
    apply: (frame, page) => ({ x: Math.round(page.width - frame.width) }),
  },
  {
    id: 'align-top',
    icon: 'fa-solid fa-arrow-up',
    label: 'Align top',
    apply: () => ({ y: 0 }),
  },
  {
    id: 'align-center-vertical',
    icon: 'fa-solid fa-arrows-up-down',
    label: 'Center vertically',
    apply: (frame, page) => ({ y: Math.round((page.height - frame.height) / 2) }),
  },
  {
    id: 'align-bottom',
    icon: 'fa-solid fa-arrow-down',
    label: 'Align bottom',
    apply: (frame, page) => ({ y: Math.round(page.height - frame.height) }),
  },
];

const SHAPE_CATEGORIES = [
  { value: 'all', label: 'All Shapes' },
  { value: 'basic', label: 'Basic Shapes' },
  { value: 'decorative', label: 'Decorative / Fancy Shapes' },
  { value: 'folded', label: 'Folded or Layered Shapes' },
  { value: 'creative', label: 'Creative / Themed Shapes' },
];

const GOOGLE_FONTS_API_KEYS = [
  'AIzaSyCSdMyA37wm0nt9gJIZSjTrxEHgXwxBMeM',
  'AIzaSyBRCDdZjTcR4brOsHV_OBsDO11We11BVi0'.replace('BRCD', 'BRCD'),
];
function makeFontsEndpoint(key) {
  return `https://www.googleapis.com/webfonts/v1/webfonts?sort=popularity&fields=items(family,category,variants)&key=${key}`;
}

const DEFAULT_FONT_OPTIONS = [
  { family: 'Inter', category: 'sans-serif', variants: ['400', '500', '600', '700'] },
  { family: 'Poppins', category: 'sans-serif', variants: ['400', '500', '600', '700'] },
  { family: 'Roboto', category: 'sans-serif', variants: ['400', '500', '700'] },
  { family: 'Lato', category: 'sans-serif', variants: ['400', '700'] },
  { family: 'Playfair Display', category: 'serif', variants: ['400', '600'] },
  { family: 'Oswald', category: 'sans-serif', variants: ['400', '500', '600'] },
  { family: 'Merriweather', category: 'serif', variants: ['400', '700'] },
  { family: 'Montserrat', category: 'sans-serif', variants: ['400', '500', '600'] },
  { family: 'Open Sans', category: 'sans-serif', variants: ['300', '400', '600', '700'] },
  { family: 'Source Sans Pro', category: 'sans-serif', variants: ['300', '400', '600', '700'] },
  { family: 'Raleway', category: 'sans-serif', variants: ['300', '400', '600', '700'] },
  { family: 'Nunito', category: 'sans-serif', variants: ['300', '400', '600', '700'] },
  { family: 'PT Sans', category: 'sans-serif', variants: ['400', '700'] },
  { family: 'PT Serif', category: 'serif', variants: ['400', '700'] },
  { family: 'Ubuntu', category: 'sans-serif', variants: ['300', '400', '500', '700'] },
  { family: 'Rubik', category: 'sans-serif', variants: ['300', '400', '500', '700'] },
  { family: 'Fira Sans', category: 'sans-serif', variants: ['300', '400', '700'] },
  { family: 'Work Sans', category: 'sans-serif', variants: ['300', '400', '600', '700'] },
  { family: 'Quicksand', category: 'sans-serif', variants: ['300', '400', '700'] },
  { family: 'Bebas Neue', category: 'display', variants: ['400'] },
  { family: 'Bitter', category: 'serif', variants: ['400', '700'] },
  { family: 'Cabin', category: 'sans-serif', variants: ['400', '700'] },
  { family: 'Cormorant Garamond', category: 'serif', variants: ['400', '500', '600'] },
  { family: 'Dosis', category: 'sans-serif', variants: ['300', '400', '600'] },
  { family: 'EB Garamond', category: 'serif', variants: ['400', '700'] },
  { family: 'Exo 2', category: 'sans-serif', variants: ['300', '400', '600', '700'] },
  { family: 'Inconsolata', category: 'monospace', variants: ['400', '700'] },
  { family: 'Indie Flower', category: 'handwriting', variants: ['400'] },
  { family: 'Karla', category: 'sans-serif', variants: ['400', '700'] },
  { family: 'Kanit', category: 'sans-serif', variants: ['300', '400', '600', '700'] },
  { family: 'Lobster', category: 'display', variants: ['400'] },
  { family: 'Manrope', category: 'sans-serif', variants: ['300', '400', '600', '700'] },
  { family: 'Merriweather Sans', category: 'sans-serif', variants: ['300', '400', '700'] },
  { family: 'Noto Sans', category: 'sans-serif', variants: ['400', '700'] },
  { family: 'Oxygen', category: 'sans-serif', variants: ['300', '400', '700'] },
  { family: 'Play', category: 'sans-serif', variants: ['400', '700'] },
  { family: 'Proza Libre', category: 'sans-serif', variants: ['400', '700'] },
  { family: 'Roboto Slab', category: 'serif', variants: ['300', '400', '700'] },
  { family: 'Saira', category: 'sans-serif', variants: ['300', '400', '600', '700'] },
  { family: 'Titillium Web', category: 'sans-serif', variants: ['200', '400', '600', '700'] },
  { family: 'Varela Round', category: 'sans-serif', variants: ['400'] },
  { family: 'Zilla Slab', category: 'serif', variants: ['400', '700'] },
];

const DEFAULT_FONT_MAP = new Map(DEFAULT_FONT_OPTIONS.map(font => [font.family, font]));

const CURATED_HANDWRITING_FONTS = [
  'Dancing Script',
  'Pacifico',
  'Caveat',
  'Shadows Into Light',
  'Satisfy',
  'Amatic SC',
  'Kalam',
  'Permanent Marker',
  'Rock Salt',
  'Allura',
  'Alex Brush',
  'Bad Script',
  'Marck Script',
  'Pangolin',
  'Great Vibes',
  'Homemade Apple',
  'Kaushan Script',
];

// Function to add more handwriting fonts to the curated list via API
export function addHandwritingFonts(newFonts) {
  if (Array.isArray(newFonts)) {
    const uniqueFonts = newFonts.filter(font => 
      typeof font === 'string' && font.trim() && !CURATED_HANDWRITING_FONTS.includes(font.trim())
    );
    CURATED_HANDWRITING_FONTS.push(...uniqueFonts);
  }
}

export function InspectorPanel() {
  const { state, dispatch } = useBuilderStore();
  const activePage = state.pages.find((page) => page.id === state.activePageId) ?? state.pages[0];
  const safeInsets = useMemo(() => resolveInsets(activePage?.safeZone), [activePage?.safeZone]);
  const selectedLayer = useMemo(() => {
    if (!activePage) return null;
    return activePage.nodes.find((node) => node.id === state.selectedLayerId) ?? null;
  }, [activePage, state.selectedLayerId]);

  // Resize functionality
  const [panelWidth, setPanelWidth] = useState(() => {
    const saved = localStorage.getItem('inspector-panel-width');
    return saved ? parseInt(saved, 10) : 340;
  });
  const [isResizing, setIsResizing] = useState(false);
  const panelRef = useRef(null);
  const fontsLoadedRef = useRef(new Set());
  const fontsFetchedRef = useRef(false);
  const [googleFonts, setGoogleFonts] = useState([]);
  const [loadedFonts, setLoadedFonts] = useState([]);
  const [loadingMore, setLoadingMore] = useState(false);
  const [fontsLoading, setFontsLoading] = useState(false);
  const [isFontModalOpen, setFontModalOpen] = useState(false);
  const [fontSearchTerm, setFontSearchTerm] = useState('');
  const [fontCategoryFilter, setFontCategoryFilter] = useState('all');
  const fontModalRef = useRef(null);
  const fontTriggerRef = useRef(null);
  const [fontModalPosition, setFontModalPosition] = useState(null);
  const [pendingImageScale, setPendingImageScale] = useState(1);
  const [pendingImageOffsetX, setPendingImageOffsetX] = useState(0);
  const [pendingImageOffsetY, setPendingImageOffsetY] = useState(0);
  const replaceImageInputRef = useRef(null);
  const [isShapePaletteOpen, setShapePaletteOpen] = useState(false);
  const [shapeSearchTerm, setShapeSearchTerm] = useState('');
  const [shapeCategoryFilter, setShapeCategoryFilter] = useState('all');
  const shapePaletteRef = useRef(null);
  const shapeButtonRef = useRef(null);
  const [shapeModalPosition, setShapeModalPosition] = useState(null);
  const [editingPageId, setEditingPageId] = useState(null);
  const [editingPageName, setEditingPageName] = useState('');

  const ensureFontLoaded = useCallback((family, variants) => {
    const normalizedFamily = normalizeFontFamilyName(family);
    if (!normalizedFamily || fontsLoadedRef.current.has(normalizedFamily)) {
      return;
    }
    const href = buildGoogleFontHref(normalizedFamily, variants);
    if (!href) {
      return;
    }
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    document.head.appendChild(link);
    fontsLoadedRef.current.add(normalizedFamily);
  }, []);

  const fontsByFamily = useMemo(() => {
    const map = new Map();
    googleFonts.forEach((font) => {
      if (font?.family) {
        map.set(font.family, font);
      }
    });
    return map;
  }, [googleFonts]);

  const primaryFontFamily = useMemo(
    () => normalizeFontFamilyName(selectedLayer?.fontFamily || ''),
    [selectedLayer?.fontFamily],
  );

  const currentFontMeta = useMemo(
    () => fontsByFamily.get(primaryFontFamily) ?? DEFAULT_FONT_MAP.get(primaryFontFamily) ?? null,
    [fontsByFamily, primaryFontFamily],
  );

  const fontChoices = useMemo(() => {
    const baseFonts = loadedFonts.length > 0 ? loadedFonts : DEFAULT_FONT_OPTIONS;
    const currentCategory = currentFontMeta?.category ?? guessCategoryFromValue(selectedLayer?.fontFamily);
    const currentVariants = currentFontMeta?.variants ?? [];
    const currentEntries = primaryFontFamily
      ? [{ family: primaryFontFamily, category: currentCategory, variants: currentVariants }]
      : [];
    const combined = [...currentEntries, ...baseFonts];
    const seen = new Set();
    const result = [];
    for (const font of combined) {
      if (!font || !font.family) {
        continue;
      }
      if (seen.has(font.family)) {
        continue;
      }
      seen.add(font.family);
      result.push(font);
      if (result.length >= 120) {
        break;
      }
    }
    return result;
  }, [loadedFonts, currentFontMeta, primaryFontFamily, selectedLayer?.fontFamily]);

  const fontCategories = useMemo(() => {
    const categories = new Set();
    fontChoices.forEach((font) => {
      if (font?.category) {
        categories.add(font.category);
      }
    });
    return ['all', ...Array.from(categories)];
  }, [fontChoices]);

  const filteredFonts = useMemo(() => {
    const query = fontSearchTerm.trim().toLowerCase();
    return fontChoices.filter((font) => {
      if (!font?.family) {
        return false;
      }
      if (fontCategoryFilter !== 'all' && (font.category ?? '').toLowerCase() !== fontCategoryFilter.toLowerCase()) {
        return false;
      }
      // Special handling for handwriting category - only show curated fonts when we have loaded fonts
      if (fontCategoryFilter === 'handwriting' && loadedFonts.length > 0 && !CURATED_HANDWRITING_FONTS.includes(font.family)) {
        return false;
      }
      if (!query) {
        return true;
      }
      return font.family.toLowerCase().includes(query);
    });
  }, [fontChoices, fontSearchTerm, fontCategoryFilter, loadedFonts.length]);

  const filteredShapeOptions = useMemo(() => {
    const query = shapeSearchTerm.trim().toLowerCase();
    return SHAPE_OPTIONS.filter((shape) => {
      if (shapeCategoryFilter !== 'all' && shape.group !== shapeCategoryFilter) {
        return false;
      }
      if (!query) {
        return true;
      }
      return shape.label.toLowerCase().includes(query) || shape.description.toLowerCase().includes(query);
    });
  }, [shapeSearchTerm, shapeCategoryFilter]);

  useEffect(() => {
    localStorage.setItem('inspector-panel-width', panelWidth.toString());
  }, [panelWidth]);

  useEffect(() => {
    if (!isFontModalOpen) {
      return;
    }
    const handleKeyDown = (event) => {
      if (event.key === 'Escape') {
        setFontModalOpen(false);
      }
    };
    const handleClick = (event) => {
      const modalEl = fontModalRef.current;
      const triggerEl = fontTriggerRef.current;
      if (!modalEl || !triggerEl) {
        return;
      }
      if (!modalEl.contains(event.target) && !triggerEl.contains(event.target)) {
        setFontModalOpen(false);
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mousedown', handleClick);

    return () => {
      document.removeEventListener('keydown', handleKeyDown);
      document.removeEventListener('mousedown', handleClick);
    };
  }, [isFontModalOpen]);

  useEffect(() => {
    if (!isShapePaletteOpen) {
      return undefined;
    }

    const handleKeyDown = (event) => {
      if (event.key === 'Escape') {
        setShapePaletteOpen(false);
      }
    };

    const handleClickOutside = (event) => {
      const paletteEl = shapePaletteRef.current;
      const triggerEl = shapeButtonRef.current;
      if (!paletteEl) {
        return;
      }
      if (paletteEl.contains(event.target)) {
        return;
      }
      if (triggerEl && triggerEl.contains(event.target)) {
        return;
      }
      setShapePaletteOpen(false);
    };

    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('mousedown', handleClickOutside);

    return () => {
      document.removeEventListener('keydown', handleKeyDown);
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [isShapePaletteOpen]);

  // Recompute modal position on scroll/resize while open so it stays adjacent to the panel
  useEffect(() => {
    if (!isFontModalOpen) return undefined;
    function recompute() {
      if (!fontTriggerRef.current || !panelRef.current) return;
      try {
  const triggerRect = fontTriggerRef.current.getBoundingClientRect();
  const panelRect = panelRef.current.getBoundingClientRect();
    const preferredWidth = Math.min(400, Math.max(250, Math.round(triggerRect.width * 1.5)));
      const preferredHeight = Math.min(640, Math.max(360, Math.round(window.innerHeight * 0.6)));
      let left = panelRect.left - preferredWidth - 12 + window.scrollX;
    // Prefer showing the modal well above the trigger; keep viewport clamping below
    let top = triggerRect.top + window.scrollY - 600;
        if (left < 8) {
          left = panelRect.right + 12 + window.scrollX;
        }
        const maxTop = window.scrollY + window.innerHeight - 48;
        if (top > maxTop) top = Math.max(window.scrollY + 8, maxTop - 8);
  if (top < window.scrollY + 8) top = window.scrollY + 8;
  setFontModalPosition({ top, left, width: preferredWidth, height: preferredHeight });
      } catch (err) {
        // ignore
      }
    }

    recompute();
    window.addEventListener('scroll', recompute, { passive: true });
    window.addEventListener('resize', recompute);
    return () => {
      window.removeEventListener('scroll', recompute);
      window.removeEventListener('resize', recompute);
    };
  }, [isFontModalOpen]);

  useEffect(() => {
    if (!isShapePaletteOpen) return undefined;
    function recompute() {
      if (!shapeButtonRef.current || !panelRef.current) return;
      try {
        const triggerRect = shapeButtonRef.current.getBoundingClientRect();
        const panelRect = panelRef.current.getBoundingClientRect();
  // Make the shape modal much wider while keeping a reasonable height
  const preferredWidth = Math.min(window.innerWidth - 24, Math.max(1000, Math.round(triggerRect.width * 3.0)));
  const preferredHeight = 500;
  let left = panelRect.left - preferredWidth - 12 + window.scrollX;
  let top = triggerRect.top + window.scrollY - 6;
        if (left < 8) {
          left = panelRect.right + 12 + window.scrollX;
        }
        const maxTop = window.scrollY + window.innerHeight - 48;
        if (top > maxTop) top = Math.max(window.scrollY + 8, maxTop - 8);
  if (top < window.scrollY + 8) top = window.scrollY + 8;
  setShapeModalPosition({ top, left, width: preferredWidth, height: preferredHeight });
      } catch (err) {
        // ignore
      }
    }

    recompute();
    window.addEventListener('scroll', recompute, { passive: true });
    window.addEventListener('resize', recompute);
    return () => {
      window.removeEventListener('scroll', recompute);
      window.removeEventListener('resize', recompute);
    };
  }, [isShapePaletteOpen]);

  useEffect(() => {
    if (fontsFetchedRef.current) {
      return;
    }
    fontsFetchedRef.current = true;

    const controller = new AbortController();
    setFontsLoading(true);

    async function loadFonts() {
      let success = false;
      for (const key of GOOGLE_FONTS_API_KEYS) {
        if (controller.signal.aborted) break;
        try {
          const url = makeFontsEndpoint(key);
          const response = await fetch(url, { signal: controller.signal });
          if (!response.ok) {
            // try next key
            continue;
          }
          const payload = await response.json();
          const items = Array.isArray(payload?.items) ? payload.items.slice(0, 250) : [];
          
          // Ensure curated handwriting fonts are included
          const handwritingFonts = items.filter(font => CURATED_HANDWRITING_FONTS.includes(font.family));
          const otherFonts = items.filter(font => !CURATED_HANDWRITING_FONTS.includes(font.family));
          const allFonts = [...handwritingFonts, ...otherFonts];
          
          setGoogleFonts(allFonts);
          setLoadedFonts(allFonts.slice(0, 150)); // initial batch of 150 fonts
          success = true;
          break;
        } catch (error) {
          if (controller.signal.aborted) break;
          // try next key
          continue;
        }
      }
      if (!success) {
        console.error('[InkWise Builder] Failed to load Google Fonts with provided API keys.');
      }
      if (!controller.signal.aborted) {
        setFontsLoading(false);
      }
    }

    loadFonts();

    return () => {
      controller.abort();
    };
  }, []);

  const loadMoreFonts = useCallback(() => {
    if (loadingMore || loadedFonts.length >= googleFonts.length) return;
    setLoadingMore(true);
    // Load next batch of 200 fonts
    setLoadedFonts((prev) => {
      const nextBatch = googleFonts.slice(prev.length, prev.length + 200);
      return [...prev, ...nextBatch];
    });
    setLoadingMore(false);
  }, [googleFonts, loadedFonts.length, loadingMore]);

  useEffect(() => {
    if (!primaryFontFamily) {
      return;
    }
    const fontMeta = fontsByFamily.get(primaryFontFamily) ?? DEFAULT_FONT_MAP.get(primaryFontFamily);
    if (!fontMeta) {
      return;
    }
    ensureFontLoaded(primaryFontFamily, fontMeta.variants);
  }, [primaryFontFamily, fontsByFamily, ensureFontLoaded]);

  useEffect(() => {
    const fontsToEnsure = new Set();
    state.pages.forEach((page) => {
      (page.nodes ?? []).forEach((node) => {
        if (node?.type === 'text' && node.fontFamily) {
          const family = normalizeFontFamilyName(node.fontFamily);
          if (family) {
            fontsToEnsure.add(family);
          }
        }
      });
    });

    fontsToEnsure.forEach((family) => {
      const fontMeta = fontsByFamily.get(family) ?? DEFAULT_FONT_MAP.get(family);
      if (fontMeta) {
        ensureFontLoaded(family, fontMeta.variants);
      }
    });
  }, [state.pages, fontsByFamily, ensureFontLoaded]);

  useEffect(() => {
    if (!isFontModalOpen) {
      return;
    }
    filteredFonts.slice(0, 24).forEach((font) => {
      if (font?.family) {
        ensureFontLoaded(font.family, font.variants);
      }
    });
  }, [isFontModalOpen, filteredFonts, ensureFontLoaded]);

  useEffect(() => {
    if (!isFontModalOpen || loadingMore || loadedFonts.length >= googleFonts.length) {
      return;
    }
    // Load more if we have less than 200 fonts loaded
    if (loadedFonts.length < 200) {
      loadMoreFonts();
    }
  }, [isFontModalOpen, loadedFonts.length, googleFonts.length, loadingMore, loadMoreFonts]);

  const handleResizeStart = (e) => {
    setIsResizing(true);
    e.preventDefault();
  };

  const handleResizeMove = (e) => {
    if (!isResizing) return;

    // Calculate new width based on mouse position from the right edge
    const newWidth = Math.max(280, Math.min(600, window.innerWidth - e.clientX));
    setPanelWidth(newWidth);
  };

  const handleResizeEnd = () => {
    setIsResizing(false);
  };

  useEffect(() => {
    if (isResizing) {
      document.addEventListener('mousemove', handleResizeMove);
      document.addEventListener('mouseup', handleResizeEnd);
      document.body.style.cursor = 'ew-resize';
      document.body.style.userSelect = 'none';
    } else {
      document.removeEventListener('mousemove', handleResizeMove);
      document.removeEventListener('mouseup', handleResizeEnd);
      document.body.style.cursor = '';
      document.body.style.userSelect = '';
    }

    return () => {
      document.removeEventListener('mousemove', handleResizeMove);
      document.removeEventListener('mouseup', handleResizeEnd);
      document.body.style.cursor = '';
      document.body.style.userSelect = '';
    };
  }, [isResizing]);

  if (!activePage) {
    return null;
  }

  const layerFrame = selectedLayer ? ensureFrame(selectedLayer.frame, activePage) : null;
  const isTextLayer = selectedLayer?.type === 'text';
  const isImageLayer = selectedLayer?.type === 'image';
  const clipContent = Boolean(selectedLayer?.metadata?.clipContent);
  const rawImageScale = Number(selectedLayer?.metadata?.imageScale);
  const imageScale = Number.isFinite(rawImageScale) ? clamp(rawImageScale, 0.25, 4) : 1;
  const imageScalePercent = Math.round(imageScale * 100);
  const rawOffsetX = Number(selectedLayer?.metadata?.imageOffsetX);
  const rawOffsetY = Number(selectedLayer?.metadata?.imageOffsetY);
  const imageOffsetX = Number.isFinite(rawOffsetX) ? clamp(rawOffsetX, -500, 500) : 0;
  const imageOffsetY = Number.isFinite(rawOffsetY) ? clamp(rawOffsetY, -500, 500) : 0;
  const flipHorizontal = Boolean(selectedLayer?.metadata?.flipHorizontal);
  const flipVertical = Boolean(selectedLayer?.metadata?.flipVertical);

  // Sync pending image state with layer values
  useEffect(() => {
    if (selectedLayer?.type === 'image') {
      setPendingImageScale(imageScale);
      setPendingImageOffsetX(imageOffsetX);
      setPendingImageOffsetY(imageOffsetY);
    }
  }, [selectedLayer?.id, imageScale, imageOffsetX, imageOffsetY]);

  // Debug: log recently uploaded images for development troubleshooting
  useEffect(() => {
    try {
      if (state.recentlyUploadedImages && state.recentlyUploadedImages.length > 0) {
        console.debug('[Inspector] recentlyUploadedImages count=', state.recentlyUploadedImages.length);
        // Log filenames and first 80 chars of data URL to help diagnose preview problems
        state.recentlyUploadedImages.forEach((img, idx) => {
          console.debug(`[Inspector] recent[${idx}]`, img.fileName, (img.dataUrl || '').slice(0, 80));
        });
      }
    } catch (err) {
      // ignore in prod
    }
  }, [state.recentlyUploadedImages]);

  // Page handlers
  const handlePageBackgroundChange = (event) => {
    dispatch({
      type: 'UPDATE_PAGE_PROPS',
      pageId: activePage.id,
      props: { background: event.target.value },
    });
  };

  const handleToggleShapePalette = () => {
    if (isShapePaletteOpen) {
      setShapePaletteOpen(false);
      setShapeSearchTerm('');
      setShapeCategoryFilter('all');
    } else {
      setShapePaletteOpen(true);
      setShapeSearchTerm('');
      setShapeCategoryFilter('all');
      // Calculate initial position
      if (shapeButtonRef.current && panelRef.current) {
        try {
          const triggerRect = shapeButtonRef.current.getBoundingClientRect();
          const panelRect = panelRef.current.getBoundingClientRect();
          // Prefer a large wide modal (width x height). Cap at viewport height and available width.
          const preferredWidth = Math.min(6000, Math.max(3500, triggerRect.width * 4.0));
          // Use fixed height instead of square sizing to keep it wide but not too tall
          const preferredHeight = 500;

          // Constrain by available viewport size (leave small margins)
          const availableTop = window.scrollY + 8;
          const availableBottom = window.scrollY + window.innerHeight - 8;
          const maxAvailableHeight = Math.max(120, availableBottom - availableTop);
          const height = Math.min(preferredHeight, maxAvailableHeight);

          // Horizontal placement: prefer left side of inspector, otherwise place to the right
          let left = panelRect.left - preferredWidth - 12 + window.scrollX;
          if (left < 8) {
            left = panelRect.right + 12 + window.scrollX;
          }

          // Vertical placement: try to align with trigger top, but keep fully visible
          let top = triggerRect.top + window.scrollY - 6;
          // Clamp top so modal fits within viewport
          const maxTopForHeight = Math.max(availableTop, availableBottom - height);
          if (top > maxTopForHeight) top = maxTopForHeight;
          if (top < availableTop) top = availableTop;

          setShapeModalPosition({ top, left, width: preferredWidth, height });
        } catch (err) {
          setShapeModalPosition(null);
        }
      }
    }
  };

  const handleAddPage = () => {
    dispatch({ type: 'ADD_PAGE', page: null });
  };

  const handleSelectPage = (pageId) => {
    dispatch({ type: 'SELECT_PAGE', pageId });
  };

  const handleDeletePage = (pageId, event) => {
    event.stopPropagation(); // Prevent triggering page selection
    dispatch({ type: 'DELETE_PAGE', pageId });
  };

  const handlePageNameClick = (pageId, currentName) => {
    setEditingPageId(pageId);
    setEditingPageName(currentName);
  };

  const handlePageNameChange = (event) => {
    setEditingPageName(event.target.value);
  };

  const handlePageNameSubmit = (pageId) => {
    if (!pageId) {
      return;
    }

    const trimmed = editingPageName.trim();
    if (trimmed) {
      dispatch({
        type: 'UPDATE_PAGE_PROPS',
        pageId,
        props: { name: trimmed },
      });
    }

    setEditingPageId(null);
    setEditingPageName('');
  };

  const handlePageNameKeyDown = (event, pageId) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      handlePageNameSubmit(pageId);
    } else if (event.key === 'Escape') {
      setEditingPageId(null);
      setEditingPageName('');
    }
  };
  const handleLayerChange = (props) => {
    if (!selectedLayer) {
      return;
    }

    const nextProps = { ...props };

    if (props.metadata) {
      nextProps.metadata = {
        ...(selectedLayer.metadata ?? {}),
        ...props.metadata,
      };
    }

    dispatch({
      type: 'UPDATE_LAYER_PROPS',
      pageId: activePage.id,
      layerId: selectedLayer.id,
      props: nextProps,
    });
  };

  const handleLayerVisibilityToggle = () => {
    handleLayerChange({ visible: !(selectedLayer.visible !== false) });
  };

  const handleLayerLockToggle = () => {
    handleLayerChange({ locked: !selectedLayer.locked });
  };

  const handleLayerSideChange = (side) => {
    handleLayerChange({ side });
  };

  const handleSelectShapeOption = (shape) => {
    if (!activePage) {
      return;
    }

    const size = shape.getSize
      ? shape.getSize({ page: activePage, safeInsets })
      : { width: 200, height: 160 };

    if (selectedLayer?.type === 'shape') {
      const currentFrame = ensureFrame(selectedLayer.frame, activePage);
      const nextFrame = adjustFrameToSize(currentFrame, size.width, size.height, activePage, safeInsets);

      handleLayerChange({
        name: shape.label,
        variant: shape.variant ?? selectedLayer.variant,
        borderRadius: shape.borderRadius ?? selectedLayer.borderRadius,
        metadata: {
          ...(selectedLayer.metadata ?? {}),
          ...(shape.metadata ?? {}),
        },
        frame: nextFrame,
      });
    } else {
      // If no layer is actively selected, treat the shape selection as a page-level mask/shape change.
      // This lets the user change the overall page shape (e.g. circular crop) while preserving page dimensions.
      if (!selectedLayer) {
        const pageShape = {
          id: shape.id,
          variant: shape.variant ?? 'rectangle',
          borderRadius: shape.borderRadius ?? 0,
        };

        dispatch({
          type: 'UPDATE_PAGE_PROPS',
          pageId: activePage.id,
          props: { shape: pageShape },
        });
      } else {
        // If a non-shape layer or other selection exists, fall back to adding a new shape layer
        const layer = createLayer('shape', activePage, {
          name: shape.label,
          variant: shape.variant ?? 'rectangle',
          borderRadius: shape.borderRadius ?? 16,
          metadata: shape.metadata ?? {},
        });

        if (layer.frame) {
          const positionedFrame = centerWithinSafeZone(size.width, size.height, activePage, safeInsets);
          layer.frame = constrainFrameToSafeZone(positionedFrame, activePage, safeInsets);
        }

        dispatch({ type: 'ADD_LAYER', pageId: activePage.id, layer });
        dispatch({ type: 'SELECT_LAYER', layerId: layer.id });
      }
    }

    setShapePaletteOpen(false);
    setShapeSearchTerm('');
    setShapeCategoryFilter('all');
  };

  const handleFrameChange = (key) => (event) => {
    const nextValue = Number.parseFloat(event.target.value);
    if (!Number.isFinite(nextValue) || !layerFrame) {
      return;
    }
    const nextFrame = {
      ...layerFrame,
      [key]: key === 'width' || key === 'height' ? Math.max(1, nextValue) : nextValue,
    };
    handleLayerChange({ frame: nextFrame });
  };

  const updateOpacity = (percent) => {
    if (!Number.isFinite(percent)) {
      return;
    }
    const bounded = clamp(percent, 0, 100);
    handleLayerChange({ opacity: bounded / 100 });
  };

  const handleOpacitySliderChange = (event) => {
    updateOpacity(Number.parseInt(event.target.value, 10));
  };

  const handleOpacityInputChange = (event) => {
    updateOpacity(Number.parseInt(event.target.value, 10));
  };

  const handleFillChange = (event) => {
    handleLayerChange({ fill: event.target.value });
  };

  const handleBorderRadiusChange = (event) => {
    const nextValue = Number.parseInt(event.target.value, 10);
    if (!Number.isFinite(nextValue)) {
      return;
    }
    handleLayerChange({ borderRadius: Math.max(0, nextValue) });
  };

  const handleTextContentChange = (event) => {
    handleLayerChange({ content: event.target.value });
  };

  const handleFontSizeChange = (event) => {
    const nextValue = Number.parseInt(event.target.value, 10);
    if (!Number.isFinite(nextValue)) {
      return;
    }
    handleLayerChange({ fontSize: Math.max(8, nextValue) });
  };

  const handleFontWeightChange = (event) => {
    const value = event.target.value;
    // allow numeric or named values
    const normalized = (String(value) || '').trim();
    if (!normalized) return;
    // store as string (CSS accepts numeric strings and named weights)
    handleLayerChange({ fontWeight: normalized });
  };

  const applyFontFamilySelection = useCallback((selectedFamily) => {
    if (!selectedFamily) {
      return;
    }
    const fontMeta = fontsByFamily.get(selectedFamily) ?? DEFAULT_FONT_MAP.get(selectedFamily) ?? null;
    if (fontMeta) {
      ensureFontLoaded(selectedFamily, fontMeta.variants);
    }
    const fallbackCategory = fontMeta?.category ?? guessCategoryFromValue(selectedLayer?.fontFamily);
    const fallback = fallbackForCategory(fallbackCategory);
    const nextFontValue = fallback ? `${selectedFamily}, ${fallback}` : selectedFamily;
    handleLayerChange({ fontFamily: nextFontValue });
  }, [fontsByFamily, selectedLayer?.fontFamily, ensureFontLoaded, handleLayerChange]);

  const handleFontFamilyClick = (family) => {
    applyFontFamilySelection(family);
    setFontModalOpen(false);
  };

  const handleFontSearchChange = (event) => {
    setFontSearchTerm(event.target.value);
  };

  const handleFontCategoryChange = (event) => {
    setFontCategoryFilter(event.target.value);
  };

  const handleShapeSearchChange = (event) => {
    setShapeSearchTerm(event.target.value);
  };

  const handleShapeCategoryChange = (event) => {
    setShapeCategoryFilter(event.target.value);
  };

  const handleTextAlignChange = (event) => {
    handleLayerChange({ textAlign: event.target.value });
  };

  const handleClipToggle = () => {
    handleLayerChange({ metadata: { clipContent: !clipContent } });
  };

  const handleImageFitChange = (event) => {
    const nextFit = event.target.value;
    handleLayerChange({ metadata: { objectFit: nextFit } });
  };

  const handleImageScaleChange = (event) => {
    const nextPercent = Number.parseInt(event.target.value, 10);
    if (!Number.isFinite(nextPercent)) {
      return;
    }
    const clampedPercent = clamp(nextPercent, 25, 400);
    setPendingImageScale(clampedPercent / 100);
  };

  const handleImageOffsetChange = (axis) => (event) => {
    const nextValue = Number.parseInt(event.target.value, 10);
    if (!Number.isFinite(nextValue)) {
      return;
    }
    const clampedValue = clamp(nextValue, -500, 500);
    if (axis === 'x') {
      setPendingImageOffsetX(clampedValue);
    } else {
      setPendingImageOffsetY(clampedValue);
    }
  };

  const handleApplyCrop = () => {
    handleLayerChange({
      metadata: {
        imageScale: pendingImageScale,
        imageOffsetX: pendingImageOffsetX,
        imageOffsetY: pendingImageOffsetY,
      },
    });
  };

  const handleImageCropReset = () => {
    const resetScale = 1;
    const resetOffsetX = 0;
    const resetOffsetY = 0;
    setPendingImageScale(resetScale);
    setPendingImageOffsetX(resetOffsetX);
    setPendingImageOffsetY(resetOffsetY);
    handleLayerChange({ metadata: { imageScale: resetScale, imageOffsetX: resetOffsetX, imageOffsetY: resetOffsetY } });
  };

  const handleRotate = (delta) => () => {
    if (!layerFrame) {
      return;
    }
    const currentRotation = Number.isFinite(layerFrame.rotation) ? layerFrame.rotation : 0;
    const nextRotationRaw = (currentRotation + delta) % 360;
    const nextRotation = nextRotationRaw < 0 ? nextRotationRaw + 360 : nextRotationRaw;
    handleLayerChange({ frame: { ...layerFrame, rotation: Math.round(nextRotation) } });
  };

  const handleFlipToggle = (axis) => () => {
    if (axis === 'horizontal') {
      handleLayerChange({ metadata: { flipHorizontal: !flipHorizontal } });
    } else {
      handleLayerChange({ metadata: { flipVertical: !flipVertical } });
    }
  };

  const handleReplaceImageClick = () => {
    if (!selectedLayer) {
      return;
    }
    replaceImageInputRef.current?.click();
  };

  const handleReplaceImageFile = (event) => {
    const file = event.target.files?.[0];
    if (!file || !selectedLayer) {
      return;
    }

    if (!file.type.startsWith('image/')) {
      alert('Please choose an image file.');
      event.target.value = '';
      return;
    }

    const maxSize = 10 * 1024 * 1024;
    if (file.size > maxSize) {
      alert('Selected image is too large. Please use a file smaller than 10MB.');
      event.target.value = '';
      return;
    }

    const reader = new FileReader();
    reader.onload = async (e) => {
      const dataUrl = e.target?.result;
      if (typeof dataUrl !== 'string' || !dataUrl.startsWith('data:image/')) {
        alert('Unable to load the selected image. Please try again.');
        return;
      }

      // Preload the data URL to ensure the browser can render it.
      const testImg = new Image();
      testImg.onload = async () => {
        // Save file blob to IndexedDB for persistence
        try {
          const dbModule = await import('../../utils/recentImagesDB');
          const id = `recent-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
          // We have the original `file` object; prefer storing it directly
          await dbModule.saveImage(id, file.name, file);
          // prune old entries
          dbModule.pruneOld(10).catch(() => {});

          // Create an objectURL for immediate UI usage (thumbnails)
          const objectUrl = URL.createObjectURL(file);

          dispatch({
            type: 'ADD_RECENTLY_UPLOADED_IMAGE',
            dataUrl: objectUrl,
            fileName: file.name,
          });
        } catch (err) {
          console.error('Failed to persist recent image:', err);
          // Fallback: still add dataUrl inline
          dispatch({ type: 'ADD_RECENTLY_UPLOADED_IMAGE', dataUrl, fileName: file.name });
        }

        handleLayerChange({
              content: dataUrl,
              name: file.name || selectedLayer.name,
              metadata: { objectFit: 'contain', imageScale: 1, imageOffsetX: 0, imageOffsetY: 0 },
            });
      };
      testImg.onerror = () => {
        console.error('Preload failed for data URL');
        alert('Uploaded image could not be rendered. Try a different file.');
      };
      // Start loading
      testImg.src = dataUrl;
    };

    reader.onerror = () => {
      alert('There was a problem reading the file. Please try again.');
    };

    reader.readAsDataURL(file);
    event.target.value = '';
  };

  const handleClearImage = () => {
    if (!selectedLayer) {
      return;
    }

    setPendingImageScale(1);
    setPendingImageOffsetX(0);
    setPendingImageOffsetY(0);

    handleLayerChange({
      content: '',
      metadata: {
        imageScale: 1,
        imageOffsetX: 0,
        imageOffsetY: 0,
      },
    });

    if (replaceImageInputRef.current) {
      replaceImageInputRef.current.value = '';
    }
  };

  const handleUseRecentImage = (imageDataUrl, fileName) => {
    if (!selectedLayer) {
      return;
    }

    handleLayerChange({
      content: imageDataUrl,
      name: fileName || selectedLayer.name,
      metadata: { objectFit: 'contain', imageScale: 1, imageOffsetX: 0, imageOffsetY: 0 },
    });
  };

  const handleLayerDelete = () => {
    if (!selectedLayer) {
      return;
    }

    const confirmed = window.confirm(`Delete "${selectedLayer.name}"? You can undo this action if needed.`);
    if (!confirmed) {
      return;
    }
    dispatch({ type: 'REMOVE_LAYER', pageId: activePage.id, layerId: selectedLayer.id });
  };

  const handleAlignment = (apply) => () => {
    if (!selectedLayer || !layerFrame) {
      return;
    }

    const proposed = apply(layerFrame, activePage) ?? {};
    const maxX = Math.max(0, activePage.width - layerFrame.width);
    const maxY = Math.max(0, activePage.height - layerFrame.height);

    const nextFrame = {
      ...layerFrame,
      ...proposed,
    };

    if (typeof nextFrame.x === 'number') {
      nextFrame.x = clamp(Math.round(nextFrame.x), 0, maxX);
    }
    if (typeof nextFrame.y === 'number') {
      nextFrame.y = clamp(Math.round(nextFrame.y), 0, maxY);
    }

    handleLayerChange({ frame: nextFrame });
  };

  // Render helpers
  const renderPagesSection = () => (
    <div className="inspector-panel__group">
      <div className="inspector-section__header">
        <h3>Pages</h3>
        <button type="button" className="builder-btn inspector-btn--add" onClick={handleAddPage}>
          Add page
        </button>
      </div>
      <div className="inspector-pages__list" role="list">
        {state.pages.map((page) => {
          const isActive = page.id === state.activePageId;
          return (
            <div key={page.id} className="inspector-pages__item-wrapper">
              <button
                type="button"
                className={`inspector-pages__item ${isActive ? 'is-active' : ''}`}
                onClick={() => handleSelectPage(page.id)}
                aria-pressed={isActive}
              >
                <span className="inspector-pages__thumb" aria-hidden="true">
                  <span className="inspector-pages__thumb-page" />
                </span>
                {editingPageId === page.id ? (
                  <input
                    type="text"
                    className="inspector-pages__label-input"
                    value={editingPageName}
                    onChange={handlePageNameChange}
                    onBlur={() => handlePageNameSubmit(page.id)}
                    onKeyDown={(event) => handlePageNameKeyDown(event, page.id)}
                    autoFocus
                  />
                ) : (
                  <span
                    className="inspector-pages__label"
                    onClick={() => handlePageNameClick(page.id, page.name)}
                    style={{ cursor: 'pointer' }}
                  >
                    {page.name}
                  </span>
                )}
              </button>
              {state.pages.length > 1 && (
                <div className="inspector-pages__item-controls">
                  <button
                    type="button"
                    className="inspector-pages__control inspector-pages__control--delete"
                    onClick={(event) => handleDeletePage(page.id, event)}
                    aria-label={`Delete ${page.name}`}
                    title={`Delete ${page.name}`}
                  >
                    <i className="fas fa-trash-alt" aria-hidden="true"></i>
                  </button>
                </div>
              )}
            </div>
          );
        })}
      </div>
    </div>
  );

  const renderPageSection = () => (
    <div className="inspector-panel__group">
      <div className="inspector-section__header">
        <h3>Page</h3>
        <span className="inspector-section__meta">{activePage.width} × {activePage.height}px</span>
      </div>
      <div className="inspector-field" style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
        <label className="inspector-field" style={{ margin: 0 }}>
          <span className="inspector-field__label">Background</span>
          <input
            type="color"
            className="inspector-field__control inspector-field__control--color"
            value={normalizeColorInput(activePage.background, '#ffffff')}
            onChange={handlePageBackgroundChange}
            aria-label="Page background color"
          />
        </label>
        <button
          type="button"
          className="builder-btn inspector-btn"
          onClick={handleToggleShapePalette}
          ref={shapeButtonRef}
          style={{ fontSize: '0.8rem', padding: '0.3rem 0.6rem', height: 'auto', alignSelf: 'flex-start' }}
        >
          SHAPES
        </button>
      </div>
    </div>
  );

  const renderPositionSection = () => (
    <div className="inspector-panel__group">
      <div className="inspector-section__header">
        <h3>Position</h3>
        <span className="inspector-section__meta">X/Y &amp; rotation</span>
      </div>
      <div className="inspector-alignment" role="group" aria-label="Alignment controls">
        {ALIGNMENT_CONTROLS.map((control) => (
          <button
            key={control.id}
            type="button"
            className="inspector-alignment__action"
            onClick={handleAlignment(control.apply)}
            aria-label={control.label}
          >
            <i className={control.icon} aria-hidden="true"></i>
          </button>
        ))}
      </div>

      <div className="inspector-grid inspector-grid--three">
        <label className="inspector-field">
          <span className="inspector-field__label">X</span>
          <input
            type="number"
            className="inspector-field__control"
            value={layerFrame.x}
            onChange={handleFrameChange('x')}
            step="1"
          />
        </label>
        <label className="inspector-field">
          <span className="inspector-field__label">Y</span>
          <input
            type="number"
            className="inspector-field__control"
            value={layerFrame.y}
            onChange={handleFrameChange('y')}
            step="1"
          />
        </label>
        <label className="inspector-field">
          <span className="inspector-field__label">Rotation</span>
          <input
            type="number"
            className="inspector-field__control"
            value={layerFrame.rotation}
            onChange={handleFrameChange('rotation')}
            step="1"
          />
        </label>
      </div>
    </div>
  );

  const renderLayoutSection = () => (
    <div className="inspector-panel__group">
      <div className="inspector-section__header">
        <h3>Layout</h3>
        <span className="inspector-section__meta">Dimensions &amp; flow</span>
      </div>
      <div className="inspector-grid inspector-grid--two">
        <label className="inspector-field">
          <span className="inspector-field__label">Width</span>
          <input
            type="number"
            className="inspector-field__control"
            value={layerFrame.width}
            onChange={handleFrameChange('width')}
            min="1"
            step="1"
          />
        </label>
        <label className="inspector-field">
          <span className="inspector-field__label">Height</span>
          <input
            type="number"
            className="inspector-field__control"
            value={layerFrame.height}
            onChange={handleFrameChange('height')}
            min="1"
            step="1"
          />
        </label>
      </div>

      <label className="inspector-toggle">
        <input
          type="checkbox"
          checked={clipContent}
          onChange={handleClipToggle}
        />
        <span>Clip content</span>
      </label>
    </div>
  );

  const renderAppearanceSection = () => (
    <div className="inspector-panel__group">
      <div className="inspector-section__header">
        <h3>Appearance</h3>
        <span className="inspector-section__meta">Color &amp; effects</span>
      </div>

      <label className="inspector-field">
        <span className="inspector-field__label">Fill</span>
        <input
          type="color"
          className="inspector-field__control inspector-field__control--color"
          value={normalizeColorInput(selectedLayer.fill, '#2563eb')}
          onChange={handleFillChange}
        />
      </label>

      <div className="inspector-opacity">
        <label className="inspector-field inspector-opacity__slider">
          <span className="inspector-field__label">Opacity</span>
          <input
            type="range"
            className="inspector-field__control inspector-field__control--range"
            min="0"
            max="100"
            step="5"
            value={Math.round((selectedLayer.opacity ?? 1) * 100)}
            onChange={handleOpacitySliderChange}
          />
        </label>
        <label className="inspector-field inspector-opacity__input">
          <span className="inspector-field__label">%</span>
          <input
            type="number"
            className="inspector-field__control"
            min="0"
            max="100"
            step="5"
            value={Math.round((selectedLayer.opacity ?? 1) * 100)}
            onChange={handleOpacityInputChange}
          />
        </label>
      </div>

      <label className="inspector-field">
        <span className="inspector-field__label">Corner radius</span>
        <input
          type="number"
          className="inspector-field__control"
          value={selectedLayer.borderRadius ?? 0}
          onChange={handleBorderRadiusChange}
          min="0"
          step="1"
        />
      </label>
    </div>
  );

  const renderTypographySection = () => (
    <div className="inspector-panel__group">
      <div className="inspector-section__header">
        <h3>Typography</h3>
        <span className="inspector-section__meta">{fontsLoading ? 'Loading fonts...' : 'Text content & styling'}</span>
      </div>
      <label className="inspector-field">
        <span className="inspector-field__label">Content</span>
        <textarea
          className="inspector-field__control inspector-field__control--textarea"
          value={selectedLayer.content ?? ''}
          onChange={handleTextContentChange}
          rows={3}
        />
      </label>
      <label className="inspector-field">
        <span className="inspector-field__label">Font</span>
        <div className="inspector-font-picker">
          <button
            type="button"
            className={`inspector-field__control inspector-font-picker__trigger${isFontModalOpen ? ' is-open' : ''}`}
            onClick={() => {
              // Toggle modal and calculate position when opening
              if (!isFontModalOpen && fontTriggerRef.current && panelRef.current) {
                try {
  const triggerRect = fontTriggerRef.current.getBoundingClientRect();
  const panelRect = panelRef.current.getBoundingClientRect();
  const preferredWidth = Math.min(400, Math.max(250, Math.round(triggerRect.width * 1.5)));
                    const preferredHeight = Math.min(640, Math.max(360, Math.round(window.innerHeight * 0.6)));
                    // Position to the left of the inspector panel, aligned to trigger top if possible
                    let left = panelRect.left - preferredWidth - 12 + window.scrollX;
                    let top = triggerRect.top + window.scrollY - 600; // Moved way up (from -300 to -600)

                  // If not enough space on left, place to the right of panel
                  if (left < 8) {
                    left = panelRect.right + 12 + window.scrollX;
                  }

                  // Keep within viewport vertically (but allow higher positioning)
                  const maxTop = window.scrollY + window.innerHeight - 48;
                  if (top > maxTop) top = Math.max(window.scrollY + 8, maxTop - 8);
                  // Allow modal to go higher than minimum scroll position
                  if (top < 0) top = 0;

                  setFontModalPosition({ top, left, width: preferredWidth, height: preferredHeight });
                } catch (err) {
                  setFontModalPosition(null);
                }
              }
              setFontModalOpen((open) => !open);
            }}
            ref={fontTriggerRef}
            aria-expanded={isFontModalOpen}
          >
            <span className="inspector-font-picker__preview">
              <span
                className="inspector-font-picker__preview-name"
                style={{ fontFamily: selectedLayer?.fontFamily || primaryFontFamily || 'Inter, sans-serif' }}
              >
                {primaryFontFamily || 'Select font'}
              </span>
              <span className="inspector-font-picker__preview-meta">
                {primaryFontFamily ? formatFontCategory(currentFontMeta?.category) : 'Choose a font'}
              </span>
            </span>
            <span className="inspector-font-picker__chevron" aria-hidden="true">
              <i className={`fa-solid ${isFontModalOpen ? 'fa-chevron-up' : 'fa-chevron-down'}`}></i>
            </span>
          </button>

          {isFontModalOpen && createPortal(
            <FontPickerModal
              ref={fontModalRef}
              fonts={filteredFonts}
              categories={fontCategories}
              categoryValue={fontCategoryFilter}
              onCategoryChange={handleFontCategoryChange}
              searchTerm={fontSearchTerm}
              onSearchChange={handleFontSearchChange}
              onSelect={handleFontFamilyClick}
              isLoading={fontsLoading}
              currentFamily={primaryFontFamily}
              ensureFontLoaded={ensureFontLoaded}
              loadMoreFonts={loadMoreFonts}
              loadingMore={loadingMore}
              style={fontModalPosition ? { position: 'absolute', top: `${fontModalPosition.top}px`, left: `${fontModalPosition.left}px`, width: `${fontModalPosition.width}px`, ...(fontModalPosition.height ? { height: `${fontModalPosition.height}px` } : {}) } : { position: 'absolute' }}
            />,
            document.body,
          )}
        </div>
      </label>
      <div className="inspector-grid inspector-grid--two">
        <label className="inspector-field">
          <span className="inspector-field__label">Font size</span>
          <input
            type="number"
            className="inspector-field__control"
            value={selectedLayer.fontSize ?? 48}
            onChange={handleFontSizeChange}
            min="8"
            step="1"
          />
        </label>
        <label className="inspector-field">
          <span className="inspector-field__label">Font weight</span>
          <select
            className="inspector-field__control"
            value={String(selectedLayer.fontWeight ?? '400')}
            onChange={handleFontWeightChange}
          >
            <option value="100">Thin</option>
            <option value="200">Extra Light / Ultra Light</option>
            <option value="300">Light</option>
            <option value="400">Regular / Normal</option>
            <option value="500">Medium</option>
            <option value="600">Semi Bold / Demi Bold</option>
            <option value="700">Bold</option>
            <option value="800">Extra Bold / Ultra Bold</option>
            <option value="900">Black / Heavy</option>
            <option value="normal">normal</option>
            <option value="bold">bold</option>
          </select>
        </label>
      </div>
    </div>
  );

  const renderImageSection = () => (
    <div className="inspector-panel__group">
      <div className="inspector-section__header">
        <h3>Image</h3>
        <span className="inspector-section__meta">Source &amp; display</span>
      </div>

      <div className="inspector-field inspector-field--actions">
        <button
          type="button"
          className="builder-btn inspector-btn"
          onClick={handleReplaceImageClick}
        >
          Replace image
        </button>
        {selectedLayer?.content && (
          <button
            type="button"
            className="builder-btn inspector-btn is-muted"
            onClick={handleClearImage}
          >
            Remove image
          </button>
        )}
      </div>

      <div className="inspector-field inspector-field--actions">
        <button
          type="button"
          className="builder-btn inspector-btn"
          onClick={handleRotate(90)}
        >
          Rotate +90°
        </button>
      </div>

      <div className="inspector-field inspector-field--actions">
        <button
          type="button"
          className={`builder-btn inspector-btn ${flipHorizontal ? 'is-active' : ''}`}
          onClick={handleFlipToggle('horizontal')}
        >
          Flip horizontal
        </button>
        <button
          type="button"
          className={`builder-btn inspector-btn ${flipVertical ? 'is-active' : ''}`}
          onClick={handleFlipToggle('vertical')}
        >
          Flip vertical
        </button>
      </div>

      <div className="inspector-field inspector-field--actions">
        <button
          type="button"
          className="builder-btn inspector-btn"
          onClick={handleApplyCrop}
        >
          Crop
        </button>
        <button
          type="button"
          className="builder-btn inspector-btn"
          onClick={handleImageCropReset}
        >
          Reset adjustments
        </button>
      </div>

      <div className="inspector-grid inspector-grid--three">
        <label className="inspector-field">
          <span className="inspector-field__label">Brightness</span>
          <input
            type="range"
            className="inspector-field__control inspector-field__control--range"
            min="0"
            max="200"
            step="5"
            value={selectedLayer.metadata?.brightness ?? 100}
            onChange={(e) => handleLayerChange({ metadata: { brightness: Number.parseInt(e.target.value, 10) } })}
          />
        </label>
        <label className="inspector-field">
          <span className="inspector-field__label">Contrast</span>
          <input
            type="range"
            className="inspector-field__control inspector-field__control--range"
            min="0"
            max="200"
            step="5"
            value={selectedLayer.metadata?.contrast ?? 100}
            onChange={(e) => handleLayerChange({ metadata: { contrast: Number.parseInt(e.target.value, 10) } })}
          />
        </label>
        <label className="inspector-field">
          <span className="inspector-field__label">Saturation</span>
          <input
            type="range"
            className="inspector-field__control inspector-field__control--range"
            min="0"
            max="200"
            step="5"
            value={selectedLayer.metadata?.saturation ?? 100}
            onChange={(e) => handleLayerChange({ metadata: { saturation: Number.parseInt(e.target.value, 10) } })}
          />
        </label>
      </div>

      {/* Recently Uploaded removed from Inspector UI per preference */}
    </div>
  );

  const renderActionsSection = () => (
    <div className="inspector-panel__group inspector-panel__group--actions">
      <div className="inspector-section__header">
        <h3>Layer actions</h3>
        <span className="inspector-section__meta">Visibility &amp; locking</span>
      </div>
      <div className="inspector-field inspector-field--actions">
        <button
          type="button"
          className={`builder-btn inspector-btn ${selectedLayer.side === 'front' ? 'is-active' : ''}`}
          onClick={() => handleLayerSideChange('front')}
        >
          Front
        </button>
        <button
          type="button"
          className={`builder-btn inspector-btn ${selectedLayer.side === 'back' ? 'is-active' : ''}`}
          onClick={() => handleLayerSideChange('back')}
        >
          Back
        </button>
      </div>
      <div className="inspector-field inspector-field--actions">
        <button
          type="button"
          className={`builder-btn inspector-btn ${selectedLayer.visible === false ? 'is-muted' : ''}`}
          onClick={handleLayerVisibilityToggle}
        >
          {selectedLayer.visible === false ? 'Show layer' : 'Hide layer'}
        </button>
        <button
          type="button"
          className={`builder-btn inspector-btn ${selectedLayer.locked ? 'is-active' : ''}`}
          onClick={handleLayerLockToggle}
        >
          {selectedLayer.locked ? 'Unlock layer' : 'Lock layer'}
        </button>
        <button
          type="button"
          className="builder-btn inspector-btn inspector-btn--danger"
          onClick={handleLayerDelete}
        >
          Delete layer
        </button>
      </div>
    </div>
  );

  return (
    <>
      <section
        ref={panelRef}
        className="inspector-panel"
        style={{
          width: `${panelWidth}px`,
          right: 0,
          top: 0,
          bottom: 0,
          position: 'absolute'
        }}
        aria-label="Layer inspector"
      >
        <header className="inspector-panel__header">
          <h2>Inspector</h2>
          <span className={`inspector-chip ${selectedLayer ? '' : 'inspector-chip--muted'}`}>
            {selectedLayer ? selectedLayer.name : 'No layer selected'}
          </span>
        </header>

        <div className="inspector-panel__body inspector-panel__body--unified">
          {renderPagesSection()}
          {renderPageSection()}

          {!selectedLayer && (
            <p className="inspector-panel__empty">Select a layer on the canvas to adjust its position, layout, and appearance.</p>
          )}

          {selectedLayer && (
            <>
              {renderPositionSection()}
              {renderLayoutSection()}
              {isTextLayer && renderTypographySection()}
              {isImageLayer && renderImageSection()}
              {renderAppearanceSection()}
              {renderActionsSection()}
            </>
          )}
        </div>

        <div
          className="inspector-panel__resize-handle"
          onMouseDown={handleResizeStart}
          aria-label="Resize inspector panel"
        />
        <input
          type="file"
          ref={replaceImageInputRef}
          onChange={handleReplaceImageFile}
          accept="image/*"
          style={{ display: 'none' }}
        />
      </section>
      {isShapePaletteOpen && createPortal(
        <div
          ref={shapePaletteRef}
          className="inspector-shape-modal"
          role="dialog"
          aria-label="Choose a shape"
          style={shapeModalPosition ? { position: 'absolute', top: `${shapeModalPosition.top}px`, left: `${shapeModalPosition.left}px`, width: `${shapeModalPosition.width}px`, height: `${shapeModalPosition.height}px` } : { position: 'absolute' }}
        >
          <div className="inspector-shape-modal__header">
            <div>
              <h4>Select a shape</h4>
              <p>Search or pick a shape to add or update.</p>
            </div>
            <button
              type="button"
              className="inspector-shape-modal__close"
              onClick={() => {
                setShapePaletteOpen(false);
                setShapeSearchTerm('');
                setShapeCategoryFilter('all');
              }}
              aria-label="Close shape picker"
            >
              <span aria-hidden="true">×</span>
            </button>
          </div>
          <div className="inspector-field inspector-shape-modal__search">
            <input
              type="search"
              className="inspector-field__control"
              value={shapeSearchTerm}
              onChange={handleShapeSearchChange}
              placeholder="Search shapes"
              aria-label="Search shapes"
              autoFocus
            />
          </div>
          <div className="inspector-field inspector-shape-modal__filter">
            <select
              className="inspector-field__control"
              value={shapeCategoryFilter}
              onChange={handleShapeCategoryChange}
              aria-label="Filter shapes by category"
            >
              {SHAPE_CATEGORIES.map((category) => (
                <option key={category.value} value={category.value}>
                  {category.label}
                </option>
              ))}
            </select>
          </div>
          <div className="inspector-shape-modal__grid">
            {filteredShapeOptions.length > 0 ? (
              filteredShapeOptions.map((shape) => (
                <button
                  key={shape.id}
                  type="button"
                  className="inspector-shape-modal__item"
                  onClick={() => handleSelectShapeOption(shape)}
                >
                  <span className={`inspector-shape-modal__preview inspector-shape-modal__preview--${shape.preview}`} aria-hidden="true" />
                  <span className="inspector-shape-modal__info">
                    <span className="inspector-shape-modal__name">{shape.label}</span>
                    <span className="inspector-shape-modal__description">{shape.description}</span>
                  </span>
                </button>
              ))
            ) : (
              <div className="inspector-shape-modal__empty">No shapes match your search.</div>
            )}
          </div>
        </div>,
        document.body,
      )}
    </>
  );
}

const FontPickerModal = React.forwardRef(function FontPickerModal(
  {
    fonts,
    categories,
    categoryValue,
    onCategoryChange,
    searchTerm,
    onSearchChange,
    onSelect,
    isLoading,
    currentFamily,
    ensureFontLoaded,
    loadMoreFonts,
    loadingMore,
    style,
  },
  ref,
) {
  const searchInputRef = useRef(null);
  const listRef = useRef(null);

  useEffect(() => {
    if (searchInputRef.current) {
      searchInputRef.current.focus();
    }
  }, []);

  useEffect(() => {
    fonts.slice(0, 32).forEach((font) => {
      if (font?.family) {
        ensureFontLoaded(font.family, font.variants);
      }
    });
  }, [fonts, ensureFontLoaded]);

  useEffect(() => {
    const list = listRef.current;
    if (!list) return;
    const handleScroll = () => {
      const { scrollTop, scrollHeight, clientHeight } = list;
      if (scrollTop + clientHeight >= scrollHeight - 200) {
        loadMoreFonts();
      }
    };
    list.addEventListener('scroll', handleScroll);
    return () => list.removeEventListener('scroll', handleScroll);
  }, [loadMoreFonts]);

  return (
  <div className="inspector-font-modal" ref={ref} role="dialog" aria-label="Choose font" style={style}>
      <div className="inspector-font-modal__header">
        <input
          ref={searchInputRef}
          type="text"
          className="inspector-font-modal__search"
          placeholder="Search fonts"
          value={searchTerm}
          onChange={onSearchChange}
          aria-label="Search fonts"
        />
        <select
          className="inspector-font-modal__filter"
          value={categoryValue}
          onChange={onCategoryChange}
          aria-label="Filter fonts by category"
        >
          {categories.map((category) => (
            <option key={category} value={category}>
              {category === 'all' ? 'All fonts' : formatFontCategory(category)}
            </option>
          ))}
        </select>
      </div>
      <div className="inspector-font-modal__list" ref={listRef} role="listbox">
        {isLoading && fonts.length === 0 && (
          <div className="inspector-font-modal__status inspector-font-modal__status--loading">
            <div className="inspector-font-modal__spinner"></div>
            Loading fonts...
          </div>
        )}
        {!isLoading && fonts.length === 0 && (
          <div className="inspector-font-modal__status">No fonts match your search.</div>
        )}
        {fonts.map((font) => {
          const fallback = fallbackForCategory(font.category);
          const previewFamily = fallback ? `${font.family}, ${fallback}` : font.family;
          const isActive = currentFamily && font.family.toLowerCase() === currentFamily.toLowerCase();
          return (
            <button
              key={font.family}
              type="button"
              className={`inspector-font-modal__item${isActive ? ' is-active' : ''}`}
              onClick={() => onSelect(font.family)}
              style={{ fontFamily: previewFamily }}
              role="option"
              aria-selected={isActive}
            >
              <span className="inspector-font-modal__item-name">{font.family}</span>
              <span className="inspector-font-modal__item-meta">{formatFontCategory(font.category)}</span>
            </button>
          );
        })}
        {loadingMore && (
          <div className="inspector-font-modal__status inspector-font-modal__status--loading">
            <div className="inspector-font-modal__spinner"></div>
            Loading more fonts...
          </div>
        )}
      </div>
    </div>
  );
});

// RecentImageButton and filename truncation removed — Recently Uploaded UI is no longer shown in Inspector

function normalizeFontFamilyName(value) {
  if (!value || typeof value !== 'string') {
    return '';
  }
  return value.replace(/["']/g, '').split(',')[0].trim();
}

function buildGoogleFontHref(family, variants) {
  if (!family) {
    return '';
  }
  const encodedFamily = family.trim().replace(/\s+/g, '+');
  let weightPart = '';
  if (Array.isArray(variants)) {
    const numericWeights = variants.filter((variant) => /^\d+$/.test(variant));
    if (numericWeights.length > 0) {
      const uniqueWeights = Array.from(new Set(numericWeights)).slice(0, 4);
      weightPart = `:wght@${uniqueWeights.join(';')}`;
    }
  }
  return `https://fonts.googleapis.com/css2?family=${encodedFamily}${weightPart}&display=swap`;
}

function fallbackForCategory(category) {
  switch ((category || '').toLowerCase()) {
    case 'serif':
      return 'serif';
    case 'monospace':
      return 'monospace';
    case 'handwriting':
      return 'cursive';
    case 'display':
      return 'sans-serif';
    default:
      return 'sans-serif';
  }
}

function guessCategoryFromValue(value) {
  if (!value || typeof value !== 'string') {
    return 'sans-serif';
  }
  const lower = value.toLowerCase();
  if (lower.includes('mono')) {
    return 'monospace';
  }
  if (lower.includes('serif')) {
    return 'serif';
  }
  if (lower.includes('script') || lower.includes('cursive') || lower.includes('handwriting')) {
    return 'handwriting';
  }
  return 'sans-serif';
}

function formatFontCategory(category) {
  if (!category || typeof category !== 'string') {
    return 'Mixed';
  }
  return category
    .split(/[-_\s]+/)
    .filter(Boolean)
    .map((segment) => segment.charAt(0).toUpperCase() + segment.slice(1))
    .join(' ');
}

function clamp(value, min, max) {
  if (!Number.isFinite(value)) {
    return min;
  }
  return Math.min(Math.max(value, min), max);
}

function ensureFrame(frame, page) {
  if (frame && typeof frame === 'object') {
    return {
      x: round(frame.x),
      y: round(frame.y),
      width: Math.max(1, round(frame.width ?? page.width / 2)),
      height: Math.max(1, round(frame.height ?? page.height / 2.5)),
      rotation: round(frame.rotation ?? 0),
    };
  }

  return {
    x: Math.round(page.width * 0.1),
    y: Math.round(page.height * 0.1),
    width: Math.round(page.width * 0.6),
    height: Math.round(page.height * 0.25),
    rotation: 0,
  };
}

function normalizeColorInput(value, fallback = '#2563eb') {
  if (typeof value === 'string') {
    const trimmed = value.trim();
    if (/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/.test(trimmed)) {
      return trimmed;
    }
    if (/^#([a-fA-F0-9]{8})$/.test(trimmed)) {
      return `#${trimmed.slice(1, 7)}`;
    }
  }
  return fallback;
}

function round(value) {
  if (!Number.isFinite(value)) {
    return 0;
  }
  return Math.round(value);
}
