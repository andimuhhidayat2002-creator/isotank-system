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

        // 3. Check if file exists in 'local' (private) storage
        if (!Storage::disk('local')->exists($path)) {
            // Fallback: Check 'public' disk just in case (during migration phase)
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->response($path);
            }
            abort(404, 'File not found');
        }

        // LOGGING: Track who views this media
        try {
            \App\Models\ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'View Media',
                'description' => 'User viewed private file: ' . $path,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'details' => ['path' => $path]
            ]);
        } catch (\Exception $e) {
            // Don't block media access if logging fails
            \Log::error('Failed to log media access: ' . $e->getMessage());
        }

        // 4. Return file stream
        return Storage::disk('local')->response($path);
    }
}
