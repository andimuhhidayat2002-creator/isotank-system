import 'package:flutter/material.dart';
import '../../../data/services/api_service.dart';
import '../inspection_form/inspection_form_screen.dart';

class InspectorJobsScreen extends StatefulWidget {
  const InspectorJobsScreen({super.key});

  @override
  State<InspectorJobsScreen> createState() => _InspectorJobsScreenState();
}

class _InspectorJobsScreenState extends State<InspectorJobsScreen> {
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
        title: const Text('My Inspections'),
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

          final allJobs = snapshot.data ?? [];
          
          if (allJobs.isEmpty) {
            return const Center(child: Text('No open inspections found.'));
          }

          return InspectionListWithSearch(jobs: allJobs, onRefresh: _loadJobs);
        },
      ),
    );
  }
}

class InspectionListWithSearch extends StatefulWidget {
  final List<dynamic> jobs;
  final VoidCallback onRefresh;

  const InspectionListWithSearch({super.key, required this.jobs, required this.onRefresh});

  @override
  State<InspectionListWithSearch> createState() => _InspectionListWithSearchState();
}

class _InspectionListWithSearchState extends State<InspectionListWithSearch> {
  final TextEditingController _searchController = TextEditingController();
  String _query = '';

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final filteredJobs = widget.jobs.where((job) {
      final iso = job['isotank']?['iso_number']?.toString().toUpperCase() ?? '';
      return iso.contains(_query.toUpperCase());
    }).toList();

    return Column(
      children: [
        Padding(
          padding: const EdgeInsets.all(16.0),
          child: TextField(
            controller: _searchController,
            decoration: InputDecoration(
              labelText: 'Search Isotank (Last 4 Digits)',
              prefixIcon: const Icon(Icons.search),
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
              suffixIcon: _query.isNotEmpty
                  ? IconButton(
                      icon: const Icon(Icons.clear),
                      onPressed: () {
                        _searchController.clear();
                        setState(() => _query = '');
                      },
                    )
                  : null,
            ),
            onChanged: (val) {
              setState(() => _query = val);
            },
          ),
        ),
        Expanded(
          child: filteredJobs.isEmpty
              ? const Center(child: Text('No matching isotanks.'))
              : ListView.builder(
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                  itemCount: filteredJobs.length,
                  itemBuilder: (context, index) {
                    final job = filteredJobs[index];
                    final isotank = job['isotank'] ?? {};
              
                    return Card(
                      elevation: 2,
                      margin: const EdgeInsets.only(bottom: 16),
                      child: ListTile(
                        contentPadding: const EdgeInsets.all(16),
                        title: Text(
                          isotank['iso_number'] ?? 'Unknown ISO',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                        ),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const SizedBox(height: 8),
                            Row(
                              children: [
                                Icon(Icons.event_note, size: 16, color: Colors.blue[700]),
                                const SizedBox(width: 4),
                                Text(
                                  job['activity_type']?.toString().split('_').join(' ').toUpperCase() ?? 'INSPECTION',
                                  style: TextStyle(color: Colors.blue[700], fontWeight: FontWeight.bold),
                                ),
                              ],
                            ),
                            const SizedBox(height: 4),
                            Row(
                              children: [
                                Icon(Icons.calendar_today, size: 16, color: Colors.grey[600]),
                                const SizedBox(width: 4),
                                Text(
                                  'Planned: ${job['planned_date'] != null ? job['planned_date'].toString().split('T')[0] : 'N/A'}',
                                ),
                              ],
                            ),
                            if (job['destination'] != null)
                              Padding(
                                padding: const EdgeInsets.only(top: 4),
                                child: Row(
                                  children: [
                                    Icon(Icons.near_me, size: 16, color: Colors.grey[600]),
                                    const SizedBox(width: 4),
                                    Text('Dest: ${job['destination']}'),
                                  ],
                                ),
                              ),
                          ],
                        ),
                        trailing: const Icon(Icons.chevron_right),
                        onTap: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) => InspectionFormScreen(jobId: job['id']),
                            ),
                          ).then((_) => widget.onRefresh());
                        },
                      ),
                    );
                  },
                ),
        ),
      ],
    );
  }
}
