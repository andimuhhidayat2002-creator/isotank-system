@echo off
echo ==========================================
echo      FIXING VACUUM UPLOAD 500 ERROR
echo ==========================================
echo 1. Packing files...
cd api
tar -cf ../update_vacuum_fix.tar app/Imports/VacuumImport.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_vacuum_fix.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_vacuum_fix.tar && rm update_vacuum_fix.tar"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Vacuum Import Fixed.
pause
