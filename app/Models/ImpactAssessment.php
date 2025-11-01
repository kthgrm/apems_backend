<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class ImpactAssessment extends Model
{
    use Auditable;

    /**
     * Attributes excluded from audit logging.
     */
    protected $auditExclude = ['updated_at', 'created_at'];

    protected $fillable = [
        'user_id',
        'tech_transfer_id',
        'title',
        'description',
        'attachment_paths',
        'is_archived',
        'status',
        'remarks'
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'attachment_paths' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function techTransfer()
    {
        return $this->belongsTo(TechTransfer::class);
    }
}
