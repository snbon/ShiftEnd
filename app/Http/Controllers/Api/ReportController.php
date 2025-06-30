<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $query = Report::with(['user', 'location', 'approver']);

        // Filter by user's access level
        if ($user->isOwner()) {
            // Owners see reports from all their locations
            $locationIds = Location::where('owner_id', $user->id)->pluck('id');
            $query->whereIn('location_id', $locationIds);
        } elseif ($user->isManager()) {
            // Managers see reports from their location
            $query->where('location_id', $user->location_id);
        } else {
            // Employees see only their own reports
            $query->where('user_id', $user->id);
        }

        $reports = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Users must be assigned to a location
        if (!$user->location_id) {
            return response()->json([
                'success' => false,
                'message' => 'You must be assigned to a location to create reports'
            ], 400);
        }

        $validated = $request->validate([
            'report_date' => 'required|date',
            'shift_start_time' => 'required|date_format:H:i',
            'shift_end_time' => 'required|date_format:H:i|after:shift_start_time',
            'cash_sales' => 'required|numeric|min:0',
            'card_sales' => 'required|numeric|min:0',
            'opening_cash' => 'required|numeric|min:0',
            'closing_cash' => 'required|numeric|min:0',
            'tips_cash' => 'required|numeric|min:0',
            'tips_card' => 'required|numeric|min:0',
            'inventory_notes' => 'nullable|string',
            'shift_notes' => 'nullable|string',
        ]);

        // Calculate totals
        $totalSales = $validated['cash_sales'] + $validated['card_sales'];
        $totalTips = $validated['tips_cash'] + $validated['tips_card'];
        $cashDifference = $validated['closing_cash'] - $validated['opening_cash'] - $validated['cash_sales'] - $validated['tips_cash'];

        $report = Report::create([
            ...$validated,
            'user_id' => $user->id,
            'location_id' => $user->location_id,
            'total_sales' => $totalSales,
            'total_tips' => $totalTips,
            'cash_difference' => $cashDifference,
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report created successfully',
            'data' => $report->load(['user', 'location'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $user = Auth::user();
        $report = Report::with(['user', 'location', 'approver'])->findOrFail($id);

        // Check access permissions
        if (!$this->canAccessReport($user, $report)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();
        $report = Report::findOrFail($id);

        // Only the report creator can update it, and only if it's still a draft
        if ($report->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only edit your own reports'
            ], 403);
        }

        if ($report->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot edit submitted or approved reports'
            ], 400);
        }

        $validated = $request->validate([
            'report_date' => 'required|date',
            'shift_start_time' => 'required|date_format:H:i',
            'shift_end_time' => 'required|date_format:H:i|after:shift_start_time',
            'cash_sales' => 'required|numeric|min:0',
            'card_sales' => 'required|numeric|min:0',
            'opening_cash' => 'required|numeric|min:0',
            'closing_cash' => 'required|numeric|min:0',
            'tips_cash' => 'required|numeric|min:0',
            'tips_card' => 'required|numeric|min:0',
            'inventory_notes' => 'nullable|string',
            'shift_notes' => 'nullable|string',
        ]);

        // Calculate totals
        $totalSales = $validated['cash_sales'] + $validated['card_sales'];
        $totalTips = $validated['tips_cash'] + $validated['tips_card'];
        $cashDifference = $validated['closing_cash'] - $validated['opening_cash'] - $validated['cash_sales'] - $validated['tips_cash'];

        $report->update([
            ...$validated,
            'total_sales' => $totalSales,
            'total_tips' => $totalTips,
            'cash_difference' => $cashDifference,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report updated successfully',
            'data' => $report->load(['user', 'location'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();
        $report = Report::findOrFail($id);

        // Only the report creator can delete it, and only if it's still a draft
        if ($report->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own reports'
            ], 403);
        }

        if ($report->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete submitted or approved reports'
            ], 400);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report deleted successfully'
        ]);
    }

    /**
     * Submit a report for approval.
     */
    public function submit(string $id): JsonResponse
    {
        $user = Auth::user();
        $report = Report::findOrFail($id);

        if ($report->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only submit your own reports'
            ], 403);
        }

        if ($report->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Report is already submitted or approved'
            ], 400);
        }

        $report->update(['status' => 'submitted']);

        return response()->json([
            'success' => true,
            'message' => 'Report submitted for approval',
            'data' => $report->load(['user', 'location'])
        ]);
    }

    /**
     * Approve or reject a report.
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();
        $report = Report::with('location')->findOrFail($id);

        // Only managers and owners can approve reports
        if (!$user->isManager() && !$user->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Only managers and owners can approve reports'
            ], 403);
        }

        // Check if user has access to this location
        if ($user->isOwner()) {
            if ($report->location->owner_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
        } else {
            if ($report->location_id !== $user->location_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }
        }

        if ($report->status !== 'submitted') {
            return response()->json([
                'success' => false,
                'message' => 'Report must be submitted before approval'
            ], 400);
        }

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'notes' => 'nullable|string',
        ]);

        $status = $validated['action'] === 'approve' ? 'approved' : 'rejected';

        $report->update([
            'status' => $status,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'shift_notes' => $report->shift_notes . "\n\nApproval Notes: " . ($validated['notes'] ?? ''),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Report {$status} successfully",
            'data' => $report->load(['user', 'location', 'approver'])
        ]);
    }

    /**
     * Check if user can access a report.
     */
    private function canAccessReport($user, $report): bool
    {
        if ($user->isOwner()) {
            return $report->location->owner_id === $user->id;
        } elseif ($user->isManager()) {
            return $report->location_id === $user->location_id;
        } else {
            return $report->user_id === $user->id;
        }
    }
}
