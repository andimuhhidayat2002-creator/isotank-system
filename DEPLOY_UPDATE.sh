#!/bin/bash

# DEPLOYMENT SCRIPT - PDF & LOGIC UPDATE
# Target: VPS 202.10.44.146

VPS_USER="root"
VPS_HOST="202.10.44.146"
REMOTE_DIR="/var/www/isotank-system/api"

echo "=========================================="
echo "  ISOTANK DEPLOYMENT - PDF & LOGIC"
echo "=========================================="

# 1. Check Remote Structure
echo ""
echo "Step 1: Checking remote folder structure..."
ssh $VPS_USER@$VPS_HOST "ls -la $REMOTE_DIR/app/Services/PdfGenerationService.php"

if [ $? -eq 0 ]; then
    echo "Files exist. Proceeding with upload..."
else
    echo "WARNING: Remote file checking failed. Please verify the path $REMOTE_DIR exists."
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# 2. Upload Package
echo ""
echo "Step 2: Uploading update package..."
scp deploy_update.tar.gz $VPS_USER@$VPS_HOST:/tmp/

# 3. Extract and Deploy
echo ""
echo "Step 3: Extracting and deploying on VPS..."
ssh $VPS_USER@$VPS_HOST << 'EOF'
    # Go to temp
    cd /tmp
    
    # Extract
    tar -xzf deploy_update.tar.gz
    
    # Copy files to production (Overwriting)
    echo "Copying files..."
    cp -rf app/ $REMOTE_DIR/
    cp -rf resources/ $REMOTE_DIR/
    
    # Set permissions (just in case)
    chown -R www-data:www-data $REMOTE_DIR/app
    chown -R www-data:www-data $REMOTE_DIR/resources
    
    # Clear Caches
    echo "Clearing Caches..."
    cd $REMOTE_DIR
    php artisan cache:clear
    php artisan view:clear
    php artisan config:clear
    
    # Restart PHP-FPM (for OPcache)
    echo "Restarting PHP Service..."
    systemctl restart php8.2-fpm
    
    # Clean up
    rm -rf /tmp/deploy_update.tar.gz
    rm -rf /tmp/app
    rm -rf /tmp/resources
    
    echo "Deployment Success!"
EOF

echo ""
echo "Done."
