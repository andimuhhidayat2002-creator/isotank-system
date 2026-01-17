@echo off
echo ==========================================
echo      FIXING CALIBRATION IMPORT 500 ERROR
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_cal_import_fix.tar app/Http/Controllers/Web/Admin/CalibrationMasterController.php app/Imports/CalibrationMasterImport.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_cal_import_fix.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_cal_import_fix.tar && rm update_cal_import_fix.tar"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Import mechanism replaced with direct I/O. 500 Error should be gone.
pause
