@echo off
echo ==========================================
echo      REPORT EXCEL UPGRADE (.xlsx)
echo ==========================================

echo 1. Packing files into archive...
cd api
tar -cf ../update_report_excel.tar app/Console/Commands/SendWeeklyReport.php app/Mail/WeeklyOperationsReport.php
cd ..

echo.
echo 2. Uploading Package (Enter Password for upload)...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_report_excel.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Extracting and Installing on Server (Enter Password for execution)...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_report_excel.tar && rm update_report_excel.tar"

echo.
echo ==========================================
echo        SUCCESSFULLY DEPLOYED!
echo ==========================================
echo Mulai sekarang, Laporan Mingguan Anda akan berbentuk File Excel Pro (.xlsx)
echo Dilengkapi Header berwarna Biru dan Teks Tebal.
pause
