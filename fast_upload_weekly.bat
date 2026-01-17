@echo off
echo ==========================================
echo      FAST WEEKLY REPORT UPLOAD
echo ==========================================

echo 1. Packing files into archive...
cd api
tar -cf ../update_weekly_report.tar app/Mail/WeeklyOperationsReport.php app/Console/Commands/SendWeeklyReport.php resources/views/emails/reports/weekly.blade.php
cd ..

echo.
echo 2. Uploading Package (Enter Password for upload)...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_weekly_report.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Extracting and Installing on Server (Enter Password for execution)...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_weekly_report.tar && rm update_weekly_report.tar"

echo.
echo ==========================================
echo        SUCCESSFULLY DEPLOYED!
echo ==========================================
echo.
echo Untuk test kirim laporan sekarang, jalankan perintah ini di VPS:
echo php artisan report:weekly email_anda@example.com
echo.
pause
