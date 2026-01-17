@echo off
echo ==========================================
echo      FIXING MISSING OWNER (FORCE UPDATE)
echo ==========================================
echo Versi ini memaksa server melupakan kode lama (Clear Cache).

echo 1. Packing files...
cd api
tar -cf ../update_model_v2.tar app/Models/MasterIsotank.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_model_v2.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_model_v2.tar && rm update_model_v2.tar"

echo.
echo 4. Clearing Server Cache...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH%/api && php artisan optimize:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Code sudah di-refresh.
echo Silakan Upload Excel lagi, data Owner harusnya muncul sekarang.
pause
