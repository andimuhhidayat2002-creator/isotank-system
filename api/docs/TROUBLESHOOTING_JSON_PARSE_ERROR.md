# Troubleshooting: JSON Parse Error pada Upload Excel

## Error yang Terjadi
```
System Error: JSON.parse: unexpected character at line 1 column 1 of the JSON data
```

## Penyebab
Error ini terjadi karena server mengembalikan response yang **bukan JSON** (kemungkinan HTML error page atau plain text).

## Solusi yang Sudah Diterapkan

### 1. ✅ Frontend Fix (JavaScript)
**File**: `resources/views/admin/yard/index.blade.php`

**Perubahan**:
- ✅ Added `Accept: application/json` header
- ✅ Check content-type before parsing JSON
- ✅ Better error messages
- ✅ Loading state on upload button
- ✅ Console logging for debugging

**Kode baru**:
```javascript
const res = await fetch("{{ route('yard.layout.upload') }}", {
    method: 'POST',
    headers: { 
        'X-CSRF-TOKEN': CSRF_TOKEN,
        'Accept': 'application/json'  // ← PENTING!
    },
    body: formData
});

// Check if response is JSON
const contentType = res.headers.get('content-type');
if (!contentType || !contentType.includes('application/json')) {
    throw new Error('Server returned non-JSON response. Check server logs.');
}
```

### 2. ✅ Backend Fix (Controller)
**File**: `app/Http/Controllers/Web/Admin/YardController.php`

**Perubahan**:
- ✅ Proper validation exception handling
- ✅ Enhanced error logging with stack trace
- ✅ Check for empty cells
- ✅ Always return JSON response

**Kode baru**:
```php
try {
    $request->validate([
        'excel_file' => 'required|file|mimes:xlsx,xls'
    ]);
} catch (ValidationException $e) {
    \Log::error('Upload validation error: ' . json_encode($e->errors()));
    return response()->json(['error' => 'Invalid file. Please upload .xlsx or .xls file.'], 422);
}

// ... processing ...

} catch (\Exception $e) {
    DB::rollBack();
    \Log::error('Upload layout error: ' . $e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    return response()->json(['error' => 'Upload failed: ' . $e->getMessage()], 500);
}
```

## Cara Testing Ulang

### Step 1: Clear Cache
```bash
cd c:\laragon\www\isotank-system\api
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Step 2: Test PhpSpreadsheet
```bash
php test_phpspreadsheet.php
```

**Expected output**:
```
✅ PhpSpreadsheet is working!
✅ Test file created: C:\laragon\www\isotank-system\api\storage\app\test_yard_layout.xlsx
✅ All tests passed!
```

### Step 3: Upload Test File
1. Open browser
2. Go to Yard Positioning page
3. Click "Upload Layout (Excel)"
4. Select file: `storage/app/test_yard_layout.xlsx`
5. Click Upload

**Expected result**:
- ✅ Success message: "Layout uploaded successfully! X cells imported."
- ✅ Page reloads
- ✅ Yard map shows the layout

### Step 4: Check Logs (if error occurs)
```bash
# View last 50 lines of log
Get-Content storage\logs\laravel.log -Tail 50

# Or follow logs in real-time
Get-Content storage\logs\laravel.log -Wait -Tail 20
```

## Common Issues & Solutions

### Issue 1: "Invalid file" error
**Cause**: File is not .xlsx or .xls

**Solution**: 
- Ensure file extension is correct
- Re-save Excel file as .xlsx
- Don't use .csv or .ods

### Issue 2: "No cells found" error
**Cause**: Excel file is empty

**Solution**:
- Add some content to Excel
- Mark at least one cell with "X"

### Issue 3: "Upload failed: Call to undefined method"
**Cause**: PhpSpreadsheet not installed properly

**Solution**:
```bash
composer require phpoffice/phpspreadsheet
composer dump-autoload
```

### Issue 4: Still getting JSON parse error
**Cause**: Server returning HTML error page

**Solution**:
1. Open browser DevTools (F12)
2. Go to Network tab
3. Try upload again
4. Click on the request
5. Check "Response" tab
6. If it's HTML, read the error message
7. Check Laravel logs for details

### Issue 5: 419 CSRF Token error
**Cause**: Session expired

**Solution**:
1. Refresh page (Ctrl + F5)
2. Try upload again
3. If still fails:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

### Issue 6: "Call to undefined method getMergeCells" (or getMergedCells)
**Cause**: Version mismatch in PhpSpreadsheet library. Some versions use `getMergeCells()`, others use `getMergedCells()`.

**Solution**:
We have implemented a **Robust Fix** in `YardController.php` that checks which method exists before calling it:

```php
if (method_exists($worksheet, 'getMergeCells')) {
    // ... use getMergeCells
} elseif (method_exists($worksheet, 'getMergedCells')) {
    // ... use getMergedCells
}
```

This ensures the upload works regardless of the installed library version.

## Debugging Checklist

When upload fails, check in this order:

1. ✅ **Browser Console** (F12 → Console tab)
2. ✅ **Network Tab** (F12 → Network tab)
   - Check Response JSON for specific error messages
3. ✅ **Laravel Logs** (`storage/logs/laravel.log`)
   - The logs now contain detailed step-by-step tracing of the upload process.


4. ✅ **Database** (if upload seems successful but data missing)
   ```sql
   SELECT COUNT(*) FROM yard_cells;
   SELECT * FROM yard_cells LIMIT 10;
   ```

5. ✅ **File Permissions** (if file can't be read)
   - Ensure uploaded file is readable
   - Check storage folder permissions

## Expected Flow

### Successful Upload:
```
1. User selects Excel file
2. JavaScript sends POST request with:
   - File in FormData
   - CSRF token in header
   - Accept: application/json in header
3. Laravel validates file (.xlsx or .xls)
4. PhpSpreadsheet reads Excel
5. Controller extracts all cell properties
6. Data inserted to yard_cells table
7. Transaction committed
8. JSON response: { "message": "...", "cells_count": 123 }
9. JavaScript shows success alert
10. Page reloads
11. Yard map renders from database
```

### Failed Upload (with proper error):
```
1. User selects invalid file
2. JavaScript sends POST request
3. Laravel validation fails
4. JSON response: { "error": "Invalid file..." }
5. JavaScript shows error alert
6. User can try again
```

## Test File Location

A test Excel file has been created at:
```
c:\laragon\www\isotank-system\api\storage\app\test_yard_layout.xlsx
```

This file contains:
- Merged cell A1:J1 with text "PANCANG PAGAR LNG" (green background, bold)
- Cells A2, B2, C2 marked with "X" (slots)

You can use this file to test the upload functionality.

## Next Steps

1. ✅ Clear all caches
2. ✅ Test PhpSpreadsheet script
3. ✅ Upload test file
4. ✅ Check browser console for errors
5. ✅ Check Laravel logs if needed
6. ✅ Create your actual yard layout Excel
7. ✅ Upload and verify

---

**If you still get JSON parse error after all fixes, please:**
1. Share the browser console error
2. Share the Network tab response
3. Share the Laravel log error
