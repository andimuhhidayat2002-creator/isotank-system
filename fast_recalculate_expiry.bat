@echo off
echo ==========================================
echo    RECALCULATING EXPIRY DATES
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_recalc.tar app/Console/Commands/RecalculateCalibrationExpiry.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_recalc.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Running Recalculation...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_recalc.tar && rm update_recalc.tar && php artisan calibration:recalculate"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Expiry dates updated based on existing calibration dates.
pause
