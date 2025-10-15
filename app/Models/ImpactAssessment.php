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
        'beneficiary',
        'geographic_coverage',
        'num_direct_beneficiary',
        'num_indirect_beneficiary',
        'is_archived',
    ];

    protected $casts = [
        'num_direct_beneficiary' => 'integer',
        'num_indirect_beneficiary' => 'integer',
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
