# Template Save System - Diagnostic & Testing Guide

## Overview
The template saving system has been enhanced with comprehensive logging and error handling to identify why previews may be blank/white.

## Changes Made

### 1. Client-Side (BuilderShell.jsx)
- **Enhanced preview capture logging**: Detailed console logs at every step
- **Added canvas content validation**: Checks if canvas has child elements before capture
- **Improved error handling**: Multiple fallback capture methods if primary fails
- **Better SVG capture**: More robust SVG generation with error details
- **Payload validation**: Checks design JSON for pages and nodes before sending

### 2. Server-Side (TemplateController.php)
- **Enhanced request logging**: Logs incoming payload details
- **Design validation**: Warns if design has no pages or nodes
- **Preview processing logs**: Tracks success/failure of preview saves

## Diagnostic Steps

### Step 1: Open Browser DevTools
1. Open the template editor in your browser
2. Press F12 to open DevTools
3. Go to the Console tab
4. Keep it open while saving

### Step 2: Create/Edit a Template
1. Go to Staff → Templates → Create or Edit
2. Add some design elements (text, shapes, images)
3. Ensure elements are visible on the canvas

### Step 3: Save and Monitor Logs

Look for these key log messages:

#### ✅ GOOD - Normal Save Flow:
```
[InkWise Builder] === Capturing preview for page [id] ===
[InkWise Builder] Page details: { nodes: 5 } // Should have > 0 nodes
[InkWise Builder] Canvas has 5 child elements
[InkWise Builder] PNG data URL length: 234567 bytes
[InkWise Builder] Capture result: SUCCESS
[InkWise Builder] ✓ Using captured preview of 234567 bytes
[InkWise Builder] === Sending save request to server ===
```

#### ❌ BAD - Empty Canvas (No Preview):
```
[InkWise Builder] WARNING: Canvas stage has no child elements - preview will be blank!
[InkWise Builder] ❌ CRITICAL: No raster data URL returned
[InkWise Builder] Possible causes:
  1. Canvas has no rendered content
  2. Canvas elements are not properly mounted in DOM
  3. Image assets failed to load
  4. CORS issues with external assets
```

#### ❌ BAD - Capture Failure:
```
[InkWise Builder] html2canvas capture exception: [error details]
[InkWise Builder] Attempting final fallback capture
[InkWise Builder] Final fallback also failed
```

#### ❌ BAD - Empty Design:
```
[InkWise Builder] ❌ CRITICAL: First page has no nodes (design elements)!
[InkWise Builder] This will result in an empty template being saved.
```

### Step 4: Check Laravel Logs
```powershell
cd c:\xampp\htdocs\InkWise-Web\ink-wise
Get-Content storage\logs\laravel.log -Tail 100
```

Look for:
- `=== saveTemplate called ===`
- `preview_image_length: [number]` - Should be > 10000 bytes
- `design_first_page_nodes: [number]` - Should be > 0
- `✓ Preview image saved successfully`

### Step 5: Check Saved Files
```powershell
# Check preview files
Get-ChildItem storage\app\public\templates\preview -File | Sort-Object LastWriteTime -Descending | Select-Object -First 3 Name, Length, LastWriteTime

# Check JSON files  
Get-ChildItem storage\app\public\templates\assets -Filter *.json | Sort-Object LastWriteTime -Descending | Select-Object -First 1 | Get-Content | ConvertFrom-Json | Select-Object -ExpandProperty pages | Select-Object -First 1 nodes
```

Preview files should be:
- **Good**: > 10 KB (contains actual image data)
- **Bad**: < 1 KB (likely blank white PNG)

## Common Issues & Solutions

### Issue 1: Canvas Has No Child Elements
**Symptom**: Log shows "Canvas stage has no child elements"
**Cause**: Design elements are not being rendered to the DOM
**Solution**:
1. Check that pages have nodes in the state
2. Verify CanvasViewport.jsx is rendering nodes correctly
3. Ensure CSS doesn't hide canvas elements

### Issue 2: html2canvas Capture Fails
**Symptom**: "html2canvas capture exception" errors
**Cause**: CORS issues, external assets, or canvas rendering problems
**Solution**:
1. Check browser console for CORS errors
2. Ensure all images are uploaded to local storage
3. Verify images are accessible via HTTPS/HTTP
4. Check that fonts are loaded before capture

### Issue 3: Preview is White but Design Has Nodes
**Symptom**: Design JSON has nodes, but preview is white
**Cause**: Assets failed to load during capture
**Solution**:
1. Wait longer after page switch before capturing
2. Increase `waitForNextFrame()` calls
3. Check network tab for failed asset requests
4. Verify Storage::url() paths are correct

### Issue 4: Design JSON is Empty
**Symptom**: `design_first_page_nodes: 0`
**Cause**: Editor state is not populated
**Solution**:
1. Check BuilderStore.jsx state management
2. Verify serializeDesign() function
3. Ensure pages array is not empty
4. Check that nodes are being added to pages

## Testing Checklist

- [ ] Open template editor with existing template
- [ ] Add text element to canvas
- [ ] Add image to canvas
- [ ] Add shape to canvas
- [ ] Check DevTools console for warnings/errors
- [ ] Click Save Template
- [ ] Monitor console logs during save
- [ ] Check Laravel logs after save
- [ ] Verify preview file exists and is > 10 KB
- [ ] Verify JSON file has pages with nodes
- [ ] Check template preview in grid view
- [ ] Verify SVG was generated

## Quick Test Command
```powershell
# Check most recent template save
cd c:\xampp\htdocs\InkWise-Web\ink-wise
$latest = Get-ChildItem storage\app\public\templates\preview -File | Sort-Object LastWriteTime -Descending | Select-Object -First 1
Write-Host "Latest preview: $($latest.Name)"
Write-Host "File size: $($latest.Length) bytes"
Write-Host "Created: $($latest.LastWriteTime)"
if ($latest.Length -lt 5000) {
    Write-Host "⚠️  WARNING: File is too small - likely blank!" -ForegroundColor Red
} else {
    Write-Host "✓ File size looks good" -ForegroundColor Green
}
```

## Next Steps

If previews are still blank after these fixes:
1. Share the complete browser console output
2. Share the Laravel log entries for the save
3. Share the saved JSON file content
4. Describe what elements are on the canvas when saving

The enhanced logging will now clearly identify the exact point of failure in the save process.
