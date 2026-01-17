@echo off
echo ==========================================
echo    DEPLOYING FINAL RESTORE
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_cal_final_restore.tar app/Http/Controllers/Web/Admin/AdminController.php resources/views/admin/dashboard/calibration_monitoring.blade.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_cal_final_restore.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Clearing Cache...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_cal_final_restore.tar && rm update_cal_final_restore.tar && php artisan view:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Full system restored with safe view logic.
pause
