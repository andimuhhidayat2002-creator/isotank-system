@echo off
echo ==========================================
echo      FAST CALIBRATION EXPORT UPLOAD
echo ==========================================

echo 1. Packing files into archive...
cd api
tar -cf ../update_calib_export.tar app/Http/Controllers/Web/Admin/CalibrationMasterController.php routes/web.php resources/views/admin/calibration-master/index.blade.php
cd ..

echo.
echo 2. Uploading Package (Enter Password for upload)...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_calib_export.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Extracting and Installing on Server (Enter Password for execution)...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_calib_export.tar && rm update_calib_export.tar"

echo.
echo ==========================================
echo        SUCCESSFULLY DEPLOYED!
echo ==========================================
echo Silakan refresh halaman 'Calibration Master' dan coba tombol Export.
pause
