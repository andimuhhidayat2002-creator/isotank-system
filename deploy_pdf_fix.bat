@echo off
echo ==========================================
echo   DEPLOYING PDF & INSPECTION VIEW FIXES
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_pdf_fix.tar resources/views/pdf/inspection_report.blade.php resources/views/admin/reports/inspection_show.blade.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system/api

scp update_pdf_fix.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

IF %ERRORLEVEL% NEQ 0 (
    echo Upload failed.
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo 3. Extracting and clearing cache...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_pdf_fix.tar && rm update_pdf_fix.tar && php artisan view:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
pause
