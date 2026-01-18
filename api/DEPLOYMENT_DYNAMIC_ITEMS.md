## Deployment: Dynamic Inspection Items Update

**Date:** 2026-01-18  
**Version:** Backend API Update

### Changes Made

#### 1. **Database Migration Update**
- Added `'dropdown'` to `input_type` enum in `inspection_items` table
- Allows custom dropdown fields with options stored as JSON

#### 2. **Inspection Items Seeder Refactored**
- Changed categories from descriptive names (`external`, `safety`, `valve`) to letter-based (`b`, `c`)
- **Category B**: General Condition (10 items)
  - Surface, Frame, Tank Plate, Grounding System, Document Container, Safety Label
  - Venting Pipe, Explosion Proof Cover, Valve Box Door, Valve Box Door Handle
- **Category C**: Valve & Piping (7 items)
  - Valve Condition, Valve Position (dropdown), Pipe Joint, Air Source Connection
  - ESDV, Blind Flange, PRV
- **Removed** hardcoded items for categories D-G (IBOX, Instrument, Vacuum, PSV)
  - These remain hardcoded in Flutter with complex calibration/vacuum logic

#### 3. **Valve Position Enhancement**
- Changed from `condition` type to `dropdown` type
- Options: `['correct', 'incorrect']`

### Deployment Steps for VPS

```bash
# 1. SSH to VPS
ssh root@your-vps-ip

# 2. Navigate to project directory
cd /var/www/isotank-system/api

# 3. Pull latest changes from GitHub
git pull origin main

# 4. Run migration refresh for inspection_items table
php artisan migrate:refresh --path=database/migrations/2026_01_10_024045_create_inspection_items_table.php

# 5. Seed inspection items
php artisan db:seed --class=InspectionItemsSeeder

# 6. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 7. Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 8. Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

### Flutter App Changes

The Flutter app has been updated to:
- **Remove hardcoded items** for categories B and C
- **Use dynamic rendering** from database for B (General Condition) and C (Valve & Piping)
- **Keep hardcoded sections** for D (IBOX), E (Instrument), F (Vacuum), G (PSV)
- **Support dropdown input type** for fields like Valve Position
- **No "Additional" labels** - all items integrated into their proper A-G categories

### New APK Version
- **Version:** 1.0.3 (Build 4)
- **File:** `app-release-v1.0.3.apk`
- Currently building...

### Testing Checklist

After deployment, verify:
- [ ] Inspection items API returns correct categories (b, c)
- [ ] Category B shows 10 items in correct order
- [ ] Category C shows 7 items including Valve Position dropdown
- [ ] No items appear for categories D-G from database
- [ ] Flutter app displays items correctly without "Additional" labels
- [ ] Valve Position shows dropdown with Correct/Incorrect options
- [ ] Form submission includes all dynamic item values

### Rollback Plan

If issues occur:
```bash
# Revert to previous commit
git revert HEAD
git push origin main

# Or reset to specific commit
git reset --hard cebf68c
git push -f origin main
```

### Notes
- Migration refresh will **drop and recreate** the `inspection_items` table
- Ensure you have a database backup before running migration refresh
- The seeder uses `updateOrCreate` so it's safe to run multiple times
- Dynamic items only apply to categories B and C
- Categories D-G remain fully hardcoded in Flutter for complex logic
