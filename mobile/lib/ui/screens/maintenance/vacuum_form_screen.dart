import 'package:flutter/material.dart';
import '../../../data/services/api_service.dart';

class VacuumFormScreen extends StatefulWidget {
  final int activityId;
  final Map<String, dynamic> activityData;

  const VacuumFormScreen({super.key, required this.activityId, required this.activityData});

  @override
  State<VacuumFormScreen> createState() => _VacuumFormScreenState();
}

class _VacuumFormScreenState extends State<VacuumFormScreen> {
  final ApiService _apiService = ApiService();
  final _formKey = GlobalKey<FormState>();
  bool _isLoading = false;

  late int _dayNumber;
  final _portableVacuumController = TextEditingController();
  final _tempController = TextEditingController();
  final _machineStartController = TextEditingController();
  final _portableStopController = TextEditingController();
  final _machineStopController = TextEditingController();
  final _tempStopController = TextEditingController();

  final _morningVacuumController = TextEditingController();
  final _morningTempController = TextEditingController();
  final _eveningVacuumController = TextEditingController();
  final _eveningTempController = TextEditingController();
  
  bool _isCompleted = false;

  @override
  void initState() {
    super.initState();
    final data = widget.activityData;
    _dayNumber = data['day_number'] ?? 1;
    
    // Helper to format: 1.0 -> "1", 1.5 -> "1.5"
    String fmt(dynamic val) {
      if (val == null) return '';
      if (val is double || val is int) {
         double d = double.tryParse(val.toString()) ?? 0.0;
         if (d == d.toInt()) return d.toInt().toString();
         return d.toString();
      }
      return val.toString();
    }

    _portableVacuumController.text = fmt(data['portable_vacuum_value']);
    _tempController.text = fmt(data['temperature']);
    _machineStartController.text = fmt(data['machine_vacuum_at_start']);
    _portableStopController.text = fmt(data['portable_vacuum_when_machine_stops']);
    _machineStopController.text = fmt(data['machine_vacuum_at_stop']);
    _tempStopController.text = fmt(data['temperature_at_machine_stop']);

    _morningVacuumController.text = fmt(data['morning_vacuum_value']);
    _morningTempController.text = fmt(data['morning_temperature']);
    _eveningVacuumController.text = fmt(data['evening_vacuum_value']);
    _eveningTempController.text = fmt(data['evening_temperature']);
    
    _isCompleted = data['completed_at'] != null;
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() => _isLoading = true);
    try {
      final Map<String, dynamic> data = {
        'day_number': _dayNumber,
        'is_completed': _isCompleted,
      };

      if (_dayNumber == 1) {
        data.addAll({
          'portable_vacuum_value': _portableVacuumController.text.isEmpty ? null : double.tryParse(_portableVacuumController.text),
          'temperature': _tempController.text.isEmpty ? null : double.tryParse(_tempController.text),
          'machine_vacuum_at_start': _machineStartController.text.isEmpty ? null : double.tryParse(_machineStartController.text),
          'portable_vacuum_when_machine_stops': _portableStopController.text.isEmpty ? null : double.tryParse(_portableStopController.text),
          'machine_vacuum_at_stop': _machineStopController.text.isEmpty ? null : double.tryParse(_machineStopController.text),
          'temperature_at_machine_stop': _tempStopController.text.isEmpty ? null : double.tryParse(_tempStopController.text),
        });
      } else {
        data.addAll({
          'morning_vacuum_value': _morningVacuumController.text.isEmpty ? null : double.tryParse(_morningVacuumController.text),
          'morning_temperature': _morningTempController.text.isEmpty ? null : double.tryParse(_morningTempController.text),
          'evening_vacuum_value': _eveningVacuumController.text.isEmpty ? null : double.tryParse(_eveningVacuumController.text),
          'evening_temperature': _eveningTempController.text.isEmpty ? null : double.tryParse(_eveningTempController.text),
        });
      }

      await _apiService.updateVacuumActivity(widget.activityId, data);
      if (mounted) Navigator.pop(context, true);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('Vacuum Suction - Day $_dayNumber'),
        backgroundColor: Colors.blue[900],
        foregroundColor: Colors.white,
      ),
      body: _isLoading 
        ? const Center(child: CircularProgressIndicator())
        : SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Form(
              key: _formKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Card(
                    color: Colors.blue[50],
                    child: Padding(
                      padding: const EdgeInsets.all(12.0),
                      child: Row(
                        children: [
                          const Icon(Icons.vibration, color: Colors.blue),
                          const SizedBox(width: 12),
                          Text(
                            'Isotank: ${widget.activityData['isotank']['iso_number']}', 
                            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  
                  const Text('Proses Pencatatan Hari Ke:', style: TextStyle(fontWeight: FontWeight.bold)),
                  const SizedBox(height: 8),
                  DropdownButtonFormField<int>(
                    value: _dayNumber,
                    decoration: const InputDecoration(border: OutlineInputBorder()),
                    items: [1, 2, 3, 4, 5].map((d) => DropdownMenuItem(value: d, child: Text('Hari Ke-$d'))).toList(),
                    onChanged: (val) {
                      setState(() {
                         _dayNumber = val!;
                         // Clear inputs when switching days to prevent stale data
                         // Ideally we should fetch data for this day from API if it exists, 
                         // but based on current architecture `activityData` is single-day scoped.
                         
                         // If user switches to a new day, we assume it's blank.
                         _morningVacuumController.clear();
                         _morningTempController.clear();
                         _eveningVacuumController.clear();
                         _eveningTempController.clear();
                         
                         // Day 1 fields also clear if we switch back to 1 (unlikely flow but safe)
                         _portableVacuumController.clear();
                         _tempController.clear();
                      });
                    },
                  ),
                  const SizedBox(height: 24),

                  // Fields Area
                  if (_dayNumber == 1) _buildDay1Fields(),
                  if (_dayNumber > 1) _buildDay2to5Fields(),

                  const Divider(height: 40),
                  
                  CheckboxListTile(
                    title: const Text('Tandai Selesai (Proses 5 Hari Selesai)', style: TextStyle(fontWeight: FontWeight.bold)),
                    subtitle: const Text('Centang jika seluruh rangkaian penyedotan vacuum 5 hari telah rampung.'),
                    value: _isCompleted,
                    activeColor: Colors.blue[900],
                    onChanged: (val) => setState(() => _isCompleted = val!),
                  ),
                  const SizedBox(height: 24),
                  SizedBox(
                    width: double.infinity,
                    height: 55,
                    child: ElevatedButton(
                      style: ElevatedButton.styleFrom(
                        backgroundColor: Colors.blue[900],
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      ),
                      onPressed: _submit,
                      child: const Text('SIMPAN DATA VACUUM', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                    ),
                  ),
                  const SizedBox(height: 40),
                ],
              ),
            ),
          ),
    );
  }

  Widget _buildDay1Fields() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('Initial Records (Day 1)', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.blue)),
        const SizedBox(height: 16),
        _buildField(_portableVacuumController, 'Portable Vacuum Value (mTorr)'),
        _buildField(_tempController, 'Temperature (°C)'),
        _buildField(_machineStartController, 'Machine Vacuum at Start (mTorr)'),
        _buildField(_portableStopController, 'Portable Vacuum when Machine Stops (mTorr)'),
        _buildField(_machineStopController, 'Machine Vacuum at Stop (mTorr)'),
        _buildField(_tempStopController, 'Temperature at Machine Stop (°C)'),
      ],
    );
  }

  Widget _buildDay2to5Fields() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text('Morning Record (Pagi)', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.orange[800])),
        const SizedBox(height: 12),
        _buildField(_morningVacuumController, 'Morning Portable Vacuum Value (mTorr)'),
        _buildField(_morningTempController, 'Morning Temperature (°C)'),
        if (widget.activityData['morning_timestamp'] != null)
           _buildTimestamp(widget.activityData['morning_timestamp']),
        
        const SizedBox(height: 24),
        Text('Evening Record (Sore)', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.blue[800])),
        const SizedBox(height: 12),
        _buildField(_eveningVacuumController, 'Evening Portable Vacuum Value (mTorr)'),
        _buildField(_eveningTempController, 'Evening Temperature (°C)'),
        if (widget.activityData['evening_timestamp'] != null)
           _buildTimestamp(widget.activityData['evening_timestamp']),
      ],
    );
  }

  Widget _buildTimestamp(dynamic timestamp) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8.0, left: 4),
      child: Row(
        children: [
          const Icon(Icons.access_time, size: 14, color: Colors.grey),
          const SizedBox(width: 4),
          Text('Tercatat pada: $timestamp', style: const TextStyle(fontSize: 12, color: Colors.grey, fontStyle: FontStyle.italic)),
        ],
      ),
    );
  }

  Widget _buildField(TextEditingController controller, String label) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 16),
      child: TextFormField(
        controller: controller,
        keyboardType: const TextInputType.numberWithOptions(decimal: true),
        decoration: InputDecoration(
          labelText: label,
          labelStyle: const TextStyle(fontSize: 14),
          border: const OutlineInputBorder(),
          filled: true,
          fillColor: Colors.white,
          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
        ),
      ),
    );
  }
}
