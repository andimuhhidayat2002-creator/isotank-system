@echo off
echo ==========================================
echo    UPGRADING EXPORT TO EXCEL
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_cal_xlsx.tar app/Exports/CalibrationAlertsExport.php app/Http/Controllers/Web/Admin/AdminController.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_cal_xlsx.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Clearing Cache...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_cal_xlsx.tar && rm update_cal_xlsx.tar && php artisan config:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Export function upgraded to .xlsx!
pause
