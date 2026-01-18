# FLUTTER IMPLEMENTATION GUIDE
## Receiver Confirmation Screen

---

## 📱 RECEIVER CONFIRMATION SCREEN

### Requirements:
- Display all 10 general condition items on **single screen** (NOT wizard/stepper)
- For each item: Show inspector condition (read-only) + ACCEPT/REJECT buttons + optional remark + optional photo
- Submit button at bottom
- Validation: All 10 items must have decision before submit

---

## 🎨 UI DESIGN SUGGESTION

```
┌─────────────────────────────────────┐
│  Receiver Confirmation              │
│  ISO-001 → Singapore                │
├─────────────────────────────────────┤
│                                     │
│  Inspector: Jane Doe                │
│  Date: 2026-01-10                   │
│  Receiver: John Smith               │
│                                     │
├─────────────────────────────────────┤
│                                     │
│  1. Surface                         │
│  Inspector: ✓ Good                  │
│  ┌─────────┐ ┌─────────┐           │
│  │ ACCEPT  │ │ REJECT  │           │
│  └─────────┘ └─────────┘           │
│  Remark: [Optional text...]         │
│  📷 Add Photo (Optional)            │
│                                     │
├─────────────────────────────────────┤
│                                     │
│  2. Frame                           │
│  Inspector: ⚠ Need Attention        │
│  ┌─────────┐ ┌─────────┐           │
│  │ ACCEPT  │ │ REJECT  │           │
│  └─────────┘ └─────────┘           │
│  Remark: [Optional text...]         │
│  📷 Add Photo (Optional)            │
│                                     │
├─────────────────────────────────────┤
│  ... (8 more items)                 │
├─────────────────────────────────────┤
│                                     │
│  ┌─────────────────────────────┐   │
│  │   SUBMIT CONFIRMATION       │   │
│  └─────────────────────────────┘   │
│                                     │
└─────────────────────────────────────┘
```

---

## 📂 FILE STRUCTURE

```
lib/
├── screens/
│   └── receiver/
│       ├── receiver_confirmation_screen.dart
│       └── receiver_dashboard_screen.dart
├── widgets/
│   └── receiver/
│       ├── confirmation_item_card.dart
│       └── decision_buttons.dart
├── models/
│   ├── receiver_confirmation.dart
│   └── inspection_for_receiver.dart
└── services/
    └── receiver_service.dart
```

---

## 💾 DATA MODELS

### `lib/models/inspection_for_receiver.dart`

```dart
class InspectionForReceiver {
  final InspectionJob job;
  final MasterIsotank isotank;
  final User inspector;
  final String inspectionDate;
  final String destination;
  final String receiverName;
  final List<InspectionItem> items;
  final bool alreadyConfirmed;

  InspectionForReceiver({
    required this.job,
    required this.isotank,
    required this.inspector,
    required this.inspectionDate,
    required this.destination,
    required this.receiverName,
    required this.items,
    required this.alreadyConfirmed,
  });

  factory InspectionForReceiver.fromJson(Map<String, dynamic> json) {
    return InspectionForReceiver(
      job: InspectionJob.fromJson(json['job']),
      isotank: MasterIsotank.fromJson(json['isotank']),
      inspector: User.fromJson(json['inspector']),
      inspectionDate: json['inspection_date'],
      destination: json['destination'] ?? '',
      receiverName: json['receiver_name'] ?? '',
      items: (json['items'] as List)
          .map((item) => InspectionItem.fromJson(item))
          .toList(),
      alreadyConfirmed: json['already_confirmed'] ?? false,
    );
  }
}

class InspectionItem {
  final String key;
  final String name;
  final String inspectorCondition;
  final String inspectorConditionFormatted;

  InspectionItem({
    required this.key,
    required this.name,
    required this.inspectorCondition,
    required this.inspectorConditionFormatted,
  });

  factory InspectionItem.fromJson(Map<String, dynamic> json) {
    return InspectionItem(
      key: json['key'],
      name: json['name'],
      inspectorCondition: json['inspector_condition'],
      inspectorConditionFormatted: json['inspector_condition_formatted'],
    );
  }
}
```

### `lib/models/receiver_confirmation.dart`

```dart
class ReceiverConfirmation {
  final String itemKey;
  String? decision; // 'ACCEPT' or 'REJECT'
  String? remark;
  File? photo;

  ReceiverConfirmation({
    required this.itemKey,
    this.decision,
    this.remark,
    this.photo,
  });

  bool get isComplete => decision != null;
}
```

---

## 🔌 SERVICE LAYER

### `lib/services/receiver_service.dart`

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'dart:io';
import '../models/inspection_for_receiver.dart';
import 'api_service.dart';

class ReceiverService {
  final ApiService _apiService = ApiService();

