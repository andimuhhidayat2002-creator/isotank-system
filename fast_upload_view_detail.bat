@echo off
echo ==========================================
echo    FIXING VACUUM LOG DETAILS
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_vac_detail.tar resources/views/admin/isotanks/show.blade.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_vac_detail.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Clearing Cache...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_vac_detail.tar && rm update_vac_detail.tar && php artisan view:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Vacuum logs should now show values.
pause
