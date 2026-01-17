@echo off
echo ==========================================
echo      UPDATING ISOTANK TEMPLATE TO XLSX
echo ==========================================

echo 1. Packing files...
cd api
tar -cf ../update_iso_template.tar app/Http/Controllers/Web/Admin/IsotankUploadController.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_iso_template.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_iso_template.tar && rm update_iso_template.tar"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Template Master Isotank sekarang sudah berformat Excel (.xlsx) yang rapi.
echo Header sudah terpisah per kolom.
pause
