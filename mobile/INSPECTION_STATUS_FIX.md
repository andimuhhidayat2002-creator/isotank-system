# ✅ Fix: Inspection Filling Status "Not Specified"

## 🎯 Issue
The "Filling Status" field in the Inspection Detail view was showing "Not Specified".
**Cause:** The `filling_status_code` fields were missing from the `$fillable` property of the `InspectionLog` model, preventing them from being saved during submission. Additionally, fallback logic was missing if the mobile app didn't send this data.

## ✅ Changes Applied

### 1. Updated `InspectionLog` Model
- Added `filling_status_code` and `filling_status_desc` to the `$fillable` array.
- This ensures these fields are properly saved to the database.

### 2. Updated `InspectionSubmitController`
- Added fallback logic:
  ```php
  'filling_status_code' => $validated['filling_status_code'] 
      ?? $job->filling_status_code 
      ?? $job->isotank->filling_status_code,
  ```
- This guarantees that even if the mobile app doesn't send the status, the system will use the status from the Plan Job or the current Isotank status.

### 3. Data Patch
- Ran a script to update 13 existing logs (including the recent one for KYNUTES) that had NULL status.
- They have been updated with their corresponding isotank's current status.

## 📝 Verification
1. Refresh the **Inspection Detail** page for KYNUTES.
2. The **Filling Status** should now show a valid status (e.g., "Ready to Fill" or "Ongoing Inspection") instead of "Not Specified".

---
**Files Updated:**
- `app/Models/InspectionLog.php`
- `app/Http/Controllers/Api/Inspector/InspectionSubmitController.php`
- Database records patched.
