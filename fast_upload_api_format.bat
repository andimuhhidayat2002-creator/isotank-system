@echo off
echo ==========================================
echo    FIXING API FORMATTING & VACUUM DATE
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_api_format_fix.tar app/Http/Controllers/Api/Inspector/InspectionJobController.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_api_format_fix.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Clearing Cache...
# Route clear is important for API
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_api_format_fix.tar && rm update_api_format_fix.tar && php artisan route:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Vacuum Date added. Values formatted.
echo Please RELOAD the Flutter app inspection page.
pause
