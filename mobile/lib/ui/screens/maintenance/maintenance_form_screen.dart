import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/foundation.dart';
import 'package:image_picker/image_picker.dart';
import '../../../data/services/api_service.dart';
import '../../../data/services/file_manager_service.dart';

class MaintenanceFormScreen extends StatefulWidget {
  final int jobId;
  final Map<String, dynamic> jobData;

  const MaintenanceFormScreen({
    super.key, 
    required this.jobId, 
    required this.jobData
  });

  @override
  State<MaintenanceFormScreen> createState() => _MaintenanceFormScreenState();
}

class _MaintenanceFormScreenState extends State<MaintenanceFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final ApiService _apiService = ApiService();
  final ImagePicker _picker = ImagePicker();
  
  final _workDescriptionController = TextEditingController();
  final _sparepartController = TextEditingController();
  final _qtyController = TextEditingController();
  
  String? _photoDuringPath;
  XFile? _photoDuringFile; // For Web upload
  String? _afterPhotoPath;
  XFile? _afterPhotoFile; // For Web upload
  bool _isLoading = false;
  String _currentStatus = 'open';

  @override
  void initState() {
    super.initState();
    _currentStatus = widget.jobData['status'] ?? 'open';
    _loadExistingData();
  }

  void _loadExistingData() {
    // Load existing work description, sparepart, qty if any
    _workDescriptionController.text = widget.jobData['work_description'] ?? '';
    _sparepartController.text = widget.jobData['sparepart'] ?? '';
    _qtyController.text = widget.jobData['qty']?.toString() ?? '';
  }

  Future<void> _takePhotoDuring() async {
    final XFile? photo = await _picker.pickImage(
      source: ImageSource.camera,
      imageQuality: 85,
      maxWidth: 1920,
      maxHeight: 1080,
    );
    
    if (photo != null) {
      try {
        final isotank = widget.jobData['isotank'] ?? {};
        final isoNumber = isotank['iso_number'] ?? 'UNKNOWN';

        if (kIsWeb) {
          setState(() {
            _photoDuringFile = photo;
            _photoDuringPath = photo.path; // Blob URL
          });
        } else {
          final photoFile = File(photo.path);
          final savedFile = await FileManagerService.saveMaintenancePhoto(
            photoFile,
            isoNumber,
            widget.jobId,
            suffix: 'during',
          );
          
          setState(() {
            _photoDuringPath = savedFile.path;
            _photoDuringFile = null;
          });
        }
        
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('During photo saved!'),
              duration: Duration(seconds: 2),
            ),
          );
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Error saving photo: $e')),
          );
        }
      }
    }
  }

  Future<void> _takePhotoAfter() async {
    final XFile? photo = await _picker.pickImage(
      source: ImageSource.camera,
      imageQuality: 85,
      maxWidth: 1920,
      maxHeight: 1080,
    );
    
    if (photo != null) {
      try {
        final isotank = widget.jobData['isotank'] ?? {};
        final isoNumber = isotank['iso_number'] ?? 'UNKNOWN';

        if (kIsWeb) {
          setState(() {
            _afterPhotoFile = photo;
            _afterPhotoPath = photo.path; // Blob URL
          });
        } else {
          final photoFile = File(photo.path);
          final savedFile = await FileManagerService.saveMaintenancePhoto(
            photoFile,
            isoNumber,
            widget.jobId,
            suffix: 'after',
          );
          
          setState(() {
            _afterPhotoPath = savedFile.path;
            _afterPhotoFile = null;
          });
        }
        
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('After photo saved!'),
              duration: Duration(seconds: 2),
            ),
          );
        }
      } catch (e) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Error saving photo: $e')),
          );
        }
      }
    }
  }

  Future<void> _updateStatus(String status) async {
    if (status == 'closed' && !_formKey.currentState!.validate()) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Work description is required')),
      );
      return;
    }

    if (status == 'closed' && _afterPhotoPath == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('After photo is required to close the job')),
      );
      return;
    }

    setState(() => _isLoading = true);

    try {
      await _apiService.updateMaintenanceStatus(
        widget.jobId,
        status,
        workDescription: _workDescriptionController.text.trim().isNotEmpty 
            ? _workDescriptionController.text.trim() 
            : null,
        photoDuring: _photoDuringFile ?? _photoDuringPath,
        afterPhoto: _afterPhotoFile ?? _afterPhotoPath,
        sparepart: _sparepartController.text.trim().isNotEmpty 
            ? _sparepartController.text.trim() 
            : null,
        qty: _qtyController.text.trim().isNotEmpty 
            ? int.tryParse(_qtyController.text.trim()) 
            : null,
      );

      setState(() {
        _currentStatus = status;
        _isLoading = false;
      });

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text(status == 'closed' 
                ? 'Maintenance Job Closed Successfully!' 
                : 'Status updated successfully'),
          ),
        );
        
        if (status == 'closed') {
          Navigator.pop(context, true);
        }
      }
    } catch (e) {
      setState(() => _isLoading = false);
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error: $e'), backgroundColor: Colors.red),
        );
      }
    }
  }

  Widget _buildPhotoSection(String title, String? photoPath, VoidCallback onTakePhoto, {bool isReadOnly = false}) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          title,
          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
        ),
        const SizedBox(height: 8),
        GestureDetector(
          onTap: isReadOnly ? null : onTakePhoto,
          child: Container(
            height: 200,
            decoration: BoxDecoration(
              border: Border.all(color: Colors.grey),
              borderRadius: BorderRadius.circular(8),
              color: Colors.grey[100],
            ),
            child: photoPath == null
                ? Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(
                        Icons.camera_alt,
                        size: 50,
                        color: isReadOnly ? Colors.grey : Colors.grey[700],
                      ),
                      const SizedBox(height: 8),
                      Text(
                        isReadOnly ? 'No photo available' : 'Tap to take photo',
                        style: TextStyle(color: isReadOnly ? Colors.grey : Colors.black),
                      ),
                    ],
                  )
                : ClipRRect(
                    borderRadius: BorderRadius.circular(8),
                    child: _buildPhotoWidget(photoPath),
                  ),
          ),
        ),
        if (photoPath != null && !isReadOnly)
          Padding(
            padding: const EdgeInsets.only(top: 8),
            child: OutlinedButton.icon(
              onPressed: () {
                setState(() {
                  if (title.contains('During')) {
                    _photoDuringPath = null;
                    _photoDuringFile = null;
                  } else if (title.contains('After')) {
                    _afterPhotoPath = null;
                    _afterPhotoFile = null;
                  }
                });
              },
              icon: const Icon(Icons.delete),
              label: const Text('Remove Photo'),
              style: OutlinedButton.styleFrom(foregroundColor: Colors.red),
            ),
          ),
      ],
    );
  }

  Widget _buildPhotoWidget(String photoPath) {
    if (photoPath.startsWith('http')) {
      // Network image (from server)
      return Image.network(
        'http://192.168.1.4:8000/storage/$photoPath',
        fit: BoxFit.cover,
        width: double.infinity,
        height: 200,
        errorBuilder: (context, error, stackTrace) => Container(
          color: Colors.grey[200],
          child: const Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.wifi_off, size: 50, color: Colors.grey),
                SizedBox(height: 8),
                Text('Photo view available online only', textAlign: TextAlign.center, style: TextStyle(color: Colors.grey)),
              ],
            ),
          ),
        ),
      );
    } else if (photoPath.startsWith('blob:') || photoPath.startsWith('http://') || photoPath.startsWith('https://')) {
      // Web blob URL or network URL
      return Image.network(
        photoPath,
        fit: BoxFit.cover,
        width: double.infinity,
        height: 200,
        errorBuilder: (context, error, stackTrace) => Container(
          color: Colors.grey[200],
          child: const Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.broken_image, size: 50, color: Colors.grey),
                Text('Could not load photo'),
              ],
            ),
          ),
        ),
      );
    } else {
      // Local file
      if (kIsWeb) {
        return const Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.image_not_supported, size: 50, color: Colors.grey),
              Text('Local file not supported on Web'),
            ],
          ),
        );
      }
      return Image.file(
        File(photoPath),
        fit: BoxFit.cover,
        width: double.infinity,
        height: 200,
        errorBuilder: (context, error, stackTrace) => Container(
          color: Colors.grey[200],
          child: const Center(
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(Icons.wifi_off, size: 50, color: Colors.grey),
                SizedBox(height: 8),
                Text('Photo view available online only', textAlign: TextAlign.center, style: TextStyle(color: Colors.grey)),
              ],
            ),
          ),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final isotank = widget.jobData['isotank'] ?? {};
    final beforePhoto = widget.jobData['before_photo'];
    final existingPhotoDuring = widget.jobData['photo_during'];
    final existingAfterPhoto = widget.jobData['after_photo'];

    // Load existing photos if available
    if (existingPhotoDuring != null && _photoDuringPath == null) {
      _photoDuringPath = existingPhotoDuring;
    }
    if (existingAfterPhoto != null && _afterPhotoPath == null) {
      _afterPhotoPath = existingAfterPhoto;
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Maintenance Job'),
        backgroundColor: const Color(0xFF0D47A1),
      ),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Job Info Card
            Card(
              color: Colors.blue[50],
              child: Padding(
                padding: const EdgeInsets.all(12.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'ISO Number: ${isotank['iso_number'] ?? 'N/A'}',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
                    ),
                    const SizedBox(height: 8),
                    Text('Item: ${widget.jobData['source_item'] ?? 'N/A'}'),
                    Text('Description: ${widget.jobData['description'] ?? 'N/A'}'),
                    const SizedBox(height: 8),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                      decoration: BoxDecoration(
                        color: _currentStatus == 'closed' 
                            ? Colors.green 
                            : _currentStatus == 'on_progress' 
                                ? Colors.orange 
                                : Colors.blue,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Text(
                        _currentStatus.toUpperCase().replaceAll('_', ' '),
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Before Photo (Read-Only from Inspection)
            _buildPhotoSection(
              'Before Photo (from Inspection)',
              beforePhoto,
              () {}, // No action, read-only
              isReadOnly: true,
            ),
            const SizedBox(height: 24),

            // During Photo (Optional, for on_progress)
            if (_currentStatus == 'open' || _currentStatus == 'on_progress' || _currentStatus == 'not_complete')
              Column(
                children: [
                  _buildPhotoSection(
                    'During Photo (Optional)',
                    _photoDuringPath ?? existingPhotoDuring,
                    _takePhotoDuring,
                  ),
                  const SizedBox(height: 24),
                ],
              ),

            // Work Description
            TextFormField(
              controller: _workDescriptionController,
              maxLines: 4,
              decoration: const InputDecoration(
                labelText: 'Work Description',
                hintText: 'Describe the work performed...',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.description),
              ),
              validator: (val) {
                if (_currentStatus == 'closed' && (val == null || val.trim().isEmpty)) {
                  return 'Work description is required';
                }
                return null;
              },
            ),
            const SizedBox(height: 16),

            // Sparepart & Qty
            Row(
              children: [
                Expanded(
                  flex: 3,
                  child: TextFormField(
                    controller: _sparepartController,
                    decoration: const InputDecoration(
                      labelText: 'Sparepart Name (Optional)',
                      border: OutlineInputBorder(),
                      prefixIcon: Icon(Icons.build),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  flex: 1,
                  child: TextFormField(
                    controller: _qtyController,
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(
                      labelText: 'Qty',
                      border: OutlineInputBorder(),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // After Photo (Required for closed)
            if (_currentStatus != 'closed')
              _buildPhotoSection(
                'After Photo (Required to close)',
                _afterPhotoPath ?? existingAfterPhoto,
                _takePhotoAfter,
              ),
            
            if (_currentStatus == 'closed')
              _buildPhotoSection(
                'After Photo',
                _afterPhotoPath ?? existingAfterPhoto,
                () {}, // Read-only if already closed
                isReadOnly: true,
              ),
            
            const SizedBox(height: 32),

            // Action Buttons
            if (_currentStatus == 'open')
              Column(
                children: [
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: _isLoading ? null : () => _updateStatus('on_progress'),
                      icon: const Icon(Icons.play_arrow),
                      label: const Text('START WORK'),
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        backgroundColor: Colors.orange,
                        foregroundColor: Colors.white,
                      ),
                    ),
                  ),
                  const SizedBox(height: 12),
                ],
              ),

            if (_currentStatus == 'on_progress' || _currentStatus == 'not_complete')
              Column(
                children: [
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: _isLoading ? null : () => _updateStatus('closed'),
                      icon: const Icon(Icons.check_circle),
                      label: const Text('CLOSE JOB'),
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        backgroundColor: const Color(0xFF0D47A1),
                        foregroundColor: Colors.white,
                      ),
                    ),
                  ),
                ],
              ),

            const SizedBox(height: 32),
          ],
        ),
      ),
    );
  }

  @override
  void dispose() {
    _workDescriptionController.dispose();
    _sparepartController.dispose();
    _qtyController.dispose();
    super.dispose();
  }
}
