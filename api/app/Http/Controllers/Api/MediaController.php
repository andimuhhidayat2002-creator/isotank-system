<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    /**
     * Download or view a private file.
     * This route is protected by Sanctum auth.
     */
    public function show(Request $request, $path)
    {
        // 1. Ensure path is not traversing directories
        if (str_contains($path, '..')) {
            abort(403, 'Invalid path');
        }

        // 2. Decode path if it was URL encoded
        // Path might come as "inspections/photo1.jpg"
        // But Laravel router might capture only first segment if not careful with regex or catch-all.
        // We will assume the route definition uses `where('path', '.*')` to capture full path.

        // 3. Check if file exists in 'local' (private) storage
        if (!Storage::disk('local')->exists($path)) {
            // Fallback: Check 'public' disk just in case (during migration phase)
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->response($path);
            }
            abort(404, 'File not found');
        }

        // 4. Return file stream
        return Storage::disk('local')->response($path);
    }
}
