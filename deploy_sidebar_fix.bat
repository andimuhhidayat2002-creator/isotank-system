@echo off
echo ==========================================
echo   DEPLOYING SIDEBAR FIX
echo ==========================================
echo 1. Packing views...
cd api
tar -cf ../update_layout_sidebar.tar resources/views/layouts/app.blade.php resources/views/admin/dashboard.blade.php resources/views/admin/dashboard/location_detail.blade.php resources/views/admin/reports/maintenance.blade.php resources/views/admin/reports/partials/maintenance_table.blade.php app/Http/Controllers/Web/Admin/AdminController.php routes/web.php
cd ..

echo.
echo 2. Uploading...
echo Please enter VPS password if prompted.
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system/api

scp update_layout_sidebar.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

IF %ERRORLEVEL% NEQ 0 (
    echo Upload failed.
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo 3. Deploying...
echo Please enter VPS password if prompted.
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_layout_sidebar.tar && rm update_layout_sidebar.tar && php artisan view:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
pause