  /// Get inspection details for receiver
  Future<InspectionForReceiver> getInspectionForReceiver(int jobId) async {
    try {
      final response = await _apiService.get('/inspector/jobs/$jobId/receiver-details');
      
      if (response['success']) {
        return InspectionForReceiver.fromJson(response['data']);
      } else {
        throw Exception(response['message'] ?? 'Failed to load inspection');
      }
    } catch (e) {
      throw Exception('Error loading inspection: $e');
    }
  }

  /// Submit receiver confirmations
  Future<Map<String, dynamic>> submitConfirmations(
    int jobId,
    Map<String, ReceiverConfirmation> confirmations,
  ) async {
    try {
      var request = http.MultipartRequest(
        'POST',
        Uri.parse('${_apiService.baseUrl}/inspector/jobs/$jobId/receiver-confirm'),
      );

      // Add auth token
      final token = await _apiService.getToken();
      request.headers['Authorization'] = 'Bearer $token';

      // Add confirmations
      for (var entry in confirmations.entries) {
        final itemKey = entry.key;
        final confirmation = entry.value;

        // Decision (required)
        request.fields['confirmations[$itemKey][decision]'] = confirmation.decision!;

        // Remark (optional)
        if (confirmation.remark != null && confirmation.remark!.isNotEmpty) {
          request.fields['confirmations[$itemKey][remark]'] = confirmation.remark!;
        }

        // Photo (optional)
        if (confirmation.photo != null) {
          request.files.add(
            await http.MultipartFile.fromPath(
              'confirmations[$itemKey][photo]',
              confirmation.photo!.path,
            ),
          );
        }
      }

      final streamedResponse = await request.send();
      final response = await http.Response.fromStream(streamedResponse);
      final data = json.decode(response.body);

      if (response.statusCode == 200 && data['success']) {
        return data;
      } else {
        throw Exception(data['message'] ?? 'Failed to submit confirmations');
      }
    } catch (e) {
      throw Exception('Error submitting confirmations: $e');
    }
  }
}
```

---

## 🎯 SCREEN IMPLEMENTATION

### `lib/screens/receiver/receiver_confirmation_screen.dart`

```dart
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'dart:io';
import '../../models/inspection_for_receiver.dart';
import '../../models/receiver_confirmation.dart';
import '../../services/receiver_service.dart';
import '../../widgets/receiver/confirmation_item_card.dart';

class ReceiverConfirmationScreen extends StatefulWidget {
  final int jobId;

  const ReceiverConfirmationScreen({Key? key, required this.jobId}) : super(key: key);

  @override
  _ReceiverConfirmationScreenState createState() => _ReceiverConfirmationScreenState();
}

class _ReceiverConfirmationScreenState extends State<ReceiverConfirmationScreen> {
  final ReceiverService _receiverService = ReceiverService();
  final ImagePicker _imagePicker = ImagePicker();

