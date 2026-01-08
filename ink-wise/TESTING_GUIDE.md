# Template Save Testing Guide

## ✅ Working Test Commands

### Method 1: Artisan Command (Recommended)
```bash
# Test any draft template
php artisan template:test

# Test a specific template by ID
php artisan template:test 112
```

### Method 2: Interactive PowerShell Test (Best for Debugging)
```powershell
.\test-template-interactive.ps1
```
This will:
1. Run the test
2. Open the template editor in your browser
3. Guide you through the save process
4. Verify the results automatically
5. Diagnose issues and show causes

### Method 3: Quick Status Check
```powershell
.\verify-template-save.ps1
```
Fast check of build status and recent saves.

### Method 4: Manual Test
1. Run: `php artisan template:test`
2. Note the template ID
3. Open: `http://localhost/staff/templates/{id}/editor`
4. Open DevTools (F12) → Console
5. Click "Save Template"
6. Run: `php artisan template:test {id}` again

## Understanding Test Results

### ✓ GOOD Results
```
✓ Design has content (2 nodes)
✓ Preview: 45.3 KB
✓ JSON: 555.9 KB (2 nodes)
```

### ⚠ ISSUES
```
⚠ Preview: 0.1 KB (too small - likely blank)
```
**Cause:** Canvas capture failed. Check browser console for:
- `❌ CRITICAL: No raster data URL returned`
- `Canvas stage has no child elements`
- `html2canvas capture exception`

### ❌ CRITICAL ISSUES
```
❌ No pages in design!
❌ Preview file not found
```
**Cause:** Template was not saved correctly or files were deleted.

## What Each Test Checks

1. **Design Check**
   - Pages exist in design JSON
   - First page has nodes (actual content)
   - Design structure is valid

2. **Storage Check**
   - Preview PNG file exists and is > 5 KB
   - SVG file exists
   - JSON file exists with correct node count

## Troubleshooting

### Preview is 0.1 KB (Blank)
**Problem:** Canvas capture is failing

**Solutions:**
1. Check browser console for detailed error messages
2. Ensure canvas has visible elements before saving
3. Wait a few seconds after adding elements before saving
4. Check that images have loaded (no broken image icons)
5. See [TEMPLATE_SAVE_DIAGNOSTIC_GUIDE.md](TEMPLATE_SAVE_DIAGNOSTIC_GUIDE.md)

### JSON has 0 nodes
**Problem:** Design is not being serialized correctly

**Solutions:**
1. Check that elements are actually added to the canvas
2. Verify BuilderStore state has pages with nodes
3. Check console for serialization errors

### Files not found
**Problem:** Storage paths are incorrect or symlink is broken

**Solutions:**
```bash
# Recreate storage symlink
php artisan storage:link

# Check permissions
# Windows: Ensure storage folder is not read-only
# Linux: chmod -R 775 storage
```

## Test Files

- **TestTemplateSave.php** - Artisan command for testing
- **test-template-interactive.ps1** - Interactive PowerShell test
- **test-template-save.php** - Standalone PHP test script
- **verify-template-save.ps1** - Quick verification script

## Next Steps After Testing

If tests show issues:
1. Review browser console logs (the enhanced logging will show exactly where it fails)
2. Check Laravel logs: `storage/logs/laravel.log`
3. See diagnostic guide: `TEMPLATE_SAVE_DIAGNOSTIC_GUIDE.md`
4. If capture fails consistently, see [STYLESHEET_FIX_NOTES.md](STYLESHEET_FIX_NOTES.md) for CDN issues

If tests pass:
1. Create templates normally
2. Previews should save correctly
3. Template grid should show actual preview images (not blank)
