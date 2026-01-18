import 'dart:io';
import 'package:path_provider/path_provider.dart';
import 'package:flutter/foundation.dart';

class FileManagerService {
  static const String _baseFolder = 'IsotankInspection';
  
  // Folder structure
  static const String _photosFolder = 'Photos';
  static const String _inspectionPhotosFolder = 'Inspection';
  static const String _maintenancePhotosFolder = 'Maintenance';
  static const String _pdfFolder = 'PDF';
  static const String _incomingPdfFolder = 'Incoming';
  static const String _outgoingPdfFolder = 'Outgoing';

  /// Get base directory for all app files
  static Future<Directory> _getBaseDirectory() async {
    Directory? baseDir;
    
    if (Platform.isAndroid) {
      // Use external storage for Android
      baseDir = await getExternalStorageDirectory();
    } else if (Platform.isIOS) {
      // Use documents directory for iOS
      baseDir = await getApplicationDocumentsDirectory();
    } else {
      // Fallback to application documents directory
      baseDir = await getApplicationDocumentsDirectory();
    }
    
    if (baseDir == null) {
      throw Exception('Could not get storage directory');
    }
    
    // Create base folder
    final appDir = Directory('${baseDir.path}/$_baseFolder');
    if (!await appDir.exists()) {
      await appDir.create(recursive: true);
      if (kDebugMode) {
        print('✅ Created base directory: ${appDir.path}');
      }
    }
    
    return appDir;
  }

  /// Get directory for inspection photos
  static Future<Directory> getInspectionPhotosDirectory() async {
    final baseDir = await _getBaseDirectory();
    final inspectionDir = Directory('${baseDir.path}/$_photosFolder/$_inspectionPhotosFolder');
    
    if (!await inspectionDir.exists()) {
      await inspectionDir.create(recursive: true);
      if (kDebugMode) {
        print('✅ Created inspection photos directory: ${inspectionDir.path}');
      }
    }
    
    return inspectionDir;
  }

  /// Get directory for maintenance photos
  static Future<Directory> getMaintenancePhotosDirectory() async {
    final baseDir = await _getBaseDirectory();
    final maintenanceDir = Directory('${baseDir.path}/$_photosFolder/$_maintenancePhotosFolder');
    
    if (!await maintenanceDir.exists()) {
      await maintenanceDir.create(recursive: true);
      if (kDebugMode) {
        print('✅ Created maintenance photos directory: ${maintenanceDir.path}');
      }
    }
    
    return maintenanceDir;
  }

  /// Get directory for incoming PDF
  static Future<Directory> getIncomingPdfDirectory() async {
    final baseDir = await _getBaseDirectory();
    final pdfDir = Directory('${baseDir.path}/$_pdfFolder/$_incomingPdfFolder');
    
    if (!await pdfDir.exists()) {
      await pdfDir.create(recursive: true);
      if (kDebugMode) {
        print('✅ Created incoming PDF directory: ${pdfDir.path}');
      }
    }
    
    return pdfDir;
  }

  /// Get directory for outgoing PDF
  static Future<Directory> getOutgoingPdfDirectory() async {
    final baseDir = await _getBaseDirectory();
    final pdfDir = Directory('${baseDir.path}/$_pdfFolder/$_outgoingPdfFolder');
    
    if (!await pdfDir.exists()) {
      await pdfDir.create(recursive: true);
      if (kDebugMode) {
        print('✅ Created outgoing PDF directory: ${pdfDir.path}');
      }
    }
    
    return pdfDir;
  }

  /// Save inspection photo with organized naming
  /// Format: INSPECTION_{ISO_NUMBER}_{PHOTO_TYPE}_{TIMESTAMP}.jpg
  static Future<File> saveInspectionPhoto(File photoFile, String isoNumber, String photoType) async {
    final dir = await getInspectionPhotosDirectory();
    final timestamp = DateTime.now().millisecondsSinceEpoch;
    final filename = 'INSPECTION_${isoNumber}_${photoType}_$timestamp.jpg';
    final newPath = '${dir.path}/$filename';
    
    final savedFile = await photoFile.copy(newPath);
    
    if (kDebugMode) {
      print('✅ Saved inspection photo: $newPath');
    }
    
    return savedFile;
  }

