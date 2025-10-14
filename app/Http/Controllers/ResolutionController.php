<?php

namespace App\Http\Controllers;

use App\Models\Resolution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ResolutionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $resolutions = Resolution::with('user')->where('is_archived', false)->get();

            return response()->json([
                'success' => true,
                'data' => $resolutions,
                'message' => 'Resolutions retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve resolutions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'resolution_number' => 'required|string|max:255|unique:resolutions,resolution_number',
            'effectivity' => 'required|date',
            'expiration' => 'required|date|after:effectivity',
            'partner_agency' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'contact_number_email' => 'required|string|max:255',
            'attachment_link' => 'nullable|url',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240', // Max 10MB per file
        ]);

        try {
            // Handle file uploads if present
            $attachmentPaths = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('resolution-attachments', 'spaces');
                    $attachmentPaths[] = $path;
                }
            }

            // Create resolution
            $resolution = Resolution::create([
                'resolution_number' => $validatedData['resolution_number'],
                'effectivity' => $validatedData['effectivity'],
                'expiration' => $validatedData['expiration'],
                'partner_agency' => $validatedData['partner_agency'],
                'contact_person' => $validatedData['contact_person'],
                'contact_number_email' => $validatedData['contact_number_email'],
                'attachment_link' => $validatedData['attachment_link'] ?? null,
                'attachment_paths' => $attachmentPaths,
                'user_id' => $request->user()->id,
                'is_archived' => false,
            ]);

            // Load relationships for response
            $resolution->load('user');

            return response()->json([
                'success' => true,
                'data' => $resolution,
                'message' => 'Resolution created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create resolution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Resolution $resolution)
    {
        try {
            $resolution->load(['user']);

            return response()->json([
                'success' => true,
                'data' => $resolution,
                'message' => 'Resolution retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve resolution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Resolution $resolution)
    {
        $validatedData = $request->validate([
            'resolution_number' => 'required|string|max:255|unique:resolutions,resolution_number,' . $resolution->id,
            'effectivity' => 'required|date',
            'expiration' => 'required|date|after:effectivity',
            'partner_agency' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'contact_number_email' => 'required|string|max:255',
            'attachment_link' => 'nullable|url',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240', // Max 10MB per file
            'is_archived' => 'nullable|boolean',
        ]);

        try {
            // Handle file uploads if present
            $attachmentPaths = $resolution->attachment_paths ?? [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('resolution-attachments', 'spaces');
                    $attachmentPaths[] = $path;
                }
            }

            if ($request->hasFile('attachments')) {
                // Delete old attachments if they exist
                if ($resolution->attachment_paths) {
                    foreach ($resolution->attachment_paths as $oldPath) {
                        if (Storage::disk('spaces')->exists($oldPath)) {
                            Storage::disk('spaces')->delete($oldPath);
                        }
                    }
                }

                // Upload new attachments
                $attachmentPaths = [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('resolution-attachments', 'spaces');
                    $attachmentPaths[] = $path;
                }
                $resolution->attachment_paths = $attachmentPaths;
            }

            $resolution->update($validatedData);
            $resolution->load(['user']);

            return response()->json([
                'success' => true,
                'data' => $resolution,
                'message' => 'Resolution updated successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update resolution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Resolution $resolution)
    {
        //
    }

    /**
     * Archive the specified resource.
     */
    public function archive(Request $request, Resolution $resolution)
    {
        $user = $request->user();

        // Check if user is admin
        if ($user->role !== 'admin' || $user->id !== $resolution->user_id) {
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
            $resolution->is_archived = true;
            $resolution->save();

            return response()->json([
                'success' => true,
                'data' => $resolution,
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
}
