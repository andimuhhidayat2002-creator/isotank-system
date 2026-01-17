@echo off
echo ==========================================
echo    REVERT PDF LAYOUT TO TABLE
echo ==========================================
echo 1. Packing files...
cd api
if exist ../update_pdf_table.tar del ../update_pdf_table.tar
tar -cf ../update_pdf_table.tar resources/views/pdf/inspection_report.blade.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_pdf_table.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Extracting and Cleaning...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_pdf_table.tar && rm update_pdf_table.tar && php artisan view:clear"

echo.
echo 4. Regenerating PDF for latest inspection...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && php regenerate_pdf.php"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
pause
