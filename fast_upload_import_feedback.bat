@echo off
echo ==========================================
echo      UPDATING IMPORT DEBUG FEEDBACK
echo ==========================================
echo Script ini akan menampilkan detail kenapa upload gagal langsung di layar.

echo 1. Packing files...
cd api
tar -cf ../update_import_feedback.tar app/Imports/MasterIsotanksImport.php app/Http/Controllers/Web/Admin/IsotankUploadController.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_import_feedback.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_import_feedback.tar && rm update_import_feedback.tar"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Silakan Upload Ulang file Excel Anda.
echo Kali ini pesan error akan menampilkan "Header" apa yang terbaca oleh sistem.
pause
