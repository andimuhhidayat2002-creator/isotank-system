# ✅ FIX: Dashboard Status Counts & Legacy Logic

## 🎯 Masalah

### 1. Dashboard (Location Breakdown) Salah Hitung
**User Report:** "Location Breakdown (Drill-down)" di dashboard menampilkan "2 Filled, 0 Empty" padahal seharusnya 1 Filled, 1 Ongoing Inspection (Empty).

**Root Cause:**
Query SQL sebelumnya menganggap **semua yang statusnya bukan 'empty'** sebagai **filled**.
```sql
-- OLD LOGIC
sum(case when code = 'empty' then 1 else 0 end) as empty_count
sum(case when code != 'empty' ... then 1 else 0 end) as filled_count
```
Karena status kita sekarang `ready_to_fill`, `ongoing_inspection`, dll (bukan string literal 'empty'), maka semuanya masuk ke `filled_count`.

### 2. Legacy Grouping Logic Salah
Di `locationDetail`, logic lama mengelompokkan `under_maintenance`, `waiting_team_calibration`, `class_survey` ke dalam **Filled**.

---

## ✅ Solusi

### 1. Update SQL Query (AdminController::index)
Logic baru:
- **Active Count:** Total isotank active
- **Empty Count:** `filling_status_code != 'filled'` (dan tidak null)
  - Termasuk: `ready_to_fill`, `ongoing_inspection`, `under_maintenance`, dll.
- **Filled Count:** `filling_status_code = 'filled'` (Strict)

```php
->selectRaw("sum(case when filling_status_code != 'filled' ... then 1 else 0 end) as empty_count")
->selectRaw("sum(case when filling_status_code = 'filled' then 1 else 0 end) as filled_count")
```

### 2. Update Legacy Logic (AdminController::locationDetail)
Logic baru untuk backward compatibility:
- **Empty:** `ready_to_fill`, `ongoing_inspection`, `under_maintenance`, `waiting_team_calibration`, `class_survey`
- **Filled:** `filled` ONLY.

---

## 📊 Hasil yang Diharapkan

**Dashboard:**
```
SMGRS
2 Active

FILLED: 1 (Blue)
EMPTY:  1 (Ongoing Inspection count as empty)
```

**Location Detail:**
```
Filled Isotanks: 1
Empty Isotanks:  1
Unspecified:     0
```

---

## 🔄 Silakan Refresh Browser

1. Clear Cache (Laravel cache sudah di-clear)
2. Refresh browser

Data sekarang seharusnya sudah akurat! 🚀
