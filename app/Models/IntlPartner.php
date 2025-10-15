<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class IntlPartner extends Model
{
    use Auditable;

    /**
     * Attributes excluded from audit logging.
     */
    protected $auditExclude = ['updated_at', 'created_at'];

    protected $fillable = [
        'user_id',
        'college_id',
        'agency_partner',
        'location',
        'activity_conducted',
        'start_date',
        'end_date',
        'number_of_participants',
        'number_of_committee',
        'narrative',
        'attachment_paths',
        'attachment_link',
        'is_archived',
    ];

    protected $casts = [
        'attachment_paths' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'number_of_participants' => 'integer',
        'number_of_committee' => 'integer',
        'is_archived' => 'boolean',
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
