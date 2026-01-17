@echo off
echo Uploading Daily Report Patch to VPS...
set VPS_USER=root
set VPS_IP=202.10.44.146
REM Assuming the Laravel root is directly at /var/www/isotank-system on the server
set REMOTE_PATH=/var/www/isotank-system

echo Creating necessary directories on VPS...
ssh %VPS_USER%@%VPS_IP% "mkdir -p %REMOTE_PATH%/app/Services"

echo Uploading Services...
scp api/app/Services/DailyReportExcelService.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/app/Services/

echo Uploading Routes...
scp api/routes/web.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/routes/

echo Uploading Controllers...
scp api/app/Http/Controllers/Web/Admin/AdminController.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/app/Http/Controllers/Web/Admin/

echo Uploading Mailables...
scp api/app/Mail/DailyOperationsReport.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/app/Mail/

echo Uploading Views...
scp api/resources/views/admin/dashboard.blade.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/resources/views/admin/
scp api/resources/views/emails/daily_report.blade.php %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/resources/views/emails/

echo.
echo Upload complete!
echo Now please login to your VPS and run:
echo ssh %VPS_USER%@%VPS_IP%
echo cd %REMOTE_PATH%
echo composer install
echo.
pause
