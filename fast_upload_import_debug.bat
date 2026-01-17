@echo off
echo ==========================================
echo      DEBUGGING ISOTANK IMPORT
echo ==========================================
echo Script ini akan menambahkan LOGGING untuk melihat kenapa file gagal dibaca.

echo 1. Packing files...
cd api
tar -cf ../update_import_debug.tar app/Imports/MasterIsotanksImport.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_import_debug.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_import_debug.tar && rm update_import_debug.tar"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Silakan Upload Ulang file Excel Anda sekarang.
echo Jika masih gagal, saya akan memeriksa Log.
pause
