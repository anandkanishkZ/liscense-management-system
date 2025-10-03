# PHP MySQL Extensions Enabler Script
# Run this as Administrator in PowerShell

Write-Host "PHP MySQL Extensions Enabler" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Green

$phpIniPath = "C:\Program Files\php-8.4.7\php.ini"

if (-not (Test-Path $phpIniPath)) {
    Write-Host "ERROR: php.ini not found at $phpIniPath" -ForegroundColor Red
    Write-Host "Please check your PHP installation path" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit
}

Write-Host "Found php.ini at: $phpIniPath" -ForegroundColor Yellow

try {
    # Read the php.ini file
    $content = Get-Content $phpIniPath
    
    # Enable mysqli extension
    $content = $content -replace ';extension=mysqli', 'extension=mysqli'
    
    # Enable pdo_mysql extension
    $content = $content -replace ';extension=pdo_mysql', 'extension=pdo_mysql'
    
    # Write back to file
    Set-Content -Path $phpIniPath -Value $content
    
    Write-Host "SUCCESS: PHP MySQL extensions enabled!" -ForegroundColor Green
    Write-Host "Enabled extensions:" -ForegroundColor Yellow
    Write-Host "  - mysqli" -ForegroundColor White
    Write-Host "  - pdo_mysql" -ForegroundColor White
    Write-Host ""
    Write-Host "You can now run your PHP project with MySQL!" -ForegroundColor Green
    
} catch {
    Write-Host "ERROR: Could not modify php.ini file" -ForegroundColor Red
    Write-Host "Please run this script as Administrator" -ForegroundColor Yellow
    Write-Host "Error details: $($_.Exception.Message)" -ForegroundColor Red
}

Read-Host "Press Enter to continue"