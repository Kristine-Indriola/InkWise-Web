// Enhanced test script to run in browser console on template editor page
// This will test the canvas capture functionality with font loading

console.log('=== Enhanced InkWise Canvas Capture Test ===');

// Find the canvas element
const canvas = document.querySelector('.fold-container') || document.querySelector('.canvas-viewport__stage');
console.log('Canvas element found:', canvas);

if (!canvas) {
  console.error('No canvas element found!');
  return;
}

// Check the stage
const stage = canvas.closest('.canvas-viewport__stage') || canvas;
console.log('Stage element:', stage);
console.log('Stage class:', stage.className);

// Check for layers
const layers = stage.querySelectorAll('[data-preview-node]');
console.log('Found', layers.length, 'layers with data-preview-node');

layers.forEach((layer, index) => {
  console.log(`Layer ${index}:`, layer.textContent?.trim() || 'No text', layer.tagName);
  const style = window.getComputedStyle(layer);
  console.log(`  Font: ${style.fontFamily}, Size: ${style.fontSize}, Color: ${style.color}`);
});

// Check computed styles
const computedStyle = window.getComputedStyle(stage);
console.log('Stage transform:', computedStyle.transform);
console.log('Stage display:', computedStyle.display);
console.log('Stage visibility:', computedStyle.visibility);

// Check for images
const images = stage.querySelectorAll('img');
console.log('Found', images.length, 'images');
images.forEach((img, index) => {
  console.log(`Image ${index} src:`, img.src);
  console.log(`Image ${index} loaded:`, img.complete);
});

// Check fonts that need to load
const allElements = stage.querySelectorAll('*');
const fontFamilies = new Set();
allElements.forEach(el => {
  const style = window.getComputedStyle(el);
  const fontFamily = style.fontFamily;
  if (fontFamily && fontFamily !== 'inherit') {
    const primaryFont = fontFamily.split(',')[0].replace(/['"]/g, '').trim();
    if (primaryFont && !primaryFont.includes('system-ui') && !primaryFont.includes('sans-serif') && !primaryFont.includes('serif')) {
      fontFamilies.add(primaryFont);
    }
  }
});
console.log('Fonts to check:', Array.from(fontFamilies));

// Test font loading
async function testFontLoading() {
  if (fontFamilies.size === 0) {
    console.log('No custom fonts to load');
    return true;
  }

  console.log('Testing font loading...');
  const fontLoadPromises = Array.from(fontFamilies).map(async fontFamily => {
    try {
      console.log(`Loading font: ${fontFamily}`);
      const result = await Promise.race([
        document.fonts.load(`12px "${fontFamily}"`).then(() => {
          console.log(`✓ Font "${fontFamily}" loaded successfully`);
          return true;
        }),
        new Promise(resolve => setTimeout(() => {
          console.warn(`✗ Font "${fontFamily}" load timeout`);
          resolve(false);
        }, 2000))
      ]);
      return result;
    } catch (error) {
      console.error(`✗ Font "${fontFamily}" failed to load:`, error);
      return false;
    }
  });

  const results = await Promise.allSettled(fontLoadPromises);
  const loadedCount = results.filter(r => r.status === 'fulfilled' && r.value).length;
  console.log(`Font loading complete: ${loadedCount}/${fontFamilies.size} fonts loaded`);
  return loadedCount > 0;
}

// Test html-to-image capture
async function testCapture() {
  if (!window.htmlToImage) {
    console.error('html-to-image library not available');
    return;
  }

  console.log('Testing html-to-image capture...');

  try {
    const dataUrl = await window.htmlToImage.toPng(stage, {
      backgroundColor: '#ffffff',
      width: stage.scrollWidth,
      height: stage.scrollHeight,
      skipFonts: false, // Ensure fonts are included
    });

    console.log('Capture successful! Data URL length:', dataUrl.length);

    // Create a test image to verify
    const testImg = new Image();
    testImg.onload = () => {
      console.log('✓ Test image loaded successfully, size:', testImg.width, 'x', testImg.height);
      // Check if image has content (not just white)
      const canvas = document.createElement('canvas');
      canvas.width = testImg.width;
      canvas.height = testImg.height;
      const ctx = canvas.getContext('2d');
      ctx.drawImage(testImg, 0, 0);
      const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
      const data = imageData.data;

      // Check if image has non-white pixels
      let hasContent = false;
      for (let i = 0; i < data.length; i += 4) {
        const r = data[i], g = data[i+1], b = data[i+2];
        if (r < 250 || g < 250 || b < 250) { // Not pure white
          hasContent = true;
          break;
        }
      }

      if (hasContent) {
        console.log('✓ Image has content (not blank white)');
      } else {
        console.warn('⚠ Image appears to be blank white');
      }
    };
    testImg.onerror = () => console.error('✗ Test image failed to load');
    testImg.src = dataUrl;

  } catch (error) {
    console.error('✗ Capture failed:', error);
  }
}

// Run the tests
(async () => {
  await testFontLoading();
  await testCapture();
  console.log('=== Test Complete ===');
})();