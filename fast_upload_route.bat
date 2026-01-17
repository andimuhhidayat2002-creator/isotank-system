@echo off
echo ==========================================
echo      FIXING TEMPLATE DOWNLOAD ROUTE
echo ==========================================

echo 1. Packing files...
cd api
tar -cf ../update_route_fix.tar routes/web.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_route_fix.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_route_fix.tar && rm update_route_fix.tar"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Route untuk download template telah diperbaiki.
echo Silakan coba download template lagi.
pause
