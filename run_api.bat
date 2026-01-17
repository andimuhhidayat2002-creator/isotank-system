@echo off
title Isotank API Server
color 0A
echo ========================================================
echo   ISOTANK SYSTEM - API SERVER LAUNCHER
echo ========================================================
echo.
echo [1/2] Navigating to API directory...
cd /d c:\laragon\www\isotank-system\api

echo [2/2] Clearing cache to ensure fresh start...
call php artisan optimize:clear

echo.
echo ========================================================
echo   STARTING SERVER ON 0.0.0.0:8000
echo   (Accepts connections from Mobile App)
echo ========================================================
echo.
php artisan serve --host=0.0.0.0 --port=8000
pause
