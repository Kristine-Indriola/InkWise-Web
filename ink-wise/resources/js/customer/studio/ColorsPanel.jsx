import React, { useEffect, useState } from 'react';

const PRESET_GROUPS = [
  {
    label: 'Warm Neutrals',
    colors: [
      { name: 'Ivory Cream', hex: '#FFF6E5' },
      { name: 'Champagne Beige', hex: '#F3E9DC' },
      { name: 'Blush Pink', hex: '#F6C1CC' },
      { name: 'Dusty Rose', hex: '#D8A7B1' },
      { name: 'Light Lavender', hex: '#E8DFF5' },
      { name: 'Pearl Gray', hex: '#F2F2F2' },
    ],
  },
  {
    label: 'Roses & Warm Accents',
    colors: [
      { name: 'Burgundy Wine', hex: '#7A1F2B' },
      { name: 'Mauve Plum', hex: '#8E5A7C' },
      { name: 'Rose Gold', hex: '#E6B7A9' },
      { name: 'Soft Peach', hex: '#FFD6C9' },
      { name: 'Warm Taupe', hex: '#C4B6A6' },
    ],
  },
  {
    label: 'Greens & Blues',
    colors: [
      { name: 'Sage Green', hex: '#A8C3A0' },
      { name: 'Olive Mist', hex: '#C7D3BF' },
      { name: 'Mint Cream', hex: '#E6F4EA' },
      { name: 'Sky Blue', hex: '#DDEEFF' },
      { name: 'Soft Teal', hex: '#B7DED9' },
      { name: 'Navy Blue', hex: '#1F2A44' },
      { name: 'Emerald Green', hex: '#1F7A5B' },
      { name: 'Royal Purple', hex: '#4B2E83' },
      { name: 'Charcoal', hex: '#2E2E2E' },
      { name: 'Midnight Blue', hex: '#0F172A' },
    ],
  },
  {
    label: 'Extra Pastels',
    colors: [
      { name: 'White', hex: '#FFFFFF' },
      { name: 'Cream', hex: '#FFF3D6' },
      { name: 'Light Pink', hex: '#FADADD' },
      { name: 'Baby Blue', hex: '#E6F0FF' },
      { name: 'Mint Green', hex: '#E9F7EF' },
      { name: 'Lavender', hex: '#EFE6FF' },
      { name: 'Soft Yellow', hex: '#FFF9C4' },
      { name: 'Light Gray', hex: '#F2F2F2' },
      { name: 'Peach', hex: '#FFE0CC' },
      { name: 'Sky Blue', hex: '#DFF2FF' },
    ],
  },
  {
    label: 'Basic Colors',
    colors: [
      { name: 'Red', hex: '#FF0000' },
      { name: 'Blue', hex: '#0000FF' },
      { name: 'Yellow', hex: '#FFFF00' },
      { name: 'Green', hex: '#00FF00' },
      { name: 'Orange', hex: '#FFA500' },
      { name: 'Purple', hex: '#800080' },
      { name: 'Pink', hex: '#FFC0CB' },
      { name: 'Brown', hex: '#8B4513' },
      { name: 'Black', hex: '#000000' },
      { name: 'White', hex: '#FFFFFF' },
    ],
  },
];

