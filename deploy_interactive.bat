@echo off
echo ==========================================
echo   STEP-BY-STEP VPS DEPLOYMENT
echo ==========================================
echo.

set VPS_IP=202.10.44.146
set VPS_USER=root

echo This script will guide you through the deployment process.
echo.
echo Prerequisites:
echo - SSH access to VPS (password ready)
echo - WinSCP or FileZilla installed (for file upload)
echo.
echo ==========================================
echo   STEP 1: UPLOAD FILES
echo ==========================================
echo.
echo A ZIP file has been created: calibration_fix_deploy.zip
echo.
echo Please upload this file to VPS using one of these methods:
echo.
echo METHOD A - Using WinSCP:
echo   1. Open WinSCP
echo   2. Connect to: %VPS_IP% (user: %VPS_USER%)
echo   3. Upload 'calibration_fix_deploy.zip' to /tmp/
echo.
echo METHOD B - Using FileZilla:
echo   1. Open FileZilla
echo   2. Host: sftp://%VPS_IP%, User: %VPS_USER%, Port: 22
echo   3. Upload 'calibration_fix_deploy.zip' to /tmp/
echo.
echo METHOD C - Using SCP command (if available):
echo   scp calibration_fix_deploy.zip %VPS_USER%@%VPS_IP%:/tmp/
echo.
pause
echo.

echo ==========================================
echo   STEP 2: SSH TO VPS
echo ==========================================
echo.
echo Opening SSH connection to VPS...
echo You will need to enter the root password.
echo.
echo After connecting, run these commands:
echo.
echo # Navigate to tmp
echo cd /tmp
echo.
echo # Extract files
echo unzip -o calibration_fix_deploy.zip
echo.
echo # Copy to correct locations
echo cp -f api/app/Http/Controllers/Api/Inspector/InspectionSubmitController.php /var/www/isotank-system/api/app/Http/Controllers/Api/Inspector/
echo cp -f api/docs/CALIBRATION_MASTER_UPDATE_TROUBLESHOOTING.md /var/www/isotank-system/api/docs/
echo cp -f api/deploy_vps.sh /var/www/isotank-system/api/
echo.
echo # Make deploy script executable
echo chmod +x /var/www/isotank-system/api/deploy_vps.sh
echo.
echo # Run deployment script
echo /var/www/isotank-system/api/deploy_vps.sh
echo.
echo # Clean up
echo rm -f /tmp/calibration_fix_deploy.zip
echo rm -rf /tmp/api
echo.
echo # Check logs
echo tail -f /var/www/isotank-system/api/storage/logs/laravel.log
echo.
echo Press ENTER to open SSH connection...
pause > nul

ssh %VPS_USER%@%VPS_IP%

echo.
echo ==========================================
echo   DEPLOYMENT COMPLETED
echo ==========================================
echo.
echo Next steps:
echo 1. Test inspection submission from Flutter app
echo 2. Check Laravel logs for calibration update messages
echo 3. Verify master_isotank_calibration_status table
echo.
pause
