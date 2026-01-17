@echo off
echo ==========================================
echo      FAST REPORT DASHBOARD UPLOAD
echo ==========================================

echo 1. Packing files into archive...
cd api
tar -cf ../update_report_dashboard.tar app/Http/Controllers/Web/Admin/ReportController.php routes/web.php resources/views/admin/reports/index.blade.php
cd ..

echo.
echo 2. Uploading Package (Enter Password for upload)...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_report_dashboard.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Extracting and Installing on Server (Enter Password for execution)...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_report_dashboard.tar && rm update_report_dashboard.tar"

echo.
echo ==========================================
echo        SUCCESSFULLY DEPLOYED!
echo ==========================================
echo Akses menu baru via URL: /admin/reports (Anda mungkin perlu menambah link menu manually di sidebar nanti)
pause
