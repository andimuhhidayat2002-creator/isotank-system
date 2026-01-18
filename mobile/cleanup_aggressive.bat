@echo off
echo ==========================================
echo   AGGRESSIVE CLEANUP - Maximum Space
echo ==========================================
echo.
echo WARNING: This will delete almost everything!
echo You'll need to re-download when building again.
echo.
echo This will delete:
echo [1] Gradle Global Cache (4.06 GB)
echo [2] Android NDK (2.12 GB)
echo [3] Android System Images (2.37 GB)
echo [4] Android Emulator (1.03 GB)
echo [5] Android Sources (0.20 GB)
echo [6] .android folder (0.50 GB)
echo.
echo Total: ~10.28 GB
echo.
set /p confirm="Are you SURE? This is aggressive! (yes/no): "
if /i not "%confirm%"=="yes" goto end

echo.
echo [1/6] Cleaning Gradle Cache...
rmdir /s /q "%USERPROFILE%\.gradle\caches" 2>nul
rmdir /s /q "%USERPROFILE%\.gradle\wrapper" 2>nul
echo Done!

echo.
echo [2/6] Cleaning Android NDK...
rmdir /s /q "%LOCALAPPDATA%\Android\Sdk\ndk" 2>nul
echo Done!

echo.
echo [3/6] Cleaning Android System Images...
rmdir /s /q "%LOCALAPPDATA%\Android\Sdk\system-images" 2>nul
echo Done!

echo.
echo [4/6] Cleaning Android Emulator...
rmdir /s /q "%LOCALAPPDATA%\Android\Sdk\emulator" 2>nul
echo Done!

echo.
echo [5/6] Cleaning Android Sources...
rmdir /s /q "%LOCALAPPDATA%\Android\Sdk\sources" 2>nul
echo Done!

echo.
echo [6/6] Cleaning .android folder...
rmdir /s /q "%USERPROFILE%\.android\avd" 2>nul
rmdir /s /q "%USERPROFILE%\.android\cache" 2>nul
echo Done!

echo.
echo ==========================================
echo   CLEANUP COMPLETE!
echo ==========================================
echo.
echo ~10.28 GB freed!
echo Files will be re-downloaded when needed.
echo.

:end
pause
