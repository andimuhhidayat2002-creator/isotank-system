<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->get();
        return view('admin.users.index', compact('users'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,inspector,maintenance,management,receiver',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return back()->with('success', 'User created successfully!');
    }

    /**
     * Update the specified user's role
     */
    public function updateRole(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent admin from changing their own role
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot change your own role!');
        }

        $validated = $request->validate([
            'role' => 'required|in:admin,inspector,maintenance,management,receiver',
        ]);

        $user->update(['role' => $validated['role']]);

        return back()->with('success', "User role updated to {$validated['role']}!");
    }

    /**
     * Update the specified user's information
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role' => 'required|in:admin,inspector,maintenance,management,receiver',
        ]);

        // Prevent admin from changing their own role
        if ($user->id === auth()->id() && $user->role !== $validated['role']) {
            return back()->with('error', 'You cannot change your own role!');
        }

        $user->update($validated);

        return back()->with('success', 'User updated successfully!');
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Password reset successfully!');
    }

    /**
     * Remove the specified user
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // Prevent admin from deleting themselves
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account!');
        }

        // Check if user has any related records (optional safety check)
        $hasInspections = $user->inspectionLogs()->exists();
        $hasMaintenanceJobs = $user->maintenanceJobs()->exists();

        if ($hasInspections || $hasMaintenanceJobs) {
            return back()->with('error', 'Cannot delete user with existing inspection or maintenance records!');
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully!');
    }
}
