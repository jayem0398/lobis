<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Sinubukan nating i-authenticate gamit ang web guard / session
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            \App\Models\ActivityLog::log('User Login', "User {$user->name} ({$user->role}) logged in.", $user);

            return response()->json([
                'status' => 'success',
                'message' => 'Logged in successfully',
                'user' => $user,
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'The provided credentials do not match our records.'
        ], 422);
    }

    public function logout(Request $request)
    {
        // Kung gumagamit ng Sanctum Tokens:
        // if ($request->user()) {
        //     $request->user()->currentAccessToken()->delete();
        // }

        // Kung session-based (SPA):
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ], 200);
    }

    public function user(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'user' => $request->user()
        ], 200);
    }
}