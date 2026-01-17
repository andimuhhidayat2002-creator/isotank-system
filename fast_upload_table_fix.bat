@echo off
echo ==========================================
echo      FIXING TABLE LAYOUT AND IMPORT LOGIC
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_table_fix.tar app/Imports/MasterIsotanksImport.php app/Http/Controllers/Web/Admin/IsotankUploadController.php resources/views/admin/isotanks.blade.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_table_fix.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_table_fix.tar && rm update_table_fix.tar"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Table Layout Updated.
echo Import Logic Updated (Date Fix).
pause
