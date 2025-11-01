<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Award extends Model
{
    use Auditable;

    /**
     * Attributes excluded from audit logging.
     */
    protected $auditExclude = ['updated_at', 'created_at'];

    protected $fillable = [
        'user_id',
        'college_id',
        'award_name',
        'description',
        'date_received',
        'event_details',
        'location',
        'awarding_body',
        'people_involved',
        'attachment_paths',
        'attachment_link',
        'is_archived',
        'status',
        'remarks'
    ];

    protected $casts = [
        'date_received' => 'date',
        'is_archived' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'attachment_paths' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function college()
    {
        return $this->belongsTo(College::class);
    }
}