  /// Save maintenance photo with organized naming
  /// Format: MAINTENANCE_{ISO_NUMBER}_{JOB_ID}_{TIMESTAMP}.jpg
  static Future<File> saveMaintenancePhoto(File photoFile, String isoNumber, int jobId, {String? suffix}) async {
    final dir = await getMaintenancePhotosDirectory();
    final timestamp = DateTime.now().millisecondsSinceEpoch;
    final suffixPart = suffix != null ? '_$suffix' : '';
    final filename = 'MAINTENANCE_${isoNumber}_JOB${jobId}${suffixPart}_$timestamp.jpg';
    final newPath = '${dir.path}/$filename';
    
    final savedFile = await photoFile.copy(newPath);
    
    if (kDebugMode) {
      print('✅ Saved maintenance photo: $newPath');
    }
    
    return savedFile;
  }

  /// Get full storage structure info
  static Future<Map<String, dynamic>> getStorageInfo() async {
    final baseDir = await _getBaseDirectory();
    
    final inspectionPhotosDir = await getInspectionPhotosDirectory();
    final maintenancePhotosDir = await getMaintenancePhotosDirectory();
    final incomingPdfDir = await getIncomingPdfDirectory();
    final outgoingPdfDir = await getOutgoingPdfDirectory();
    
    // Count files in each directory
    final inspectionPhotosCount = await _countFiles(inspectionPhotosDir);
    final maintenancePhotosCount = await _countFiles(maintenancePhotosDir);
    final incomingPdfCount = await _countFiles(incomingPdfDir);
    final outgoingPdfCount = await _countFiles(outgoingPdfDir);
    
    return {
      'base_path': baseDir.path,
      'structure': {
        'Photos': {
          'Inspection': {
            'path': inspectionPhotosDir.path,
            'count': inspectionPhotosCount,
          },
          'Maintenance': {
            'path': maintenancePhotosDir.path,
            'count': maintenancePhotosCount,
          },
        },
        'PDF': {
          'Incoming': {
            'path': incomingPdfDir.path,
            'count': incomingPdfCount,
          },
          'Outgoing': {
            'path': outgoingPdfDir.path,
            'count': outgoingPdfCount,
          },
        },
      },
    };
  }

  /// Count files in a directory
  static Future<int> _countFiles(Directory dir) async {
    if (!await dir.exists()) return 0;
    
    try {
      final files = await dir.list().where((entity) => entity is File).toList();
      return files.length;
    } catch (e) {
      if (kDebugMode) {
        print('⚠️ Error counting files in ${dir.path}: $e');
      }
      return 0;
    }
  }

  /// Clean up old files (optional - for maintenance)
  static Future<void> cleanupOldFiles({int daysToKeep = 90}) async {
    final baseDir = await _getBaseDirectory();
    final cutoffDate = DateTime.now().subtract(Duration(days: daysToKeep));
    
    await _cleanupDirectory(baseDir, cutoffDate);
    
    if (kDebugMode) {
      print('✅ Cleanup completed. Files older than $daysToKeep days removed.');
    }
  }

  static Future<void> _cleanupDirectory(Directory dir, DateTime cutoffDate) async {
    if (!await dir.exists()) return;
    
    await for (var entity in dir.list(recursive: true)) {
      if (entity is File) {
        final stat = await entity.stat();
        if (stat.modified.isBefore(cutoffDate)) {
          try {
            await entity.delete();
            if (kDebugMode) {
              print('🗑️ Deleted old file: ${entity.path}');
            }
          } catch (e) {
            if (kDebugMode) {
              print('⚠️ Failed to delete ${entity.path}: $e');
            }
          }
        }
      }
    }
  }

  /// Print storage structure (for debugging)
  static Future<void> printStorageStructure() async {
    final info = await getStorageInfo();
    
    if (kDebugMode) {
      print('\n📁 ISOTANK INSPECTION STORAGE STRUCTURE');
      print('=' * 50);
      print('Base Path: ${info['base_path']}');
      print('\nStructure:');
      print('├── Photos/');
      print('│   ├── Inspection/ (${info['structure']['Photos']['Inspection']['count']} files)');
      print('│   │   └── ${info['structure']['Photos']['Inspection']['path']}');
      print('│   └── Maintenance/ (${info['structure']['Photos']['Maintenance']['count']} files)');
      print('│       └── ${info['structure']['Photos']['Maintenance']['path']}');
      print('└── PDF/');
      print('    ├── Incoming/ (${info['structure']['PDF']['Incoming']['count']} files)');
      print('    │   └── ${info['structure']['PDF']['Incoming']['path']}');
      print('    └── Outgoing/ (${info['structure']['PDF']['Outgoing']['count']} files)');
      print('        └── ${info['structure']['PDF']['Outgoing']['path']}');
      print('=' * 50);
    }
  }
}
