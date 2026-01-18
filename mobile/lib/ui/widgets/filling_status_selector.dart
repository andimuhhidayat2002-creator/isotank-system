import 'package:flutter/material.dart';
import '../../data/models/filling_status.dart';

/// Widget for selecting filling status
/// Can be used as dropdown or button group
class FillingStatusSelector extends StatelessWidget {
  final FillingStatus? selectedStatus;
  final Function(FillingStatus?) onChanged;
  final bool enabled;
  final String? label;
  final bool isRequired;
  final SelectorStyle style;

  const FillingStatusSelector({
    super.key,
    required this.selectedStatus,
    required this.onChanged,
    this.enabled = true,
    this.label,
    this.isRequired = false,
    this.style = SelectorStyle.dropdown,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (label != null)
          Padding(
            padding: const EdgeInsets.only(bottom: 8.0),
            child: Row(
              children: [
                Text(
                  label!,
                  style: const TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 14,
                  ),
                ),
                if (isRequired)
                  const Text(
                    ' *',
                    style: TextStyle(color: Colors.red),
                  ),
              ],
            ),
          ),
        style == SelectorStyle.dropdown
            ? _buildDropdown(context)
            : _buildButtonGroup(context),
      ],
    );
  }

  Widget _buildDropdown(BuildContext context) {
    return DropdownButtonFormField<FillingStatus>(
      value: selectedStatus,
      decoration: InputDecoration(
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(8),
        ),
        contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        filled: true,
        fillColor: enabled ? Colors.white : Colors.grey[100],
      ),
      hint: const Text('Select Status'),
      items: FillingStatus.values.map((status) {
        return DropdownMenuItem<FillingStatus>(
          value: status,
          child: Row(
            children: [
              _getStatusIcon(status),
              const SizedBox(width: 8),
              Text(status.displayName),
            ],
          ),
        );
      }).toList(),
      onChanged: enabled ? onChanged : null,
      validator: isRequired
          ? (value) => value == null ? 'Please select a status' : null
          : null,
    );
  }

  Widget _buildButtonGroup(BuildContext context) {
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: FillingStatus.values.map((status) {
        final isSelected = selectedStatus == status;
        return FilterChip(
          selected: isSelected,
          label: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              _getStatusIcon(status, size: 16),
              const SizedBox(width: 6),
              Text(status.displayName),
            ],
          ),
          onSelected: enabled
              ? (selected) => onChanged(selected ? status : null)
              : null,
          backgroundColor: Colors.grey[200],
          selectedColor: _getStatusColor(status).withOpacity(0.2),
          checkmarkColor: _getStatusColor(status),
          labelStyle: TextStyle(
            color: isSelected ? _getStatusColor(status) : Colors.black87,
            fontWeight: isSelected ? FontWeight.w600 : FontWeight.normal,
          ),
        );
      }).toList(),
    );
  }

  Icon _getStatusIcon(FillingStatus status, {double size = 20}) {
    IconData iconData;
    Color color = _getStatusColor(status);

    switch (status) {
      case FillingStatus.readyToFill:
        iconData = Icons.check_circle_outline;
        break;
      case FillingStatus.filled:
        iconData = Icons.check_circle;
        break;
      case FillingStatus.underMaintenance:
        iconData = Icons.build_circle;
        break;
      case FillingStatus.waitingTeamCalibration:
        iconData = Icons.schedule;
        break;
      case FillingStatus.ongoingInspection:
        iconData = Icons.access_time;
        break;
      case FillingStatus.classSurvey:
        iconData = Icons.assignment;
        break;
    }

    return Icon(iconData, color: color, size: size);
  }

  Color _getStatusColor(FillingStatus status) {
    switch (status) {
      case FillingStatus.readyToFill:
        return Colors.green;
      case FillingStatus.filled:
        return Colors.blue;
      case FillingStatus.underMaintenance:
        return Colors.orange;
      case FillingStatus.waitingTeamCalibration:
        return Colors.amber;
      case FillingStatus.ongoingInspection:
        return Colors.grey;
      case FillingStatus.classSurvey:
        return Colors.purple;
    }
  }
}

/// Style options for the selector
enum SelectorStyle {
  dropdown,
  buttonGroup,
}
