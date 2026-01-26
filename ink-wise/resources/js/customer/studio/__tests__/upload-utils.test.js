import { describe, it, expect } from 'vitest';
import { shouldAutoSwitch } from '../upload-utils';

describe('shouldAutoSwitch', () => {
  it('returns false when target side equals current side', () => {
    expect(shouldAutoSwitch('front', 'front', false)).toBe(false);
  });

  it('returns false when activeImageSelected is true', () => {
    expect(shouldAutoSwitch('front', 'back', true)).toBe(false);
  });

  it('returns true when sides differ and no active image selected', () => {
    expect(shouldAutoSwitch('front', 'back', false)).toBe(true);
  });

  it('handles missing current side gracefully', () => {
    expect(shouldAutoSwitch(null, 'back', false)).toBe(true);
  });

  it('returns false for invalid target side', () => {
    expect(shouldAutoSwitch('front', null, false)).toBe(false);
  });
});