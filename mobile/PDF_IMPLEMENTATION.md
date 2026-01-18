# PDF & File Storage Implementation

## Overview
Implementasi lengkap untuk:
1.  **PDF Generation**: Membuat laporan inspeksi (Incoming/Outgoing) dalam format PDF A4 secara lokal.
2.  **File Storage**: Mengorganisir penyimpanan file (Foto & PDF) dalam struktur folder yang rapi.
3.  **PDF Upload**: Mengunggah file PDF yang di-generate ke server Laravel agar tersimpan secara online.

## 1. File Storage Structure
Lokasi penyimpanan di device (Android/iOS):
```
IsotankInspection/
├── Photos/
│   ├── Inspection/   -> Foto-foto saat inspeksi
│   └── Maintenance/  -> Foto bukti perbaikan
└── PDF/
    ├── Incoming/     -> Laporan PDF Incoming Inspection
    └── Outgoing/     -> Laporan PDF Outgoing Inspection
```

## 2. PDF Features
*   **Format**: A4 Portrait
*   **Header**: Logo perusahaan (PT Kayan LNG Nusantara)
*   **Content**: Detail inspeksi lengkap, signatures, status color-coded
*   **Auto-Open**: PDF otomatis terbuka setelah dibuat
*   **Auto-Upload**: PDF otomatis terunggah ke server setelah dibuat

## 3. Upload Flow
### Incoming Inspection
1.  Inspector klik "FINAL SUBMIT"
2.  Data inspeksi tersimpan ke server
3.  PDF di-generate lokal (`PDF/Incoming/...`)
4.  PDF otomatis di-upload ke endpoint `/api/inspector/jobs/{id}/upload-pdf`
5.  Database `inspection_logs` diupdate dengan path PDF (`pdf_path`)
6.  Snackbar konfirmasi muncul: "Inspection Submitted & PDF Uploaded!"

### Outgoing Inspection
1.  Receiver klik "CONFIRM & DISPATCH ISOTANK"
2.  Data konfirmasi tersimpan ke server
3.  PDF di-generate lokal (`PDF/Outgoing/...`)
4.  PDF otomatis di-upload ke endpoint `/api/inspector/jobs/{id}/upload-pdf`
5.  Database `inspection_logs` diupdate dengan path PDF
6.  Snackbar konfirmasi muncul

## 4. Backend Integration
*   **Migration**: Added `pdf_path` column to `inspection_logs` table.
*   **Endpoint**: `POST /api/inspector/jobs/{id}/upload-pdf`
*   **Controller**: `InspectionSubmitController@uploadPdf`
    *   Validasi file PDF (max 10MB)
    *   Simpan di `storage/app/public/inspection_pdfs`
    *   Update record database

## 5. Technical Notes
*   **Offline Capability**: PDF dibuat lokal sehingga selalu tersedia di HP inspector.
*   **Online Sync**: Upload memastikan admin memiliki salinan laporan yang sama persis.
*   **Error Handling**: Jika upload gagal (misal koneksi putus), user diberi notifikasi via Snackbar, tapi proses submit data utama tetap berhasil (tidak memblokir operasional).
