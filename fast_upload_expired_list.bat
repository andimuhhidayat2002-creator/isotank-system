@echo off
echo ==========================================
echo    UPDATING ALERT LIST (INCLUDE EXPIRED)
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_expired_list.tar app/Http/Controllers/Web/Admin/AdminController.php resources/views/admin/dashboard/calibration_monitoring.blade.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_expired_list.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Clearing Cache...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_expired_list.tar && rm update_expired_list.tar && php artisan view:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Expired items added to dashboard list.
pause
