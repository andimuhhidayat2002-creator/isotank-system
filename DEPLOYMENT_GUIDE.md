# DEPLOYMENT GUIDE - Calibration Fix to VPS

## 📦 Files Changed

1. **api/app/Http/Controllers/Api/Inspector/InspectionSubmitController.php**
   - Added comprehensive logging to `updateMasterCalibrationStatus()` function
   - Added validation to check if serial number is not empty
   - Improved debugging capability

2. **api/docs/CALIBRATION_MASTER_UPDATE_TROUBLESHOOTING.md**
   - Complete troubleshooting guide
   - Field mapping reference
   - Testing steps

3. **api/deploy_vps.sh**
   - Automated deployment script for VPS

## 🚀 Deployment Methods

### Method 1: Manual File Upload (RECOMMENDED for now)

#### Step 1: Upload Changed File to VPS

**Option A: Using SCP (if you have SSH access)**
```bash
scp "c:\laragon\www\isotank-system\api\app\Http\Controllers\Api\Inspector\InspectionSubmitController.php" root@202.10.44.146:/var/www/isotank-system/api/app/Http/Controllers/Api/Inspector/
```

**Option B: Using WinSCP or FileZilla**
1. Connect to VPS: `202.10.44.146`
2. Navigate to: `/var/www/isotank-system/api/app/Http/Controllers/Api/Inspector/`
3. Upload file: `InspectionSubmitController.php`
4. Navigate to: `/var/www/isotank-system/api/docs/`
5. Upload file: `CALIBRATION_MASTER_UPDATE_TROUBLESHOOTING.md`

#### Step 2: SSH to VPS and Clear Cache

```bash
# SSH to VPS
ssh root@202.10.44.146

# Navigate to project
cd /var/www/isotank-system/api

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize
php artisan config:cache
php artisan route:cache

# Set permissions (if needed)
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Restart services
systemctl restart php8.2-fpm
systemctl restart nginx

# Check logs
tail -f storage/logs/laravel.log
```

---

### Method 2: Using Git (If you setup Git on VPS)

#### Step 1: Setup Git Repository on VPS

```bash
# SSH to VPS
ssh root@202.10.44.146

# Navigate to project
cd /var/www/isotank-system

# Initialize git (if not already)
git init

# Add remote (your local machine or GitHub)
# For now, we'll use local transfer
```

#### Step 2: Transfer via Git Bundle

**On Local Machine:**
```bash
cd c:\laragon\www\isotank-system
git bundle create isotank-update.bundle HEAD
```

**Transfer bundle to VPS (using SCP):**
```bash
scp isotank-update.bundle root@202.10.44.146:/tmp/
```

**On VPS:**
```bash
cd /var/www/isotank-system
git pull /tmp/isotank-update.bundle
```

---

### Method 3: Using Deployment Script

**On VPS, create and run deployment script:**

```bash
# SSH to VPS
ssh root@202.10.44.146

# Create deployment script
nano /var/www/isotank-system/api/deploy.sh
```

Paste this content:
```bash
#!/bin/bash
cd /var/www/isotank-system/api
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
systemctl restart php8.2-fpm
systemctl restart nginx
echo "Deployment completed!"
```

Make it executable:
```bash
chmod +x /var/www/isotank-system/api/deploy.sh
```

Run after uploading files:
```bash
/var/www/isotank-system/api/deploy.sh
```

---

## ✅ Verification Steps

After deployment, verify the changes:

### 1. Check File is Updated
```bash
ssh root@202.10.44.146
grep "UPDATE MASTER CALIBRATION STATUS" /var/www/isotank-system/api/app/Http/Controllers/Api/Inspector/InspectionSubmitController.php
```

Expected output: Should show the log line

### 2. Test Inspection Submission

1. Submit a new inspection from Flutter app with:
   - PG Serial: `TEST-001`
   - PG Calibration Date: Today's date
   
2. Check Laravel log:
```bash
ssh root@202.10.44.146
tail -f /var/www/isotank-system/api/storage/logs/laravel.log
```

Look for:
```
=== UPDATE MASTER CALIBRATION STATUS ===
Checking calibration item: pressure_gauge
```

### 3. Verify Database

```bash
ssh root@202.10.44.146
mysql -u isotank_user -p isotank_db
```

```sql
SELECT * FROM master_isotank_calibration_status 
WHERE item_name = 'pressure_gauge' 
ORDER BY updated_at DESC 
LIMIT 5;
```

---

## 🔧 Troubleshooting

### Issue: Permission Denied
```bash
chown -R www-data:www-data /var/www/isotank-system
chmod -R 775 /var/www/isotank-system
```

### Issue: Cache Not Clearing
```bash
rm -rf /var/www/isotank-system/api/bootstrap/cache/*
rm -rf /var/www/isotank-system/api/storage/framework/cache/*
rm -rf /var/www/isotank-system/api/storage/framework/views/*
```

### Issue: PHP-FPM Not Restarting
```bash
systemctl status php8.2-fpm
journalctl -u php8.2-fpm -n 50
```

---

## 📝 Quick Deployment Checklist

- [ ] Upload `InspectionSubmitController.php` to VPS
- [ ] Upload `CALIBRATION_MASTER_UPDATE_TROUBLESHOOTING.md` to VPS
- [ ] SSH to VPS
- [ ] Run `php artisan config:clear`
- [ ] Run `php artisan cache:clear`
- [ ] Run `php artisan config:cache`
- [ ] Run `systemctl restart php8.2-fpm`
- [ ] Run `systemctl restart nginx`
- [ ] Test inspection submission
- [ ] Check logs: `tail -f storage/logs/laravel.log`
- [ ] Verify database update

---

**Deployment Date:** 2026-01-16  
**Version:** Calibration Master Update Fix v1.0
