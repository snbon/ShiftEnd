<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Handle user login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();
            if (is_null($user->email_verified_at)) {
                Auth::logout();
                return response()->json([
                    'message' => 'Please verify your email before logging in.'
                ], 403);
            }
            return response()->json([
                'user' => $user,
                'message' => 'Login successful'
            ]);
        }

        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logout successful'
        ]);
    }

    /**
     * Get current user info
     */
    public function user(Request $request)
    {
        $user = Auth::user()->load(['locations' => function($q) {
            $q->withPivot('role', 'status');
        }]);
        return response()->json([
            'user' => $user
        ]);
    }

    /**
     * Check if user is authenticated
     */
    public function check(Request $request)
    {
        return response()->json([
            'authenticated' => Auth::check()
        ]);
    }

    /**
     * Handle user registration
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'status' => 'active', // All users are active by default
        ]);

        // Send email verification
        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Registration successful! Please check your email to verify your account.',
            'user_id' => $user->id
        ], 201);
    }

    /**
     * Resend email verification link
     */
    public function resendVerification(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }
        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email already verified.'], 400);
        }
        $user->sendEmailVerificationNotification();
        return response()->json(['message' => 'Verification email resent. Please check your inbox.']);
    }
}
