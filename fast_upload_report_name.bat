@echo off
echo ==========================================
echo      REPORT NAME UPDATE (PG -> Pressure Gauge)
echo ==========================================

echo 1. Packing files into archive...
cd api
tar -cf ../update_report_name.tar app/Console/Commands/SendWeeklyReport.php
cd ..

echo.
echo 2. Uploading Package (Enter Password for upload)...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_report_name.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Extracting and Installing on Server (Enter Password for execution)...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_report_name.tar && rm update_report_name.tar"

echo.
echo ==========================================
echo        SUCCESSFULLY DEPLOYED!
echo ==========================================
echo Silakan kirim laporan sekali lagi. Tulisan 'PG Main' akan menjadi 'Pressure Gauge'.
pause
