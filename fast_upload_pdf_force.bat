@echo off
echo ==========================================
echo    RETRY PDF UPLOAD (FORCE)
echo ==========================================
echo 1. Packing files...
cd api
if exist ../update_pdf_force.tar del ../update_pdf_force.tar
tar -cf ../update_pdf_force.tar resources/views/pdf/inspection_report.blade.php
cd ..

echo.
echo 2. Uploading (Verbose)...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp -v update_pdf_force.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Extracting...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_pdf_force.tar && rm update_pdf_force.tar && php artisan view:clear"

echo.
echo ==========================================
echo        SUCCESS (HOPEFULLY)
echo ==========================================
pause
