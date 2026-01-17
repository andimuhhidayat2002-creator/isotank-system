@echo off
echo ==========================================
echo      REPORT PREVIEW & FIX UPLOAD
echo ==========================================

echo 1. Packing files into archive...
cd api
tar -cf ../update_report_preview.tar app/Console/Commands/SendWeeklyReport.php app/Http/Controllers/Web/Admin/ReportController.php routes/web.php resources/views/admin/dashboard.blade.php
cd ..

echo.
echo 2. Uploading Package (Enter Password for upload)...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_report_preview.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Extracting and Installing on Server (Enter Password for execution)...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_report_preview.tar && rm update_report_preview.tar"

echo.
echo ==========================================
echo        SUCCESSFULLY DEPLOYED!
echo ==========================================
echo Semua fitur report (termasuk preview & error handling) sudah diperbarui.
pause
