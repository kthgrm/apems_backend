<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Resolution extends Model
{
    use Auditable;

    /**
     * Attributes excluded from audit logging.
     */
    protected $auditExclude = ['updated_at', 'created_at'];

    protected $fillable = [
        'resolution_number',
        'effectivity',
        'expiration',
        'contact_person',
        'contact_number_email',
        'partner_agency',
        'attachment_paths',
        'attachment_link',
        'user_id',
        'is_archived',
    ];

    protected $casts = [
        'effectivity' => 'date',
        'expiration' => 'date',
        'attachment_paths' => 'array',
        'is_archived' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
