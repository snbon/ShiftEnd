<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserManagementController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// Authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/user', [AuthController::class, 'user']);
Route::get('/auth/check', [AuthController::class, 'check']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/resend-verification', [AuthController::class, 'resendVerification']);

// Public invitation endpoints
Route::get('/invitations/public/{inviteCode}', [InvitationController::class, 'publicShow']);
Route::post('/invitations/public/{inviteCode}/accept', [InvitationController::class, 'publicAccept']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Location routes
    Route::apiResource('locations', LocationController::class);
    Route::get('locations/{id}/team', [LocationController::class, 'team']);
    Route::put('locations/{location_id}/users/{user_id}/role', [LocationController::class, 'updateUserRole']);

    // Report routes
    Route::apiResource('reports', ReportController::class);
    Route::post('reports/{id}/submit', [ReportController::class, 'submit']);
    Route::post('reports/{id}/approve', [ReportController::class, 'approve']);

    // Invitation routes
    Route::apiResource('invitations', InvitationController::class);
    Route::post('invitations/{id}/resend', [InvitationController::class, 'resend']);
    Route::post('invitations/{inviteCode}/accept', [InvitationController::class, 'accept']);

    // User routes
    Route::get('users/pending', [UserController::class, 'pending']);
    Route::get('users/team-with-invitations', [UserController::class, 'teamWithInvitations']);
    Route::put('users/me/role', [UserController::class, 'updateMyRole']);
    Route::put('users/me/location', [UserController::class, 'updateCurrentLocation']);
    Route::put('users/{id}/role', [UserController::class, 'updateRole']);
    Route::delete('users/{id}/location', [UserController::class, 'removeFromLocation']);
    Route::get('users/team', [UserController::class, 'teamMembers']);

    // Enhanced User Management routes (owners only)
    Route::prefix('user-management')->group(function () {
        Route::get('users', [UserManagementController::class, 'getAllUsers']);
        Route::get('locations', [UserManagementController::class, 'getAvailableLocations']);
        Route::post('users/{userId}/assign-locations', [UserManagementController::class, 'assignToLocations']);
        Route::post('users/{userId}/remove-location', [UserManagementController::class, 'removeFromLocation']);
        Route::get('locations/{locationId}/roles/{role}/permissions', [UserManagementController::class, 'getRolePermissions']);
        Route::put('locations/{locationId}/roles/{role}/permissions', [UserManagementController::class, 'updateRolePermissions']);
    });
});
