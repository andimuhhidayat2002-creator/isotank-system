# PowerShell Deployment Script for VPS
# This script will SSH to VPS and run deployment commands

$VPS_USER = "root"
$VPS_HOST = "202.10.44.146"
$PROJECT_PATH = "/var/www/isotank-system/api"

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Deploying to VPS: $VPS_HOST" -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host ""

# Test connection
Write-Host "Testing connection to VPS..." -ForegroundColor Yellow
$pingResult = Test-Connection -ComputerName $VPS_HOST -Count 2 -Quiet

if (-not $pingResult) {
    Write-Host "ERROR: Cannot reach VPS at $VPS_HOST" -ForegroundColor Red
    exit 1
}

Write-Host "OK: VPS is reachable" -ForegroundColor Green
Write-Host ""

# Upload deployment script
Write-Host "Uploading deployment script..." -ForegroundColor Yellow
scp deploy_dynamic_items.sh ${VPS_USER}@${VPS_HOST}:${PROJECT_PATH}/

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to upload script" -ForegroundColor Red
    Write-Host "Make sure SSH is configured properly" -ForegroundColor Yellow
    exit 1
}

Write-Host "OK: Script uploaded" -ForegroundColor Green
Write-Host ""

# Execute deployment
Write-Host "Executing deployment on VPS..." -ForegroundColor Yellow
Write-Host ""

ssh ${VPS_USER}@${VPS_HOST} "cd $PROJECT_PATH && chmod +x deploy_dynamic_items.sh && ./deploy_dynamic_items.sh"

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "==========================================" -ForegroundColor Green
    Write-Host "Deployment Successful!" -ForegroundColor Green
    Write-Host "==========================================" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "==========================================" -ForegroundColor Red
    Write-Host "Deployment Failed" -ForegroundColor Red
    Write-Host "==========================================" -ForegroundColor Red
}
