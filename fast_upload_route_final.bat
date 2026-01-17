@echo off
echo ==========================================
echo    FIXING STUBBORN ROUTE ERROR
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_cal_route_final.tar routes/web.php resources/views/admin/dashboard/calibration_monitoring.blade.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_cal_route_final.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Clearing ALL Caches...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_cal_route_final.tar && rm update_cal_route_final.tar && php artisan route:clear && php artisan config:clear && php artisan view:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Route renamed and caches nuked. Try downloading now.
pause
