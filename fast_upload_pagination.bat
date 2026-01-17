@echo off
echo ==========================================
echo      FIXING PAGINATION STYLE
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_pagination.tar app/Providers/AppServiceProvider.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_pagination.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_pagination.tar && rm update_pagination.tar && php artisan view:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Pagination fixed using Bootstrap style.
pause
