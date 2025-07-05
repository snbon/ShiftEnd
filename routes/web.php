<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

// Authentication routes
Route::post('/api/login', [AuthController::class, 'login']);
Route::post('/api/logout', [AuthController::class, 'logout']);
Route::get('/api/user', [AuthController::class, 'user']);
Route::get('/api/auth/check', [AuthController::class, 'check']);
Route::post('/api/register', [AuthController::class, 'register']);
Route::post('/api/resend-verification', [AuthController::class, 'resendVerification']);

// Location routes (protected by auth)
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('api/locations', LocationController::class);

    // Report routes
    Route::apiResource('api/reports', ReportController::class);
    Route::post('api/reports/{id}/submit', [ReportController::class, 'submit']);
    Route::post('api/reports/{id}/approve', [ReportController::class, 'approve']);

    // Invitation routes
    Route::apiResource('api/invitations', InvitationController::class);
    Route::post('api/invitations/{id}/resend', [InvitationController::class, 'resend']);
    Route::post('api/invitations/{inviteCode}/accept', [InvitationController::class, 'accept']);

    // User routes
    Route::get('api/users/pending', [UserController::class, 'pending']);
    Route::get('api/users/team-with-invitations', [UserController::class, 'teamWithInvitations']);
    Route::put('api/users/me/role', [UserController::class, 'updateMyRole']);
    Route::put('api/users/me/location', [UserController::class, 'updateCurrentLocation']);
    Route::put('api/users/{id}/role', [UserController::class, 'updateRole']);
    Route::delete('api/users/{id}/location', [UserController::class, 'removeFromLocation']);
    Route::get('api/users/team', [UserController::class, 'teamMembers']);

    // New team endpoint
    Route::get('api/locations/{id}/team', [LocationController::class, 'team']);

    // Update user role in location
    Route::put('api/locations/{location_id}/users/{user_id}/role', [App\Http\Controllers\Api\LocationController::class, 'updateUserRole']);

    // Enhanced User Management routes (owners only)
    Route::prefix('api/user-management')->group(function () {
        Route::get('users', [App\Http\Controllers\Api\UserManagementController::class, 'getAllUsers']);
        Route::get('locations', [App\Http\Controllers\Api\UserManagementController::class, 'getAvailableLocations']);
        Route::post('users/{userId}/assign-locations', [App\Http\Controllers\Api\UserManagementController::class, 'assignToLocations']);
        Route::post('users/{userId}/remove-location', [App\Http\Controllers\Api\UserManagementController::class, 'removeFromLocation']);
        Route::get('locations/{locationId}/roles/{role}/permissions', [App\Http\Controllers\Api\UserManagementController::class, 'getRolePermissions']);
        Route::put('locations/{locationId}/roles/{role}/permissions', [App\Http\Controllers\Api\UserManagementController::class, 'updateRolePermissions']);
    });
});

// Public invitation endpoints
Route::get('api/invitations/public/{inviteCode}', [App\Http\Controllers\Api\InvitationController::class, 'publicShow']);
Route::post('api/invitations/public/{inviteCode}/accept', [App\Http\Controllers\Api\InvitationController::class, 'publicAccept']);

// Email verification routes
Route::get('/email/verify/{id}/{hash}', function ($id, $hash) {
    try {
        // Find the user
        $user = \App\Models\User::find($id);

        if (!$user) {
            return redirect('/?verification_error=1&message=User not found');
        }

        // Check if already verified
        if ($user->hasVerifiedEmail()) {
            return redirect('/?verification_error=1&message=Email already verified');
        }

        // Verify the hash
        if (!hash_equals(
            sha1($user->getEmailForVerification()),
            $hash
        )) {
            return redirect('/?verification_error=1&message=Invalid verification link');
        }

        // Mark as verified
        $user->markEmailAsVerified();

        return redirect('/login?verified=1');
    } catch (\Exception $e) {
        return redirect('/?verification_error=1&message=Verification failed');
    }
})->middleware(['signed'])->name('verification.verify');

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// SPA route - this should be last to catch all other routes
Route::get('/{any?}', function () {
    return view('welcome');
})->where('any', '.*');
