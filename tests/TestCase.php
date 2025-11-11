<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Models\User;
use App\Models\College;
use Laravel\Sanctum\Sanctum;

abstract class TestCase extends BaseTestCase
{

    /**
     * Create and authenticate a user with admin privileges.
     *
     * @param array $attributes
     * @return User
     */
    protected function authenticateAsAdmin(array $attributes = []): User
    {
        // Ensure college_id is set if not provided
        if (!isset($attributes['college_id'])) {
            $college = College::factory()->create();
            $attributes['college_id'] = $college->id;
        }

        $user = User::factory()->create(array_merge([
            'role' => 'admin',
            'is_active' => true,
        ], $attributes));

        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * Create and authenticate a regular user.
     *
     * @param array $attributes
     * @return User
     */
    protected function authenticateAsUser(array $attributes = []): User
    {
        // Ensure college_id is set if not provided
        if (!isset($attributes['college_id'])) {
            $college = College::factory()->create();
            $attributes['college_id'] = $college->id;
        }

        $user = User::factory()->create(array_merge([
            'role' => 'user',
            'is_active' => true,
        ], $attributes));

        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * Create a user without authenticating.
     *
     * @param array $attributes
     * @return User
     */
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /**
     * Assert that the response has a successful JSON structure.
     *
     * @param \Illuminate\Testing\TestResponse $response
     * @param string $message
     * @return void
     */
    protected function assertSuccessResponse($response, string $message = null): void
    {
        $response->assertStatus(200);

        if ($message) {
            $response->assertJson([
                'message' => $message,
            ]);
        }
    }

    /**
     * Assert that the response has an error JSON structure.
     *
     * @param \Illuminate\Testing\TestResponse $response
     * @param int $status
     * @param string $message
     * @return void
     */
    protected function assertErrorResponse($response, int $status = 422, string $message = null): void
    {
        $response->assertStatus($status);

        if ($message) {
            $response->assertJson([
                'message' => $message,
            ]);
        }
    }
}
