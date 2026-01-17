@echo off
echo ==========================================
echo      FIXING MISSING OWNER & PRODUCT
echo ==========================================
echo Masalah: Kolom Owner dan Product kosong di website.
echo Penyebab: Model database lupa diizinkan untuk menyimpan kolom tersebut (Security Feature).
echo Solusi: Update Model agar mengizinkan penyimpanan Owner dan Product.

echo 1. Packing files...
cd api
tar -cf ../update_model_data.tar app/Models/MasterIsotank.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_model_data.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_model_data.tar && rm update_model_data.tar"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Model sudah diperbaiki.
echo Silakan Upload Excel SEKALI LAGI untuk mengisi data yang kosong.
pause
