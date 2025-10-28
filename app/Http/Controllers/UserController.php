<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\College;
use App\Models\User;
use App\Mail\UserCreated;
use App\Notifications\WelcomeNewUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with(['college', 'college.campus'])
            ->orderBy('created_at', 'desc');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->filled('role')) {
            if ($request->role === 'admin') {
                $query->where('role', 'admin');
            } elseif ($request->role === 'user') {
                $query->where('role', 'user');
            }
        }

        // Filter by campus
        if ($request->filled('campus_id')) {
            $query->whereHas('college.campus', function ($q) use ($request) {
                $q->where('id', $request->campus_id);
            });
        }

        // Filter by college
        if ($request->filled('college_id')) {
            $query->where('college_id', $request->college_id);
        }

        // Filter by campus college (keep for backward compatibility)
        if ($request->filled('campus_college_id')) {
            $query->where('campus_college_id', $request->campus_college_id);
        }

        $users = $query->paginate(10)->withQueryString();

        $campuses = Campus::orderBy('name')->get();
        $colleges = College::orderBy('name')->get();

        // Calculate stats for all users (not just paginated results)
        $totalUsers = User::count();
        $adminUsers = User::where('role', 'admin')->count();
        $regularUsers = User::where('role', 'user')->count();
        $activeUsers = User::where('is_active', true)->count();
        $inactiveUsers = User::where('is_active', false)->count();

        return response()->json([
            'users' => $users,
            'campuses' => $campuses,
            'colleges' => $colleges,
            'stats' => [
                'total' => $totalUsers,
                'admin' => $adminUsers,
                'user' => $regularUsers,
                'active' => $activeUsers,
                'inactive' => $inactiveUsers,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:admin,user',
            'college_id' => 'required|exists:colleges,id',
            'is_active' => 'boolean',
        ]);

        // Generate a random temporary password
        $temporaryPassword = Str::random(12);

        // Create the user
        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'password' => Hash::make($temporaryPassword),
            'role' => $validated['role'],
            'college_id' => $validated['college_id'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Load relationships
        $user->load(['college', 'college.campus']);

        // Send email with temporary password
        $user->notify(new WelcomeNewUser($temporaryPassword));

        return response()->json([
            'message' => 'User created successfully. A temporary password has been sent to their email.',
            'user' => $user,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::with(['college.campus', 'techTransfers'])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'college' => $user->college ? [
                    'id' => $user->college->id,
                    'name' => $user->college->name,
                    'logo' => $user->college->logo,
                    'campus' => $user->college->campus ? [
                        'id' => $user->college->campus->id,
                        'name' => $user->college->campus->name,
                    ] : null,
                ] : null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'email_verified_at' => $user->email_verified_at,
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'role' => 'sometimes|in:admin,user',
            'college_id' => 'sometimes|exists:colleges,id',
            'is_active' => 'sometimes|boolean',
            'password' => 'sometimes|nullable|string|min:8|confirmed',
        ]);

        // Remove password from validated if it's null or empty
        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        // Remove password_confirmation as it's not a database field
        unset($validated['password_confirmation']);

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'college' => $user->college ? [
                    'id' => $user->college->id,
                    'name' => $user->college->name,
                    'campus' => $user->college->campus ? [
                        'id' => $user->college->campus->id,
                        'name' => $user->college->campus->name,
                    ] : null,
                ] : null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'email_verified_at' => $user->email_verified_at,
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        // Prevent deleting yourself
        if (auth()->id() === $user->id) {
            return response()->json([
                'message' => 'You cannot delete your own account',
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    /**
     * Toggle admin privileges for a user.
     */
    public function toggleAdmin(string $id)
    {
        $user = User::findOrFail($id);

        // Prevent modifying yourself
        if (auth()->id() === $user->id) {
            return response()->json([
                'message' => 'You cannot modify your own admin privileges',
            ], 403);
        }

        // Toggle role
        $user->role = $user->role === 'admin' ? 'user' : 'admin';
        $user->save();

        return response()->json([
            'message' => 'Admin privileges updated successfully',
            'user' => $user->load(['college', 'college.campus']),
        ]);
    }

    /**
     * Bulk activate users.
     */
    public function bulkActivate(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $updated = User::whereIn('id', $validated['user_ids'])
            ->update(['is_active' => true]);

        return response()->json([
            'message' => "{$updated} user(s) activated successfully",
            'count' => $updated,
        ]);
    }

    /**
     * Bulk deactivate users.
     */
    public function bulkDeactivate(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        // Prevent deactivating yourself
        if (in_array(auth()->id(), $validated['user_ids'])) {
            return response()->json([
                'message' => 'You cannot deactivate your own account',
            ], 403);
        }

        $updated = User::whereIn('id', $validated['user_ids'])
            ->update(['is_active' => false]);

        return response()->json([
            'message' => "{$updated} user(s) deactivated successfully",
            'count' => $updated,
        ]);
    }
}
