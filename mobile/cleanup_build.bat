@echo off
cd /d "c:\Users\USER\isotank_app"

echo ==========================================
echo      FLUTTER BUILD CLEANUP
echo ==========================================
echo.
echo Checking current disk usage...
echo.

REM Show current sizes
powershell -Command "Get-ChildItem -Path 'build', '.dart_tool', 'android\.gradle' -Directory -ErrorAction SilentlyContinue | ForEach-Object { $size = (Get-ChildItem $_.FullName -Recurse -ErrorAction SilentlyContinue | Measure-Object -Property Length -Sum).Sum / 1MB; [PSCustomObject]@{Folder=$_.Name; 'Size_MB'=[math]::Round($size, 2)} } | Format-Table -AutoSize"

echo.
echo ==========================================
echo What would you like to clean?
echo ==========================================
echo.
echo [1] Clean build folder only (Recommended - ~800MB freed)
echo [2] Clean build + .dart_tool (~860MB freed)
echo [3] Clean build + .dart_tool + android/.gradle (~880MB freed)
echo [4] Full flutter clean (safest, rebuilds everything)
echo [5] Cancel
echo.
set /p choice="Enter your choice (1-5): "

if "%choice%"=="1" goto clean_build
if "%choice%"=="2" goto clean_build_dart
if "%choice%"=="3" goto clean_all
if "%choice%"=="4" goto flutter_clean
if "%choice%"=="5" goto end

:clean_build
echo.
echo Cleaning build folder...
rmdir /s /q build 2>nul
echo Done! ~800MB freed.
goto show_result

:clean_build_dart
echo.
echo Cleaning build and .dart_tool folders...
rmdir /s /q build 2>nul
rmdir /s /q .dart_tool 2>nul
echo Done! ~860MB freed.
goto show_result

:clean_all
echo.
echo Cleaning build, .dart_tool, and android/.gradle...
rmdir /s /q build 2>nul
rmdir /s /q .dart_tool 2>nul
rmdir /s /q android\.gradle 2>nul
echo Done! ~880MB freed.
goto show_result

:flutter_clean
echo.
echo Running flutter clean...
call flutter clean
echo Done!
goto show_result

:show_result
echo.
echo ==========================================
echo Checking new disk usage...
echo ==========================================
echo.
powershell -Command "Get-ChildItem -Path 'build', '.dart_tool', 'android\.gradle' -Directory -ErrorAction SilentlyContinue | ForEach-Object { $size = (Get-ChildItem $_.FullName -Recurse -ErrorAction SilentlyContinue | Measure-Object -Property Length -Sum).Sum / 1MB; [PSCustomObject]@{Folder=$_.Name; 'Size_MB'=[math]::Round($size, 2)} } | Format-Table -AutoSize"
echo.
echo IMPORTANT: APK file is still safe at:
echo build\app\outputs\flutter-apk\app-release.apk
echo.
echo To rebuild, run: flutter pub get
echo.

:end
pause
