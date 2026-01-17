@echo off
echo ==========================================
echo    FIXING EXCEL CLASS NOT FOUND
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_cal_export_fallback.tar app/Http/Controllers/Web/Admin/AdminController.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_cal_export_fallback.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Clearing Cache...
# Also dumping autoload just in case
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_cal_export_fallback.tar && rm update_cal_export_fallback.tar && php artisan config:clear && composer dump-autoload"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Export fixed. If Excel fails, it will auto-fallback to CSV.
pause
