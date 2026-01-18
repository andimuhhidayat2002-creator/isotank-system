import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../data/services/api_service.dart';
import '../../../logic/providers/auth_provider.dart';
import 'receiver_confirmation_screen.dart';

class ReceiverDashboard extends StatefulWidget {
  const ReceiverDashboard({super.key});

  @override
  State<ReceiverDashboard> createState() => _ReceiverDashboardState();
}

class _ReceiverDashboardState extends State<ReceiverDashboard> {
  final ApiService _apiService = ApiService();
  late Future<List<dynamic>> _jobsFuture;

  @override
  void initState() {
    super.initState();
    _loadJobs();
  }

  void _loadJobs() {
    setState(() {
      _jobsFuture = _apiService.getInspectorJobs();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Pending Confirmations'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadJobs,
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () => Provider.of<AuthProvider>(context, listen: false).logout(),
          ),
        ],
      ),
      body: FutureBuilder<List<dynamic>>(
        future: _jobsFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.error_outline, size: 48, color: Colors.orange),
                  const SizedBox(height: 16),
                  Text('Error: ${snapshot.error}'),
                  TextButton(
                    onPressed: _loadJobs,
                    child: const Text('Retry'),
                  ),
                ],
              ),
            );
          }

          final jobs = snapshot.data ?? [];

          if (jobs.isEmpty) {
            return const Center(
              child: Padding(
                padding: EdgeInsets.all(32.0),
                child: Text(
                  'No outgoing inspections ready for confirmation.',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 16, color: Colors.grey),
                ),
              ),
            );
          }

          return ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: jobs.length,
            itemBuilder: (context, index) {
              final job = jobs[index];
              final isotank = job['isotank'] ?? {};
              
              return Card(
                elevation: 3,
                margin: const EdgeInsets.only(bottom: 16),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                child: ListTile(
                  contentPadding: const EdgeInsets.all(16),
                  leading: const CircleAvatar(
                    backgroundColor: Color(0xFF0D47A1),
                    child: Icon(Icons.local_shipping, color: Colors.white),
                  ),
                  title: Text(
                    isotank['iso_number'] ?? 'Unknown ISO',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                  ),
                  subtitle: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const SizedBox(height: 8),
                      Text('Destination: ${job['destination'] ?? 'N/A'}', style: const TextStyle(fontWeight: FontWeight.w500)),
                      const SizedBox(height: 4),
                      Text('Assigned: ${job['planned_date'] != null ? job['planned_date'].toString().split('T')[0] : 'N/A'}'),
                    ],
                  ),
                  trailing: const Icon(Icons.arrow_forward_ios, size: 16),
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => ReceiverConfirmationScreen(jobId: job['id']),
                      ),
                    ).then((_) => _loadJobs());
                  },
                ),
              );
            },
          );
        },
      ),
    );
  }
}
