<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait Reviewable
{
    public function reviewItem(Request $request, $model): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'review_notes' => 'nullable|string|max:500',
        ]);

        $model->status = $validated['status'];
        $model->review_notes = $validated['review_notes'] ?? null;
        $model->save();

        return response()->json([
            'success' => true,
            'data' => $model,
            'message' => "{$model->getTable()} {$validated['status']} successfully",
        ]);
    }
}
