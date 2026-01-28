@echo off
echo ==========================================
echo   DEPLOYMENT HELPER - ISOTANK SYSTEM
echo ==========================================
echo.
echo Script ini akan menghubungkan ke VPS dan menarik update terbaru.
echo Pastikan Anda mengetahui password SSH VPS.
echo.
echo Connecting to 202.10.44.146...
echo.

ssh root@202.10.44.146 "cd /var/www/isotank-system/api && git pull origin main && php artisan migrate --force && php artisan cache:clear && php artisan config:clear"

echo.
echo ==========================================
echo   DEPLOYMENT SELESAI (Jika tidak ada error di atas)
echo ==========================================
pause
