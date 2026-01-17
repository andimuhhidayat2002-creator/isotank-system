@echo off
echo ==========================================
echo    DEPLOYING HELLO WORLD TEST
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_cal_hello.tar app/Http/Controllers/Web/Admin/AdminController.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_cal_hello.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Clearing Cache...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_cal_hello.tar && rm update_cal_hello.tar && php artisan route:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Controller replaced with Hello World.
pause
