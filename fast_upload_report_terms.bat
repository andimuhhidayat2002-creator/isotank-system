@echo off
echo ==========================================
echo      REPORT TERMINOLOGY UPDATE
echo ==========================================

echo 1. Packing files...
cd api
tar -cf ../update_report_terms.tar resources/views/emails/reports/weekly.blade.php app/Mail/WeeklyOperationsReport.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_report_terms.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_report_terms.tar && rm update_report_terms.tar"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Istilah 'Fleet' telah diubah menjadi 'Isotank' agar lebih jelas.
pause
