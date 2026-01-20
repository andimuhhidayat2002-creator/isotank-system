@echo off
echo ========================================================
echo       ISOTANK SERVER REPAIR LAUNCHER
echo ========================================================
echo.
echo This tool will:
echo 1. Upload 'repair_server.sh' to your VPS.
echo 2. Execute it to fix the Git Structure and Nginx.
echo.
echo Connecting to VPS (202.10.44.146)...
echo Please enter your password when prompted (twice).
echo.
echo [STEP 1] Uploading Script...
scp repair_server.sh root@202.10.44.146:/tmp/repair_server.sh
echo.
echo [STEP 2] Executing Repair...
ssh root@202.10.44.146 "chmod +x /tmp/repair_server.sh && /tmp/repair_server.sh && rm /tmp/repair_server.sh"
echo.
echo ========================================================
echo DONE.
echo ========================================================
pause
