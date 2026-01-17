@echo off
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

echo List directory content...
ssh %VPS_USER%@%VPS_IP% "ls -F %REMOTE_PATH%"
pause
