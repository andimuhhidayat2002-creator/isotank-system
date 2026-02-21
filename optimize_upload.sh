#!/bin/bash

# OPTIMIZE UPLOAD LIMITS SCRIPT (V2 - ROBUST)
# This script increases the upload limits for Nginx and PHP to handle calibration files.

echo "==============================================="
echo "   UPDATING UPLOAD LIMITS (V2 - ROBUST)"
echo "   Target: Nginx & PHP-FPM"
echo "==============================================="

# 1. Update Nginx Global Config
NGINX_GLOBAL="/etc/nginx/nginx.conf"
if [ -f "$NGINX_GLOBAL" ]; then
    echo "[1] Updating Global Nginx Config: $NGINX_GLOBAL"
    if grep -q "client_max_body_size" "$NGINX_GLOBAL"; then
        sed -i 's/client_max_body_size .*/client_max_body_size 100M;/g' "$NGINX_GLOBAL"
    else
        # Add inside http block
        sed -i '/http {/a \    client_max_body_size 100M;' "$NGINX_GLOBAL"
    fi
fi

# 2. Update All Enabled Nginx Sites
echo "[2] Scanning Nginx enabled sites..."
for site in /etc/nginx/sites-enabled/*; do
    if [ -f "$site" ]; then
        echo "    - Updating site: $site"
        if grep -q "client_max_body_size" "$site"; then
            sed -i 's/client_max_body_size .*/client_max_body_size 100M;/g' "$site"
        else
            sed -i '/server_name/a \    client_max_body_size 100M;' "$site"
        fi
    fi
done

# 3. Update PHP-FPM Configuration (Scan for common versions)
PHP_VERSIONS=("8.2" "8.1" "8.0" "7.4")
for ver in "${PHP_VERSIONS[@]}"; do
    PHP_INI="/etc/php/$ver/fpm/php.ini"
    if [ -f "$PHP_INI" ]; then
        echo "[3] Updating PHP $ver Config: $PHP_INI"
        sed -i 's/upload_max_filesize = .*/upload_max_filesize = 100M/g' "$PHP_INI"
        sed -i 's/post_max_size = .*/post_max_size = 100M/g' "$PHP_INI"
        sed -i 's/memory_limit = .*/memory_limit = 256M/g' "$PHP_INI"
        
        systemctl restart php$ver-fpm 2>/dev/null
        echo "    - PHP $ver restarted."
    fi
done

# 4. Restart Nginx
echo "[4] Restarting Nginx..."
nginx -t && systemctl restart nginx
echo "    - Nginx restarted."

echo "==============================================="
echo "✅ OPTIMIZATION COMPLETED"
echo "   - Nginx Global: 100M"
echo "   - PHP Limits: 100M"
echo "   - Session Memory: 256M"
echo "==============================================="

# 5. Verification Command
echo "Current Nginx Setting:"
nginx -T 2>/dev/null | grep client_max_body_size | head -n 5

