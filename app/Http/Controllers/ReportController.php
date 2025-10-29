<?php

namespace App\Http\Controllers;

use App\Models\TechTransfer;
use App\Models\Campus;
use App\Models\College;
use App\Models\Award;
use App\Models\ImpactAssessment;
use App\Models\Modality;
use App\Models\Resolution;
use App\Models\AuditLog;
use App\Models\Engagement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Get technology transfers report data
     */
    public function technologyTransfers(Request $request): JsonResponse
    {
        $query = TechTransfer::with(['user', 'college', 'college.campus'])
            ->where('is_archived', false)
            ->where('status', 'approved');

        // Apply filters
        if ($request->filled('campus_id')) {
            $query->whereHas('college', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        if ($request->filled('college_id')) {
            $query->where('college_id', $request->college_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('leader', 'like', "%{$search}%")
                    ->orWhere('agency_partner', 'like', "%{$search}%");
            });
        }

        // Date range filter (using month input from frontend)
        if ($request->filled('date_from')) {
            $dateFrom = $request->date_from . '-01'; // Convert YYYY-MM to YYYY-MM-DD
            $query->where('start_date', '>=', $dateFrom);
        }

        if ($request->filled('date_to')) {
            $dateTo = $request->date_to . '-01'; // Convert YYYY-MM to YYYY-MM-DD
            // Get the last day of the month
            $lastDay = date('Y-m-t', strtotime($dateTo));
            $query->where('start_date', '<=', $lastDay);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $projects = $query->paginate(15)->withQueryString();

        // Transform the data to match frontend expectations
        $projects->getCollection()->transform(function ($project) {
            return [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'category' => $project->category,
                'purpose' => $project->purpose,
                'start_date' => $project->start_date?->format('Y-m-d'),
                'end_date' => $project->end_date?->format('Y-m-d'),
                'leader' => $project->leader,
                'created_at' => $project->created_at?->format('Y-m-d H:i:s'),
                'user' => $project->user ? [
                    'id' => $project->user->id,
                    'first_name' => $project->user->first_name,
                    'last_name' => $project->user->last_name,
                ] : null,
                'college' => $project->college ? [
                    'id' => $project->college->id,
                    'name' => $project->college->name,
                    'campus' => $project->college->campus ? [
                        'id' => $project->college->campus->id,
                        'name' => $project->college->campus->name,
                    ] : null,
                ] : null,
            ];
        });

        // Get all campuses and colleges for filters
        $campuses = Campus::orderBy('name')->get(['id', 'name']);
        $colleges = College::with('campus:id,name')->orderBy('name')->get(['id', 'name', 'campus_id']);

        // Calculate statistics
        $statistics = [
            'total_projects' => TechTransfer::where('is_archived', false)->count(),
            'projects_by_month' => TechTransfer::where('is_archived', false)
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as period, COUNT(*) as count')
                ->groupBy('period')
                ->orderBy('period', 'desc')
                ->limit(12)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'projects' => $projects,
                'campuses' => $campuses,
                'colleges' => $colleges,
                'filters' => [
                    'campus_id' => $request->campus_id,
                    'college_id' => $request->college_id,
                    'date_from' => $request->date_from,
                    'date_to' => $request->date_to,
                    'search' => $request->search,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ],
                'statistics' => $statistics,
            ],
            'message' => 'Technology transfers report retrieved successfully'
        ], 200);
    }

    /**
     * Generate PDF for technology transfers report
     */
    public function technologyTransfersPdf(Request $request)
    {
        $query = TechTransfer::with(['user', 'college', 'college.campus'])
            ->where('is_archived', false)
            ->where('status', 'approved');

        $user = $request->user();

        if ($user->role === 'user') {
            // Limit to user's own submissions
            $query->where('user_id', $user->id);
        }

        // Apply the same filters as the main report
        if ($request->filled('campus_id')) {
            $query->whereHas('college', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        if ($request->filled('college_id')) {
            $query->where('college_id', $request->college_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('leader', 'like', "%{$search}%")
                    ->orWhere('agency_partner', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $dateFrom = $request->date_from . '-01';
            $query->where('start_date', '>=', $dateFrom);
        }

        if ($request->filled('date_to')) {
            $dateTo = $request->date_to . '-01';
            $lastDay = date('Y-m-t', strtotime($dateTo));
            $query->where('start_date', '<=', $lastDay);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $projects = $query->get();

        // Get filter information for display
        $filterInfo = [];
        if ($request->filled('campus_id')) {
            $campus = Campus::find($request->campus_id);
            $filterInfo['campus'] = $campus?->name;
        }
        if ($request->filled('college_id')) {
            $college = College::find($request->college_id);
            $filterInfo['college'] = $college?->name;
        }
        if ($request->filled('date_from')) {
            $filterInfo['date_from'] = $request->date_from;
        }
        if ($request->filled('date_to')) {
            $filterInfo['date_to'] = $request->date_to;
        }
        if ($request->filled('search')) {
            $filterInfo['search'] = $request->search;
        }

        // Prepare filters array for the template
        $filters = [
            'search' => $request->search,
            'campus_id' => $filterInfo['campus'] ?? null,
            'college_id' => $filterInfo['college'] ?? null,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'sort_by' => $request->sort_by,
            'sort_order' => $request->sort_order ?? 'desc',
        ];

        // Statistics for the template
        $statistics = [
            'total_projects' => $projects->count(),
        ];

        $pdf = Pdf::loadView('reports.projects-pdf', [
            'projects' => $projects,
            'filters' => $filters,
            'generated_at' => now('Asia/Manila')->format('F d, Y h:i A'),
            'generated_by' => $request->user()->first_name . ' ' . $request->user()->last_name ?? 'System',
            'statistics' => $statistics,
        ]);

        // Set paper size and orientation to portrait
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('technology-transfers-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Get awards report data
     */
    public function awards(Request $request): JsonResponse
    {
        $query = Award::with(['user', 'college', 'college.campus'])
            ->where('is_archived', false)
            ->where('status', 'approved');

        // Apply filters
        if ($request->filled('campus_id')) {
            $query->whereHas('college', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        if ($request->filled('college_id')) {
            $query->where('college_id', $request->college_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('award_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('awarding_body', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $dateFrom = $request->date_from . '-01';
            $query->where('date_received', '>=', $dateFrom);
        }

        if ($request->filled('date_to')) {
            $dateTo = $request->date_to . '-01';
            $lastDay = date('Y-m-t', strtotime($dateTo));
            $query->where('date_received', '<=', $lastDay);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $awards = $query->paginate(15)->withQueryString();

        $campuses = Campus::orderBy('name')->get(['id', 'name']);
        $colleges = College::with('campus:id,name')->orderBy('name')->get(['id', 'name', 'campus_id']);

        $statistics = [
            'total_awards' => Award::where('is_archived', false)->count(),
            'awards_by_month' => Award::where('is_archived', false)
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as period, COUNT(*) as count')
                ->groupBy('period')
                ->orderBy('period', 'desc')
                ->limit(12)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'awards' => $awards,
                'campuses' => $campuses,
                'colleges' => $colleges,
                'filters' => [
                    'campus_id' => $request->campus_id,
                    'college_id' => $request->college_id,
                    'date_from' => $request->date_from,
                    'date_to' => $request->date_to,
                    'search' => $request->search,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ],
                'statistics' => $statistics,
            ],
            'message' => 'Awards report retrieved successfully'
        ], 200);
    }

    /**
     * Generate PDF for awards report
     */
    public function awardsPdf(Request $request)
    {
        $query = Award::with(['user', 'college', 'college.campus'])
            ->where('is_archived', false)
            ->where('status', 'approved');

        $user = $request->user();

        if ($user->role === 'user') {
            // Limit to user's own submissions
            $query->where('user_id', $user->id);
        }

        if ($request->filled('campus_id')) {
            $query->whereHas('college', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        if ($request->filled('college_id')) {
            $query->where('college_id', $request->college_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('award_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('awarding_body', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $dateFrom = $request->date_from . '-01';
            $query->where('date_received', '>=', $dateFrom);
        }

        if ($request->filled('date_to')) {
            $dateTo = $request->date_to . '-01';
            $lastDay = date('Y-m-t', strtotime($dateTo));
            $query->where('date_received', '<=', $lastDay);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $awards = $query->get();

        $filterInfo = [];
        if ($request->filled('campus_id')) {
            $campus = Campus::find($request->campus_id);
            $filterInfo['campus'] = $campus?->name;
        }
        if ($request->filled('college_id')) {
            $college = College::find($request->college_id);
            $filterInfo['college'] = $college?->name;
        }

        $filters = [
            'search' => $request->search,
            'campus_id' => $filterInfo['campus'] ?? null,
            'college_id' => $filterInfo['college'] ?? null,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'sort_by' => $request->sort_by,
            'sort_order' => $request->sort_order ?? 'desc',
        ];

        $statistics = [
            'total_awards' => $awards->count(),
        ];

        $pdf = Pdf::loadView('reports.awards-pdf', [
            'awards' => $awards,
            'filters' => $filters,
            'generated_at' => now('Asia/Manila')->format('F d, Y h:i A'),
            'generated_by' => $request->user()->first_name . ' ' . $request->user()->last_name ?? 'System',
            'statistics' => $statistics,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('awards-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Generate PDF for engagements report
     */
    public function engagements(Request $request): JsonResponse
    {
        $query = Engagement::with(['user', 'college', 'college.campus'])
            ->where('is_archived', false)
            ->where('status', 'approved');

        if ($request->filled('campus_id')) {
            $query->whereHas('college', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        if ($request->filled('college_id')) {
            $query->where('college_id', $request->college_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('agency_partner', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('activity_conducted', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $dateFrom = $request->date_from . '-01';
            $query->where('start_date', '>=', $dateFrom);
        }

        if ($request->filled('date_to')) {
            $dateTo = $request->date_to . '-01';
            $lastDay = date('Y-m-t', strtotime($dateTo));
            $query->where('start_date', '<=', $lastDay);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $engagements = $query->paginate(15)->withQueryString();

        $campuses = Campus::orderBy('name')->get(['id', 'name']);
        $colleges = College::with('campus:id,name')->orderBy('name')->get(['id', 'name', 'campus_id']);

        $statistics = [
            'total_engagements' => Engagement::where('is_archived', false)->count(),
            'engagements_by_month' => Engagement::where('is_archived', false)
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as period, COUNT(*) as count')
                ->groupBy('period')
                ->orderBy('period', 'desc')
                ->limit(12)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'engagements' => $engagements,
                'campuses' => $campuses,
                'colleges' => $colleges,
                'filters' => [
                    'campus_id' => $request->campus_id,
                    'college_id' => $request->college_id,
                    'date_from' => $request->date_from,
                    'date_to' => $request->date_to,
                    'search' => $request->search,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ],
                'statistics' => $statistics,
            ],
            'message' => 'Engagement report retrieved successfully'
        ], 200);
    }

    /**
     * Generate PDF for engagements report
     */
    public function engagementsPdf(Request $request)
    {
        $query = Engagement::with(['user', 'college', 'college.campus'])
            ->where('is_archived', false)
            ->where('status', 'approved');

        $user = $request->user();

        if ($user->role === 'user') {
            // Limit to user's own submissions
            $query->where('user_id', $user->id);
        }

        if ($request->filled('campus_id')) {
            $query->whereHas('college', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        if ($request->filled('college_id')) {
            $query->where('college_id', $request->college_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('agency_partner', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('activity_conducted', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $dateFrom = $request->date_from . '-01';
            $query->where('start_date', '>=', $dateFrom);
        }

        if ($request->filled('date_to')) {
            $dateTo = $request->date_to . '-01';
            $lastDay = date('Y-m-t', strtotime($dateTo));
            $query->where('start_date', '<=', $lastDay);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $engagements = $query->get();

        $filterInfo = [];
        if ($request->filled('campus_id')) {
            $campus = Campus::find($request->campus_id);
            $filterInfo['campus'] = $campus?->name;
        }
        if ($request->filled('college_id')) {
            $college = College::find($request->college_id);
            $filterInfo['college'] = $college?->name;
        }

        $filters = [
            'search' => $request->search,
            'campus_id' => $filterInfo['campus'] ?? null,
            'college_id' => $filterInfo['college'] ?? null,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'sort_by' => $request->sort_by,
            'sort_order' => $request->sort_order ?? 'desc',
        ];

        $statistics = [
            'total_engagements' => $engagements->count(),
        ];

        $pdf = Pdf::loadView('reports.engagements-pdf', [
            'engagements' => $engagements,
            'filters' => $filters,
            'generated_at' => now('Asia/Manila')->format('F d, Y h:i A'),
            'generated_by' => $request->user()->first_name . ' ' . $request->user()->last_name ?? 'System',
            'statistics' => $statistics,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('engagements-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Get impact assessments report data
     */
    public function impactAssessments(Request $request): JsonResponse
    {
        $query = ImpactAssessment::with(['user', 'techTransfer.college', 'techTransfer.college.campus'])
            ->where('is_archived', false)
            ->where('status', 'approved');

        if ($request->filled('campus_id')) {
            $query->whereHas('techTransfer.college', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        if ($request->filled('college_id')) {
            $query->whereHas('techTransfer', function ($q) use ($request) {
                $q->where('college_id', $request->college_id);
            });
        }

        if ($request->filled('date_from')) {
            $dateFrom = $request->date_from . '-01';
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($request->filled('date_to')) {
            $dateTo = $request->date_to . '-01';
            $lastDay = date('Y-m-t', strtotime($dateTo));
            $query->where('created_at', '<=', $lastDay);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->whereHas('techTransfer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $assessments = $query->paginate(15)->withQueryString();

        $campuses = Campus::orderBy('name')->get(['id', 'name']);
        $colleges = College::with('campus:id,name')->orderBy('name')->get(['id', 'name', 'campus_id']);

        $statistics = [
            'total_assessments' => ImpactAssessment::where('is_archived', false)->count(),
            'assessments_by_month' => ImpactAssessment::where('is_archived', false)
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as period, COUNT(*) as count')
                ->groupBy('period')
                ->orderBy('period', 'desc')
                ->limit(12)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'assessments' => $assessments,
                'campuses' => $campuses,
                'colleges' => $colleges,
                'filters' => [
                    'campus_id' => $request->campus_id,
                    'college_id' => $request->college_id,
                    'search' => $request->search,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ],
                'statistics' => $statistics,
            ],
            'message' => 'Impact assessments report retrieved successfully'
        ], 200);
    }

    /**
     * Generate PDF for impact assessments report
     */
    public function impactAssessmentsPdf(Request $request)
    {
        $query = ImpactAssessment::with(['user', 'techTransfer.college', 'techTransfer.college.campus'])
            ->where('is_archived', false)
            ->where('status', 'approved');

        $user = $request->user();

        if ($user->role === 'user') {
            // Limit to user's own submissions
            $query->where('user_id', $user->id);
        }

        if ($request->filled('campus_id')) {
            $query->whereHas('techTransfer.college', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        if ($request->filled('college_id')) {
            $query->whereHas('techTransfer', function ($q) use ($request) {
                $q->where('college_id', $request->college_id);
            });
        }

        if ($request->filled('date_from')) {
            $dateFrom = $request->date_from . '-01';
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($request->filled('date_to')) {
            $dateTo = $request->date_to . '-01';
            $lastDay = date('Y-m-t', strtotime($dateTo));
            $query->where('created_at', '<=', $lastDay);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->whereHas('techTransfer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $assessments = $query->get();

        $filterInfo = [];
        if ($request->filled('campus_id')) {
            $campus = Campus::find($request->campus_id);
            $filterInfo['campus'] = $campus?->name;
        }
        if ($request->filled('college_id')) {
            $college = College::find($request->college_id);
            $filterInfo['college'] = $college?->name;
        }

        $filters = [
            'search' => $request->search,
            'campus_id' => $filterInfo['campus'] ?? null,
            'college_id' => $filterInfo['college'] ?? null,
            'sort_by' => $request->sort_by,
            'sort_order' => $request->sort_order ?? 'desc',
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];

        $statistics = [
            'total_assessments' => $assessments->count(),
        ];

        $pdf = Pdf::loadView('reports.impact-assessments-pdf', [
            'assessments' => $assessments,
            'filters' => $filters,
            'generated_at' => now('Asia/Manila')->format('F d, Y h:i A'),
            'generated_by' => $request->user()->first_name . ' ' . $request->user()->last_name ?? 'System',
            'statistics' => $statistics,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('impact-assessments-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Get modalities report data
     */
    public function modalities(Request $request): JsonResponse
    {
        $query = Modality::with(['user', 'techTransfer.college', 'techTransfer.college.campus'])
            ->where('is_archived', false)
            ->where('status', 'approved');

        if ($request->filled('campus_id')) {
            $query->whereHas('techTransfer.college', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        if ($request->filled('college_id')) {
            $query->whereHas('techTransfer', function ($q) use ($request) {
                $q->where('college_id', $request->college_id);
            });
        }

        if ($request->filled('date_from')) {
            $dateFrom = $request->date_from . '-01';
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($request->filled('date_to')) {
            $dateTo = $request->date_to . '-01';
            $lastDay = date('Y-m-t', strtotime($dateTo));
            $query->where('created_at', '<=', $lastDay);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('modality', 'like', "%{$search}%")
                    ->orWhere('partner_agency', 'like', "%{$search}%")
                    ->orWhere('hosted_by', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $modalities = $query->paginate(15)->withQueryString();

        $campuses = Campus::orderBy('name')->get(['id', 'name']);
        $colleges = College::with('campus:id,name')->orderBy('name')->get(['id', 'name', 'campus_id']);

        $statistics = [
            'total_modalities' => Modality::where('is_archived', false)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'modalities' => $modalities,
                'campuses' => $campuses,
                'colleges' => $colleges,
                'filters' => [
                    'campus_id' => $request->campus_id,
                    'college_id' => $request->college_id,
                    'search' => $request->search,
                    'sort_by' => $sortBy,
                    'sort_order' => $sortOrder,
                ],
                'statistics' => $statistics,
            ],
            'message' => 'Modalities report retrieved successfully'
        ], 200);
    }

    /**
     * Generate PDF for modalities report
     */
    public function modalitiesPdf(Request $request)
    {
        $query = Modality::with(['user', 'techTransfer.college', 'techTransfer.college.campus'])
            ->where('is_archived', false)
            ->where('status', 'approved');

        $user = $request->user();

        if ($user->role === 'user') {
            // Limit to user's own submissions
            $query->where('user_id', $user->id);
        }

        if ($request->filled('campus_id')) {
            $query->whereHas('techTransfer.college', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        if ($request->filled('college_id')) {
            $query->whereHas('techTransfer', function ($q) use ($request) {
                $q->where('college_id', $request->college_id);
            });
        }

        if ($request->filled('date_from')) {
            $dateFrom = $request->date_from . '-01';
            $query->where('created_at', '>=', $dateFrom);
        }
        if ($request->filled('date_to')) {
            $dateTo = $request->date_to . '-01';
            $lastDay = date('Y-m-t', strtotime($dateTo));
            $query->where('created_at', '<=', $lastDay);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('modality', 'like', "%{$search}%")
                    ->orWhere('partner_agency', 'like', "%{$search}%")
                    ->orWhere('hosted_by', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $modalities = $query->get();

        $filterInfo = [];
        if ($request->filled('campus_id')) {
            $campus = Campus::find($request->campus_id);
            $filterInfo['campus'] = $campus?->name;
        }
        if ($request->filled('college_id')) {
            $college = College::find($request->college_id);
            $filterInfo['college'] = $college?->name;
        }

        $filters = [
            'search' => $request->search,
            'campus_id' => $filterInfo['campus'] ?? null,
            'college_id' => $filterInfo['college'] ?? null,
            'sort_by' => $request->sort_by,
            'sort_order' => $request->sort_order ?? 'desc',
        ];

        $statistics = [
            'total_modalities' => $modalities->count(),
        ];

        $pdf = Pdf::loadView('reports.modalities-pdf', [
            'modalities' => $modalities,
            'filters' => $filters,
            'generated_at' => now('Asia/Manila')->format('F d, Y h:i A'),
            'generated_by' => $request->user()->first_name . ' ' . $request->user()->last_name ?? 'System',
            'statistics' => $statistics,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('modalities-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Get resolutions report data
     */
    public function resolutions(Request $request): JsonResponse
    {
        try {
            $query = Resolution::with(['user']);

            // Apply search filter
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%");
                });
            }

            // Date range filter
            if ($request->filled('date_from')) {
                $dateFrom = $request->date_from . '-01';
                $query->where('created_at', '>=', $dateFrom);
            }

            if ($request->filled('date_to')) {
                $dateTo = $request->date_to . '-01';
                $lastDay = date('Y-m-t', strtotime($dateTo));
                $query->where('created_at', '<=', $lastDay);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $resolutions = $query->paginate(15);

            // Calculate statistics
            $statistics = [
                'total' => Resolution::count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'resolutions' => $resolutions,
                    'statistics' => $statistics,
                ],
            ]);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Error in resolutions method: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching resolutions.',
            ], 500);
        }
    }

    /**
     * Generate resolutions report PDF
     */
    public function resolutionsPdf(Request $request)
    {
        $query = Resolution::with(['user']);

        // Apply same filters as the main report
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $dateFrom = $request->date_from . '-01';
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($request->filled('date_to')) {
            $dateTo = $request->date_to . '-01';
            $lastDay = date('Y-m-t', strtotime($dateTo));
            $query->where('created_at', '<=', $lastDay);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $resolutions = $query->get();

        $statistics = [
            'total' => $resolutions->count(),
        ];

        $pdf = PDF::loadView('reports.resolutions-pdf', [
            'resolutions' => $resolutions,
            'filters' => $request->all(),
            'generated_at' => now('Asia/Manila')->format('F d, Y h:i A'),
            'generated_by' => $request->user()->first_name . ' ' . $request->user()->last_name ?? 'System',
            'statistics' => $statistics,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('resolutions-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Get users report data
     */
    public function users(Request $request): JsonResponse
    {
        $query = \App\Models\User::with(['college', 'college.campus']);

        // Apply filters
        if ($request->filled('campus_id')) {
            $query->whereHas('college', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        if ($request->filled('college_id')) {
            $query->where('college_id', $request->college_id);
        }

        // User type filter
        if ($request->filled('user_type')) {
            if ($request->user_type === 'admin') {
                $query->where('role', 'admin');
            } elseif ($request->user_type === 'regular') {
                $query->where('role', 'user');
            }
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $users = $query->paginate(15);

        // Get campuses and colleges for filters
        $campuses = Campus::all();
        $colleges = College::all();

        // Calculate statistics
        $statistics = [
            'total' => \App\Models\User::count(),
            'admin' => \App\Models\User::where('role', 'admin')->count(),
            'regular' => \App\Models\User::where('role', 'user')->count(),
            'active' => \App\Models\User::where('is_active', true)->count(),
            'inactive' => \App\Models\User::where('is_active', false)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users,
                'campuses' => $campuses,
                'colleges' => $colleges,
                'statistics' => $statistics,
            ],
        ]);
    }

    /**
     * Generate users report PDF
     */
    public function usersPdf(Request $request)
    {
        $query = \App\Models\User::with(['college', 'college.campus']);

        // Apply same filters as the main report
        if ($request->filled('campus_id')) {
            $query->whereHas('college', function ($q) use ($request) {
                $q->where('campus_id', $request->campus_id);
            });
        }

        if ($request->filled('college_id')) {
            $query->where('college_id', $request->college_id);
        }

        if ($request->filled('user_type')) {
            if ($request->user_type === 'admin') {
                $query->where('role', 'admin');
            } elseif ($request->user_type === 'regular') {
                $query->where('role', 'user');
            }
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $users = $query->get();

        $statistics = [
            'total' => $users->count(),
            'admin' => $users->filter(fn($u) => $u->role === 'admin')->count(),
            'regular' => $users->filter(fn($u) => $u->role === 'user')->count(),
            'active' => $users->filter(fn($u) => $u->is_active)->count(),
            'inactive' => $users->filter(fn($u) => !$u->is_active)->count(),
        ];

        $pdf = PDF::loadView('reports.users-pdf', [
            'users' => $users,
            'filters' => $request->all(),
            'generated_at' => now('Asia/Manila')->format('F d, Y h:i A'),
            'generated_by' => $request->user()->first_name . ' ' . $request->user()->last_name ?? 'System',
            'statistics' => $statistics,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('users-report-' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Get audit trail report data
     */
    public function auditTrail(Request $request): JsonResponse
    {
        try {
            $query = AuditLog::with('user');

            // Filter by search in description
            if ($request->filled('search')) {
                $query->where('description', 'like', '%' . $request->search . '%');
            }

            // Filter by user if provided
            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by auditable_type if provided
            if ($request->filled('auditable_type') && $request->auditable_type !== 'all') {
                $query->where('model_type', $request->auditable_type);
            }

            // Filter by action if provided
            if ($request->filled('action') && $request->action !== 'all') {
                $query->where('action', $request->action);
            }

            // Filter by date range
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $auditLogs = $query->paginate($perPage)->withQueryString();

            // Calculate statistics
            $statistics = [
                'total' => AuditLog::count(),
                'created' => AuditLog::where('action', 'create')->count(),
                'updated' => AuditLog::where('action', 'update')->count(),
                'deleted' => AuditLog::where('action', 'delete')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'auditLogs' => $auditLogs,
                    'filters' => [
                        'search' => $request->search,
                        'user_id' => $request->user_id,
                        'auditable_type' => $request->auditable_type,
                        'action' => $request->action,
                        'date_from' => $request->date_from,
                        'date_to' => $request->date_to,
                        'sort_by' => $sortBy,
                        'sort_order' => $sortOrder,
                    ],
                    'statistics' => $statistics,
                ],
                'message' => 'Audit logs retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in auditTrail method: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve audit logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate PDF for audit trail report
     */
    public function auditTrailPdf(Request $request)
    {
        try {
            $query = AuditLog::with('user');

            // Apply filters
            if ($request->filled('search')) {
                $query->where('description', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('auditable_type') && $request->auditable_type !== 'all') {
                $query->where('model_type', $request->auditable_type);
            }

            if ($request->filled('action') && $request->action !== 'all') {
                $query->where('action', $request->action);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $auditLogs = $query->get();

            // Calculate statistics for summary box
            $actionCounts = $auditLogs->groupBy('action')->map->count()->toArray();
            $statistics = [
                'total' => $auditLogs->count(),
                'action_counts' => $actionCounts,
            ];

            $filters = [
                'search' => $request->search,
                'user_id' => $request->user_id,
                'auditable_type' => $request->auditable_type,
                'action' => $request->action,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'sort_by' => $request->sort_by,
                'sort_order' => $request->sort_order ?? 'desc',
            ];

            // Get user name for generated_by
            $user = $request->user();
            $generatedBy = $user ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : 'System';
            if (empty($generatedBy)) {
                $generatedBy = $user->email ?? 'System';
            }

            $pdf = Pdf::loadView('reports.audit-trail-pdf', [
                'auditLogs' => $auditLogs,
                'filters' => $filters,
                'statistics' => $statistics,
                'generated_at' => now('Asia/Manila')->format('F d, Y h:i A'),
                'generated_by' => $generatedBy,
            ]);

            $pdf->setPaper('a4', 'portrait');

            return $pdf->download('audit-trail-report-' . now()->format('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            Log::error('Error generating audit trail PDF: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
