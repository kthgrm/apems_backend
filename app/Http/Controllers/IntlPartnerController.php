<?php

namespace App\Http\Controllers;

use App\Models\IntlPartner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class IntlPartnerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = IntlPartner::with(['user', 'college', 'college.campus'])->where('is_archived', false);

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
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'agency_partner' => 'required|string|max:255',
                'location' => 'required|string|max:255',
                'activity_conducted' => 'required|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'number_of_participants' => 'required|integer|min:0',
                'number_of_committee' => 'required|integer|min:0',
                'narrative' => 'required|string|max:5000',
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
                    $path = $file->store('partner-attachments', 'spaces');
                    $attachmentPaths[] = $path;
                }
                $validatedData['attachment_paths'] = $attachmentPaths;
            }

            $partner = IntlPartner::create($validatedData);
            $partner->load(['user', 'college', 'college.campus']);

            return response()->json([
                'success' => true,
                'data' => $partner,
                'message' => 'International partner created successfully'
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
                'message' => 'Failed to create international partner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/international-partners/{internationalPartner}
     */
    public function show(IntlPartner $internationalPartner): JsonResponse
    {
        try {
            $internationalPartner->load(['user', 'college', 'college.campus']);

            return response()->json([
                'success' => true,
                'data' => $internationalPartner,
                'message' => 'International partner retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve international partner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, IntlPartner $internationalPartner): JsonResponse
    {
        $validatedData = $request->validate([
            'agency_partner' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'activity_conducted' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'number_of_participants' => 'required|integer|min:0',
            'number_of_committee' => 'required|integer|min:0',
            'narrative' => 'required|string|max:5000',
            'attachments.*' => 'file|mimes:jpeg,jpg,png,pdf,doc,docx|max:10240',
            'attachment_link' => 'nullable|url|max:255',
        ]);

        try {
            // Handle multiple file uploads for update
            if ($request->hasFile('attachments')) {
                // Delete old attachments if they exist
                if ($internationalPartner->attachment_paths) {
                    foreach ($internationalPartner->attachment_paths as $oldPath) {
                        if (Storage::disk('spaces')->exists($oldPath)) {
                            Storage::disk('spaces')->delete($oldPath);
                        }
                    }
                }

                // Upload new attachments
                $attachmentPaths = [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('partner-attachments', 'spaces');
                    $attachmentPaths[] = $path;
                }
                $internationalPartner->attachment_paths = $attachmentPaths;
            }

            $internationalPartner->update($validatedData);
            $internationalPartner->load(['user', 'college', 'college.campus']);

            return response()->json([
                'success' => true,
                'data' => $internationalPartner,
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
     */
    public function archive(Request $request, IntlPartner $internationalPartner): JsonResponse
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
        if ($user->role !== 'admin' && $user->id !== $internationalPartner->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action. You can only archive your own records.'
            ], 403);
        }

        try {
            $internationalPartner->is_archived = true;
            $internationalPartner->save();

            return response()->json([
                'success' => true,
                'data' => $internationalPartner,
                'message' => 'International partner archived successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to archive international partner',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
