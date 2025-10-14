<?php

namespace App\Http\Controllers;

use App\Models\TechTransfer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class TechTransferController extends Controller
{
    /**
     * Display a listing of tech transfers.
     * GET /api/tech-transfers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = TechTransfer::with(['user', 'college', 'college.campus'])->where('is_archived', false);

            // Filter by user if provided
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->has('campus')) {
                // tech_transfers table does not have a campus_id column.
                // Filter by campus through the related college instead.
                $campusId = $request->campus;
                $query->whereHas('college', function ($q) use ($campusId) {
                    $q->where('campus_id', $campusId);
                });
            }

            // Filter by college if provided
            if ($request->has('college_id')) {
                $query->where('college_id', $request->college_id);
            }

            // Filter by category if provided
            if ($request->has('category')) {
                $query->where('category', $request->category);
            }

            // Filter by purpose if provided
            if ($request->has('purpose')) {
                $query->where('purpose', $request->purpose);
            }

            // Filter by copyright status if provided
            if ($request->has('copyright')) {
                $query->where('copyright', $request->copyright);
            }

            // Filter by archived status if provided
            if ($request->has('is_archived')) {
                $query->where('is_archived', $request->boolean('is_archived'));
            }

            // Search by name, description, leader, or agency partner
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('leader', 'like', "%{$search}%")
                        ->orWhere('agency_partner', 'like', "%{$search}%");
                });
            }

            // Filter by date range
            if ($request->has('start_date_from')) {
                $query->where('start_date', '>=', $request->start_date_from);
            }

            if ($request->has('start_date_to')) {
                $query->where('start_date', '<=', $request->start_date_to);
            }

            // Filter by assessment based
            if ($request->has('is_assessment_based')) {
                $query->where('is_assessment_based', $request->boolean('is_assessment_based'));
            }

            $techTransfers = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $techTransfers,
                'message' => 'Tech transfers retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tech transfers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created tech transfer.
     * POST /api/tech-transfers
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'category' => 'required|string|max:255',
                'purpose' => 'required|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'tags' => 'required|string|max:255',
                'leader' => 'required|string|max:255',
                'deliverables' => 'nullable|string|max:255',
                'agency_partner' => 'required|string|max:255',
                'contact_person' => 'required|string|max:255',
                'contact_email' => 'required|email|max:255',
                'contact_phone' => 'required|string|max:255',
                'contact_address' => 'required|string|max:255',
                'copyright' => 'required|string|in:yes,no,pending',
                'ip_details' => 'nullable|string',
                'is_assessment_based' => 'required|boolean',
                'monitoring_evaluation_plan' => 'nullable|string',
                'sustainability_plan' => 'nullable|string',
                'reporting_frequency' => 'required|integer|min:1',
                'attachments.*' => 'file|mimes:jpeg,jpg,png,pdf,doc,docx|max:10240',
                'attachment_link' => 'nullable|url|max:255',
                'user_id' => 'required|exists:users,id',
                'college_id' => 'required|exists:colleges,id',
                'is_archived' => 'nullable|boolean',
            ]);

            $techTransfer = TechTransfer::create($validatedData);
            $techTransfer->load(['user', 'college']);

            return response()->json([
                'success' => true,
                'data' => $techTransfer,
                'message' => 'Tech transfer created successfully'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create tech transfer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified tech transfer.
     * GET /api/tech-transfers/{techTransfer}
     */
    public function show(Request $request, TechTransfer $techTransfer): JsonResponse
    {
        try {
            $techTransfer->load(['user', 'college', 'college.campus', 'impactAssessments', 'modalities']);

            return response()->json([
                'success' => true,
                'data' => $techTransfer,
                'message' => 'Tech transfer retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tech transfer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified tech transfer.
     * PUT/PATCH /api/tech-transfers/{techTransfer}
     */
    public function update(Request $request, TechTransfer $techTransfer): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'category' => 'sometimes|required|string|max:255',
                'purpose' => 'sometimes|required|string|max:255',
                'start_date' => 'sometimes|required|date',
                'end_date' => 'sometimes|required|date|after:start_date',
                'tags' => 'sometimes|required|string|max:255',
                'leader' => 'sometimes|required|string|max:255',
                'deliverables' => 'nullable|string|max:255',
                'agency_partner' => 'sometimes|required|string|max:255',
                'contact_person' => 'sometimes|required|string|max:255',
                'contact_email' => 'sometimes|required|email|max:255',
                'contact_phone' => 'sometimes|required|string|max:255',
                'contact_address' => 'sometimes|required|string|max:255',
                'copyright' => 'sometimes|required|string|in:yes,no,pending',
                'ip_details' => 'nullable|string',
                'is_assessment_based' => 'sometimes|required|boolean',
                'monitoring_evaluation_plan' => 'nullable|string',
                'sustainability_plan' => 'nullable|string',
                'reporting_frequency' => 'sometimes|required|integer|min:1',
                'attachments.*' => 'file|mimes:jpeg,jpg,png,pdf,doc,docx|max:10240',
                'attachment_link' => 'nullable|url|max:255',
                'is_archived' => 'nullable|boolean',
            ]);

            // Handle multiple file uploads for update
            if ($request->hasFile('attachments')) {
                // Delete old attachments if they exist
                if ($techTransfer->attachment_paths) {
                    foreach ($techTransfer->attachment_paths as $oldPath) {
                        if (Storage::disk('spaces')->exists($oldPath)) {
                            Storage::disk('spaces')->delete($oldPath);
                        }
                    }
                }

                // Upload new attachments
                $attachmentPaths = [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('project-attachments', 'spaces');
                    $attachmentPaths[] = $path;
                }
                $techTransfer->attachment_paths = $attachmentPaths;
            }

            $techTransfer->update($validatedData);
            $techTransfer->load(['user', 'college']);

            return response()->json([
                'success' => true,
                'data' => $techTransfer,
                'message' => 'Tech transfer updated successfully'
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update tech transfer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified tech transfer.
     * DELETE /api/tech-transfers/{techTransfer}
     */
    public function destroy(TechTransfer $techTransfer): JsonResponse
    {
        try {
            $techTransfer->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tech transfer deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tech transfer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function archive(TechTransfer $techTransfer, Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user is admin
        if ($user->role !== 'admin' || $user->id !== $techTransfer->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action'
            ], 403);
        }

        // Validate password presence
        if (! $request->filled('password')) {
            return response()->json([
                'success' => false,
                'message' => 'Password is required'
            ], 422);
        }

        // Check provided password against authenticated user's password
        if (! Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password does not match'
            ], 403);
        }

        try {
            $techTransfer->is_archived = true;
            $techTransfer->save();

            return response()->json([
                'success' => true,
                'data' => $techTransfer,
                'message' => 'Tech transfer archived successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to archive tech transfer',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tech transfer statistics.
     * GET /api/tech-transfers/statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $stats = [
                'total_tech_transfers' => TechTransfer::count(),
                'active_tech_transfers' => TechTransfer::where('is_archived', false)->count(),
                'archived_tech_transfers' => TechTransfer::where('is_archived', true)->count(),
                'assessment_based_transfers' => TechTransfer::where('is_assessment_based', true)->count(),
                'transfers_by_category' => TechTransfer::selectRaw('category, COUNT(*) as count')
                    ->groupBy('category')
                    ->get(),
                'transfers_by_purpose' => TechTransfer::selectRaw('purpose, COUNT(*) as count')
                    ->groupBy('purpose')
                    ->orderBy('count', 'desc')
                    ->get(),
                'transfers_by_copyright' => TechTransfer::selectRaw('copyright, COUNT(*) as count')
                    ->groupBy('copyright')
                    ->get(),
                'transfers_this_year' => TechTransfer::whereYear('start_date', now()->year)->count(),
                'average_reporting_frequency' => TechTransfer::avg('reporting_frequency'),
                'transfers_by_college' => TechTransfer::selectRaw('college_id, COUNT(*) as count')
                    ->with('college:id,name')
                    ->groupBy('college_id')
                    ->orderBy('count', 'desc')
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Tech transfer statistics retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tech transfer statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
