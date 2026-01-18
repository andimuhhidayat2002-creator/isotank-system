import 'package:flutter/material.dart';
import '../../data/services/connectivity_service.dart';
import 'dart:async';

/// Widget untuk menampilkan status koneksi (online/offline)
class ConnectivityIndicator extends StatefulWidget {
  const ConnectivityIndicator({super.key});

  @override
  State<ConnectivityIndicator> createState() => _ConnectivityIndicatorState();
}

class _ConnectivityIndicatorState extends State<ConnectivityIndicator> {
  final ConnectivityService _connectivityService = ConnectivityService.instance;
  late StreamSubscription<bool> _connectivitySubscription;
  bool _isOnline = true;

  @override
  void initState() {
    super.initState();
    _isOnline = _connectivityService.isOnline;
    _connectivitySubscription = _connectivityService.connectivityStream.listen(
      (isOnline) {
        if (mounted) {
          setState(() {
            _isOnline = isOnline;
          });
        }
      },
    );
  }

  @override
  void dispose() {
    _connectivitySubscription.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_isOnline) {
      return const SizedBox.shrink(); // Hide when online
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      color: Colors.orange,
      child: Row(
        children: [
          const Icon(Icons.cloud_off, color: Colors.white, size: 20),
          const SizedBox(width: 8),
          const Expanded(
            child: Text(
              'Offline Mode - Changes will be saved locally',
              style: TextStyle(color: Colors.white, fontWeight: FontWeight.w500),
            ),
          ),
        ],
      ),
    );
  }
}

