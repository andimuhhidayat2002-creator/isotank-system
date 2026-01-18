import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:dio/dio.dart';
import '../../data/services/api_service.dart';

class AuthProvider with ChangeNotifier {
  bool _isAuthenticated = false;
  String? _token;
  String? _role;
  Map<String, dynamic>? _user;
  bool _isLoading = false;

  bool get isAuthenticated => _isAuthenticated;
  String? get role => _role;
  Map<String, dynamic>? get user => _user;
  bool get isLoading => _isLoading;

  final ApiService _apiService = ApiService();

  AuthProvider() {
    _loadUserFromPrefs();
  }

  Future<void> _loadUserFromPrefs() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString('token');
    _role = prefs.getString('role');
    
    if (_token != null) {
      _isAuthenticated = true;
      // Setup default headers
      _apiService.setToken(_token!);
    }
    notifyListeners();
  }

  Future<bool> login(String email, String password) async {
    _isLoading = true;
    notifyListeners();

    try {
      final response = await _apiService.login(email, password);
      
      if (response['success']) {
        _token = response['token']; // Sanctum returns plain text token
        _user = response['user']; // User data is in 'user' key, not 'data'
        _role = response['user']['role']; // Get role from user object
        
        if (kDebugMode) {
          print('🎉 Login successful!');
          print('👤 User: ${_user?['name']} (${_user?['email']})');
          print('🔑 Role: $_role');
          print('🎫 Token: ${_token?.substring(0, 20)}...');
        }
        
        // Save to prefs
        final prefs = await SharedPreferences.getInstance();
        await prefs.setString('token', _token!);
        await prefs.setString('role', _role!);
        
        if (kDebugMode) print('💾 Token saved to SharedPreferences');
        
        _apiService.setToken(_token!);
        _isAuthenticated = true;
        _isLoading = false;
        notifyListeners();
        return true;
      }
    } catch (e) {
      if (kDebugMode) print('Login Error: $e');
    }

    _isLoading = false;
    notifyListeners();
    return false;
  }

  Future<void> logout() async {
    try {
      await _apiService.logout();
    } catch (e) {
      // Ignore network errors on logout
    }

    _token = null;
    _role = null;
    _user = null;
    _isAuthenticated = false;
    
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
    
    notifyListeners();
  }
}
