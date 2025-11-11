<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\ImpactAssessment;
use App\Models\TechTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImpactAssessmentTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that admin can view all impact assessments.
     */
    public function test_admin_can_view_all_impact_assessments(): void
    {
        $admin = $this->authenticateAsAdmin();

        ImpactAssessment::factory()->count(5)->create(['status' => 'approved', 'is_archived' => false]);
        ImpactAssessment::factory()->count(2)->create(['status' => 'pending']);

        $response = $this->getJson('/api/impact-assessments');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    /**
     * Test that user can only see own approved impact assessments.
     */
    public function test_user_can_only_see_own_approved_impact_assessments(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        ImpactAssessment::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'approved',
            'is_archived' => false,
        ]);

        ImpactAssessment::factory()->count(3)->create([
            'user_id' => $otherUser->id,
            'status' => 'approved',
            'is_archived' => false,
        ]);

        $response = $this->getJson('/api/impact-assessments');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * Test that user can create an impact assessment.
     */
    public function test_user_can_create_impact_assessment(): void
    {
        Storage::fake('spaces');

        $user = $this->authenticateAsUser();
        $techTransfer = TechTransfer::factory()->create([
            'user_id' => $user->id,
            'college_id' => $user->college_id,
        ]);

        $assessmentData = [
            'tech_transfer_id' => $techTransfer->id,
            'title' => 'Impact Study 2024',
            'description' => 'This is a comprehensive impact assessment of the technology transfer program.',
        ];

        $response = $this->postJson('/api/impact-assessments', $assessmentData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('impact_assessments', [
            'title' => 'Impact Study 2024',
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test that impact assessment creation requires required fields.
     */
    public function test_impact_assessment_creation_requires_required_fields(): void
    {
        $user = $this->authenticateAsUser();

        $response = $this->postJson('/api/impact-assessments', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tech_transfer_id', 'title', 'description']);
    }

    /**
     * Test that user can update own impact assessment.
     */
    public function test_user_can_update_own_impact_assessment(): void
    {
        $user = $this->authenticateAsUser();
        $assessment = ImpactAssessment::factory()->create([
            'user_id' => $user->id,
            'title' => 'Old Title',
        ]);

        $response = $this->putJson("/api/impact-assessments/{$assessment->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('impact_assessments', [
            'id' => $assessment->id,
            'title' => 'Updated Title',
        ]);
    }

    /**
     * Test that user cannot update another user's impact assessment.
     */
    public function test_user_cannot_update_another_users_impact_assessment(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        $assessment = ImpactAssessment::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->putJson("/api/impact-assessments/{$assessment->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test that archived impact assessment cannot be updated.
     */
    public function test_cannot_update_archived_impact_assessment(): void
    {
        $user = $this->authenticateAsUser();

        $assessment = ImpactAssessment::factory()->create([
            'user_id' => $user->id,
            'is_archived' => true,
        ]);

        $response = $this->putJson("/api/impact-assessments/{$assessment->id}", [
            'title' => 'New Title',
        ]);

        $response->assertStatus(404);
    }

    /**
     * Test that user can archive own impact assessment.
     */
    public function test_user_can_archive_own_impact_assessment(): void
    {
        $user = $this->authenticateAsUser(['password' => Hash::make('secret')]);
        $assessment = ImpactAssessment::factory()->create([
            'user_id' => $user->id,
            'is_archived' => false,
        ]);

        $response = $this->patchJson("/api/impact-assessments/{$assessment->id}/archive", [
            'password' => 'secret',
        ]);

        $response->assertStatus(200);

        $assessment->refresh();
        $this->assertTrue($assessment->is_archived);
    }

    /**
     * Test that archive requires password.
     */
    public function test_archive_requires_password(): void
    {
        $user = $this->authenticateAsUser();
        $assessment = ImpactAssessment::factory()->create([
            'user_id' => $user->id,
            'is_archived' => false,
        ]);

        $response = $this->patchJson("/api/impact-assessments/{$assessment->id}/archive", []);

        $response->assertStatus(422);
    }

    /**
     * Test that user can view their own impact assessments.
     */
    public function test_user_can_view_own_impact_assessments(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        ImpactAssessment::factory()->count(3)->create([
            'user_id' => $user->id,
            'is_archived' => false,
        ]);

        ImpactAssessment::factory()->count(2)->create([
            'user_id' => $otherUser->id,
            'is_archived' => false,
        ]);

        $response = $this->getJson('/api/user/impact-assessments');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test that unauthenticated user cannot access impact assessments.
     */
    public function test_unauthenticated_user_cannot_access_impact_assessments(): void
    {
        $response = $this->getJson('/api/impact-assessments');

        $response->assertStatus(401);
    }
}
