<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that password reset email can be requested.
     */
    public function test_password_reset_link_can_be_requested(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'We have emailed your password reset link.',
            ]);
    }

    /**
     * Test that password reset link request fails for non-existent email.
     */
    public function test_password_reset_link_fails_for_nonexistent_email(): void
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test that email is required for password reset request.
     */
    public function test_password_reset_requires_email(): void
    {
        $response = $this->postJson('/api/forgot-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test that email must be valid format for password reset.
     */
    public function test_password_reset_requires_valid_email_format(): void
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test that password can be reset with valid token.
     */
    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token' => $token,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Your password has been reset.',
            ]);

        // Verify password was actually changed
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /**
     * Test that password reset fails with invalid token.
     */
    public function test_password_reset_fails_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token' => 'invalid-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test that password reset requires all fields.
     */
    public function test_password_reset_requires_all_fields(): void
    {
        $response = $this->postJson('/api/reset-password', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password', 'token']);
    }

    /**
     * Test that password must be confirmed.
     */
    public function test_password_reset_requires_password_confirmation(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'different-password',
            'token' => $token,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test that password must meet minimum length.
     */
    public function test_password_reset_requires_minimum_length(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
            'token' => $token,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test that user can login with new password after reset.
     */
    public function test_user_can_login_with_new_password_after_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('oldpassword'),
            'is_active' => true,
        ]);

        $token = Password::createToken($user);

        // Reset password
        $this->postJson('/api/reset-password', [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token' => $token,
        ])->assertStatus(200);

        // Try to login with new password
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['user', 'token']);
    }

    /**
     * Test that old password doesn't work after reset.
     */
    public function test_old_password_does_not_work_after_reset(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('oldpassword'),
            'is_active' => true,
        ]);

        $token = Password::createToken($user);

        // Reset password
        $this->postJson('/api/reset-password', [
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'token' => $token,
        ])->assertStatus(200);

        // Try to login with old password
        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'oldpassword',
        ]);

        $response->assertStatus(401);
    }
}
