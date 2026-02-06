#!/bin/bash
# SECURITY FIXES - PRIORITY 1 (CRITICAL)
# Script untuk implementasi perbaikan keamanan kritis
# Jalankan di VPS production: bash security_fixes_p1.sh

echo "=================================================="
echo "ISOTANK SECURITY FIXES - PRIORITY 1"
echo "=================================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}ERROR: Please run as root (sudo bash security_fixes_p1.sh)${NC}"
    exit 1
fi

echo -e "${YELLOW}[1/5] Checking current environment...${NC}"
cd /var/www/isotank-system/api || exit

# Backup current .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
echo -e "${GREEN}✓ .env backed up${NC}"

echo ""
echo -e "${YELLOW}[2/5] Verifying production environment settings...${NC}"

# Check APP_DEBUG
if grep -q "APP_DEBUG=true" .env; then
    echo -e "${RED}✗ WARNING: APP_DEBUG is still TRUE!${NC}"
    echo "  Updating to false..."
    sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
    echo -e "${GREEN}✓ APP_DEBUG set to false${NC}"
else
    echo -e "${GREEN}✓ APP_DEBUG is already false${NC}"
fi

# Check APP_ENV
if grep -q "APP_ENV=local" .env; then
    echo -e "${RED}✗ WARNING: APP_ENV is still 'local'!${NC}"
    echo "  Updating to production..."
    sed -i 's/APP_ENV=local/APP_ENV=production/' .env
    echo -e "${GREEN}✓ APP_ENV set to production${NC}"
else
    echo -e "${GREEN}✓ APP_ENV is correct${NC}"
fi

echo ""
echo -e "${YELLOW}[3/5] Adding rate limiting to login endpoint...${NC}"

# Backup routes file
cp routes/api.php routes/api.php.backup.$(date +%Y%m%d_%H%M%S)

# Add throttle middleware to login route
if grep -q "Route::post('/login'" routes/api.php; then
    if ! grep -q "throttle:5,1" routes/api.php; then
        sed -i "s|Route::post('/login', \[AuthController::class, 'login'\]);|Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');|" routes/api.php
        echo -e "${GREEN}✓ Rate limiting added (5 attempts per minute)${NC}"
    else
        echo -e "${GREEN}✓ Rate limiting already configured${NC}"
    fi
fi

echo ""
echo -e "${YELLOW}[4/5] Updating session lifetime for field users...${NC}"

# Update session lifetime to 8 hours (480 minutes)
if grep -q "SESSION_LIFETIME=120" .env; then
    sed -i 's/SESSION_LIFETIME=120/SESSION_LIFETIME=480/' .env
    echo -e "${GREEN}✓ Session lifetime extended to 8 hours${NC}"
else
    echo -e "${GREEN}✓ Session lifetime already configured${NC}"
fi

echo ""
echo -e "${YELLOW}[5/5] Clearing cache and restarting services...${NC}"

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Restart PHP-FPM
systemctl restart php8.2-fpm

# Restart Nginx
systemctl restart nginx

echo -e "${GREEN}✓ All caches cleared and services restarted${NC}"

echo ""
echo "=================================================="
echo -e "${GREEN}PRIORITY 1 FIXES COMPLETED!${NC}"
echo "=================================================="
echo ""
echo -e "${YELLOW}NEXT STEPS (MANUAL):${NC}"
echo ""
echo "1. CHANGE ALL DEFAULT PASSWORDS:"
echo "   - Login to web admin: https://kayanconect.com/admin"
echo "   - Go to User Management"
echo "   - Change passwords for ALL users:"
echo "     • admin@isotank.com"
echo "     • inspector@isotank.com"
echo "     • maintenance@isotank.com"
echo "     • management@isotank.com"
echo "     • driver@isotank.com"
echo "     • receiver@isotank.com"
echo "     • yard@isotank.com"
echo ""
echo "2. VERIFY CHANGES:"
echo "   - Test login rate limiting (try 6 failed attempts)"
echo "   - Verify APP_DEBUG=false (check error pages)"
echo "   - Test session timeout (should be 8 hours)"
echo ""
echo -e "${RED}IMPORTANT: Do NOT forget to change all default passwords!${NC}"
echo ""
