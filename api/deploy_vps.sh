#!/bin/bash

# VPS Deployment Script for Isotank System
# Run this script ON THE VPS SERVER after git pull

echo "=========================================="
echo "  ISOTANK SYSTEM - VPS DEPLOYMENT"
echo "=========================================="
echo ""

# Configuration
PROJECT_PATH="/var/www/isotank-system/api"
WEB_USER="www-data"

echo "Project Path: $PROJECT_PATH"
echo "Web User: $WEB_USER"
echo ""

# Navigate to project
cd $PROJECT_PATH || { echo "Error: Cannot access $PROJECT_PATH"; exit 1; }

echo "Step 1: Pulling latest changes from Git..."
git pull origin main
echo "✓ Git pull completed"
echo ""

echo "Step 2: Installing/Updating Composer dependencies..."
composer install --no-dev --optimize-autoloader
echo "✓ Composer install completed"
echo "Step 3: Running database migrations..."
php artisan migrate --force
echo "✓ Migrations completed"
echo ""

echo "Step 4: Clearing all caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✓ Cache cleared"
echo ""

echo "Step 5: Optimizing application..."
php artisan config:cache
php artisan route:cache
echo "✓ Optimization completed"
echo ""

echo "Step 6: Setting correct permissions..."
chown -R $WEB_USER:$WEB_USER storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
echo "✓ Permissions set"
echo ""

echo "Step 7: Restarting services..."
systemctl restart php8.2-fpm
systemctl restart nginx
echo "✓ Services restarted"
echo ""

echo "=========================================="
echo "  DEPLOYMENT COMPLETED SUCCESSFULLY!"
echo "=========================================="
echo ""
echo "Changes deployed:"
echo "- Added logging to calibration master update"
echo "- Improved debugging for calibration sync issue"
echo ""
echo "Next steps:"
echo "1. Test inspection submission from Flutter app"
echo "2. Check logs: tail -f storage/logs/laravel.log"
echo "3. Verify master_isotank_calibration_status table"
echo ""
