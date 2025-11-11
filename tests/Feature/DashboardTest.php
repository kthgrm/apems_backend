<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Campus;
use App\Models\College;
use App\Models\TechTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that admin can view college stats for a campus.
     */
    public function test_admin_can_view_college_stats(): void
    {
        $admin = $this->authenticateAsAdmin();
        $campus = Campus::factory()->create();

        $response = $this->getJson("/api/dashboard/college-stats?campus_id={$campus->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    /**
     * Test that user can view their own dashboard.
     */
    public function test_user_can_view_own_dashboard(): void
    {
        $user = $this->authenticateAsUser();

        $response = $this->getJson('/api/user/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'userStats' => [
                    'total_projects',
                    'total_awards',
                    'total_engagements',
                ],
                'recentSubmissions' => [
                    'projects',
                    'awards',
                    'engagements',
                ],
            ]);
    }

    /**
     * Test that unauthenticated user cannot view admin dashboard stats.
     */
    public function test_unauthenticated_user_cannot_view_admin_dashboard_stats(): void
    {
        $response = $this->getJson('/api/dashboard/admin-stats');

        $response->assertStatus(401);
    }

    /**
     * Test that unauthenticated user cannot view user dashboard.
     */
    public function test_unauthenticated_user_cannot_view_user_dashboard(): void
    {
        $response = $this->getJson('/api/user/dashboard');

        $response->assertStatus(401);
    }
}
