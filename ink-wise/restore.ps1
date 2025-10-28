# Database Restore Script for InkWise-Web
# This script restores a MySQL database backup

param(
    [Parameter(Mandatory=$true)]
    [string]$BackupFile,
    [switch]$Force
)

# Configuration - Update these if needed
$MySQLPath = "C:\xampp\mysql\bin\mysql.exe"
$DBHost = "127.0.0.1"
$Database = "laravels"
$Username = "root"
$Password = ""

# Check if backup file exists
if (!(Test-Path $BackupFile)) {
    Write-Host "Error: Backup file '$BackupFile' not found!" -ForegroundColor Red
    exit 1
}

# Safety check
if (!$Force) {
    Write-Host "WARNING: This will overwrite the current database '$Database'!" -ForegroundColor Red
    Write-Host "All current data will be lost." -ForegroundColor Red
    $confirmation = Read-Host "Are you sure you want to continue? (yes/no)"
    if ($confirmation -ne "yes") {
        Write-Host "Restore cancelled." -ForegroundColor Yellow
        exit 0
    }
}

Write-Host "Starting database restore..." -ForegroundColor Green
Write-Host "Database: $Database" -ForegroundColor Yellow
Write-Host "Backup file: $BackupFile" -ForegroundColor Yellow

# Create restore command
$Command = "& '$MySQLPath' --host=$DBHost --user=$Username --password='$Password' $Database < '$BackupFile'"

# Execute restore
try {
    Invoke-Expression $Command

    if ($LASTEXITCODE -eq 0) {
        Write-Host "Restore completed successfully!" -ForegroundColor Green
        Write-Host "Database '$Database' has been restored from '$BackupFile'" -ForegroundColor Cyan
    } else {
        Write-Host "Restore failed with exit code: $LASTEXITCODE" -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "Error during restore: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}