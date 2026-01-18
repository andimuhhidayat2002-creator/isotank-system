# 🆕 NEW STATUS: "Waiting Inspection"

## 🎯 Use Case

**Scenario:** Isotank baru datang ke site, admin sudah create incoming inspection job, tapi inspector belum melakukan pemeriksaan.

**Problem:** Isotank ini tidak punya status yang tepat karena:
- ❌ Bukan "Ready to Fill" - belum di-inspect
- ❌ Bukan "Filled" - belum tahu isinya
- ❌ Bukan "Not Specified" - admin sudah aware dan create job

**Solution:** Status baru `waiting_inspection`

---

## ✅ Implementation

### **1. Backend - MasterIsotank.php**

```php
// New constant
public const FILLING_STATUS_WAITING_INSPECTION = 'waiting_inspection';

// Added to getValidFillingStatuses()
public static function getValidFillingStatuses(): array
{
    return [
        self::FILLING_STATUS_WAITING_INSPECTION => 'Waiting Inspection', // NEW
        self::FILLING_STATUS_READY_TO_FILL => 'Ready to Fill',
        self::FILLING_STATUS_FILLED => 'Filled',
        self::FILLING_STATUS_UNDER_MAINTENANCE => 'Under Maintenance',
        self::FILLING_STATUS_WAITING_CALIBRATION => 'Waiting Team Calibration',
        self::FILLING_STATUS_CLASS_SURVEY => 'Class Survey',
    ];
}

// New scope
public function scopeWaitingInspection($query)
{
    return $query->where('filling_status_code', self::FILLING_STATUS_WAITING_INSPECTION);
}
```

### **2. Flutter - filling_status.dart**

```dart
enum FillingStatus {
  waitingInspection('waiting_inspection', 'Waiting Inspection'), // NEW
  readyToFill('ready_to_fill', 'Ready to Fill'),
  filled('filled', 'Filled'),
  underMaintenance('under_maintenance', 'Under Maintenance'),
  waitingTeamCalibration('waiting_team_calibration', 'Waiting Team Calibration'),
  classSurvey('class_survey', 'Class Survey');
  
  // ... rest of code
}
```

### **3. Yard Map - Color Coding**

```javascript
// Added to switch statement
case 'waiting_inspection':
    bgColor = 'linear-gradient(135deg, #9E9E9E 0%, #BDBDBD 100%)'; // Grey
    break;

// Added to statusColors
const statusColors = {
    'waiting_inspection': '#9E9E9E', // NEW
    'ready_to_fill': '#4CAF50',
    'filled': '#2196F3',
    // ... rest
};
```

### **4. Dashboard & Location Detail**

```php
// Added to color mapping
$statusColors = [
    'waiting_inspection' => ['color' => '#9E9E9E', 'label' => 'Waiting Inspection'], // NEW
    'ready_to_fill' => ['color' => '#4CAF50', 'label' => 'Ready to Fill'],
    // ... rest
];
```

---

## 🎨 Visual Design

### **Color:** Grey (#9E9E9E)
- **Rationale:** Neutral color indicating "pending" state
- **Gradient:** `linear-gradient(135deg, #9E9E9E 0%, #BDBDBD 100%)`
- **Distinguishable:** Different from all other statuses

### **Icon Suggestion:** 
- `schedule` or `pending` or `hourglass_empty`

---

## 📊 Status Lifecycle

```
┌─────────────────────────────────────────────────────────┐
│                  ISOTANK LIFECYCLE                       │
└─────────────────────────────────────────────────────────┘

1. Isotank arrives at site
   ↓
2. Admin creates incoming inspection job
   ↓
3. Admin sets status: "Waiting Inspection" ⬅️ NEW!
   ↓
4. Inspector performs inspection
   ↓
5. Inspector sets actual status:
   - "Ready to Fill" (if empty & good condition)
   - "Filled" (if has cargo)
   - "Under Maintenance" (if needs repair)
   - "Waiting Team Calibration" (if needs calibration)
   - "Class Survey" (if needs survey)
```

---

## 🔄 Workflow Integration

### **Admin Workflow:**
1. Isotank baru datang
2. Create incoming inspection job
3. **Set status: "Waiting Inspection"** ← Manual action
4. Assign to inspector
5. Wait for inspection

