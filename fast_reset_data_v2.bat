@echo off
echo ==========================================
echo      RESET OPERATIONAL DATA (SAFE MODE v2)
echo ==========================================
echo Script ini akan menghapus DATA OPERASIONAL saja.
echo.
echo YANG ITEM YG DIHAPUS (Isotank ^& History):
echo [+] Master Isotanks
echo [+] Inspection Logs
echo [+] Maintenance Jobs
echo [+] Vacuum Logs
echo [+] Calibration Logs
echo.
echo YANG AMAN / TIDAK DIHAPUS (Config):
echo [OK] User ^& Password
echo [OK] Inspection Items (Checklist)
echo [OK] Yard Layout (Excel Config)
echo.
echo Tekan tombol apa saja untuk mulai...
pause

echo 1. Packing files...
cd api
tar -cf ../update_reset_cmd_v2.tar app/Console/Commands/ResetOperationalData.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_reset_cmd_v2.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_reset_cmd_v2.tar && rm update_reset_cmd_v2.tar"

echo.
echo 4. Executing Reset Command...
echo Trying Path 1: /api folder...
ssh -t %VPS_USER%@%VPS_IP% "if [ -d %REMOTE_PATH%/api ]; then cd %REMOTE_PATH%/api && php artisan app:reset-operational-data; else echo 'API folder not found, trying root...'; cd %REMOTE_PATH% && php artisan app:reset-operational-data; fi"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Data operasional sudah bersih. Config aman.
pause
