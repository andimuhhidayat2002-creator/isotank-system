@echo off
echo ==========================================
echo    FIXING DOWNLOAD BUTTON CRASH
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_cal_csv_fix.tar resources/views/admin/dashboard/calibration_monitoring.blade.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_cal_csv_fix.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Clearing Cache...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_cal_csv_fix.tar && rm update_cal_csv_fix.tar && php artisan view:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Download button disabled temporarily. Dashboard should load.
pause
