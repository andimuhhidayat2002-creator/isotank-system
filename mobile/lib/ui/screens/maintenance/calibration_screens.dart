import 'package:flutter/material.dart';
import '../../../data/models/calibration_log.dart';
import '../../../data/services/calibration_service.dart';
import '../../../data/services/api_service.dart';

class CalibrationListScreen extends StatefulWidget {
  const CalibrationListScreen({super.key});

  @override
  State<CalibrationListScreen> createState() => _CalibrationListScreenState();
}

class _CalibrationListScreenState extends State<CalibrationListScreen> {
  late CalibrationService _calibrationService;
  late Future<List<CalibrationLog>> _jobsFuture;
  final TextEditingController _searchController = TextEditingController();
  String _query = '';

  @override
  void initState() {
    super.initState();
    _calibrationService = CalibrationService(ApiService());
    _loadJobs();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _loadJobs() {
    setState(() {
      _jobsFuture = _calibrationService.getCalibrationJobs();
    });
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<List<CalibrationLog>>(
      future: _jobsFuture,
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }

        if (snapshot.hasError) {
          return Center(child: Text('Error: ${snapshot.error}'));
        }

        final allJobs = snapshot.data ?? [];

        if (allJobs.isEmpty) {
          return const Center(child: Text('No planned calibrations.'));
        }
        
        final filteredJobs = allJobs.where((job) {
           final iso = job.isotankNumber?.toUpperCase() ?? '';
           return iso.contains(_query.toUpperCase());
        }).toList();

        return Column(
          children: [
            Padding(
              padding: const EdgeInsets.all(16.0),
              child: TextField(
                controller: _searchController,
                decoration: InputDecoration(
                  labelText: 'Search Isotank',
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
               ? const Center(child: Text('No matching jobs.'))
               : ListView.builder(
                  padding: const EdgeInsets.symmetric(horizontal: 16),
                  itemCount: filteredJobs.length,
                  itemBuilder: (context, index) {
                    final job = filteredJobs[index];
                    return Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      child: ListTile(
                        title: Text(job.isotankNumber ?? 'Unknown ISO'),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text('${job.itemName}'),
                            if (job.serialNumber != null) 
                               Text('SN: ${job.serialNumber}', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                            if (job.plannedDate != null)
                              Text('Date: ${job.plannedDate.toString().split(' ')[0]}', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                          ],
                        ),
                        trailing: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: Colors.blue[100],
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: Text(
                            job.status.toUpperCase(),
                            style: const TextStyle(fontWeight: FontWeight.bold),
                          ),
                        ),
                        onTap: () {
                           Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) => CalibrationFormScreen(job: job),
                            ),
                          ).then((value) {
                            if (value == true) _loadJobs();
                          });
                        },
                      ),
                    );
                  },
                ),
            ),
          ],
        );
      },
    );
  }
}

class CalibrationFormScreen extends StatefulWidget {
  final CalibrationLog job;

  const CalibrationFormScreen({super.key, required this.job});

  @override
  State<CalibrationFormScreen> createState() => _CalibrationFormScreenState();
}

