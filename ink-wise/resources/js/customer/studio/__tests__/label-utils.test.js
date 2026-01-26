import { describe, it, expect, beforeEach } from 'vitest';
import { updateUploadSideLabel } from '../label-utils';

describe('updateUploadSideLabel', () => {
  beforeEach(() => {
    // Set up DOM elements
    document.body.innerHTML = `
      <button id="upload-button"></button>
      <span id="upload-side-label"></span>
    `;
  });

  it('updates label text and aria on button', () => {
    const text = updateUploadSideLabel('back');
    expect(text).toBe('Back');
    const label = document.getElementById('upload-side-label');
    const btn = document.getElementById('upload-button');
    expect(label.textContent).toBe('Back');
    expect(btn.getAttribute('aria-label')).toBe('Upload image for: Back');
  });

  it('defaults to Front for invalid input', () => {
    const text = updateUploadSideLabel(null);
    expect(text).toBe('Front');
  });
});