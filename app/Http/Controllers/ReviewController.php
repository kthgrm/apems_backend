<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\Engagement;
use App\Models\ImpactAssessment;
use App\Models\Modality;
use App\Models\TechTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function getPendingSubmissions(Request $request): JsonResponse
    {
        $type = $request->query('type', 'all');

        $submissions = [];

        if ($type === 'all' || $type === 'tech-transfer') {
            $techTransfers = TechTransfer::with(['user', 'college', 'college.campus'])
                ->where('status', 'pending')
                ->where('is_archived', false)
                ->get()
                ->map(fn($item) => array_merge($item->toArray(), ['type' => 'tech-transfer']));
            $submissions = array_merge($submissions, $techTransfers->toArray());
        }

        if ($type === 'all' || $type === 'awards') {
            $awards = Award::with(['user', 'college', 'college.campus'])
                ->where('status', 'pending')
                ->where('is_archived', false)
                ->get()
                ->map(fn($item) => array_merge($item->toArray(), ['type' => 'award']));
            $submissions = array_merge($submissions, $awards->toArray());
        }

        if ($type === 'all' || $type === 'engagements') {
            $engagements = Engagement::with(['user', 'college', 'college.campus'])
                ->where('status', 'pending')
                ->where('is_archived', false)
                ->get()
                ->map(fn($item) => array_merge($item->toArray(), ['type' => 'engagement']));
            $submissions = array_merge($submissions, $engagements->toArray());
        }

        if ($type === 'all' || $type === 'modalities') {
            $modalities = Modality::with(['user', 'techTransfer.college', 'techTransfer.college.campus'])
                ->where('status', 'pending')
                ->where('is_archived', false)
                ->get()
                ->map(fn($item) => array_merge($item->toArray(), ['type' => 'modality']));
            $submissions = array_merge($submissions, $modalities->toArray());
        }

        if ($type === 'all' || $type === 'impact-assessments') {
            $impactAssessments = ImpactAssessment::with(['user', 'techTransfer.college', 'techTransfer.college.campus'])
                ->where('status', 'pending')
                ->where('is_archived', false)
                ->get()
                ->map(fn($item) => array_merge($item->toArray(), ['type' => 'impact-assessment']));
            $submissions = array_merge($submissions, $impactAssessments->toArray());
        }

        // Sort by submission date
        usort(
            $submissions,
            fn($a, $b) =>
            strtotime($b['created_at']) - strtotime($a['created_at'])
        );

        return response()->json([
            'success' => true,
            'data' => $submissions,
            'stats' => [
                'total' => count($submissions),
                'tech_transfers' => count(array_filter($submissions, fn($s) => $s['type'] === 'tech-transfer')),
                'awards' => count(array_filter($submissions, fn($s) => $s['type'] === 'award')),
                'engagements' => count(array_filter($submissions, fn($s) => $s['type'] === 'engagement')),
                'modalities' => count(array_filter($submissions, fn($s) => $s['type'] === 'modality')),
                'impact_assessments' => count(array_filter($submissions, fn($s) => $s['type'] === 'impact-assessment')),
            ]
        ]);
    }

    public function reviewSubmission(Request $request, string $type, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'remarks' => 'nullable|string'
        ]);

        $model = match ($type) {
            'tech-transfer' => TechTransfer::class,
            'award' => Award::class,
            'modality' => Modality::class,
            'engagement' => Engagement::class,
            'impact-assessment' => ImpactAssessment::class,
            default => throw new \Exception('Invalid type')
        };

        $submission = $model::findOrFail($id);

        $submission->update([
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $submission,
            'message' => "Submission {$validated['status']} successfully"
        ]);
    }
}
