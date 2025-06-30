<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Get pending users (owners only).
     */
    public function pending(): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $pendingUsers = User::where('status', 'pending')
                           ->orderBy('created_at', 'desc')
                           ->get(['id', 'name', 'email', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $pendingUsers
        ]);
    }

    /**
     * Update current user's role (for onboarding).
     */
    public function updateMyRole(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'role' => 'required|in:employee,manager,owner',
        ]);

        $user->update([
            'role' => $validated['role'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Update user role (owners only).
     */
    public function updateRole(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();
        $targetUser = User::findOrFail($id);

        if (!$user->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validated = $request->validate([
            'role' => 'required|in:employee,manager',
        ]);

        $targetUser->update([
            'role' => $validated['role'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User role updated successfully',
            'data' => $targetUser
        ]);
    }

    /**
     * Update current user's default location.
     */
    public function updateDefaultLocation(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'default_location_id' => 'required|exists:locations,id',
        ]);

        // Check if user has access to this location
        if ($user->isOwner()) {
            $location = Location::find($validated['default_location_id']);
            if ($location->owner_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
        } else {
            if ($validated['default_location_id'] != $user->location_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
        }

        $user->update([
            'default_location_id' => $validated['default_location_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Default location updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Update current user's location.
     */
    public function updateCurrentLocation(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
        ]);

        // Check if user has access to this location
        if ($user->isOwner()) {
            $location = Location::find($validated['location_id']);
            if ($location->owner_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
        } else {
            // For non-owners, check if they're assigned to this location
            $assignedLocation = Location::where('id', $validated['location_id'])
                ->whereHas('users', function($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->first();

            if (!$assignedLocation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
        }

        $user->update([
            'location_id' => $validated['location_id'],
            'default_location_id' => $validated['location_id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Current location updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Get team members for current user's location.
     */
    public function teamMembers(): JsonResponse
    {
        $user = Auth::user();

        if (!$user->location_id) {
            return response()->json([
                'success' => false,
                'message' => 'No location assigned'
            ], 400);
        }

        $teamMembers = User::where('location_id', $user->location_id)
                           ->orderBy('role', 'desc')
                           ->orderBy('name')
                           ->get(['id', 'name', 'email', 'role', 'status', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $teamMembers
        ]);
    }

    /**
     * Remove user from location (owners and managers only).
     */
    public function removeFromLocation(string $id): JsonResponse
    {
        $user = Auth::user();
        $targetUser = User::findOrFail($id);

        if ($user->isOwner()) {
            // Owners can remove anyone from their locations
            if (!$targetUser->location_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not assigned to any location'
                ], 400);
            }
        } elseif ($user->isManager()) {
            // Managers can only remove employees from their location
            if ($targetUser->location_id !== $user->location_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
            if ($targetUser->role !== 'employee') {
                return response()->json([
                    'success' => false,
                    'message' => 'Managers can only remove employees'
                ], 403);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $targetUser->update([
            'location_id' => null,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User removed from location successfully'
        ]);
    }
}
