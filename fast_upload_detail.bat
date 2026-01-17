@echo off
echo ==========================================
echo      DEPLOYING ISOTANK DETAIL PAGE
echo ==========================================

echo 1. Packing files...
cd api
tar -cf ../update_isotank_detail.tar app/Http/Controllers/Web/Admin/AdminController.php resources/views/admin/isotanks/show.blade.php routes/web.php resources/views/admin/isotanks.blade.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_isotank_detail.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_isotank_detail.tar && rm update_isotank_detail.tar"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Halaman Detail Isotank (Passport) sudah aktif!
echo Anda bisa klik Nomor ISO di menu Master Isotanks untuk melihat:
echo - History Maintenance dan Inspeksi
echo - Log Vakum dan Kalibrasi
echo - Status Sertifikat dan Kondisi Terkini
pause
