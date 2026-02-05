#!/bin/bash
cd /var/www/isotank-system/api
git pull origin main
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
systemctl restart php8.2-fpm
echo "Deployment completed successfully!"
