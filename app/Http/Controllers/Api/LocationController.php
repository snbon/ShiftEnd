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

        // Owners can see all their locations, others see only their assigned location
        if ($user->isOwner()) {
            $locations = Location::where('owner_id', $user->id)->get();
        } else {
            $locations = Location::where('id', $user->location_id)->get();
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
        // Only owners can create locations
        if (!Auth::user()->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Only owners can create locations'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
        ]);

        $location = Location::create([
            ...$validated,
            'owner_id' => Auth::id(),
        ]);

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
        if ($user->isOwner()) {
            if ($location->owner_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
        } else {
            if ($location->id !== $user->location_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
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
}
