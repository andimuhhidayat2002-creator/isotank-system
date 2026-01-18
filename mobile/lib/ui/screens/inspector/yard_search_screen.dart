import 'package:flutter/material.dart';
import '../../../data/services/api_service.dart';

class YardMapScreen extends StatefulWidget {
  const YardMapScreen({super.key});

  @override
  State<YardMapScreen> createState() => _YardMapScreenState();
}

class _YardMapScreenState extends State<YardMapScreen> {
  final ApiService _apiService = ApiService();
  final TransformationController _transformController = TransformationController();
  final TextEditingController _searchController = TextEditingController();

  List<dynamic> _slots = [];
  List<dynamic> _placed = [];
  List<dynamic> _unplaced = [];
  bool _isLoading = true;
  String _error = '';
  
  // Grid properties
  double _cellWidth = 60.0;
  double _cellHeight = 60.0;
  double _padding = 20.0;
  double _mapWidth = 1000.0;
  double _mapHeight = 1000.0;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  void _loadData() async {
    setState(() { _isLoading = true; _error = ''; });
    try {
      final results = await Future.wait([
        _apiService.getYardLayout(),
        _apiService.getYardPositions(),
      ]);
      
      final slots = results[0] as List<dynamic>;
      final positions = results[1] as Map<String, dynamic>;

      _processLayout(slots);
      
      setState(() {
        _slots = slots;
        _placed = positions['placed'] ?? [];
        _unplaced = positions['unplaced'] ?? [];
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  void _processLayout(List<dynamic> slots) {
    int maxCol = 0;
    int maxRow = 0;
    for (var slot in slots) {
      if (slot['col_index'] > maxCol) maxCol = slot['col_index'];
      if (slot['row_index'] > maxRow) maxRow = slot['row_index'];
    }
    _mapWidth = (maxCol * _cellWidth) + (_padding * 2);
    _mapHeight = (maxRow * _cellHeight) + (_padding * 2);
  }

  void _handleSearch(String query) {
    if (query.isEmpty) return;
    query = query.toUpperCase();

    // Find in Placed
    dynamic foundTank;
    dynamic foundSlot;

    // Check placed tanks
    for (var pos in _placed) {
      if (pos['isotank']['isotank_number'].toString().contains(query)) {
        foundTank = pos;
        // Find slot coordinates
        foundSlot = _slots.firstWhere((s) => s['id'] == pos['slot_id'], orElse: () => null);
        break;
      }
    }

    if (foundSlot != null) {
      _zoomToSlot(foundSlot['row_index'], foundSlot['col_index']);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Found ${foundTank['isotank']['isotank_number']} in Yard')),
      );
      return;
    }

    // Check Unplaced
    final foundUnplaced = _unplaced.firstWhere(
      (u) => u['isotank_number'].toString().contains(query),
      orElse: () => null
    );

    if (foundUnplaced != null) {
      _showUnplacedDialog();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Found ${foundUnplaced['isotank_number']} in Unplaced List')),
      );
    } else {
       ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Isotank not found')),
      );
    }
  }

  void _zoomToSlot(int row, int col) {
    // Calculate center of slot
    final x = (col - 1) * _cellWidth + _padding + (_cellWidth / 2);
    final y = (row - 1) * _cellHeight + _padding + (_cellHeight / 2);

    // Zoom level
    const zoom = 1.5;
    
    // Viewport dimensions (Approximation, or use LayoutBuilder)
    final viewportW = MediaQuery.of(context).size.width;
    final viewportH = MediaQuery.of(context).size.height - 150; // account for headers

    // Matrix calculation to center (x,y)
    // Translate(-x + vw/2, -y + vh/2) * Scale(z)
    // Actually: Scale then translate? 
    // Matrix4.identity()..translate(-x,-y)..scale(zoom) -> Focus on 0,0 relative
    
    final matrix = Matrix4.identity()
      ..translate(viewportW / 2, viewportH / 2) // Move 0,0 to center of screen
      ..scale(zoom)
      ..translate(-x, -y); // Move map so (x,y) is at 0,0

    _transformController.value = matrix;
  }

  void _showUnplacedDialog() {
    showModalBottomSheet(context: context, builder: (ctx) {
      return Container(
        padding: const EdgeInsets.all(16),
        height: 400,
        child: Column(
          children: [
            const Text('Unplaced Isotanks (SMGRS)', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18)),
            const Divider(),
            Expanded(
              child: ListView.builder(
                itemCount: _unplaced.length,
                itemBuilder: (context, index) {
                  final t = _unplaced[index];
                  return ListTile(
                    leading: const Icon(Icons.inventory_2, color: Colors.grey),
                    title: Text(t['isotank_number']),
                    subtitle: Text(t['current_cargo'] ?? '-'),
                  );
                },
              ),
            ),
          ],
        ),
      );
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Yard Layout Map'),
        actions: [
          IconButton(onPressed: _showUnplacedDialog, icon: const Icon(Icons.list)),
        ],
      ),
      body: Column(
        children: [
          // Search Bar
          Padding(
            padding: const EdgeInsets.all(8.0),
            child: TextField(
              controller: _searchController,
              decoration: const InputDecoration(
                hintText: 'Find Isotank...',
                prefixIcon: Icon(Icons.search),
                border: OutlineInputBorder(),
                isDense: true,
              ),
              onSubmitted: _handleSearch,
            ),
          ),
          Expanded(
            child: _isLoading 
              ? const Center(child: CircularProgressIndicator()) 
              : _error.isNotEmpty 
                  ? Center(child: Text('Error: $_error'))
                  : InteractiveViewer(
                      transformationController: _transformController,
                      constrained: false,
                      boundaryMargin: const EdgeInsets.all(500),
                      minScale: 0.1,
                      maxScale: 4.0,
                      child: CustomPaint(
                        size: Size(_mapWidth, _mapHeight),
                        painter: YardMapPainter(
                          slots: _slots,
                          placed: _placed,
                          cellWidth: _cellWidth,
                          cellHeight: _cellHeight,
                          padding: _padding,
                        ),
                      ),
                    ),
          ),
        ],
      ),
    );
  }
}

