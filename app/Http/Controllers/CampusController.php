<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CampusController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/campuses
     */
    public function index(): JsonResponse
    {
        try {
            $campuses = Campus::with('colleges')
                ->withCount([
                    'techTransfers as tech_transfers_count' => function ($query) {
                        $query->where('is_archived', false)
                            ->where('status', 'approved');
                    },
                    'awards as awards_count' => function ($query) {
                        $query->where('is_archived', false)
                            ->where('status', 'approved');
                    },
                    'engagements as engagements_count' => function ($query) {
                        $query->where('is_archived', false)
                            ->where('status', 'approved');
                    },
                ])
                ->get();

            // Manually add impact assessments count for each campus
            $campuses->each(function ($campus) {
                $campus->impact_assessments_count = $campus->impactAssessments()
                    ->where('is_archived', false)
                    ->where('status', 'approved')
                    ->count();
            });

            // Manually add modalities count for each campus
            $campuses->each(function ($campus) {
                $campus->modalities_count = $campus->modalities()
                    ->where('is_archived', false)
                    ->where('status', 'approved')
                    ->count();
            });

            return response()->json([
                'success' => true,
                'data' => $campuses,
                'message' => 'Campuses retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve campuses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/campuses
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'required|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        try {
            // Handle logo file upload if present
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('campus-logos', 'spaces');
                $validatedData['logo'] = $logoPath;
            }

            $campus = Campus::create($validatedData);

            return response()->json([
                'success' => true,
                'data' => $campus,
                'message' => 'Campus created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create campus',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * GET /api/campuses/{campus}
     */
    public function show(Campus $campus): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $campus,
                'message' => 'Campus retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve campus',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/campuses/{campus}
     */
    public function update(Request $request, Campus $campus): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ]);

        try {
            // Handle logo file upload if present
            if ($request->hasFile('logo')) {
                if ($campus->logo) {
                    // Delete the old logo from storage
                    $oldPath = $campus->logo;
                    if (Storage::disk('spaces')->exists($oldPath)) {
                        Storage::disk('spaces')->delete($oldPath);
                    }
                }
                $logoPath = $request->file('logo')->store('campus-logos', 'spaces');
                $validatedData['logo'] = $logoPath;
            }

            $campus->update($validatedData);

            return response()->json([
                'success' => true,
                'data' => $campus,
                'message' => 'Campus updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update campus',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/campuses/{campus}
     */
    public function destroy(Request $request, Campus $campus): JsonResponse
    {
        // Ensure there's an authenticated user
        $user = $request->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Check if user is admin
        if ($user->role !== 'admin') {
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

        // Validate password correctness
        if (! Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password'
            ], 401);
        }

        try {
            if ($campus->logo) {
                $logoPath = $campus->logo;
                if (Storage::disk('spaces')->exists($logoPath)) {
                    Storage::disk('spaces')->delete($logoPath);
                }
            }

            $campus->delete();

            return response()->json([
                'success' => true,
                'message' => 'Campus deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete campus',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCollegesFromCampus(Campus $campus): JsonResponse
    {
        try {
            $colleges = $campus->colleges()
                ->withCount([
                    'techTransfers as tech_transfers_count' => function ($query) {
                        $query->where('is_archived', false)
                            ->where('status', 'approved');
                    },
                    'awards as awards_count' => function ($query) {
                        $query->where('is_archived', false)
                            ->where('status', 'approved');
                    },
                    'engagements as engagements_count' => function ($query) {
                        $query->where('is_archived', false)
                            ->where('status', 'approved');
                    },
                ])
                ->get();

            // Manually add impact assessments count for each college
            $colleges->each(function ($college) {
                $college->impact_assessments_count = $college->impactAssessments()
                    ->where('is_archived', false)
                    ->where('status', 'approved')
                    ->count();
            });

            // Manually add modalities count for each college
            $colleges->each(function ($college) {
                $college->modalities_count = $college->modalities()
                    ->where('is_archived', false)
                    ->where('status', 'approved')
                    ->count();
            });

            return response()->json([
                'success' => true,
                'campus' => $campus,
                'data' => $colleges,
                'message' => 'Colleges retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve colleges',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
