<?php

namespace Tests\Feature\User;

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
     * Test that user can view their own dashboard.
     */
    public function test_user_can_view_own_dashboard(): void
    {
        $user = $this->authenticateAsUser();

        // Create some submissions for this user
        Award::factory()->count(2)->create(['user_id' => $user->id]);
        TechTransfer::factory()->count(1)->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/user/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'awards_count',
                'tech_transfers_count',
                'engagements_count',
                'impact_assessments_count',
                'modalities_count',
            ]);
    }

    /**
     * Test that dashboard shows only user's own data.
     */
    public function test_dashboard_shows_only_user_data(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        // Create submissions for this user
        Award::factory()->count(3)->create(['user_id' => $user->id]);

        // Create submissions for another user
        Award::factory()->count(5)->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/user/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'awards_count' => 3, // Should only see their own
            ]);
    }

    /**
     * Test that unauthenticated user cannot access dashboard.
     */
    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $response = $this->getJson('/api/user/dashboard');

        $response->assertStatus(401);
    }

    /**
     * Test that dashboard returns zero counts for new users.
     */
    public function test_dashboard_returns_zero_counts_for_new_users(): void
    {
        $user = $this->authenticateAsUser();

        $response = $this->getJson('/api/user/dashboard');

        $response->assertStatus(200)
            ->assertJson([
                'awards_count' => 0,
                'tech_transfers_count' => 0,
                'engagements_count' => 0,
                'impact_assessments_count' => 0,
                'modalities_count' => 0,
            ]);
    }

    /**
     * Test that dashboard includes recent submissions if available.
     */
    public function test_dashboard_includes_recent_submissions(): void
    {
        $user = $this->authenticateAsUser();

        Award::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/user/dashboard');

        $response->assertStatus(200);

        // Check if recent_awards exists in response
        if (isset($response->json()['recent_awards'])) {
            $response->assertJsonStructure([
                'recent_awards' => [
                    '*' => ['id', 'title'],
                ],
            ]);
        }
    }
}
