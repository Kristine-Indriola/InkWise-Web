import { describe, it, expect } from 'vitest';
import { looksLikeSvgString, isSvgFilename } from '../review-utils';

describe('review-utils', () => {
  it('detects svg strings', () => {
    expect(looksLikeSvgString('<svg xmlns="http://www.w3.org/2000/svg"></svg>')).toBe(true);
    expect(looksLikeSvgString('<?xml version="1.0"?>\n<svg></svg>')).toBe(true);
    expect(looksLikeSvgString('<div></div>')).toBe(false);
  });

  it('detects svg file names', () => {
    expect(isSvgFilename('image.svg')).toBe(true);
    expect(isSvgFilename('photo.png')).toBe(false);
    expect(isSvgFilename(null)).toBe(false);
  });
});