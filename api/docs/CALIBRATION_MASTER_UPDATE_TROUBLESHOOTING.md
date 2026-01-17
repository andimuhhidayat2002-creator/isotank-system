# CALIBRATION MASTER UPDATE - TROUBLESHOOTING GUIDE

## Masalah
Data kalibrasi (PG Serial, PSV Serial, Calibration Date) dari inspection tidak ter-update ke master isotank calibration status.

## Root Cause Analysis

### Kemungkinan Penyebab:

1. **Inspection disimpan sebagai DRAFT**
   - Draft TIDAK akan update master tables
   - Hanya inspection yang di-submit (bukan draft) yang update master

2. **Field name tidak cocok**
   - Backend expects: `pressure_gauge_serial`, `psv1_serial`, etc.
   - Flutter harus mengirim dengan nama field yang sama

3. **Serial number kosong/null**
   - Jika serial number tidak diisi, master tidak akan di-update
   - Kondisi: `if ($hasSerial && !empty($validated[$serialKey]))`

4. **Validation error**
   - Jika ada validation error, transaction di-rollback
   - Master tidak akan ter-update

## Fix yang Sudah Diterapkan

✅ **Added comprehensive logging** di `updateMasterCalibrationStatus()`
- Log setiap item yang dicek
- Log nilai serial number dan calibration date
- Log apakah update berhasil atau di-skip

## Cara Debugging

### 1. Cek Laravel Log

```bash
# Windows
tail -f storage/logs/laravel.log

# Atau buka file:
# c:\laragon\www\isotank-system\api\storage\logs\laravel.log
```

### 2. Submit Inspection dari Flutter

Pastikan:
- ✅ Inspection di-submit (bukan draft)
- ✅ PG Serial Number diisi
- ✅ PSV Serial Number diisi (jika ada)
- ✅ Calibration Date diisi

### 3. Cek Log Output

Anda akan melihat log seperti ini:

```
[2026-01-16 20:30:00] local.INFO: === UPDATE MASTER CALIBRATION STATUS === {"isotank_id":1,"validated_keys":["inspection_date","surface","frame",...,"pressure_gauge_serial","pressure_gauge_calibration_date",...]}

[2026-01-16 20:30:00] local.INFO: Checking calibration item: pressure_gauge {"serial_key":"pressure_gauge_serial","has_serial":true,"serial_value":"PG-12345","date_value":"2026-01-15"}

[2026-01-16 20:30:00] local.INFO: Updating master calibration for pressure_gauge {"serial_number":"PG-12345","calibration_date":"2026-01-15","valid_until":"2027-01-15","status":"valid"}

[2026-01-16 20:30:00] local.INFO: Successfully updated master calibration for pressure_gauge
```

### 4. Jika Log Menunjukkan "Skipping"

```
[2026-01-16 20:30:00] local.INFO: Skipping pressure_gauge - no serial number provided
```

**Artinya:**
- Serial number TIDAK dikirim dari Flutter, ATAU
- Serial number kosong/null

**Solusi:**
- Cek Flutter app, pastikan field serial number terisi
- Cek network request (inspect payload yang dikirim ke API)

### 5. Verifikasi Data di Database

```sql
-- Cek inspection log terakhir
SELECT id, isotank_id, inspection_date, 
       pressure_gauge_serial_number, 
       pressure_gauge_calibration_date,
       psv1_serial_number, psv1_calibration_date,
       is_draft
FROM inspection_logs 
ORDER BY id DESC 
LIMIT 5;

-- Cek master calibration status
SELECT isotank_id, item_name, serial_number, 
       calibration_date, valid_until, updated_at
FROM master_isotank_calibration_status
ORDER BY updated_at DESC
LIMIT 10;
```

## Field Mapping Reference

| Flutter Field Name | Backend Validation Key | Inspection Log Column | Master Table Field |
|-------------------|------------------------|----------------------|-------------------|
| `pressure_gauge_serial` | `pressure_gauge_serial` | `pressure_gauge_serial_number` | `serial_number` (item: pressure_gauge) |
| `pressure_gauge_calibration_date` | `pressure_gauge_calibration_date` | `pressure_gauge_calibration_date` | `calibration_date` |
| `psv1_serial` | `psv1_serial` | `psv1_serial_number` | `serial_number` (item: psv1) |
| `psv1_calibration_date` | `psv1_calibration_date` | `psv1_calibration_date` | `calibration_date` |
| `psv2_serial` | `psv2_serial` | `psv2_serial_number` | `serial_number` (item: psv2) |
| ... | ... | ... | ... |

## Testing Steps

1. **Clear old data (optional)**
   ```sql
   TRUNCATE TABLE master_isotank_calibration_status;
   ```

2. **Submit new inspection** dari Flutter dengan data:
   - PG Serial: `TEST-PG-001`
   - PG Calibration Date: `2026-01-16`
   - PSV1 Serial: `TEST-PSV1-001`
   - PSV1 Calibration Date: `2026-01-16`

3. **Check log** di `storage/logs/laravel.log`

4. **Verify database**:
   ```sql
   SELECT * FROM master_isotank_calibration_status 
   WHERE serial_number LIKE 'TEST-%';
   ```

## Expected Behavior

✅ **CORRECT:**
- Inspection submitted (not draft)
- Serial number filled → Master table UPDATED
- Serial number empty → Master table NOT UPDATED (skipped)

❌ **INCORRECT:**
- Inspection submitted with serial → Master NOT updated
  - Check log for errors
  - Check if `is_draft = 0`
  - Check validation errors

## Next Steps if Still Not Working

1. Share the Laravel log output
2. Share the inspection_logs record (SQL query result)
3. Share the Flutter payload (network request body)
4. I'll analyze and provide specific fix

---

**Last Updated:** 2026-01-16  
**Status:** Logging added, ready for debugging
