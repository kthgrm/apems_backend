<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Resolution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResolutionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that admin can view all resolutions.
     */
    public function test_admin_can_view_all_resolutions(): void
    {
        $admin = $this->authenticateAsAdmin();

        Resolution::factory()->count(5)->create(['is_archived' => false]);
        Resolution::factory()->count(2)->create(['is_archived' => true]);

        $response = $this->getJson('/api/resolutions');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
    }

    /**
     * Test that non-admin cannot view resolutions.
     */
    public function test_user_cannot_view_resolutions(): void
    {
        $user = $this->authenticateAsUser();

        Resolution::factory()->count(3)->create();

        $response = $this->getJson('/api/resolutions');

        $response->assertStatus(403);
    }

    /**
     * Test that admin can create a resolution.
     */
    public function test_admin_can_create_resolution(): void
    {
        Storage::fake('spaces');

        $admin = $this->authenticateAsAdmin();

        $file = UploadedFile::fake()->create('resolution.pdf', 100);

        $response = $this->postJson('/api/resolutions', [
            'attachments' => [$file],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('resolutions', [
            'user_id' => $admin->id,
        ]);
    }

    /**
     * Test that admin can view single resolution.
     */
    public function test_admin_can_view_single_resolution(): void
    {
        $admin = $this->authenticateAsAdmin();
        $resolution = Resolution::factory()->create();

        $response = $this->getJson("/api/resolutions/{$resolution->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $resolution->id);
    }

    /**
     * Test that admin can archive a resolution with password.
     */
    public function test_admin_can_archive_resolution(): void
    {
        $admin = $this->authenticateAsAdmin(['password' => Hash::make('secret')]);
        $resolution = Resolution::factory()->create([
            'user_id' => $admin->id,
            'is_archived' => false,
        ]);

        $response = $this->patchJson("/api/resolutions/{$resolution->id}/archive", [
            'password' => 'secret',
        ]);

        $response->assertStatus(200);

        $resolution->refresh();
        $this->assertTrue($resolution->is_archived);
    }

    /**
     * Test that archive requires password.
     */
    public function test_archive_requires_password(): void
    {
        $admin = $this->authenticateAsAdmin();
        $resolution = Resolution::factory()->create([
            'user_id' => $admin->id,
            'is_archived' => false,
        ]);

        $response = $this->patchJson("/api/resolutions/{$resolution->id}/archive", []);

        $response->assertStatus(422);
    }

    /**
     * Test that archive requires correct password.
     */
    public function test_archive_requires_correct_password(): void
    {
        $admin = $this->authenticateAsAdmin(['password' => Hash::make('secret')]);
        $resolution = Resolution::factory()->create([
            'user_id' => $admin->id,
            'is_archived' => false,
        ]);

        $response = $this->patchJson("/api/resolutions/{$resolution->id}/archive", [
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test that unauthenticated user cannot access resolutions.
     */
    public function test_unauthenticated_user_cannot_access_resolutions(): void
    {
        $response = $this->getJson('/api/resolutions');

        $response->assertStatus(401);
    }
}
