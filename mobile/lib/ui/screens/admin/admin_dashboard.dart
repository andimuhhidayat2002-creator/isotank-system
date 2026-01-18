import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../data/services/api_service.dart';
import '../../../logic/providers/auth_provider.dart';

class AdminDashboard extends StatefulWidget {
  const AdminDashboard({super.key});

  @override
  State<AdminDashboard> createState() => _AdminDashboardState();
}

class _AdminDashboardState extends State<AdminDashboard> {
  final ApiService _apiService = ApiService();
  late Future<Map<String, dynamic>> _dashboardFuture;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  void _loadData() {
    setState(() {
      _dashboardFuture = _apiService.getAdminDashboard();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Admin Dashboard'),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _loadData,
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () => Provider.of<AuthProvider>(context, listen: false).logout(),
          ),
        ],
      ),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _dashboardFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return Center(child: Text('Error: ${snapshot.error}'));
          }

          final data = snapshot.data!;
          final stats = data['stats'];
          final locationSummary = List.from(data['isotanks_by_location'] ?? []);
          final vacuumAlerts = List.from(data['vacuum_alerts'] ?? []);
          final calibrationAlerts = List.from(data['calibration_alerts'] ?? []);

          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              // Stats Cards
              Row(
                children: [
                  _buildStatCard('Active ISO', stats['active_isotanks'].toString(), Colors.blue),
                  _buildStatCard('Open Insp', stats['open_inspections'].toString(), Colors.orange),
                  _buildStatCard('Open Maint', stats['open_maintenance'].toString(), Colors.green),
                ],
              ),
              const SizedBox(height: 24),
              
              _buildSectionTitle('Isotank Location Summary'),
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(8.0),
                  child: Table(
                    columnWidths: const {
                      0: FlexColumnWidth(2.5),
                      1: FlexColumnWidth(1),
                      2: FlexColumnWidth(1),
                      3: FlexColumnWidth(1),
                      4: FlexColumnWidth(1),
                      5: FlexColumnWidth(1),
                      6: FlexColumnWidth(1),
                      7: FlexColumnWidth(1),
                      8: FlexColumnWidth(1),
                    },
                    children: [
                      const TableRow(
                        children: [
                          _Th('Loc'),
                          _Th('Act'),
                          _Th('Ina'),
                          _Th('Inc'),
                          _Th('Mnt'),
                          _Th('Out'),
                          _Th('Vac'),
                          _Th('Cal'),
                          _Th('Tot'),
                        ],
                      ),
                      ...locationSummary.map((loc) => TableRow(
                        children: [
                          _Td(loc['location'].toString()),
                          _Td(loc['active'].toString(), color: Colors.green),
                          _Td(loc['inactive'].toString(), color: Colors.red),
                          _Td(loc['incoming'].toString()),
                          _Td(loc['maintenance'].toString()),
                          _Td(loc['outgoing'].toString()),
                          _Td(loc['vacuum'].toString()),
                          _Td(loc['calibration'].toString()),
                          _Td(loc['total'].toString(), bold: true),
                        ],
                      )),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 24),

              _buildSectionTitle('Vacuum Alerts (11 Months)'),
              if (vacuumAlerts.isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 8.0),
                  child: Text('No vacuum alerts.'),
                )
              else
                ...vacuumAlerts.map((a) => _buildAlertTile(
                  a['isotank']['iso_number'], 
                  'Last Check: ${a['last_measurement_at'].toString().split('T')[0]}',
                  Colors.red,
                )),

              const SizedBox(height: 24),
              _buildSectionTitle('Calibration Alerts (Expired/Rejected)'),
               if (calibrationAlerts.isEmpty)
                const Padding(
                  padding: EdgeInsets.symmetric(vertical: 8.0),
                  child: Text('No calibration alerts.'),
                )
              else
                ...calibrationAlerts.map((a) => _buildAlertTile(
                  a['isotank']['iso_number'], 
                  '${a['item_name']}: ${a['status']} (${a['valid_until'] != null ? a['valid_until'].toString().split('T')[0] : 'N/A'})',
                  a['status'] == 'rejected' ? Colors.red : Colors.orange,
                )),
            ],
          );
        },
      ),
    );
  }

  Widget _buildStatCard(String label, String value, Color color) {
    return Expanded(
      child: Card(
        color: color.withOpacity(0.1),
        child: Padding(
          padding: const EdgeInsets.all(12.0),
          child: Column(
            children: [
              Text(value, style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: color)),
              Text(label, style: const TextStyle(fontSize: 10)),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSectionTitle(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8.0),
      child: Text(
        title, 
        style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Color(0xFF0D47A1)),
      ),
    );
  }

  Widget _buildAlertTile(String iso, String sub, Color color) {
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: Icon(Icons.warning, color: color),
        title: Text(iso, style: const TextStyle(fontWeight: FontWeight.bold)),
        subtitle: Text(sub),
      ),
    );
  }
}

class _Th extends StatelessWidget {
  final String text;
  const _Th(this.text);
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(4),
      child: Text(text, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 10)),
    );
  }
}

class _Td extends StatelessWidget {
  final String text;
  final Color? color;
  final bool bold;
  const _Td(this.text, {this.color, this.bold = false});
  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(4),
      child: Text(
        text, 
        style: TextStyle(
          color: color, 
          fontWeight: bold ? FontWeight.bold : FontWeight.normal,
          fontSize: 10,
        ),
      ),
    );
  }
}