class _CalibrationFormScreenState extends State<CalibrationFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final TextEditingController _notesController = TextEditingController();
  final TextEditingController _replacementSerialController = TextEditingController(); // New
  
  String _status = 'completed'; // completed (Pass) or rejected (Reject)
  
  DateTime _calibrationDate = DateTime.now();
  DateTime _validUntil = DateTime.now().add(const Duration(days: 365));
  
  DateTime _replacementCalibrationDate = DateTime.now(); // New
  DateTime _replacementValidUntil = DateTime.now().add(const Duration(days: 365)); // New
  
  bool _isLoading = false;

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Update Calibration ${widget.job.isotankNumber}')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _buildInfoRow('Item Name', widget.job.itemName),
              const SizedBox(height: 8),
              if (widget.job.serialNumber != null) ...[
                _buildInfoRow('Serial Number', widget.job.serialNumber!),
                const SizedBox(height: 8),
              ],
              _buildInfoRow('Vendor', widget.job.vendor ?? '-'),
              const Divider(height: 32),
              
              const Text('Result', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 16),
              
              Row(
                children: [
                   Expanded(
                     child: RadioListTile<String>(
                       title: const Text('Pass'),
                       value: 'completed',
                       groupValue: _status,
                       onChanged: (val) => setState(() => _status = val!),
                     ),
                   ),
                   Expanded(
                     child: RadioListTile<String>(
                       title: const Text('Reject / Replace'),
                       value: 'rejected',
                       groupValue: _status,
                       onChanged: (val) => setState(() => _status = val!),
                     ),
                   ),
                ],
              ),
              
              const SizedBox(height: 16),
              
              if (_status == 'completed') ...[
                  _buildDatePicker('Calibration Date', _calibrationDate, (d) {
                    setState(() {
                         _calibrationDate = d;
                         _validUntil = d.add(const Duration(days: 365));
                    });
                  }),
                  const SizedBox(height: 16),
                  _buildDatePicker('Valid Until', _validUntil, (d) => setState(() => _validUntil = d)),
              ] else ...[
                  const Text('Replacement Item Details', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.orange)),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _replacementSerialController,
                    decoration: const InputDecoration(
                      labelText: 'New Serial Number',
                      border: OutlineInputBorder(),
                    ),
                    validator: (val) => val == null || val.isEmpty ? 'Required' : null,
                  ),
                  const SizedBox(height: 16),
                  _buildDatePicker('New Calibration Date', _replacementCalibrationDate, (d) {
                    setState(() {
                         _replacementCalibrationDate = d;
                         _replacementValidUntil = d.add(const Duration(days: 365));
                    });
                  }),
                  const SizedBox(height: 16),
                  _buildDatePicker('New Valid Until', _replacementValidUntil, (d) => setState(() => _replacementValidUntil = d)),
              ],

              const SizedBox(height: 16),
              
              TextFormField(
                controller: _notesController,
                decoration: const InputDecoration(
                  labelText: 'Notes / Remarks',
                  border: OutlineInputBorder(),
                ),
                maxLines: 3,
              ),
              
              const SizedBox(height: 24),
              
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _status == 'completed' ? Colors.green : Colors.orange, 
                    padding: const EdgeInsets.symmetric(vertical: 16),
                  ),
                  onPressed: _isLoading ? null : _submit,
                  child: _isLoading 
                    ? const CircularProgressIndicator(color: Colors.white) 
                    : Text(_status == 'completed' ? 'PASS & COMPLETE' : 'REJECT & REPLACE', style: const TextStyle(color: Colors.white)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
  
  Widget _buildDatePicker(String label, DateTime date, Function(DateTime) onPicked) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label),
        const SizedBox(height: 8),
        InkWell(
          onTap: () async {
            final picked = await showDatePicker(
              context: context,
              initialDate: date,
              firstDate: DateTime(2020),
              lastDate: DateTime(2030),
            );
            if (picked != null) onPicked(picked);
          },
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 16),
            decoration: BoxDecoration(border: Border.all(color: Colors.grey), borderRadius: BorderRadius.circular(4)),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(date.toIso8601String().split('T')[0]),
                const Icon(Icons.calendar_today),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(width: 100, child: Text(label, style: const TextStyle(fontWeight: FontWeight.bold, color: Colors.grey))),
        Expanded(child: Text(value)),
      ],
    );
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    
    setState(() => _isLoading = true);
    
    try {
      final service = CalibrationService(ApiService());
      await service.completeCalibration(
        widget.job.id, 
        status: _status,
        calibrationDate: _calibrationDate, // Will be ignored if rejected
        validUntil: _validUntil, // Will be ignored if rejected
        replacementSerial: _replacementSerialController.text,
        replacementCalibrationDate: _replacementCalibrationDate,
        replacementValidUntil: _replacementValidUntil,
        notes: _notesController.text,
      );
      
      if (mounted) {
        Navigator.pop(context, true);
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(
            content: Text(_status == 'completed' ? 'Calibration Passed' : 'Item Replaced & Calibrated')
        ));
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }
}
