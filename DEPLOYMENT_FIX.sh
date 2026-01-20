# DEPLOYMENT FIX SCRIPT
# Run this on VPS to ensure latest code is active

## Step 1: Clear ALL caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

## Step 2: Clear OPcache (if using PHP-FPM)
# Option A: Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Option B: Or create a script to clear opcache
# Create file: public/opcache-reset.php
# <?php
# if (function_exists('opcache_reset')) {
#     opcache_reset();
#     echo "OPcache cleared!";
# } else {
#     echo "OPcache not enabled";
# }
# ?>
# Then visit: http://your-domain/opcache-reset.php
# Then delete the file for security

## Step 3: Verify files are updated
# Check if PdfGenerationService has dynamic items code
cat app/Services/PdfGenerationService.php | grep -A 10 "ADD DYNAMIC ITEMS"

# Check if InspectionSubmitController has getInspectionForReceiver
cat app/Http/Controllers/Api/Inspector/InspectionSubmitController.php | grep -A 5 "getInspectionForReceiver"

## Step 4: Test the endpoint
# Test receiver details endpoint
curl -X GET "http://202.10.44.146/api/inspector/jobs/{JOB_ID}/receiver-details" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

## CRITICAL CHECKS:
# 1. PDF outgoing should ONLY generate on receiver confirm (line 1060-1067 in InspectionSubmitController)
# 2. Receiver items should include dynamic items from inspection_items table
# 3. getGeneralConditionItems() should query database for category 'b' items

## If still not working:
# 1. Check if inspection_items table has items with category = 'b'
SELECT * FROM inspection_items WHERE category = 'b' AND is_active = 1;

# 2. Verify the code is actually on server
ls -la app/Services/PdfGenerationService.php
ls -la app/Http/Controllers/Api/Inspector/InspectionSubmitController.php

# 3. Check PHP error logs
tail -f /var/log/php8.2-fpm.log
tail -f storage/logs/laravel.log
