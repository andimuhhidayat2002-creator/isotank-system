@echo off
echo ==========================================
echo      FIXING MASTER ISOTANK IMPORT
echo ==========================================
echo Masalah: Template Excel baru memiliki nama kolom yang berbeda.
echo Solusi: Update script Import agar mengenali nama kolom baru.
echo.

echo 1. Packing files...
cd api
tar -cf ../update_import.tar app/Imports/MasterIsotanksImport.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_import.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_import.tar && rm update_import.tar"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Script Import sudah diperbaiki.
echo Silakan coba upload file Excel Master Isotank Anda lagi.
pause
