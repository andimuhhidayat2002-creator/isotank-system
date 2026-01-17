@echo off
echo ==========================================
echo   UPLOAD TO VPS - INTERACTIVE MODE
echo ==========================================
echo.
echo File: calibration_fix_deploy.zip
echo VPS: 202.10.44.146
echo User: root
echo.
echo This will open a new terminal window.
echo You will be prompted for VPS password.
echo.
pause

start cmd /k "cd /d c:\laragon\www\isotank-system && echo Uploading to VPS... && echo. && scp calibration_fix_deploy.zip root@202.10.44.146:/tmp/ && echo. && echo ========================================== && echo UPLOAD SUCCESSFUL! && echo ========================================== && echo. && echo Next: SSH to VPS and run deployment && echo. && pause && ssh root@202.10.44.146"
