# Quick Reference: Testing API-based Laravel Backend

## Test File Template

```php
<?php

namespace Tests\Feature\Admin; // or Tests\Feature\User

use App\Models\User;
use App\Models\YourModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YourModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_description_of_what_is_being_tested(): void
    {
        // Arrange: Set up test data
        $admin = $this->authenticateAsAdmin();
        $model = YourModel::factory()->create();

        // Act: Perform the action
        $response = $this->getJson('/api/your-models');

        // Assert: Check the results
        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['*' => ['id', 'name']]]);
    }
}
```

## Authentication Helpers

```php
// Authenticate as admin
$admin = $this->authenticateAsAdmin();
$admin = $this->authenticateAsAdmin(['email' => 'custom@email.com']);

// Authenticate as regular user
$user = $this->authenticateAsUser();
$user = $this->authenticateAsUser(['college_id' => 1]);

// Create user without authenticating
$user = $this->createUser(['role' => 'user']);
```

## HTTP Methods

```php
// GET request
$response = $this->getJson('/api/resource');

// POST request
$response = $this->postJson('/api/resource', ['key' => 'value']);

// PUT request (full update)
$response = $this->putJson('/api/resource/1', ['key' => 'value']);

// PATCH request (partial update)
$response = $this->patchJson('/api/resource/1', ['key' => 'value']);

// DELETE request
$response = $this->deleteJson('/api/resource/1');
```

## Common Assertions

```php
// Status codes
$response->assertStatus(200);  // OK
$response->assertStatus(201);  // Created
$response->assertStatus(401);  // Unauthorized
$response->assertStatus(403);  // Forbidden
$response->assertStatus(404);  // Not Found
$response->assertStatus(422);  // Validation Error

// JSON structure
$response->assertJsonStructure([
    'data' => [
        '*' => ['id', 'name', 'email']
    ]
]);

// JSON content
$response->assertJson([
    'data' => [
        'name' => 'John Doe'
    ]
]);

// JSON count
$response->assertJsonCount(5, 'data');

// Validation errors
$response->assertJsonValidationErrors(['email', 'password']);

// Database assertions
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);
$this->assertDatabaseMissing('users', ['id' => 999]);
$this->assertDatabaseCount('users', 5);
```

## Testing Patterns

### CRUD Operations

```php
// CREATE
public function test_can_create_resource(): void
{
    $user = $this->authenticateAsUser();

    $response = $this->postJson('/api/resources', [
        'name' => 'Test Resource'
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('resources', ['name' => 'Test Resource']);
}

// READ (List)
public function test_can_list_resources(): void
{
    $user = $this->authenticateAsUser();
    Resource::factory()->count(3)->create();

    $response = $this->getJson('/api/resources');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
}

// READ (Single)
public function test_can_view_single_resource(): void
{
    $user = $this->authenticateAsUser();
    $resource = Resource::factory()->create();

    $response = $this->getJson("/api/resources/{$resource->id}");

    $response->assertStatus(200)
        ->assertJson(['data' => ['id' => $resource->id]]);
}

// UPDATE
public function test_can_update_resource(): void
{
    $user = $this->authenticateAsUser();
    $resource = Resource::factory()->create(['name' => 'Old']);

    $response = $this->putJson("/api/resources/{$resource->id}", [
        'name' => 'New'
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('resources', [
        'id' => $resource->id,
        'name' => 'New'
    ]);
}

// DELETE
public function test_can_delete_resource(): void
{
    $user = $this->authenticateAsUser();
    $resource = Resource::factory()->create();

    $response = $this->deleteJson("/api/resources/{$resource->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('resources', ['id' => $resource->id]);
}
```

### Authorization Tests

```php
// Test admin access
public function test_admin_can_access(): void
{
    $admin = $this->authenticateAsAdmin();
    $response = $this->getJson('/api/admin-only-route');
    $response->assertStatus(200);
}

// Test user cannot access
public function test_user_cannot_access(): void
{
    $user = $this->authenticateAsUser();
    $response = $this->getJson('/api/admin-only-route');
    $response->assertStatus(403);
}

// Test unauthenticated cannot access
public function test_guest_cannot_access(): void
{
    $response = $this->getJson('/api/protected-route');
    $response->assertStatus(401);
}
```

### Validation Tests

```php
// Test required fields
public function test_validation_requires_fields(): void
{
    $user = $this->authenticateAsUser();

    $response = $this->postJson('/api/resources', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email']);
}

// Test unique constraint
public function test_validation_unique_constraint(): void
{
    $user = $this->authenticateAsUser();
    Resource::factory()->create(['email' => 'test@example.com']);

    $response = $this->postJson('/api/resources', [
        'email' => 'test@example.com'
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
}

// Test format validation
public function test_validation_email_format(): void
{
    $user = $this->authenticateAsUser();

    $response = $this->postJson('/api/resources', [
        'email' => 'invalid-email'
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
}
```

### Owner-based Access

```php
public function test_user_can_only_access_own_resources(): void
{
    $user = $this->authenticateAsUser();
    $otherUser = User::factory()->create();

    // User's own resources
    Resource::factory()->count(3)->create(['user_id' => $user->id]);

    // Another user's resources
    Resource::factory()->count(2)->create(['user_id' => $otherUser->id]);

    $response = $this->getJson('/api/user/resources');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
}

public function test_user_cannot_update_others_resource(): void
{
    $user = $this->authenticateAsUser();
    $otherUser = User::factory()->create();

    $resource = Resource::factory()->create(['user_id' => $otherUser->id]);

    $response = $this->putJson("/api/resources/{$resource->id}", [
        'name' => 'Hacked'
    ]);

    $response->assertStatus(403);
}
```

## Running Tests

```bash
# All tests
php artisan test

# Specific file
php artisan test tests/Feature/Admin/UserManagementTest.php

# Specific test
php artisan test --filter test_user_can_login

# With coverage
php artisan test --coverage

# Parallel
php artisan test --parallel

# Stop on failure
php artisan test --stop-on-failure
```

## Test Naming Convention

Use descriptive test names following this pattern:

```
test_{who}_{can/cannot}_{action}_{resource}
```

Examples:

-   `test_admin_can_create_user`
-   `test_user_cannot_delete_campus`
-   `test_unauthenticated_user_cannot_access_dashboard`
-   `test_validation_requires_email`
-   `test_user_can_update_own_award`

## Common Mistakes to Avoid

1. ❌ Using `->get()` instead of `->getJson()`
2. ❌ Forgetting to authenticate before testing protected routes
3. ❌ Not using `RefreshDatabase` trait
4. ❌ Testing multiple things in one test
5. ❌ Not asserting database state after mutations
6. ❌ Hardcoding IDs instead of using factories
7. ❌ Not testing negative cases (failures)

## Best Practices

✅ Use factories for all test data
✅ Test one thing per test method
✅ Use descriptive test names
✅ Test both success and failure cases
✅ Test authorization at different levels
✅ Assert database state after changes
✅ Keep tests independent
✅ Use helper methods from TestCase
