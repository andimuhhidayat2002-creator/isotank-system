@echo off
echo ==========================================
echo      DEPLOYING CALIBRATION MASTER UPLOAD
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_cal_master.tar app/Imports/CalibrationMasterImport.php app/Http/Controllers/Web/Admin/CalibrationMasterController.php routes/web.php resources/views/admin/calibration-master/index.blade.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_cal_master.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_cal_master.tar && rm update_cal_master.tar && php artisan route:clear && php artisan view:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Calibration Master Import feature deployed.
pause
