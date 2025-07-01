<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvitationController extends Controller
{
    /**
     * Display a listing of invitations for a location.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $locationId = $request->query('location_id');

        if (!$locationId) {
            return response()->json([
                'success' => false,
                'message' => 'Location ID is required'
            ], 400);
        }

        // Check if user has access to this location
        if ($user->isOwner()) {
            $location = Location::where('id', $locationId)
                               ->where('owner_id', $user->id)
                               ->first();
        } else {
            $location = Location::where('id', $locationId)
                               ->where('id', $user->location_id)
                               ->first();
        }

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $invitations = Invitation::with(['inviter', 'acceptor'])
                                ->where('location_id', $locationId)
                                ->orderBy('created_at', 'desc')
                                ->get();

        return response()->json([
            'success' => true,
            'data' => $invitations
        ]);
    }

    /**
     * Store a newly created invitation.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        $locationId = $request->input('location_id');

        // Check if user can invite to this location
        if ($user->isOwner()) {
            $location = Location::where('id', $locationId)
                               ->where('owner_id', $user->id)
                               ->first();
        } elseif ($user->isManager()) {
            $location = Location::where('id', $locationId)
                               ->where('id', $user->location_id)
                               ->first();
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Only owners and managers can send invitations'
            ], 403);
        }

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:employee,manager',
            'location_id' => 'required|exists:locations,id',
        ]);

        // Check if user already exists and is assigned to this location
        $existingUser = User::where('email', $validated['email'])->first();
        if ($existingUser && $existingUser->location_id === $locationId) {
            return response()->json([
                'success' => false,
                'message' => 'User is already assigned to this location'
            ], 400);
        }

        // Check if there's already a pending invitation for this email and location
        $existingInvitation = Invitation::where('email', $validated['email'])
                                       ->where('location_id', $locationId)
                                       ->where('status', 'pending')
                                       ->first();

        if ($existingInvitation) {
            return response()->json([
                'success' => false,
                'message' => 'An invitation has already been sent to this email'
            ], 400);
        }

        // Create invitation
        $invitation = Invitation::create([
            'location_id' => $locationId,
            'invited_by' => $user->id,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'invite_code' => Invitation::generateInviteCode(),
            'expires_at' => now()->addDays(7),
            'status' => 'pending',
        ]);

        // Send invitation email
        \Mail::to($invitation->email)->send(new \App\Mail\InvitationMail($invitation, $location, $user));

        return response()->json([
            'success' => true,
            'message' => 'Invitation sent successfully',
            'data' => $invitation->load(['location', 'inviter'])
        ], 201);
    }

    /**
     * Display the specified invitation.
     */
    public function show(string $id): JsonResponse
    {
        $user = Auth::user();
        $invitation = Invitation::with(['location', 'inviter', 'acceptor'])->findOrFail($id);

        // Check if user has access to this invitation
        if ($user->isOwner()) {
            if ($invitation->location->owner_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
        } elseif ($user->isManager()) {
            if ($invitation->location_id !== $user->location_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $invitation
        ]);
    }

    /**
     * Accept an invitation.
     */
    public function accept(Request $request, string $inviteCode): JsonResponse
    {
        $user = Auth::user();
        $invitation = Invitation::with('location')->where('invite_code', $inviteCode)->first();

        if (!$invitation) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid invitation code'
            ], 404);
        }

        if (!$invitation->canBeAccepted()) {
            return response()->json([
                'success' => false,
                'message' => 'Invitation has expired or already been accepted'
            ], 400);
        }

        // Check if user email matches invitation email
        if ($invitation->email !== $user->email) {
            return response()->json([
                'success' => false,
                'message' => 'This invitation was sent to a different email address'
            ], 400);
        }

        // Check if user is already assigned to a location
        if ($user->location_id) {
            return response()->json([
                'success' => false,
                'message' => 'You are already assigned to a location'
            ], 400);
        }

        DB::transaction(function () use ($invitation, $user) {
            // Update invitation
            $invitation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
                'accepted_by' => $user->id,
            ]);

            // Update user
            $user->update([
                'location_id' => $invitation->location_id,
                'role' => $invitation->role,
                'status' => 'active',
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Invitation accepted successfully',
            'data' => $invitation->load(['location', 'inviter'])
        ]);
    }

    /**
     * Cancel/delete an invitation.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();
        $invitation = Invitation::with('location')->findOrFail($id);

        // Check if user can cancel this invitation
        if ($user->isOwner()) {
            if ($invitation->location->owner_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
        } elseif ($user->isManager()) {
            if ($invitation->location_id !== $user->location_id || $invitation->invited_by !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        if ($invitation->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot cancel accepted or expired invitations'
            ], 400);
        }

        $invitation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invitation cancelled successfully'
        ]);
    }

    /**
     * Resend an invitation.
     */
    public function resend(string $id): JsonResponse
    {
        $user = Auth::user();
        $invitation = Invitation::with('location')->findOrFail($id);

        // Check if user can resend this invitation
        if ($user->isOwner()) {
            if ($invitation->location->owner_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
        } elseif ($user->isManager()) {
            if ($invitation->location_id !== $user->location_id || $invitation->invited_by !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        if ($invitation->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot resend accepted or expired invitations'
            ], 400);
        }

        // Update invitation with new expiry and code
        $invitation->update([
            'invite_code' => Invitation::generateInviteCode(),
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invitation resent successfully',
            'data' => $invitation->load(['location', 'inviter'])
        ]);
    }
}
