<?php

namespace App\Http\Controllers;

use App\Models\ImpactAssessment;
use App\Traits\Reviewable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ImpactAssessmentController extends Controller
{
    use Reviewable;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = ImpactAssessment::with(['user', 'techTransfer.college', 'techTransfer.college.campus'])
                ->where('is_archived', false)
                ->where('status', 'approved');

            $user = $request->user();

            if ($user->role !== 'admin') {
                // Non-admins: only their own
                $query->where('user_id', $user->id);
            }

            if ($request->has('campus')) {
                // ImpactAssessment -> techTransfer -> college -> campus
                // Filter by campus through the techTransfer's college
                $campusId = $request->campus;
                $query->whereHas('techTransfer.college', function ($q) use ($campusId) {
                    $q->where('campus_id', $campusId);
                });
            }

            // Filter by college if provided
            if ($request->has('college_id')) {
                $query->whereHas('techTransfer', function ($q) use ($request) {
                    $q->where('college_id', $request->college_id);
                });
            }

            $assessments = $query->get();

            return response()->json([
                'success' => true,
                'data' => $assessments,
                'message' => 'Impact Assessments retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve impact assessments',
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
                'tech_transfer_id' => 'required|exists:tech_transfers,id',
                'title' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
                'attachments.*' => 'file|mimes:pdf,doc,docx|max:10240',
            ]);
            $user = $request->user();
            $validatedData['user_id'] = $user->id;

            // Handle multiple file uploads
            if ($request->hasFile('attachments')) {
                $attachmentPaths = [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('impact-attachments', 'spaces');
                    $attachmentPaths[] = $path;
                }
                $validatedData['attachment_paths'] = $attachmentPaths;
            }

            $assessment = ImpactAssessment::create($validatedData);
            $assessment->load(['user', 'techTransfer.college']);

            return response()->json([
                'success' => true,
                'data' => $assessment,
                'message' => 'Impact assessment created successfully'
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
                'message' => 'Failed to create impact assessment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ImpactAssessment $impactAssessment, Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin' && $user->id !== $impactAssessment->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        //check if archived
        if ($impactAssessment->is_archived) {
            return response()->json([
                'success' => false,
                'message' => 'Impact assessment is archived',
            ], 404);
        }

        try {
            $impactAssessment->load(['user', 'techTransfer.college', 'techTransfer.college.campus']);

            return response()->json([
                'success' => true,
                'data' => $impactAssessment,
                'message' => 'Impact assessment retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve impact assessment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ImpactAssessment $impactAssessment)
    {
        $user = $request->user();
        if ($user->role !== 'admin' && $user->id !== $impactAssessment->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        //check if archived
        if ($impactAssessment->is_archived) {
            return response()->json([
                'success' => false,
                'message' => 'Not Found',
            ], 404);
        }

        try {
            $validatedData = $request->validate([
                'tech_transfer_id' => 'sometimes|required|exists:tech_transfers,id',
                'title' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string|max:1000',
                'attachments.*' => 'file|mimes:pdf,doc,docx|max:10240',
            ]);

            // Handle multiple file uploads for update
            if ($request->hasFile('attachments')) {
                // Delete old attachments if they exist
                if ($impactAssessment->attachment_paths) {
                    foreach ($impactAssessment->attachment_paths as $oldPath) {
                        if (Storage::disk('spaces')->exists($oldPath)) {
                            Storage::disk('spaces')->delete($oldPath);
                        }
                    }
                }

                // Upload new attachments
                $attachmentPaths = [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('impact-attachments', 'spaces');
                    $attachmentPaths[] = $path;
                }
                $impactAssessment->attachment_paths = $attachmentPaths;
            }

            if ($impactAssessment->status === 'rejected') {
                $validatedData['status'] = 'pending';
                $validatedData['remarks'] = null;
            }

            $impactAssessment->update($validatedData);
            $impactAssessment->load(['user', 'techTransfer.college', 'techTransfer.college.campus']);

            return response()->json([
                'success' => true,
                'data' => $impactAssessment,
                'message' => 'Impact assessment updated successfully'
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

    public function archive(ImpactAssessment $impactAssessment, Request $request)
    {
        $user = $request->user();

        // Check if user is admin
        if ($user->role !== 'admin' && $user->id !== $impactAssessment->user_id) {
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
            $impactAssessment->is_archived = true;
            $impactAssessment->save();

            return response()->json([
                'success' => true,
                'data' => $impactAssessment,
                'message' => 'Impact assessment archived successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to archive impact assessment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function review(Request $request, ImpactAssessment $impactAssessment)
    {
        return $this->reviewItem($request, $impactAssessment);
    }

    public function getUserImpactAssessments(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $query = ImpactAssessment::with(['techTransfer.college', 'techTransfer.college.campus', 'user'])
                ->where('user_id', $user->id)
                ->where('is_archived', false);

            $impactAssessments = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $impactAssessments,
                'message' => 'User impact assessments retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user impact assessments',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
