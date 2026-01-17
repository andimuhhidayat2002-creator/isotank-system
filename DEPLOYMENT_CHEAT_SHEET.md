# 📋 QUICK DEPLOYMENT CHEAT SHEET

## 🎯 FASTEST METHOD (Copy-Paste Ready)

### 1️⃣ Upload File
**Using WinSCP:**
- Host: `202.10.44.146`
- User: `root`
- Upload: `calibration_fix_deploy.zip` → `/tmp/`

### 2️⃣ SSH Commands (Copy All at Once)
```bash
cd /tmp && \
unzip -o calibration_fix_deploy.zip && \
cp -f api/app/Http/Controllers/Api/Inspector/InspectionSubmitController.php /var/www/isotank-system/api/app/Http/Controllers/Api/Inspector/ && \
cp -f api/docs/CALIBRATION_MASTER_UPDATE_TROUBLESHOOTING.md /var/www/isotank-system/api/docs/ && \
cp -f api/deploy_vps.sh /var/www/isotank-system/api/ && \
chmod +x /var/www/isotank-system/api/deploy_vps.sh && \
/var/www/isotank-system/api/deploy_vps.sh && \
rm -f /tmp/calibration_fix_deploy.zip && \
rm -rf /tmp/api && \
echo "✅ DEPLOYMENT COMPLETED!"
```

### 3️⃣ Verify
```bash
grep "UPDATE MASTER CALIBRATION STATUS" /var/www/isotank-system/api/app/Http/Controllers/Api/Inspector/InspectionSubmitController.php
```

### 4️⃣ Monitor
```bash
tail -f /var/www/isotank-system/api/storage/logs/laravel.log
```

---

## 📦 FILES INCLUDED

- `InspectionSubmitController.php` - Fixed calibration update logic
- `CALIBRATION_MASTER_UPDATE_TROUBLESHOOTING.md` - Debug guide
- `deploy_vps.sh` - Auto-deployment script

---

## ✅ SUCCESS CHECK

After deployment, you should see in logs:
```
=== UPDATE MASTER CALIBRATION STATUS ===
Checking calibration item: pressure_gauge
Updating master calibration for pressure_gauge
Successfully updated master calibration for pressure_gauge
```

---

## 🆘 EMERGENCY ROLLBACK

If something breaks:
```bash
cd /var/www/isotank-system/api
git checkout app/Http/Controllers/Api/Inspector/InspectionSubmitController.php
php artisan config:clear && php artisan cache:clear
systemctl restart php8.2-fpm
```

---

**VPS:** 202.10.44.146  
**User:** root  
**Package:** calibration_fix_deploy.zip  
**Date:** 2026-01-16
