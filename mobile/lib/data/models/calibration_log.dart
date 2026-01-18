class CalibrationLog {
  final int id;
  final int isotankId;
  final String itemName;
  final String? serialNumber;
  final String? description;
  final DateTime? plannedDate;
  final String? vendor;
  final String status;
  final String? isotankNumber;

  CalibrationLog({
    required this.id,
    required this.isotankId,
    required this.itemName,
    this.serialNumber,
    this.description,
    this.plannedDate,
    this.vendor,
    required this.status,
    this.isotankNumber,
  });

  factory CalibrationLog.fromJson(Map<String, dynamic> json) {
    return CalibrationLog(
      id: json['id'],
      isotankId: json['isotank_id'],
      itemName: json['item_name'],
      serialNumber: json['serial_number'],
      description: json['description'],
      plannedDate: json['planned_date'] != null ? DateTime.parse(json['planned_date']) : null,
      vendor: json['vendor'],
      status: json['status'],
      isotankNumber: json['isotank'] != null ? json['isotank']['iso_number'] : null,
    );
  }
}
