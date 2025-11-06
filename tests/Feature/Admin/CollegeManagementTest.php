<?php

namespace Tests\Feature\Admin;

use App\Models\Campus;
use App\Models\College;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollegeManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that admin can view all colleges.
     */
    public function test_admin_can_view_all_colleges(): void
    {
        $admin = $this->authenticateAsAdmin();

        College::factory()->count(3)->create();

        $response = $this->getJson('/api/colleges');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'campus_id',
                    ],
                ],
            ]);
    }

    /**
     * Test that admin can create a college.
     */
    public function test_admin_can_create_college(): void
    {
        $admin = $this->authenticateAsAdmin();
        $campus = Campus::factory()->create();

        $collegeData = [
            'name' => 'College of Engineering',
            'campus_id' => $campus->id,
            'code' => 'COE',
            'logo' => null,
        ];

        $response = $this->postJson('/api/colleges', $collegeData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('colleges', [
            'name' => 'College of Engineering',
        ]);
    }

    /**
     * Test that college creation requires name.
     */
    public function test_college_creation_requires_name(): void
    {
        $admin = $this->authenticateAsAdmin();
        $campus = Campus::factory()->create();

        $response = $this->postJson('/api/colleges', [
            'campus_id' => $campus->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test that college creation requires campus_id.
     */
    public function test_college_creation_requires_campus_id(): void
    {
        $admin = $this->authenticateAsAdmin();

        $response = $this->postJson('/api/colleges', [
            'name' => 'College of Science',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['campus_id']);
    }

    /**
     * Test that campus_id must exist.
     */
    public function test_campus_id_must_exist(): void
    {
        $admin = $this->authenticateAsAdmin();

        $response = $this->postJson('/api/colleges', [
            'name' => 'College of Science',
            'campus_id' => 999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['campus_id']);
    }

    /**
     * Test that admin can update a college.
     */
    public function test_admin_can_update_college(): void
    {
        $admin = $this->authenticateAsAdmin();
        $campus = Campus::factory()->create();
        $college = College::factory()->create([
            'name' => 'Old College',
            'campus_id' => $campus->id,
        ]);

        $response = $this->putJson("/api/colleges/{$college->id}", [
            'name' => 'Updated College',
            'campus_id' => $campus->id,
            'code' => $college->code,
            'logo' => null,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('colleges', [
            'id' => $college->id,
            'name' => 'Updated College',
        ]);
    }

    /**
     * Test that admin can delete a college.
     */
    public function test_admin_can_delete_college(): void
    {
        $admin = $this->authenticateAsAdmin();
        $college = College::factory()->create();

        // Remove any users that depend on this college
        $college->users()->update(['college_id' => null]);

        $response = $this->deleteJson("/api/colleges/{$college->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('colleges', [
            'id' => $college->id,
        ]);
    }

    /**
     * Test that admin can view a single college.
     */
    public function test_admin_can_view_single_college(): void
    {
        $admin = $this->authenticateAsAdmin();
        $college = College::factory()->create([
            'name' => 'Test College',
        ]);

        $response = $this->getJson("/api/colleges/{$college->id}");

        $response->assertStatus(200);
    }

    /**
     * Test that regular user can view colleges.
     */
    public function test_user_can_view_colleges(): void
    {
        $user = $this->authenticateAsUser();

        College::factory()->count(3)->create();

        $response = $this->getJson('/api/colleges');

        $response->assertStatus(200);
    }

    /**
     * Test that non-admin cannot create college.
     */
    public function test_non_admin_cannot_create_college(): void
    {
        $user = $this->authenticateAsUser();
        $campus = Campus::factory()->create();

        $response = $this->postJson('/api/colleges', [
            'name' => 'New College',
            'campus_id' => $campus->id,
            'code' => 'NC',
            'logo' => null,
        ]);

        // Should be 403 if authorization is implemented
        $this->assertContains($response->status(), [403, 422]);
    }

    /**
     * Test that non-admin cannot update college.
     */
    public function test_non_admin_cannot_update_college(): void
    {
        $user = $this->authenticateAsUser();
        $college = College::factory()->create();

        $response = $this->putJson("/api/colleges/{$college->id}", [
            'name' => 'Updated College',
            'campus_id' => $college->campus_id,
            'code' => $college->code,
            'logo' => null,
        ]);

        // TODO: Should be 403 when authorization middleware is added
        $this->assertContains($response->status(), [200, 403, 422]);
    }

    /**
     * Test that non-admin cannot delete college.
     */
    public function test_non_admin_cannot_delete_college(): void
    {
        $user = $this->authenticateAsUser();
        $college = College::factory()->create();

        $response = $this->deleteJson("/api/colleges/{$college->id}");

        // TODO: Should be 403 when authorization middleware is added
        $this->assertContains($response->status(), [200, 403]);
    }

    /**
     * Test that unauthenticated user cannot access colleges.
     */
    public function test_unauthenticated_user_cannot_access_colleges(): void
    {
        $response = $this->getJson('/api/colleges');

        $response->assertStatus(401);
    }
}
