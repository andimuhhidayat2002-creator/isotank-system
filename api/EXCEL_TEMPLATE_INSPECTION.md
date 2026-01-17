# TEMPLATE EXCEL - BULK INSPECTION UPLOAD

## 📋 FORMAT EXCEL UNTUK OUTGOING INSPECTION

### Struktur Kolom (4 kolom):

| A: iso_number | B: planned_date | C: destination | D: receiver_name |
|---------------|-----------------|----------------|------------------|
| ISO-001       | 2026-01-15      | Singapore      | John Doe         |
| ISO-002       | 2026-01-16      | Malaysia       | Jane Smith       |
| ISO-003       | 2026-01-17      | Thailand       | Bob Wilson       |

---

## 📋 FORMAT EXCEL UNTUK INCOMING INSPECTION

### Struktur Kolom (2 kolom - destination & receiver_name TIDAK PERLU):

| A: iso_number | B: planned_date |
|---------------|-----------------|
| ISO-001       | 2026-01-15      |
| ISO-002       | 2026-01-16      |
| ISO-003       | 2026-01-17      |

---

## ✅ ATURAN VALIDASI

### Untuk OUTGOING INSPECTION:
1. ✅ **iso_number** - WAJIB, harus ada di master_isotanks
2. ✅ **planned_date** - Opsional, format: YYYY-MM-DD
3. ✅ **destination** - WAJIB untuk outgoing
4. ✅ **receiver_name** - WAJIB untuk outgoing (BARU!)

### Untuk INCOMING INSPECTION:
1. ✅ **iso_number** - WAJIB, harus ada di master_isotanks
2. ✅ **planned_date** - Opsional, format: YYYY-MM-DD

---

## 📝 CONTOH FILE EXCEL

### Outgoing Inspection Template:

```
Row 1 (Header):
iso_number | planned_date | destination | receiver_name

Row 2:
ISO-001 | 2026-01-15 | Singapore | John Doe

Row 3:
ISO-002 | 2026-01-16 | Malaysia | Jane Smith

Row 4:
ISO-003 | 2026-01-17 | Thailand | Bob Wilson
```

### Incoming Inspection Template:

```
Row 1 (Header):
iso_number | planned_date

Row 2:
ISO-001 | 2026-01-15

Row 3:
ISO-002 | 2026-01-16

Row 4:
ISO-003 | 2026-01-17
```

---

## 🚨 KESALAHAN UMUM

### Error: "receiver_name is required for outgoing inspection"
**Penyebab:** Kolom receiver_name kosong untuk outgoing inspection
**Solusi:** Isi kolom D (receiver_name) untuk semua baris outgoing

### Error: "destination is required for outgoing inspection"
**Penyebab:** Kolom destination kosong untuk outgoing inspection
**Solusi:** Isi kolom C (destination) untuk semua baris outgoing

### Error: "iso_number not found in master_isotanks"
**Penyebab:** ISO number tidak ada di database
**Solusi:** Pastikan ISO number sudah di-upload ke master isotanks terlebih dahulu

### Error: "Isotank is inactive"
**Penyebab:** Status isotank = inactive
**Solusi:** Aktifkan isotank terlebih dahulu melalui admin panel

---

## 📤 CARA UPLOAD

1. **Pilih Activity Type:**
   - Incoming Inspection
   - Outgoing Inspection

2. **Upload File Excel:**
   - Format: .xlsx atau .xls
   - Pastikan header row ada di baris pertama

3. **Review Result:**
   - Success count: Jumlah job yang berhasil dibuat
   - Failed count: Jumlah baris yang gagal
   - Failed rows: Detail error per baris

---

## 🔄 ALUR KERJA OUTGOING INSPECTION

```
1. Admin Upload Excel (dengan receiver_name & destination)
   ↓
2. System membuat Inspection Job (status: open)
   ↓
3. Inspector melakukan inspeksi
   ↓
4. Inspector submit inspection
   ↓
5. Receiver login dan confirm (ACCEPT/REJECT per item)
   ↓
6. Jika ALL ACCEPT:
   - Status = done
   - Location updated ke destination
   - PDF generated
   
   Jika ANY REJECT:
   - Status = receiver_rejected
   - Location TIDAK updated
   - PDF generated (dengan status rejected)
```

---

## 📊 CONTOH RESPONSE API

### Success Response:
```json
{
  "success": true,
  "message": "Bulk upload completed",
  "data": {
    "total_rows": 10,
    "success_count": 8,
    "failed_count": 2,
    "failed_rows": [
      {
        "row": 3,
        "iso_number": "ISO-999",
        "reason": "iso_number not found in master_isotanks"
      },
      {
        "row": 5,
        "iso_number": "ISO-005",
        "reason": "receiver_name is required for outgoing inspection"
      }
    ]
  }
}
```

---

## 💡 TIPS

1. **Gunakan Template:**
   - Download template Excel yang sudah disediakan
   - Jangan ubah urutan kolom

2. **Validasi Data:**
   - Pastikan semua ISO number sudah ada di master
   - Pastikan format tanggal benar (YYYY-MM-DD)
   - Untuk outgoing: WAJIB isi destination & receiver_name

3. **Batch Upload:**
   - Upload maksimal 100 baris per file
   - Untuk data besar, split menjadi beberapa file

4. **Error Handling:**
   - Jika ada error, perbaiki baris yang gagal
   - Upload ulang hanya baris yang gagal

---

**Last Updated:** 2026-01-10  
**Version:** 1.1 (Extension - Receiver Name Added)