function ColorsPanel({ bootstrap = {} }) {
  const [color, setColor] = useState('#000000');
  const [recent, setRecent] = useState([]);
  const [applyTarget, setApplyTarget] = useState('selection');

  useEffect(() => {
    try {
      const stored = localStorage.getItem('inkwise:recentColors');
      if (stored) setRecent(JSON.parse(stored));
    } catch (e) {}
  }, []);

  const persistRecent = (c) => {
    try {
      const next = [c, ...recent.filter(r => r !== c)].slice(0, 12);
      setRecent(next);
      localStorage.setItem('inkwise:recentColors', JSON.stringify(next));
    } catch (e) {}
  };

  const applyColor = (c, target = 'selection') => {
    if (!c) return;
    setColor(c);
    persistRecent(c);

    // Use the bridge and dispatch text-style events only when applying to a selection
    const bridge = typeof window !== 'undefined' ? window.inkwiseToolbar : null;
    if (target === 'selection') {
      if (bridge && typeof bridge.setColor === 'function') {
        try { bridge.setColor(c); } catch (e) {}
      }
      try {
        // Signal selection color and text-style change (applies to text toolbar / active text)
        window.dispatchEvent(new CustomEvent('inkwise:color-selected', { detail: { color: c } }));
        window.dispatchEvent(new CustomEvent('inkwise:text-style-changed', { detail: { attribute: 'fill', value: c } }));
      } catch (e) {}
    } else {
      // Still let other listeners know a color was chosen (but avoid applying to text)
      try { window.dispatchEvent(new CustomEvent('inkwise:color-selected', { detail: { color: c } })); } catch (e) {}
      // If target requests background apply, dispatch dedicated event
      if (target === 'front' || target === 'back' || target === 'both') {
        try { window.dispatchEvent(new CustomEvent('inkwise:apply-background', { detail: { side: target, color: c } })); } catch (e) {}
      }
    }

    // Auto-close colors modal when applying to backgrounds
    try {
      if (target === 'front' || target === 'back' || target === 'both') {
        const modal = document.getElementById('colors-modal');
        if (modal) {
          modal.setAttribute('aria-hidden', 'true');
          modal.classList.remove('is-open');
          const navBtn = document.querySelector(`.sidenav-btn[data-nav="colors"]`);
          if (navBtn) navBtn.classList.remove('active');
        }
      }
    } catch (e) {}
  };

  

  return (
    <div className="colors-panel-react" style={{ fontFamily: 'system-ui, sans-serif', padding: 10 }}>
      <div className="colors-panel-inner" style={{ display: 'grid', gap: 12 }}>
        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <div style={{ fontWeight: 600 }}>Choose a color</div>
        </div>

        <div className="colors-custom" style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
          <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
          <label htmlFor="colors-panel-custom" style={{ fontSize: 13 }}>Custom</label>
          <input
            id="colors-panel-custom"
            type="color"
            value={color}
            onChange={(e) => { setColor(e.target.value); applyColor(e.target.value, applyTarget); }}
            style={{ width: 44, height: 32, padding: 0, border: 'none', background: 'transparent' }}
          />
          <input
            type="text"
            value={color}
            onChange={(e) => setColor(e.target.value)}
            onKeyDown={(e) => { if (e.key === 'Enter') applyColor(e.currentTarget.value || color, applyTarget); }}
            onBlur={(e) => applyColor(e.currentTarget.value || color, applyTarget)}
            style={{ marginLeft: 6, width: 110 }}
          />
        </div>

          <div style={{ display: 'flex', gap: 12, alignItems: 'center', flexWrap: 'wrap' }}>
            <label style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
              <input type="radio" name="applyTarget" value="selection" checked={applyTarget === 'selection'} onChange={() => setApplyTarget('selection')} />
              <span style={{ fontSize: 13 }}>Selection</span>
            </label>
            <label style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
              <input type="radio" name="applyTarget" value="front" checked={applyTarget === 'front'} onChange={() => setApplyTarget('front')} />
              <span style={{ fontSize: 13 }}>Front background</span>
            </label>
            <label style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
              <input type="radio" name="applyTarget" value="back" checked={applyTarget === 'back'} onChange={() => setApplyTarget('back')} />
              <span style={{ fontSize: 13 }}>Back background</span>
            </label>
            <label style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
              <input type="radio" name="applyTarget" value="both" checked={applyTarget === 'both'} onChange={() => setApplyTarget('both')} />
              <span style={{ fontSize: 13 }}>Both</span>
            </label>
          </div>
        </div>

        {PRESET_GROUPS.map((group) => (
          <div key={group.label} style={{ display: 'block' }}>
            <div style={{ marginBottom: 6, fontSize: 12, color: '#444' }}>{group.label}</div>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(6, 1fr)', gap: 8, marginBottom: 10 }}>
              {group.colors.map(({ name, hex }) => (
                <button
                  key={hex}
                  type="button"
                  aria-label={`${name} ${hex}`}
                  className="color-swatch"
                  title={`${name} — ${hex}`}
                  style={{ background: hex, width: '100%', height: 36, borderRadius: 6, border: color.toLowerCase() === hex.toLowerCase() ? '2px solid #000' : '1px solid rgba(0,0,0,0.06)' }}
                  onClick={() => applyColor(hex, applyTarget)}
                />
              ))}
            </div>
          </div>
        ))}

        {recent.length > 0 && (
          <div>
            <div style={{ marginBottom: 6, fontSize: 12, color: '#444' }}>Recent</div>
            <div className="recent-grid" style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
              {recent.map(c => (
                <button key={c} type="button" aria-label={`Recent ${c}`} className="color-swatch" style={{ background: c, width: 36, height: 36, borderRadius: 6, border: '1px solid rgba(0,0,0,0.06)' }} onClick={() => applyColor(c, applyTarget)} />
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

export default ColorsPanel;
