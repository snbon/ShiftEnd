<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        // Owners can see all their locations, others see only their assigned locations
        if ($user->locations()->exists()) {
            $locations = $user->locations;
        } else {
            $locations = [];
        }

        return response()->json([
            'success' => true,
            'data' => $locations
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
        ]);

        $location = Location::create([
            ...$validated,
            'owner_id' => $user->id,
        ]);

        // Assign the user as owner in the pivot table
        $user->locations()->attach($location->id, [
            'role' => 'owner',
            'status' => 'active',
        ]);

        // Set default_location_id if not set or if this is their first location
        if (!$user->default_location_id) {
            $user->default_location_id = $location->id;
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Location created successfully',
            'data' => $location
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $user = Auth::user();
        $location = Location::findOrFail($id);

        // Check if user has access to this location
        $isOwner = $location->owner_id === $user->id;
        $isMember = $location->users()->where('user_id', $user->id)->exists();
        if (!$isOwner && !$isMember) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $location->load(['owner', 'users'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();
        $location = Location::findOrFail($id);

        // Only owners can update their locations
        if (!$user->isOwner() || $location->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'boolean',
        ]);

        $location->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => $location
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();
        $location = Location::findOrFail($id);

        // Only owners can delete their locations
        if (!$user->isOwner() || $location->owner_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        // Check if location has users or reports
        if ($location->users()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete location with assigned users'
            ], 400);
        }

        if ($location->reports()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete location with existing reports'
            ], 400);
        }

        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Location deleted successfully'
        ]);
    }

    /**
     * Get all team members for a location (with role/status).
     */
    public function team($id)
    {
        $location = Location::with(['users'])->findOrFail($id);
        // Only allow if user is owner or belongs to this location
        $user = auth()->user();
        $isOwner = $location->owner_id === $user->id;
        $isMember = $location->users->contains($user->id);
        if (!$isOwner && !$isMember) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        return response()->json([
            'success' => true,
            'team' => $location->users->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->pivot->role,
                    'status' => $user->pivot->status,
                ];
            }),
        ]);
    }

    /**
     * Remove a user from a location (owner only).
     */
    public function removeUser($locationId, $userId)
    {
        $location = Location::findOrFail($locationId);
        if (auth()->id() !== $location->owner_id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $location->users()->detach($userId);
        return response()->json(['success' => true]);
    }

    /**
     * Update a user's role in a location (owner only).
     */
    public function updateUserRole($locationId, $userId, Request $request)
    {
        $location = Location::findOrFail($locationId);
        if (auth()->id() !== $location->owner_id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $validated = $request->validate([
            'role' => 'required|in:manager,employee',
        ]);
        $location->users()->updateExistingPivot($userId, ['role' => $validated['role']]);
        return response()->json(['success' => true]);
    }
}
