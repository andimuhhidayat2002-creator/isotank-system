# PANDUAN IMPLEMENTASI PERBAIKAN KEAMANAN

## Daftar Isi
1. [Perbaikan Prioritas 1 (CRITICAL)](#prioritas-1-critical)
2. [Perbaikan Prioritas 2 (HIGH)](#prioritas-2-high)
3. [Testing & Verification](#testing--verification)
4. [Rollback Plan](#rollback-plan)

---

## Prioritas 1 (CRITICAL)

### 1.1 Automated Fixes (Via Script)

**File:** `security_fixes_p1.sh`

**Cara Menjalankan:**
```bash
# Di VPS Production
cd /var/www/isotank-system
sudo bash security_fixes_p1.sh
```

**Yang Dilakukan Script:**
- ✅ Set `APP_DEBUG=false`
- ✅ Set `APP_ENV=production`
- ✅ Tambah rate limiting pada login endpoint
- ✅ Extend session lifetime ke 8 jam
- ✅ Clear all caches
- ✅ Restart services

---

### 1.2 Manual Fixes (WAJIB!)

#### A. Ubah Semua Password Default

**Langkah:**
1. Login ke web admin: https://kayanconect.com/admin
2. Masuk ke menu **User Management**
3. Untuk setiap user, klik tombol **Reset Password**
4. Gunakan password yang kuat (min 12 karakter)

**Password Requirements:**
- Minimal 12 karakter
- Kombinasi huruf besar & kecil
- Minimal 1 angka
- Minimal 1 simbol (@, !, #, $, dll)

**Contoh Password Kuat:**
```
Admin2026!Isotank
Inspect#2026Kayan
Maint@Isotank2026
```

**User yang Harus Diubah:**
- [ ] admin@isotank.com
- [ ] inspector@isotank.com
- [ ] maintenance@isotank.com
- [ ] management@isotank.com
- [ ] driver@isotank.com
- [ ] receiver@isotank.com
- [ ] yard@isotank.com

---

#### B. Tambah File Upload Size Validation

**File:** `api/app/Http/Controllers/Api/Inspector/InspectionSubmitController.php`

**Tambahkan di method `submit()`:**
```php
// Setelah line validation rules yang ada
$rules = [
    // ... existing rules ...
    
    // Add photo validation
    'photo_*' => 'nullable|image|mimes:jpeg,png,jpg|max:5120', // Max 5MB
];
```

**File:** `api/app/Http/Controllers/Api/Maintenance/MaintenanceJobController.php`

**Tambahkan di method `updateStatus()`:**
```php
$rules = [
    // ... existing rules ...
    
    'photo_during' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
    'after_photo' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
];
```

---

## Prioritas 2 (HIGH)

### 2.1 Implementasi Account Lockout & Login Tracking

#### Step 1: Run Migration

```bash
cd /var/www/isotank-system/api
php artisan migrate
```

**Migration yang dijalankan:**
- Membuat tabel `login_attempts`
- Menambah kolom di tabel `users`:
  - `failed_login_attempts`
  - `locked_until`
  - `force_password_change`
  - `password_changed_at`

---

#### Step 2: Register Middleware

**File:** `api/bootstrap/app.php`

**Tambahkan:**
```php
use App\Http\Middleware\LoginSecurityMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Add login security middleware
        $middleware->alias([
            'login.security' => LoginSecurityMiddleware::class,
        ]);
    })
    // ... rest of config
```

---

#### Step 3: Apply Middleware to Login Route

**File:** `api/routes/api.php`

**Update login route:**
```php
// Before
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1');

// After
Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['throttle:5,1', 'login.security']);
```

---

### 2.2 Implementasi Password Complexity Validation

**File:** `api/app/Http/Controllers/Web/Admin/UserManagementController.php`

**Update method `store()` dan `resetPassword()`:**
```php
public function resetPassword(Request $request, $id)
{
    $request->validate([
        'password' => [
            'required',
            'min:12',
            'confirmed',
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
        ],
    ], [
        'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character (@$!%*?&)',
        'password.min' => 'Password must be at least 12 characters long',
    ]);
    
    $user = User::findOrFail($id);
    $user->update([
        'password' => Hash::make($request->password),
        'password_changed_at' => now(),
        'force_password_change' => false,
    ]);
    
    return redirect()->back()->with('success', 'Password updated successfully');
}
```

---

### 2.3 Update Frontend Validation

**File:** `api/resources/views/admin/users/index.blade.php`

**Update password input:**
```html
<!-- Before -->
<input type="password" class="form-control" name="password" required minlength="6">

<!-- After -->
<input type="password" class="form-control" name="password" required minlength="12" 
       pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$">
<small class="form-text text-muted">
    Password harus minimal 12 karakter dan mengandung: huruf besar, huruf kecil, angka, dan simbol (@$!%*?&)
</small>
```

---

### 2.4 Tambah Security Headers di Nginx

**File:** `/etc/nginx/sites-available/isotank` (di VPS)

**Tambahkan di dalam block `server`:**
```nginx
server {
    # ... existing config ...
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
    
    # HSTS (uncomment after testing)
    # add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    
    # ... rest of config ...
}
```

**Restart Nginx:**
```bash
sudo nginx -t
sudo systemctl restart nginx
```

---

## Testing & Verification

### Test 1: Rate Limiting
```bash
# Test dari terminal (ganti dengan email yang valid)
for i in {1..7}; do
  curl -X POST https://kayanconect.com/api/login \
    -H "Content-Type: application/json" \
    -d '{"email":"test@test.com","password":"wrong"}' \
    -w "\nAttempt $i: HTTP %{http_code}\n"
  sleep 1
done
```

**Expected Result:**
- Attempt 1-5: HTTP 401 (Unauthorized)
- Attempt 6+: HTTP 429 (Too Many Requests)

---

### Test 2: Account Lockout
1. Login dengan password salah 5 kali
2. Cek database:
```sql
SELECT email, failed_login_attempts, locked_until 
FROM users 
WHERE email = 'test@isotank.com';
```
3. Coba login lagi, harus dapat error "Account is locked"

---

### Test 3: Password Complexity
1. Coba reset password dengan password lemah: "123456"
2. Harus ditolak dengan error message
3. Coba dengan password kuat: "Admin2026!Isotank"
4. Harus berhasil

---

### Test 4: Session Timeout
1. Login ke web admin
2. Tunggu 2 jam (atau set SESSION_LIFETIME=2 untuk testing)
3. Coba akses halaman lain
4. Harus masih login (tidak logout)

---

### Test 5: File Upload Size
1. Coba upload foto > 5MB di inspection form
2. Harus ditolak dengan error message
3. Upload foto < 5MB harus berhasil

---

### Test 6: Security Headers
```bash
curl -I https://kayanconect.com
```

**Expected Headers:**
```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
```

---

## Rollback Plan

### Jika Terjadi Masalah

#### Rollback Environment Settings
```bash
cd /var/www/isotank-system/api

# Restore backup .env
cp .env.backup.YYYYMMDD_HHMMSS .env

# Clear cache
php artisan config:clear
php artisan cache:clear

# Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

---

#### Rollback Migration
```bash
cd /var/www/isotank-system/api
php artisan migrate:rollback --step=1
```

---

#### Rollback Routes
```bash
cd /var/www/isotank-system/api
cp routes/api.php.backup.YYYYMMDD_HHMMSS routes/api.php
php artisan route:clear
```

---

#### Rollback Nginx Config
```bash
sudo cp /etc/nginx/sites-available/isotank.backup /etc/nginx/sites-available/isotank
sudo nginx -t
sudo systemctl restart nginx
```

---

## Monitoring Post-Implementation

### Daily Checks (Minggu Pertama)

**1. Check Login Attempts:**
```sql
SELECT 
    DATE(attempted_at) as date,
    COUNT(*) as total_attempts,
    SUM(CASE WHEN successful = 1 THEN 1 ELSE 0 END) as successful,
    SUM(CASE WHEN successful = 0 THEN 1 ELSE 0 END) as failed
FROM login_attempts
WHERE attempted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(attempted_at)
ORDER BY date DESC;
```

**2. Check Locked Accounts:**
```sql
SELECT email, failed_login_attempts, locked_until
FROM users
WHERE locked_until IS NOT NULL AND locked_until > NOW();
```

**3. Check Error Logs:**
```bash
tail -f /var/www/isotank-system/api/storage/logs/laravel.log
```

**4. Check Nginx Logs:**
```bash
tail -f /var/log/nginx/error.log
```

---

## Checklist Implementasi

### Pre-Implementation
- [ ] Backup database
- [ ] Backup .env file
- [ ] Backup routes files
- [ ] Backup nginx config
- [ ] Notify team about maintenance window

### Implementation
- [ ] Run `security_fixes_p1.sh`
- [ ] Change all default passwords
- [ ] Add file upload validation
- [ ] Run migration
- [ ] Register middleware
- [ ] Update routes
- [ ] Update password validation
- [ ] Update frontend validation
- [ ] Add nginx security headers

### Post-Implementation
- [ ] Test rate limiting
- [ ] Test account lockout
- [ ] Test password complexity
- [ ] Test session timeout
- [ ] Test file upload size
- [ ] Verify security headers
- [ ] Monitor logs for 24 hours
- [ ] Document any issues

### Communication
- [ ] Notify users about password change requirement
- [ ] Update user documentation
- [ ] Train admin on new security features
- [ ] Schedule security review in 3 months

---

## Support & Troubleshooting

### Common Issues

**Issue 1: "Too Many Attempts" Error**
- **Cause:** Rate limiting triggered
- **Solution:** Wait 1 minute or clear rate limit cache:
```bash
php artisan cache:clear
```

**Issue 2: Account Locked Unexpectedly**
- **Cause:** 5 failed login attempts
- **Solution:** Admin can unlock via database:
```sql
UPDATE users 
SET failed_login_attempts = 0, locked_until = NULL 
WHERE email = 'user@example.com';
```

**Issue 3: Password Validation Too Strict**
- **Cause:** Users tidak familiar dengan requirement
- **Solution:** Provide password generator atau examples

---

## Next Steps (Prioritas 3 & 4)

Setelah Prioritas 1 & 2 selesai, lanjutkan dengan:

1. **Two-Factor Authentication (2FA)**
   - Implementasi TOTP (Google Authenticator)
   - Wajib untuk role admin

2. **Encrypted Offline Database**
   - Implementasi SQLCipher di Flutter app
   - Encrypt sensitive fields

3. **API Versioning**
   - Migrate ke `/api/v1/`
   - Maintain backward compatibility

4. **Automated Backup**
   - Setup daily backup dengan cron
   - Test restore procedure monthly

---

**Prepared by:** Antigravity AI Security Agent  
**Last Updated:** 7 Februari 2026  
**Version:** 1.0
