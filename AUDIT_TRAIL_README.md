# Audit Trail Implementation

## Overview

The audit trail system automatically logs all changes (create, update, delete) to important models in the system. This provides a complete history of who did what and when.

## Features

✅ **Automatic Logging** - Changes are automatically tracked using the `Auditable` trait
✅ **User Tracking** - Records which user made the change
✅ **Change Tracking** - Stores old and new values for all modifications
✅ **IP & User Agent** - Captures request metadata for security
✅ **Comprehensive API** - Full REST API for querying audit logs
✅ **PDF Export** - Generate downloadable PDF reports
✅ **Statistics** - Real-time statistics and analytics

## Models with Audit Logging

The following models are automatically audited:

-   ✅ `User` - User account changes
-   ✅ `TechTransfer` - Technology transfer projects
-   ✅ `Award` - Awards and recognitions
-   ✅ `Resolution` - Resolutions
-   ✅ `Modality` - Delivery modalities
-   ✅ `IntlPartner` - International partnerships
-   ✅ `ImpactAssessment` - Impact assessments
-   ✅ `Campus` - Campus information
-   ✅ `College` - College information

## Database Schema

```sql
CREATE TABLE audit_logs (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NULL,                  -- Who made the change
    action VARCHAR(255),                   -- created, updated, deleted
    model_type VARCHAR(255),               -- Full class name of the model
    model_id BIGINT,                       -- ID of the affected record
    old_values JSON NULL,                  -- Previous values (for update/delete)
    new_values JSON NULL,                  -- New values (for create/update)
    ip_address VARCHAR(255) NULL,          -- Request IP
    user_agent TEXT NULL,                  -- Browser/client info
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX (model_type, model_id),
    INDEX (user_id),
    INDEX (action),
    INDEX (created_at)
);
```

## API Endpoints

### List All Audit Logs

```http
GET /api/audit-logs
```

**Query Parameters:**

-   `user_id` - Filter by user ID
-   `model_type` - Filter by model class name
-   `action` - Filter by action (created, updated, deleted)
-   `from_date` - Filter from date
-   `to_date` - Filter to date
-   `per_page` - Results per page (default: 15)

**Response:**

```json
{
    "success": true,
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 1,
                "user_id": 1,
                "action": "updated",
                "model_type": "App\\Models\\TechTransfer",
                "model_id": 5,
                "old_values": { "name": "Old Name" },
                "new_values": { "name": "New Name" },
                "ip_address": "192.168.1.1",
                "user_agent": "Mozilla/5.0...",
                "created_at": "2025-01-16T10:30:00",
                "user": {
                    "id": 1,
                    "first_name": "John",
                    "last_name": "Doe",
                    "email": "john@example.com"
                }
            }
        ],
        "total": 100,
        "per_page": 15
    }
}
```

### Get Specific Audit Log

```http
GET /api/audit-logs/{id}
```

### Get Model Audit Logs

```http
GET /api/audit-logs/model/{modelType}/{modelId}
```

Example:

```http
GET /api/audit-logs/model/App\Models\TechTransfer/5
```

### Get User's Audit Logs

```http
GET /api/audit-logs/user/{userId}
```

### Get Statistics

```http
GET /api/audit-logs/statistics
```

**Response:**

```json
{
    "success": true,
    "data": {
        "total_logs": 1500,
        "logs_today": 45,
        "logs_this_week": 320,
        "logs_this_month": 850,
        "top_actions": [
            {"action": "updated", "count": 750},
            {"action": "created", "count": 500},
            {"action": "deleted", "count": 250}
        ],
        "top_models": [
            {"model_type": "App\\Models\\TechTransfer", "count": 600},
            {"model_type": "App\\Models\\User", "count": 400}
        ],
        "top_users": [
            {
                "user_id": 1,
                "count": 234,
                "user": {
                    "id": 1,
                    "first_name": "John",
                    "last_name": "Doe",
                    "email": "john@example.com"
                }
            }
        ],
        "recent_activities": [...]
    }
}
```

### Get Logs by Date Range

```http
GET /api/audit-logs/by-date-range?start_date=2025-01-01&end_date=2025-01-31
```

### Generate PDF Report

```http
GET /api/audit-logs/pdf
```

**Query Parameters:**

-   Same as list endpoint (user_id, model_type, action, from_date, to_date)
-   Downloads a formatted PDF report

## Usage in Models

### Adding Audit Trail to a New Model

