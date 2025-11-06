# Test Migration Guide: From Laravel+Inertia to API-based Architecture

## Overview

This guide explains how to migrate your existing tests from the Laravel+Inertia starter kit to the new API-based architecture with a separated backend and frontend.

## Key Differences

### Before (Inertia)

-   Tests used `$this->get()` and expected Inertia responses
-   Tests checked for Inertia page components
-   Authentication was session-based
-   Responses were HTML/Inertia pages

### After (API)

-   Tests use `$this->getJson()`, `$this->postJson()`, etc.
-   Tests check for JSON structure and data
-   Authentication is token-based (Laravel Sanctum)
-   Responses are JSON

## Migration Steps

### 1. Update TestCase Base Class

The `TestCase.php` has been enhanced with helper methods:

```php
// Authenticate as admin
$admin = $this->authenticateAsAdmin();

// Authenticate as regular user
$user = $this->authenticateAsUser();

// Create a user without authenticating
$user = $this->createUser(['email' => 'test@example.com']);
```

### 2. Convert Route Calls

**Before (Inertia):**

```php
$response = $this->get(route('users.index'));
$response->assertInertia(fn ($page) => $page->component('Users/Index'));
```

**After (API):**

```php
$response = $this->getJson('/api/users');
$response->assertStatus(200)
    ->assertJsonStructure([
        'data' => [
            '*' => ['id', 'first_name', 'last_name', 'email']
        ]
    ]);
```

### 3. Convert Authentication

**Before (Inertia):**

```php
$user = User::factory()->create();
$this->actingAs($user);
```

**After (API with Sanctum):**

```php
// Use helper methods
$user = $this->authenticateAsUser();
// OR
$admin = $this->authenticateAsAdmin();
```

### 4. Convert Form Submissions

**Before (Inertia):**

```php
$response = $this->post(route('users.store'), [
    'first_name' => 'John',
    'last_name' => 'Doe',
]);
```

**After (API):**

```php
$response = $this->postJson('/api/users', [
    'first_name' => 'John',
    'last_name' => 'Doe',
]);
```

### 5. Convert Response Assertions

**Before (Inertia):**

```php
$response->assertInertia(fn ($page) =>
    $page->component('Users/Show')
        ->has('user')
        ->where('user.name', 'John Doe')
);
```

**After (API):**

```php
$response->assertStatus(200)
    ->assertJson([
        'data' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]
    ])
    ->assertJsonStructure([
        'data' => ['id', 'first_name', 'last_name', 'email']
    ]);
```

### 6. Convert Validation Tests

**Before (Inertia):**

```php
$response = $this->post(route('users.store'), []);
$response->assertSessionHasErrors(['first_name', 'email']);
```

**After (API):**

```php
$response = $this->postJson('/api/users', []);
$response->assertStatus(422)
    ->assertJsonValidationErrors(['first_name', 'email']);
```

### 7. Convert Redirects

**Before (Inertia):**

```php
$response->assertRedirect(route('users.index'));
```

**After (API):**

```php
// API doesn't redirect, check status codes instead
$response->assertStatus(201); // Created
$response->assertStatus(200); // Updated
$response->assertStatus(204); // Deleted (no content)
```

## Common Patterns

### Testing CRUD Operations

```php
// INDEX - List all
public function test_admin_can_view_all_resources(): void
{
    $admin = $this->authenticateAsAdmin();
    Resource::factory()->count(3)->create();

    $response = $this->getJson('/api/resources');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'created_at']
            ]
        ]);
}

// STORE - Create new
public function test_admin_can_create_resource(): void
{
    $admin = $this->authenticateAsAdmin();

    $response = $this->postJson('/api/resources', [
        'name' => 'Test Resource',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'data' => ['name' => 'Test Resource']
        ]);

    $this->assertDatabaseHas('resources', ['name' => 'Test Resource']);
}

// SHOW - View single
public function test_admin_can_view_single_resource(): void
{
    $admin = $this->authenticateAsAdmin();
    $resource = Resource::factory()->create();

    $response = $this->getJson("/api/resources/{$resource->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => ['id' => $resource->id]
        ]);
}

// UPDATE - Modify existing
public function test_admin_can_update_resource(): void
{
    $admin = $this->authenticateAsAdmin();
    $resource = Resource::factory()->create();

    $response = $this->putJson("/api/resources/{$resource->id}", [
        'name' => 'Updated Name',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'data' => ['name' => 'Updated Name']
        ]);

    $this->assertDatabaseHas('resources', [
        'id' => $resource->id,
        'name' => 'Updated Name'
    ]);
}

// DESTROY - Delete
public function test_admin_can_delete_resource(): void
{
    $admin = $this->authenticateAsAdmin();
    $resource = Resource::factory()->create();

    $response = $this->deleteJson("/api/resources/{$resource->id}");

    $response->assertStatus(200);

    $this->assertDatabaseMissing('resources', ['id' => $resource->id]);
}
```

### Testing Authorization

