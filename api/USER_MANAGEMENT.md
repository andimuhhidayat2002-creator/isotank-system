# User Management Feature

## Overview
Fitur User Management memungkinkan admin untuk mengelola akun pengguna dan role mereka di sistem Isotank.

## Fitur yang Tersedia

### 1. **Melihat Daftar User**
- Menampilkan semua user dengan informasi:
  - ID
  - Nama
  - Email
  - Role (dengan badge berwarna)
  - Tanggal dibuat
- Dilengkapi dengan DataTables untuk search, sort, dan pagination

### 2. **Menambah User Baru**
- Form untuk membuat user baru dengan field:
  - Name (required)
  - Email (required, unique)
  - Password (required, min 6 karakter)
  - Role (required): admin, inspector, maintenance, management, receiver

### 3. **Edit User**
- Mengubah informasi user:
  - Name
  - Email
  - Role (kecuali untuk akun sendiri)

### 4. **Ubah Role**
- Quick action untuk mengubah role user
- Tidak bisa mengubah role akun sendiri (untuk keamanan)
- Role yang tersedia:
  - **Admin**: Full access ke semua fitur
  - **Inspector**: Akses untuk melakukan inspeksi
  - **Maintenance**: Akses untuk maintenance jobs
  - **Management**: Akses view reports
  - **Receiver**: Akses untuk konfirmasi penerimaan isotank

### 5. **Reset Password**
- Admin bisa reset password user lain
- Memerlukan konfirmasi password
- Minimum 6 karakter

### 6. **Hapus User**
- Menghapus user dari sistem
- Tidak bisa menghapus akun sendiri
- Validasi: tidak bisa hapus user yang memiliki inspection logs atau maintenance jobs
- Memerlukan konfirmasi

## Akses
- **URL**: `/admin/users`
- **Role Required**: Admin only
- **Menu Location**: Sidebar → Users (hanya terlihat untuk admin)

## Routes
```
GET     /admin/users                    - Tampilkan daftar user
POST    /admin/users                    - Buat user baru
PUT     /admin/users/{id}               - Update user
PATCH   /admin/users/{id}/role          - Update role user
PATCH   /admin/users/{id}/password      - Reset password user
DELETE  /admin/users/{id}               - Hapus user
```

## Security Features
1. **Self-protection**: Admin tidak bisa mengubah role atau menghapus akun sendiri
2. **Data integrity**: Tidak bisa hapus user yang memiliki records terkait
3. **Password hashing**: Password di-hash menggunakan bcrypt
4. **Role-based access**: Hanya admin yang bisa akses fitur ini

## Cara Menggunakan

### Menambah User Baru
1. Klik tombol "Add New User"
2. Isi form dengan data user
3. Pilih role yang sesuai
4. Klik "Create User"

### Mengubah Role User
1. Klik icon badge (kuning) pada user yang ingin diubah
2. Pilih role baru dari dropdown
3. Klik "Change Role"

### Reset Password
1. Klik icon key (biru) pada user
2. Masukkan password baru dan konfirmasi
3. Klik "Reset Password"

### Hapus User
1. Klik icon trash (merah) pada user
2. Konfirmasi penghapusan
3. Klik "Delete User"

## Badge Colors
- **Admin**: Red (bg-danger)
- **Inspector**: Blue (bg-primary)
- **Maintenance**: Yellow (bg-warning)
- **Management**: Green (bg-success)
- **Receiver**: Cyan (bg-info)

## Files Created/Modified
1. `app/Http/Controllers/Web/Admin/UserManagementController.php` - Controller
2. `resources/views/admin/users/index.blade.php` - View
3. `routes/web.php` - Routes
4. `resources/views/layouts/app.blade.php` - Navigation menu
5. `app/Models/User.php` - Added relationships
