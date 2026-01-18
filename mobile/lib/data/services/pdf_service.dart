import 'dart:io';
import 'package:flutter/services.dart';
import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:open_file/open_file.dart';
import 'file_manager_service.dart';

class PdfService {
  static Future<File> generateIncomingPdf(Map<String, dynamic> jobData, Map<String, dynamic> formData) async {
    final pdf = pw.Document();
    
    // Load header image
    Uint8List? headerImage;
    try {
      final ByteData headerBytes = await rootBundle.load('assets/images/header_kln.png');
      headerImage = headerBytes.buffer.asUint8List();
    } catch (e) {
      print('Error loading header image: $e');
      try {
        final ByteData fallback = await rootBundle.load('assets/images/header_logo.png');
        headerImage = fallback.buffer.asUint8List();
      } catch(_) {}
    }

    // Prepare Inspector Name - Robust fallback
    final String inspectorName = 
        jobData['inspector']?['name'] ?? 
        jobData['inspection_job']?['inspector']?['name'] ?? 
        jobData['inspector_name'] ?? 
        '-';

    pdf.addPage(
      pw.Page(
        pageFormat: PdfPageFormat.a4,
        margin: const pw.EdgeInsets.all(20),
        build: (pw.Context context) {
          return pw.Column(
            crossAxisAlignment: pw.CrossAxisAlignment.start,
            children: [
              // HEADER
              if (headerImage != null)
                pw.Container(
                  alignment: pw.Alignment.center,
                  height: 60,
                  width: double.infinity,
                  child: pw.Image(pw.MemoryImage(headerImage), fit: pw.BoxFit.contain),
                )
              else
                 pw.Center(child: pw.Text('PT KAYAN LNG NUSANTARA', style: pw.TextStyle(fontSize: 16, fontWeight: pw.FontWeight.bold))),

              pw.SizedBox(height: 10),
              
              // TITLE
              pw.Center(
                child: pw.Container(
                  padding: const pw.EdgeInsets.symmetric(vertical: 4, horizontal: 20),
                  color: PdfColors.blue100,
                  child: pw.Text(
                    'INCOMING INSPECTION REPORT',
                    style: pw.TextStyle(fontSize: 12, fontWeight: pw.FontWeight.bold),
                  ),
                ),
              ),
              pw.SizedBox(height: 10),
              
              // SECTION A: DATA OF TANK
              _buildSectionTitle('A. DATA OF TANK'),
              pw.Container(
                padding: const pw.EdgeInsets.all(6),
                decoration: pw.BoxDecoration(border: pw.Border.all(color: PdfColors.grey)),
                child: pw.Row(
                  mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                  children: [
                     _buildInfoItem('ISO Number', jobData['isotank']?['iso_number'] ?? jobData['iso_number'] ?? '-', width: 100),
                     _buildInfoItem('Product', jobData['isotank']?['product'] ?? '-', width: 80),
                     _buildInfoItem('Date', formData['inspection_date'] ?? '-', width: 80),
                     _buildInfoItem('Inspector', inspectorName, width: 120),
                  ]
                )
              ),
              
              pw.SizedBox(height: 10),
              
              // MAIN CONTENT (2 COLUMNS)
              pw.Expanded(
                child: pw.Row(
                  crossAxisAlignment: pw.CrossAxisAlignment.start,
                  children: [
                    // LEFT COLUMN
                    pw.Expanded(
                      flex: 1,
                      child: pw.Column(
                        children: [
                          // B. GENERAL
                          _buildCompactSection('B. GENERAL CONDITION', [
                             _buildConditionRow('Surface', formData['surface']),
                             _buildConditionRow('Frame', formData['frame']),
                             _buildConditionRow('Tank Plate', formData['tank_plate']),
                             _buildConditionRow('Venting Pipe', formData['venting_pipe']),
                             _buildConditionRow('Ex.Proof Cover', formData['explosion_proof_cover']),
                             _buildConditionRow('Grounding', formData['grounding_system']),
                             _buildConditionRow('Doc Container', formData['document_container']),
                             _buildConditionRow('Safety Label', formData['safety_label']),
                             _buildConditionRow('V.Box Door', formData['valve_box_door']),
                             _buildConditionRow('V.Door Handle', formData['valve_box_door_handle']),
                          ]),
                          pw.SizedBox(height: 6),
                          
                          // C. VALVES
                          _buildCompactSection('C. VALVES & PIPING', [
                             _buildConditionRow('Valve Cond.', formData['valve_condition']),
                             _buildConditionRow('Valve Pos.', formData['valve_position']),
                             _buildConditionRow('Pipe Joint', formData['pipe_joint']),
                             _buildConditionRow('Air Conn.', formData['air_source_connection']),
                             _buildConditionRow('ESDV', formData['esdv']),
                             _buildConditionRow('Blind Flange', formData['blind_flange']),
                             _buildConditionRow('PRV', formData['prv']),
                          ]),
                          pw.SizedBox(height: 6),

                          // D. IBOX
                          _buildCompactSection('D. IBOX SYSTEM', [
                             _buildConditionRow('Condition', formData['ibox_condition']),
                             _buildRow('Battery', '${formData['ibox_battery'] ?? formData['ibox_battery_percent'] ?? '-'} %'),
                             _buildRow('Pressure', '${formData['ibox_pressure'] ?? '-'}'),
                             _buildRow('Temp', '${formData['ibox_temperature'] ?? '-'}'),
                             _buildRow('Level', '${formData['ibox_level'] ?? '-'}'),
                          ]),
                        ]
                      )
                    ),
                    pw.SizedBox(width: 10),
                    
                    // RIGHT COLUMN
                    pw.Expanded(
                      flex: 1,
                      child: pw.Column(
                        children: [
                          // E. INSTRUMENTS
                          _buildCompactSection('E. INSTRUMENTS', [
                            _buildRowHeader('Pressure Gauge', formData['pressure_gauge_condition']),
                            _buildSubRow('SN: ${formData['pressure_gauge_serial'] ?? formData['pressure_gauge_serial_number'] ?? '-'}'),
                            _buildSubRow('Cal: ${formData['pressure_gauge_calibration_date'] ?? '-'}'),
                            _buildSubRow('Read: ${formData['pressure_1'] ?? '-'} MPa'),
                            pw.SizedBox(height: 4),
                            _buildRowHeader('Level Gauge', formData['level_gauge_condition']),
                            _buildSubRow('Read: ${formData['level_1'] ?? '-'} %'),
                          ]),
                          pw.SizedBox(height: 6),
                          
                          // F. VACUUM
                          _buildCompactSection('F. VACUUM SYSTEM', [
                            _buildRowHeader('Vacuum Gauge', formData['vacuum_gauge_condition']),
                            _buildRowHeader('Port Suction', formData['vacuum_port_suction_condition']),
                            _buildSubRow('Val: ${formData['vacuum_value'] ?? '-'} ${formData['vacuum_unit'] ?? 'torr'}'),
                            _buildSubRow('Temp: ${formData['vacuum_temperature'] ?? '-'} C'),
                          ]),
                           pw.SizedBox(height: 6),
                           
                           // G. PSV
                           _buildCompactSection('G. SAFETY VALVES (PSV)', [
                             for (int i=1; i<=4; i++) 
                               if (formData['psv${i}_serial'] != null || formData['psv${i}_serial_number'] != null || formData['psv${i}_condition'] != null)
                                 pw.Column(
                                   children: [
                                     _buildRowHeader('PSV #$i', formData['psv${i}_condition']),
                                     _buildSubRow('SN: ${formData['psv${i}_serial'] ?? formData['psv${i}_serial_number'] ?? '-'}'),
                                     _buildSubRow('Cal: ${formData['psv${i}_calibration_date'] ?? '-'}'),
                                     if(i < 4) pw.SizedBox(height: 4),
                                   ]
                                 )
                           ]),
                           
                           pw.SizedBox(height: 6),
                           
                           // NOTES
                           _buildSectionTitle('MAINTENANCE NOTES'),
                           pw.Container(
                             height: 40,
                             width: double.infinity,
                             padding: const pw.EdgeInsets.all(5),
                             decoration: pw.BoxDecoration(border: pw.Border.all(color: PdfColors.grey400)),
                             child: pw.Text(
                               formData['maintenance_notes']?.toString() ?? 'No specific notes.', 
                               style: const pw.TextStyle(fontSize: 7)
                             )
                           )
                        ]
                      )
                    ),
                  ],
                ),
              ),

              // SIGNATURES
              pw.SizedBox(height: 10),
              pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                children: [
                  _buildSignatureBox('Inspector Signature', inspectorName),
                  pw.Column(
                    crossAxisAlignment: pw.CrossAxisAlignment.end,
                    children: [
                       pw.Text('Generated: ${DateTime.now().toString().split('.')[0]}', style: const pw.TextStyle(fontSize: 6, color: PdfColors.grey)),
                       pw.Text('Page 1 of 1', style: const pw.TextStyle(fontSize: 8)),
                    ]
                  )
                ],
              ),
            ],
          );
        },
      ),
    );

