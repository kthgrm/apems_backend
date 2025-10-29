<?php

namespace App\Http\Controllers;

use App\Models\Modality;
use App\Traits\Reviewable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ModalityController extends Controller
{
    use Reviewable;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Modality::with(['user', 'techTransfer.college', 'techTransfer.college.campus'])
                ->where('is_archived', false);

            $user = $request->user();

            if ($user->role !== 'admin') {
                // Non-admins: only their own approved submissions
                $query->where('user_id', $user->id)
                    ->where('status', 'approved');
            }

            if ($request->has('campus')) {
                // modality -> techTransfer -> college -> campus
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

            $modality = $query->get();

            return response()->json([
                'success' => true,
                'data' => $modality,
                'message' => 'Modalities retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve modalities',
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
                'modality' => 'required|string|max:255',
                'tv_channel' => 'nullable|string|max:255',
                'radio' => 'nullable|string|max:255',
                'online_link' => 'nullable|url|max:255',
                'time_air' => 'nullable|string|max:255',
                'period' => 'required|string|max:255',
                'partner_agency' => 'required|string|max:255',
                'hosted_by' => 'required|string|max:255',
            ]);

            $user = $request->user();
            $validatedData['user_id'] = $user->id;

            $modality = Modality::create($validatedData);
            $modality->load(['user', 'techTransfer.college', 'techTransfer.college.campus']);

            return response()->json([
                'success' => true,
                'data' => $modality,
                'message' => 'Modality created successfully'
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
                'message' => 'Failed to create modality',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Modality $modality, Request $request)
    {
        $user = $request->user();
        if ($user->role !== 'admin' && $user->id !== $modality->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        //check if archived
        if ($modality->is_archived) {
            return response()->json([
                'success' => false,
                'message' => 'Modality is archived',
            ], 404);
        }

        try {
            $modality->load(['user', 'techTransfer.college', 'techTransfer.college.campus']);

            return response()->json([
                'success' => true,
                'data' => $modality,
                'message' => 'Modality retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve modality',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Modality $modality)
    {
        $user = $request->user();
        if ($user->role !== 'admin' && $user->id !== $modality->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        //check if archived
        if ($modality->is_archived) {
            return response()->json([
                'success' => false,
                'message' => 'Not Found',
            ], 404);
        }

        try {
            $validatedData = $request->validate([
                'tech_transfer_id' => 'required|exists:tech_transfers,id',
                'modality' => 'required|string|max:255',
                'tv_channel' => 'nullable|string|max:255',
                'radio' => 'nullable|string|max:255',
                'online_link' => 'nullable|string|max:255',
                'time_air' => 'nullable|string|max:255',
                'period' => 'required|string|max:255',
                'partner_agency' => 'required|string|max:255',
                'hosted_by' => 'required|string|max:255',
            ]);

            $modality->update($validatedData);
            $modality->load(['user', 'techTransfer.college', 'techTransfer.college.campus']);

            return response()->json([
                'success' => true,
                'data' => $modality,
                'message' => 'Modality updated successfully'
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

    public function archive(Modality $modality, Request $request)
    {
        $user = $request->user();

        // Check if user is admin
        if ($user->role !== 'admin' && $user->id !== $modality->user_id) {
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
            $modality->is_archived = true;
            $modality->save();

            return response()->json([
                'success' => true,
                'data' => $modality,
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

    public function review(Request $request, Modality $modality)
    {
        return $this->reviewItem($request, $modality);
    }
}
