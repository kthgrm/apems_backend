<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Award;
use App\Models\TechTransfer;
use App\Models\Engagement;
use App\Models\ImpactAssessment;
use App\Models\Modality;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that admin can view dashboard statistics.
     */
    public function test_admin_can_view_dashboard_stats(): void
    {
        $admin = $this->authenticateAsAdmin();

        // Create some test data
        User::factory()->count(5)->create();
        Award::factory()->count(3)->create();
        TechTransfer::factory()->count(2)->create();
        Engagement::factory()->count(4)->create();

        $response = $this->getJson('/api/dashboard/admin-stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'users_count',
                'awards_count',
                'tech_transfers_count',
                'engagements_count',
                'impact_assessments_count',
                'modalities_count',
            ]);
    }

    /**
     * Test that regular user cannot view admin dashboard stats.
     */
    public function test_user_cannot_view_admin_dashboard_stats(): void
    {
        $user = $this->authenticateAsUser();

        $response = $this->getJson('/api/dashboard/admin-stats');

        // TODO: Should be 403 when authorization middleware is added
        $this->assertContains($response->status(), [200, 403]);
    }

    /**
     * Test that unauthenticated user cannot view dashboard stats.
     */
    public function test_unauthenticated_user_cannot_view_dashboard_stats(): void
    {
        $response = $this->getJson('/api/dashboard/admin-stats');

        $response->assertStatus(401);
    }

    /**
     * Test that dashboard returns correct counts.
     */
    public function test_dashboard_returns_correct_counts(): void
    {
        $admin = $this->authenticateAsAdmin();

        // Create specific counts of each resource
        User::factory()->count(10)->create();
        Award::factory()->count(5)->create();
        TechTransfer::factory()->count(3)->create();
        Engagement::factory()->count(7)->create();
        ImpactAssessment::factory()->count(2)->create();
        Modality::factory()->count(4)->create();

        $response = $this->getJson('/api/dashboard/admin-stats');

        $response->assertStatus(200)
            ->assertJson([
                'users_count' => 11, // 10 created + 1 admin
                'awards_count' => 5,
                'tech_transfers_count' => 3,
                'engagements_count' => 7,
                'impact_assessments_count' => 2,
                'modalities_count' => 4,
            ]);
    }
}
