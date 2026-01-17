# ========================================== 
# DEPLOYMENT INSTRUCTIONS - STEP BY STEP
# ==========================================

## STEP 1: UPLOAD FILE

Silakan jalankan command ini di PowerShell atau Command Prompt:

```cmd
cd c:\laragon\www\isotank-system
scp calibration_fix_deploy.zip root@202.10.44.146:/tmp/
```

**Anda akan diminta password VPS.**
Masukkan password, lalu tunggu upload selesai.

---

## STEP 2: SSH TO VPS

Setelah upload berhasil, jalankan:

```cmd
ssh root@202.10.44.146
```

**Masukkan password VPS lagi.**

---

## STEP 3: DEPLOY (COPY-PASTE COMMAND INI)

Setelah masuk ke VPS, copy-paste command ini (SEMUA SEKALIGUS):

```bash
cd /tmp && unzip -o calibration_fix_deploy.zip && cp -f api/app/Http/Controllers/Api/Inspector/InspectionSubmitController.php /var/www/isotank-system/api/app/Http/Controllers/Api/Inspector/ && cp -f api/docs/CALIBRATION_MASTER_UPDATE_TROUBLESHOOTING.md /var/www/isotank-system/api/docs/ && cp -f api/deploy_vps.sh /var/www/isotank-system/api/ && chmod +x /var/www/isotank-system/api/deploy_vps.sh && /var/www/isotank-system/api/deploy_vps.sh && rm -f /tmp/calibration_fix_deploy.zip && rm -rf /tmp/api && echo "✅ DEPLOYMENT COMPLETED!"
```

Tunggu sampai muncul: **✅ DEPLOYMENT COMPLETED!**

---

## STEP 4: VERIFY

Masih di SSH VPS, jalankan:

```bash
grep "UPDATE MASTER CALIBRATION STATUS" /var/www/isotank-system/api/app/Http/Controllers/Api/Inspector/InspectionSubmitController.php
```

**Expected output:**
```
\Log::info("=== UPDATE MASTER CALIBRATION STATUS ===", [
```

Jika muncul, berarti **BERHASIL!** ✅

---

## STEP 5: MONITOR LOGS

```bash
tail -f /var/www/isotank-system/api/storage/logs/laravel.log
```

Biarkan terminal ini terbuka, lalu test inspection dari Flutter app.

---

## TROUBLESHOOTING

### Jika SCP tidak tersedia:

**Gunakan WinSCP (RECOMMENDED):**

1. Download: https://winscp.net/eng/download.php
2. Install dan buka WinSCP
3. New Site:
   - File protocol: SFTP
   - Host name: 202.10.44.146
   - Port: 22
   - User name: root
   - Password: [your VPS password]
4. Click "Login"
5. Drag & drop `calibration_fix_deploy.zip` dari kiri (local) ke kanan (VPS `/tmp/`)
6. Lanjut ke STEP 2 (SSH to VPS)

---

## QUICK COMMANDS REFERENCE

**Upload:**
```cmd
scp calibration_fix_deploy.zip root@202.10.44.146:/tmp/
```

**SSH:**
```cmd
ssh root@202.10.44.146
```

**Deploy (on VPS):**
```bash
cd /tmp && unzip -o calibration_fix_deploy.zip && cp -f api/app/Http/Controllers/Api/Inspector/InspectionSubmitController.php /var/www/isotank-system/api/app/Http/Controllers/Api/Inspector/ && cp -f api/docs/CALIBRATION_MASTER_UPDATE_TROUBLESHOOTING.md /var/www/isotank-system/api/docs/ && cp -f api/deploy_vps.sh /var/www/isotank-system/api/ && chmod +x /var/www/isotank-system/api/deploy_vps.sh && /var/www/isotank-system/api/deploy_vps.sh && rm -f /tmp/calibration_fix_deploy.zip && rm -rf /tmp/api && echo "✅ DEPLOYMENT COMPLETED!"
```

**Verify:**
```bash
grep "UPDATE MASTER CALIBRATION STATUS" /var/www/isotank-system/api/app/Http/Controllers/Api/Inspector/InspectionSubmitController.php
```

**Monitor:**
```bash
tail -f /var/www/isotank-system/api/storage/logs/laravel.log
```

---

**File Location:** `c:\laragon\www\isotank-system\calibration_fix_deploy.zip`  
**VPS:** 202.10.44.146  
**User:** root  
**Date:** 2026-01-16
