<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Award;
use App\Models\Engagement;
use App\Models\TechTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get user's statistics
        $userStats = [
            'total_projects' => TechTransfer::where('user_id', $user->id)->where('is_archived', false)->count(),
            'total_awards' => Award::where('user_id', $user->id)->where('is_archived', false)->count(),
            'total_engagements' => Engagement::where('user_id', $user->id)->where('is_archived', false)->count(),
        ];

        // Get recent submissions from other users (excluding current user)
        $recentSubmissions = [
            'projects' => TechTransfer::with(['user', 'college.campus'])
                ->where('user_id', '!=', $user->id)
                ->where('is_archived', false)
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                        'type' => 'Project',
                        'user_name' => $project->user->first_name . ' ' . $project->user->last_name ?? 'Unknown User',
                        'campus' => $project->college?->campus?->name ?? 'N/A',
                        'college' => $project->college?->name ?? 'N/A',
                        'created_at' => $project->created_at,
                        'description' => $project->description
                    ];
                }),
            'awards' => Award::with(['user', 'college.campus'])
                ->where('user_id', '!=', $user->id)
                ->where('is_archived', false)
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($award) {
                    return [
                        'id' => $award->id,
                        'name' => $award->award_name,
                        'type' => 'Award',
                        'user_name' => $award->user->name ?? 'Unknown User',
                        'campus' => $award->college?->campus?->name ?? 'N/A',
                        'college' => $award->college?->name ?? 'N/A',
                        'created_at' => $award->created_at,
                        'description' => $award->description ?? '',
                        'date_received' => $award->date_received
                    ];
                }),
            'engagements' => Engagement::with(['user', 'college.campus'])
                ->where('user_id', '!=', $user->id)
                ->where('is_archived', false)
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($engagement) {
                    return [
                        'id' => $engagement->id,
                        'name' => $engagement->agency_partner,
                        'type' => 'Engagement',
                        'user_name' => $engagement->user->name ?? 'Unknown User',
                        'campus' => $engagement->college?->campus?->name ?? 'N/A',
                        'college' => $engagement->college?->name ?? 'N/A',
                        'created_at' => $engagement->created_at,
                        'description' => $engagement->activity_conducted ?? '',
                        'location' => $engagement->location ?? ''
                    ];
                })
        ];

        return response()->json([
            'userStats' => $userStats,
            'recentSubmissions' => $recentSubmissions,
        ]);
    }
}
