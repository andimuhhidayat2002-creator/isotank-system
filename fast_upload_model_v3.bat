@echo off
echo ==========================================
echo      FIXING MISSING OWNER (V3 - ROBUST)
echo ==========================================
echo Menggunakan koneksi lebih stabil dan satu kali login (jika memungkinkan).

echo 1. Packing files...
cd api
tar -cf ../update_model_v3.tar app/Models/MasterIsotank.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_model_v3.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing & Clearing Cache...
echo (Anda diminta password untuk terakhir kalinya)
ssh -t %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xvf update_model_v3.tar && rm -f update_model_v3.tar && cd api && php artisan optimize:clear && echo DONE_SUCCESS"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Script selesai. Silakan Upload Excel lagi.
pause
