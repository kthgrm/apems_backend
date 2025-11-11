<?php

namespace App\Http\Controllers;

use App\Models\College;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CollegeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $colleges = College::all();

            return response()->json([
                'success' => true,
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

    /**
     * Store a newly created college.
     * POST /api/colleges
     */
    public function store(Request $request)
    {
        // Check if user is admin
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action'
            ], 403);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'campus_id' => 'required|exists:campuses,id',
            'logo' => 'required|image|mimes:jpg,jpeg,png|max:2048', // Max 2MB, optional
        ]);

        try {
            // Handle logo file upload if present
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('college-logos', 'spaces');
                $validatedData['logo'] = $logoPath;
            }

            // Create college with validated data
            $college = College::create($validatedData);

            // Load the campus relationship for the response
            $college->load('campus');

            return response()->json([
                'success' => true,
                'data' => $college,
                'message' => 'College created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create college',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(College $college)
    {
        $college->load('campus');

        try {
            return response()->json([
                'success' => true,
                'data' => $college,
                'message' => 'College retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve college',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, College $college)
    {
        // Check if user is admin
        $user = $request->user();
        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action'
            ], 403);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:10',
            'campus_id' => 'sometimes|exists:campuses,id',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // Max 2MB, optional
        ]);

        try {
            // Handle logo file upload if present
            if ($request->hasFile('logo')) {
                if ($college->logo) {
                    // Delete the old logo from storage
                    $oldPath = $college->logo;
                    if (Storage::disk('spaces')->exists($oldPath)) {
                        Storage::disk('spaces')->delete($oldPath);
                    }
                }
                $logoPath = $request->file('logo')->store('college-logos', 'spaces');
                $validatedData['logo'] = $logoPath;
            }

            // Update college with validated data
            $college->update($validatedData);

            // Load the campus relationship for the response
            $college->load('campus');

            return response()->json([
                'success' => true,
                'data' => $college,
                'message' => 'College updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update college',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, College $college)
    {
        $user = $request->user();

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
            if ($college->logo) {
                $logoPath = $college->logo;
                if (Storage::disk('spaces')->exists($logoPath)) {
                    Storage::disk('spaces')->delete($logoPath);
                }
            }

            $college->delete();

            return response()->json([
                'success' => true,
                'message' => 'College deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete college',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
