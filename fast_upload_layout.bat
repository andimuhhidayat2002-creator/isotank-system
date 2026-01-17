@echo off
echo ==========================================
echo      FIXING UI FEEDBACK (WARNING MSG)
echo ==========================================
echo Masalah: Pesan 'Warning' tidak muncul di layar.
echo Solusi: Update layout agar menampilkan pesan Warning.

echo 1. Packing files...
cd api
tar -cf ../update_layout.tar resources/views/layouts/app.blade.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_layout.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_layout.tar && rm update_layout.tar"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Layout sudah diperbaiki.
echo Silakan Upload Ulang file Excel Anda.
echo Kali ini jika gagal, seharusnya muncul pesan error berwarna KUNING/MERAH.
pause