class YardMapPainter extends CustomPainter {
  final List<dynamic> slots;
  final List<dynamic> placed;
  final double cellWidth;
  final double cellHeight;
  final double padding;

  YardMapPainter({
    required this.slots,
    required this.placed,
    required this.cellWidth,
    required this.cellHeight,
    required this.padding,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final bgPaint = Paint()..color = Colors.white;
    canvas.drawRect(Rect.fromLTWH(0, 0, size.width, size.height), bgPaint);

    final borderPaint = Paint()
      ..color = Colors.grey.shade300
      ..style = PaintingStyle.stroke;
    
    final textStyle = const TextStyle(color: Colors.black, fontSize: 10);
    final smallTextStyle = const TextStyle(color: Colors.grey, fontSize: 8);

    // Draw Slots
    for (var slot in slots) {
      final double left = (slot['col_index'] - 1) * cellWidth + padding;
      final double top = (slot['row_index'] - 1) * cellHeight + padding;
      
      final rect = Rect.fromLTWH(left, top, cellWidth - 2, cellHeight - 2);
      
      // BG Color if any
      final paint = Paint()..color = Colors.grey.shade100;
      if (slot['bg_color'] != null) {
         // Parse basic colors or hex if passed. Web uses hex or name.
         // Simplified: specific known colors or default
         // If excel passes '#RRGGBB', parse it
         if (slot['bg_color'].toString().startsWith('#')) {
            paint.color = _hexToColor(slot['bg_color']);
         }
      }
      
      canvas.drawRect(rect, paint);
      canvas.drawRect(rect, borderPaint);
      
      // Slot Label (Area)
      // Check if occupied
      final placedTank = placed.firstWhere((p) => p['slot_id'] == slot['id'], orElse: () => null);

      if (placedTank != null) {
        // Draw Tank Card
        final tankRect = rect.deflate(2);
        final tankPaint = Paint()..color = Colors.blue.shade700;
        canvas.drawRRect(RRect.fromRectAndRadius(tankRect, const Radius.circular(4)), tankPaint);
        
        final iso = placedTank['isotank'];
        _drawText(canvas, iso['isotank_number'], tankRect.center.dx, tankRect.center.dy - 6, 
            textStyle.copyWith(color: Colors.white, fontWeight: FontWeight.bold));
        _drawText(canvas, iso['current_cargo'], tankRect.center.dx, tankRect.center.dy + 6, 
            smallTextStyle.copyWith(color: Colors.white70));
      } else {
        // Draw Empty Slot Label
        _drawText(canvas, slot['area_label'] ?? '', rect.center.dx, rect.center.dy, smallTextStyle);
      }
    }
  }

  void _drawText(Canvas canvas, String text, double x, double y, TextStyle style) {
    final span = TextSpan(text: text, style: style);
    final tp = TextPainter(text: span, textAlign: TextAlign.center, textDirection: TextDirection.ltr);
    tp.layout(maxWidth: cellWidth);
    tp.paint(canvas, Offset(x - tp.width / 2, y - tp.height / 2));
  }

  Color _hexToColor(String hex) {
    try {
      hex = hex.replaceAll('#', '');
      if (hex.length == 6) hex = 'FF$hex';
      return Color(int.parse('0x$hex'));
    } catch (e) {
      return Colors.grey.shade100;
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => true;
}
