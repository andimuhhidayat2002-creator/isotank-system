@echo off
echo ==========================================
echo      FAST CALIBRATION UPLOAD SYSTEM
echo ==========================================

echo 1. Packing files into archive...
cd api
tar -cf ../update_pkg.tar database/migrations/2026_01_15_212115_create_master_isotank_components_table.php app/Models/MasterIsotankComponent.php app/Models/MasterIsotank.php app/Http/Controllers/Api/Admin/CalibrationController.php app/Http/Controllers/Web/Admin/CalibrationMasterController.php routes/api.php routes/web.php resources/views/admin/calibration-master resources/views/layouts/app.blade.php
cd ..

echo.
echo 2. Uploading Package (Enter Password for upload)...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_pkg.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Extracting and Installing on Server (Enter Password for execution)...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_pkg.tar && rm update_pkg.tar && php artisan migrate --force"

echo.
echo ==========================================
echo        SUCCESSFULLY DEPLOYED!
echo ==========================================
echo Silakan cek menu 'Calibration Master' di Admin Panel.
pause
