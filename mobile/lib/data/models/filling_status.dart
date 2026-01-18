/// Enum for Isotank Filling Status
/// Maps to filling_status_code in backend
enum FillingStatus {
  ongoingInspection('ongoing_inspection', 'Ongoing Inspection'),
  readyToFill('ready_to_fill', 'Ready to Fill'),
  filled('filled', 'Filled'),
  underMaintenance('under_maintenance', 'Under Maintenance'),
  waitingTeamCalibration('waiting_team_calibration', 'Waiting Team Calibration'),
  classSurvey('class_survey', 'Class Survey');

  final String code;
  final String displayName;

  const FillingStatus(this.code, this.displayName);

  /// Convert from backend code to enum
  static FillingStatus? fromCode(String? code) {
    if (code == null || code.isEmpty) return null;
    
    try {
      return FillingStatus.values.firstWhere(
        (status) => status.code == code,
      );
    } catch (e) {
      return null;
    }
  }

  /// Get all status codes as list
  static List<String> get allCodes => FillingStatus.values.map((e) => e.code).toList();

  /// Get all display names as list
  static List<String> get allDisplayNames => FillingStatus.values.map((e) => e.displayName).toList();

  @override
  String toString() => displayName;
}

/// Extension for easy conversion
extension FillingStatusExtension on String {
  FillingStatus? toFillingStatus() => FillingStatus.fromCode(this);
}
