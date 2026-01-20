@echo off
echo ========================================================
echo       ISOTANK SERVER RE-SYNC & REPAIR TOOL
echo ========================================================
echo.
echo WARNING: This will FORCE the server to match GitHub 'main'.
echo It will:
echo 1. Backup your .env and storage (Safety First).
echo 2. Switch server git from 'master' to 'main'.
echo 3. Reset all code to match latest local/github version.
echo 4. Restore .env and storage.
echo.
echo Press Ctrl+C to cancel, or any key to proceed.
pause

echo.
echo Connecting to server... (Enter Password if prompted)
echo.

ssh root@202.10.44.146 "cd /var/www/isotank-system/api && echo '[1] Backing up Data...' && cp .env .env.bak_repair && cp -r storage storage_bak_repair && echo '[2] Fixing Git Branch...' && git fetch origin && git checkout main && echo '[3] Resetting Codebase...' && git reset --hard origin/main && echo '[4] Restoring Data...' && cp .env.bak_repair .env && cp -r storage_bak_repair/* storage/ && echo '[5] Setting Permissions...' && chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache && php artisan migrate --force && echo '✅ SUCCESS! Server is now Synced with Main Branch.'"

echo.
echo ========================================================
echo RE-SYNC COMPLETED.
echo ========================================================
echo Server is now strictly identical to GitHub 'main' branch.
echo You can verify by running VERIFY_SYNC.bat again.
echo ========================================================
pause
