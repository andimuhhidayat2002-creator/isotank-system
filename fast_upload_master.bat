@echo off
echo ==========================================
echo      FAST MASTER DATA UPLOAD SYSTEM
echo ==========================================

echo 1. Packing files into archive...
cd api
tar -cf ../update_master_pkg.tar database/migrations/2026_01_15_215341_add_details_to_master_isotanks_table.php app/Models/MasterIsotank.php app/Http/Controllers/Web/Admin/AdminController.php app/Http/Controllers/Web/Admin/IsotankUploadController.php app/Imports/MasterIsotanksImport.php resources/views/admin/isotanks.blade.php routes/web.php
cd ..

echo.
echo 2. Uploading Package (Enter Password for upload)...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_master_pkg.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Extracting and Installing on Server (Enter Password for execution)...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_master_pkg.tar && rm update_master_pkg.tar && php artisan migrate --force"

echo.
echo ==========================================
echo        SUCCESSFULLY DEPLOYED!
echo ==========================================
echo Silakan refresh halaman 'Master Isotanks' di Admin Panel.
pause
