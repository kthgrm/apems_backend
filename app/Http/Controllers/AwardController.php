<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Traits\Reviewable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AwardController extends Controller
{
    use Reviewable;
    /**
     * Display a listing of the resource.
     * GET /api/awards-recognition
     */
    public function index(Request $request)
    {
        try {
            $query = Award::with(['user', 'college', 'college.campus'])->where('is_archived', false)->where('status', 'approved');

            $user = $request->user();

            if ($user->role !== 'admin') {
                // Non-admins: only their own submissions
                $query->where('user_id', $user->id);
            }

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

            $partners = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $partners,
                'message' => 'International partners retrieved successfully'
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
     * Store a newly created resource in storage.
     * POST /api/awards
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'award_name' => 'required|string|max:255',
                'description' => 'required|string',
                'date_received' => 'required|date',
                'event_details' => 'required|string',
                'location' => 'required|string|max:255',
                'awarding_body' => 'required|string|max:255',
                'people_involved' => 'required|string|max:255',
                'attachments.*' => 'file|mimes:jpeg,jpg,png,pdf,doc,docx|max:10240',
                'attachment_link' => 'nullable|url|max:255',
            ]);

            $user = $request->user();
            $validatedData['user_id'] = $user->id;
            $validatedData['college_id'] = $user->college_id;

            // Handle multiple file uploads
            if ($request->hasFile('attachments')) {
                $attachmentPaths = [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('award-attachments', 'spaces');
                    $attachmentPaths[] = $path;
                }
                $validatedData['attachment_paths'] = $attachmentPaths;
            }

            $award = Award::create($validatedData);
            $award->load(['user', 'college', 'college.campus']);

            return response()->json([
                'success' => true,
                'data' => $award,
                'message' => 'Award created successfully'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create award',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/awards-recognition/{award}
     */
    public function show(Award $award, Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin' && $user->id !== $award->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // if ($award->status === 'rejected') {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Award not found',
        //     ], 404);
        // }

        //check if archived
        if ($award->is_archived) {
            return response()->json([
                'success' => false,
                'message' => 'Award not found',
            ], 404);
        }
        try {
            $award->load(['user', 'college', 'college.campus']);


            return response()->json([
                'success' => true,
                'data' => $award,
                'message' => 'Award retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve award',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Award $award)
    {
        $validatedData = $request->validate([
            'award_name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'date_received' => 'sometimes|required|date',
            'event_details' => 'sometimes|required|string',
            'location' => 'sometimes|required|string|max:255',
            'awarding_body' => 'sometimes|required|string|max:255',
            'people_involved' => 'sometimes|required|string|max:255',
            'attachments.*' => 'file|mimes:jpeg,jpg,png,pdf,doc,docx|max:10240',
            'attachment_link' => 'nullable|url|max:255',
        ]);

        try {
            // Handle multiple file uploads for update
            if ($request->hasFile('attachments')) {
                // Delete old attachments if they exist
                if ($award->attachment_paths) {
                    foreach ($award->attachment_paths as $oldPath) {
                        if (Storage::disk('spaces')->exists($oldPath)) {
                            Storage::disk('spaces')->delete($oldPath);
                        }
                    }
                }

                // Upload new attachments
                $attachmentPaths = [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('award-attachments', 'spaces');
                    $attachmentPaths[] = $path;
                }
                $award->attachment_paths = $attachmentPaths;
            }

            if ($award->status === 'rejected') {
                $validatedData['status'] = 'pending';
                $validatedData['remarks'] = null;
            }

            $award->update($validatedData);
            $award->load(['user', 'college', 'college.campus']);

            return response()->json([
                'success' => true,
                'data' => $award,
                'message' => 'International partner updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update international partner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Archive the specified resource.
     * PATCH /api/awards-recognition/{award}/archive
     */
    public function archive(Award $award, Request $request)
    {
        // Validate password presence
        if (! $request->filled('password')) {
            return response()->json([
                'success' => false,
                'message' => 'Password is required'
            ], 422);
        }

        $user = $request->user();

        // Check provided password against authenticated user's password
        if (! Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password does not match'
            ], 403);
        }

        // Check if user is admin OR the owner of the record
        if ($user->role !== 'admin' && $user->id !== $award->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action. You can only archive your own records.'
            ], 403);
        }

        try {
            $award->is_archived = true;
            $award->save();

            return response()->json([
                'success' => true,
                'data' => $award,
                'message' => 'Award archived successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to archive international partner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function review(Request $request, Award $award)
    {
        return $this->reviewItem($request, $award);
    }

    public function getUserAwards(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $query = Award::with(['college', 'college.campus', 'user'])
                ->where('user_id', $user->id)
                ->where('is_archived', false);

            $awards = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $awards,
                'stats' => [
                    'total' => $awards->count(),
                    'approved' => $awards->where('status', 'approved')->count(),
                    'pending' => $awards->where('status', 'pending')->count(),
                    'rejected' => $awards->where('status', 'rejected')->count(),
                ],
                'message' => 'User awards retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user awards',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