  InspectionForReceiver? _inspection;
  Map<String, ReceiverConfirmation> _confirmations = {};
  bool _isLoading = true;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    _loadInspection();
  }

  Future<void> _loadInspection() async {
    try {
      final inspection = await _receiverService.getInspectionForReceiver(widget.jobId);
      
      // Initialize confirmations map
      final confirmations = <String, ReceiverConfirmation>{};
      for (var item in inspection.items) {
        confirmations[item.key] = ReceiverConfirmation(itemKey: item.key);
      }

      setState(() {
        _inspection = inspection;
        _confirmations = confirmations;
        _isLoading = false;
      });

      // Check if already confirmed
      if (inspection.alreadyConfirmed) {
        _showAlreadyConfirmedDialog();
      }
    } catch (e) {
      setState(() => _isLoading = false);
      _showErrorDialog('Failed to load inspection: $e');
    }
  }

  void _updateDecision(String itemKey, String decision) {
    setState(() {
      _confirmations[itemKey]!.decision = decision;
    });
  }

  void _updateRemark(String itemKey, String remark) {
    setState(() {
      _confirmations[itemKey]!.remark = remark;
    });
  }

  Future<void> _pickPhoto(String itemKey) async {
    final XFile? image = await _imagePicker.pickImage(
      source: ImageSource.camera,
      maxWidth: 1920,
      maxHeight: 1080,
      imageQuality: 85,
    );

    if (image != null) {
      setState(() {
        _confirmations[itemKey]!.photo = File(image.path);
      });
    }
  }

  void _removePhoto(String itemKey) {
    setState(() {
      _confirmations[itemKey]!.photo = null;
    });
  }

  bool _validateConfirmations() {
    // Check if all items have a decision
    for (var confirmation in _confirmations.values) {
      if (!confirmation.isComplete) {
        return false;
      }
    }
    return true;
  }

  Future<void> _submitConfirmations() async {
    if (!_validateConfirmations()) {
      _showErrorDialog('Please make a decision (ACCEPT or REJECT) for all items.');
      return;
    }

    setState(() => _isSubmitting = true);

    try {
      final result = await _receiverService.submitConfirmations(
        widget.jobId,
        _confirmations,
      );

      setState(() => _isSubmitting = false);

      // Show success dialog
      _showSuccessDialog(result);
    } catch (e) {
      setState(() => _isSubmitting = false);
      _showErrorDialog('Failed to submit confirmations: $e');
    }
  }

  void _showSuccessDialog(Map<String, dynamic> result) {
    final allAccepted = result['data']['all_accepted'] ?? false;
    final message = result['message'] ?? 'Confirmation submitted successfully';

    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: Text(allAccepted ? 'All Items Accepted' : 'Some Items Rejected'),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(context).pop(); // Close dialog
              Navigator.of(context).pop(); // Return to previous screen
            },
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  void _showErrorDialog(String message) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Error'),
        content: Text(message),
        actions: [
          TextButton(
            onPressed: () => Navigator.of(context).pop(),
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  void _showAlreadyConfirmedDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => AlertDialog(
        title: const Text('Already Confirmed'),
        content: const Text('This inspection has already been confirmed and cannot be modified.'),
        actions: [
          TextButton(
            onPressed: () {
              Navigator.of(context).pop(); // Close dialog
              Navigator.of(context).pop(); // Return to previous screen
            },
            child: const Text('OK'),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return Scaffold(
        appBar: AppBar(title: const Text('Receiver Confirmation')),
        body: const Center(child: CircularProgressIndicator()),
      );
    }

    if (_inspection == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('Receiver Confirmation')),
        body: const Center(child: Text('Failed to load inspection')),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Receiver Confirmation'),
        backgroundColor: Colors.blue,
      ),
      body: Column(
        children: [
          // Header Card
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(16),
            color: Colors.blue.shade50,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  '${_inspection!.isotank.isoNumber} → ${_inspection!.destination}',
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                Text('Inspector: ${_inspection!.inspector.name}'),
                Text('Date: ${_inspection!.inspectionDate}'),
                Text('Receiver: ${_inspection!.receiverName}'),
              ],
            ),
          ),

          // Items List
          Expanded(
            child: ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: _inspection!.items.length,
              itemBuilder: (context, index) {
                final item = _inspection!.items[index];
                final confirmation = _confirmations[item.key]!;

                return ConfirmationItemCard(
                  itemNumber: index + 1,
                  item: item,
                  confirmation: confirmation,
                  onDecisionChanged: (decision) => _updateDecision(item.key, decision),
                  onRemarkChanged: (remark) => _updateRemark(item.key, remark),
                  onPickPhoto: () => _pickPhoto(item.key),
                  onRemovePhoto: () => _removePhoto(item.key),
                );
              },
            ),
          ),

          // Submit Button
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(16),
            child: ElevatedButton(
              onPressed: _isSubmitting || _inspection!.alreadyConfirmed
                  ? null
                  : _submitConfirmations,
              style: ElevatedButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 16),
                backgroundColor: Colors.blue,
              ),
              child: _isSubmitting
                  ? const CircularProgressIndicator(color: Colors.white)
                  : const Text(
                      'SUBMIT CONFIRMATION',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                    ),
            ),
          ),
        ],
      ),
    );
  }
}
```

---

## 🧩 WIDGET COMPONENTS

### `lib/widgets/receiver/confirmation_item_card.dart`

```dart
import 'package:flutter/material.dart';
import 'dart:io';
import '../../models/inspection_for_receiver.dart';
import '../../models/receiver_confirmation.dart';

class ConfirmationItemCard extends StatelessWidget {
  final int itemNumber;
  final InspectionItem item;
  final ReceiverConfirmation confirmation;
  final Function(String) onDecisionChanged;
  final Function(String) onRemarkChanged;
  final VoidCallback onPickPhoto;
  final VoidCallback onRemovePhoto;

  const ConfirmationItemCard({
    Key? key,
    required this.itemNumber,
    required this.item,
    required this.confirmation,
    required this.onDecisionChanged,
    required this.onRemarkChanged,
    required this.onPickPhoto,
    required this.onRemovePhoto,
  }) : super(key: key);

  Color _getConditionColor(String condition) {
    switch (condition) {
      case 'good':
        return Colors.green;
      case 'not_good':
        return Colors.red;
      case 'need_attention':
        return Colors.orange;
      default:
        return Colors.grey;
    }
  }

