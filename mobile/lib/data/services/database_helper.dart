import 'package:sqflite/sqflite.dart';
import 'package:path/path.dart';
import 'dart:convert';

class DatabaseHelper {
  static final DatabaseHelper _instance = DatabaseHelper._internal();
  factory DatabaseHelper() => _instance;
  
  static Database? _database;
  
  DatabaseHelper._internal();
  
  Future<Database> get database async {
    if (_database != null) return _database!;
    _database = await _initDatabase();
    return _database!;
  }
  
  Future<Database> _initDatabase() async {
    String path = join(await getDatabasesPath(), 'isotank_offline.db');
    
    return await openDatabase(
      path,
      version: 3,
      onCreate: _onCreate,
      onUpgrade: _onUpgrade,
    );
  }

  Future<void> _onUpgrade(Database db, int oldVersion, int newVersion) async {
    if (oldVersion < 2) {
      // Fix cache table schema issue (ID collision)
      await db.execute('DROP TABLE IF EXISTS cached_jobs');
      await db.execute('''
        CREATE TABLE cached_jobs (
          key_id INTEGER PRIMARY KEY AUTOINCREMENT,
          id INTEGER NOT NULL,
          type TEXT NOT NULL,
          data TEXT NOT NULL,
          cached_at TEXT NOT NULL,
          UNIQUE(id, type)
        )
      ''');
    }
    
    if (oldVersion < 3) {
      // Add tables for Vacuum and Calibration offline support
       await db.execute('''
        CREATE TABLE pending_vacuum_activities (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          activity_id INTEGER NOT NULL,
          data TEXT NOT NULL,
          created_at TEXT NOT NULL,
          retry_count INTEGER DEFAULT 0
        )
      ''');

       await db.execute('''
        CREATE TABLE pending_calibration_activities (
          id INTEGER PRIMARY KEY AUTOINCREMENT,
          job_id INTEGER NOT NULL,
          data TEXT NOT NULL,
          created_at TEXT NOT NULL,
          retry_count INTEGER DEFAULT 0
        )
      ''');
    }
  }
  
  Future<void> _onCreate(Database db, int version) async {
    // Table untuk menyimpan inspection submissions yang pending
    await db.execute('''
      CREATE TABLE pending_inspections (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        job_id INTEGER NOT NULL,
        data TEXT NOT NULL,
        photos TEXT,
        created_at TEXT NOT NULL,
        retry_count INTEGER DEFAULT 0
      )
    ''');
    
    // Table untuk menyimpan maintenance updates yang pending
    await db.execute('''
      CREATE TABLE pending_maintenance (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        job_id INTEGER NOT NULL,
        status TEXT NOT NULL,
        data TEXT NOT NULL,
        photos TEXT,
        created_at TEXT NOT NULL,
        retry_count INTEGER DEFAULT 0
      )
    ''');
    
    // Table untuk menyimpan receiver confirmations yang pending
    await db.execute('''
      CREATE TABLE pending_receiver_confirmations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        job_id INTEGER NOT NULL,
        data TEXT NOT NULL,
        created_at TEXT NOT NULL,
        retry_count INTEGER DEFAULT 0
      )
    ''');
    
    // Table untuk cache data jobs (untuk offline viewing)
    await db.execute('''
      CREATE TABLE cached_jobs (
        key_id INTEGER PRIMARY KEY AUTOINCREMENT,
        id INTEGER NOT NULL,
        type TEXT NOT NULL,
        data TEXT NOT NULL,
        cached_at TEXT NOT NULL,
        UNIQUE(id, type)
      )
    ''');

    // New tables for Vacuum & Calibration (Version 3)
    await db.execute('''
      CREATE TABLE pending_vacuum_activities (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        activity_id INTEGER NOT NULL,
        data TEXT NOT NULL,
        created_at TEXT NOT NULL,
        retry_count INTEGER DEFAULT 0
      )
    ''');

     await db.execute('''
      CREATE TABLE pending_calibration_activities (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        job_id INTEGER NOT NULL,
        data TEXT NOT NULL,
        created_at TEXT NOT NULL,
        retry_count INTEGER DEFAULT 0
      )
    ''');
  }
  
  // Inspection Methods
  Future<int> savePendingInspection(int jobId, Map<String, dynamic> data, Map<String, String>? photos) async {
    final db = await database;
    return await db.insert('pending_inspections', {
      'job_id': jobId,
      'data': _encodeJson(data),
      'photos': photos != null ? _encodeJson(photos) : null,
      'created_at': DateTime.now().toIso8601String(),
    });
  }
  
  Future<List<Map<String, dynamic>>> getPendingInspections() async {
    final db = await database;
    return await db.query('pending_inspections', orderBy: 'created_at ASC');
  }
  
  Future<void> deletePendingInspection(int id) async {
    final db = await database;
    await db.delete('pending_inspections', where: 'id = ?', whereArgs: [id]);
  }
  
  Future<void> incrementRetryCount(String table, int id) async {
    final db = await database;
    await db.rawUpdate(
      'UPDATE $table SET retry_count = retry_count + 1 WHERE id = ?',
      [id],
    );
  }
  
