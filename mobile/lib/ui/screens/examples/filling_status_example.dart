import 'package:flutter/material.dart';
import '../../data/models/filling_status.dart';
import '../../ui/widgets/filling_status_selector.dart';

/// Example screen demonstrating FillingStatusSelector usage
class FillingStatusExample extends StatefulWidget {
  const FillingStatusExample({super.key});

  @override
  State<FillingStatusExample> createState() => _FillingStatusExampleState();
}

class _FillingStatusExampleState extends State<FillingStatusExample> {
  FillingStatus? _dropdownStatus;
  FillingStatus? _buttonGroupStatus;
  
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Filling Status Selector Demo'),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Example 1: Dropdown Style
          const Text(
            'Example 1: Dropdown Style',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),
          FillingStatusSelector(
            selectedStatus: _dropdownStatus,
            onChanged: (status) {
              setState(() => _dropdownStatus = status);
              print('Dropdown selected: ${status?.code}');
            },
            label: 'Select Filling Status',
            isRequired: true,
            style: SelectorStyle.dropdown,
          ),
          if (_dropdownStatus != null)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Text(
                'Selected: ${_dropdownStatus!.displayName} (${_dropdownStatus!.code})',
                style: const TextStyle(fontStyle: FontStyle.italic),
              ),
            ),
          
          const SizedBox(height: 32),
          const Divider(),
          const SizedBox(height: 32),
          
          // Example 2: Button Group Style
          const Text(
            'Example 2: Button Group Style',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),
          FillingStatusSelector(
            selectedStatus: _buttonGroupStatus,
            onChanged: (status) {
              setState(() => _buttonGroupStatus = status);
              print('Button group selected: ${status?.code}');
            },
            label: 'Choose Status',
            style: SelectorStyle.buttonGroup,
          ),
          if (_buttonGroupStatus != null)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Text(
                'Selected: ${_buttonGroupStatus!.displayName} (${_buttonGroupStatus!.code})',
                style: const TextStyle(fontStyle: FontStyle.italic),
              ),
            ),
          
          const SizedBox(height: 32),
          const Divider(),
          const SizedBox(height: 32),
          
          // Example 3: Disabled State
          const Text(
            'Example 3: Disabled State',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),
          FillingStatusSelector(
            selectedStatus: FillingStatus.filled,
            onChanged: (status) {},
            label: 'Read-Only Status',
            enabled: false,
            style: SelectorStyle.dropdown,
          ),
          
          const SizedBox(height: 32),
          
          // Example 4: Form Integration
          const Text(
            'Example 4: Form Integration',
            style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 16),
          _buildFormExample(),
        ],
      ),
    );
  }
  
  Widget _buildFormExample() {
    final formKey = GlobalKey<FormState>();
    FillingStatus? formStatus;
    
    return Form(
      key: formKey,
      child: Column(
        children: [
          FillingStatusSelector(
            selectedStatus: formStatus,
            onChanged: (status) {
              setState(() => formStatus = status);
            },
            label: 'Filling Status (Required)',
            isRequired: true,
            style: SelectorStyle.dropdown,
          ),
          const SizedBox(height: 16),
          ElevatedButton(
            onPressed: () {
              if (formKey.currentState!.validate()) {
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text('Form valid! Status: ${formStatus?.code}'),
                  ),
                );
              }
            },
            child: const Text('Validate Form'),
          ),
        ],
      ),
    );
  }
}

/// Example: How to integrate into InspectionFormScreen
/// 
/// Add this to your InspectionFormScreen state:
/// ```dart
/// FillingStatus? _selectedFillingStatus;
/// ```
/// 
/// Add this to your form (e.g., after PSV section):
/// ```dart
/// _buildSectionHeader('H. Filling Status'),
/// FillingStatusSelector(
///   selectedStatus: _selectedFillingStatus,
///   onChanged: (status) {
///     setState(() {
///       _selectedFillingStatus = status;
///       _formData['filling_status_code'] = status?.code;
///       _formData['filling_status_desc'] = status?.displayName;
///     });
///   },
///   label: 'Current Filling Status',
///   isRequired: false,
///   style: SelectorStyle.buttonGroup,
/// ),
/// const SizedBox(height: 16),
/// ```
/// 
/// The status will automatically be included in submission data.
