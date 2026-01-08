# Deploy AuthController Fix to Production
# This script copies the updated AuthController.php to production server

$ProductionServer = "72.61.115.6"
$ProductionPath = "/home/api.interntrack.online/public_html/app/Http/Controllers/Api/"
$LocalFile = "app\Http\Controllers\Api\AuthController.php"

Write-Host "================================" -ForegroundColor Cyan
Write-Host "  Deploy AuthController Fix" -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

# Check if file exists
if (-not (Test-Path $LocalFile)) {
    Write-Host "Error: $LocalFile not found!" -ForegroundColor Red
    exit 1
}

Write-Host "File to deploy: $LocalFile" -ForegroundColor Green
Write-Host "Target server: $ProductionServer" -ForegroundColor Green
Write-Host "Target path: $ProductionPath" -ForegroundColor Green
Write-Host ""

# Ask for confirmation
$confirm = Read-Host "Deploy to production? (yes/no)"
if ($confirm -ne "yes") {
    Write-Host "Deployment cancelled." -ForegroundColor Yellow
    exit 0
}

Write-Host ""
Write-Host "Deployment Methods:" -ForegroundColor Cyan
Write-Host "1. Using SCP (requires SSH access)" -ForegroundColor White
Write-Host "2. Using FTP (FileZilla or WinSCP)" -ForegroundColor White
Write-Host "3. Using CyberPanel File Manager" -ForegroundColor White
Write-Host ""

$method = Read-Host "Choose deployment method (1/2/3)"

switch ($method) {
    "1" {
        Write-Host ""
        Write-Host "Using SCP to deploy..." -ForegroundColor Cyan
        Write-Host "You'll need SSH credentials for $ProductionServer" -ForegroundColor Yellow
        Write-Host ""
        
        $username = Read-Host "SSH Username"
        
        Write-Host ""
        Write-Host "Executing SCP command..." -ForegroundColor Cyan
        scp $LocalFile "${username}@${ProductionServer}:${ProductionPath}AuthController.php"
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host ""
            Write-Host "✓ File uploaded successfully!" -ForegroundColor Green
            Write-Host ""
            Write-Host "Next steps:" -ForegroundColor Cyan
            Write-Host "1. Clear Laravel cache on production:" -ForegroundColor White
            Write-Host "   ssh ${username}@${ProductionServer}" -ForegroundColor Gray
            Write-Host "   cd /home/api.interntrack.online/public_html" -ForegroundColor Gray
            Write-Host "   php artisan config:clear" -ForegroundColor Gray
            Write-Host "   php artisan cache:clear" -ForegroundColor Gray
        } else {
            Write-Host ""
            Write-Host "✗ Upload failed!" -ForegroundColor Red
        }
    }
    "2" {
        Write-Host ""
        Write-Host "Manual FTP Upload Instructions:" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "1. Open FileZilla or WinSCP" -ForegroundColor White
        Write-Host "2. Connect to:" -ForegroundColor White
        Write-Host "   Host: $ProductionServer" -ForegroundColor Gray
        Write-Host "   Protocol: SFTP (SSH)" -ForegroundColor Gray
        Write-Host "   Port: 22" -ForegroundColor Gray
        Write-Host "3. Navigate to: $ProductionPath" -ForegroundColor White
        Write-Host "4. Upload file: $LocalFile" -ForegroundColor White
        Write-Host "5. Backup old file first (rename to AuthController.php.backup)" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "After upload, SSH to server and run:" -ForegroundColor Cyan
        Write-Host "   cd /home/api.interntrack.online/public_html" -ForegroundColor Gray
        Write-Host "   php artisan config:clear" -ForegroundColor Gray
        Write-Host "   php artisan cache:clear" -ForegroundColor Gray
        Write-Host ""
        
        # Open file location
        $confirm = Read-Host "Open file location in Explorer? (y/n)"
        if ($confirm -eq "y") {
            explorer.exe /select,"$(Get-Location)\$LocalFile"
        }
    }
    "3" {
        Write-Host ""
        Write-Host "CyberPanel File Manager Instructions:" -ForegroundColor Cyan
        Write-Host ""
        Write-Host "1. Login to CyberPanel: https://${ProductionServer}:8090" -ForegroundColor White
        Write-Host "2. Go to: File Manager → Select domain: api.interntrack.online" -ForegroundColor White
        Write-Host "3. Navigate to: public_html/app/Http/Controllers/Api/" -ForegroundColor White
        Write-Host "4. Backup AuthController.php (download or rename)" -ForegroundColor Yellow
        Write-Host "5. Click Upload and select:" -ForegroundColor White
        Write-Host "   $(Get-Location)\$LocalFile" -ForegroundColor Gray
        Write-Host ""
        Write-Host "After upload, go to Terminal and run:" -ForegroundColor Cyan
        Write-Host "   cd /home/api.interntrack.online/public_html" -ForegroundColor Gray
        Write-Host "   php artisan config:clear" -ForegroundColor Gray
        Write-Host "   php artisan cache:clear" -ForegroundColor Gray
        Write-Host ""
        
        # Open file location
        $confirm = Read-Host "Open file location in Explorer? (y/n)"
        if ($confirm -eq "y") {
            explorer.exe /select,"$(Get-Location)\$LocalFile"
        }
    }
    default {
        Write-Host "Invalid choice." -ForegroundColor Red
        exit 1
    }
}

Write-Host ""
Write-Host "================================" -ForegroundColor Cyan
Write-Host "Deployment script completed!" -ForegroundColor Green
Write-Host "================================" -ForegroundColor Cyan
