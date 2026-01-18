@echo off
echo ==========================================
echo   CONSERVATIVE CLEANUP - Safe to Delete
echo ==========================================
echo.
echo This will delete:
echo [1] Gradle Global Cache (4.06 GB)
echo [2] Android NDK (2.12 GB) - Will re-download if needed
echo [3] Android System Images (2.37 GB) - Only if you don't use emulator
echo.
echo Total: ~7.42 GB (if you don't use emulator)
echo         ~5.05 GB (if you use emulator - skip system images)
echo.
set /p confirm="Continue? (y/n): "
if /i not "%confirm%"=="y" goto end

echo.
echo [1/3] Cleaning Gradle Cache...
rmdir /s /q "%USERPROFILE%\.gradle\caches" 2>nul
rmdir /s /q "%USERPROFILE%\.gradle\wrapper" 2>nul
echo Done! ~4 GB freed.

echo.
echo [2/3] Cleaning Android NDK...
rmdir /s /q "%LOCALAPPDATA%\Android\Sdk\ndk" 2>nul
echo Done! ~2.12 GB freed.

echo.
set /p skipEmulator="Do you use Android Emulator? (y/n): "
if /i "%skipEmulator%"=="y" (
    echo Skipping system-images deletion...
) else (
    echo [3/3] Cleaning Android System Images...
    rmdir /s /q "%LOCALAPPDATA%\Android\Sdk\system-images" 2>nul
    rmdir /s /q "%LOCALAPPDATA%\Android\Sdk\emulator" 2>nul
    echo Done! ~3.4 GB freed.
)

echo.
echo ==========================================
echo   CLEANUP COMPLETE!
echo ==========================================
echo.
echo Note: Files will be re-downloaded automatically when needed.
echo.

:end
pause
