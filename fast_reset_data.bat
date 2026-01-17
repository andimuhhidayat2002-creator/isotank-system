@echo off
echo ==========================================
echo      RESET OPERATIONAL DATA (CLEANUP)
echo ==========================================
echo PERINGATAN! Script ini akan MENGHAPUS SEMUA DATA:
echo - Master Isotank
echo - Riwayat Inspeksi, Maintenance, Vakum, Kalibrasi
echo - File Upload
echo.
echo User dan Konfigurasi System TIDAK akan dihapus.
echo.
echo Tekan Ctrl+C sekarang jika ingin membatalkan.
pause

echo 1. Packing files...
cd api
tar -cf ../update_reset_cmd.tar app/Console/Commands/ResetOperationalData.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_reset_cmd.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_reset_cmd.tar && rm update_reset_cmd.tar"

echo.
echo 4. Executing Reset Command (Interactive)...
ssh -t %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH%/api && php artisan app:reset-operational-data"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Data lama telah dibersihkan. Sistem siap untuk Data Real.
pause
