import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:image_picker/image_picker.dart';
import 'connectivity_service.dart';
import 'database_helper.dart';
import 'dart:convert';

class ApiService {
  // Singleton pattern
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  
  late Dio _dio;
  final ConnectivityService _connectivity = ConnectivityService();
  final DatabaseHelper _db = DatabaseHelper();
  
  // Helper to fix missing method error
  dynamic decodeJson(String source) => jsonDecode(source);
  
  
  // Production VPS Server
  String get _baseUrl {
    // Use production server for all platforms
    return 'http://202.10.44.146/api';
  }

  ApiService._internal() {
    _dio = Dio(BaseOptions(
      baseUrl: _baseUrl,
      connectTimeout: const Duration(seconds: 60),
      receiveTimeout: const Duration(seconds: 60),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    ));
    
    // Add interceptor for logging
    _dio.interceptors.add(LogInterceptor(
      requestBody: true, 
      responseBody: true,
    ));
  }

  void setToken(String token) {
    _dio.options.headers['Authorization'] = 'Bearer $token';
    if (kDebugMode) {
      print('✅ Token set: Bearer ${token.substring(0, 20)}...');
      print('✅ Current headers: ${_dio.options.headers}');
    }
  }

  // Admin Methods
  Future<Map<String, dynamic>> getAdminDashboard() async {
    try {
      final response = await _dio.get('/admin/dashboard');
      return response.data['data'];
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  // Auth Methods
  Future<Map<String, dynamic>> login(String email, String password) async {
    try {
      final response = await _dio.post('/login', data: {
        'email': email,
        'password': password,
      });
      return response.data;
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Future<void> logout() async {
    try {
      await _dio.post('/logout');
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  // Inspector Methods
  Future<List<dynamic>> getInspectorJobs() async {
    try {
      if (!_connectivity.isOnline) {
         throw DioException(requestOptions: RequestOptions(path: '/inspector/jobs'), type: DioExceptionType.connectionError);
      }
      // Try to fetch from server
      final response = await _dio.get('/inspector/jobs');
      final jobs = response.data['data']['data']; // Laravel pagination structure
      
      // Cache the jobs for offline use
      for (final job in jobs) {
        await _db.cacheJob(job['id'], 'inspection', job);
      }
      
      if (kDebugMode) {
        print('✅ Cached ${jobs.length} inspection jobs');
      }
      
      return jobs;
    } on DioException catch (e) {
      // If offline or network error, try to load from cache
      if (e.type == DioExceptionType.connectionTimeout || 
          e.type == DioExceptionType.receiveTimeout ||
          e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.unknown ||
          !_connectivity.isOnline) {
        
        if (kDebugMode) {
          print('📱 Loading inspection jobs from cache (offline mode)');
        }
        
        // Load all cached jobs
        final db = await _db.database;
        final results = await db.query('cached_jobs', where: 'type = ?', whereArgs: ['inspection']);
        
        if (results.isEmpty) {
          if (!_connectivity.isOnline) {
             // Return empty list instead of exception for smoother UX in dashboard
             return [];
          }
          throw Exception('No cached data available. Please connect to internet first.');
        }
        
        final jobs = results.map((row) {
          return _db.decodeJson(row['data'] as String);
        }).toList();
        
        return jobs;
      }
      
      throw _handleError(e);
    }
  }

  Future<Map<String, dynamic>> getInspectionJobDetails(int id) async {
    try {
      if (!_connectivity.isOnline) {
         throw DioException(requestOptions: RequestOptions(path: '/inspector/jobs/$id'), type: DioExceptionType.connectionError);
      }
      // Try to fetch from server
      final response = await _dio.get('/inspector/jobs/$id');
      final data = response.data['data'];
      
      // Cache the job details for offline use
      await _db.cacheJob(id, 'inspection_detail', data);
      
      return data;
      return data;
    } catch (e) { // Catch ALL errors, not just DioException
      // If offline or network error, try to load from cache
      bool isNetworkError = !_connectivity.isOnline;
      if (e is DioException) {
         if (e.type == DioExceptionType.connectionTimeout || 
             e.type == DioExceptionType.receiveTimeout ||
             e.type == DioExceptionType.connectionError ||
             e.type == DioExceptionType.unknown) {
           isNetworkError = true;
         }
      }

      if (isNetworkError) {
            
        if (kDebugMode) {
          print('📱 Loading inspection detail $id from cache (offline mode)');
        }
        
        final db = await _db.database;
        final results = await db.query(
          'cached_jobs',
          where: 'id = ? AND type = ?',
          whereArgs: [id, 'inspection_detail'],
        );
        
        if (results.isNotEmpty) {
           return _db.decodeJson(results.first['data'] as String);
        }
        
        // Fallback: try to find in list cache if detail not found (better than nothing)
        final resultsList = await db.query(
          'cached_jobs',
          where: 'id = ? AND type = ?',
          whereArgs: [id, 'inspection'],
        );
        
        if (resultsList.isNotEmpty) {
           return _db.decodeJson(resultsList.first['data'] as String);
        }

        // CUSTOM EXCEPTION MESSAGE
        throw Exception('Offline: Data for Job #$id not found in local storage. Did you download offline data?');
      }
      
      if (e is DioException) throw _handleError(e);
      throw e;
    }
  }

  Future<void> submitInspection(int jobId, Map<String, dynamic> data) async {
    // Check if offline
    if (!_connectivity.isOnline) {
      // Extract photo paths for offline storage
      final Map<String, String> photos = {};
      data.forEach((key, value) {
        if (key.startsWith('photo_') && value is String && value.isNotEmpty) {
          photos[key] = value;
        }
      });
      
      // Save to local database for later sync
      await _db.savePendingInspection(jobId, data, photos.isNotEmpty ? photos : null);
      if (kDebugMode) {
        print('📥 Inspection saved offline for job $jobId with ${photos.length} photos');
      }
      return; // Don't throw error, just save locally
    }
    
    try {
      final formData = FormData();
      
      for (final entry in data.entries) {
        final key = entry.key;
        final value = entry.value;
        
        if (value == null) continue;
        
        if (key.startsWith('photo_')) {
          if (value is XFile) {
             // Handle XFile (Web/Mobile safe)
             final bytes = await value.readAsBytes();
             formData.files.add(MapEntry(
               key,
               MultipartFile.fromBytes(bytes, filename: value.name),
             ));
          } else if (value is String && value.isNotEmpty && !value.contains('placeholder')) {
             // Fallback for String paths (Mobile only)
             // Check if it's a blob URL (Web), if so, skip or we can't upload it easily here without bytes
             if (value.startsWith('blob:')) {
               debugPrint('Skipping blob URL upload: $value. Use XFile instead.');
               continue;
             }
             formData.files.add(MapEntry(
               key,
               await MultipartFile.fromFile(value, filename: value.split('/').last),
             ));
          }
        } else {
          formData.fields.add(MapEntry(key, value.toString()));
        }
      }

      await _dio.post('/inspector/jobs/$jobId/submit', data: formData);
    } on DioException catch (e) {
      // If network error, save offline
      if (e.type == DioExceptionType.connectionTimeout || 
          e.type == DioExceptionType.receiveTimeout ||
          e.type == DioExceptionType.connectionError) {
        // Extract photo paths
        final Map<String, String> photos = {};
        data.forEach((key, value) {
          if (key.startsWith('photo_') && value is String && value.isNotEmpty) {
            photos[key] = value;
          }
        });
        
        await _db.savePendingInspection(jobId, data, photos.isNotEmpty ? photos : null);
        if (kDebugMode) {
          print('📥 Inspection saved offline due to network error for job $jobId with ${photos.length} photos');
        }
        return; // Don't throw, saved offline
      }
      throw _handleError(e);
    }
  }

  // Maintenance Methods
  Future<List<dynamic>> getMaintenanceJobs() async {
    try {
      // Try to fetch from server
      final response = await _dio.get('/maintenance/jobs');
      final jobs = response.data['data']['data'];
      
      // Cache the jobs for offline use
      for (final job in jobs) {
        await _db.cacheJob(job['id'], 'maintenance', job);
      }
      
      if (kDebugMode) {
        print('✅ Cached ${jobs.length} maintenance jobs');
      }
      
      return jobs;
    } on DioException catch (e) {
      // If offline or network error, try to load from cache
      if (e.type == DioExceptionType.connectionTimeout || 
          e.type == DioExceptionType.receiveTimeout ||
          e.type == DioExceptionType.connectionError ||
          !_connectivity.isOnline) {
            
        if (kDebugMode) {
          print('📱 Loading maintenance jobs from cache (offline mode)');
        }
        
        final db = await _db.database;
        final results = await db.query('cached_jobs', where: 'type = ?', whereArgs: ['maintenance']);
        
        if (results.isEmpty) {
          throw Exception('No cached maintenance data available.');
        }
        
        final jobs = results.map((row) {
          return _db.decodeJson(row['data'] as String);
        }).toList();
        
        return jobs;
      }
      
      throw _handleError(e);
    }
  }

  // Vacuum Suction Methods
  Future<List<dynamic>> getVacuumActivities() async {
    try {
      final response = await _dio.get('/maintenance/vacuum-activities');
      final items = response.data['data'];
      
      // Cache items
      for (final item in items) {
        // Assuming item has 'id'
        if (item['id'] != null) {
          await _db.cacheJob(item['id'], 'vacuum', item);
        }
      }
      
      return items;
    } on DioException catch (e) {
       // If offline or network error, try to load from cache
      if (e.type == DioExceptionType.connectionTimeout || 
          e.type == DioExceptionType.receiveTimeout ||
          e.type == DioExceptionType.connectionError ||
          !_connectivity.isOnline) {
            
        if (kDebugMode) {
          print('📱 Loading vacuum activities from cache (offline mode)');
        }
        
        final db = await _db.database;
        final results = await db.query('cached_jobs', where: 'type = ?', whereArgs: ['vacuum']);
        
        if (results.isEmpty) {
          // If empty, return empty list instead of error for smoother UX
          return [];
        }
        
        final items = results.map((row) {
          return decodeJson(row['data'] as String);
        }).toList();
        
        return items;
      }
      throw _handleError(e);
    }
  }

  Future<void> updateVacuumActivity(int id, Map<String, dynamic> data) async {
    // Check if offline
    if (!_connectivity.isOnline) {
      await _db.savePendingVacuumActivity(id, data);
      if (kDebugMode) {
        print('📥 Vacuum activity saved offline for activity $id');
      }
      return; // Don't throw error, just save locally
    }

    try {
      await _dio.put('/maintenance/vacuum-activities/$id', data: data);
    } on DioException catch (e) {
      // If network error, save offline
      if (e.type == DioExceptionType.connectionTimeout || 
          e.type == DioExceptionType.receiveTimeout ||
          e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.unknown) {
        
        await _db.savePendingVacuumActivity(id, data);
        if (kDebugMode) {
          print('📥 Vacuum activity saved offline due to network error for activity $id');
        }
        return;
      }
      throw _handleError(e);
    }
  }

  Future<void> updateCalibration(int id, Map<String, dynamic> data) async {
    // Check if offline
    if (!_connectivity.isOnline) {
      await _db.savePendingCalibration(id, data);
      if (kDebugMode) {
        print('📥 Calibration saved offline for job $id');
      }
      return; 
    }

    try {
      await _dio.put('/maintenance/calibration/$id', data: data);
    } on DioException catch (e) {
       // If network error, save offline
      if (e.type == DioExceptionType.connectionTimeout || 
          e.type == DioExceptionType.receiveTimeout ||
          e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.unknown) {
        
        await _db.savePendingCalibration(id, data);
        if (kDebugMode) {
          print('📥 Calibration saved offline due to network error for job $id');
        }
        return;
      }
      throw _handleError(e);
    }
  }

  Future<void> updateMaintenanceStatus(
    int jobId, 
    String status, {
    String? workDescription, 
    dynamic photoDuring,
    dynamic afterPhoto, 
    String? sparepart, 
    int? qty
  }) async {
    // CHECK OFFLINE
    if (!_connectivity.isOnline) {
      final Map<String, dynamic> data = {
        'work_description': workDescription,
        'sparepart': sparepart,
        'qty': qty,
      };
      
      final Map<String, String> photos = {};
      if (photoDuring != null) {
        if (photoDuring is XFile) photos['photo_during'] = photoDuring.path;
        else if (photoDuring is String) photos['photo_during'] = photoDuring;
      }
      if (afterPhoto != null) {
        if (afterPhoto is XFile) photos['after_photo'] = afterPhoto.path;
        else if (afterPhoto is String) photos['after_photo'] = afterPhoto;
      }
      
      await _db.savePendingMaintenance(jobId, status, data, photos.isNotEmpty ? photos : null);
      if (kDebugMode) print('📥 Maintenance saved offline for job $jobId');
      return;
    }

    try {
      final formData = FormData.fromMap({
        'status': status,
        if (workDescription != null) 'work_description': workDescription,
        if (sparepart != null) 'sparepart': sparepart,
        if (qty != null) 'qty': qty,
        '_method': 'PUT', // Laravel method spoofing
      });

      // Handle photo_during (optional, for on_progress status)
      if (photoDuring != null) {
        if (photoDuring is XFile) {
          final bytes = await photoDuring.readAsBytes();
          formData.files.add(MapEntry(
            'photo_during',
            MultipartFile.fromBytes(bytes, filename: photoDuring.name),
          ));
        } else if (photoDuring is String && !photoDuring.startsWith('blob:') && !photoDuring.startsWith('http')) {
          formData.files.add(MapEntry(
            'photo_during',
            await MultipartFile.fromFile(photoDuring, filename: photoDuring.split('/').last),
          ));
        }
      }

      // Handle after_photo (optional, for closed status)
      if (afterPhoto != null) {
        if (afterPhoto is XFile) {
           final bytes = await afterPhoto.readAsBytes();
           formData.files.add(MapEntry(
             'after_photo',
             MultipartFile.fromBytes(bytes, filename: afterPhoto.name),
           ));
        } else if (afterPhoto is String && !afterPhoto.startsWith('blob:') && !afterPhoto.startsWith('http')) {
           formData.files.add(MapEntry(
             'after_photo',
             await MultipartFile.fromFile(afterPhoto, filename: afterPhoto.split('/').last),
           ));
        }
      }

      await _dio.post('/maintenance/jobs/$jobId/status', data: formData);
    } on DioException catch (e) {
      // If network error, save offline
      if (e.type == DioExceptionType.connectionTimeout || 
          e.type == DioExceptionType.receiveTimeout ||
          e.type == DioExceptionType.connectionError) {
            
        final Map<String, dynamic> data = {
          'work_description': workDescription,
          'sparepart': sparepart,
          'qty': qty,
        };
        
        final Map<String, String> photos = {};
        if (photoDuring != null) {
          if (photoDuring is XFile) photos['photo_during'] = photoDuring.path;
          else if (photoDuring is String) photos['photo_during'] = photoDuring;
        }
        if (afterPhoto != null) {
          if (afterPhoto is XFile) photos['after_photo'] = afterPhoto.path;
          else if (afterPhoto is String) photos['after_photo'] = afterPhoto;
        }
        
        await _db.savePendingMaintenance(jobId, status, data, photos.isNotEmpty ? photos : null);
        if (kDebugMode) print('📥 Maintenance saved offline due to network error for job $jobId');
        return;
      }
      throw _handleError(e);
    }
  }


  // Generic Error Handling
  String _handleError(DioException error) {
    if (error.response != null) {
      if (error.response?.data is Map && error.response?.data['message'] != null) {
        return error.response?.data['message'];
      }
      return 'Server Error: ${error.response?.statusCode}';
    }
    return 'Connection Error';
  }

  Future<void> receiverConfirm(int jobId, Map<String, dynamic> data) async {
    try {
      await _dio.post('/inspector/jobs/$jobId/receiver-confirm', data: data);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Future<void> uploadPdf(int jobId, String pdfFilePath) async {
    try {
      final formData = FormData.fromMap({
        'pdf': await MultipartFile.fromFile(pdfFilePath, filename: pdfFilePath.split('/').last),
      });

      await _dio.post('/inspector/jobs/$jobId/upload-pdf', data: formData);
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  // Receiver Methods
  Future<Map<String, dynamic>> getInspectionForReceiver(int jobId) async {
    try {
      final response = await _dio.get('/inspector/jobs/$jobId/receiver-details');
      return response.data['data'];
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Future<Map<String, dynamic>> submitReceiverConfirmations(int jobId, FormData formData) async {
    try {
      final response = await _dio.post('/inspector/jobs/$jobId/receiver-confirm', data: formData);
      return response.data;
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  // Shared Methods
  Future<List<dynamic>> searchIsotanks(String query) async {
    try {
      // Assuming the backend supports ?search= parameter on /isotanks from previous backend edits
      final response = await _dio.get('/isotanks', queryParameters: {'search': query});
      if (response.data is Map && response.data.containsKey('data')) {
         // Pagination wrapper
         return response.data['data'];
      }
      return response.data; // List direct
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  Future<List<dynamic>> getYardLayout() async {
    try {
      if (!_connectivity.isOnline) {
        throw DioException(
          requestOptions: RequestOptions(path: '/yard/layout'), 
          type: DioExceptionType.connectionError
        );
      }

      final response = await _dio.get('/yard/layout');
      final data = response.data['data']; 
      
      // Cache the layout
      await _db.cacheJob(0, 'yard_layout', {'slots': data});
      
      return data;
    } on DioException catch (e) {
       if (e.type == DioExceptionType.connectionTimeout || 
          e.type == DioExceptionType.receiveTimeout ||
          e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.unknown ||
          !_connectivity.isOnline) {
          
          final db = await _db.database;
          final results = await db.query('cached_jobs', where: 'id = ? AND type = ?', whereArgs: [0, 'yard_layout']);
          
          if (results.isNotEmpty) {
            final cached = decodeJson(results.first['data'] as String);
            return cached['slots'] ?? [];
          }
       }
       throw _handleError(e);
    }
  }

  Future<Map<String, dynamic>> getYardPositions() async {
    try {
      if (!_connectivity.isOnline) {
        throw DioException(
          requestOptions: RequestOptions(path: '/yard/positions'), 
          type: DioExceptionType.connectionError
        );
      }

      final response = await _dio.get('/yard/positions');
      final data = response.data['data'];
      
      // Cache positions
      await _db.cacheJob(0, 'yard_positions', data);
      
      return data;
    } on DioException catch (e) {
      if (e.type == DioExceptionType.connectionTimeout || 
          e.type == DioExceptionType.receiveTimeout ||
          e.type == DioExceptionType.connectionError ||
          e.type == DioExceptionType.unknown ||
          !_connectivity.isOnline) {
          
          final db = await _db.database;
          final results = await db.query('cached_jobs', where: 'id = ? AND type = ?', whereArgs: [0, 'yard_positions']);
          
          if (results.isNotEmpty) {
            return decodeJson(results.first['data'] as String);
          }
       }
       throw _handleError(e);
    }
  }

  // Inspection Items Method
  Future<List<dynamic>> getInspectionItems() async {
    try {
      // Try to fetch from server
      final response = await _dio.get('/inspection-items');
      final items = response.data['data'];
      
      // Cache items for offline use
      await _db.cacheJob(0, 'inspection_items', {'items': items});
      
      return items;
    } on DioException catch (e) {
      // If offline or network error, try to load from cache
      if (e.type == DioExceptionType.connectionTimeout || 
          e.type == DioExceptionType.receiveTimeout ||
          e.type == DioExceptionType.connectionError ||
          !_connectivity.isOnline) {
          
          final db = await _db.database;
          final results = await db.query('cached_jobs', where: 'id = ? AND type = ?', whereArgs: [0, 'inspection_items']);
          
          if (results.isNotEmpty) {
             final cached = decodeJson(results.first['data'] as String);
             return cached['items'] ?? [];
          }
          // If no cache, return empty list (or fall back to hardcoded defaults in UI)
          return [];
       }
       throw _handleError(e);
    }
  }
  // Calibration methods
  Future<List<dynamic>> getCalibrationJobs() async {
    try {
      final response = await _dio.get('/maintenance/calibration');
      // Assume API returns { data: [...] } or just List
      
      var items = [];
      if (response.data is List) {
        items = response.data;
      } else if (response.data is Map && response.data['data'] is List) {
        items = response.data['data'];
      }
      
      // Cache items
      for (final item in items) {
         if (item['id'] != null) {
           await _db.cacheJob(item['id'], 'calibration', item);
         }
      }
      
      return items;
    } on DioException catch (e) {
      if (e.type == DioExceptionType.connectionTimeout || 
          e.type == DioExceptionType.receiveTimeout ||
          e.type == DioExceptionType.connectionError ||
          !_connectivity.isOnline) {
          
          final db = await _db.database;
          final results = await db.query('cached_jobs', where: 'type = ?', whereArgs: ['calibration']);
          
          if (results.isNotEmpty) {
             return results.map((row) => decodeJson(row['data'] as String)).toList();
          }
          return [];
       }
       throw _handleError(e);
    }
  }


  String get baseUrl => _baseUrl;
  
  // Getter for dio instance (for use in services that need it)
  Dio get dio => _dio;

  // Generic Get Method
  Future<dynamic> get(String path, {Map<String, dynamic>? queryParameters}) async {
    try {
      final response = await _dio.get(path, queryParameters: queryParameters);
      if (response.data is Map && response.data.containsKey('data')) {
           return response.data['data']; // Default unwrap
      }
      return response.data;
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }

  // Generic Put Method
  Future<dynamic> put(String path, dynamic data) async {
    try {
      final response = await _dio.put(path, data: data);
      return response.data;
    } on DioException catch (e) {
      throw _handleError(e);
    }
  }
}
