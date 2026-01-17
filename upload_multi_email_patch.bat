@echo off
echo Uploading Multi-Recipient Patch to VPS...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

echo Uploading Dashboard View...
scp api/resources/views/admin/dashboard.blade.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/resources/views/admin/

echo Uploading Admin Controller...
scp api/app/Http/Controllers/Web/Admin/AdminController.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/app/Http/Controllers/Web/Admin/

echo.
echo Upload complete! No composer update needed this time.
echo.
pause
