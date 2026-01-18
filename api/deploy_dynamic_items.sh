#!/bin/bash

# Deployment Script for Dynamic Inspection Items Update
# Date: 2026-01-18
# Target: VPS 202.10.44.146

set -e  # Exit on error

echo "=========================================="
echo "Deploying Dynamic Inspection Items Update"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# VPS Configuration
VPS_USER="root"
VPS_HOST="202.10.44.146"
PROJECT_PATH="/var/www/isotank-system/api"

echo -e "${YELLOW}Step 1: Pulling latest changes from GitHub...${NC}"
cd $PROJECT_PATH
git pull origin main

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Git pull successful${NC}"
else
    echo -e "${RED}✗ Git pull failed${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 2: Installing/Updating Composer dependencies...${NC}"
composer install --no-dev --optimize-autoloader

echo ""
echo -e "${YELLOW}Step 3: Running migration refresh for inspection_items...${NC}"
echo -e "${RED}WARNING: This will drop and recreate the inspection_items table!${NC}"
read -p "Continue? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate:refresh --path=database/migrations/2026_01_10_024045_create_inspection_items_table.php --force
    echo -e "${GREEN}✓ Migration completed${NC}"
else
    echo -e "${YELLOW}Skipping migration refresh${NC}"
fi

echo ""
echo -e "${YELLOW}Step 4: Seeding inspection items...${NC}"
php artisan db:seed --class=InspectionItemsSeeder --force

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Seeding successful${NC}"
else
    echo -e "${RED}✗ Seeding failed${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Step 5: Clearing all caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ Caches cleared${NC}"

echo ""
echo -e "${YELLOW}Step 6: Optimizing for production...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✓ Optimization complete${NC}"

echo ""
echo -e "${YELLOW}Step 7: Setting correct permissions...${NC}"
chown -R www-data:www-data $PROJECT_PATH
chmod -R 775 $PROJECT_PATH/storage
chmod -R 775 $PROJECT_PATH/bootstrap/cache
echo -e "${GREEN}✓ Permissions set${NC}"

echo ""
echo -e "${YELLOW}Step 8: Restarting services...${NC}"
systemctl restart php8.2-fpm
systemctl restart nginx
echo -e "${GREEN}✓ Services restarted${NC}"

echo ""
echo "=========================================="
echo -e "${GREEN}Deployment Complete!${NC}"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Test the API endpoint: http://202.10.44.146/api/inspector/inspection-items"
echo "2. Install new APK on mobile device: app-release-v1.0.3-dynamic-items.apk"
echo "3. Test inspection form to verify dynamic items appear correctly"
echo ""
echo "Rollback command (if needed):"
echo "  git revert HEAD && git push origin main"
echo ""
