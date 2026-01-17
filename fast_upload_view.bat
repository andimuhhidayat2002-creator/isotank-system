@echo off
echo ==========================================
echo      FAST VIEW UPLOAD SYSTEM
echo ==========================================

echo 1. Packing files into archive...
cd api
tar -cf ../update_view_pkg.tar resources/views/admin/isotanks.blade.php
cd ..

echo.
echo 2. Uploading Package (Enter Password for upload)...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_view_pkg.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Extracting and Installing on Server (Enter Password for execution)...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_view_pkg.tar && rm update_view_pkg.tar"

echo.
echo ==========================================
echo        SUCCESSFULLY DEPLOYED!
echo ==========================================
echo Silakan refresh halaman 'Master Isotanks'.
pause
