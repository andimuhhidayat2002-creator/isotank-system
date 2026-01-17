@echo off
echo ==========================================
echo    DEPLOYING DB HEALTH CHECK
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_cal_db_check.tar app/Http/Controllers/Web/Admin/AdminController.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_cal_db_check.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Clearing Cache...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_cal_db_check.tar && rm update_cal_db_check.tar && php artisan route:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo DB Check deployed. Read output on screen.
pause
