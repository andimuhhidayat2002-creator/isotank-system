@echo off
echo Uploading Calibration Feature to VPS...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

echo Creating necessary directories on VPS...
ssh %VPS_USER%@%VPS_IP% "mkdir -p %REMOTE_PATH%/app/Http/Controllers/Api/Admin"
ssh %VPS_USER%@%VPS_IP% "mkdir -p %REMOTE_PATH%/resources/views/admin/calibration-master"

echo Uploading Migrations...
scp api/database/migrations/2026_01_15_212115_create_master_isotank_components_table.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/database/migrations/

echo Uploading Models...
scp api/app/Models/MasterIsotankComponent.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/app/Models/
scp api/app/Models/MasterIsotank.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/app/Models/

echo Uploading Controllers...
scp api/app/Http/Controllers/Api/Admin/CalibrationController.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/app/Http/Controllers/Api/Admin/
scp api/app/Http/Controllers/Web/Admin/CalibrationMasterController.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/app/Http/Controllers/Web/Admin/

echo Uploading Routes...
scp api/routes/api.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/routes/
scp api/routes/web.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/routes/

echo Uploading Views...
scp api/resources/views/admin/calibration-master/index.blade.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/resources/views/admin/calibration-master/
scp api/resources/views/admin/calibration-master/show.blade.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/resources/views/admin/calibration-master/
scp api/resources/views/layouts/app.blade.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/resources/views/layouts/

echo.
echo Upload complete!
echo.
echo IMPORTANT: You must run the migration on the VPS!
echo ---------------------------------------------------
echo ssh %VPS_USER%@%VPS_IP%
echo cd %REMOTE_PATH%
echo php artisan migrate
echo ---------------------------------------------------
echo.
pause
