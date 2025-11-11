<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Engagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EngagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that admin can view all engagements.
     */
    public function test_admin_can_view_all_engagements(): void
    {
        $admin = $this->authenticateAsAdmin();

        Engagement::factory()->count(5)->create(['status' => 'approved', 'is_archived' => false]);
        Engagement::factory()->count(2)->create(['status' => 'pending']);

        $response = $this->getJson('/api/engagements');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'agency_partner',
                        'user_id',
                        'status',
                    ],
                ],
            ]);
        // Admin should see only approved engagements
        $this->assertCount(5, $response->json('data'));
    }

    /**
     * Test that non-admin user can only see their own approved engagements.
     */
    public function test_user_can_only_see_own_approved_engagements(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        // Create engagements for authenticated user
        Engagement::factory()->count(2)->create([
            'user_id' => $user->id,
            'college_id' => $user->college_id,
            'status' => 'approved',
            'is_archived' => false,
        ]);

        // Create engagements for other user
        Engagement::factory()->count(3)->create([
            'user_id' => $otherUser->id,
            'college_id' => $otherUser->college_id,
            'status' => 'approved',
            'is_archived' => false,
        ]);

        $response = $this->getJson('/api/engagements');

        $response->assertStatus(200);
        // User should only see their own 2 engagements
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * Test that admin can view a single engagement.
     */
    public function test_admin_can_view_single_engagement(): void
    {
        $admin = $this->authenticateAsAdmin();
        $engagement = Engagement::factory()->create([
            'agency_partner' => 'Test Partner',
        ]);

        $response = $this->getJson("/api/engagements/{$engagement->id}");

        $response->assertStatus(200);
    }

    /**
     * Test that user cannot view another user's engagement.
     */
    public function test_user_cannot_view_another_users_engagement(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        $engagement = Engagement::factory()->create([
            'user_id' => $otherUser->id,
            'college_id' => $otherUser->college_id,
        ]);

        $response = $this->getJson("/api/engagements/{$engagement->id}");

        $response->assertStatus(403);
    }

    /**
     * Test that archived engagement returns 404.
     */
    public function test_archived_engagement_returns_404(): void
    {
        $user = $this->authenticateAsUser();

        $engagement = Engagement::factory()->create([
            'user_id' => $user->id,
            'college_id' => $user->college_id,
            'is_archived' => true,
        ]);

        $response = $this->getJson("/api/engagements/{$engagement->id}");

        $response->assertStatus(404);
    }

    /**
     * Test that user can create an engagement.
     */
    public function test_user_can_create_engagement(): void
    {
        Storage::fake('spaces');

        $user = $this->authenticateAsUser();

        $engagementData = [
            'agency_partner' => 'Partner Agency',
            'location' => 'Manila',
            'activity_conducted' => 'Training Program',
            'start_date' => '2024-01-15',
            'end_date' => '2024-01-20',
            'number_of_participants' => 50,
            'faculty_involved' => 'Dr. John Doe',
            'narrative' => 'This is a test engagement narrative.',
        ];

        $response = $this->postJson('/api/engagements', $engagementData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('engagements', [
            'agency_partner' => 'Partner Agency',
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test that engagement creation requires required fields.
     */
    public function test_engagement_creation_requires_required_fields(): void
    {
        $user = $this->authenticateAsUser();

        $response = $this->postJson('/api/engagements', [
            'location' => 'Some location',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['agency_partner', 'activity_conducted', 'start_date', 'end_date', 'number_of_participants', 'faculty_involved', 'narrative']);
    }

    /**
     * Test that user can update their own engagement.
     */
    public function test_user_can_update_own_engagement(): void
    {
        $user = $this->authenticateAsUser();
        $engagement = Engagement::factory()->create([
            'user_id' => $user->id,
            'college_id' => $user->college_id,
            'agency_partner' => 'Old Partner',
        ]);

        $response = $this->putJson("/api/engagements/{$engagement->id}", [
            'agency_partner' => 'Updated Partner',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('engagements', [
            'id' => $engagement->id,
            'agency_partner' => 'Updated Partner',
        ]);
    }

    /**
     * Test that user cannot update another user's engagement.
     */
    public function test_user_cannot_update_another_users_engagement(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        $engagement = Engagement::factory()->create([
            'user_id' => $otherUser->id,
            'college_id' => $otherUser->college_id,
        ]);

        $response = $this->putJson("/api/engagements/{$engagement->id}", [
            'agency_partner' => 'Hacked Partner',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test that archived engagement cannot be updated.
     */
    public function test_cannot_update_archived_engagement(): void
    {
        $user = $this->authenticateAsUser();

        $engagement = Engagement::factory()->create([
            'user_id' => $user->id,
            'college_id' => $user->college_id,
            'is_archived' => true,
        ]);

        $response = $this->putJson("/api/engagements/{$engagement->id}", [
            'agency_partner' => 'New Partner',
        ]);

        $response->assertStatus(404);
    }

    /**
     * Test that admin can archive an engagement.
     */
    public function test_admin_can_archive_engagement(): void
    {
        $admin = $this->authenticateAsAdmin(['password' => Hash::make('password')]);
        $engagement = Engagement::factory()->create([
            'is_archived' => false,
        ]);

        $response = $this->patchJson("/api/engagements/{$engagement->id}/archive", [
            'password' => 'password',
        ]);

        $response->assertStatus(200);

        $engagement->refresh();
        $this->assertTrue($engagement->is_archived);
    }

    /**
     * Test that archive requires password.
     */
    public function test_archive_requires_password(): void
    {
        $user = $this->authenticateAsUser();
        $engagement = Engagement::factory()->create([
            'user_id' => $user->id,
            'college_id' => $user->college_id,
            'is_archived' => false,
        ]);

        $response = $this->patchJson("/api/engagements/{$engagement->id}/archive", []);

        $response->assertStatus(422);
    }

    /**
     * Test that archive requires correct password.
     */
    public function test_archive_requires_correct_password(): void
    {
        $user = $this->authenticateAsUser(['password' => Hash::make('secret')]);
        $engagement = Engagement::factory()->create([
            'user_id' => $user->id,
            'college_id' => $user->college_id,
            'is_archived' => false,
        ]);

        $response = $this->patchJson("/api/engagements/{$engagement->id}/archive", [
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test that user can archive their own engagement.
     */
    public function test_user_can_archive_own_engagement(): void
    {
        $user = $this->authenticateAsUser(['password' => Hash::make('secret')]);
        $engagement = Engagement::factory()->create([
            'user_id' => $user->id,
            'college_id' => $user->college_id,
            'is_archived' => false,
        ]);

        $response = $this->patchJson("/api/engagements/{$engagement->id}/archive", [
            'password' => 'secret',
        ]);

        $response->assertStatus(200);

        $engagement->refresh();
        $this->assertTrue($engagement->is_archived);
    }

    /**
     * Test that user cannot archive another user's engagement.
     */
    public function test_user_cannot_archive_another_users_engagement(): void
    {
        $user = $this->authenticateAsUser(['password' => Hash::make('secret')]);
        $otherUser = User::factory()->create();

        $engagement = Engagement::factory()->create([
            'user_id' => $otherUser->id,
            'college_id' => $otherUser->college_id,
            'is_archived' => false,
        ]);

        $response = $this->patchJson("/api/engagements/{$engagement->id}/archive", [
            'password' => 'secret',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test that user can view their own engagements.
     */
    public function test_user_can_view_own_engagements(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        // Create engagements for the authenticated user
        Engagement::factory()->count(3)->create([
            'user_id' => $user->id,
            'college_id' => $user->college_id,
            'is_archived' => false,
        ]);

        // Create engagements for another user
        Engagement::factory()->count(2)->create([
            'user_id' => $otherUser->id,
            'college_id' => $otherUser->college_id,
            'is_archived' => false,
        ]);

        $response = $this->getJson('/api/user/engagements');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'data',
                'message',
            ]);
    }

    /**
     * Test that unauthenticated user cannot access engagements.
     */
    public function test_unauthenticated_user_cannot_access_engagements(): void
    {
        $response = $this->getJson('/api/engagements');

        $response->assertStatus(401);
    }
}
