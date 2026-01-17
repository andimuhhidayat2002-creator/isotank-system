@echo off
echo ==========================================
echo      REPORT CENTER COMPLETE UPLOAD
echo ==========================================

echo 1. Packing files into archive...
cd api
tar -cf ../update_report_complete.tar app/Http/Controllers/Web/Admin/ReportController.php app/Console/Commands/SendWeeklyReport.php
cd ..

echo.
echo 2. Uploading Package (Enter Password for upload)...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_report_complete.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Extracting and Installing on Server (Enter Password for execution)...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_report_complete.tar && rm update_report_complete.tar"

echo.
echo ==========================================
echo        SUCCESSFULLY DEPLOYED!
echo ==========================================
echo Sekarang Anda bisa mengirim Daily Report dan Weekly Report ke banyak email sekaligus.
echo Weekly Preview dan Error Handling juga sudah aktif.
pause
