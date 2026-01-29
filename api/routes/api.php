<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\MasterIsotankController;
use App\Http\Controllers\Api\Admin\BulkInspectionUploadController;
use App\Http\Controllers\Api\Admin\BulkCalibrationUploadController;
use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Inspector\InspectionJobController;
use App\Http\Controllers\Api\Inspector\InspectionSubmitController;
use App\Http\Controllers\Api\Maintenance\MaintenanceJobController;
use App\Http\Controllers\Api\Maintenance\VacuumSuctionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/me/signature', [AuthController::class, 'uploadSignature']);
    
    // Admin only routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        Route::post('/register', [AuthController::class, 'register']);
        
        // Master Isotank Management
        Route::apiResource('isotanks', MasterIsotankController::class);
        Route::post('/isotanks/{id}/activate', [MasterIsotankController::class, 'activate']);
        Route::post('/isotanks/{id}/deactivate', [MasterIsotankController::class, 'deactivate']);
        Route::get('/isotanks-active', [MasterIsotankController::class, 'active']);
        
        // Bulk Upload Routes
        Route::post('/bulk/inspection', [BulkInspectionUploadController::class, 'upload']);
        Route::get('/bulk/inspection/history', [BulkInspectionUploadController::class, 'history']);
        
        Route::post('/bulk/calibration', [BulkCalibrationUploadController::class, 'upload']);
        Route::get('/bulk/calibration/history', [BulkCalibrationUploadController::class, 'history']);

        // Yard Layout Management (Upload only)
        Route::post('/yard/layout/upload', [\App\Http\Controllers\Api\Admin\YardLayoutController::class, 'upload']);
        
        // Maintenance Management
        Route::get('/maintenance', [\App\Http\Controllers\Api\Admin\AdminMaintenanceController::class, 'index']);
        Route::post('/maintenance', [\App\Http\Controllers\Api\Admin\AdminMaintenanceController::class, 'store']);
        Route::get('/maintenance/{id}', [\App\Http\Controllers\Api\Admin\AdminMaintenanceController::class, 'show']);
        Route::put('/maintenance/{id}', [\App\Http\Controllers\Api\Admin\AdminMaintenanceController::class, 'update']);
        Route::put('/maintenance/{id}/status', [\App\Http\Controllers\Api\Admin\AdminMaintenanceController::class, 'updateStatus']);
        
        // Calibration Management (Master Data)
        Route::get('/calibration', [\App\Http\Controllers\Api\Admin\CalibrationController::class, 'index']);
        Route::get('/calibration/{id}', [\App\Http\Controllers\Api\Admin\CalibrationController::class, 'show']);
        Route::post('/calibration/{id}/init', [\App\Http\Controllers\Api\Admin\CalibrationController::class, 'initialize']);
        Route::post('/calibration/{id}/update', [\App\Http\Controllers\Api\Admin\CalibrationController::class, 'bulkUpdate']);
    });
    
    // Inspector routes
    // Inspector & Receiver routes
    // Inspector, Receiver & Maintenance routes (Shared access to job details if needed)
    Route::middleware('role:inspector,receiver,maintenance')->prefix('inspector')->group(function () {
        // Inspection Jobs (List & Details)
        Route::get('/jobs', [InspectionJobController::class, 'index']);
        Route::get('/jobs/{id}', [InspectionJobController::class, 'show']);
        
        // Receiver confirmation
        Route::get('/jobs/{id}/receiver-details', [InspectionSubmitController::class, 'getInspectionForReceiver']);
        Route::post('/jobs/{id}/receiver-confirm', [InspectionSubmitController::class, 'receiverConfirm']);
        
        // PDF Upload (for both inspector and receiver)
        Route::post('/jobs/{id}/upload-pdf', [InspectionSubmitController::class, 'uploadPdf']);
    });
    
    // Inspector ONLY routes
    // Inspector ONLY routes (Maintenance can also submit/update if required by business logic, otherwise keep strict)
    Route::middleware('role:inspector,maintenance')->prefix('inspector')->group(function () {
        Route::get('/jobs/isotank/{isotankId}/history', [InspectionJobController::class, 'history']);
        Route::post('/jobs/{id}/submit', [InspectionSubmitController::class, 'submit']);
    });
    
    // Maintenance routes
    Route::middleware('role:maintenance')->prefix('maintenance')->group(function () {
        Route::get('/jobs', [MaintenanceJobController::class, 'index']);
        Route::get('/jobs/{id}', [MaintenanceJobController::class, 'show']);
        Route::put('/jobs/{id}/status', [MaintenanceJobController::class, 'updateStatus']);
        Route::get('/jobs/isotank/{isotankId}/history', [MaintenanceJobController::class, 'history']);
        
        // Vacuum Suction
        Route::get('/vacuum-activities', [VacuumSuctionController::class, 'index']);
        Route::get('/vacuum-activities/{id}', [VacuumSuctionController::class, 'show']);
        Route::put('/vacuum-activities/{id}', [VacuumSuctionController::class, 'update']);
        
        // Calibration
        Route::get('/calibration', [\App\Http\Controllers\Api\Maintenance\CalibrationController::class, 'index']);
        Route::get('/calibration/{id}', [\App\Http\Controllers\Api\Maintenance\CalibrationController::class, 'show']);
        Route::put('/calibration/{id}', [\App\Http\Controllers\Api\Maintenance\CalibrationController::class, 'update']);
    });
    
    // Management (read-only) routes
    Route::middleware('role:management')->prefix('management')->group(function () {
        // Dashboard and reporting routes
        Route::get('/isotanks', [MasterIsotankController::class, 'index']);
        Route::get('/isotanks/{id}', [MasterIsotankController::class, 'show']);
    });
    
    // Shared routes (multiple roles)
    Route::middleware('role:admin,inspector,maintenance,management')->group(function () {
        // Common isotank list
        Route::get('/isotanks', [MasterIsotankController::class, 'index']);
        Route::get('/isotanks/{id}', [MasterIsotankController::class, 'show']);
        
        // Inspection Items
        Route::get('/inspection-items', [\App\Http\Controllers\Api\InspectionItemApiController::class, 'index']);

        // Filling Status
        Route::get('/filling-statuses', [\App\Http\Controllers\Api\FillingStatusController::class, 'index']);
        Route::get('/filling-statuses/statistics', [\App\Http\Controllers\Api\FillingStatusController::class, 'statistics']);

        // Yard Views
        Route::get('/yard/layout', [\App\Http\Controllers\Api\Admin\YardLayoutController::class, 'index']);
        Route::get('/yard/positions', [\App\Http\Controllers\Api\Admin\YardLayoutController::class, 'positions']);
    });
});
