# Database Backup Script for InkWise-Web
# This script backs up the MySQL database used by the Laravel application

param(
    [string]$BackupName = "",
    [switch]$IncludeDate = $true
)

# Configuration - Update these if needed
$MySQLPath = "C:\xampp\mysql\bin\mysqldump.exe"
$DBHost = "127.0.0.1"
$Database = "laravels"
$Username = "root"
$Password = ""

# Generate backup filename
if ($BackupName -eq "") {
    $BackupName = "inkwise_backup"
}

if ($IncludeDate) {
    $DateStamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
    $BackupName = "$BackupName_$DateStamp"
}

$BackupFile = "backups\$BackupName.sql"

Write-Host "Starting database backup..." -ForegroundColor Green
Write-Host "Database: $Database" -ForegroundColor Yellow
Write-Host "Backup file: $BackupFile" -ForegroundColor Yellow

# Create backup command
$Command = "& '$MySQLPath' --host=$DBHost --user=$Username --password='$Password' $Database > '$BackupFile'"

# Execute backup
try {
    Invoke-Expression $Command

    if ($LASTEXITCODE -eq 0) {
        $FileSize = (Get-Item $BackupFile).Length / 1MB
        Write-Host "Backup completed successfully!" -ForegroundColor Green
        Write-Host "File: $BackupFile" -ForegroundColor Cyan
        Write-Host ("Size: {0:N2} MB" -f $FileSize) -ForegroundColor Cyan
    } else {
        Write-Host "Backup failed with exit code: $LASTEXITCODE" -ForegroundColor Red
        exit 1
    }
} catch {
    Write-Host "Error during backup: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}
