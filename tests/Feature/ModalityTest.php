<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Modality;
use App\Models\TechTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModalityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that admin can view all modalities.
     */
    public function test_admin_can_view_all_modalities(): void
    {
        $admin = $this->authenticateAsAdmin();

        Modality::factory()->count(5)->create(['status' => 'approved', 'is_archived' => false]);
        Modality::factory()->count(2)->create(['status' => 'pending']);

        $response = $this->getJson('/api/modalities');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    /**
     * Test that user can only see own approved modalities.
     */
    public function test_user_can_only_see_own_approved_modalities(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        Modality::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'is_archived' => false,
        ]);

        Modality::factory()->count(3)->create([
            'user_id' => $otherUser->id,
            'status' => 'approved',
            'is_archived' => false,
        ]);

        $response = $this->getJson('/api/modalities');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * Test that user can create a modality.
     */
    public function test_user_can_create_modality(): void
    {
        $user = $this->authenticateAsUser();
        $techTransfer = TechTransfer::factory()->create([
            'user_id' => $user->id,
            'college_id' => $user->college_id,
        ]);

        $modalityData = [
            'tech_transfer_id' => $techTransfer->id,
            'modality' => 'TV',
            'tv_channel' => 'Channel 7',
            'period' => 'Q1 2024',
            'partner_agency' => 'Media Partner Inc',
            'hosted_by' => 'Dr. Smith',
        ];

        $response = $this->postJson('/api/modalities', $modalityData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('modalities', [
            'modality' => 'TV',
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test that modality creation requires required fields.
     */
    public function test_modality_creation_requires_required_fields(): void
    {
        $user = $this->authenticateAsUser();

        $response = $this->postJson('/api/modalities', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tech_transfer_id', 'modality', 'period', 'partner_agency', 'hosted_by']);
    }

    /**
     * Test that user can update own modality.
     */
    public function test_user_can_update_own_modality(): void
    {
        $user = $this->authenticateAsUser();
        $modality = Modality::factory()->create([
            'user_id' => $user->id,
            'modality' => 'TV',
        ]);

        $response = $this->putJson("/api/modalities/{$modality->id}", [
            'modality' => 'Radio',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('modalities', [
            'id' => $modality->id,
            'modality' => 'Radio',
        ]);
    }

    /**
     * Test that user cannot update another user's modality.
     */
    public function test_user_cannot_update_another_users_modality(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        $modality = Modality::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->putJson("/api/modalities/{$modality->id}", [
            'modality' => 'Hacked',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test that archived modality cannot be updated.
     */
    public function test_cannot_update_archived_modality(): void
    {
        $user = $this->authenticateAsUser();

        $modality = Modality::factory()->create([
            'user_id' => $user->id,
            'is_archived' => true,
        ]);

        $response = $this->putJson("/api/modalities/{$modality->id}", [
            'modality' => 'New',
        ]);

        $response->assertStatus(404);
    }

    /**
     * Test that user can archive own modality.
     */
    public function test_user_can_archive_own_modality(): void
    {
        $user = $this->authenticateAsUser(['password' => Hash::make('secret')]);
        $modality = Modality::factory()->create([
            'user_id' => $user->id,
            'is_archived' => false,
        ]);

        $response = $this->patchJson("/api/modalities/{$modality->id}/archive", [
            'password' => 'secret',
        ]);

        $response->assertStatus(200);

        $modality->refresh();
        $this->assertTrue($modality->is_archived);
    }

    /**
     * Test that archive requires password.
     */
    public function test_archive_requires_password(): void
    {
        $user = $this->authenticateAsUser();
        $modality = Modality::factory()->create([
            'user_id' => $user->id,
            'is_archived' => false,
        ]);

        $response = $this->patchJson("/api/modalities/{$modality->id}/archive", []);

        $response->assertStatus(422);
    }

    /**
     * Test that user can view their own modalities.
     */
    public function test_user_can_view_own_modalities(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        Modality::factory()->count(3)->create([
            'user_id' => $user->id,
            'is_archived' => false,
        ]);

        Modality::factory()->count(2)->create([
            'user_id' => $otherUser->id,
            'is_archived' => false,
        ]);

        $response = $this->getJson('/api/user/modalities');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test that unauthenticated user cannot access modalities.
     */
    public function test_unauthenticated_user_cannot_access_modalities(): void
    {
        $response = $this->getJson('/api/modalities');

        $response->assertStatus(401);
    }
}
