<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // check if the user is active
        if (!Auth::user()->is_active) {
            return response()->json([
                'message' => 'Your account is inactive. Please contact the administrator.'
            ], 403);
        }

        $user = $request->user();
        $token = $user->createToken('auth_token')->plainTextToken;

        // Audit log for login
        \App\Models\AuditLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'model_type' => get_class($user),
            'model_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'old_values' => null,
            'new_values' => null,
        ]);

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user,
        ], 200);
    }

    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        // Audit log for logout
        \App\Models\AuditLog::create([
            'user_id' => $user->id,
            'action' => 'logout',
            'model_type' => get_class($user),
            'model_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'old_values' => null,
            'new_values' => null,
        ]);

        return response()->json([
            'message' => 'Logged out'
        ], 200);
    }
}
