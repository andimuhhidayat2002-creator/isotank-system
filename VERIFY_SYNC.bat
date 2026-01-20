@echo off
echo ========================================================
echo       ISOTANK SYSTEM - INTEGRITY CHECK (UPDATED)
echo ========================================================
echo.
echo [1] LOCAL REPOSITORY (YOUR PC)
echo --------------------------------------------------------
git log -1 --format="Commit: %%H"
git status -s
echo.
echo.
echo [2] REMOTE SERVER (VPS: 202.10.44.146)
echo --------------------------------------------------------
echo Connecting to server check /var/www/isotank-system/api ... 
echo (Please enter SSH password if prompted)
echo.
ssh root@202.10.44.146 "cd /var/www/isotank-system/api && echo '--- SERVER GIT STATUS ---' && git status && echo '' && echo '--- SERVER LAST COMMIT ---' && git log -1 --format='Commit: %%H'"
echo.
echo ========================================================
echo INSTRUCTIONS:
echo 1. COPY the output above.
echo 2. PASTE it into the chat with the AI.
echo ========================================================
pause
