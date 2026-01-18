import 'dart:async';
import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/foundation.dart';

class ConnectivityService {
  static final ConnectivityService _instance = ConnectivityService._internal();
  factory ConnectivityService() => _instance;
  
  final Connectivity _connectivity = Connectivity();
  final StreamController<bool> _connectionStatusController = StreamController<bool>.broadcast();
  
  bool _isOnline = true;
  StreamSubscription? _connectivitySubscription;
  
  ConnectivityService._internal();
  
  Stream<bool> get connectionStatus => _connectionStatusController.stream;
  bool get isOnline => _isOnline;
  
  Future<void> initialize() async {
    // Check initial connectivity
    await _updateConnectionStatus(await _connectivity.checkConnectivity());
    
    // Listen to connectivity changes
    _connectivitySubscription = _connectivity.onConnectivityChanged.listen(_updateConnectionStatus);
  }
  
  Future<void> _updateConnectionStatus(List<ConnectivityResult> results) async {
    // Consider online if any connection is available
    final wasOnline = _isOnline;
    _isOnline = results.any((result) => 
      result == ConnectivityResult.mobile || 
      result == ConnectivityResult.wifi ||
      result == ConnectivityResult.ethernet
    );
    
    if (wasOnline != _isOnline) {
      if (kDebugMode) {
        print('📡 Connection status changed: ${_isOnline ? "ONLINE" : "OFFLINE"}');
      }
      _connectionStatusController.add(_isOnline);
    }
  }
  
  void dispose() {
    _connectivitySubscription?.cancel();
    _connectionStatusController.close();
  }
}
