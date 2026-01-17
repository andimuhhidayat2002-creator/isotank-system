@echo off
echo ==========================================
echo      LINKING LATEST CONDITION TO DETAIL
echo ==========================================

echo 1. Packing files...
cd api
tar -cf ../update_latest_link.tar resources/views/admin/reports/latest_inspections.blade.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_latest_link.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_latest_link.tar && rm update_latest_link.tar"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Sekarang Tabel 'Latest Condition' sudah terhubung ke Halaman Detail.
echo Klik Nomor ISO di tabel untuk melihat detailnya.
pause
