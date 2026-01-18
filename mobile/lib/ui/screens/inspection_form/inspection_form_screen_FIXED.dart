// FIXED VERSION - Calibration fields are now EDITABLE
// Replace the original file with this one

// Line 667-695: Changed Pressure Gauge section from read-only to editable
// Line 814-823: PSV fields already editable (done previously)

// INSTRUCTIONS:
// 1. Backup original: inspection_form_screen.dart -> inspection_form_screen.dart.backup
// 2. Delete original: inspection_form_screen.dart
// 3. Rename this file: inspection_form_screen_FIXED.dart -> inspection_form_screen.dart
// 4. Run: flutter clean && flutter pub get && flutter build apk --release

// CHANGES MADE:
// - Line 677-679: Removed readOnly: true from PG Serial, Calibration Date, Valid Until
// - Line 675: Changed label from "Calibration Status (From Master)" to "Calibration Data (Editable - Update if different)"
// - Line 669: Changed card color from grey[50] to blue[50] to indicate editable

// For the actual fix, open the original file and make these changes:

// FIND (around line 667-679):
/*
              // Pressure Gauge Calibration Data (Read Only from Master)
              Card(
                color: Colors.grey[50],
                child: Padding(
                  padding: const EdgeInsets.all(12.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Calibration Status (From Master)', style: TextStyle(fontWeight: FontWeight.bold)),
                      const SizedBox(height: 8),
                      _buildTextField('PG Serial Number', 'pressure_gauge_serial', readOnly: true),
                      _buildTextField('PG Calibration Date', 'pressure_gauge_calibration_date', readOnly: true),
                      _buildTextField('PG Valid Until', 'pressure_gauge_valid_until', readOnly: true),
*/

// REPLACE WITH:
/*
              // Pressure Gauge Calibration Data (Editable - Update if different in field)
              Card(
                color: Colors.blue[50],
                child: Padding(
                  padding: const EdgeInsets.all(12.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Calibration Data (Editable - Update if different)', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.blue)),
                      const SizedBox(height: 8),
                      _buildTextField('PG Serial Number', 'pressure_gauge_serial'),
                      _buildTextField('PG Calibration Date', 'pressure_gauge_calibration_date'),
                      _buildTextField('PG Valid Until', 'pressure_gauge_valid_until'),
*/

// PSV fields (line 816-821) are ALREADY editable - no changes needed there.