```php
// Test admin-only access
public function test_non_admin_cannot_create_resource(): void
{
    $user = $this->authenticateAsUser();

    $response = $this->postJson('/api/resources', [
        'name' => 'Test',
    ]);

    $response->assertStatus(403);
}

// Test authentication requirement
public function test_unauthenticated_user_cannot_access_resources(): void
{
    $response = $this->getJson('/api/resources');

    $response->assertStatus(401);
}
```

### Testing User-Specific Data

```php
public function test_user_can_only_see_own_data(): void
{
    $user = $this->authenticateAsUser();
    $otherUser = User::factory()->create();

    // User's own resources
    Resource::factory()->count(3)->create(['user_id' => $user->id]);

    // Other user's resources
    Resource::factory()->count(5)->create(['user_id' => $otherUser->id]);

    $response = $this->getJson('/api/user/resources');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
}
```

## API Route Patterns

All API routes are prefixed with `/api` and protected by `auth:sanctum` middleware:

```php
// Public routes
POST   /api/auth/login
POST   /api/forgot-password
POST   /api/reset-password

// Protected routes (require authentication)
GET    /api/auth/user
POST   /api/auth/logout

// Admin routes (require admin role)
GET    /api/users
POST   /api/users
GET    /api/users/{user}
PUT    /api/users/{user}
DELETE /api/users/{user}

// User routes (authenticated users)
GET    /api/user/dashboard
GET    /api/user/awards
GET    /api/user/tech-transfers
```

## HTTP Status Codes

-   `200 OK` - Successful GET, PUT, PATCH
-   `201 Created` - Successful POST (resource created)
-   `204 No Content` - Successful DELETE
-   `401 Unauthorized` - Not authenticated
-   `403 Forbidden` - Authenticated but not authorized
-   `422 Unprocessable Entity` - Validation errors
-   `404 Not Found` - Resource doesn't exist

## Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Auth/AuthenticationTest.php

# Run specific test method
php artisan test --filter test_user_can_login_with_valid_credentials

# Run tests with coverage
php artisan test --coverage

# Run tests in parallel
php artisan test --parallel
```

## Test Structure Created

```
tests/
├── Feature/
│   ├── Auth/
│   │   ├── AuthenticationTest.php
│   │   └── PasswordResetTest.php
│   ├── Admin/
│   │   ├── UserManagementTest.php
│   │   ├── CampusManagementTest.php
│   │   ├── CollegeManagementTest.php
│   │   ├── DashboardTest.php
│   │   └── ... (other admin tests)
│   ├── User/
│   │   ├── DashboardTest.php
│   │   └── ... (other user tests)
│   └── Middleware/
│       └── MiddlewareTest.php
├── Unit/
│   └── UserTest.php
└── TestCase.php
```

## Next Steps

1. **Create remaining admin tests:**

    - AwardTest.php
    - TechnologyTransferTest.php
    - ImpactAssessmentTest.php
    - ModalitiesTest.php
    - ResolutionTest.php
    - ReportsTest.php

2. **Create remaining user tests:**

    - AwardTest.php
    - TechnologyTransferTest.php
    - ImpactAssessmentTest.php
    - ModalitiesTest.php

3. **Ensure factories exist** for all models in `database/factories/`

4. **Configure test database** in `phpunit.xml`:

    ```xml
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    ```

5. **Run tests regularly** during development to ensure API contracts are maintained

## Tips

1. **Use factories**: Always use model factories to create test data
2. **Test negative cases**: Test both success and failure scenarios
3. **Test permissions**: Always test authorization (admin vs user)
4. **Test validation**: Test all validation rules
5. **Keep tests isolated**: Each test should be independent
6. **Use descriptive names**: Test method names should describe what they test
7. **Assert database state**: Use `assertDatabaseHas` and `assertDatabaseMissing`
8. **Test edge cases**: Empty data, invalid data, boundary conditions

## Common Issues and Solutions

### Issue: "Table already exists" error with SQLite

**Solution**: The `RefreshDatabase` trait should be used in individual test classes, not in the base `TestCase.php`. Each test class should include:

```php
class YourTest extends TestCase
{
    use RefreshDatabase;

    // your tests...
}
```

### Issue: Tests fail with authentication errors

**Solution**: Make sure you're using `authenticateAsUser()` or `authenticateAsAdmin()` before making requests to protected routes.

### Issue: Validation errors not appearing

**Solution**: Use `->postJson()` instead of `->post()` to ensure JSON responses.

### Issue: Database state persists between tests

**Solution**: Ensure `use RefreshDatabase;` trait is present in your test class.

### Issue: Factories not found

**Solution**: Create factories for all models using `php artisan make:factory ModelNameFactory`.

## Conclusion

The migration from Inertia to API testing mainly involves:

1. Changing route calls to use JSON methods
2. Updating authentication to use Sanctum
3. Changing assertions to check JSON structure instead of Inertia components
4. Updating status code expectations

Use the provided test files as templates for creating additional tests!
