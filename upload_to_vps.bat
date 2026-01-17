@echo off
echo ==========================================
echo   UPLOAD FILE TO VPS - INTERACTIVE
echo ==========================================
echo.
echo File to upload: calibration_fix_deploy.zip
echo VPS: 202.10.44.146
echo User: root
echo Destination: /tmp/
echo.
echo Starting upload...
echo You will be prompted for password.
echo.
pause

REM Try SCP upload
scp "calibration_fix_deploy.zip" root@202.10.44.146:/tmp/

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ==========================================
    echo   UPLOAD SUCCESSFUL!
    echo ==========================================
    echo.
    echo File uploaded to: /tmp/calibration_fix_deploy.zip
    echo.
    echo Next step: Run deployment commands on VPS
    echo.
    set /p continue="Continue to SSH deployment? (y/n): "
    if /i "%continue%"=="y" (
        echo.
        echo Opening SSH connection...
        echo.
        echo After connecting, paste these commands:
        echo.
        type vps_one_liner.txt
        echo.
        pause
        ssh root@202.10.44.146
    )
) else (
    echo.
    echo ==========================================
    echo   UPLOAD FAILED
    echo ==========================================
    echo.
    echo Please use WinSCP or FileZilla to upload manually:
    echo.
    echo 1. Download WinSCP: https://winscp.net/
    echo 2. Connect to: 202.10.44.146 (user: root)
    echo 3. Upload calibration_fix_deploy.zip to /tmp/
    echo.
    pause
)
