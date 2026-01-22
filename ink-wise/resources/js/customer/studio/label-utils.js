export function updateUploadSideLabel(side) {
  const label = typeof document !== 'undefined' ? document.getElementById('upload-side-label') : null;
  const uploadBtn = typeof document !== 'undefined' ? document.getElementById('upload-button') : null;
  const text = typeof side === 'string' && side.length ? side.charAt(0).toUpperCase() + side.slice(1) : 'Front';
  if (label) label.textContent = text;
  if (uploadBtn) uploadBtn.setAttribute('aria-label', `Upload image for: ${text}`);
  return text;
}