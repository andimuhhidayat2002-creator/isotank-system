#!/bin/bash
# Quick deployment commands for VPS
# Copy-paste these commands after uploading the ZIP file

echo "=========================================="
echo "  ISOTANK CALIBRATION FIX DEPLOYMENT"
echo "=========================================="
echo ""

# Extract uploaded files
cd /tmp
unzip -o calibration_fix_deploy.zip

# Copy to correct locations
echo "Copying files to production..."
cp -f api/app/Http/Controllers/Api/Inspector/InspectionSubmitController.php /var/www/isotank-system/api/app/Http/Controllers/Api/Inspector/
cp -f api/docs/CALIBRATION_MASTER_UPDATE_TROUBLESHOOTING.md /var/www/isotank-system/api/docs/
cp -f api/deploy_vps.sh /var/www/isotank-system/api/

# Make deploy script executable
chmod +x /var/www/isotank-system/api/deploy_vps.sh

# Run deployment
echo ""
echo "Running deployment script..."
/var/www/isotank-system/api/deploy_vps.sh

# Clean up
echo ""
echo "Cleaning up temporary files..."
rm -f /tmp/calibration_fix_deploy.zip
rm -rf /tmp/api

echo ""
echo "=========================================="
echo "  DEPLOYMENT COMPLETED!"
echo "=========================================="
echo ""
echo "Files deployed:"
echo "✓ InspectionSubmitController.php"
echo "✓ CALIBRATION_MASTER_UPDATE_TROUBLESHOOTING.md"
echo "✓ deploy_vps.sh"
echo ""
echo "Services restarted:"
echo "✓ PHP-FPM"
echo "✓ Nginx"
echo ""
echo "Next: Test inspection submission and check logs"
echo "      tail -f /var/www/isotank-system/api/storage/logs/laravel.log"
echo ""
