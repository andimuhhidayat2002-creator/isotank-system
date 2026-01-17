<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterIsotank extends Model
{
    protected $fillable = [
        'iso_number',
        'owner', // Added
        'product', // Added
        'manufacturer',
        'model_type', // New
        'manufacturer_serial_number',
        'csc_certificate',
        'capacity',
        'tare_weight',
        'max_gross_weight',
        'initial_pressure_test_date', // New
        'csc_initial_test_date', // New
        'class_survey_expiry_date', // New
        'csc_survey_expiry_date', // New
        'status', // active, inactive, maintenance, etc.
        'location', // Current location code (e.g. SMGRS)
        'filling_status_code',
        'filling_status_desc',
    ];

    protected $casts = [
        'status' => 'string',
        'capacity' => 'decimal:2',
        'tare_weight' => 'decimal:2',
        'max_gross_weight' => 'decimal:2',
        'initial_pressure_test_date' => 'date',
        'csc_initial_test_date' => 'date',
        'class_survey_expiry_date' => 'date',
        'csc_survey_expiry_date' => 'date',
    ];

    // Relationships
    public function classSurveys(): HasMany
    {
        return $this->hasMany(ClassSurvey::class, 'isotank_id');
    }

    public function inspectionJobs(): HasMany
    {
        return $this->hasMany(InspectionJob::class, 'isotank_id');
    }

    public function inspectionLogs(): HasMany
    {
        return $this->hasMany(InspectionLog::class, 'isotank_id');
    }

    public function maintenanceJobs(): HasMany
    {
        return $this->hasMany(MaintenanceJob::class, 'isotank_id');
    }

    public function calibrationLogs(): HasMany
    {
        return $this->hasMany(CalibrationLog::class, 'isotank_id');
    }

    public function vacuumLogs(): HasMany
    {
        return $this->hasMany(VacuumLog::class, 'isotank_id');
    }

    public function vacuumSuctionActivities(): HasMany
    {
        return $this->hasMany(VacuumSuctionActivity::class, 'isotank_id');
    }

    public function itemStatuses(): HasMany
    {
        return $this->hasMany(MasterIsotankItemStatus::class, 'isotank_id');
    }

    public function measurementStatus()
    {
        return $this->hasOne(MasterIsotankMeasurementStatus::class, 'isotank_id');
    }

    public function calibrationStatuses(): HasMany
    {
        return $this->hasMany(MasterIsotankCalibrationStatus::class, 'isotank_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(MasterIsotankComponent::class, 'isotank_id');
    }

    public function latestInspection()
    {
        return $this->hasOne(MasterLatestInspection::class, 'isotank_id');
    }

    // Constants for filling status codes
    public const FILLING_STATUS_ONGOING_INSPECTION = 'ongoing_inspection';
    public const FILLING_STATUS_READY_TO_FILL = 'ready_to_fill';
    public const FILLING_STATUS_FILLED = 'filled';
    public const FILLING_STATUS_UNDER_MAINTENANCE = 'under_maintenance';
    public const FILLING_STATUS_WAITING_CALIBRATION = 'waiting_team_calibration';
    public const FILLING_STATUS_CLASS_SURVEY = 'class_survey';

    /**
     * Get all valid filling status codes
     */
    public static function getValidFillingStatuses(): array
    {
        return [
            self::FILLING_STATUS_ONGOING_INSPECTION => 'Ongoing Inspection',
            self::FILLING_STATUS_READY_TO_FILL => 'Ready to Fill',
            self::FILLING_STATUS_FILLED => 'Filled',
            self::FILLING_STATUS_UNDER_MAINTENANCE => 'Under Maintenance',
            self::FILLING_STATUS_WAITING_CALIBRATION => 'Waiting Team Calibration',
            self::FILLING_STATUS_CLASS_SURVEY => 'Class Survey',
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeByFillingStatus($query, string $fillingStatus)
    {
        return $query->where('filling_status_code', $fillingStatus);
    }

    public function scopeOngoingInspection($query)
    {
        return $query->where('filling_status_code', self::FILLING_STATUS_ONGOING_INSPECTION);
    }

    public function scopeReadyToFill($query)
    {
        return $query->where('filling_status_code', self::FILLING_STATUS_READY_TO_FILL);
    }

    public function scopeFilled($query)
    {
        return $query->where('filling_status_code', self::FILLING_STATUS_FILLED);
    }

    public function scopeUnderMaintenance($query)
    {
        return $query->where('filling_status_code', self::FILLING_STATUS_UNDER_MAINTENANCE);
    }

    public function scopeWaitingCalibration($query)
    {
        return $query->where('filling_status_code', self::FILLING_STATUS_WAITING_CALIBRATION);
    }

    public function scopeClassSurvey($query)
    {
        return $query->where('filling_status_code', self::FILLING_STATUS_CLASS_SURVEY);
    }
}
