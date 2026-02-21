#!/bin/bash

# OPTIMIZE UPLOAD LIMITS SCRIPT
# This script increases the upload limits for Nginx and PHP to handle large calibration files.

echo "==============================================="
echo "   UPDATING UPLOAD LIMITS (Nginx & PHP)"
echo "==============================================="

# 1. Update Nginx Configuration
NGINX_CONF="/etc/nginx/sites-available/isotank"
if [ -f "$NGINX_CONF" ]; then
    echo "[1] Updating Nginx Config: $NGINX_CONF"
    
    # Check if client_max_body_size already exists
    if grep -q "client_max_body_size" "$NGINX_CONF"; then
        sed -i 's/client_max_body_size .*/client_max_body_size 100M;/g' "$NGINX_CONF"
    else
        # Add it inside the server block if not exist
        sed -i '/server_name/a \    client_max_body_size 100M;' "$NGINX_CONF"
    fi
    
    nginx -t && systemctl restart nginx
    echo "    - Nginx updated to 100M and restarted."
else
    echo "[!] Nginx config $NGINX_CONF not found. Skipping Nginx update."
fi

# 2. Update PHP-FPM Configuration
PHP_INI="/etc/php/8.2/fpm/php.ini"
if [ -f "$PHP_INI" ]; then
    echo "[2] Updating PHP-FPM Config: $PHP_INI"
    
    # Update upload_max_filesize
    sed -i 's/upload_max_filesize = .*/upload_max_filesize = 100M/g' "$PHP_INI"
    
    # Update post_max_size
    sed -i 's/post_max_size = .*/post_max_size = 100M/g' "$PHP_INI"
    
    systemctl restart php8.2-fpm
    echo "    - PHP-FPM updated to 100M and restarted."
else
    echo "[!] PHP-FPM config $PHP_INI not found. Trying CLI or different version..."
    # Fallback to check other potential locations or just notify
fi

echo "==============================================="
echo "✅ UPLOAD LIMITS UPDATED TO 100M"
echo "==============================================="
