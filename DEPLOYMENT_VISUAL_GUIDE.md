# 🚀 DEPLOYMENT GUIDE - VISUAL STEP BY STEP

## 📦 Package Ready!

File yang sudah disiapkan:
- ✅ `calibration_fix_deploy.zip` - Package untuk upload ke VPS
- ✅ `deploy_interactive.bat` - Script bantuan deployment
- ✅ `vps_deploy_commands.sh` - Commands untuk dijalankan di VPS

---

## 🎯 DEPLOYMENT STEPS

### STEP 1: Upload File ke VPS

#### Option A: Menggunakan WinSCP (RECOMMENDED)

1. **Download WinSCP** (jika belum punya): https://winscp.net/

2. **Buka WinSCP** dan buat koneksi baru:
   ```
   File Protocol: SFTP
   Host name: 202.10.44.146
   Port: 22
   User name: root
   Password: [your VPS password]
   ```

3. **Connect** dan tunggu sampai terhubung

4. **Upload file:**
   - Di panel kiri (local): Navigate ke `c:\laragon\www\isotank-system\`
   - Di panel kanan (VPS): Navigate ke `/tmp/`
   - Drag & drop file `calibration_fix_deploy.zip` dari kiri ke kanan

5. **Verify upload:**
   - Pastikan file `calibration_fix_deploy.zip` muncul di `/tmp/` di VPS

---

#### Option B: Menggunakan FileZilla

1. **Download FileZilla** (jika belum punya): https://filezilla-project.org/

2. **Buka FileZilla** dan connect:
   ```
   Host: sftp://202.10.44.146
   Username: root
   Password: [your VPS password]
   Port: 22
   ```

3. **Upload file:**
   - Local site: `c:\laragon\www\isotank-system\`
   - Remote site: `/tmp/`
   - Drag `calibration_fix_deploy.zip` ke remote site

---

#### Option C: Menggunakan Command Line (Advanced)

```bash
# Jika punya SSH client
scp "c:\laragon\www\isotank-system\calibration_fix_deploy.zip" root@202.10.44.146:/tmp/
```

---

### STEP 2: SSH ke VPS dan Deploy

#### Option A: Menggunakan PuTTY

1. **Download PuTTY** (jika belum punya): https://www.putty.org/

2. **Buka PuTTY** dan connect:
   ```
   Host Name: 202.10.44.146
   Port: 22
   Connection type: SSH
   ```

3. **Login:**
   ```
   login as: root
   password: [your VPS password]
   ```

4. **Copy-paste commands ini satu per satu:**

```bash
# Navigate to tmp
cd /tmp

# Extract files
unzip -o calibration_fix_deploy.zip

# Copy to production
cp -f api/app/Http/Controllers/Api/Inspector/InspectionSubmitController.php /var/www/isotank-system/api/app/Http/Controllers/Api/Inspector/
cp -f api/docs/CALIBRATION_MASTER_UPDATE_TROUBLESHOOTING.md /var/www/isotank-system/api/docs/
cp -f api/deploy_vps.sh /var/www/isotank-system/api/

# Make executable
chmod +x /var/www/isotank-system/api/deploy_vps.sh

# Run deployment
/var/www/isotank-system/api/deploy_vps.sh

# Clean up
rm -f /tmp/calibration_fix_deploy.zip
rm -rf /tmp/api
```

---

#### Option B: Menggunakan Windows Terminal / PowerShell

```powershell
# Open PowerShell and run:
ssh root@202.10.44.146

# Then paste the commands above
```

---

### STEP 3: Verify Deployment

Setelah deployment selesai, verify dengan commands ini:

```bash
# Check if file updated
grep "UPDATE MASTER CALIBRATION STATUS" /var/www/isotank-system/api/app/Http/Controllers/Api/Inspector/InspectionSubmitController.php

# Should output: \Log::info("=== UPDATE MASTER CALIBRATION STATUS ===", [
```

Jika muncul output seperti di atas, berarti file berhasil di-update! ✅

---

### STEP 4: Monitor Logs

```bash
# Watch Laravel logs in real-time
tail -f /var/www/isotank-system/api/storage/logs/laravel.log
```

Biarkan terminal ini terbuka, lalu:
1. Buka Flutter app
2. Submit inspection baru dengan data kalibrasi
3. Lihat log yang muncul di terminal

**Expected log output:**
```
[2026-01-16 21:15:00] local.INFO: === UPDATE MASTER CALIBRATION STATUS ===
[2026-01-16 21:15:00] local.INFO: Checking calibration item: pressure_gauge
[2026-01-16 21:15:00] local.INFO: Updating master calibration for pressure_gauge
[2026-01-16 21:15:00] local.INFO: Successfully updated master calibration for pressure_gauge
```

---

### STEP 5: Verify Database

```bash
# Connect to MySQL
mysql -u isotank_user -p isotank_db

# Enter password when prompted
```

```sql
-- Check master calibration status
SELECT 
    isotank_id, 
    item_name, 
    serial_number, 
    calibration_date, 
    valid_until,
    updated_at
FROM master_isotank_calibration_status
ORDER BY updated_at DESC
LIMIT 10;

-- Exit MySQL
EXIT;
```

---

## ✅ SUCCESS INDICATORS

Deployment berhasil jika:

1. ✅ File upload sukses (terlihat di WinSCP/FileZilla)
2. ✅ Commands di SSH berjalan tanpa error
3. ✅ `grep` command menunjukkan log line baru
4. ✅ Services restart sukses (PHP-FPM & Nginx)
5. ✅ Log menunjukkan "UPDATE MASTER CALIBRATION STATUS"
6. ✅ Database ter-update setelah inspection submission

---

## ❌ TROUBLESHOOTING

### Error: "unzip: command not found"

```bash
# Install unzip
apt update && apt install -y unzip
```

### Error: "Permission denied"

```bash
# Fix permissions
chown -R www-data:www-data /var/www/isotank-system
chmod -R 775 /var/www/isotank-system
```

### Error: "Cannot connect to VPS"

1. Check VPS IP: `202.10.44.146`
2. Check firewall allows SSH (port 22)
3. Verify VPS is running
4. Check credentials (username: root)

### Error: "File not found"

```bash
# Verify file exists
ls -la /tmp/calibration_fix_deploy.zip

# If not exists, re-upload using WinSCP/FileZilla
```

---

## 📞 NEED HELP?

Jika ada error atau masalah:

1. **Screenshot error message**
2. **Copy log output:**
   ```bash
   tail -n 50 /var/www/isotank-system/api/storage/logs/laravel.log
   ```
3. **Share dengan saya** untuk troubleshooting

---

## 🎉 AFTER DEPLOYMENT

1. **Test inspection submission** dari Flutter app
2. **Monitor logs** untuk melihat proses update
3. **Check database** untuk verify data masuk
4. **Report hasil** ke saya jika ada issue

---

**Deployment Package:** `calibration_fix_deploy.zip`  
**Location:** `c:\laragon\www\isotank-system\`  
**Size:** ~50 KB  
**VPS Target:** `202.10.44.146`  
**Deployment Date:** 2026-01-16
