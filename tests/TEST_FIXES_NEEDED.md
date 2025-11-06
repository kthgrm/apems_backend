# Test Fixes Needed

## ✅ Completed

-   **Authentication Tests**: All 23 tests passing
-   **Password Reset Tests**: All passing
-   Test infrastructure setup complete

## ❌ Issues to Fix

### 1. Missing Factories

You need to create factories for these models:

```bash
php artisan make:factory AwardFactory
php artisan make:factory TechTransferFactory
php artisan make:factory EngagementFactory
php artisan make:factory ImpactAssessmentFactory
php artisan make:factory ModalityFactory
php artisan make:factory ResolutionFactory
```

### 2. Award Model - Field Mismatches

**Test expects:**

-   `title`, `description`, `date_awarded`, `awarding_body`

**API actually uses:**

-   `award_name`, `event_details`, `date_received`, `location`, `people_involved`, `awarding_body`

**Fix:** Update `AwardTest.php` to use correct field names.

### 3. Campus Model - Missing Fields

**Test expects:**

-   `name`, `address`

**API requires:**

-   `name`, `address`, `logo` (required)

**Fix:** Update `CampusManagementTest.php` to include `logo` field.

### 4. College Model - Missing Fields

**Test expects:**

-   `name`, `campus_id`

**API requires:**

-   `name`, `campus_id`, `code` (required), `logo` (required)

**Fix:** Update `CollegeManagementTest.php` to include `code` and `logo` fields.

### 5. User Management - Response Structure

**Test expects:**

```json
{
    "data": {
        "id": 1,
        "name": "..."
    }
}
```

**API returns:**

```json
{
    "id": 1,
    "name": "..."
}
```

**Fix:** Remove `data` wrapper from assertions or update API to include it.

### 6. Authorization Not Working

**Issue:** Regular users can access admin-only routes (users, campuses, colleges).

**Failing tests:**

-   `non_admin_cannot_view_all_users` - expects 403, gets 200
-   `non_admin_cannot_update_user` - expects 403, gets 200
-   `non_admin_cannot_delete_user` - expects 403, gets 200
-   `non_admin_cannot_create_campus` - expects 403, gets 422
-   `non_admin_cannot_update_campus` - expects 403, gets 200
-   `non_admin_cannot_create_college` - expects 403, gets 422
-   `non_admin_cannot_update_college` - expects 403, gets 200

**Fix:** Add admin middleware/authorization checks in controllers or create custom middleware.

Example middleware:

```php
// app/Http/Middleware/EnsureUserIsAdmin.php
public function handle($request, Closure $next)
{
    if (!$request->user() || $request->user()->role !== 'admin') {
        return response()->json(['message' => 'Forbidden'], 403);
    }
    return $next($request);
}
```

Then apply to routes:

```php
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::apiResource('users', UserController::class);
    Route::apiResource('campuses', CampusController::class);
    Route::apiResource('colleges', CollegeController::class);
});
```

### 7. User Validation - Missing Password Requirement

**Test expects:** Password required when creating user

**API actually:** Doesn't require password on creation (gets auto-generated?)

**Fix:** Either update API validation or update test expectations.

### 8. Dashboard - SQLite YEAR() Function

**Issue:** `YEAR(created_at)` function doesn't exist in SQLite.

**Error:**

```
SQLSTATE[HY000]: General error: 1 no such function: YEAR
```

**Fix:** Update `DashboardController.php` to use SQLite-compatible date functions:

```php
// Instead of:
->selectRaw('DISTINCT YEAR(created_at) as year')

// Use:
->selectRaw("DISTINCT strftime('%Y', created_at) as year")
```

### 9. Bulk Operations - Message Format

**Test expects:**

```json
{ "message": "Users activated successfully" }
```

**API returns:**

```json
{ "message": "3 user(s) activated successfully", "count": 3 }
```

**Fix:** Update test assertions to match actual message or make messages consistent.

## Quick Fixes Priority

1. **HIGH**: Fix authorization middleware (affects 8+ tests)
2. **HIGH**: Create missing factories
3. **HIGH**: Fix YEAR() SQLite function in Dashboard
4. **MEDIUM**: Update field names in Award tests
5. **MEDIUM**: Add missing required fields (logo, code) to Campus/College tests
6. **LOW**: Standardize response structures
7. **LOW**: Fix bulk operation messages

## Running Specific Test Suites

```bash
# Run only passing tests
php artisan test tests/Feature/Auth/

# Run admin tests (to see failures)
php artisan test tests/Feature/Admin/UserManagementTest.php

# Run specific test
php artisan test --filter test_admin_can_create_user
```

## Next Steps

1. Create factories for all models
2. Add admin authorization middleware
3. Fix SQLite compatibility in Dashboard
4. Update test field names to match actual API
5. Re-run tests and fix remaining issues