    return await _saveIncomingPdf(pdf, 'Incoming_Inspection_${jobData['isotank']?['iso_number'] ?? ' Unknown'}_${DateTime.now().millisecondsSinceEpoch}.pdf');
  }

  static Future<File> generateOutgoingPdf(Map<String, dynamic> jobData, Map<String, dynamic> defaults, Map<String, dynamic> formData) async {
    final pdf = pw.Document();
    
    // Load header image
    Uint8List? headerImage;
    try {
      final ByteData headerBytes = await rootBundle.load('assets/images/header_kln.png');
      headerImage = headerBytes.buffer.asUint8List();
    } catch (e) {try{final ByteData fallback = await rootBundle.load('assets/images/header_logo.png');headerImage = fallback.buffer.asUint8List();}catch(_){}}

    final String inspectorName = jobData['inspector']?['name'] ?? jobData['inspector_name'] ?? '-';

    pdf.addPage(
      pw.Page(
        pageFormat: PdfPageFormat.a4,
        margin: const pw.EdgeInsets.all(20),
        build: (pw.Context context) {
          return pw.Column(
            children: [
               // Header
               if (headerImage != null)
                pw.Container(
                  alignment: pw.Alignment.center,
                  height: 60,
                  width: double.infinity,
                  child: pw.Image(pw.MemoryImage(headerImage), fit: pw.BoxFit.contain),
                ),
              pw.SizedBox(height: 10),
              
              // Title
              pw.Center(child: pw.Container(
                  padding: const pw.EdgeInsets.symmetric(vertical: 4, horizontal: 20),
                  color: PdfColors.green100,
                  child: pw.Text('OUTGOING INSPECTION REPORT', style: pw.TextStyle(fontSize: 12, fontWeight: pw.FontWeight.bold)),
              )),
              pw.SizedBox(height: 10),
              
              // Info
              pw.Container(
                padding: const pw.EdgeInsets.all(6),
                decoration: pw.BoxDecoration(border: pw.Border.all(color: PdfColors.grey)),
                child: pw.Row(
                  mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                  children: [
                     _buildInfoItem('ISO Number', jobData['isotank']?['iso_number'] ?? jobData['iso_number'] ?? '-', width: 90),
                     _buildInfoItem('Date', DateTime.now().toString().split(' ')[0], width: 90),
                     _buildInfoItem('Receiver', formData['receiver_name'] ?? 'Unknown', width: 90),
                     _buildInfoItem('Destination', jobData['destination'] ?? '-', width: 90),
                  ]
                )
              ),
              
              pw.SizedBox(height: 15),
              
              // Only showing General + Dispatch validation for brevity as consistent with incoming
              pw.Row(
                crossAxisAlignment: pw.CrossAxisAlignment.start,
                children: [
                   // General Condition (Same as Incoming)
                  pw.Expanded(
                    child: _buildCompactSection('B. GENERAL CONDITION', [
                      _buildConditionRow('Surface', formData['surface']),
                      _buildConditionRow('Frame', formData['frame']),
                      _buildConditionRow('Tank Plate', formData['tank_plate']),
                      _buildConditionRow('Venting Pipe', formData['venting_pipe']),
                      _buildConditionRow('Ex.Proof Cover', formData['explosion_proof_cover']),
                      _buildConditionRow('Grounding', formData['grounding_system']),
                      _buildConditionRow('Doc Container', formData['document_container']),
                      _buildConditionRow('Safety Label', formData['safety_label']),
                      _buildConditionRow('V.Box Door', formData['valve_box_door']),
                      _buildConditionRow('V.Door Handle', formData['valve_box_door_handle']),
                    ])
                  ),
                  pw.SizedBox(width: 20),
                  
                  // Validation
                  pw.Expanded(
                    child: pw.Column(
                      children: [
                        _buildSectionTitle('DISPATCH VALIDATION'),
                        pw.Container(
                          padding: const pw.EdgeInsets.all(10),
                          decoration: pw.BoxDecoration(border: pw.Border.all(color: PdfColors.grey300)),
                          child: pw.Column(
                            crossAxisAlignment: pw.CrossAxisAlignment.start,
                            children: [
                              pw.Text('READY FOR TRANSPORT', style: pw.TextStyle(fontSize: 14, fontWeight: pw.FontWeight.bold, color: PdfColors.green700)),
                              pw.SizedBox(height: 10),
                              pw.Text('Note: The isotank has been inspected and found free of defects influencing transport safety.', style: const pw.TextStyle(fontSize: 8)),
                            ]
                          )
                        ),
                         pw.SizedBox(height: 20),
                        _buildSectionTitle('SEAL NUMBERS'),
                        _buildRow('Seal 1', formData['seal_1'] ?? '-'),
                        _buildRow('Seal 2', formData['seal_2'] ?? '-'),
                        _buildRow('Seal 3', formData['seal_3'] ?? '-'),
                      ]
                    )
                  ),
                ]
              ),
              
              pw.Spacer(),
              
              // Signatures
              pw.Row(
                mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
                children: [
                  _buildSignatureBox('Dispatched By', inspectorName),
                  _buildSignatureBox('Received By', formData['receiver_name'] ?? 'Receiver'),
                ],
              ),
            ],
          );
        },
      ),
    );
     return await _saveOutgoingPdf(pdf, 'Outgoing_Inspection_${jobData['isotank']?['iso_number'] ?? 'ISO'}_${DateTime.now().millisecondsSinceEpoch}.pdf');
  }

  // --- Helpers ---
  
  static pw.Widget _buildInfoItem(String label, String value, {double? width}) {
    return pw.Container(
      width: width,
      child: pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.start,
        children: [
          pw.Text(label, style: const pw.TextStyle(fontSize: 6, color: PdfColors.grey600)),
          pw.Text(value, style: pw.TextStyle(fontSize: 8, fontWeight: pw.FontWeight.bold)),
        ]
      )
    );
  }

  static pw.Widget _buildSectionTitle(String title) {
    return pw.Container(
      width: double.infinity,
      color: PdfColors.grey200,
      padding: const pw.EdgeInsets.symmetric(vertical: 2, horizontal: 4),
      margin: const pw.EdgeInsets.only(bottom: 4),
      child: pw.Text(title, style: pw.TextStyle(fontSize: 8, fontWeight: pw.FontWeight.bold)),
    );
  }

  static pw.Widget _buildCompactSection(String title, List<pw.Widget> children) {
    return pw.Container(
      decoration: pw.BoxDecoration(border: pw.Border.all(color: PdfColors.grey300)),
      child: pw.Column(
        crossAxisAlignment: pw.CrossAxisAlignment.start,
        children: [
          _buildSectionTitle(title),
          pw.Padding(
            padding: const pw.EdgeInsets.symmetric(horizontal: 4, vertical: 2),
            child: pw.Column(children: children)
          )
        ],
      ),
    );
  }
  
  static pw.Widget _buildRow(String label, String value, {bool highlight = false}) {
    return pw.Padding(
      padding: const pw.EdgeInsets.only(bottom: 2),
      child: pw.Row(
        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
        children: [
          pw.Text(label, style: const pw.TextStyle(fontSize: 7)),
          pw.Text(value, style: pw.TextStyle(fontSize: 7, fontWeight: highlight ? pw.FontWeight.bold : pw.FontWeight.normal)),
        ],
      ),
    );
  }

  static pw.Widget _buildRowHeader(String label, dynamic condition) {
    return pw.Padding(
        padding: const pw.EdgeInsets.only(bottom: 1),
        child: pw.Row(
        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
        children: [
            pw.Text(label, style: pw.TextStyle(fontSize: 7, fontWeight: pw.FontWeight.bold)),
            _buildBadge(condition),
        ],
        ),
    );
  }

  static pw.Widget _buildSubRow(String text) {
      return pw.Padding(
        padding: const pw.EdgeInsets.only(left: 6, bottom: 1),
        child: pw.Text(text, style: const pw.TextStyle(fontSize: 6, color: PdfColors.grey700)),
      );
  }

  static pw.Widget _buildConditionRow(String label, dynamic value) {
    return pw.Padding(
      padding: const pw.EdgeInsets.only(bottom: 2),
      child: pw.Row(
        mainAxisAlignment: pw.MainAxisAlignment.spaceBetween,
        children: [
          pw.Text(label, style: const pw.TextStyle(fontSize: 7)),
          _buildBadge(value),
        ],
      ),
    );
  }

  static pw.Widget _buildBadge(dynamic value) {
      PdfColor color = PdfColors.black;
      String status = 'N/A';
      String valStr = value?.toString().toLowerCase() ?? 'na';
      
      if (valStr == 'good' || valStr == 'correct') {
        color = PdfColors.green700;
        status = 'OK';
      } else if (valStr == 'not_good' || valStr == 'incorrect' || valStr == 'bad') {
        color = PdfColors.red700;
        status = 'X';
      } else if (valStr == 'need_attention') {
        color = PdfColors.orange700;
        status = 'ATT';
      } else if (valStr != 'na' && valStr != 'null') {
         color = PdfColors.grey600;
         status = valStr.toUpperCase();
      }

      return pw.Container(
             padding: const pw.EdgeInsets.symmetric(horizontal: 4, vertical: 1),
             decoration: pw.BoxDecoration(
               color: color == PdfColors.black ? PdfColors.grey100 : color,
               borderRadius: const pw.BorderRadius.all(pw.Radius.circular(2))
             ),
             child: pw.Text(status, style: const pw.TextStyle(fontSize: 6, color: PdfColors.white, fontWeight: pw.FontWeight.bold))
          );
  }
  
  static pw.Widget _buildSignatureBox(String label, String name) {
    return pw.Column(
      crossAxisAlignment: pw.CrossAxisAlignment.start,
      children: [
        pw.Text(label, style: pw.TextStyle(fontSize: 8, fontWeight: pw.FontWeight.bold)),
        pw.SizedBox(height: 30),
        pw.Container(width: 120, height: 1, color: PdfColors.black),
        pw.SizedBox(height: 2),
        pw.Text(name, style: const pw.TextStyle(fontSize: 7)),
      ]
    );
  }

  static Future<File> _saveIncomingPdf(pw.Document pdf, String filename) async {
    try {
      final dir = await FileManagerService.getIncomingPdfDirectory();
      final file = File('${dir.path}/$filename');
      await file.writeAsBytes(await pdf.save());
      await OpenFile.open(file.path);
      return file;
    } catch (e) {
      throw Exception('Failed to save PDF: $e');
    }
  }

  static Future<File> _saveOutgoingPdf(pw.Document pdf, String filename) async {
    try {
      final dir = await FileManagerService.getOutgoingPdfDirectory();
      final file = File('${dir.path}/$filename');
      await file.writeAsBytes(await pdf.save());
      await OpenFile.open(file.path);
      return file;
    } catch (e) {
      throw Exception('Failed to save PDF: $e');
    }
  }
}
