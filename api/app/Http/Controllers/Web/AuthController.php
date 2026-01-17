<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Allow Admin, Management, Inspector, and Yard Operator to login
            $allowedRoles = ['admin', 'management', 'inspector', 'yard_operator'];
            
            if (!in_array($user->role, $allowedRoles)) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Access denied. Unauthorized role.',
                ]);
            }

            // Redirect based on role
            if ($user->role === 'yard_operator') {
                return redirect()->route('yard.index');
            }

            return redirect()->route('admin.dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
