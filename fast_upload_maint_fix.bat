@echo off
echo ==========================================
echo      UPDATING MAINTENANCE IMPORT
echo ==========================================

echo 1. Packing files...
cd api
tar -cf ../update_maint_import.tar app/Imports/MaintenanceImport.php app/Http/Controllers/Web/Admin/TemplateController.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_maint_import.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_maint_import.tar && rm update_maint_import.tar"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Sekarang proses upload Maintenance sudah mendukung status 'Closed' / 'Completed'.
echo Template Excel juga sudah diperbarui dengan kolom Status & Completion Date.
pause
