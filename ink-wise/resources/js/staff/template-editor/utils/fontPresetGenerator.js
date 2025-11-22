/**
 * fontPresetGenerator.js
 *
 * Generates many short (1-2 word) styled text presets for preview cards similar to Canva's
 * ready-made fonts panel. Presets are created for given categories and pair with provided
 * curated font combinations. Words are chosen to avoid repeats within a category.
 *
 * Usage:
 *   import generateFontPresets from './fontPresetGenerator';
 *   const presets = generateFontPresets(curatedCombos, { perCategory: 12 });
 */

function shuffle(array) {
  for (let i = array.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [array[i], array[j]] = [array[j], array[i]];
  }
  return array;
}

const THEME_CONFIG = {
  birthday: {
    descriptor: 'Celebration',
    phrases: [
      'Party Pop', 'Cake Day', 'Spark Joy', 'Wish Glow', 'Confetti', 'Happy Vibes', 'Cheer Up', 'Fun Burst',
      'Sugar Rush', 'Joy Ride', 'Glow Up', 'Gift Time', 'Funfetti', 'Sweet Bash', 'Party Vibe', 'Cake Rush',
      'Festive Pop', 'Neon Fun', 'Sparkle', 'Cheer Wave', 'Joy Beam', 'Glitz Pop', 'Star Bash', 'Color Pop',
      'Fun Bloom', 'Bright Bash', 'Lively Duo', 'Pop Joy', 'Glitter Pop', 'Glow Fest'
    ]
  },
  corporate: {
    descriptor: 'Executive',
    phrases: [
      'Prime Edge', 'Bold Pitch', 'Team Sync', 'Strategy Hub', 'Future Desk', 'Growth Lab', 'Vision Deck',
      'Focus Meet', 'Sharp Line', 'Core Plan', 'Pulse Ops', 'Scale Up', 'Insight Grid', 'Brand Apex',
      'Data Flow', 'Venture Co', 'Logic Hub', 'Drive Co', 'Power Desk', 'Beyond HQ', 'Clarity Lab',
      'Metric Mode', 'Peak Brief', 'Unity Team', 'Agenda Pro', 'Project Grid', 'Summit Note', 'Lead Sync',
      'Bright Co', 'Studio HQ'
    ]
  },
  baptism: {
    descriptor: 'Sacred',
    phrases: [
      'Pure Grace', 'Holy Light', 'Sacred Joy', 'Blessed Flow', 'Faith Rise', 'Grace Note', 'Gentle Waters',
      'Pure Hope', 'Divine Glow', 'Sacred Bloom', 'Peace Tide', 'Hope Wave', 'Kind Light', 'Serene Gift',
      'Grace Path', 'Soul Shine', 'Angel Dawn', 'Pure Promise', 'Light Song', 'Calm River', 'Soft Halo',
      'Gentle Flame', 'Grace Drift', 'Hope Bloom', 'Dove Glow', 'Quiet Joy', 'Still Light', 'Angel Whisper',
      'Sacred Rise', 'Grace Aura'
    ]
  },
  wedding: {
    descriptor: 'Romantic',
    phrases: [
      'True Love', 'Ever Us', 'Golden Vow', 'Sweet Knot', 'Heart Duo', 'Luxe Union', 'Pure Romance',
      'Velvet Kiss', 'Love Bloom', 'Charm Pair', 'Crystal Vow', 'Forever You', 'Bliss Bond', 'Tender Two',
      'Moonlight', 'Rose Promise', 'Soulmate', 'Joyful Duo', 'Unity Glow', 'Dream Pair', 'Soft Embrace',
      'Aurora Kiss', 'Gentle Vow', 'Eternal Two', 'Velvet Vow', 'Pearl Love', 'Blush Union', 'Amor Duo',
      'Starry Kiss', 'Golden Duo'
    ]
  }
};

/**
 * Generate a list of styled text presets.
 * @param {Array} curatedCombos - Array of font combo objects (with `id`, `label`, `layers` etc.)
 * @param {Object} options
 * @param {number} options.perCategory - How many presets to generate per category (default 12)
 * @param {Object} options.categoryWords - Optional custom words per category
 * @returns {Array} Array of preset objects consumable by `ToolSidebar.jsx` (id, label, description, layers, category)
 */
export default function generateFontPresets(curatedCombos = [], options = {}) {
  const perCategory = options.perCategory || 20;
  const themeConfig = { ...THEME_CONFIG, ...(options.themeConfig || {}) };

  const categories = Object.keys(themeConfig);
  const presets = [];

  categories.forEach((category) => {
    const config = themeConfig[category];
    if (!config) {
      return;
    }

    const pool = shuffle([...(config.phrases || [])]);
    const takeCount = Math.min(perCategory, pool.length);

    // distribute presets across curated combos round-robin
    for (let i = 0; i < takeCount; i++) {
      const combo = curatedCombos[i % Math.max(1, curatedCombos.length)];
      const phrase = pool[i];
      const tokens = (phrase || '').split(/\s+/).filter(Boolean);
      const headingText = tokens[0] ?? '';
      const subText = tokens.length > 1 ? tokens.slice(1).join(' ') : '';

      // create layers using combo layers but swap contents to short text
      const layers = (combo?.layers || []).map((srcLayer, idx) => ({
        role: srcLayer.role ?? (idx === 0 ? 'heading' : 'subheading'),
        family: srcLayer.family || (idx === 0 ? 'Inter' : 'Georgia'),
        fallback: srcLayer.fallback || (idx === 0 ? 'sans-serif' : 'serif'),
        fontSize: idx === 0 ? Math.max(18, Math.round((srcLayer.fontSize || 36) * 0.72)) : Math.max(10, Math.round((srcLayer.fontSize || 16) * 0.7)),
        fontWeight: srcLayer.fontWeight || (idx === 0 ? '600' : '400'),
        content: idx === 0 ? headingText : (idx === 1 ? subText : ''),
        transform: srcLayer.transform || 'none',
        align: srcLayer.align || 'center',
        letterSpacing: srcLayer.letterSpacing ?? 0,
        offsetY: srcLayer.offsetY ?? (idx === 0 ? 0 : 40)
      }));

      presets.push({
        id: `${category}-${i}-${combo?.id ?? 'combo'}`,
        label: config.descriptor ? `${combo?.label ?? 'Preset'} · ${config.descriptor}` : (combo?.label ?? 'Preset'),
        description: config.descriptor ?? '',
        category,
        layers
      });
    }
  });

  return presets;
}
