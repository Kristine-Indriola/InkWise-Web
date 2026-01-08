// Simple test script for InkWise canvas capture
console.log('=== Simple Canvas Test ===');

const testCanvas = document.querySelector('.fold-container') || document.querySelector('.canvas-viewport__stage');
console.log('Canvas found:', !!testCanvas);

if (testCanvas) {
  const testStage = testCanvas.closest('.canvas-viewport__stage') || testCanvas;
  const testLayers = testStage.querySelectorAll('[data-preview-node]');
  console.log('Layers found:', testLayers.length);

  const testFonts = new Set();
  testLayers.forEach(layer => {
    const style = window.getComputedStyle(layer);
    const fontFamily = style.fontFamily.split(',')[0].replace(/['"]/g, '').trim();
    if (fontFamily && !fontFamily.includes('system-ui') && !fontFamily.includes('sans-serif')) {
      testFonts.add(fontFamily);
    }
  });
  console.log('Fonts detected:', Array.from(testFonts));

  console.log('Testing font loading...');
  testFonts.forEach(async font => {
    try {
      await document.fonts.load(`12px "${font}"`);
      console.log(`✓ Font loaded: ${font}`);
    } catch (e) {
      console.log(`✗ Font failed: ${font}`);
    }
  });
}

console.log('=== Test Complete ===');