import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../data/services/api_service.dart';
import '../../../data/services/sync_service.dart';
import '../../../logic/providers/auth_provider.dart';
import 'dart:async';
import '../../../data/services/connectivity_service.dart';
import 'inspector_jobs_screen.dart'; // We will move the jobs list here
import 'yard_search_screen.dart';   // We will create this

class InspectorDashboard extends StatefulWidget {
  const InspectorDashboard({super.key});

  @override
  State<InspectorDashboard> createState() => _InspectorDashboardState();
}

class _InspectorDashboardState extends State<InspectorDashboard> {
  final SyncService _syncService = SyncService();
  final ConnectivityService _connectivityService = ConnectivityService();
  StreamSubscription<bool>? _connectionSubscription;
  bool _isOnline = true;
  int _pendingCount = 0;

  @override
  void initState() {
    super.initState();
    _isOnline = _connectivityService.isOnline;
    _updatePendingCount();
    
    // Listen to connection changes
    _connectionSubscription = _connectivityService.connectionStatus.listen((isOnline) {
      if (mounted) {
        setState(() => _isOnline = isOnline);
        if (isOnline) {
          _performAutoSync();
        }
      }
    });

    // Initial check (if already online, maybe sync pending)
    if (_isOnline) {
      _performAutoSync();
    }
  }

  @override
  void dispose() {
    _connectionSubscription?.cancel();
    super.dispose();
  }

  Future<void> _updatePendingCount() async {
    final count = await _syncService.getPendingCount();
    if (mounted) setState(() => _pendingCount = count);
  }

  Future<void> _performAutoSync() async {
    await _updatePendingCount();
    if (_pendingCount > 0) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('📡 Connection restored. Syncing pending data...'), backgroundColor: Colors.orange),
        );
      }
      
      await _syncService.syncPendingData();
      await _updatePendingCount();
      
      if (mounted) {
        if (_pendingCount == 0) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('✅ Data synced successfully!'), backgroundColor: Colors.green),
          );
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('⚠️ Sync incomplete. $_pendingCount items remaining.'), backgroundColor: Colors.orange),
          );
        }
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Inspector Dashboard'),
        centerTitle: true,
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () => Provider.of<AuthProvider>(context, listen: false).logout(),
          ),
        ],
      ),
      body: Container(
        decoration: BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topCenter,
            end: Alignment.bottomCenter,
            colors: [Colors.blue.shade50, Colors.white],
          ),
        ),
        padding: const EdgeInsets.all(16.0),
        child: Column(
          children: [
            // Status Card
            Card(
              color: _isOnline ? Colors.green[50] : Colors.red[50],
              child: Padding(
                padding: const EdgeInsets.all(12.0),
                child: Row(
                  children: [
                    Icon(_isOnline ? Icons.wifi : Icons.wifi_off, 
                         color: _isOnline ? Colors.green : Colors.red),
                    const SizedBox(width: 12),
                    Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(_isOnline ? 'ONLINE Mode' : 'OFFLINE Mode', 
                             style: const TextStyle(fontWeight: FontWeight.bold)),
                        Text(_pendingCount > 0 ? '$_pendingCount items pending sync' : 'All data synced',
                             style: TextStyle(color: _pendingCount > 0 ? Colors.orange : Colors.grey[700], fontSize: 12)),
                      ],
                    ),
                    const Spacer(),
                    if (_pendingCount > 0 && _isOnline)
                      IconButton(
                        icon: const Icon(Icons.sync, color: Colors.blue),
                        onPressed: _performAutoSync,
                        tooltip: 'Sync Now',
                      ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),
            
            Expanded(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _MenuCard(
                    title: 'My Inspections',
                    icon: Icons.assignment,
                    color: Colors.blue.shade700,
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (context) => const InspectorJobsScreen()),
                      ).then((_) => _updatePendingCount());
                    },
                  ),
                  const SizedBox(height: 24),
                  _MenuCard(
                    title: 'Yard Positioning',
                    icon: Icons.map,
                    color: Colors.orange.shade700,
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(builder: (context) => const YardMapScreen()),
                      );
                    },
                  ),
                  const SizedBox(height: 24),
                  _MenuCard(
                    title: 'Download Offline Data',
                    icon: Icons.download_for_offline, // Changed icon
                    color: Colors.green.shade700,
                    onTap: () async {
                      // Show loading indicator
                      showDialog(
                        context: context,
                        barrierDismissible: false,
                        builder: (ctx) => const Center(child: CircularProgressIndicator()),
                      );
                      
                      try {
                        await _syncService.downloadOfflineData();
                        if (context.mounted) {
                          Navigator.pop(context); // Close loading
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('✅ Data downloaded for offline use!'), backgroundColor: Colors.green),
                          );
                          _updatePendingCount();
                        }
                      } catch (e) {
                        if (context.mounted) {
                          Navigator.pop(context); // Close loading
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(content: Text('❌ Sync failed: $e'), backgroundColor: Colors.red),
                          );
                        }
                      }
                    },
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _MenuCard extends StatelessWidget {
  final String title;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;

  const _MenuCard({
    required this.title,
    required this.icon,
    required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 4,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.all(24), // Reduced padding slightly
          child: Row( // Changed to Row for better look on wide screens or just stylistic
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, size: 48, color: color),
              const SizedBox(width: 24),
              Expanded( // Prevent overflow
                child: Text(
                  title,
                  style: Theme.of(context).textTheme.headlineSmall?.copyWith(
                        fontWeight: FontWeight.bold,
                        color: Colors.grey[800],
                        fontSize: 20,
                      ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
