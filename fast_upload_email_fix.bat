@echo off
echo ==========================================
echo      EMAIL SPLITTER FIX UPLOAD
echo ==========================================

echo 1. Packing files into archive...
cd api
tar -cf ../update_email_fix.tar app/Console/Commands/SendWeeklyReport.php
cd ..

echo.
echo 2. Uploading Package (Enter Password for upload)...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_email_fix.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Extracting and Installing on Server (Enter Password for execution)...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_email_fix.tar && rm update_email_fix.tar"

echo.
echo ==========================================
echo        SUCCESSFULLY DEPLOYED!
echo ==========================================
echo Sekarang Anda bisa mengirim ke banyak email sekaligus (dipisah koma).
pause