### **Inspector Workflow:**
1. See job with status "Waiting Inspection"
2. Perform inspection
3. **Update status based on findings**
4. Submit inspection

### **System Behavior:**
- Yard map shows grey card for "Waiting Inspection"
- Dashboard counts "Waiting Inspection" separately
- Location detail shows breakdown
- Reports include this status

---

## 📈 Benefits

### **1. Better Visibility**
- ✅ Clear distinction between "not inspected yet" vs "no status set"
- ✅ Admin knows which isotanks are pending inspection
- ✅ Inspector can prioritize based on status

### **2. Accurate Reporting**
- ✅ Dashboard shows how many isotanks waiting for inspection
- ✅ Can track inspection backlog
- ✅ Better resource planning

### **3. Workflow Clarity**
- ✅ Clear handoff between admin and inspector
- ✅ No confusion about isotank state
- ✅ Audit trail of status changes

---

## 🎯 When to Use Each Status

| Status | When to Use | Set By |
|--------|-------------|--------|
| **Waiting Inspection** 🆕 | Isotank just arrived, job created, not inspected yet | Admin |
| **Ready to Fill** | Empty, good condition, ready for filling | Inspector |
| **Filled** | Contains cargo | Inspector |
| **Under Maintenance** | Needs repair/maintenance | Inspector |
| **Waiting Team Calibration** | Needs calibration team | Inspector |
| **Class Survey** | Needs class survey | Inspector |
| **Not Specified** | No status set (legacy/unknown) | System |

---

## 🚀 Usage Example

### **Scenario: New Isotank Arrival**

```php
// Admin creates isotank and job
$isotank = MasterIsotank::create([
    'iso_number' => 'KYNU234567',
    'location' => 'SMGRS',
    'status' => 'active',
    'filling_status_code' => 'waiting_inspection', // Set this!
    'filling_status_desc' => 'Waiting Inspection',
]);

$job = InspectionJob::create([
    'isotank_id' => $isotank->id,
    'activity_type' => 'incoming_inspection',
    'status' => 'open',
]);
```

### **Query Examples:**

```php
// Get all isotanks waiting for inspection
$waiting = MasterIsotank::waitingInspection()->get();

// Count by location
$smgrsWaiting = MasterIsotank::where('location', 'SMGRS')
    ->waitingInspection()
    ->count();

// Get oldest waiting
$oldest = MasterIsotank::waitingInspection()
    ->orderBy('created_at')
    ->first();
```

---

## 📝 Documentation Updates

### **Files Modified:**
1. ✅ `MasterIsotank.php` - Added constant & scope
2. ✅ `filling_status.dart` - Added enum value
3. ✅ `index.blade.php` (yard) - Added color mapping
4. ✅ `location_detail.blade.php` - Added color
5. ✅ `dashboard.blade.php` - Added color

### **Total Statuses:** 6 + 1 (Not Specified) = **7 statuses**

---

## ✅ Complete Status List

| # | Code | Display Name | Color | Use Case |
|---|------|--------------|-------|----------|
| 1 | `waiting_inspection` | Waiting Inspection | 🔘 Grey | Just arrived, pending inspection |
| 2 | `ready_to_fill` | Ready to Fill | 🟢 Green | Empty, good condition |
| 3 | `filled` | Filled | 🔵 Blue | Contains cargo |
| 4 | `under_maintenance` | Under Maintenance | 🟠 Orange | Needs repair |
| 5 | `waiting_team_calibration` | Waiting Team Calibration | 🟡 Amber | Needs calibration |
| 6 | `class_survey` | Class Survey | 🟣 Purple | Needs survey |
| 7 | `null` | Not Specified | ⚪ Grey | No status set |

---

## 🎉 Summary

**New Status Added:** `waiting_inspection`

**Purpose:** For isotanks that just arrived at site and are waiting for incoming inspection.

**Color:** Grey (#9E9E9E) - Neutral, pending state

**Set By:** Admin when creating incoming inspection job

**Changed By:** Inspector after completing inspection

**Benefit:** Clear visibility of inspection backlog and better workflow management

---

**Last Updated:** 2026-01-14 05:50 WIB
**Version:** 1.2.0 - Waiting Inspection Status Added
