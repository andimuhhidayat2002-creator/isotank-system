@echo off
echo ==========================================
echo    POPULATING LATEST CONDITION DATA
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_latest_pop.tar app/Http/Controllers/Web/Admin/AdminController.php resources/views/admin/reports/latest_inspections.blade.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_latest_pop.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Clearing Cache...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_latest_pop.tar && rm update_latest_pop.tar && php artisan view:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Empty cells in Latest Condition should now be filled from Master Data.
pause
