# Filling Status Implementation Guide

## Overview
Sistem filling status telah diimplementasikan menggunakan field `filling_status_code` (string) di backend dan enum di Flutter untuk type-safety.

## Backend Implementation

### Database Schema
Field yang sudah ada di `master_isotanks`:
- `filling_status_code` (string, nullable)
- `filling_status_desc` (string, nullable)

### Valid Status Codes
```php
// Defined in MasterIsotank model
'ready_to_fill'             => 'Ready to Fill'
'filled'                    => 'Filled'
'under_maintenance'         => 'Under Maintenance'
'waiting_team_calibration'  => 'Waiting Team Calibration'
'class_survey'              => 'Class Survey'
```

### Model Scopes
```php
// Usage examples:
MasterIsotank::readyToFill()->get();
MasterIsotank::filled()->get();
MasterIsotank::underMaintenance()->get();
MasterIsotank::byFillingStatus('ready_to_fill')->get();
```

### API Endpoints
```
GET /api/filling-statuses              // Get all available statuses
GET /api/filling-statuses/statistics   // Get count per status
```

## Flutter Implementation

### 1. Enum Definition
```dart
// lib/data/models/filling_status.dart
enum FillingStatus {
  readyToFill('ready_to_fill', 'Ready to Fill'),
  filled('filled', 'Filled'),
  underMaintenance('under_maintenance', 'Under Maintenance'),
  waitingTeamCalibration('waiting_team_calibration', 'Waiting Team Calibration'),
  classSurvey('class_survey', 'Class Survey');

  final String code;
  final String displayName;
  
  const FillingStatus(this.code, this.displayName);
  
  static FillingStatus? fromCode(String? code) { ... }
}
```

### 2. Widget Usage

#### Dropdown Style
```dart
import 'package:your_app/ui/widgets/filling_status_selector.dart';
import 'package:your_app/data/models/filling_status.dart';

FillingStatusSelector(
  selectedStatus: _selectedStatus,
  onChanged: (status) {
    setState(() => _selectedStatus = status);
  },
  label: 'Filling Status',
  isRequired: true,
  style: SelectorStyle.dropdown,
)
```

#### Button Group Style
```dart
FillingStatusSelector(
  selectedStatus: _selectedStatus,
  onChanged: (status) {
    setState(() => _selectedStatus = status);
  },
  label: 'Select Status',
  style: SelectorStyle.buttonGroup,
)
```

### 3. Example: Adding to Inspection Form

```dart
// In InspectionFormScreen
FillingStatus? _selectedFillingStatus;

// In build method, add this section:
_buildSectionHeader('H. Filling Status'),
FillingStatusSelector(
  selectedStatus: _selectedFillingStatus,
  onChanged: (status) {
    setState(() {
      _selectedFillingStatus = status;
      _formData['filling_status_code'] = status?.code;
      _formData['filling_status_desc'] = status?.displayName;
    });
  },
  label: 'Current Filling Status',
  isRequired: false,
  style: SelectorStyle.buttonGroup, // or SelectorStyle.dropdown
),
```

### 4. Sending to Backend

```dart
// The widget automatically sets the code in _formData
// When submitting:
final submissionData = {
  ...
  'filling_status_code': _formData['filling_status_code'], // e.g., 'ready_to_fill'
  'filling_status_desc': _formData['filling_status_desc'], // e.g., 'Ready to Fill'
  ...
};

await _apiService.submitInspection(jobId, submissionData);
```

## Color Coding

### Status Colors
- **Ready to Fill**: Green (#4CAF50)
- **Filled**: Blue (#2196F3)
- **Under Maintenance**: Orange (#FF9800)
- **Waiting Team Calibration**: Amber (#FFC107)
- **Class Survey**: Purple (#9C27B0)

### Icons
- **Ready to Fill**: check_circle_outline
- **Filled**: check_circle
- **Under Maintenance**: build_circle
- **Waiting Team Calibration**: schedule
- **Class Survey**: assignment

## Yard Map Integration

The filling status colors are automatically applied to isotank cards in the yard map based on the `filling_status_code` field.

## Workflow Impact

### ✅ TIDAK MERUSAK WORKFLOW
1. **Backward Compatible**: Field nullable, isotank lama tetap berfungsi
2. **Flexible**: String field di backend, enum di frontend
3. **Type-Safe**: Flutter enum mencegah typo dan invalid values
4. **Extensible**: Mudah menambah status baru di masa depan

### Adding New Status (Future)
1. Add constant to `MasterIsotank` model
2. Add to `getValidFillingStatuses()` array
3. Add scope method (optional)
4. Add enum value to Flutter `FillingStatus`
5. Update color/icon mappings

## Testing Checklist

- [ ] Inspector dapat memilih filling status via dropdown/buttons
- [ ] Status tersimpan ke database dengan benar
- [ ] Yard map menampilkan warna sesuai status
- [ ] API endpoint `/filling-statuses` mengembalikan semua status
- [ ] Statistics endpoint menghitung jumlah per status
- [ ] Isotank tanpa status tidak error (nullable)
- [ ] Filter by status berfungsi (scopes)

## Migration Notes

**TIDAK PERLU MIGRATION BARU** karena field `filling_status_code` sudah ada sejak migration `2026_01_10_203500_add_filling_status_to_tables.php`.

Cukup update aplikasi untuk menggunakan nilai-nilai status yang sudah didefinisikan.
