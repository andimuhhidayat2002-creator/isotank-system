@echo off
echo ==========================================
echo   DEPLOYING WEB RELEASE (Refactor + CSV)
echo ==========================================
echo 1. Packing views and controllers...
cd api
tar -cf ../update_release.tar resources/views/layouts/app.blade.php resources/views/admin/dashboard.blade.php resources/views/admin/dashboard/location_detail.blade.php resources/views/admin/reports/maintenance.blade.php resources/views/admin/reports/partials/maintenance_table.blade.php resources/views/emails/daily_report.blade.php resources/views/admin/activities.blade.php resources/views/pdf/inspection_report.blade.php resources/views/admin/reports/inspection_show.blade.php app/Http/Controllers/Web/Admin/AdminController.php app/Http/Controllers/Api/Inspector/InspectionSubmitController.php app/Http/Controllers/Api/Maintenance/MaintenanceJobController.php routes/web.php
cd ..

echo.
echo 2. Uploading...
echo Please enter VPS password if prompted.
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system/api

scp update_release.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

IF %ERRORLEVEL% NEQ 0 (
    echo Upload failed.
    pause
    exit /b %ERRORLEVEL%
)

echo.
echo 3. Deploying...
echo Please enter VPS password if prompted.
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_release.tar && rm update_release.tar && php artisan view:clear && php artisan route:clear"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
pause
