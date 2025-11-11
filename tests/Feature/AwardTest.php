<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Award;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AwardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that admin can view all awards.
     */
    public function test_admin_can_view_all_awards(): void
    {
        $admin = $this->authenticateAsAdmin();

        Award::factory()->count(5)->create(['status' => 'approved', 'is_archived' => false]);
        Award::factory()->count(2)->create(['status' => 'pending']);

        $response = $this->getJson('/api/awards');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'award_name',
                        'user_id',
                        'status',
                    ],
                ],
            ]);
        // Admin should see only approved awards
        $this->assertCount(5, $response->json('data'));
    }

    /**
     * Test that non-admin user can only see their own approved awards.
     */
    public function test_user_can_only_see_own_approved_awards(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        // Create awards for authenticated user
        Award::factory()->count(2)->create([
            'user_id' => $user->id,
            'college_id' => $user->college_id,
            'status' => 'approved',
            'is_archived' => false,
        ]);

        // Create awards for other user
        Award::factory()->count(3)->create([
            'user_id' => $otherUser->id,
            'college_id' => $otherUser->college_id,
            'status' => 'approved',
            'is_archived' => false,
        ]);

        $response = $this->getJson('/api/awards');

        $response->assertStatus(200);
        // User should only see their own 2 awards
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * Test that admin can view a single award.
     */
    public function test_admin_can_view_single_award(): void
    {
        $admin = $this->authenticateAsAdmin();
        $award = Award::factory()->create([
            'award_name' => 'Test Award',
        ]);

        $response = $this->getJson("/api/awards/{$award->id}");

        $response->assertStatus(200);
    }

    /**
     * Test that user cannot view another user's award.
     */
    public function test_user_cannot_view_another_users_award(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        $award = Award::factory()->create([
            'user_id' => $otherUser->id,
            'college_id' => $otherUser->college_id,
        ]);

        $response = $this->getJson("/api/awards/{$award->id}");

        $response->assertStatus(403);
    }

    /**
     * Test that archived award returns 404.
     */
    public function test_archived_award_returns_404(): void
    {
        $user = $this->authenticateAsUser();

        $award = Award::factory()->create([
            'user_id' => $user->id,
            'college_id' => $user->college_id,
            'is_archived' => true,
        ]);

        $response = $this->getJson("/api/awards/{$award->id}");

        $response->assertStatus(404);
    }

    /**
     * Test that user can create an award.
     */
    public function test_user_can_create_award(): void
    {
        Storage::fake('spaces');

        $user = $this->authenticateAsUser();

        $awardData = [
            'award_name' => 'Best Research Award',
            'description' => 'Award for outstanding research',
            'date_received' => '2024-01-15',
            'event_details' => 'Annual Research Conference',
            'location' => 'Manila',
            'awarding_body' => 'Research Council',
            'people_involved' => 'Dr. John Doe, Dr. Jane Smith',
        ];

        $response = $this->postJson('/api/awards', $awardData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('awards', [
            'award_name' => 'Best Research Award',
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test that award creation requires required fields.
     */
    public function test_award_creation_requires_required_fields(): void
    {
        $user = $this->authenticateAsUser();

        $response = $this->postJson('/api/awards', [
            'description' => 'Some description',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['award_name', 'date_received', 'event_details', 'location', 'awarding_body', 'people_involved']);
    }

    /**
     * Test that user can update their own award.
     */
    public function test_user_can_update_own_award(): void
    {
        $user = $this->authenticateAsUser();
        $award = Award::factory()->create([
            'user_id' => $user->id,
            'college_id' => $user->college_id,
            'award_name' => 'Old Title',
        ]);

        $response = $this->putJson("/api/awards/{$award->id}", [
            'award_name' => 'Updated Title',
            'description' => $award->description,
            'date_received' => $award->date_received->format('Y-m-d'),
            'event_details' => $award->event_details,
            'location' => $award->location,
            'awarding_body' => $award->awarding_body,
            'people_involved' => $award->people_involved,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('awards', [
            'id' => $award->id,
            'award_name' => 'Updated Title',
        ]);
    }

    /**
     * Test that admin can update any award.
     */
    public function test_admin_can_update_any_award(): void
    {
        $admin = $this->authenticateAsAdmin();
        $otherUser = User::factory()->create();

        $award = Award::factory()->create([
            'user_id' => $otherUser->id,
            'college_id' => $otherUser->college_id,
            'award_name' => 'Original Title',
        ]);

        $response = $this->putJson("/api/awards/{$award->id}", [
            'award_name' => 'Admin Updated Title',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('awards', [
            'id' => $award->id,
            'award_name' => 'Admin Updated Title',
        ]);
    }

    /**
     * Test that user cannot update another user's award.
     */
    public function test_user_cannot_update_another_users_award(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        $award = Award::factory()->create([
            'user_id' => $otherUser->id,
            'college_id' => $otherUser->college_id,
        ]);

        $response = $this->putJson("/api/awards/{$award->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test that archived award cannot be updated.
     */
    public function test_cannot_update_archived_award(): void
    {
        $user = $this->authenticateAsUser();

        $award = Award::factory()->create([
            'user_id' => $user->id,
            'college_id' => $user->college_id,
            'is_archived' => true,
        ]);

        $response = $this->putJson("/api/awards/{$award->id}", [
            'award_name' => 'New Title',
        ]);

        $response->assertStatus(404);
    }

    /**
     * Test that admin can archive an award.
     */
    public function test_admin_can_archive_award(): void
    {
        $admin = $this->authenticateAsAdmin(['password' => Hash::make('password')]);
        $award = Award::factory()->create([
            'is_archived' => false,
        ]);

        // archive requires current password
        $response = $this->patchJson("/api/awards/{$award->id}/archive", [
            'password' => 'password',
        ]);

        $response->assertStatus(200);

        $award->refresh();
        $this->assertTrue($award->is_archived);
    }

    /**
     * Test that archive requires password.
     */
    public function test_archive_requires_password(): void
    {
        $user = $this->authenticateAsUser();
        $award = Award::factory()->create([
            'user_id' => $user->id,
            'college_id' => $user->college_id,
            'is_archived' => false,
        ]);

        $response = $this->patchJson("/api/awards/{$award->id}/archive", []);

        $response->assertStatus(422);
    }

    /**
     * Test that archive requires correct password.
     */
    public function test_archive_requires_correct_password(): void
    {
        $user = $this->authenticateAsUser(['password' => Hash::make('secret')]);
        $award = Award::factory()->create([
            'user_id' => $user->id,
            'college_id' => $user->college_id,
            'is_archived' => false,
        ]);

        $response = $this->patchJson("/api/awards/{$award->id}/archive", [
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test that user can archive their own award.
     */
    public function test_user_can_archive_own_award(): void
    {
        $user = $this->authenticateAsUser(['password' => Hash::make('secret')]);
        $award = Award::factory()->create([
            'user_id' => $user->id,
            'college_id' => $user->college_id,
            'is_archived' => false,
        ]);

        $response = $this->patchJson("/api/awards/{$award->id}/archive", [
            'password' => 'secret',
        ]);

        $response->assertStatus(200);

        $award->refresh();
        $this->assertTrue($award->is_archived);
    }

    /**
     * Test that user cannot archive another user's award.
     */
    public function test_user_cannot_archive_another_users_award(): void
    {
        $user = $this->authenticateAsUser(['password' => Hash::make('secret')]);
        $otherUser = User::factory()->create();

        $award = Award::factory()->create([
            'user_id' => $otherUser->id,
            'college_id' => $otherUser->college_id,
            'is_archived' => false,
        ]);

        $response = $this->patchJson("/api/awards/{$award->id}/archive", [
            'password' => 'secret',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test that user can view their own awards.
     */
    public function test_user_can_view_own_awards(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        // Create awards for the authenticated user
        Award::factory()->count(3)->create([
            'user_id' => $user->id,
            'college_id' => $user->college_id,
            'is_archived' => false,
        ]);

        // Create awards for another user
        Award::factory()->count(2)->create([
            'user_id' => $otherUser->id,
            'college_id' => $otherUser->college_id,
            'is_archived' => false,
        ]);

        $response = $this->getJson('/api/user/awards');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'success',
                'data',
                'stats' => [
                    'total',
                    'approved',
                    'pending',
                    'rejected',
                ],
                'message',
            ]);
    }

    /**
     * Test that unauthenticated user cannot access awards.
     */
    public function test_unauthenticated_user_cannot_access_awards(): void
    {
        $response = $this->getJson('/api/awards');

        $response->assertStatus(401);
    }
}
