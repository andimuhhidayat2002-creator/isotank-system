@echo off
echo ==========================================
echo      FIXING DATABASE SCHEMA
echo ==========================================
echo Masalah: Kolom 'manufacturer_serial_number' dan tanggal belum ada di database.
echo Penyebab: File migrasi database belum dijalankan di server.
echo Solusi: Upload file migrasi dan jalankan 'migrate'.

echo 1. Packing files...
cd api
tar -cf ../update_migration.tar database/migrations/2026_01_15_215341_add_details_to_master_isotanks_table.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_migration.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_migration.tar && rm update_migration.tar"

echo.
echo 4. Running Migration...
echo Trying Path 1: /api folder...
ssh -t %VPS_USER%@%VPS_IP% "if [ -d %REMOTE_PATH%/api ]; then cd %REMOTE_PATH%/api && php artisan migrate --force; else echo 'API folder not found, trying root...'; cd %REMOTE_PATH% && php artisan migrate --force; fi"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Database sudah di-update. Silakan Upload Excel lagi.
pause
