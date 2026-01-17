---
description: Admin Input Modes Extension Implementation Plan
---

# ADMIN INPUT MODES EXTENSION - IMPLEMENTATION PLAN

## OVERVIEW
Extend existing system to support TWO input methods for Admin:
1. **Manual Input** (Web Form)
2. **Bulk Input** (Excel Upload)

Applies to:
- Master Isotanks
- Activity Planner (Inspection/Maintenance/Calibration)

## CRITICAL RULES
- ✅ NO removal of existing rules
- ✅ NO renaming of fields
- ✅ NO simplification of workflows
- ✅ Manual and Excel use SAME backend validation
- ✅ Neither mode writes to inspection_logs directly
- ✅ Neither mode updates master_isotank_item_status directly

---

## PHASE 1: MASTER ISOTANK INPUT MODES

### 1.1 Manual Master Isotank Input (ALREADY EXISTS)
**Status**: ✅ Already implemented in Admin Web
**Location**: `resources/views/admin/isotanks/create.blade.php`

**Verify**:
- [ ] Form has all required fields
- [ ] Validation works
- [ ] Only admin can access

### 1.2 Bulk Master Isotank Upload (NEW)

**Backend Tasks**:
- [ ] Create migration for `isotank_uploads` table (audit trail)
- [ ] Create `IsotankUploadController` with `upload()` method
- [ ] Implement Excel validation using `maatwebsite/excel`
- [ ] Handle CREATE vs UPDATE logic (based on iso_number)
- [ ] Store uploaded file for audit
- [ ] Return detailed validation report

**Frontend Tasks**:
- [ ] Add "Upload Excel" button to isotanks index page
- [ ] Create upload modal/page
- [ ] Show validation results (success/errors)
- [ ] Download sample Excel template

**Excel Structure**:
```
| iso_number* | product | owner | manufacturer | model_type | location | status |
```

---

## PHASE 2: ACTIVITY PLANNER INPUT MODES

### 2.1 Manual Activity Creation (PARTIALLY EXISTS)

**Current State**:
- Inspection: Needs verification
- Maintenance: Needs verification
- Calibration: Needs implementation

**Tasks**:
- [ ] Verify existing manual forms
- [ ] Ensure all activity types supported
- [ ] Add activity type selector
- [ ] Implement validation per activity type

### 2.2 Bulk Activity Upload (NEW)

**Backend Tasks**:
- [ ] Create migration for `activity_uploads` table
- [ ] Create `ActivityUploadController`
- [ ] Implement separate handlers for each activity type:
  - [ ] Inspection (Incoming/Outgoing)
  - [ ] Maintenance
  - [ ] Calibration
- [ ] Validate against master_isotanks
- [ ] Apply business rules (e.g., Incoming → location = SMGRS)
- [ ] Store uploaded file for audit

**Frontend Tasks**:
- [ ] Add "Upload Excel" button to activities page
- [ ] Activity type selection BEFORE upload
- [ ] Show upload results
- [ ] Download sample templates per activity type

**Excel Structures**:

**Inspection**:
```
| iso_number* | planned_date | destination (outgoing only) |
```

**Maintenance**:
```
| iso_number* | item_name* | description* | priority | planned_date |
```

**Calibration**:
```
| iso_number* | item_name* | description* | planned_date | vendor |
```

---

## PHASE 3: DATABASE SCHEMA

### 3.1 Audit Tables

**isotank_uploads**:
```php
- id
- filename (original)
- filepath (stored)
- uploaded_by (user_id)
- total_rows
- success_count
- error_count
- error_details (json)
- created_at
```

**activity_uploads**:
```php
- id
- activity_type (inspection_incoming/inspection_outgoing/maintenance/calibration)
- filename
- filepath
- uploaded_by
- total_rows
- success_count
- error_count
- error_details (json)
- created_at
```

---

## PHASE 4: VALIDATION LOGIC

### 4.1 Shared Validation Rules
- iso_number must exist in master_isotanks
- iso_number must be active
- Dates must be valid format
- All mandatory fields present

### 4.2 Activity-Specific Rules
**Incoming Inspection**:
- FORCE location = "SMGRS"
- destination not allowed

**Outgoing Inspection**:
- destination required
- location NOT changed until receiver confirms

**Maintenance**:
- item_name must match inspection items exactly
- Creates maintenance_jobs with status = open

**Calibration**:
- item_name must be calibratable
- Creates calibration_logs with status = planned
- NEVER closes maintenance

---

## PHASE 5: UI REQUIREMENTS

### 5.1 Admin Web Pages to Update
- [ ] Master Isotanks Index: Add "Upload Excel" button
- [ ] Activities Index: Add "Upload Excel" button
- [ ] Create upload modals/pages
- [ ] Show clear distinction: "Add Manually" vs "Upload Excel"

### 5.2 User Experience
- [ ] Download sample Excel templates
- [ ] Show upload progress
- [ ] Display validation results clearly
- [ ] Allow download of error report

---

## IMPLEMENTATION ORDER

1. ✅ Create audit tables (migrations)
2. ✅ Install/verify maatwebsite/excel package
3. ✅ Implement Master Isotank bulk upload
4. ✅ Test Master Isotank upload
5. ✅ Implement Activity bulk upload (all types)
6. ✅ Test Activity uploads
7. ✅ Update UI with upload buttons
8. ✅ Create sample Excel templates
9. ✅ End-to-end testing

---

## TESTING CHECKLIST

### Master Isotank Upload
- [ ] Valid Excel with new isotanks → CREATE
- [ ] Valid Excel with existing isotanks → UPDATE
- [ ] Invalid iso_number format → REJECT
- [ ] Duplicate iso_number in Excel → REJECT
- [ ] Missing mandatory fields → REJECT
- [ ] File stored for audit

### Activity Upload
- [ ] Incoming inspection → location forced to SMGRS
- [ ] Outgoing inspection → destination required
- [ ] Maintenance → item_name validated
- [ ] Calibration → item_name validated (calibratable only)
- [ ] Invalid iso_number → REJECT
- [ ] Inactive isotank → REJECT
- [ ] File stored for audit

---

## NOTES
- All uploads are PLANNING TOOLS only
- Backend logic handles all side effects
- No direct writes to inspection_logs or master_isotank_item_status
- Maintain single source of truth principle
