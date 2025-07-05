<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Location;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserManagementController extends Controller
{
    /**
     * Get all users across all locations owned by the current user.
     */
    public function getAllUsers(): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        // Get all locations owned by the user
        $ownedLocations = Location::where('owner_id', $user->id)->pluck('id');

        // Get all users assigned to these locations
        $users = User::whereHas('locations', function($query) use ($ownedLocations) {
            $query->whereIn('location_id', $ownedLocations);
        })
        ->with(['locations' => function($query) use ($ownedLocations) {
            $query->whereIn('location_id', $ownedLocations)
                  ->withPivot('role', 'status');
        }])
        ->get()
        ->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'locations' => $user->locations->map(function($location) {
                    return [
                        'id' => $location->id,
                        'name' => $location->name,
                        'role' => $location->pivot->role,
                        'status' => $location->pivot->status,
                    ];
                }),
                'total_locations' => $user->locations->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Assign user to multiple locations.
     */
    public function assignToLocations(Request $request, string $userId): JsonResponse
    {
        $user = Auth::user();
        $targetUser = User::findOrFail($userId);

        if (!$user->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validated = $request->validate([
            'location_assignments' => 'required|array',
            'location_assignments.*.location_id' => 'required|exists:locations,id',
            'location_assignments.*.role' => 'required|in:manager,employee',
        ]);

        // Check if user owns all the locations
        $locationIds = collect($validated['location_assignments'])->pluck('location_id');
        $ownedLocations = Location::where('owner_id', $user->id)->pluck('id');

        if (!$locationIds->every(fn($id) => $ownedLocations->contains($id))) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to one or more locations'
            ], 403);
        }

        DB::transaction(function() use ($targetUser, $validated) {
            foreach ($validated['location_assignments'] as $assignment) {
                // Check if user is already assigned to this location
                $existingAssignment = $targetUser->locations()
                    ->where('location_id', $assignment['location_id'])
                    ->first();

                if ($existingAssignment) {
                    // Update existing assignment
                    $targetUser->locations()->updateExistingPivot($assignment['location_id'], [
                        'role' => $assignment['role'],
                        'status' => 'active',
                    ]);
                } else {
                    // Create new assignment
                    $targetUser->locations()->attach($assignment['location_id'], [
                        'role' => $assignment['role'],
                        'status' => 'active',
                    ]);
                }

                // Also set location_id if not set
                if ($targetUser->location_id !== $assignment['location_id']) {
                    $targetUser->location_id = $assignment['location_id'];
                    $targetUser->save();
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'User assigned to locations successfully'
        ]);
    }

    /**
     * Remove user from location with confirmation.
     */
    public function removeFromLocation(Request $request, string $userId): JsonResponse
    {
        $user = Auth::user();
        $targetUser = User::findOrFail($userId);

        if (!$user->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validated = $request->validate([
            'location_id' => 'required|exists:locations,id',
            'confirmation' => 'required|string|in:CONFIRM_REMOVE_USER',
        ]);

        // Check if user owns the location
        $location = Location::where('id', $validated['location_id'])
            ->where('owner_id', $user->id)
            ->first();

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        // Check if target user is assigned to this location
        if (!$targetUser->locations()->where('location_id', $validated['location_id'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'User is not assigned to this location'
            ], 400);
        }

        // Remove user from location
        $targetUser->locations()->detach($validated['location_id']);

        // If this was their only location, set location_id to null
        if ($targetUser->locations()->count() === 0) {
            $targetUser->update([
                'location_id' => null,
                'status' => 'pending',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User removed from location successfully'
        ]);
    }

    /**
     * Get permissions for a specific role in a location.
     */
    public function getRolePermissions(string $locationId, string $role): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        // Check if user owns the location
        $location = Location::where('id', $locationId)
            ->where('owner_id', $user->id)
            ->first();

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $permissions = Permission::getGroupedPermissions();
        $rolePermissions = RolePermission::where('location_id', $locationId)
            ->where('role', $role)
            ->with('permission')
            ->get()
            ->keyBy('permission_id');

        $result = [];
        foreach ($permissions as $category => $categoryPermissions) {
            $result[$category] = $categoryPermissions->map(function($permission) use ($rolePermissions) {
                $rolePermission = $rolePermissions->get($permission->id);
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => $permission->display_name,
                    'description' => $permission->description,
                    'granted' => $rolePermission ? $rolePermission->granted : false,
                ];
            });
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Update permissions for a role in a location.
     */
    public function updateRolePermissions(Request $request, string $locationId, string $role): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        // Check if user owns the location
        $location = Location::where('id', $locationId)
            ->where('owner_id', $user->id)
            ->first();

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*.permission_id' => 'required|exists:permissions,id',
            'permissions.*.granted' => 'required|boolean',
        ]);

        DB::transaction(function() use ($locationId, $role, $validated) {
            foreach ($validated['permissions'] as $permissionData) {
                RolePermission::updateOrCreate(
                    [
                        'location_id' => $locationId,
                        'role' => $role,
                        'permission_id' => $permissionData['permission_id'],
                    ],
                    [
                        'granted' => $permissionData['granted'],
                    ]
                );
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated successfully'
        ]);
    }

    /**
     * Get available locations for user assignment.
     */
    public function getAvailableLocations(): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $locations = Location::where('owner_id', $user->id)
            ->select('id', 'name', 'address')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $locations
        ]);
    }
}
