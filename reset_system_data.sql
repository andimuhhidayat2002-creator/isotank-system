SET FOREIGN_KEY_CHECKS = 0;

-- 1. Clear Transactional / Log Data
TRUNCATE TABLE receiver_confirmations;
TRUNCATE TABLE inspection_logs;
TRUNCATE TABLE inspection_jobs;
TRUNCATE TABLE maintenance_jobs;
TRUNCATE TABLE vacuum_logs;
TRUNCATE TABLE vacuum_suction_activities;
TRUNCATE TABLE calibration_logs;

-- 2. Clear Snapshot / Status Tables (Master Condition)
TRUNCATE TABLE master_latest_inspections;
TRUNCATE TABLE master_isotank_item_status;
TRUNCATE TABLE master_isotank_measurement_status;
TRUNCATE TABLE master_isotank_calibration_status;

-- 3. Clear Historical Upload Logs
TRUNCATE TABLE excel_upload_logs;
TRUNCATE TABLE activity_uploads;
TRUNCATE TABLE isotank_uploads;

-- 4. Reset Master Isotank Live Status
UPDATE master_isotanks SET 
    status = 'active', 
    filling_status_code = NULL, 
    filling_status_desc = NULL;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Reset Completed' AS result;
