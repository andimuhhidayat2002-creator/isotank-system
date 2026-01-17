@echo off
echo ==========================================
echo    FIXING FLUTTER API DATA LOADER
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_api_cal_fix.tar app/Http/Controllers/Api/Inspector/InspectionJobController.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_api_cal_fix.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Clearing Cache...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_api_cal_fix.tar && rm update_api_cal_fix.tar && php artisan config:clear && php artisan route:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo API now serves Master Component data and Latest Vacuum!
pause
