@echo off
echo ==========================================
echo      FAST REPORT SWITCHER UPLOAD
echo ==========================================

echo 1. Packing files into archive...
cd api
tar -cf ../update_report_switch.tar resources/views/admin/dashboard.blade.php app/Http/Controllers/Web/Admin/ReportController.php routes/web.php
cd ..

echo.
echo 2. Uploading Package (Enter Password for upload)...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_report_switch.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Extracting and Installing on Server (Enter Password for execution)...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_report_switch.tar && rm update_report_switch.tar"

echo.
echo ==========================================
echo        SUCCESSFULLY DEPLOYED!
echo ==========================================
echo Silakan refresh Dashboard Utama. Tombol 'Send Daily Report' sekarang sudah menjadi 'Send Report' dengan opsi ganda.
pause
