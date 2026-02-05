$ServerIP = "202.10.44.146"
$User = "root"
$BasePath = "c:\laragon\www\isotank-system"
$RemotePath = "/var/www/isotank-system/api"

Write-Host "Starting Deployment..."

# 1. Controller
Write-Host "Uploading InspectionSubmitController.php..."
scp "$BasePath\api\app\Http\Controllers\Api\Inspector\InspectionSubmitController.php" "$User@$ServerIP`:$RemotePath/app/Http/Controllers/Api/Inspector/"

# 2. View: show.blade.php
Write-Host "Uploading show.blade.php..."
scp "$BasePath\api\resources\views\admin\isotanks\show.blade.php" "$User@$ServerIP`:$RemotePath/resources/views/admin/isotanks/"

# 3. View: latest_condition.blade.php
Write-Host "Uploading latest_condition.blade.php..."
scp "$BasePath\api\resources\views\admin\reports\latest_condition.blade.php" "$User@$ServerIP`:$RemotePath/resources/views/admin/reports/"

# 4. Clear Cache
Write-Host "Clearing Cache on Server..."
ssh "$User@$ServerIP" "cd $RemotePath && php artisan view:clear && php artisan cache:clear && systemctl restart php8.2-fpm"

Write-Host "Deployment Completed."
