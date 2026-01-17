# Dynamic Inspection Items Feature

## Overview
Fitur ini memungkinkan admin untuk mengelola item-item inspeksi secara dinamis dari web interface, tanpa perlu hardcode di aplikasi. Item yang dikonfigurasi akan otomatis muncul di Flutter inspection form.

## ✅ Phase 1: Backend Foundation (COMPLETED)

### Database Schema
**Tabel: `inspection_items`**
- `id` - Primary key
- `code` - Unique identifier (e.g., 'surface', 'frame')
- `label` - Display name (e.g., 'Surface Condition')
- `category` - Kategori item (external, internal, safety, valve, measurement, calibration)
- `input_type` - Tipe input (condition, text, number, date, boolean)
- `description` - Deskripsi item
- `order` - Urutan tampilan
- `is_required` - Wajib diisi atau tidak
- `is_active` - Aktif atau tidak (hanya item aktif yang muncul di app)
- `applies_to` - Berlaku untuk (both, incoming, outgoing)
- `options` - JSON untuk custom options
- `timestamps`

**Update: `inspection_logs`**
- Ditambahkan kolom `inspection_data` (JSON) untuk menyimpan data inspeksi dinamis

### Input Types
1. **condition** - Dropdown: Good / Not Good / Need Attention / NA
2. **text** - Free text input
3. **number** - Numeric input
4. **date** - Date picker
5. **boolean** - Yes/No toggle

### Categories
- **external** - External Inspection
- **internal** - Internal Inspection
- **safety** - Safety Equipment
- **valve** - Valve & Piping
- **measurement** - Measurements
- **calibration** - Calibration

### Default Items Seeded
✅ 16 inspection items sudah di-seed:
- Surface Condition
- Frame Structure
- Tank Plate
- Venting Pipe
- Explosion Proof Cover
- Valve Condition
- Valve Position
- Pipe Joint
- Pressure Gauge Reading
- Level Gauge Reading
- Vacuum Check Date
- Pressure Gauge Serial Number
- Pressure Gauge Calibration Date
- PSV Serial Number
- PSV Calibration Date
- Cleanliness

## ✅ Phase 2: Admin Web Interface (COMPLETED)

### Features
1. **View All Items** - Tabel dengan DataTables
2. **Add New Item** - Modal form untuk tambah item baru
3. **Edit Item** - Modal form untuk edit item
4. **Toggle Active/Inactive** - Quick toggle status
5. **Delete Item** - Hapus item dengan konfirmasi
6. **Drag & Drop Reorder** - Ubah urutan tampilan dengan drag & drop

### Access
- **URL**: `/admin/inspection-items`
- **Menu**: Sidebar → Inspection Items (admin only)
- **Role**: Admin only

### UI Features
- ✅ DataTables untuk search, sort, pagination
- ✅ SortableJS untuk drag & drop reordering
- ✅ Color-coded badges untuk kategori dan tipe
- ✅ Inline status toggle
- ✅ Comprehensive modals untuk CRUD

### Routes
```
GET     /admin/inspection-items              - List all items
POST    /admin/inspection-items              - Create new item
PUT     /admin/inspection-items/{id}         - Update item
PATCH   /admin/inspection-items/{id}/toggle  - Toggle active status
POST    /admin/inspection-items/reorder      - Reorder items
DELETE  /admin/inspection-items/{id}         - Delete item
```

## 🔄 Phase 3: API & Flutter Integration (NEXT)

### API Endpoints (To Be Created)
```
GET /api/inspection-items?type={incoming|outgoing}
```
Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "surface",
      "label": "Surface Condition",
      "category": "external",
      "input_type": "condition",
      "description": "Overall surface condition",
      "order": 1,
      "is_required": true,
      "applies_to": "both"
    }
  ]
}
```

### Flutter Changes Required
1. **Fetch Items from API**
   - Call API saat load inspection form
   - Cache items locally
   - Refresh periodically

2. **Dynamic Form Rendering**
   - Loop through items dan render sesuai `input_type`
   - Grouping by category (optional)
   - Respect `order` untuk urutan tampilan
   - Validate `is_required` fields

3. **Data Submission**
   - Simpan data sebagai JSON object
   - Format: `{"surface": "good", "frame": "not_good", ...}`
   - Submit ke `inspection_data` field

4. **Backward Compatibility**
   - Keep existing hardcoded fields untuk data lama
   - Migrate gradually ke dynamic system

## How to Use

### Adding New Inspection Item
1. Login sebagai admin
2. Klik menu "Inspection Items"
3. Klik "Add New Item"
4. Isi form:
   - **Code**: Unique identifier (lowercase, underscore)
   - **Label**: Display name
   - **Category**: Pilih kategori
   - **Input Type**: Pilih tipe input
   - **Applies To**: Both/Incoming/Outgoing
   - **Order**: Urutan tampilan
   - **Required**: Centang jika wajib
   - **Active**: Centang untuk aktifkan
   - **Description**: Deskripsi (optional)
5. Klik "Create Item"

### Editing Item
1. Klik icon pensil (biru) pada item
2. Edit field yang diperlukan
3. Klik "Update Item"

### Reordering Items
1. Drag icon grip (☰) pada item
2. Drop ke posisi yang diinginkan
3. Order akan otomatis tersimpan

### Activating/Deactivating Item
1. Klik tombol status (Active/Inactive)
2. Status akan toggle otomatis
3. Item inactive tidak akan muncul di mobile app

## Files Created/Modified

### Created
1. `database/migrations/2026_01_10_024045_create_inspection_items_table.php`
2. `app/Models/InspectionItem.php`
3. `database/seeders/InspectionItemsSeeder.php`
4. `app/Http/Controllers/Web/Admin/InspectionItemController.php`
5. `resources/views/admin/inspection_items/index.blade.php`

### Modified
1. `routes/web.php` - Added inspection items routes
2. `resources/views/layouts/app.blade.php` - Added menu item

## Next Steps

### For Backend Developer:
1. ✅ Create API endpoint untuk fetch inspection items
2. ✅ Update InspectionSubmitController untuk handle JSON data
3. ✅ Create migration helper untuk migrate old data

### For Flutter Developer:
1. ✅ Create InspectionItem model
2. ✅ Create API service untuk fetch items
3. ✅ Update InspectionFormScreen untuk dynamic rendering
4. ✅ Update data submission logic
5. ✅ Test dengan berbagai tipe input

## Benefits
✅ **Flexibility** - Tambah/ubah item tanpa update app
✅ **Customizable** - Sesuaikan per kebutuhan bisnis
✅ **Maintainable** - Admin bisa manage sendiri
✅ **Scalable** - Mudah tambah tipe input baru
✅ **User-friendly** - Drag & drop reordering

## Testing Checklist
- [ ] Tambah item baru
- [ ] Edit item existing
- [ ] Toggle active/inactive
- [ ] Reorder dengan drag & drop
- [ ] Hapus item
- [ ] Filter by category
- [ ] Test di Flutter app
- [ ] Test submission data
- [ ] Test validation

## Database Commands
```bash
# Run migration
php artisan migrate

# Seed default items
php artisan db:seed --class=InspectionItemsSeeder

# Rollback (if needed)
php artisan migrate:rollback
```
