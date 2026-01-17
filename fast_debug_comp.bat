@echo off
echo ==========================================
echo    DEBUGGING COMPONENT DATA (REMOTE)
echo ==========================================
echo 1. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp api/debug_comp_remote.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 2. Running...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && php debug_comp_remote.php && rm debug_comp_remote.php"

echo.
pause
