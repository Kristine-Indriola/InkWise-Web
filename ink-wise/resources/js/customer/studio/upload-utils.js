export function shouldAutoSwitch(currentSide, targetSide, activeImageSelected) {
  // Only auto-switch when target side differs and user is not currently editing a specific image element
  if (!targetSide || typeof targetSide !== 'string') return false;
  const cur = (currentSide || 'front').toString().toLowerCase();
  const tgt = targetSide.toString().toLowerCase();
  if (cur === tgt) return false;
  if (activeImageSelected) return false;
  return true;
}