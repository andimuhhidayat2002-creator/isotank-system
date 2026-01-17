@echo off
echo ==========================================
echo      REPORT BUSINESS LOGIC ALIGNMENT
echo ==========================================

echo 1. Packing files into archive...
cd api
tar -cf ../update_report_business.tar app/Http/Controllers/Web/Admin/ReportController.php
cd ..

echo.
echo 2. Uploading Package (Enter Password for upload)...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_report_business.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Extracting and Installing on Server (Enter Password for execution)...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_report_business.tar && rm update_report_business.tar"

echo.
echo ==========================================
echo        SUCCESSFULLY DEPLOYED!
echo ==========================================
echo Logika Laporan Harian Updated:
echo 1. Incoming: Dihitung saat Admin buat Job (Gate In).
echo 2. Outgoing: Dihitung saat Receiver Confirm (Gate Out).
echo Sesuai dengan Business Rule yang Anda jelaskan.
pause
