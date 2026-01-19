# Template Save System - Quick Verification Script
# Run this to check the current state of template saves

Write-Host ""
Write-Host "=== Template Save System Verification ===" -ForegroundColor Cyan
Write-Host ""

$inkWisePath = "c:\xampp\htdocs\InkWise-Web\ink-wise"
Set-Location $inkWisePath

# 1. Check build assets
Write-Host "1. Checking build assets..." -ForegroundColor Yellow
$buildFile = Get-Item "public\build\assets\main-*.js" -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending | Select-Object -First 1
if ($buildFile) {
    Write-Host "   Build updated: $($buildFile.LastWriteTime)" -ForegroundColor Green
} else {
    Write-Host "   Build assets not found" -ForegroundColor Red
}

# 2. Check recent preview files
Write-Host ""
Write-Host "2. Checking recent preview files..." -ForegroundColor Yellow
$previews = Get-ChildItem "storage\app\public\templates\preview" -File -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending | Select-Object -First 3
if ($previews) {
    foreach ($p in $previews) {
        $sizeKB = [math]::Round($p.Length / 1024, 1)
        $color = if ($p.Length -lt 5000) { "Red" } elseif ($p.Length -gt 10000) { "Green" } else { "Yellow" }
        Write-Host "   $($p.Name): $sizeKB KB" -ForegroundColor $color
    }
} else {
    Write-Host "   No preview files found" -ForegroundColor Gray
}

# 3. Check recent JSON files
Write-Host ""
Write-Host "3. Checking design JSON files..." -ForegroundColor Yellow
$jsons = Get-ChildItem "storage\app\public\templates\assets" -Filter "*.json" -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending | Select-Object -First 1
if ($jsons) {
    $json = Get-Content $jsons[0].FullName | ConvertFrom-Json
    $pages = if ($json.pages) { $json.pages.Count } else { 0 }
    $nodes = if ($json.pages -and $json.pages[0].nodes) { $json.pages[0].nodes.Count } else { 0 }
    Write-Host "   Latest: $($jsons[0].Name)" -ForegroundColor Gray
    Write-Host "   Pages: $pages" -ForegroundColor $(if ($pages -gt 0) { "Green" } else { "Red" })
    Write-Host "   Nodes: $nodes" -ForegroundColor $(if ($nodes -gt 0) { "Green" } else { "Red" })
} else {
    Write-Host "   No JSON files found" -ForegroundColor Gray
}

Write-Host ""
Write-Host "=== Next Steps ===" -ForegroundColor Cyan
Write-Host "1. Open template editor in browser" -ForegroundColor Gray
Write-Host "2. Press F12 to open DevTools Console" -ForegroundColor Gray
Write-Host "3. Add elements to canvas and click Save" -ForegroundColor Gray
Write-Host "4. Monitor console for detailed save logs" -ForegroundColor Gray
Write-Host ""
Write-Host "See TEMPLATE_SAVE_DIAGNOSTIC_GUIDE.md for details" -ForegroundColor Cyan
Write-Host ""
