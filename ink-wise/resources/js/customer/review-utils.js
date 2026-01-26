export function looksLikeSvgString(value) {
  if (!value || typeof value !== 'string') return false;
  return /<svg[\s>]/i.test(value.trim());
}

export function isSvgFilename(name) {
  return typeof name === 'string' && name.toLowerCase().endsWith('.svg');
}