<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait Auditable
{
    /**
     * Boot the auditable trait for a model.
     */
    public static function bootAuditable()
    {
        static::created(function ($model) {
            $model->auditCreated();
        });

        static::updated(function ($model) {
            $model->auditUpdated();
        });

        static::deleted(function ($model) {
            $model->auditDeleted();
        });
    }

    /**
     * Log model creation.
     */
    protected function auditCreated()
    {
        $this->createAuditLog('created', [], $this->getAuditableAttributes());
    }

    /**
     * Log model update.
     */
    protected function auditUpdated()
    {
        $changes = $this->getChanges();
        $original = $this->getOriginal();

        // Filter out timestamps if not needed
        $excludedAttributes = $this->getAuditExclude();

        $oldValues = [];
        $newValues = [];

        foreach ($changes as $key => $value) {
            if (in_array($key, $excludedAttributes)) {
                continue;
            }

            $oldValues[$key] = $original[$key] ?? null;
            $newValues[$key] = $value;
        }

        if (!empty($newValues)) {
            $this->createAuditLog('updated', $oldValues, $newValues);
        }
    }

    /**
     * Log model deletion.
     */
    protected function auditDeleted()
    {
        $this->createAuditLog('deleted', $this->getAuditableAttributes(), []);
    }

    /**
     * Create an audit log entry.
     */
    protected function createAuditLog(string $action, array $oldValues, array $newValues)
    {
        try {
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'model_type' => get_class($this),
                'model_id' => $this->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Log the error but don't stop the operation
            Log::error('Failed to create audit log: ' . $e->getMessage());
        }
    }

    /**
     * Get attributes that should be audited.
     */
    protected function getAuditableAttributes(): array
    {
        $attributes = $this->getAttributes();
        $excludedAttributes = $this->getAuditExclude();

        return array_filter($attributes, function ($key) use ($excludedAttributes) {
            return !in_array($key, $excludedAttributes);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get attributes that should be excluded from audit.
     */
    protected function getAuditExclude(): array
    {
        return property_exists($this, 'auditExclude')
            ? $this->auditExclude
            : ['updated_at', 'created_at', 'password', 'remember_token'];
    }

    /**
     * Get audit logs for this model.
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'model');
    }
}
