<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class Modality extends Model
{
    use Auditable;

    /**
     * Attributes excluded from audit logging.
     */
    protected $auditExclude = ['updated_at', 'created_at'];

    protected $fillable = [
        'user_id',
        'tech_transfer_id',
        'modality',
        'tv_channel',
        'radio',
        'online_link',
        'time_air',
        'period',
        'partner_agency',
        'hosted_by',
        'is_archived',
        'status',
        'remarks'
    ];

    protected $casts = [
        'is_archived' => 'boolean',
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
