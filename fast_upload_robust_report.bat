@echo off
echo ==========================================
echo      ROBUST REPORT UPLOAD (FIX 500)
echo ==========================================

echo 1. Packing files into archive...
cd api
tar -cf ../update_robust_report.tar app/Console/Commands/SendWeeklyReport.php app/Http/Controllers/Web/Admin/ReportController.php
cd ..

echo.
echo 2. Uploading Package (Enter Password for upload)...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_robust_report.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Extracting and Installing on Server (Enter Password for execution)...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_robust_report.tar && rm update_robust_report.tar"

echo.
echo ==========================================
echo        SUCCESSFULLY DEPLOYED!
echo ==========================================
echo Jika masih error, besar kemungkinan karena Config SMTP Mail belum diset di .env
echo Silakan cek file .env di VPS Anda pastikan MAIL_HOST, MAIL_USERNAME dll sudah benar.
pause
