# Template Save - Interactive Test Script
# This script will guide you through testing the template save functionality

Write-Host ""
Write-Host "=== Template Save Interactive Test ===" -ForegroundColor Cyan
Write-Host ""

$inkWisePath = "c:\xampp\htdocs\InkWise-Web\ink-wise"
Set-Location $inkWisePath

# Run the test command
Write-Host "Step 1: Running template test..." -ForegroundColor Yellow
php artisan template:test

Write-Host ""
Write-Host "Step 2: Preparing to open browser..." -ForegroundColor Yellow
Write-Host "The editor will open in your default browser." -ForegroundColor Gray
Write-Host "Keep this window visible to see the test instructions." -ForegroundColor Gray
Write-Host ""

# Get template ID from the test output
$testOutput = php artisan template:test 2>&1 | Out-String
if ($testOutput -match "ID:\s*(\d+)") {
    $templateId = $matches[1]
    $editorUrl = "http://localhost/staff/templates/$templateId/editor"
    
    Write-Host "Opening template editor..." -ForegroundColor Green
    Start-Process $editorUrl
    
    Start-Sleep -Seconds 2
    
    Write-Host ""
    Write-Host "=== TESTING INSTRUCTIONS ===" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "In the browser window that just opened:" -ForegroundColor White
    Write-Host ""
    Write-Host "1. Press F12 to open DevTools" -ForegroundColor Yellow
    Write-Host "   - Click on the 'Console' tab" -ForegroundColor Gray
    Write-Host ""
    Write-Host "2. Look at the canvas - you should see elements" -ForegroundColor Yellow
    Write-Host "   - If empty, add a text box or shape" -ForegroundColor Gray
    Write-Host ""
    Write-Host "3. Click the 'Save Template' button (top right)" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "4. Watch the Console tab for these messages:" -ForegroundColor Yellow
    Write-Host "   [InkWise Builder] === Capturing preview for page ===" -ForegroundColor Gray
    Write-Host "   [InkWise Builder] Canvas has X child elements" -ForegroundColor Gray
    Write-Host "   [InkWise Builder] Capture result: SUCCESS" -ForegroundColor Green
    Write-Host "   OR" -ForegroundColor Gray
    Write-Host "   [InkWise Builder] CRITICAL: No raster data URL returned" -ForegroundColor Red
    Write-Host ""
    Write-Host "5. After save completes, press any key here..." -ForegroundColor Yellow
    
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    
    Write-Host ""
    Write-Host "Step 3: Checking save results..." -ForegroundColor Yellow
    Write-Host ""
    
    php artisan template:test $templateId
    
    Write-Host ""
    Write-Host "=== Test Analysis ===" -ForegroundColor Cyan
    Write-Host ""
    
    # Check the results
    $checkOutput = php artisan template:test $templateId 2>&1 | Out-String
    
    if ($checkOutput -match "0\.1 KB \(too small") {
        Write-Host "ISSUE DETECTED:" -ForegroundColor Red
        Write-Host "Preview is still 0.1 KB (blank)" -ForegroundColor Red
        Write-Host ""
        Write-Host "This means the canvas capture failed." -ForegroundColor Yellow
        Write-Host ""
        Write-Host "Check the browser console for:" -ForegroundColor Yellow
        Write-Host "  - 'Canvas stage has no child elements'" -ForegroundColor Gray
        Write-Host "  - 'html2canvas capture exception'" -ForegroundColor Gray
        Write-Host "  - 'No raster data URL returned'" -ForegroundColor Gray
        Write-Host ""
        Write-Host "Common causes:" -ForegroundColor Yellow
        Write-Host "  1. Canvas was empty (no design elements)" -ForegroundColor Gray
        Write-Host "  2. Elements failed to render before capture" -ForegroundColor Gray
        Write-Host "  3. Image assets failed to load (CORS issues)" -ForegroundColor Gray
        Write-Host ""
        Write-Host "See TEMPLATE_SAVE_DIAGNOSTIC_GUIDE.md for solutions" -ForegroundColor Cyan
    } elseif ($checkOutput -match "(\d+\.\d+) KB" -and [double]($matches[1]) -gt 5) {
        Write-Host "SUCCESS!" -ForegroundColor Green
        Write-Host "Preview was saved correctly ($(matches[1]) KB)" -ForegroundColor Green
        Write-Host ""
        Write-Host "The template save system is working properly." -ForegroundColor Green
        Write-Host "The enhanced logging helped capture the preview successfully." -ForegroundColor Green
    } else {
        Write-Host "PARTIAL SUCCESS:" -ForegroundColor Yellow
        Write-Host "Preview was saved but may be small." -ForegroundColor Yellow
        Write-Host "Check browser console logs for details." -ForegroundColor Yellow
    }
    
} else {
    Write-Host "Could not determine template ID from test output" -ForegroundColor Red
    Write-Host "Run manually: php artisan template:test" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=== Test Complete ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "For more information:" -ForegroundColor Gray
Write-Host "  - See TEMPLATE_SAVE_DIAGNOSTIC_GUIDE.md" -ForegroundColor Gray
Write-Host "  - Check ink-wise/storage/logs/laravel.log" -ForegroundColor Gray
Write-Host "  - Run: php artisan template:test [template-id]" -ForegroundColor Gray
Write-Host ""
