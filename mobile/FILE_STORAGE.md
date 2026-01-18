# File Storage Organization

## Overview
Sistem penyimpanan file terorganisir untuk foto inspection, foto maintenance, dan PDF reports.

## Storage Structure

```
IsotankInspection/
├── Photos/
│   ├── Inspection/
│   │   └── INSPECTION_{ISO_NUMBER}_{PHOTO_TYPE}_{TIMESTAMP}.jpg
│   └── Maintenance/
│       └── MAINTENANCE_{ISO_NUMBER}_JOB{ID}_{SUFFIX}_{TIMESTAMP}.jpg
└── PDF/
    ├── Incoming/
    │   └── Incoming_Inspection_{ISO_NUMBER}_{TIMESTAMP}.pdf
    └── Outgoing/
        └── Outgoing_Inspection_{ISO_NUMBER}_{TIMESTAMP}.pdf
```

## Storage Locations

### Android
```
/storage/emulated/0/Android/data/com.example.isotank_app/files/IsotankInspection/
```

### iOS
```
/Documents/IsotankInspection/
```

## File Naming Conventions

### Inspection Photos
**Format**: `INSPECTION_{ISO_NUMBER}_{PHOTO_TYPE}_{TIMESTAMP}.jpg`

**Examples**:
- `INSPECTION_ISO001_front_1704459600000.jpg`
- `INSPECTION_ISO002_valve_box_1704459700000.jpg`
- `INSPECTION_ISO003_surface_1704459800000.jpg`

**Photo Types**:
- `front`, `back`, `left`, `right`
- `inside_valve_box`
- `additional`, `extra`
- `surface`, `frame`, `tank_plate`, etc. (for maintenance triggers)

### Maintenance Photos
**Format**: `MAINTENANCE_{ISO_NUMBER}_JOB{ID}_{SUFFIX}_{TIMESTAMP}.jpg`

**Examples**:
- `MAINTENANCE_ISO001_JOB123_after_1704459600000.jpg`
- `MAINTENANCE_ISO002_JOB124_after_1704459700000.jpg`

**Suffix**: `after` (photo setelah maintenance selesai)

### PDF Files

#### Incoming Inspection
**Format**: `Incoming_Inspection_{ISO_NUMBER}_{TIMESTAMP}.pdf`

**Example**: `Incoming_Inspection_ISO001_1704459600000.pdf`

#### Outgoing Inspection
**Format**: `Outgoing_Inspection_{ISO_NUMBER}_{TIMESTAMP}.pdf`

**Example**: `Outgoing_Inspection_ISO001_1704459600000.pdf`

## FileManagerService API

### Get Directories

```dart
// Get inspection photos directory
final dir = await FileManagerService.getInspectionPhotosDirectory();

// Get maintenance photos directory
final dir = await FileManagerService.getMaintenancePhotosDirectory();

// Get incoming PDF directory
final dir = await FileManagerService.getIncomingPdfDirectory();

// Get outgoing PDF directory
final dir = await FileManagerService.getOutgoingPdfDirectory();
```

### Save Files

```dart
// Save inspection photo
final savedFile = await FileManagerService.saveInspectionPhoto(
  photoFile,
  'ISO001',
  'front',
);

// Save maintenance photo
final savedFile = await FileManagerService.saveMaintenancePhoto(
  photoFile,
  'ISO001',
  123, // job ID
  suffix: 'after',
);
```

### Storage Info

```dart
// Get storage structure info
final info = await FileManagerService.getStorageInfo();
print(info['base_path']);
print(info['structure']['Photos']['Inspection']['count']);

// Print storage structure (debug)
await FileManagerService.printStorageStructure();
```

### Cleanup (Optional)

```dart
// Clean up files older than 90 days
await FileManagerService.cleanupOldFiles(daysToKeep: 90);
```

## Integration Points

### 1. Inspection Form Screen
- **File**: `lib/ui/screens/inspection_form/inspection_form_screen.dart`
- **Method**: `_takePhoto(String key)`
- **Action**: Saves photo to `Photos/Inspection/` folder
- **Naming**: `INSPECTION_{ISO_NUMBER}_{PHOTO_TYPE}_{TIMESTAMP}.jpg`

### 2. Maintenance Form Screen
- **File**: `lib/ui/screens/maintenance/maintenance_form_screen.dart`
- **Method**: `_takePhoto()`
- **Action**: Saves photo to `Photos/Maintenance/` folder
- **Naming**: `MAINTENANCE_{ISO_NUMBER}_JOB{ID}_after_{TIMESTAMP}.jpg`

### 3. PDF Service
- **File**: `lib/data/services/pdf_service.dart`
- **Methods**: 
  - `_saveIncomingPdf()` → saves to `PDF/Incoming/`
  - `_saveOutgoingPdf()` → saves to `PDF/Outgoing/`

## Benefits

### ✅ Organized Structure
- Semua file terorganisir dalam folder yang jelas
- Mudah ditemukan berdasarkan kategori

### ✅ Descriptive Naming
- Nama file mengandung informasi penting (ISO number, type, timestamp)
- Mudah diidentifikasi tanpa membuka file

### ✅ Easy Maintenance
- Cleanup otomatis untuk file lama
- Storage info untuk monitoring

### ✅ Cross-Platform
- Bekerja di Android dan iOS
- Menggunakan path yang sesuai untuk setiap platform

## User Experience

### Saat Mengambil Foto
1. User klik tombol camera
2. Kamera terbuka
3. User ambil foto
4. Foto otomatis disimpan ke folder yang sesuai
5. Snackbar muncul menampilkan nama file yang tersimpan
6. Contoh: "Photo saved: INSPECTION_ISO001_front_1704459600000.jpg"

### Saat Generate PDF
1. Inspection/Receiver confirmation selesai
2. PDF otomatis di-generate
3. PDF disimpan ke folder Incoming/Outgoing
4. PDF otomatis terbuka
5. User bisa langsung melihat hasilnya

## Storage Management

### Monitoring
```dart
// Check storage info
final info = await FileManagerService.getStorageInfo();

// Inspection photos count
int inspectionCount = info['structure']['Photos']['Inspection']['count'];

// Maintenance photos count
int maintenanceCount = info['structure']['Photos']['Maintenance']['count'];

// Incoming PDF count
int incomingPdfCount = info['structure']['PDF']['Incoming']['count'];

// Outgoing PDF count
int outgoingPdfCount = info['structure']['PDF']['Outgoing']['count'];
```

### Cleanup Strategy
- Default: Keep files for 90 days
- Can be customized: `cleanupOldFiles(daysToKeep: 30)`
- Runs on-demand (not automatic)
- Deletes files based on modification date

## Error Handling

### Photo Save Errors
```dart
try {
  final savedFile = await FileManagerService.saveInspectionPhoto(...);
} catch (e) {
  // Show error to user
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(content: Text('Error saving photo: $e')),
  );
}
```

### PDF Save Errors
```dart
try {
  await PdfService.generateIncomingPdf(...);
} catch (e) {
  // Show warning but don't block the flow
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(
      content: Text('PDF generation failed: $e'),
      backgroundColor: Colors.orange,
    ),
  );
}
```

## Permissions

### Android
Requires storage permissions (already handled by `path_provider`)

### iOS
Requires photo library permissions (already handled by `image_picker`)

## Notes

- Semua foto disimpan dalam format JPG dengan quality 50% untuk menghemat space
- Timestamp menggunakan milliseconds since epoch untuk uniqueness
- File manager service menggunakan singleton pattern untuk efisiensi
- Semua operasi file bersifat asynchronous untuk performa yang baik
