<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Services\YardGenerator;
use Illuminate\Http\Request;
use App\Models\IsotankPosition;

class YardGeneratorController extends Controller
{
    protected $generator;

    public function __construct(YardGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function index()
    {
        // No SVG generation anymore. Just view the tool.
        return view('admin.yard.generator');
    }

    public function downloadCsv()
    {
        $csv = $this->generator->generateCsv();
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="yard_layout_generated.csv"');
    }

    public function importExcel(Request $request)
    {
        $request->validate(['file' => 'required|file']);
        
        $path = $request->file('file')->getRealPath();
        
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            
            $highestRow = $sheet->getHighestRow();
            $highestCol = $sheet->getHighestColumn();
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
            
            $cells = [];

            // Scan ALL cells
            for ($row = 1; $row <= $highestRow; $row++) {
                for ($col = 1; $col <= $highestColIndex; $col++) {
                    $colString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                    $cellCoord = "{$colString}{$row}";
                    $cell = $sheet->getCell($cellCoord);
                    
                    $cellValue = trim((string)$cell->getValue());
                    $style = $cell->getStyle();
                    
                    // Extract background color
                    $bgColor = $style->getFill()->getStartColor()->getRGB();
                    if ($bgColor === '000000' || $bgColor === 'FFFFFF') {
                        $bgColor = null; // Default/no color
                    } else {
                        $bgColor = '#' . $bgColor;
                    }
                    
                    // Extract border (simplified - just check if has border)
                    $borderStyle = null;
                    $borders = $style->getBorders();
                    if ($borders->getTop()->getBorderStyle() !== \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE) {
                        $borderColor = $borders->getTop()->getColor()->getRGB();
                        $borderStyle = "1px solid #" . $borderColor;
                    }
                    
                    // Extract font
                    $font = $style->getFont();
                    $fontColor = '#' . $font->getColor()->getRGB();
                    $fontSize = $font->getSize();
                    $fontWeight = $font->getBold() ? 'bold' : 'normal';
                    
                    // Check if merged
                    $colspan = 1;
                    $rowspan = 1;
                    foreach ($sheet->getMergeCells() as $mergeRange) {
                        if ($sheet->getCell($cellCoord)->isInRange($mergeRange)) {
                            // This cell is part of a merge
                            list($startCell, $endCell) = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::splitRange($mergeRange)[0];
                            $startCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($startCell);
                            $endCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($endCell);
                            
                            $colspan = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($endCoord[0]) - 
                                       \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startCoord[0]) + 1;
                            $rowspan = $endCoord[1] - $startCoord[1] + 1;
                            break;
                        }
                    }
                    
                    // Determine if slot (strict uppercase X)
                    $isSlot = ($cellValue === 'X');
                    
                    $cells[] = [
                        'row_index' => $row,
                        'col_index' => $col,
                        'cell_value' => $cellValue,
                        'bg_color' => $bgColor,
                        'border_style' => $borderStyle,
                        'text_content' => $cellValue,
                        'font_color' => $fontColor,
                        'font_size' => $fontSize,
                        'font_weight' => $fontWeight,
                        'colspan' => $colspan,
                        'rowspan' => $rowspan,
                        'is_slot' => $isSlot,
                    ];
                }
            }
            
            if (empty($cells)) {
                return back()->with('error', 'No cells found in Excel file.');
            }
            
            // Save to database
            \DB::table('yard_cells')->truncate();
            \DB::table('yard_cells')->insert($cells);
            
            $slotCount = collect($cells)->where('is_slot', true)->count();
            
            return back()->with('success', "Excel imported successfully. {$slotCount} slots identified from " . count($cells) . " total cells.");
            
        } catch (\Exception $e) {
            return back()->with('error', 'Import Failed: ' . $e->getMessage());
        }
    }
}
