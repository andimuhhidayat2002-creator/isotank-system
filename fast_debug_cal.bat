@echo off
echo ==========================================
echo    DEBUGGING CALIBRATION QUERY
echo ==========================================
echo 1. Uploading debug script...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp api/debug_cal_query.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/api/

echo.
echo 2. Running debug script...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH%/api && php debug_cal_query.php"

echo.
echo 3. Cleanup...
ssh %VPS_USER%@%VPS_IP% "rm %REMOTE_PATH%/api/debug_cal_query.php"

echo.
pause
