@echo off
echo ==========================================
echo      DEPLOYING DEBUG MODE
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_debug.tar app/Http/Controllers/Web/Admin/AdminController.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_debug.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_debug.tar && rm update_debug.tar && php artisan view:clear && php artisan cache:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Debug mode active. Please refresh the web page to see the specific error.
pause
