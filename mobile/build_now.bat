@echo off
cd /d "c:\Users\USER\isotank_app"

echo ==========================================
echo      FLUTTER BUILD PROCESS
echo ==========================================

echo 1. Cleaning project...
call flutter clean

echo.
echo 2. Getting dependencies...
call flutter pub get

echo.
echo 3. Building Release APK...
echo    This may take a few minutes. Please wait.
call flutter build apk --release

echo.
echo ==========================================
echo      BUILD FINISHED
echo ==========================================
if exist "build\app\outputs\flutter-apk\app-release.apk" (
    echo APK created successfully at:
    echo build\app\outputs\flutter-apk\app-release.apk
) else (
    echo BUILD FAILED. Please check the errors above.
)
pause
