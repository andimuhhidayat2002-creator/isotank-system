@echo off
echo ==========================================
echo      DEPLOYING DATA MIGRATION TOOLS
echo ==========================================

echo 1. Packing files...
cd api
tar -cf ../update_migration_tool.tar app/Http/Controllers/Web/Admin/ActivityUploadController.php app/Http/Controllers/Web/Admin/TemplateController.php app/Imports/VacuumImport.php resources/views/admin/activities.blade.php routes/web.php
cd ..

echo.
echo 2. Uploading...
set VPS_USER=root
set VPS_IP=202.10.44.146
set REMOTE_PATH=/var/www/isotank-system

scp update_migration_tool.tar %VPS_USER%@%VPS_IP%:%REMOTE_PATH%/

echo.
echo 3. Installing...
ssh %VPS_USER%@%VPS_IP% "cd %REMOTE_PATH% && tar -xf update_migration_tool.tar && rm update_migration_tool.tar"

echo.
echo ==========================================
echo        SUCCESS
echo ==========================================
echo Modul Migrasi Data Real sudah siap!
echo.
echo Silakan buka menu 'Activity Planner' di Web Admin.
echo Anda akan melihat opsi UPLOAD EXCEL untuk:
echo 1. Inspection (Inspeksi Masuk/Keluar)
echo 2. Maintenance (Riwayat Perbaikan)
echo 3. Calibration (Riwayat Kalibrasi)
echo 4. Vacuum (Riwayat Vakum) - BARU!
echo.
echo Gunakan tombol 'Download Template' untuk melihat format Excelnya.
pause