  IconData _getConditionIcon(String condition) {
    switch (condition) {
      case 'good':
        return Icons.check_circle;
      case 'not_good':
        return Icons.cancel;
      case 'need_attention':
        return Icons.warning;
      default:
        return Icons.help;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Item Header
            Row(
              children: [
                CircleAvatar(
                  backgroundColor: Colors.blue,
                  child: Text(
                    '$itemNumber',
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Text(
                    item.name,
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),

            // Inspector Condition (Read-Only)
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: _getConditionColor(item.inspectorCondition).withOpacity(0.1),
                borderRadius: BorderRadius.circular(8),
                border: Border.all(
                  color: _getConditionColor(item.inspectorCondition),
                  width: 1,
                ),
              ),
              child: Row(
                children: [
                  Icon(
                    _getConditionIcon(item.inspectorCondition),
                    color: _getConditionColor(item.inspectorCondition),
                  ),
                  const SizedBox(width: 8),
                  Text(
                    'Inspector: ${item.inspectorConditionFormatted}',
                    style: TextStyle(
                      fontWeight: FontWeight.w500,
                      color: _getConditionColor(item.inspectorCondition),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Decision Buttons
            const Text(
              'Your Decision:',
              style: TextStyle(fontWeight: FontWeight.w500),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Expanded(
                  child: ElevatedButton(
                    onPressed: () => onDecisionChanged('ACCEPT'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: confirmation.decision == 'ACCEPT'
                          ? Colors.green
                          : Colors.grey.shade300,
                      foregroundColor: confirmation.decision == 'ACCEPT'
                          ? Colors.white
                          : Colors.black,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                    child: const Text('ACCEPT', style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: ElevatedButton(
                    onPressed: () => onDecisionChanged('REJECT'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: confirmation.decision == 'REJECT'
                          ? Colors.red
                          : Colors.grey.shade300,
                      foregroundColor: confirmation.decision == 'REJECT'
                          ? Colors.white
                          : Colors.black,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                    ),
                    child: const Text('REJECT', style: TextStyle(fontWeight: FontWeight.bold)),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 16),

            // Remark (Optional)
            TextField(
              decoration: const InputDecoration(
                labelText: 'Remark (Optional)',
                border: OutlineInputBorder(),
                hintText: 'Add your remark here...',
              ),
              maxLines: 2,
              maxLength: 500,
              onChanged: onRemarkChanged,
            ),
            const SizedBox(height: 12),

            // Photo (Optional)
            if (confirmation.photo != null)
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Stack(
                    children: [
                      ClipRRect(
                        borderRadius: BorderRadius.circular(8),
                        child: Image.file(
                          confirmation.photo!,
                          height: 150,
                          width: double.infinity,
                          fit: BoxFit.cover,
                        ),
                      ),
                      Positioned(
                        top: 8,
                        right: 8,
                        child: IconButton(
                          onPressed: onRemovePhoto,
                          icon: const Icon(Icons.close, color: Colors.white),
                          style: IconButton.styleFrom(
                            backgroundColor: Colors.red,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                ],
              ),
            OutlinedButton.icon(
              onPressed: onPickPhoto,
              icon: const Icon(Icons.camera_alt),
              label: Text(confirmation.photo != null ? 'Change Photo' : 'Add Photo (Optional)'),
              style: OutlinedButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 12),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
```

---

## 🔄 NAVIGATION

### From Receiver Dashboard to Confirmation Screen:

```dart
Navigator.push(
  context,
  MaterialPageRoute(
    builder: (context) => ReceiverConfirmationScreen(jobId: job.id),
  ),
);
```

---

## ✅ VALIDATION CHECKLIST

Before enabling submit button:
- [ ] All 10 items have a decision (ACCEPT or REJECT)
- [ ] No null decisions in confirmations map

After successful submission:
- [ ] Show success message with result
- [ ] Navigate back to dashboard
- [ ] Refresh job list

---

## 🎨 UI/UX ENHANCEMENTS

1. **Progress Indicator:**
   - Show "X/10 items decided" at the top
   - Disable submit until all 10 complete

2. **Color Coding:**
   - ACCEPT button: Green when selected
   - REJECT button: Red when selected
   - Inspector condition: Color-coded badge

3. **Confirmation Dialog:**
   - Show summary before final submit
   - "You are about to ACCEPT 8 items and REJECT 2 items. Continue?"

4. **Photo Preview:**
   - Tap to view full-size
   - Swipe to delete

5. **Offline Support:**
   - Save draft locally
   - Submit when online

---

## 🧪 TESTING

1. **Test All Accept:**
   - Select ACCEPT for all 10 items
   - Submit
   - Verify location updated

2. **Test Any Reject:**
   - Select REJECT for at least 1 item
   - Submit
   - Verify location NOT updated
   - Verify status = receiver_rejected

3. **Test Validation:**
   - Try to submit with incomplete decisions
   - Verify error message shown

4. **Test Immutability:**
   - Submit once
   - Try to access screen again
   - Verify "already confirmed" message

---

**Last Updated:** 2026-01-10  
**Flutter Version:** 3.x  
**Dart Version:** 3.x
