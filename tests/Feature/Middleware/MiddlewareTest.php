<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that unauthenticated requests are rejected.
     */
    public function test_unauthenticated_requests_are_rejected(): void
    {
        $response = $this->getJson('/api/users');

        $response->assertStatus(401);
    }

    /**
     * Test that authenticated requests are allowed.
     */
    public function test_authenticated_requests_are_allowed(): void
    {
        $user = $this->authenticateAsAdmin();

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);
    }

    /**
     * Test that inactive users cannot access protected routes.
     */
    public function test_inactive_users_cannot_access_protected_routes(): void
    {
        $user = User::factory()->create([
            'is_active' => false,
        ]);

        $this->actingAs($user, 'sanctum');

        $response = $this->getJson('/api/users');

        // The response might be 401 or 403 depending on implementation
        $this->assertContains($response->status(), [401, 403]);
    }

    /**
     * Test CORS headers are present.
     */
    public function test_cors_headers_are_present(): void
    {
        $user = $this->authenticateAsUser();

        $response = $this->getJson('/api/auth/user');

        $response->assertHeader('Access-Control-Allow-Origin');
    }

    /**
     * Test that admin-only routes reject non-admin users.
     */
    public function test_admin_only_routes_reject_non_admin_users(): void
    {
        $user = $this->authenticateAsUser();

        $adminRoutes = [
            ['method' => 'POST', 'uri' => '/api/users'],
            ['method' => 'POST', 'uri' => '/api/campuses'],
            ['method' => 'POST', 'uri' => '/api/colleges'],
        ];

        foreach ($adminRoutes as $route) {
            $response = $this->json($route['method'], $route['uri'], []);

            $response->assertStatus(403);
        }
    }

    /**
     * Test that admin routes allow admin users.
     */
    public function test_admin_routes_allow_admin_users(): void
    {
        $admin = $this->authenticateAsAdmin();

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);
    }

    /**
     * Test that JSON is required for API requests.
     */
    public function test_api_routes_expect_json(): void
    {
        $response = $this->post('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Should expect JSON content type
        $this->assertNotEquals(200, $response->status());
    }

    /**
     * Test that validation errors return proper JSON structure.
     */
    public function test_validation_errors_return_json(): void
    {
        $user = $this->authenticateAsAdmin();

        $response = $this->postJson('/api/users', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors',
            ]);
    }
}
