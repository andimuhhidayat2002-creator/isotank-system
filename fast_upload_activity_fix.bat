@echo off
echo ==========================================
echo      FIXING ACTIVITY PLANNER CRASH
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_activity_fix.tar app/Http/Controllers/Web/Admin/AdminController.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_activity_fix.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_activity_fix.tar && rm update_activity_fix.tar"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Controller Updated. 500 Error should be gone.
pause
