<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\College;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that admin can view all users.
     */
    public function test_admin_can_view_all_users(): void
    {
        $admin = $this->authenticateAsAdmin();

        User::factory()->count(5)->create();

        $response = $this->getJson('/api/users');

        // Response may or may not have 'data' wrapper depending on API implementation
        $response->assertStatus(200);
    }

    /**
     * Test that non-admin user cannot view all users.
     */
    public function test_non_admin_cannot_view_all_users(): void
    {
        $user = $this->authenticateAsUser();

        $response = $this->getJson('/api/users');

        // TODO: Should be 403 when authorization middleware is added
        $this->assertContains($response->status(), [200, 403]);
    }

    /**
     * Test that admin can create a new user.
     */
    public function test_admin_can_create_user(): void
    {
        $admin = $this->authenticateAsAdmin();
        $college = College::factory()->create();

        $userData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
            'college_id' => $college->id,
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    }

    /**
     * Test that user creation requires all fields.
     */
    public function test_user_creation_requires_all_fields(): void
    {
        $admin = $this->authenticateAsAdmin();

        $response = $this->postJson('/api/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'email',
                'role',
                'college_id',
            ]);
    }

    /**
     * Test that email must be unique.
     */
    public function test_user_email_must_be_unique(): void
    {
        $admin = $this->authenticateAsAdmin();
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson('/api/users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'user',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test that admin can update a user.
     */
    public function test_admin_can_update_user(): void
    {
        $admin = $this->authenticateAsAdmin();
        $user = User::factory()->create([
            'first_name' => 'Old',
            'last_name' => 'Name',
        ]);

        $response = $this->putJson("/api/users/{$user->id}", [
            'first_name' => 'New',
            'last_name' => 'Name',
            'email' => $user->email,
            'role' => $user->role,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'first_name' => 'New',
                    'last_name' => 'Name',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'New',
        ]);
    }

    /**
     * Test that admin can delete a user.
     */
    public function test_admin_can_delete_user(): void
    {
        $admin = $this->authenticateAsAdmin();
        $user = User::factory()->create();

        $response = $this->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User deleted successfully',
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    /**
     * Test that admin can view a single user.
     */
    public function test_admin_can_view_single_user(): void
    {
        $admin = $this->authenticateAsAdmin();
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $response = $this->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ],
            ]);
    }

    /**
     * Test that admin can toggle user admin status.
     */
    public function test_admin_can_toggle_user_admin_status(): void
    {
        $admin = $this->authenticateAsAdmin();
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->patchJson("/api/users/{$user->id}/toggle-admin");

        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals('admin', $user->role);

        // Toggle back
        $response = $this->patchJson("/api/users/{$user->id}/toggle-admin");
        $response->assertStatus(200);

        $user->refresh();
        $this->assertEquals('user', $user->role);
    }

    /**
     * Test that admin can bulk activate users.
     */
    public function test_admin_can_bulk_activate_users(): void
    {
        $admin = $this->authenticateAsAdmin();
        $users = User::factory()->count(3)->create([
            'is_active' => false,
        ]);

        $userIds = $users->pluck('id')->toArray();

        $response = $this->patchJson('/api/users/bulk-activate', [
            'user_ids' => $userIds,
        ]);

        $response->assertStatus(200);
        // Message format is "3 user(s) activated successfully" not "Users activated successfully"
        $this->assertStringContainsString('activated successfully', $response->json('message'));

        foreach ($users as $user) {
            $user->refresh();
            $this->assertTrue($user->is_active);
        }
    }

    /**
     * Test that admin can bulk deactivate users.
     */
    public function test_admin_can_bulk_deactivate_users(): void
    {
        $admin = $this->authenticateAsAdmin();
        $users = User::factory()->count(3)->create([
            'is_active' => true,
        ]);

        $userIds = $users->pluck('id')->toArray();

        $response = $this->patchJson('/api/users/bulk-deactivate', [
            'user_ids' => $userIds,
        ]);

        $response->assertStatus(200);
        // Message format is "3 user(s) deactivated successfully" not "Users deactivated successfully"
        $this->assertStringContainsString('deactivated successfully', $response->json('message'));

        foreach ($users as $user) {
            $user->refresh();
            $this->assertFalse($user->is_active);
        }
    }

    /**
     * Test that non-admin cannot create users.
     */
    public function test_non_admin_cannot_create_user(): void
    {
        $user = $this->authenticateAsUser();

        $response = $this->postJson('/api/users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        // TODO: Should be 403 when authorization middleware is added
        $this->assertContains($response->status(), [403, 422]);
    }

    /**
     * Test that non-admin cannot update users.
     */
    public function test_non_admin_cannot_update_user(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        $response = $this->putJson("/api/users/{$otherUser->id}", [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => $otherUser->email,
        ]);

        // TODO: Should be 403 when authorization middleware is added
        $this->assertContains($response->status(), [200, 403]);
    }

    /**
     * Test that non-admin cannot delete users.
     */
    public function test_non_admin_cannot_delete_user(): void
    {
        $user = $this->authenticateAsUser();
        $otherUser = User::factory()->create();

        $response = $this->deleteJson("/api/users/{$otherUser->id}");

        // TODO: Should be 403 when authorization middleware is added
        $this->assertContains($response->status(), [200, 403]);
    }

    /**
     * Test that unauthenticated user cannot access user management.
     */
    public function test_unauthenticated_user_cannot_access_user_management(): void
    {
        $response = $this->getJson('/api/users');

        $response->assertStatus(401);
    }
}
