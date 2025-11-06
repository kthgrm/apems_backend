<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Award;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        Award::factory()->count(5)->create();

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
     * Test that user can create an award.
     */
    public function test_user_can_create_award(): void
    {
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
     * Test that user cannot update another user's award.
     */
    public function test_user_cannot_update_another_users_award(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        $award = Award::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->putJson("/api/awards/{$award->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test that user can delete their own award.
     */
    public function test_user_can_delete_own_award(): void
    {
        $user = $this->authenticateAsUser();
        $award = Award::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->deleteJson("/api/awards/{$award->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('awards', [
            'id' => $award->id,
        ]);
    }

    /**
     * Test that user cannot delete another user's award.
     */
    public function test_user_cannot_delete_another_users_award(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        $award = Award::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->deleteJson("/api/awards/{$award->id}");

        $response->assertStatus(403);
    }

    /**
     * Test that admin can archive an award.
     */
    public function test_admin_can_archive_award(): void
    {
        $admin = $this->authenticateAsAdmin();
        $award = Award::factory()->create([
            'is_archived' => false,
        ]);

        $response = $this->patchJson("/api/awards/{$award->id}/archive");

        $response->assertStatus(200);

        $award->refresh();
        $this->assertTrue($award->is_archived);
    }

    /**
     * Test that user can view their own awards.
     */
    public function test_user_can_view_own_awards(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        // Create awards for the authenticated user
        Award::factory()->count(3)->create(['user_id' => $user->id]);

        // Create awards for another user
        Award::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $response = $this->getJson('/api/user/awards');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test that unauthenticated user cannot access awards.
     */
    public function test_unauthenticated_user_cannot_access_awards(): void
    {
        $response = $this->getJson('/api/awards');

        $response->assertStatus(401);
    }

    /**
     * Test that awards are filtered correctly.
     */
    public function test_awards_can_be_filtered(): void
    {
        $admin = $this->authenticateAsAdmin();

        Award::factory()->create(['status' => 'pending']);
        Award::factory()->create(['status' => 'approved']);
        Award::factory()->create(['status' => 'rejected']);

        $response = $this->getJson('/api/awards?status=pending');

        $response->assertStatus(200);

        if (isset($response->json()['data'])) {
            $statuses = collect($response->json()['data'])->pluck('status')->unique();

            if ($statuses->count() > 0) {
                $this->assertTrue($statuses->every(fn($status) => $status === 'pending'));
            }
        }
    }
}
