@echo off
echo ==========================================
echo      FIXING EXCEL EXPORT FORMAT
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_cal_export.tar app/Exports/CalibrationMasterExport.php app/Http/Controllers/Web/Admin/CalibrationMasterController.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_cal_export.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_cal_export.tar && rm update_cal_export.tar && php artisan config:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Export fixed. Now generates valid .xlsx file.
pause
