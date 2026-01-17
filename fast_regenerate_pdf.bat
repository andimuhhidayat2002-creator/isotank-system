@echo off
echo ==========================================
echo    REGENERATE PDF (LATEST)
echo ==========================================
echo 1. Uploading script...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp api/regenerate_pdf.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 2. Executing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && php regenerate_pdf.php && rm regenerate_pdf.php"

echo.
echo ==========================================
echo        DONE
echo ==========================================
pause
