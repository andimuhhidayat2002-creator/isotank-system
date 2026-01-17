@echo off
echo ==========================================
echo    FIXING FATAL ERROR (DUPLICATE METHOD)
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_cal_fatal_fix.tar app/Http/Controllers/Web/Admin/AdminController.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_cal_fatal_fix.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Clearing Cache...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_cal_fatal_fix.tar && rm update_cal_fatal_fix.tar && php artisan route:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Fatal error fix deployed.
pause