1. Add the `Auditable` trait to your model:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class YourModel extends Model
{
    use Auditable;

    /**
     * Attributes excluded from audit logging.
     */
    protected $auditExclude = ['updated_at', 'created_at'];

    // ... rest of your model
}
```

2. That's it! The model will now automatically log all changes.

### Customizing Excluded Attributes

By default, these attributes are excluded from audit logs:

-   `password`
-   `remember_token`
-   `updated_at`
-   `created_at`

To customize, add the `$auditExclude` property:

```php
protected $auditExclude = ['updated_at', 'created_at', 'sensitive_field'];
```

### Accessing Audit Logs for a Model Instance

```php
$techTransfer = TechTransfer::find(1);
$auditLogs = $techTransfer->auditLogs;

// With pagination
$auditLogs = $techTransfer->auditLogs()->paginate(15);

// Filter by action
$updates = $techTransfer->auditLogs()->where('action', 'updated')->get();
```

## Frontend Integration

### Fetching Audit Logs

```typescript
import api from "@/lib/axios";

// Get all audit logs
const response = await api.get("/audit-logs", {
    params: {
        per_page: 15,
        user_id: 1,
        action: "updated",
    },
});

// Get statistics
const stats = await api.get("/audit-logs/statistics");

// Get logs for a specific model
const logs = await api.get("/audit-logs/model/App\\Models\\TechTransfer/5");
```

### Downloading PDF Report

```typescript
const downloadAuditPDF = async (filters: any) => {
    const params = new URLSearchParams(filters);
    const token = localStorage.getItem("token");

    const response = await fetch(
        `${import.meta.env.VITE_API_URL}/audit-logs/pdf?${params}`,
        {
            headers: {
                Authorization: `Bearer ${token}`,
            },
        }
    );

    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `audit-trail-${new Date().toISOString().split("T")[0]}.pdf`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
};
```

## How It Works

### The Auditable Trait

The `Auditable` trait uses Eloquent model events to automatically capture changes:

1. **Created Event** - When a new record is created, logs all attributes
2. **Updated Event** - When a record is updated, logs old vs new values
3. **Deleted Event** - When a record is deleted, logs the final state

### Change Detection

For updates, the trait:

-   Compares old values with new values
-   Only logs fields that actually changed
-   Respects the `$auditExclude` list
-   Stores complete before/after snapshots

### Metadata Capture

Every audit log includes:

-   **User ID** - From `Auth::id()`
-   **IP Address** - From `request()->ip()`
-   **User Agent** - From `request()->userAgent()`
-   **Timestamp** - Automatic via `created_at`

## Security & Performance

### Security

-   ✅ Audit logs are read-only via API (no delete/update endpoints)
-   ✅ Requires authentication (protected by `auth:sanctum` middleware)
-   ✅ Captures IP address for security auditing
-   ✅ User agent helps identify suspicious activity

### Performance

-   ✅ Database indexes on frequently queried fields
-   ✅ Pagination on all list endpoints
-   ✅ Async logging (doesn't block main operations)
-   ✅ Failed audit logs don't prevent operations (logged to system log)

### Storage Considerations

-   JSON fields efficiently store complex changes
-   Consider archiving old logs periodically
-   Implement data retention policies as needed

## Migration

To run the migration:

```bash
php artisan migrate
```

The migration file: `database/migrations/2025_01_16_000000_create_audit_logs_table.php`

## Testing

Example test scenarios:

```php
// Test audit log creation
$user = User::factory()->create();
$this->assertDatabaseHas('audit_logs', [
    'model_type' => User::class,
    'model_id' => $user->id,
    'action' => 'created'
]);

// Test update tracking
$user->update(['first_name' => 'Jane']);
$log = AuditLog::latest()->first();
$this->assertEquals('John', $log->old_values['first_name']);
$this->assertEquals('Jane', $log->new_values['first_name']);
```

## Troubleshooting

### Audit logs not being created?

1. Check that the model uses the `Auditable` trait
2. Verify the migration has been run
3. Check Laravel logs for any errors
4. Ensure user is authenticated (for user_id tracking)

### Performance issues?

1. Add more specific indexes
2. Implement log archiving
3. Use queue jobs for heavy operations
4. Consider pagination limits

## Future Enhancements

-   [ ] Real-time notifications for critical changes
-   [ ] Advanced filtering UI in frontend
-   [ ] Log retention policies
-   [ ] Automated anomaly detection
-   [ ] Export to CSV/Excel
-   [ ] Rollback functionality

---

**Created:** January 16, 2025
**Version:** 1.0.0
