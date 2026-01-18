# Offline Support Implementation Guide

## Overview
Aplikasi Flutter ini telah dilengkapi dengan offline support untuk inspection forms menggunakan `DraftStorageService`.

## Services yang Tersedia

### 1. DraftStorageService
Service untuk menyimpan dan memuat draft inspection forms secara offline.

**Location:** `lib/data/services/draft_storage_service.dart`

**Methods:**
- `saveDraft(int jobId, Map<String, dynamic> formData)` - Simpan draft
- `loadDraft(int jobId)` - Muat draft
- `deleteDraft(int jobId)` - Hapus draft setelah submit berhasil
- `hasDraft(int jobId)` - Cek apakah draft ada
- `getAllDraftJobIds()` - Dapatkan semua draft job IDs
- `clearAllDrafts()` - Hapus semua draft

### 2. ConnectivityService
Service untuk memantau status koneksi (dasar).

**Location:** `lib/data/services/connectivity_service.dart`

**Note:** Untuk production, disarankan menggunakan package `connectivity_plus` untuk monitoring koneksi yang lebih reliable.

### 3. ConnectivityIndicator Widget
Widget untuk menampilkan status offline di UI.

**Location:** `lib/ui/widgets/connectivity_indicator.dart`

**Usage:**
```dart
ConnectivityIndicator()
```

## Cara Menggunakan di Inspection Form

### Contoh Implementasi:

```dart
import 'package:flutter/material.dart';
import '../../data/services/draft_storage_service.dart';
import '../../data/services/connectivity_service.dart';
import '../widgets/connectivity_indicator.dart';

class InspectionFormScreen extends StatefulWidget {
  final int jobId;
  
  @override
  _InspectionFormScreenState createState() => _InspectionFormScreenState();
}

class _InspectionFormScreenState extends State<InspectionFormScreen> {
  final Map<String, dynamic> _formData = {};
  bool _isOnline = true;
  
  @override
  void initState() {
    super.initState();
    _loadDraftIfExists();
    _checkConnectivity();
  }
  
  Future<void> _loadDraftIfExists() async {
    final draft = await DraftStorageService.loadDraft(widget.jobId);
    if (draft != null) {
      setState(() {
        _formData.addAll(draft);
      });
      
      // Show dialog to restore draft
      if (mounted) {
        showDialog(
          context: context,
          builder: (context) => AlertDialog(
            title: const Text('Draft Found'),
            content: const Text('Found a saved draft. Do you want to restore it?'),
            actions: [
              TextButton(
                onPressed: () {
                  Navigator.pop(context);
                  DraftStorageService.deleteDraft(widget.jobId);
                },
                child: const Text('No'),
              ),
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('Yes'),
              ),
            ],
          ),
        );
      }
    }
  }
  
  Future<void> _checkConnectivity() async {
    // Simple check - in production use connectivity_plus
    try {
      // Try a simple API call
      final isOnline = await ConnectivityService.instance.checkConnectivity();
      setState(() {
        _isOnline = isOnline;
      });
    } catch (e) {
      setState(() {
        _isOnline = false;
      });
    }
  }
  
  Future<void> _saveDraft() async {
    try {
      await DraftStorageService.saveDraft(widget.jobId, _formData);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Draft saved locally')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error saving draft: $e')),
        );
      }
    }
  }
  
  Future<void> _submitForm() async {
    try {
      // Try to submit
      await _apiService.submitInspection(widget.jobId, _formData);
      
      // If successful, delete draft
      await DraftStorageService.deleteDraft(widget.jobId);
      
      if (mounted) {
        Navigator.pop(context, true);
      }
    } catch (e) {
      // If offline or error, save as draft
      await _saveDraft();
      
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(_isOnline 
                ? 'Error submitting: $e' 
                : 'Offline - Draft saved. Will sync when online.'),
            backgroundColor: _isOnline ? Colors.red : Colors.orange,
          ),
        );
      }
    }
  }
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Inspection Form')),
      body: Column(
        children: [
          // Connectivity Indicator
          const ConnectivityIndicator(),
          
          // Form content
          Expanded(
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                // Form fields...
                
                // Save Draft Button (optional)
                if (!_isOnline)
                  OutlinedButton.icon(
                    onPressed: _saveDraft,
                    icon: const Icon(Icons.save),
                    label: const Text('Save Draft'),
                  ),
                
                // Submit Button
                ElevatedButton(
                  onPressed: _submitForm,
                  child: const Text('Submit'),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
```

## Auto-Save Draft (Optional)

Untuk auto-save draft setiap beberapa detik, bisa menggunakan Timer:

```dart
Timer? _autoSaveTimer;

@override
void initState() {
  super.initState();
  _autoSaveTimer = Timer.periodic(const Duration(seconds: 30), (timer) {
    _saveDraft();
  });
}

@override
void dispose() {
  _autoSaveTimer?.cancel();
  super.dispose();
}
```

## Sync Drafts Ketika Online (Future Enhancement)

Untuk sync semua drafts ketika kembali online:

```dart
Future<void> syncAllDrafts() async {
  final draftJobIds = await DraftStorageService.getAllDraftJobIds();
  
  for (final jobId in draftJobIds) {
    final draft = await DraftStorageService.loadDraft(jobId);
    if (draft != null) {
      try {
        await _apiService.submitInspection(jobId, draft);
        await DraftStorageService.deleteDraft(jobId);
      } catch (e) {
        // Handle error
      }
    }
  }
}
```

## Notes

1. **Storage:** Drafts disimpan menggunakan `SharedPreferences` (local storage)
2. **Limitations:** 
   - Photos tidak bisa disimpan di draft (hanya paths, tapi files perlu disimpan secara terpisah)
   - Untuk production, pertimbangkan menggunakan database lokal (sqflite) untuk draft yang lebih kompleks
3. **Connectivity:** Service saat ini basic, untuk production gunakan `connectivity_plus` package

## Package Recommendations

Untuk production, pertimbangkan untuk menambahkan:

```yaml
dependencies:
  connectivity_plus: ^5.0.0  # Better connectivity monitoring
  sqflite: ^2.3.0  # Local database for complex drafts
  path_provider: ^2.1.0  # File paths (already included)
```

