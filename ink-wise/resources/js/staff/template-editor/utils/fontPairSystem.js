/**
 * Font Pair System Module
 *
 * This module manages font pairings similar to Canva, providing predefined font combinations
 * for headlines and subtext. It supports various font styles including Serif, Sans-Serif,
 * Script, Decorative, and Bold combinations.
 *
 * @module FontPairSystem
 */

/**
 * Array of predefined font pairs. Each pair contains headline and subtext fonts
 * with appropriate fallbacks for web use.
 * @type {Array<{headline: string, subtext: string}>}
 */
const predefinedFontPairs = [
  // Serif headline + Sans subtext
  {
    headline: "'Playfair Display', serif",
    subtext: "'Source Sans Pro', sans-serif"
  },
  {
    headline: "'Crimson Text', serif",
    subtext: "'Open Sans', sans-serif"
  },
  {
    headline: "'Libre Baskerville', serif",
    subtext: "'Lato', sans-serif"
  },
  // Sans headline + Serif subtext
  {
    headline: "'Montserrat', sans-serif",
    subtext: "'Merriweather', serif"
  },
  {
    headline: "'Roboto', sans-serif",
    subtext: "'Crimson Text', serif"
  },
  {
    headline: "'Poppins', sans-serif",
    subtext: "'Playfair Display', serif"
  },
  // Script headline + Sans subtext
  {
    headline: "'Dancing Script', cursive",
    subtext: "'Nunito', sans-serif"
  },
  {
    headline: "'Great Vibes', cursive",
    subtext: "'Roboto', sans-serif"
  },
  {
    headline: "'Pacifico', cursive",
    subtext: "'Open Sans', sans-serif"
  },
  // Decorative headline + Sans subtext
  {
    headline: "'Abril Fatface', cursive",
    subtext: "'Work Sans', sans-serif"
  },
  {
    headline: "'Cinzel', serif",
    subtext: "'Space Grotesk', sans-serif"
  },
  {
    headline: "'Oswald', sans-serif",
    subtext: "'Inter', sans-serif"
  },
  // Bold combinations
  {
    headline: "'Bebas Neue', cursive",
    subtext: "'Helvetica', sans-serif"
  },
  {
    headline: "'Anton', sans-serif",
    subtext: "'Georgia', serif"
  },
  {
    headline: "'Bangers', cursive",
    subtext: "'Arial', sans-serif"
  }
];

/**
 * Returns a random font pair from the predefined list.
 *
 * @returns {{headline: string, subtext: string}} A random font pair object
 */
function getRandomFontPair() {
  const randomIndex = Math.floor(Math.random() * predefinedFontPairs.length);
  return predefinedFontPairs[randomIndex];
}

/**
 * Adds a custom font pair to the system.
 *
 * @param {string} headlineFont - The font family for headlines (with fallbacks)
 * @param {string} subtextFont - The font family for subtext (with fallbacks)
 */
function addFontPair(headlineFont, subtextFont) {
  if (!headlineFont || !subtextFont) {
    throw new Error('Both headlineFont and subtextFont must be provided');
  }
  predefinedFontPairs.push({
    headline: headlineFont,
    subtext: subtextFont
  });
}

/**
 * Applies a font pair to an HTML element.
 *
 * @param {string} elementId - The ID of the HTML element to apply fonts to
 * @param {{headline: string, subtext: string}} fontPair - The font pair object
 * @param {string} [type='headline'] - The type of text ('headline' or 'subtext')
 */
function applyFontPair(elementId, fontPair, type = 'headline') {
  const element = document.getElementById(elementId);
  if (!element) {
    console.warn(`Element with ID '${elementId}' not found`);
    return;
  }

  if (!fontPair || !fontPair[type]) {
    console.warn(`Invalid font pair or type '${type}'`);
    return;
  }

  element.style.fontFamily = fontPair[type];
}

// Export the functions for use as a module
export {
  getRandomFontPair,
  addFontPair,
  applyFontPair
};