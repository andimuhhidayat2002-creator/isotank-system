@echo off
echo ==========================================
echo      REPORT LOGIC UPDATE (FINAL SYNC)
echo ==========================================

echo 1. Packing files into archive...
cd api
tar -cf ../update_report_final.tar app/Console/Commands/SendWeeklyReport.php app/Http/Controllers/Web/Admin/ReportController.php
cd ..

echo.
echo 2. Uploading Package (Enter Password for upload)...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_report_final.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Extracting and Installing on Server (Enter Password for execution)...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_report_final.tar && rm update_report_final.tar"

echo.
echo ==========================================
echo        SUCCESSFULLY DEPLOYED!
echo ==========================================
echo Logic Weekly Report & Preview sudah disesuaikan dengan Business Rules baru:
echo - Incoming = Job Created (Barang Masuk)
echo - Outgoing = Receiver Confirmed (Barang Keluar)
echo - Activity Highlights = Incoming + Outgoing (Total Gate Moves)
pause
