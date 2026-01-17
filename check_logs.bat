@echo off
echo ==========================================
echo      CHECKING SYSTEM LOGS
echo ==========================================
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

ssh %VPS_USER%@%VPS_IP% "tail -n 100 %REMOTE_PATH%/api/storage/logs/laravel.log"
pause
