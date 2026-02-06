<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class LoginSecurityMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * Security Features:
     * 1. Track login attempts
     * 2. Lock account after 5 failed attempts
     * 3. Rate limit by IP address
     * 4. Log all login attempts
     */
    public function handle(Request $request, Closure $next): Response
    {
        $email = $request->input('email');
        $ipAddress = $request->ip();
        
        // Check if account is locked
        if ($email) {
            $user = \App\Models\User::where('email', $email)->first();
            
            if ($user && $user->locked_until && now()->lt($user->locked_until)) {
                $minutesLeft = now()->diffInMinutes($user->locked_until);
                return response()->json([
                    'success' => false,
                    'message' => "Account is locked due to multiple failed login attempts. Please try again in {$minutesLeft} minutes."
                ], 423); // 423 Locked
            }
        }
        
        // Rate limit by IP (10 attempts per minute)
        $key = 'login-ip:' . $ipAddress;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Too many login attempts from this IP. Please try again in {$seconds} seconds."
            ], 429); // 429 Too Many Requests
        }
        
        RateLimiter::hit($key, 60); // 60 seconds decay
        
        $response = $next($request);
        
        // Log the attempt after response
        $this->logLoginAttempt($request, $response);
        
        return $response;
    }
    
    /**
     * Log login attempt to database
     */
    protected function logLoginAttempt(Request $request, Response $response): void
    {
        $email = $request->input('email');
        $successful = $response->getStatusCode() === 200;
        
        // Insert login attempt record
        DB::table('login_attempts')->insert([
            'email' => $email ?? 'unknown',
            'ip_address' => $request->ip(),
            'successful' => $successful,
            'user_agent' => $request->userAgent(),
            'attempted_at' => now(),
        ]);
        
        if (!$email) return;
        
        $user = \App\Models\User::where('email', $email)->first();
        if (!$user) return;
        
        if ($successful) {
            // Reset failed attempts on successful login
            $user->update([
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);
        } else {
            // Increment failed attempts
            $failedAttempts = $user->failed_login_attempts + 1;
            
            $updateData = [
                'failed_login_attempts' => $failedAttempts,
            ];
            
            // Lock account after 5 failed attempts for 30 minutes
            if ($failedAttempts >= 5) {
                $updateData['locked_until'] = now()->addMinutes(30);
                
                // TODO: Send email notification to user about account lock
                // \Mail::to($user->email)->send(new AccountLockedNotification($user));
            }
            
            $user->update($updateData);
        }
    }
}