  // Maintenance Methods
  Future<int> savePendingMaintenance(int jobId, String status, Map<String, dynamic> data, Map<String, String>? photos) async {
    final db = await database;
    return await db.insert('pending_maintenance', {
      'job_id': jobId,
      'status': status,
      'data': _encodeJson(data),
      'photos': photos != null ? _encodeJson(photos) : null,
      'created_at': DateTime.now().toIso8601String(),
    });
  }
  
  Future<List<Map<String, dynamic>>> getPendingMaintenance() async {
    final db = await database;
    return await db.query('pending_maintenance', orderBy: 'created_at ASC');
  }
  
  Future<void> deletePendingMaintenance(int id) async {
    final db = await database;
    await db.delete('pending_maintenance', where: 'id = ?', whereArgs: [id]);
  }
  
  // Receiver Confirmation Methods
  Future<int> savePendingReceiverConfirmation(int jobId, Map<String, dynamic> data) async {
    final db = await database;
    return await db.insert('pending_receiver_confirmations', {
      'job_id': jobId,
      'data': _encodeJson(data),
      'created_at': DateTime.now().toIso8601String(),
    });
  }
  
  Future<List<Map<String, dynamic>>> getPendingReceiverConfirmations() async {
    final db = await database;
    return await db.query('pending_receiver_confirmations', orderBy: 'created_at ASC');
  }
  
  Future<void> deletePendingReceiverConfirmation(int id) async {
    final db = await database;
    await db.delete('pending_receiver_confirmations', where: 'id = ?', whereArgs: [id]);
  }

  // Vacuum Methods
  Future<int> savePendingVacuumActivity(int activityId, Map<String, dynamic> data) async {
    final db = await database;
    return await db.insert('pending_vacuum_activities', {
      'activity_id': activityId,
      'data': _encodeJson(data),
      'created_at': DateTime.now().toIso8601String(),
    });
  }
  
  Future<List<Map<String, dynamic>>> getPendingVacuumActivities() async {
    final db = await database;
    return await db.query('pending_vacuum_activities', orderBy: 'created_at ASC');
  }
  
  Future<void> deletePendingVacuumActivity(int id) async {
    final db = await database;
    await db.delete('pending_vacuum_activities', where: 'id = ?', whereArgs: [id]);
  }

  // Calibration Methods
  Future<int> savePendingCalibration(int jobId, Map<String, dynamic> data) async {
    final db = await database;
    return await db.insert('pending_calibration_activities', {
      'job_id': jobId,
      'data': _encodeJson(data),
      'created_at': DateTime.now().toIso8601String(),
    });
  }
  
  Future<List<Map<String, dynamic>>> getPendingCalibrationActivities() async {
    final db = await database;
    return await db.query('pending_calibration_activities', orderBy: 'created_at ASC');
  }
  
  Future<void> deletePendingCalibration(int id) async {
    final db = await database;
    await db.delete('pending_calibration_activities', where: 'id = ?', whereArgs: [id]);
  }
  
  // Cache Methods
  Future<void> cacheJob(int jobId, String type, Map<String, dynamic> data) async {
    final db = await database;
    await db.insert(
      'cached_jobs',
      {
        'id': jobId,
        'type': type,
        'data': _encodeJson(data),
        'cached_at': DateTime.now().toIso8601String(),
      },
      conflictAlgorithm: ConflictAlgorithm.replace,
    );
  }
  
  Future<Map<String, dynamic>?> getCachedJob(int jobId, {String type = 'inspection_detail'}) async {
    final db = await database;
    final results = await db.query(
      'cached_jobs',
      where: 'id = ? AND type = ?',
      whereArgs: [jobId, type],
    );
    
    if (results.isEmpty) return null;
    return decodeJson(results.first['data'] as String);
  }
  
  Future<void> clearOldCache({int daysOld = 7}) async {
    final db = await database;
    final cutoffDate = DateTime.now().subtract(Duration(days: daysOld));
    await db.delete(
      'cached_jobs',
      where: 'cached_at < ?',
      whereArgs: [cutoffDate.toIso8601String()],
    );
  }
  
  // Helper methods
  String _encodeJson(dynamic data) {
    try {
      return jsonEncode(data);
    } catch (e) {
      return '{}';
    }
  }
  
  Map<String, dynamic> decodeJson(String data) {
    try {
      final decoded = jsonDecode(data);
      if (decoded is Map<String, dynamic>) {
        return decoded;
      }
      return {};
    } catch (e) {
      return {};
    }
  }
  
  Future<int> getPendingCount() async {
    final db = await database;
    final inspections = await db.rawQuery('SELECT COUNT(*) as count FROM pending_inspections');
    final maintenance = await db.rawQuery('SELECT COUNT(*) as count FROM pending_maintenance');
    final confirmations = await db.rawQuery('SELECT COUNT(*) as count FROM pending_receiver_confirmations');
    final vacuum = await db.rawQuery('SELECT COUNT(*) as count FROM pending_vacuum_activities');
    final calibration = await db.rawQuery('SELECT COUNT(*) as count FROM pending_calibration_activities');
    
    int total = 0;
    total += (inspections.first['count'] as int?) ?? 0;
    total += (maintenance.first['count'] as int?) ?? 0;
    total += (confirmations.first['count'] as int?) ?? 0;
    total += (vacuum.first['count'] as int?) ?? 0;
    total += (calibration.first['count'] as int?) ?? 0;
    
    return total;
  }
}
