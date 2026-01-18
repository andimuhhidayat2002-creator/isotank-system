import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/calibration_log.dart';
import 'api_service.dart';

class CalibrationService {
  final ApiService _apiService;

  CalibrationService(this._apiService);

  Future<List<CalibrationLog>> getCalibrationJobs() async {
    final response = await _apiService.getCalibrationJobs();
    
    if (response is List) {
      return response.map((data) => CalibrationLog.fromJson(data)).toList();
    } else {
      // In case apiService parses it differently or returns wrapped data
      // For now assumed ApiService.get returns dynamic which is the decoded JSON body.
       throw Exception('Unexpected response format');
    }
  }

  Future<CalibrationLog> getCalibrationJob(int id) async {
    final response = await _apiService.get('/maintenance/calibration/$id');
    return CalibrationLog.fromJson(response);
  }

  Future<void> completeCalibration(int id, {
    required String status,
    DateTime? calibrationDate,
    DateTime? validUntil,
    String? replacementSerial,
    DateTime? replacementCalibrationDate,
    DateTime? replacementValidUntil,
    String? notes,
  }) async {
    final body = {
      'status': status,
      'notes': notes,
    };

    if (status == 'completed') {
      body['calibration_date'] = calibrationDate!.toIso8601String().split('T')[0];
      body['valid_until'] = validUntil!.toIso8601String().split('T')[0];
    } else if (status == 'rejected') {
      body['replacement_serial'] = replacementSerial;
      body['replacement_calibration_date'] = replacementCalibrationDate!.toIso8601String().split('T')[0];
      body['replacement_valid_until'] = replacementValidUntil!.toIso8601String().split('T')[0];
    }
    
    await _apiService.updateCalibration(id, body);
  }
}
