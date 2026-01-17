@echo off
echo ==========================================
echo    FINAL PDF FIX UPLOAD
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_pdf_report.tar resources/views/pdf/inspection_report.blade.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_pdf_report.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Extracting and Cleaning Cache...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_pdf_report.tar && rm update_pdf_report.tar && php artisan view:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo PDF template updated.
pause
