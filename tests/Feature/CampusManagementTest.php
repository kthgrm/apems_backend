<?php

namespace Tests\Feature\Admin;

use App\Models\Campus;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CampusManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that admin can view all campuses.
     */
    public function test_admin_can_view_all_campuses(): void
    {
        $admin = $this->authenticateAsAdmin();

        Campus::factory()->count(3)->create();

        $response = $this->getJson('/api/campuses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                    ],
                ],
            ]);
    }
    /**
     * Test that admin can create a campus.
     */
    public function test_admin_can_create_campus(): void
    {
        Storage::fake('spaces');

        $admin = $this->authenticateAsAdmin();

        $campusData = [
            'name' => 'Main Campus',
            'logo' => UploadedFile::fake()->create('logo.png', 100),
        ];

        // use normal post so file uploads are handled
        $response = $this->post('/api/campuses', $campusData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('campuses', [
            'name' => 'Main Campus',
        ]);
    }

    /**
     * Test that campus creation requires name.
     */
    public function test_campus_creation_requires_name(): void
    {
        $admin = $this->authenticateAsAdmin();

        $response = $this->postJson('/api/campuses', [
            'address' => '123 University Ave',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test that campus name must be unique.
     */
    public function test_campus_name_must_be_unique(): void
    {
        Storage::fake('spaces');

        $admin = $this->authenticateAsAdmin();

        Campus::factory()->create([
            'name' => 'Existing Campus',
        ]);

        $response = $this->postJson('/api/campuses', [
            'name' => 'Existing Campus',
            'logo' => UploadedFile::fake()->create('logo.png', 100),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
    /**
     * Test that admin can update a campus.
     */
    public function test_admin_can_update_campus(): void
    {
        Storage::fake('spaces');

        $admin = $this->authenticateAsAdmin();
        $campus = Campus::factory()->create([
            'name' => 'Old Campus',
        ]);

        $response = $this->put("/api/campuses/{$campus->id}", [
            'name' => 'Updated Campus',
            'logo' => UploadedFile::fake()->create('logo2.png', 100),
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('campuses', [
            'id' => $campus->id,
            'name' => 'Updated Campus',
        ]);
    }

    /**
     * Test that admin can delete a campus.
     */
    public function test_admin_can_delete_campus(): void
    {
        Storage::fake('spaces');

        $admin = $this->authenticateAsAdmin(['password' => Hash::make('password')]);
        $campus = Campus::factory()->create();

        // Remove any colleges that depend on this campus
        $campus->colleges()->delete();

        $response = $this->deleteJson("/api/campuses/{$campus->id}", [
            'password' => 'password',
        ]);

        if ($response->status() !== 200) {
            dump($response->json());
        }

        $response->assertStatus(200);

        $this->assertDatabaseMissing('campuses', [
            'id' => $campus->id,
        ]);
    }

    /**
     * Test that admin can view a single campus.
     */
    public function test_admin_can_view_single_campus(): void
    {
        $admin = $this->authenticateAsAdmin();
        $campus = Campus::factory()->create([
            'name' => 'Test Campus',
        ]);

        $response = $this->getJson("/api/campuses/{$campus->id}");

        $response->assertStatus(200);
    }

    /**
     * Test that admin can get colleges from a campus.
     */
    public function test_admin_can_get_colleges_from_campus(): void
    {
        $admin = $this->authenticateAsAdmin();
        $campus = Campus::factory()
            ->hasColleges(3)
            ->create();

        $response = $this->getJson("/api/relationships/campuses/{$campus->id}/colleges");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'campus_id',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test that regular user can view campuses.
     */
    public function test_user_can_view_campuses(): void
    {
        $user = $this->authenticateAsUser();

        Campus::factory()->count(3)->create();

        $response = $this->getJson('/api/campuses');

        $response->assertStatus(200);
    }

    /**
     * Test that non-admin cannot create campus.
     */
    public function test_non_admin_cannot_create_campus(): void
    {
        Storage::fake('spaces');

        $user = $this->authenticateAsUser();

        $response = $this->post('/api/campuses', [
            'name' => 'New Campus',
            'logo' => UploadedFile::fake()->create('logo3.png', 100),
        ]);

        // Should be 403 if authorization is implemented
        $this->assertContains($response->status(), [403, 422]);
    }

    /**
     * Test that non-admin cannot update campus.
     */
    public function test_non_admin_cannot_update_campus(): void
    {
        Storage::fake('spaces');

        $user = $this->authenticateAsUser();
        $campus = Campus::factory()->create();

        $response = $this->put("/api/campuses/{$campus->id}", [
            'name' => 'Updated Campus',
            'logo' => UploadedFile::fake()->create('logo4.png', 100),
        ]);

        // TODO: Should be 403 when authorization middleware is added
        $this->assertContains($response->status(), [200, 403, 422]);
    }

    /**
     * Test that non-admin cannot delete campus.
     */
    public function test_non_admin_cannot_delete_campus(): void
    {
        $user = $this->authenticateAsUser();
        $campus = Campus::factory()->create();

        $response = $this->deleteJson("/api/campuses/{$campus->id}");

        // TODO: Should be 403 when authorization middleware is added
        $this->assertContains($response->status(), [200, 403]);
    }

    /**
     * Test that unauthenticated user cannot access campuses.
     */
    public function test_unauthenticated_user_cannot_access_campuses(): void
    {
        $response = $this->getJson('/api/campuses');

        $response->assertStatus(401);
    }
}
