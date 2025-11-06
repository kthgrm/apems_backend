<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TechTransfer;
use App\Models\Award;
use App\Models\Campus;
use App\Models\College;
use App\Models\Engagement;
use App\Models\ImpactAssessment;
use App\Models\Modality;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Get admin dashboard statistics
     */
    public function getAdminStats(Request $request): JsonResponse
    {
        $year = $request->get('year', date('Y'));
        $campusId = $request->get('campus_id');

        // Overall statistics
        $overallStats = [
            'total_projects' => $this->getFilteredCount(TechTransfer::class, $year, $campusId),
            'total_awards' => $this->getFilteredCount(Award::class, $year, $campusId),
            'total_engagements' => $this->getFilteredCount(Engagement::class, $year, $campusId),
        ];

        // Monthly statistics for the selected year
        $monthlyStats = $this->getMonthlyStats($year, $campusId);

        // Campus statistics
        $campusStats = $this->getCampusStats($year);

        // Get available years based on data
        $availableYears = $this->getAvailableYears();

        // Review statistics
        $reviewStats = $this->getReviewStats();

        return response()->json([
            'success' => true,
            'data' => [
                'overall_stats' => $overallStats,
                'monthly_stats' => $monthlyStats,
                'campus_stats' => $campusStats,
                'selected_year' => $year,
                'available_years' => $availableYears,
                'review_stats' => $reviewStats
            ],
            'message' => 'Dashboard statistics retrieved successfully'
        ], 200);
    }

    /**
     * Get filtered count for a model based on year and optional campus
     */
    private function getFilteredCount(string $modelClass, string $year, ?string $campusId = null): int
    {
        $query = $modelClass::where('is_archived', false)
            ->where('status', 'approved')
            ->whereYear('created_at', $year);

        if ($campusId) {
            // Get college IDs for the selected campus
            $campus = Campus::with('colleges')->find($campusId);
            if ($campus) {
                $collegeIds = $campus->colleges->pluck('id')->toArray();
                $query->whereIn('college_id', $collegeIds);
            }
        }

        return $query->count();
    }

    /**
     * Get monthly statistics for a specific year
     */
    private function getMonthlyStats(string $year, ?string $campusId = null): array
    {
        $months = [
            '01' => 'Jan',
            '02' => 'Feb',
            '03' => 'Mar',
            '04' => 'Apr',
            '05' => 'May',
            '06' => 'Jun',
            '07' => 'Jul',
            '08' => 'Aug',
            '09' => 'Sep',
            '10' => 'Oct',
            '11' => 'Nov',
            '12' => 'Dec'
        ];

        $monthlyData = [];
        $collegeIds = null;

        // Get college IDs if campus is selected
        if ($campusId) {
            $campus = Campus::with('colleges')->find($campusId);
            if ($campus) {
                $collegeIds = $campus->colleges->pluck('id')->toArray();
            }
        }

        foreach ($months as $monthNum => $monthName) {
            $startDate = "{$year}-{$monthNum}-01";
            $endDate = date('Y-m-t', strtotime($startDate));

            $projectsQuery = TechTransfer::where('is_archived', false)
                ->where('status', 'approved')
                ->whereBetween('created_at', [$startDate, $endDate]);

            $awardsQuery = Award::where('is_archived', false)
                ->where('status', 'approved')
                ->whereBetween('created_at', [$startDate, $endDate]);

            $engagementsQuery = Engagement::where('is_archived', false)
                ->where('status', 'approved')
                ->whereBetween('created_at', [$startDate, $endDate]);

            // Apply campus filter if provided
            if ($collegeIds) {
                $projectsQuery->whereIn('college_id', $collegeIds);
                $awardsQuery->whereIn('college_id', $collegeIds);
                $engagementsQuery->whereIn('college_id', $collegeIds);
            }

            $projects = $projectsQuery->count();
            $awards = $awardsQuery->count();
            $engagements = $engagementsQuery->count();

            $monthlyData[] = [
                'month' => $monthName,
                'projects' => $projects,
                'awards' => $awards,
                'engagements' => $engagements,
            ];
        }

        return $monthlyData;
    }

    /**
     * Get campus statistics for a specific year
     */
    private function getCampusStats(string $year): array
    {
        $campuses = Campus::with('colleges')->get();
        $campusStats = [];

        foreach ($campuses as $campus) {
            $collegeIds = $campus->colleges->pluck('id')->toArray();

            $totalProjects = TechTransfer::where('is_archived', false)
                ->where('status', 'approved')
                ->whereIn('college_id', $collegeIds)
                ->whereYear('created_at', $year)
                ->count();

            $totalAwards = Award::where('is_archived', false)
                ->where('status', 'approved')
                ->whereIn('college_id', $collegeIds)
                ->whereYear('created_at', $year)
                ->count();

            $totalEngagements = Engagement::where('is_archived', false)
                ->where('status', 'approved')
                ->whereIn('college_id', $collegeIds)
                ->whereYear('created_at', $year)
                ->count();

            $campusStats[] = [
                'id' => $campus->id,
                'name' => $campus->name,
                'total_colleges' => $campus->colleges->count(),
                'total_projects' => $totalProjects,
                'total_awards' => $totalAwards,
                'total_engagements' => $totalEngagements,
            ];
        }

        return $campusStats;
    }

    /**
     * Get available years based on existing data
     */
    private function getAvailableYears(): array
    {
        $years = [];

        // Get years from tech transfers
        $techTransferYears = TechTransfer::selectRaw('DISTINCT YEAR(created_at) as year')
            ->whereNotNull('created_at')
            ->pluck('year')
            ->toArray();

        // Get years from awards
        $awardYears = Award::selectRaw('DISTINCT YEAR(created_at) as year')
            ->whereNotNull('created_at')
            ->pluck('year')
            ->toArray();

        // Get years from engagements
        $engagementYears = Engagement::selectRaw('DISTINCT YEAR(created_at) as year')
            ->whereNotNull('created_at')
            ->pluck('year')
            ->toArray();

        // Merge and sort years
        $years = array_unique(array_merge($techTransferYears, $awardYears, $engagementYears));
        rsort($years);

        // If no data exists, return current year
        if (empty($years)) {
            $years = [date('Y')];
        }

        return array_values(array_map('strval', $years));
    }

    /**
     * Get review statistics
     */
    private function getReviewStats(): array
    {
        $totalTechTransferReviews = TechTransfer::where('status', 'pending')->where('is_archived', false)->count();
        $totalAwardReviews = Award::where('status', 'pending')->where('is_archived', false)->count();
        $totalEngagementReviews = Engagement::where('status', 'pending')->where('is_archived', false)->count();
        $totalModalityReviews = Modality::where('status', 'pending')->where('is_archived', false)->count();
        $totalImpactAssessmentReviews = ImpactAssessment::where('status', 'pending')->where('is_archived', false)->count();

        $totalReviews = $totalTechTransferReviews + $totalAwardReviews + $totalEngagementReviews + $totalModalityReviews + $totalImpactAssessmentReviews;

        return [
            'total' => $totalReviews,
            'tech_transfers' => $totalTechTransferReviews,
            'awards' => $totalAwardReviews,
            'engagements' => $totalEngagementReviews,
            'modalities' => $totalModalityReviews,
            'impact_assessments' => $totalImpactAssessmentReviews,
        ];
    }

    /**
     * Get college statistics for a specific campus
     */
    public function getCollegeStats(Request $request): JsonResponse
    {
        $year = $request->get('year', date('Y'));
        $campusId = $request->get('campus_id');

        if (!$campusId) {
            return response()->json([
                'success' => false,
                'message' => 'Campus ID is required'
            ], 400);
        }

        $campus = Campus::with('colleges')->find($campusId);

        if (!$campus) {
            return response()->json([
                'success' => false,
                'message' => 'Campus not found'
            ], 404);
        }

        $collegeStats = [];

        foreach ($campus->colleges as $college) {
            $totalProjects = TechTransfer::where('is_archived', false)
                ->where('status', 'approved')
                ->where('college_id', $college->id)
                ->whereYear('created_at', $year)
                ->count();

            $totalAwards = Award::where('is_archived', false)
                ->where('status', 'approved')
                ->where('college_id', $college->id)
                ->whereYear('created_at', $year)
                ->count();

            $totalEngagements = Engagement::where('is_archived', false)
                ->where('status', 'approved')
                ->where('college_id', $college->id)
                ->whereYear('created_at', $year)
                ->count();

            $collegeStats[] = [
                'id' => $college->id,
                'name' => $college->name,
                'code' => $college->code,
                'total_projects' => $totalProjects,
                'total_awards' => $totalAwards,
                'total_engagements' => $totalEngagements,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'campus_name' => $campus->name,
                'college_stats' => $collegeStats,
                'selected_year' => $year
            ],
            'message' => 'College statistics retrieved successfully'
        ], 200);
    }
}
