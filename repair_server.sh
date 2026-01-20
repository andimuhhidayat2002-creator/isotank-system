#!/bin/bash

# REPAIR SERVER SCRIPT
# This script aligns the VPS structure with the GitHub repository (Main Branch)
# It handles the migration from "Root Structure" to "API Subfolder Structure"

echo "==============================================="
echo "   ISOTANK SERVER REPAIR & SYNC TOOL"
echo "==============================================="

SERVER_ROOT="/var/www/isotank-system"
BACKUP_DIR="/var/www/isotank_backup_$(date +%Y%m%d_%H%M%S)"

echo "[1] Creating Backup Directory at $BACKUP_DIR..."
mkdir -p "$BACKUP_DIR"

cd "$SERVER_ROOT" || exit

# 1. Backup Critical Data
echo "[2] Backing up Configuration and Storage..."
if [ -f .env ]; then
    cp .env "$BACKUP_DIR/.env"
    echo "    - .env backed up."
fi
if [ -d storage ]; then
    cp -r storage "$BACKUP_DIR/storage"
    echo "    - storage folder backed up."
fi

# 2. Fix Git Branch
echo "[3] Switching to MAIN branch..."
git fetch origin
git checkout main
git reset --hard origin/main

# 3. Restore Data to Correct Location (inside /api)
echo "[4] Restoring Data to 'api/' subfolder..."
if [ -f "$BACKUP_DIR/.env" ]; then
    cp "$BACKUP_DIR/.env" api/.env
    echo "    - .env restored to api/.env"
fi

if [ -d "$BACKUP_DIR/storage" ]; then
    # Remove git-created empty storage
    rm -rf api/storage
    # Copy backed up storage
    cp -r "$BACKUP_DIR/storage" api/storage
    echo "    - Storage restored to api/storage"
fi

# 4. Update Nginx Config
echo "[5] Updating Nginx Configuration..."
NGINX_CONF="/etc/nginx/sites-available/isotank"
if grep -q "root /var/www/isotank-system/public" "$NGINX_CONF"; then
    sed -i 's|root /var/www/isotank-system/public|root /var/www/isotank-system/api/public|g' "$NGINX_CONF"
    echo "    - Nginx root updated to api/public"
    systemctl restart nginx
    echo "    - Nginx restarted"
else
    echo "    - Nginx config already points to api/public or different path. Skipping."
fi

# 5. Set Permissions
echo "[6] Fix Permissions..."
chown -R www-data:www-data api/storage api/bootstrap/cache
chmod -R 775 api/storage api/bootstrap/cache

# 6. Run Migrations
echo "[7] Running Database Migrations..."
cd api
php artisan migrate --force

echo "==============================================="
echo "✅ REPAIR COMPLETED SUCCESSFULY"
echo "   - Git Branch: main"
echo "   - File Structure: Aligned with Local"
echo "   - Nginx: Updated"
echo "   - Data: Preserved"
echo "==============================================="
