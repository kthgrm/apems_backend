<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TechTransfer;
use App\Models\Award;
use App\Models\IntlPartner;
use App\Models\Campus;
use App\Models\College;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get admin dashboard statistics
     */
    public function getAdminStats(Request $request): JsonResponse
    {
        $year = $request->get('year', date('Y'));

        // Overall statistics
        $overallStats = [
            'total_users' => User::count(),
            'total_projects' => TechTransfer::where('is_archived', false)->count(),
            'total_awards' => Award::where('is_archived', false)->count(),
            'total_international_partners' => IntlPartner::where('is_archived', false)->count(),
            'total_campuses' => Campus::count(),
            'total_colleges' => College::count(),
        ];

        // Monthly statistics for the selected year
        $monthlyStats = $this->getMonthlyStats($year);

        // Campus statistics
        $campusStats = $this->getCampusStats($year);

        // Get available years based on data
        $availableYears = $this->getAvailableYears();

        return response()->json([
            'success' => true,
            'data' => [
                'overall_stats' => $overallStats,
                'monthly_stats' => $monthlyStats,
                'campus_stats' => $campusStats,
                'selected_year' => $year,
                'available_years' => $availableYears,
            ],
            'message' => 'Dashboard statistics retrieved successfully'
        ], 200);
    }

    /**
     * Get monthly statistics for a specific year
     */
    private function getMonthlyStats(string $year): array
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

        foreach ($months as $monthNum => $monthName) {
            $startDate = "{$year}-{$monthNum}-01";
            $endDate = date('Y-m-t', strtotime($startDate));

            $projects = TechTransfer::where('is_archived', false)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $awards = Award::where('is_archived', false)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $partners = IntlPartner::where('is_archived', false)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();

            $monthlyData[] = [
                'month' => $monthName,
                'projects' => $projects,
                'awards' => $awards,
                'partners' => $partners,
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
                ->whereIn('college_id', $collegeIds)
                ->whereYear('created_at', $year)
                ->count();

            $totalAwards = Award::where('is_archived', false)
                ->whereIn('college_id', $collegeIds)
                ->whereYear('created_at', $year)
                ->count();

            $totalPartners = IntlPartner::where('is_archived', false)
                ->whereIn('college_id', $collegeIds)
                ->whereYear('created_at', $year)
                ->count();

            $campusStats[] = [
                'id' => $campus->id,
                'name' => $campus->name,
                'total_colleges' => $campus->colleges->count(),
                'total_projects' => $totalProjects,
                'total_awards' => $totalAwards,
                'total_partners' => $totalPartners,
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

        // Get years from partners
        $partnerYears = IntlPartner::selectRaw('DISTINCT YEAR(created_at) as year')
            ->whereNotNull('created_at')
            ->pluck('year')
            ->toArray();

        // Merge and sort years
        $years = array_unique(array_merge($techTransferYears, $awardYears, $partnerYears));
        rsort($years);

        // If no data exists, return current year
        if (empty($years)) {
            $years = [date('Y')];
        }

        return array_values(array_map('strval', $years));
    }
}
