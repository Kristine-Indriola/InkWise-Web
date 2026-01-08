# Template Images Not Showing - Diagnosis

## Issue
Template preview images are not appearing on the templates list page at `http://127.0.0.1:8000/staff/templates`.

## Investigation Results

### Database Check ✅
The database **HAS** the preview paths correctly stored:
```
Template ID: 112
Front Image: templates/preview/template_37017742-64aa-4efe-b536-d0693c0d856b.png
Preview: templates/preview/template_11dc9caa-c23a-49c8-bd3c-4b28519e034d.png
```

### File System Check ✅
The files **EXIST** in the storage directory:
- `storage/app/public/templates/preview/template_*.png` - Files are present
- `public/storage` symlink **EXISTS** and points to `storage/app/public`
- Files are accessible via symlink

### What Should Be Working
The view at [index.blade.php](ink-wise/resources/views/staff/templates/index.blade.php) line 1076 should display images using:
```php
<img src="{{ \App\Support\ImageResolver::url($front) }}" alt="Preview">
```

Where `$front` comes from (line 995):
```php
$front = $template->front_image ?? $template->preview ?? $template->preview_front ?? ...
```

ImageResolver should convert `templates/preview/template_xxx.png` to `/storage/templates/preview/template_xxx.png`

## Possible Causes

### 1. Port Mismatch
You're accessing `http://127.0.0.1:8000` but Apache runs on port 80/443. If you're using `php artisan serve`, the server might not be handling static files correctly.

**Solution:** Check if using:
- Laravel dev server: `php artisan serve` (port 8000)
- Apache: Use `http://localhost/staff/templates` (port 80)

### 2. Asset URL Configuration
Check `.env` file for `APP_URL` and `ASSET_URL`:
```env
APP_URL=http://127.0.0.1:8000
# Or
APP_URL=http://localhost
```

### 3. Browser DevTools Console Errors
Open the page and press F12 → Console to see:
- 404 errors for images
- CORS errors  
- Path mismatches

### 4. Missing Blade Compilation
The Blade template might need to be recompiled:
```bash
php artisan view:clear
php artisan cache:clear
```

## Testing

### Test 1: Check Image URL Resolution
Open: `http://127.0.0.1:8000/test-image-urls.php`

This will show:
- Database paths
- Resolved URLs
- Whether images actually load

### Test 2: Direct Image Access
Try accessing an image directly:
```
http://127.0.0.1:8000/storage/templates/preview/template_11dc9caa-c23a-49c8-bd3c-4b28519e034d.png
```

If this returns 404, the issue is with routing or symlinks.

### Test 3: Check Symlink
```powershell
Get-Item c:\xampp\htdocs\InkWise-Web\ink-wise\public\storage | Select-Object LinkType, Target
```

Should show: `Junction {C:\xampp\htdocs\InkWise-Web\ink-wise\storage\app\public}`

## Quick Fixes to Try

### Fix 1: Recreate Symlink
```bash
cd c:\xampp\htdocs\InkWise-Web\ink-wise
php artisan storage:link --force
```

### Fix 2: Clear All Caches
```bash
php artisan view:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Fix 3: Check .env
Make sure `.env` has:
```env
APP_URL=http://127.0.0.1:8000
```

Or if using Apache:
```env
APP_URL=http://localhost
```

### Fix 4: Verify Server
If using Laravel dev server:
```bash
php artisan serve --host=127.0.0.1 --port=8000
```

If using Apache, access via:
```
http://localhost/staff/templates
```

## Next Steps

1. Open `http://127.0.0.1:8000/test-image-urls.php` - see if images load
2. Open `http://127.0.0.1:8000/staff/templates` and press F12 - check console for errors
3. Try accessing image directly: `http://127.0.0.1:8000/storage/templates/preview/template_11dc9caa-c23a-49c8-bd3c-4b28519e034d.png`
4. Report back what you see in the console and whether test page works
