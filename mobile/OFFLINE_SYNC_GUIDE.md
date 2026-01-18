# Fitur Offline & Auto-Sync

## Overview
Aplikasi Isotank sekarang mendukung mode offline dengan auto-sync otomatis saat koneksi kembali.

## Fitur Utama

### 1. **Deteksi Koneksi Otomatis**
- Aplikasi secara otomatis mendeteksi status koneksi internet
- Menampilkan banner orange saat offline
- Monitoring real-time perubahan koneksi

### 2. **Penyimpanan Data Offline**
Data yang disimpan offline:
- Inspection submissions (incoming & outgoing)
- Maintenance updates
- Receiver confirmations

### 3. **Auto-Sync**
- Otomatis sync data saat koneksi kembali
- Retry mechanism untuk data yang gagal
- Background sync tanpa mengganggu user

### 4. **Cache Data**
- Job details di-cache untuk viewing offline
- Cache otomatis dibersihkan setelah 7 hari

## Cara Kerja

### Saat Offline:
1. User tetap bisa mengisi form inspection
2. Data disimpan ke database lokal (SQLite)
3. Muncul notifikasi "Saved Offline"
4. Banner orange muncul di atas form

### Saat Online Kembali:
1. Aplikasi otomatis detect koneksi
2. Sync service mulai upload data pending
3. Data berhasil sync akan dihapus dari local
4. User mendapat notifikasi sukses

## Technical Details

### Dependencies Baru:
```yaml
sqflite: ^2.3.3+1        # Local database
connectivity_plus: ^6.1.1 # Network monitoring
uuid: ^4.5.1              # Unique IDs
```

### Services:
1. **DatabaseHelper** - Manage local SQLite database
2. **ConnectivityService** - Monitor network status
3. **SyncService** - Handle auto-sync logic
4. **ApiService** - Enhanced with offline support

### Database Tables:
- `pending_inspections` - Inspection data pending upload
- `pending_maintenance` - Maintenance updates pending
- `pending_receiver_confirmations` - Receiver confirmations pending
- `cached_jobs` - Cached job details for offline viewing

## Limitasi

1. **Photos**: Saat ini photo paths disimpan, tapi file photo harus tetap ada di device
2. **PDF Generation**: PDF hanya generate saat online
3. **Cache Size**: Perlu monitoring untuk cache yang terlalu besar

## Future Improvements

1. Compress photos untuk offline storage
2. Partial sync (sync per item)
3. Conflict resolution untuk data yang berubah di server
4. Manual sync trigger
5. Sync status indicator dengan progress

## Testing

### Test Offline Mode:
1. Aktifkan Airplane Mode di device
2. Isi form inspection
3. Submit - akan muncul "Saved Offline"
4. Matikan Airplane Mode
5. Data otomatis ter-sync

### Verify Sync:
1. Check console logs untuk "🔄 Starting sync..."
2. Check "✅ Synced inspection for job X"
3. Verify data di admin panel

## Troubleshooting

### Data tidak sync?
- Check console logs untuk error messages
- Verify API connection
- Check retry_count di database

### Banner offline tidak muncul?
- Verify ConnectivityService initialized di main.dart
- Check connectivity_plus permissions

### Database error?
- Clear app data dan reinstall
- Check database migration version
