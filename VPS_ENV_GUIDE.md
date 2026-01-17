# Panduan Konfigurasi Email (.env) di VPS

Berikut adalah langkah-langkah untuk mengatur akun pengirim email di server VPS Anda.

## 1. Login ke VPS
Buka terminal/CMD di laptop Anda dan jalankan:
```bash
ssh root@202.10.44.146
```
*(Masukkan password saat diminta)*

## 2. Masuk ke Folder Project
Pindah ke direktori dimana file Laravel berada:
```bash
cd /var/www/isotank-system/api
```

## 3. Edit File .env
Gunakan text editor `nano` untuk membuka file konfigurasi:
```bash
nano .env
```

## 4. Cari Bagian Konfigurasi Mail
Tekan panah bawah pada keyboard untuk scroll ke bawah sampai menemukan bagian yang dimulai dengan `MAIL_`. Ubah nilainya sesuai penyedia email Anda.

**Contoh jika menggunakan Gmail:**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=email.anda@gmail.com
MAIL_PASSWORD=password_aplikasi_anda   <-- BUKAN password login gmail biasa!
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="admin@ptkayan.com"
MAIL_FROM_NAME="Sistem Isotank Kayan"
```

*Catatan: Password Gmail harus menggunakan "App Password" (Password Aplikasi) yang bisa dibuat di pengaturan Akun Google > Security > 2-Step Verification > App passwords.*

**Contoh jika menggunakan Hosting Biasa (cPanel/Titan/Webmail):**
```env
MAIL_MAILER=smtp
MAIL_HOST=mail.ptkayan.com
MAIL_PORT=465
MAIL_USERNAME=no-reply@ptkayan.com
MAIL_PASSWORD=rahasia123
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="no-reply@ptkayan.com"
MAIL_FROM_NAME="Sistem Isotank Kayan"
```

## 5. Simpan Perubahan
1.  Tekan `Ctrl + O` (Huruf O) lalu `Enter` untuk menyimpan.
2.  Tekan `Ctrl + X` untuk keluar dari editor nano.

## 6. Clear Cache (PENTING!)
Agar perubahan terbaca oleh Laravel, Anda **WAJIB** menjalankan perintah ini setelah mengedit `.env`:

```bash
php artisan config:cache
```

---
**Selesai!** Sekarang cobalah kirim Daily Report lagi dari dashboard, email akan dikirim menggunakan akun yang baru saja Anda setting.
