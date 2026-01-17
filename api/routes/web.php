<?php

use App\Http\Controllers\Web\Admin\AdminController;
use App\Http\Controllers\Web\Admin\InspectionItemController;
use App\Http\Controllers\Web\Admin\UserManagementController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\Admin\YardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Access: Admin (Web)
|
*/

// Redirect root to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin Protected Routes
Route::middleware(['auth:web', 'role:admin,management,yard_operator'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/dashboard/location/{location}', [AdminController::class, 'locationDetail'])->name('dashboard.location');
    
    // Global Statistics Modules
    Route::get('/dashboard/maintenance', [AdminController::class, 'maintenanceStatistics'])->name('dashboard.maintenance');
    Route::get('/dashboard/vacuum', [AdminController::class, 'vacuumMonitoring'])->name('dashboard.vacuum');
    Route::get('/dashboard/calibration', [AdminController::class, 'calibrationMonitoring'])->name('dashboard.calibration');
    // New Export Route for Alerts - Renamed to fix cache issue
    Route::get('/dashboard/calibration/export-alerts-csv', [AdminController::class, 'exportCalibrationAlerts'])->name('calibration.export_csv');
    
    Route::get('/dashboard/inspection', [AdminController::class, 'inspectionPerformance'])->name('dashboard.inspection');
    Route::get('/dashboard/outgoing-quality', [AdminController::class, 'outgoingQuality'])->name('dashboard.outgoing');
    
    // Reports
    Route::prefix('reports')->name('reports.')->group(function() {
        Route::get('/', [\App\Http\Controllers\Web\Admin\ReportController::class, 'index'])->name('index');
        Route::post('/send', [\App\Http\Controllers\Web\Admin\ReportController::class, 'sendUnified'])->name('send_unified');
        Route::get('/weekly/preview', [\App\Http\Controllers\Web\Admin\ReportController::class, 'previewWeekly'])->name('weekly.preview');
        // Assume daily preview exists in AdminController as per previous code, linking it here for consistency if needed
        Route::get('/daily/preview', [AdminController::class, 'previewDailyReport'])->name('daily.preview');
    });
    Route::prefix('calibration-master')->name('calibration-master.')->group(function() {
        Route::get('/', [\App\Http\Controllers\Web\Admin\CalibrationMasterController::class, 'index'])->name('index');
        Route::get('/export', [\App\Http\Controllers\Web\Admin\CalibrationMasterController::class, 'export'])->name('export');
        Route::get('/{id}', [\App\Http\Controllers\Web\Admin\CalibrationMasterController::class, 'show'])->name('show');
        Route::post('/{id}/init', [\App\Http\Controllers\Web\Admin\CalibrationMasterController::class, 'initialize'])->name('init');
        Route::post('/{id}/update', [\App\Http\Controllers\Web\Admin\CalibrationMasterController::class, 'batchUpdate'])->name('update');
        Route::post('/import', [\App\Http\Controllers\Web\Admin\CalibrationMasterController::class, 'import'])->name('import');
    });

    // Isotank Management
    Route::get('/isotanks', [AdminController::class, 'isotanks'])->name('isotanks.index');
    Route::get('/isotanks/template', [App\Http\Controllers\Web\Admin\IsotankUploadController::class, 'downloadTemplate'])->name('isotanks.template')->middleware('role:admin');
    Route::get('/isotanks/{id}', [AdminController::class, 'showIsotank'])->name('isotanks.show');
    Route::post('/isotanks', [AdminController::class, 'storeIsotank'])->name('isotanks.store')->middleware('role:admin');
    Route::post('/isotanks/upload', [App\Http\Controllers\Web\Admin\IsotankUploadController::class, 'store'])->name('isotanks.upload')->middleware('role:admin');
    Route::post('/isotanks/{id}/toggle-status', [AdminController::class, 'toggleIsotankStatus'])->name('isotanks.toggle')->middleware('role:admin');
    Route::post('/isotanks/{id}/survey', [AdminController::class, 'storeClassSurvey'])->name('isotanks.survey.store')->middleware('role:admin');
    Route::post('/isotanks/bulk-survey', [AdminController::class, 'bulkUpdateClassSurvey'])->name('isotanks.survey.bulk')->middleware('role:admin');

    // Activity Uploads & Planning (Admin Only)
    Route::middleware('role:admin')->group(function() {
        Route::get('/activities', [AdminController::class, 'activities'])->name('activities.index');
        Route::post('/activities/manual', [AdminController::class, 'storeManualActivity'])->name('activities.manual');
        Route::post('/activities/upload', [App\Http\Controllers\Web\Admin\ActivityUploadController::class, 'upload'])->name('activities.upload');
        Route::delete('/activities/inspection/{id}', [AdminController::class, 'deleteInspectionJob'])->name('activities.inspection.delete');
        Route::delete('/activities/maintenance/{id}', [AdminController::class, 'deleteMaintenanceJob'])->name('activities.maintenance.delete');
        Route::delete('/activities/calibration/{id}', [AdminController::class, 'deleteCalibrationJob'])->name('activities.calibration.delete');
        
        // Manual Report Trigger
        Route::get('/reports/daily/preview', [AdminController::class, 'previewDailyReport'])->name('reports.daily.preview');
        Route::post('/send-daily-report', [AdminController::class, 'sendDailyReport'])->name('reports.send_daily');

        // Yard Generator Tool
        Route::get('/yard/generator', [App\Http\Controllers\Web\Admin\YardGeneratorController::class, 'index'])->name('yard.generator');
        Route::get('/yard/generator/csv', [App\Http\Controllers\Web\Admin\YardGeneratorController::class, 'downloadCsv'])->name('yard.generator.csv');
        Route::post('/yard/generator/import', [App\Http\Controllers\Web\Admin\YardGeneratorController::class, 'importExcel'])->name('yard.generator.import');
        
        // Templates
        Route::get('/templates/calibration', [App\Http\Controllers\Web\Admin\TemplateController::class, 'downloadCalibrationTemplate'])->name('templates.calibration');
        Route::get('/templates/inspection', [App\Http\Controllers\Web\Admin\TemplateController::class, 'downloadInspectionTemplate'])->name('templates.inspection');
        Route::get('/templates/maintenance', [App\Http\Controllers\Web\Admin\TemplateController::class, 'downloadMaintenanceTemplate'])->name('templates.maintenance');
        Route::get('/templates/vacuum', [App\Http\Controllers\Web\Admin\TemplateController::class, 'downloadVacuumTemplate'])->name('templates.vacuum');
    });
    
    // User Management (Admin Only)
    Route::middleware('role:admin')->prefix('users')->name('users.')->group(function() {
        Route::get('/', [UserManagementController::class, 'index'])->name('index');
        Route::post('/', [UserManagementController::class, 'store'])->name('store');
        Route::put('/{id}', [UserManagementController::class, 'update'])->name('update');
        Route::patch('/{id}/role', [UserManagementController::class, 'updateRole'])->name('updateRole');
        Route::patch('/{id}/password', [UserManagementController::class, 'resetPassword'])->name('resetPassword');
        Route::delete('/{id}', [UserManagementController::class, 'destroy'])->name('destroy');
    });
    
    // Inspection Items Management (Admin Only)
    Route::middleware('role:admin')->prefix('inspection-items')->name('inspection-items.')->group(function() {
        Route::get('/', [InspectionItemController::class, 'index'])->name('index');
        Route::post('/', [InspectionItemController::class, 'store'])->name('store');
        Route::put('/{id}', [InspectionItemController::class, 'update'])->name('update');
        Route::patch('/{id}/toggle', [InspectionItemController::class, 'toggleActive'])->name('toggle');
        Route::post('/reorder', [InspectionItemController::class, 'reorder'])->name('reorder');
        Route::delete('/{id}', [InspectionItemController::class, 'destroy'])->name('destroy');
    });
    
    // Reports / Tables View
    Route::get('/reports/inspection', [AdminController::class, 'inspectionLogs'])->name('reports.inspection');
    Route::get('/reports/inspection/{id}', [AdminController::class, 'showInspectionLog'])->name('reports.inspection.show');
    Route::get('/reports/latest-condition', [AdminController::class, 'latestInspections'])->name('reports.latest');
    Route::get('/reports/maintenance', [AdminController::class, 'maintenanceJobs'])->name('reports.maintenance');
    Route::get('/reports/maintenance/{id}', [AdminController::class, 'showMaintenanceJob'])->name('reports.maintenance.show');
    Route::get('/reports/calibration', [AdminController::class, 'calibrationLogs'])->name('reports.calibration');
    Route::get('/reports/vacuum', [AdminController::class, 'vacuumActivities'])->name('reports.vacuum');
});

// Yard Management (Visual Only)
// View Access (Admin, Management, Yard Operator, Inspector)
Route::middleware(['auth:web', 'role:admin,management,yard_operator,inspector'])->prefix('yard')->name('yard.')->group(function() {
    Route::get('/', [YardController::class, 'index'])->name('index');
    Route::get('/layout', [YardController::class, 'getLayout'])->name('layout');
    Route::get('/positions', [YardController::class, 'getPositions'])->name('positions');
});

// Write Access (Yard Operator ONLY)
Route::middleware(['auth:web', 'role:yard_operator'])->prefix('yard')->name('yard.')->group(function() {
     Route::post('/layout/upload', [YardController::class, 'uploadLayout'])->name('layout.upload');
     Route::post('/move', [YardController::class, 'moveIsotank'])->name('move');
     

});

// PDF Generation Route
Route::get('/admin/reports/inspection/{id}/pdf', [AdminController::class, 'downloadInspectionPdf'])->name('admin.reports.inspection.pdf')->middleware(['auth:web', 'role:admin,management,yard_operator']);
