import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../logic/providers/auth_provider.dart';
import 'inspector/inspector_dashboard.dart';
import 'maintenance/maintenance_dashboard.dart';
import 'admin/admin_dashboard.dart';
import 'receiver/receiver_dashboard.dart';

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final authProvider = Provider.of<AuthProvider>(context);
    final role = authProvider.role;

    if (role == 'inspector') {
      return const InspectorDashboard();
    }
    
    if (role == 'maintenance') {
      return const MaintenanceDashboard();
    }

    if (role == 'receiver') {
      return const ReceiverDashboard();
    }

    if (role == 'admin' || role == 'management') {
      return const AdminDashboard();
    }
    
    // Placeholder for other roles
    return Scaffold(
      appBar: AppBar(
        title: const Text('Isotank System'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () => authProvider.logout(),
          ),
        ],
      ),
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Text('Welcome, ${authProvider.user?['name']}'),
            Text('Role: $role'),
            const SizedBox(height: 20),
             if (role == 'maintenance') 
               FilledButton.icon(
                 onPressed: () {
                   Navigator.of(context).push(
                     MaterialPageRoute(builder: (_) => const MaintenanceDashboard()),
                   );
                 },
                 icon: const Icon(Icons.build),
                 label: const Text('Go to Maintenance Dashboard'),
               )
             else 
               const Text('Dashboard under construction for this role'),
          ],
        ),
      ),
    );
  }
}
