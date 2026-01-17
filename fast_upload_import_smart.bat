@echo off
echo ==========================================
echo      FIXING IMPORT (SMART HEADER)
echo ==========================================
echo Masalah: Semua baris gagal diimpor (Total Failure).
echo Penyebab: Kemungkinan ada baris kosong di atas Header.
echo Solusi: Script sekarang akan MENCARI Header di mana pun posisinya.

echo 1. Packing files...
cd api
tar -cf ../update_import_smart.tar app/Imports/MasterIsotanksImport.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_import_smart.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_import_smart.tar && rm update_import_smart.tar"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Import Isotank sudah lebih pintar.
echo Silakan Upload Ulang file Excel Anda.
pause
