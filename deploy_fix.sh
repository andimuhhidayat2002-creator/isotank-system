#!/bin/bash

# ISOTANK SYSTEM - DEPLOYMENT & CACHE CLEAR SCRIPT
# Run this on VPS to fix receiver confirmation & PDF generation issues
# Author: Development Team
# Date: 2026-01-18

echo "========================================="
echo "ISOTANK SYSTEM - DEPLOYMENT FIX"
echo "========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Step 1: Clear Laravel Caches
echo -e "${YELLOW}Step 1: Clearing Laravel caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ Laravel caches cleared${NC}"
echo ""

# Step 2: Clear OPcache (CRITICAL!)
echo -e "${YELLOW}Step 2: Clearing OPcache...${NC}"
if command -v systemctl &> /dev/null; then
    echo "Restarting PHP-FPM..."
    sudo systemctl restart php8.2-fpm
    echo -e "${GREEN}✓ PHP-FPM restarted (OPcache cleared)${NC}"
else
    echo -e "${RED}⚠ systemctl not found. Please restart PHP-FPM manually or use opcache-reset.php${NC}"
fi
echo ""

# Step 3: Verify Database Has Dynamic Items
echo -e "${YELLOW}Step 3: Checking inspection_items table...${NC}"
ITEM_COUNT=$(mysql -u root -proot isotank_db -se "SELECT COUNT(*) FROM inspection_items WHERE category = 'b' AND is_active = 1;")
echo "Dynamic items in category 'b': $ITEM_COUNT"
if [ "$ITEM_COUNT" -gt 0 ]; then
    echo -e "${GREEN}✓ Dynamic items found${NC}"
    mysql -u root -proot isotank_db -e "SELECT code, name, category FROM inspection_items WHERE category = 'b' AND is_active = 1;"
else
    echo -e "${RED}⚠ No dynamic items found! You may need to seed the database.${NC}"
fi
echo ""

# Step 4: Verify Files Are Updated
echo -e "${YELLOW}Step 4: Verifying code deployment...${NC}"
if grep -q "ADD DYNAMIC ITEMS" app/Services/PdfGenerationService.php; then
    echo -e "${GREEN}✓ PdfGenerationService has dynamic items code${NC}"
else
    echo -e "${RED}✗ PdfGenerationService missing dynamic items code!${NC}"
fi

if grep -q "getInspectionForReceiver" app/Http/Controllers/Api/Inspector/InspectionSubmitController.php; then
    echo -e "${GREEN}✓ InspectionSubmitController has receiver endpoint${NC}"
else
    echo -e "${RED}✗ InspectionSubmitController missing receiver endpoint!${NC}"
fi

if grep -q "generateOutgoingPdf" app/Http/Controllers/Api/Inspector/InspectionSubmitController.php; then
    echo -e "${GREEN}✓ Outgoing PDF generation found in receiver confirm${NC}"
else
    echo -e "${RED}✗ Outgoing PDF generation missing!${NC}"
fi
echo ""

# Step 5: Check Laravel Logs for Errors
echo -e "${YELLOW}Step 5: Checking recent errors...${NC}"
if [ -f storage/logs/laravel.log ]; then
    echo "Last 10 errors in Laravel log:"
    tail -n 20 storage/logs/laravel.log | grep -i "error" || echo "No recent errors found"
else
    echo "No Laravel log file found"
fi
echo ""

# Step 6: Test API Endpoint (Optional - requires token)
echo -e "${YELLOW}Step 6: API Endpoint Test${NC}"
echo "To test the receiver details endpoint, run:"
echo ""
echo "curl -X GET \"http://202.10.44.146/api/inspector/jobs/{JOB_ID}/receiver-details\" \\"
echo "  -H \"Authorization: Bearer YOUR_TOKEN\" \\"
echo "  -H \"Accept: application/json\" | jq '.data.items'"
echo ""

# Summary
echo "========================================="
echo -e "${GREEN}DEPLOYMENT FIX COMPLETED${NC}"
echo "========================================="
echo ""
echo "Next steps:"
echo "1. Test receiver confirmation in Flutter app"
echo "2. Verify dynamic items are loaded"
echo "3. Check that PDF is generated ONLY after receiver confirm"
echo ""
echo "If issues persist:"
echo "- Check RECEIVER_PDF_ISSUE_ANALYSIS.md for detailed debugging"
echo "- Verify database has items with category = 'b'"
echo "- Check Laravel logs: tail -f storage/logs/laravel.log"
echo ""
