<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = ['auditable_type', 'auditable_id', 'description'];

    /**
     * Get the auditable_type attribute (alias for model_type).
     */
    public function getAuditableTypeAttribute()
    {
        return $this->model_type;
    }

    /**
     * Get the auditable_id attribute (alias for model_id).
     */
    public function getAuditableIdAttribute()
    {
        return $this->model_id;
    }

    /**
     * Get the description attribute.
     */
    public function getDescriptionAttribute()
    {
        $modelName = $this->model_type ? class_basename($this->model_type) : 'Unknown';
        $action = ucfirst($this->action);

        return "{$action} {$modelName}";
    }

    /**
     * Get the user that performed the action.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model.
     */
    public function auditable()
    {
        return $this->morphTo('model');
    }
}
