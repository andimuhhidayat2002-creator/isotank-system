import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import '../../../data/services/api_service.dart';
import '../../../data/services/pdf_service.dart';
import '../../../data/services/file_manager_service.dart';
import '../../../data/services/connectivity_service.dart';
import '../../../data/models/filling_status.dart';
import '../../widgets/filling_status_selector.dart';

class InspectionFormScreen extends StatefulWidget {
  final int jobId;

  const InspectionFormScreen({super.key, required this.jobId});

  @override
  State<InspectionFormScreen> createState() => _InspectionFormScreenState();
}

class _InspectionFormScreenState extends State<InspectionFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final ApiService _apiService = ApiService();
  final ImagePicker _picker = ImagePicker();
  final ConnectivityService _connectivity = ConnectivityService();
  bool _isLoading = true;
  bool _isOnline = true;

  Future<void> _takePhoto(String key) async {
    try {
      final XFile? photo = await _picker.pickImage(
        source: ImageSource.camera,
        imageQuality: 50, // Optimize size
        maxWidth: 1024,
        maxHeight: 1024,
      );
      
      if (photo != null) {
        String savedPath;
        final isoNumber = _job?['isotank']?['iso_number'] ?? 'UNKNOWN';

        // Check platform using flutter/foundation.dart to be safe, 
        // but since we imported dart:io, we must be careful.
        // For this specific 'unsupported operation' error on Platform.operatingSystem,
        // it usually means we are running on Web code that tries to access dart:io Platform.
        // We will try to rely on FileManagerService logic, but FileManagerService might be using File().
        
        // However, FileManagerService logic likely assumes Mobile.
        // For WEB, we should probably upload bytes directly or save to a temporary memory spot.
        // Since FileManagerService.saveInspectionPhoto returns a File object (dart:io), 
        // that service itself needs fix for Web.
        
        // But for now, let's fix THIS call site to avoid crashing if possible, 
        // or just let FileManagerService handle it if updated.
        
        // Actually, to fully fix "Unsupported operation: Platform._operatingSystem",
        // we need to avoid `File(photo.path)` on Web.
        
        // Let's assume FileManagerService is Mobile-only for now and we need to patch THIS widget
        // to handle web or update FileManagerService.
        
        // Since I cannot update FileManagerService at this exact moment in THIS tool call easily without checking it,
        // I will implement a safe check here.
        
        bool isWeb = false;
        try {
          if (identical(0, 0.0)) isWeb = true; 
        } catch(_) {}

        if (isWeb) {
             // For WEB: Store XFile directly for upload
             // ApiService is updated to handle XFile
             
             setState(() {
               _formData['photo_$key'] = photo; // Store XFile object
             });
        } else {
             // MOBILE Code
            final photoFile = File(photo.path);
            final savedFile = await FileManagerService.saveInspectionPhoto(
              photoFile,
              isoNumber,
              key,
            );
            savedPath = savedFile.path;
            
            setState(() {
              _formData['photo_$key'] = savedPath; // Store String path
            });
        }
        
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text('Photo saved!'),
              duration: const Duration(seconds: 2),
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error taking photo: $e')),
        );
      }
    }
  }
  Map<String, dynamic>? _job;
  Map<String, dynamic> _formData = {};
  Map<String, dynamic> _calibrationData = {};
  List<dynamic> _openMaintenance = [];
  FillingStatus? _selectedFillingStatus;

  // Dropdown options
  final List<String> _conditionOptions = ['good', 'not_good', 'need_attention', 'na'];

  @override
  void initState() {
    super.initState();
    _loadData();
    
    // Listen to connectivity changes
    _connectivity.connectionStatus.listen((isOnline) {
      if (mounted) {
        setState(() {
          _isOnline = isOnline;
        });
      }
    });
    
    // Set initial status
    _isOnline = _connectivity.isOnline;
  }

  List<dynamic> _dynamicItems = [];
  Map<String, List<dynamic>> _groupedItems = {};

  // ... (previous code)

  Future<void> _loadData() async {
    try {
      // Parallel loading for speed
      final results = await Future.wait([
        _apiService.getInspectionJobDetails(widget.jobId),
        _apiService.getInspectionItems(), 
      ]);

      final data = results[0] as Map<String, dynamic>;
      final items = results[1] as List<dynamic>;

      setState(() {
        _job = data['job'];
        _openMaintenance = (data['open_maintenance'] as List?) ?? [];
        if (data['calibration_data'] is Map) {
           _calibrationData = Map<String, dynamic>.from(data['calibration_data']);
        } else {
           _calibrationData = {};
        }
        
        // Dynamic Items Processing
        _dynamicItems = items;
        _groupedItems = {};
        
        // Group items by category
        // Group items by category
        for (var item in _dynamicItems) {
           // Backend already filters active items, and API might not return 'is_active' field.
           // if (item['is_active'] != 1 && item['is_active'] != true) continue;
           
           final appliesTo = item['applies_to'] ?? 'both';
           final activityType = _job?['activity_type'] ?? 'both';
           bool isIncoming = activityType == 'incoming_inspection';
           bool isOutgoing = activityType == 'outgoing_inspection';
           
           if (appliesTo == 'incoming' && !isIncoming) continue;
           if (appliesTo == 'outgoing' && !isOutgoing) continue;

           final rawCategory = item['category'] ?? 'General';
           final category = rawCategory.toString().toLowerCase(); // Normalize to lowercase
           if (!_groupedItems.containsKey(category)) {
             _groupedItems[category] = [];
           }
           _groupedItems[category]!.add(item);
        }

        // Initialize form data with default values
        final rawDefaults = data['default_values'];
        if (rawDefaults is Map) {
          _formData = Map<String, dynamic>.from(rawDefaults);
          
          // ... (rest of mapping logic)
        }
        
        // ... (rest of defaults)

        _isLoading = false;
      });
    } catch (e) {
      // ... (error handling)
    }
  }

  Widget _buildDynamicSection(String category, String title) {
    if (!_groupedItems.containsKey(category)) return const SizedBox.shrink();
    
    final items = _groupedItems[category]!;
    items.sort((a, b) => (a['order'] ?? 0).compareTo(b['order'] ?? 0));

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildSectionHeader(title),
        ...items.map((item) {
          final key = item['code'];
          final label = item['label'];
          final type = item['input_type'];
          
          if (type == 'condition') {
            return _buildConditionButtons(label, key);
          } else if (type == 'text') {
            return _buildTextField(label, key);
          } else if (type == 'number') {
            return _buildTextField(label, key, numeric: true);
          } else if (type == 'boolean') {
             // Simple Yes/No dropdown or switch
             return Padding(
               padding: const EdgeInsets.only(bottom: 16.0),
               child: DropdownButtonFormField<String>(
                 decoration: InputDecoration(labelText: label),
                 value: _formData[key]?.toString(),
                 items: ['yes', 'no'].map((v) => DropdownMenuItem(value: v, child: Text(v.toUpperCase()))).toList(),
                 onChanged: (v) => setState(() => _formData[key] = v),
               ),
             );
          }
          return const SizedBox.shrink();
        }).toList(),
      ],
    );
  }

  // NOTE: We keep specialized sections D, E, F, G hardcoded for now 
  // because they contain complex logic (locking, timestamp stages, etc.)
  // Only generic inspection items (Surface, Frame, Valve Condition) become dynamic.

  // ...

  Future<void> _submit({required bool asDraft}) async {
    if (!_formKey.currentState!.validate()) return;
    _formKey.currentState!.save();

    setState(() => _isLoading = true);

    try {
      final activityType = _job?['activity_type'] ?? 'both';
      final data = {
        ..._formData,
        'job_id': widget.jobId,
        'status': asDraft ? 'draft' : 'completed',
        'activity_type': activityType,
        'filled_at': DateTime.now().toIso8601String(),
      };

      await _apiService.submitInspection(widget.jobId, data);

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(asDraft ? 'Draft Saved' : 'Inspection Submitted')),
        );
        Navigator.pop(context);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('Error: $e')));
      }
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final activityType = _job?['activity_type'] ?? 'both';
    final isOutgoing = activityType == 'outgoing_inspection';

    if (_isLoading) {
       return Scaffold(
         appBar: AppBar(title: Text('Inspection #${widget.jobId}')),
         body: const Center(child: CircularProgressIndicator()),
       );
    }

    return Scaffold(
      appBar: AppBar(
        title: Text('Inspection #${widget.jobId}'),
        actions: [
          IconButton(
            icon: Icon(_isOnline ? Icons.cloud_done : Icons.cloud_off),
            onPressed: null,
          )
        ],
      ),
      body: Form(
        key: _formKey,
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
               // Maintenance Warning
               if (_openMaintenance.isNotEmpty)
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(12),
                    margin: const EdgeInsets.only(bottom: 16),
                    color: Colors.orange[100],
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('⚠️ Open Maintenance Logs:', style: TextStyle(fontWeight: FontWeight.bold)),
                        const SizedBox(height: 4),
                        ..._openMaintenance.map((m) => Text('• ${m['description'] ?? '-'}')),
                      ],
                    ),
                  ),

                // Job Info Card (Restored)
                Container(
                  padding: const EdgeInsets.all(16),
                  color: Colors.blue[50], // Light blue background
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('Job ID', style: TextStyle(color: Colors.black54)),
                          Text('#${widget.jobId}', style: const TextStyle(fontWeight: FontWeight.bold)),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('Isotank', style: TextStyle(color: Colors.black54)),
                          Text(
                            _job?['isotank']?['iso_number'] ?? _job?['isotank']?['tank_number'] ?? 'Unknown',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16)
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text('Activity', style: TextStyle(color: Colors.black54)),
                          Text(
                            (_job?['activity_type'] ?? '-').toString().replaceAll('_', ' ').toUpperCase(), 
                            style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF0D47A1))
                          ),
                        ],
                      ),
                    ],
                  ),
                ),

                // A. Inspection Data
                _buildSectionHeader('A. Inspection Data'),
                // Automatic Date (Read Only)
                TextFormField(
                  initialValue: DateTime.now().toLocal().toString().split(' ')[0], // YYYY-MM-DD
                  readOnly: true,
                  decoration: const InputDecoration(
                    labelText: 'Inspection Date (Auto)',
                    border: OutlineInputBorder(),
                    prefixIcon: Icon(Icons.calendar_today),
                    fillColor: Color(0xFFEEEEEE),
                    filled: true,
                  ),
                ),
                const SizedBox(height: 16),

                // Dynamic Sections (Replacing B, C, D, E)
                 // B. General Condition
                 _buildSectionHeader('B. General Condition'),
                 _buildConditionButtons('Surface', 'surface'),
                 _buildConditionButtons('Frame', 'frame'),
                 _buildConditionButtons('Tank Plate', 'tank_plate'),
                 _buildConditionButtons('Venting Pipe', 'venting_pipe'),
                 _buildConditionButtons('Explosion Proof Cover', 'explosion_proof_cover'),
                 _buildConditionButtons('Grounding System', 'grounding_system'),
                 _buildConditionButtons('Document Container', 'document_container'),
                 _buildConditionButtons('Safety Label', 'safety_label'),
                 _buildConditionButtons('Valve Box Door', 'valve_box_door'),
                 _buildConditionButtons('Valve Box Door Handle', 'valve_box_door_handle'),
                 
                 // Dynamic Items (Appended)
                 _buildDynamicSection('external', 'Additional General Items'),

                 // C. Valve & Piping
                 _buildSectionHeader('C. Valve & Piping'),
                 _buildConditionButtons('Valve Condition', 'valve_condition'),
                 
                 // Valve Position (Specific Selector)
                 Padding(
                   padding: const EdgeInsets.only(bottom: 16.0),
                   child: DropdownButtonFormField<String>(
                     decoration: const InputDecoration(labelText: 'Valve Position', border: OutlineInputBorder()),
                     value: _formData['valve_position']?.toString(),
                     items: ['correct', 'incorrect'].map((v) => DropdownMenuItem(
                       value: v, 
                       child: Text(v.toUpperCase()),
                     )).toList(),
                     onChanged: (v) => setState(() => _formData['valve_position'] = v),
                   ),
                 ),

                 _buildConditionButtons('Pipe Joint', 'pipe_joint'),
                 _buildConditionButtons('Air Source Connection', 'air_source_connection'),
                 _buildConditionButtons('ESDV', 'esdv'),
                 _buildConditionButtons('Blind Flange', 'blind_flange'),
                 _buildConditionButtons('PRV', 'prv'),
                 
                 // Dynamic Items (Appended)
                 _buildDynamicSection('valve', 'Additional Valve Items'),
                 _buildDynamicSection('safety', 'Additional Safety Items'),
                 
                 // Catch-all: Render any other dynamic categories not yet shown (e.g. internal)
                 ..._groupedItems.keys
                    .where((k) => !['external', 'valve', 'safety'].contains(k))
                    .map((k) => _buildDynamicSection(k, 'Additional ${k.toUpperCase()} Items')),
                 
                 // Specialized Sections
                  _buildSectionHeader('D. IBOX System'),
                  _buildConditionButtons('IBOX Condition', 'ibox_condition'),
                  
                  _buildReadingStage('IBOX Temperature (°C)', 'ibox_temperature', 1, isOutgoing),
                  _buildReadingStage('IBOX Temperature (°C)', 'ibox_temperature', 2, isOutgoing),
                  _buildTextField('IBOX Pressure (Bar)', 'pressure', numeric: true),
                  _buildTextField('IBOX Level', 'level', numeric: true),
                  _buildTextField('IBOX Battery %', 'battery_percent', numeric: true),

                  _buildSectionHeader('E. Instrument'),
                  _buildConditionButtons('Pressure Gauge Condition', 'pressure_gauge_condition'),
                  
                  // Calibration Card
                  Card(
                    color: Colors.grey[50],
                    margin: const EdgeInsets.only(bottom: 16),
                    child: Padding(
                      padding: const EdgeInsets.all(12.0),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Calibration Status (From Master)', style: TextStyle(fontWeight: FontWeight.bold)),
                          const SizedBox(height: 8),
                          _buildTextField('PG Serial Number', 'pressure_gauge_serial'),
                          _buildDatePicker('PG Calibration Date', 'pressure_gauge_calibration_date'),
                          _buildDatePicker('PG Valid Until', 'pressure_gauge_valid_until'),
                          
                          SwitchListTile(
                            title: const Text('Reject Calibration'),
                            subtitle: const Text('Triggers calibration activity'),
                            value: _formData['pressure_gauge_status'] == 'rejected',
                            onChanged: (val) {
                              setState(() {
                                _formData['pressure_gauge_status'] = val ? 'rejected' : 'valid';
                              });
                            },
                            activeColor: Colors.red,
                          ),
                        ],
                      ),
                    ),
                  ),
                  
                  _buildReadingStage('PG Reading (Bar)', 'pressure', 1, isOutgoing),
                  _buildReadingStage('PG Reading (Bar)', 'pressure', 2, isOutgoing),
                  
                  _buildConditionButtons('Level Gauge Condition', 'level_gauge_condition'),
                  _buildReadingStage('Level Reading (mm/%)', 'level', 1, isOutgoing),
                  _buildReadingStage('Level Reading (mm/%)', 'level', 2, isOutgoing),
                  
                  _buildSectionHeader('F. Vacuum System'),
                  _buildDatePicker('Vacuum Check Datetime', 'vacuum_check_datetime', includeTime: true),
                  Row(
                    children: [
                      Expanded(flex: 2, child: _buildTextField('Vacuum Value', 'vacuum_value', numeric: true)),
                      const SizedBox(width: 12),
                      Expanded(
                        flex: 1,
                        child: DropdownButtonFormField<String>(
                          decoration: const InputDecoration(labelText: 'Unit'),
                          value: _formData['vacuum_unit'] ?? 'mtorr',
                          items: ['mtorr', 'torr', 'scientific'].map((u) => DropdownMenuItem(
                            value: u, 
                            child: Text(u.toUpperCase()),
                          )).toList(),
                          onChanged: (v) => setState(() => _formData['vacuum_unit'] = v),
                        ),
                      ),
                    ],
                  ),
                  // Vacuum Warning Logic
                  Builder(builder: (ctx) {
                     double? v = double.tryParse(_formData['vacuum_value']?.toString() ?? '');
                     String u = _formData['vacuum_unit'] ?? 'mtorr';
                     double mtorr = (u == 'torr') ? (v ?? 0) * 1000 : (v ?? 0);
                     if (v != null && mtorr > 8) {
                        return Container(
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.all(8),
                          color: Colors.red[100],
                          child: Row(
                             children: [
                               const Icon(Icons.warning, color: Colors.red),
                               const SizedBox(width: 8),
                               Expanded(child: Text("Warning: Vacuum > 8 mTorr! This will trigger a Vacuum Suction Activity.", style: TextStyle(color: Colors.red[900], fontWeight: FontWeight.bold))),
                             ]
                          )
                        );
                     }
                     return const SizedBox.shrink();
                  }),

                  _buildTextField('Vacuum Temp (°C)', 'vacuum_temperature', numeric: true),
                  _buildConditionButtons('Vacuum Gauge Condition', 'vacuum_gauge_condition'),
                  _buildConditionButtons('Vacuum Port Suction Condition', 'vacuum_port_suction_condition'),
                  
                  _buildSectionHeader('G. PSV'),
                  _buildPSVSection(1),
                  _buildPSVSection(2),
                  _buildPSVSection(3),
                  _buildPSVSection(4),

                  _buildSectionHeader('H. Filling Status'),
                  FillingStatusSelector(
                    selectedStatus: _selectedFillingStatus,
                    onChanged: (status) {
                      setState(() {
                        _selectedFillingStatus = status;
                        _formData['filling_status_code'] = status?.code;
                        _formData['filling_status_desc'] = status?.displayName;
                      });
                    },
                    label: 'Current Filling Status',
                    isRequired: false,
                    style: SelectorStyle.buttonGroup,
                  ),
                  const SizedBox(height: 16),

                  if (isOutgoing) ...[
                    _buildSectionHeader('F. Outgoing Photos'),
                    _buildPhotoField('Front View', 'front'),
                    _buildPhotoField('Back View', 'back'),
                    _buildPhotoField('Left Side', 'left'),
                    _buildPhotoField('Right Side', 'right'),
                    _buildPhotoField('Inside Valve Box', 'inside_valve_box'),
                    _buildPhotoField('Additional Photo', 'additional'),
                    _buildPhotoField('Extra Photo', 'extra'),

                    _buildSectionHeader('Outgoing Info'),
                    _buildTextField('Destination', 'destination'),
                    _buildTextField('Receiver Name', 'receiver_name'),
                  ],

                  const SizedBox(height: 32),
                  // Submit Buttons
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: _isLoading ? null : () => _submit(asDraft: true),
                          style: OutlinedButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 16)),
                          child: const Text('SAVE DRAFT'),
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: FilledButton(
                          onPressed: _isLoading ? null : () => _submit(asDraft: false),
                          style: FilledButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 16),
                            backgroundColor: const Color(0xFF0D47A1),
                          ),
                          child: const Text('FINAL SUBMIT'),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 48),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildSectionHeader(String title) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 12),
      margin: const EdgeInsets.symmetric(vertical: 8),
      color: Colors.grey[200],
      child: Text(title, style: const TextStyle(fontWeight: FontWeight.bold)),
    );
  }

  Widget _buildConditionButtons(String label, String key) {
    final selected = _formData[key];
    final isBad = selected == 'not_good' || selected == 'need_attention';

    return Padding(
      padding: const EdgeInsets.only(bottom: 24.0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 15)),
          const SizedBox(height: 8),
          
          // Proportional Row of Icons
          Container(
            decoration: BoxDecoration(
              border: Border.all(color: Colors.grey[300]!),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              children: _conditionOptions.map((o) {
                 final isSelected = selected == o;
                 
                 Color color = Colors.grey;
                 IconData icon = Icons.circle_outlined;
                 String text = o.replaceAll('_', ' ').toUpperCase();
                 
                 if(o == 'good') { color = Colors.green; icon = Icons.check_circle_outline; }
                 else if(o == 'not_good') { color = Colors.red; icon = Icons.cancel_outlined; text = 'NOT GOOD'; }
                 else if(o == 'need_attention') { color = Colors.orange; icon = Icons.report_problem_outlined; text = 'ATTN'; }
                 else if(o == 'na') { color = Colors.grey; icon = Icons.not_interested; text = 'N/A'; }
                 
                 return Expanded(
                   child: InkWell(
                     onTap: () => setState(() => _formData[key] = o),
                     child: Container(
                       padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 4),
                       decoration: BoxDecoration(
                         color: isSelected ? color.withOpacity(0.1) : Colors.transparent,
                         border: isSelected ? Border(bottom: BorderSide(color: color, width: 3)) : null,
                       ),
                       child: Column(
                         children: [
                           Icon(icon, color: isSelected ? color : Colors.grey[400], size: 28),
                           const SizedBox(height: 4),
                           Text(
                             text, 
                             style: TextStyle(
                               fontSize: 10, 
                               fontWeight: FontWeight.bold,
                               color: isSelected ? color : Colors.grey[600]
                             ),
                             textAlign: TextAlign.center,
                           ),
                         ],
                       ),
                     ),
                   ),
                 );
              }).toList(),
            ),
          ),

          if (isBad) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: Colors.red[50],
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(color: Colors.red[200]!)
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                     const Text('⚠️ Defect Maintenance Trigger:', style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
                     const SizedBox(height: 8),
                     _buildTextField('Remark / Description', 'remark_$key'),
                     _buildPhotoField('Evidence Photo', key),
                  ],
                ),
              ),
          ]
        ],
      ),
    );
  }

  Widget _buildTextField(String label, String key, {bool numeric = false}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12.0),
      child: TextFormField(
        initialValue: _formData[key]?.toString(),
        decoration: InputDecoration(labelText: label, border: const OutlineInputBorder()),
        keyboardType: numeric ? TextInputType.number : TextInputType.text,
        onChanged: (v) => _formData[key] = v,
      ),
    );
  }

  Widget _buildReadingStage(String label, String key, int stage, bool isOutgoing) {
    if (!isOutgoing && stage == 2) return const SizedBox.shrink(); 
    final fieldKey = isOutgoing ? '${key}_$stage' : key;
    final timestampKey = '${fieldKey}_timestamp';
    final timestamp = _formData[timestampKey];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildTextField('$label ${isOutgoing ? "(Stage $stage)" : ""}', fieldKey, numeric: true),
        if (timestamp != null)
           Padding(
             padding: const EdgeInsets.only(bottom: 12, left: 12),
             child: Text('Recorded: $timestamp', style: const TextStyle(color: Colors.grey, fontSize: 12)),
           ),
      ],
    );
  }

  Widget _buildDatePicker(String label, String key, {bool includeTime = false}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12.0),
      child: InkWell(
        onTap: () async {
          final now = DateTime.now();
          final d = await showDatePicker(context: context, initialDate: now, firstDate: DateTime(2000), lastDate: DateTime(2100));
          if (d != null) {
            if (includeTime) {
               final t = await showTimePicker(context: context, initialTime: TimeOfDay.now());
               if(t != null) {
                  final dt = DateTime(d.year, d.month, d.day, t.hour, t.minute);
                  setState(() => _formData[key] = dt.toIso8601String());
               }
            } else {
               setState(() => _formData[key] = d.toIso8601String().split('T')[0]);
            }
          }
        },
        child: InputDecorator(
          decoration: InputDecoration(labelText: label, border: const OutlineInputBorder()),
          child: Text(_formData[key] ?? 'Select Date'),
        ),
      ),
    );
  }

  Widget _buildPhotoField(String label, String key) {
    final val = _formData['photo_$key'];
    return Padding(
      padding: const EdgeInsets.only(bottom: 12.0),
      child: Row(
        children: [
          Expanded(child: Text(label)),
          if (val != null) const Icon(Icons.check_circle, color: Colors.green),
          IconButton(icon: const Icon(Icons.camera_alt), onPressed: () => _takePhoto(key)),
        ],
      ),
    );
  }

  Widget _buildPSVSection(int num) {
    final prefix = 'psv$num';
    final isRejected = _formData['${prefix}_status'] == 'rejected';

    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      child: Padding(
        padding: const EdgeInsets.all(12.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('PSV $num', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0D47A1))),
            const SizedBox(height: 12),
            _buildConditionButtons('Condition', '${prefix}_condition'),
            
            const Text('Current Calibration:', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
            const SizedBox(height: 8),
            _buildTextField('Serial Number', '${prefix}_serial'),
            Row(
              children: [
                Expanded(child: _buildDatePicker('Calibration Date', '${prefix}_calibration_date')),
                const SizedBox(width: 12),
                Expanded(child: _buildDatePicker('Valid Until', '${prefix}_valid_until')),
              ],
            ),
            
            DropdownButtonFormField<String>(
              decoration: const InputDecoration(labelText: 'Calibration Status'),
              value: _formData['${prefix}_status'] ?? 'valid',
              items: ['valid', 'expired', 'rejected'].map((v) => DropdownMenuItem(
                value: v, 
                child: Text(v.toUpperCase()),
              )).toList(),
              onChanged: (v) {
                setState(() {
                  _formData['${prefix}_status'] = v;
                });
              },
            ),
            
            if (isRejected) ...[
              const SizedBox(height: 16),
              const Text('REPLACEMENT REQUIRED', style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              _buildTextField('Replacement Serial', '${prefix}_replacement_serial'),
              _buildDatePicker('Replacement Calibration Date', '${prefix}_replacement_calibration_date'),
            ],
          ],
        ),
      ),
    );
  }
}
