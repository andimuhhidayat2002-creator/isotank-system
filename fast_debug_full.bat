@echo off
echo ==========================================
echo    DEBUGGING FULL DATA
echo ==========================================
echo 1. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp api/debug_full.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 2. Running...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && php debug_full.php && rm debug_full.php"

echo.
pause
