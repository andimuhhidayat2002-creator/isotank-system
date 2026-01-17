@echo off
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

echo Uploading debug script...
scp api/debug_errors.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo Running debug script on VPS...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && php debug_errors.php && rm debug_errors.php"

pause
