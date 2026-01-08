# Frontend Canvas Capture Fix

## Problem
Template previews were saving as blank (0.1 KB) images even though the design JSON saved correctly.

## Root Cause
The `captureCanvasRaster` function was trying to find child elements inside `canvasRef.current`, but `canvasRef` already **IS** the `.fold-container` element that holds the canvas content.

## Solution
Modified `captureCanvasRaster` in `BuilderShell.jsx` to:
1. Use `canvas` parameter directly (it's already the `.fold-container`)
2. Added 100ms delay to ensure all child elements are rendered
3. Enhanced logging to show what elements are being captured

## Changes Made

### BuilderShell.jsx
- **Line 33-70**: Fixed `captureCanvasRaster` function
  - Removed incorrect `querySelector` calls
  - Use `canvas` parameter directly as target
  - Added rendering delay
  - Enhanced diagnostic logging

## Testing Instructions

### 1. Open Template Editor
```
http://localhost/staff/templates/112/editor
```

### 2. Open Browser DevTools
Press **F12** and go to the **Console** tab

### 3. Make a Simple Edit
- Click on any text element
- Change some text
- Or add a new text/image element

### 4. Click "Save Template" Button

### 5. Watch Console Output
You should see detailed logs like:

```
[InkWise Builder] Starting save with canvas ref: <div class="fold-container">
[InkWise Builder] === Capturing preview for page xxx with background #ffffff ===
[InkWise Builder] Page details: {id: "xxx", name: "Page 1", width: 1000, height: 1400, nodesCount: 2}
[InkWise Builder] Capture target: fold-container size 1000 x 1400 children: 1
[InkWise Builder] Canvas has 1 child elements
[InkWise Builder] Element classes found: ["panel", "canvas-viewport__grid", "canvas-layer", ...]
[InkWise Builder] Capture result: SUCCESS (50000+ chars)
```

### 6. Verify Preview File
Run the test command:
```powershell
cd c:\xampp\htdocs\InkWise-Web\ink-wise
php artisan template:test 112
```

Expected output:
```
✓ Preview: 15-50 KB (good size with content)
✓ SVG: 1-10 KB
✓ JSON: 500+ KB (2 nodes)
```

## What to Look For

### ✅ Good Signs
- Console shows "Canvas has X child elements" (X > 0)
- Console shows "Element classes found: [...]" with actual class names
- Console shows "Capture result: SUCCESS"
- Preview file is > 10 KB
- Preview actually shows the template design

### ❌ Bad Signs
- Console shows "Canvas has 0 child elements"
- Console shows "Capture result: FAILED - NO DATA"
- Preview file is 0.1 KB
- Preview is blank/white

## Troubleshooting

### If Preview is Still Blank

1. **Check if nodes exist**:
   - Console should show `nodesCount: 2` or more
   - If 0, the design has no content to capture

2. **Check element rendering**:
   - Open DevTools → Elements tab
   - Find `.fold-container` element
   - Expand it to see `.panel` → `.canvas-layer` elements
   - If empty, there's a rendering issue

3. **Check image loading**:
   - If template has images, check Network tab
   - Verify images loaded successfully (200 status)
   - CORS errors will prevent capture

4. **Check timing**:
   - Added 100ms delay should be enough
   - If still issues, increase delay in line 72 of BuilderShell.jsx

## Test Files Created

1. **test-canvas-capture.html** - Standalone test to verify html2canvas works
   - Open: `http://localhost/test-canvas-capture.html`
   - Click buttons to test different capture targets
   - Should successfully capture and show preview

2. **php artisan template:test** - Backend verification
   - Tests file sizes and structure
   - Shows what's actually saved to disk

## Next Steps

If this fix works:
- Test with other templates
- Test with image-heavy templates
- Test with complex multi-layer designs

If this fix doesn't work:
- Check console logs for specific errors
- Verify html2canvas library is loaded
- Check for CSS/styling conflicts
- May need to capture `.panel` elements individually
