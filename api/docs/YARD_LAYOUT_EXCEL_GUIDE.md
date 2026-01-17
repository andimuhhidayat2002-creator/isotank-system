# Yard Layout Excel Upload Guide

## Overview
The Yard Layout system now uses **Excel files** as the single source of truth for the visual yard layout. This means the yard view will be an exact 1:1 representation of your Excel file, including:

- ✅ **Merged cells** for labels and zones
- ✅ **Background colors** for visual grouping
- ✅ **Border styles** for cell boundaries
- ✅ **Text content** for labels (e.g., "FILLING STATION LNG", "PANCANG PAGAR LNG")
- ✅ **Font properties** (color, size, weight)

## How to Create Your Yard Layout Excel

### Step 1: Design Your Layout
1. Open Excel (or Google Sheets, then export as .xlsx)
2. Design your yard layout visually
3. Use colors, merged cells, and borders as you want them to appear

### Step 2: Mark Slot Positions
- Mark **every slot position** with the letter **"X"** (uppercase or lowercase)
- These "X" marks indicate where isotanks can be placed
- Example:
  ```
  | X | X | FILLING STATION LNG | X | X |
  ```

### Step 3: Add Labels and Zones
- Use **merged cells** for large labels (e.g., "PANCANG PAGAR LNG")
- Add **background colors** to differentiate zones
- Add **text** for zone names, area labels, etc.
- Example merged cell spanning 10 columns:
  ```
  |        PANCANG PAGAR LNG        |
  ```

### Step 4: Apply Visual Styling
- **Background colors**: Will be preserved exactly
- **Borders**: Will be preserved (solid, dashed, etc.)
- **Font colors**: Will be preserved
- **Font size**: Will be preserved
- **Bold text**: Will be preserved

## Example Layout Structure

```
┌─────────────────────────────────────────────┐
│       PANCANG PAGAR LNG (Green)             │
├───┬───┬───┬───┬───┬───┬───┬───┬───┬───┬───┤
│ X │ X │ X │ X │ X │ X │ X │ X │ X │ X │   │
├───┼───┼───┼───┼───┼───┼───┼───┼───┼───┼───┤
│ X │ X │   FILLING STATION LNG (Blue)  │ X │
├───┼───┼───────────────────────────┼───┼───┤
│ X │ X │ X │ X │ X │ X │ X │ X │ X │ X │   │
└───┴───┴───┴───┴───┴───┴───┴───┴───┴───┴───┘
```

## Upload Process

### For Yard Operators:
1. Go to **Yard Positioning** page
2. Click **"Upload Layout (Excel)"** button
3. Select your `.xlsx` or `.xls` file
4. Click **Upload**
5. The system will:
   - Read all cells from your Excel
   - Extract colors, borders, text, merged cells
   - Mark slots where you placed "X"
   - Render the yard exactly as designed

### Important Notes:
⚠️ **This will REPLACE the entire current layout**
⚠️ **All existing isotank positions will be preserved** (they won't be deleted)
⚠️ **Only cells marked with "X" are interactive slots**

## Technical Details

### What Gets Imported:
- `row_index` - Row number from Excel
- `col_index` - Column number from Excel
- `cell_value` - Original cell value
- `bg_color` - Background color (hex format)
- `border_style` - CSS border style
- `text_content` - Text to display (null for "X" cells)
- `font_color` - Text color (hex format)
- `font_size` - Font size in pixels
- `font_weight` - normal or bold
- `colspan` - Number of columns merged
- `rowspan` - Number of rows merged
- `is_slot` - true if cell contains "X"

### Rendering:
- All cells are positioned absolutely based on row/column index
- Merged cells span multiple grid positions
- Slots are overlaid on top with drag-drop functionality
- Colors and borders match your Excel exactly

## Troubleshooting

### "Upload failed" error:
- Check file format (.xlsx or .xls only)
- Ensure file is not corrupted
- Check server logs for details

### Slots not appearing:
- Ensure you marked them with "X" (not lowercase x in some cases)
- Check that cells are not empty

### Colors not showing:
- Ensure you applied background fill (not just font color)
- Avoid using "No Fill" for colored areas

### Merged cells not working:
- Ensure cells are properly merged in Excel
- Don't manually type across cells

## Best Practices

1. **Start Simple**: Create a small test layout first
2. **Use Colors Wisely**: Different colors for different zones
3. **Label Everything**: Use merged cells for clear labels
4. **Grid Alignment**: Keep your layout grid-aligned
5. **Test Upload**: Upload and verify before finalizing
6. **Backup**: Keep a copy of your Excel file

## Example Color Scheme

- 🟢 **Green** (#00FF00): Filling areas
- 🔵 **Blue** (#0000FF): Washing stations
- 🟡 **Yellow** (#FFFF00): Inspection zones
- ⚪ **Gray** (#CCCCCC): Buffer zones
- 🟠 **Orange** (#FFA500): Restricted areas

---

**Need Help?** Contact your system administrator.
