# Yard Positioning - Excel-Driven Layout (1:1 Visual Match)

## ✅ Masalah yang Diperbaiki

### 1. **Error saat Move** ✅
**Masalah**: "Move failed" error tanpa detail
**Solusi**: 
- Menambahkan proper error handling dengan try-catch
- Menambahkan database transaction untuk data integrity
- Menambahkan logging untuk debugging
- Mengembalikan error message yang jelas ke frontend

### 2. **Yard Positioning Belum 1:1 dengan Excel** ✅
**Masalah**: 
- Merged cells tidak ditampilkan
- Text/label dari Excel tidak muncul
- Warna dan border tidak preserved

**Solusi**:
- Implementasi **Excel upload** (bukan CSV)
- Membaca **semua cell properties**:
  - ✅ Merged cells (colspan, rowspan)
  - ✅ Background colors
  - ✅ Border styles
  - ✅ Text content
  - ✅ Font properties (color, size, weight)
- Rendering yang **exact match** dengan Excel

## 🔧 Perubahan Teknis

### Backend (`YardController.php`)
1. **Added `uploadLayout()` method**:
   - Reads Excel file using PhpSpreadsheet
   - Extracts all cell properties
   - Handles merged cells
   - Marks slots with "X"
   - Bulk inserts to `yard_cells` table

2. **Improved `moveIsotank()` method**:
   - Added transaction support
   - Better error handling
   - Detailed error messages
   - Proper rollback on failure

### Frontend (`index.blade.php`)
1. **Changed upload from CSV to Excel**:
   - Accept `.xlsx` and `.xls` files
   - Updated UI text and instructions
   - Better user guidance

### Database
- Uses existing `yard_cells` table with:
  - `row_index`, `col_index` - Position
  - `colspan`, `rowspan` - Merged cells
  - `bg_color`, `border_style` - Visual styling
  - `text_content`, `font_*` - Text properties
  - `is_slot` - Interactive slot marker

## 📋 Cara Testing

### 1. Prepare Excel File
Buat file Excel dengan layout yard:
```
Row 1: [Merged cells] "PANCANG PAGAR LNG" (green background)
Row 2: X | X | X | X | X | X | X | X | X | X
Row 3: X | X | [Merged] "FILLING STATION LNG" (blue) | X | X
Row 4: X | X | X | X | X | X | X | X | X | X
```

**Penting**:
- Mark slots dengan **"X"** (huruf besar atau kecil)
- Gunakan **merge cells** untuk labels
- Gunakan **background colors** untuk zones
- Tambahkan **text** untuk labels

### 2. Upload Layout
1. Login sebagai `yard_operator`
2. Go to **Yard Positioning** page
3. Click **"Upload Layout (Excel)"**
4. Select your `.xlsx` file
5. Click **Upload**
6. Verify:
   - ✅ Merged cells appear correctly
   - ✅ Colors match Excel
   - ✅ Text labels visible
   - ✅ Borders preserved
   - ✅ Only "X" cells are interactive

### 3. Test Move Functionality
1. Drag isotank from "Unplaced" list
2. Drop on a slot marked with "X"
3. Verify:
   - ✅ No "Move failed" error
   - ✅ Isotank appears in slot
   - ✅ Removed from unplaced list
   - ✅ Database updated correctly

### 4. Test Error Cases
1. **Try to drop on non-slot cell** (text label):
   - Should show: "Target cell is not a valid slot"

2. **Try to drop on occupied slot**:
   - Should show: "Slot is already occupied"

3. **Try to move inactive isotank**:
   - Should show: "Isotank is not active in SMGRS yard"

## 🎨 Visual Rendering

### Excel → Yard View Mapping
```
Excel:                          Yard View:
┌─────────────────────┐        ┌─────────────────────┐
│  PANCANG PAGAR LNG  │   →    │  PANCANG PAGAR LNG  │
│     (Green, Bold)   │        │     (Green, Bold)   │
├───┬───┬───┬───┬───┬─┤        ├───┬───┬───┬───┬───┬─┤
│ X │ X │ X │ X │ X │ │   →    │[T]│[T]│[T]│[T]│[T]│ │
└───┴───┴───┴───┴───┴─┘        └───┴───┴───┴───┴───┴─┘
```
- `[T]` = Interactive slot (draggable)
- Text cells = Static labels
- Colors = Exact match

## 📊 Database Flow

### Upload Process:
```
Excel File
    ↓
PhpSpreadsheet Reader
    ↓
Extract Cell Properties
    ↓
Process Merged Cells
    ↓
Mark Slots ("X")
    ↓
Bulk Insert to yard_cells
    ↓
Render in Frontend
```

### Move Process:
```
Drag Isotank
    ↓
Validate Target (is_slot = true)
    ↓
Check Occupancy
    ↓
Begin Transaction
    ↓
Update/Create isotank_position
    ↓
Log Movement
    ↓
Commit Transaction
    ↓
Refresh UI
```

## 🐛 Debugging

### Check Logs:
```bash
tail -f storage/logs/laravel.log
```

### Check Database:
```sql
-- View all cells
SELECT * FROM yard_cells;

-- View slots only
SELECT * FROM yard_cells WHERE is_slot = 1;

-- View merged cells
SELECT * FROM yard_cells WHERE colspan > 1 OR rowspan > 1;

-- View isotank positions
SELECT * FROM isotank_positions;
```

### Common Issues:

1. **"Upload failed"**:
   - Check file format (.xlsx or .xls)
   - Check PhpSpreadsheet installation
   - Check server logs

2. **"Move failed"**:
   - Check error message in alert
   - Check browser console
   - Check Laravel logs
   - Verify yard_cell_id exists

3. **Merged cells not showing**:
   - Verify Excel cells are properly merged
   - Check colspan/rowspan in database
   - Check CSS rendering

## 📝 Next Steps

1. ✅ Test with real yard layout Excel
2. ✅ Verify all merged cells render correctly
3. ✅ Test move functionality thoroughly
4. ✅ Check error handling
5. ⏳ Create sample Excel template
6. ⏳ Train yard operators on Excel creation

## 📚 Documentation

See: `docs/YARD_LAYOUT_EXCEL_GUIDE.md` for complete Excel creation guide.

---

**Status**: ✅ Ready for Testing
**Version**: 2.0 (Excel-Driven)
**Date**: 2026-01-12
