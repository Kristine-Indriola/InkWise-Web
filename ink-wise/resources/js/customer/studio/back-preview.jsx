// Minimal back preview entry
document.addEventListener('DOMContentLoaded', () => {
  const payload = (typeof window !== 'undefined' && window.inkwiseStudioBootstrap) ? window.inkwiseStudioBootstrap : {};
  const assets = payload.assets || {};
  const svgUrls = payload.svg || {};

  const container = document.getElementById('back-preview-root');
  if (!container) return;

  const card = document.createElement('div');
  card.className = 'preview-card front-card active';
  card.innerHTML = `
    <div class="preview-card-inner">
      <div class="preview-card-bg" id="back-preview-bg"></div>
      <svg id="back-preview-svg" class="preview-svg"></svg>
    </div>
  `;
  container.appendChild(card);

  // Set background if available
  const bgUrl = assets.back_image || (payload.preview_images && payload.preview_images.back) || null;
  if (bgUrl) {
    const bgEl = document.getElementById('back-preview-bg');
    if (bgEl) bgEl.style.backgroundImage = `url('${bgUrl}')`;
  }

  // If SVG provided, fetch and inject
  const svgUrl = svgUrls.back || null;
  if (svgUrl) {
    fetch(svgUrl, { cache: 'no-store' })
      .then((r) => r.text())
      .then((text) => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(text, 'image/svg+xml');
        const svgRoot = doc.documentElement;
        const target = document.getElementById('back-preview-svg');
        if (svgRoot && target) {
          // copy children
          while (svgRoot.firstChild) {
            target.appendChild(svgRoot.firstChild);
          }
          // copy viewBox/attributes
          if (svgRoot.getAttribute('viewBox')) target.setAttribute('viewBox', svgRoot.getAttribute('viewBox'));
          target.setAttribute('preserveAspectRatio', 'xMidYMid meet');
        }
      })
      .catch(() => {});
  }
});

export default {};
