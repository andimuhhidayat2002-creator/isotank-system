@echo off
echo ==========================================
echo      NUMERIC FORMATTING FIX (12.0000 -> 12)
echo ==========================================

echo 1. Packing files...
cd api
tar -cf ../update_numeric_format.tar resources/views/pdf/inspection_report.blade.php resources/views/admin/reports/inspection_show.blade.php resources/views/admin/reports/latest_inspections.blade.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_numeric_format.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_numeric_format.tar && rm update_numeric_format.tar"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Format angka 12.0000 sudah diperbaiki menjadi 12 (trailing zeros dihapus)
echo di PDF, Halaman Detail Inspeksi, dan Kondisi Terkini.
pause
