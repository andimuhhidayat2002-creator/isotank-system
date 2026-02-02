<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class LogActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only log write operations (POST, PUT, PATCH, DELETE) and only for authenticated users
        if (Auth::check() && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            try {
                // Skip if it's a login/logout request
                if ($request->is('login') || $request->is('logout')) {
                    return $response;
                }

                // Filter out sensitive data from request
                $details = $request->except(['password', 'password_confirmation', '_token', 'signature_data']);

                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => $request->method() . ' ' . $request->path(),
                    'description' => 'User performed ' . $request->method() . ' on ' . $request->path(),
                    'details' => $details,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to log activity: ' . $e->getMessage());
            }
        }

        return $response;
    }
}
