<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class TechTransfer extends Model
{
    use HasFactory, Auditable;

    /**
     * Attributes excluded from audit logging.
     */
    protected $auditExclude = ['updated_at', 'created_at'];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'college_id',
        'name',
        'description',
        'category',
        'purpose',
        'start_date',
        'end_date',
        'tags',
        'leader',
        'deliverables',
        'agency_partner',
        'contact_person',
        'contact_email',
        'contact_phone',
        'contact_address',
        'copyright',
        'ip_details',
        'is_assessment_based',
        'monitoring_evaluation_plan',
        'sustainability_plan',
        'reporting_frequency',
        'attachment_paths',
        'attachment_link',
        'is_archived',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'reporting_frequency' => 'integer',
        'is_assessment_based' => 'boolean',
        'is_archived' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'attachment_paths' => 'array',
    ];

    /**
     * Get the user that owns this tech transfer.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the college that this tech transfer belongs to.
     */
    public function college()
    {
        return $this->belongsTo(College::class);
    }

    /**
     * Get the impact assessments for this tech transfer.
     */
    public function impactAssessments()
    {
        return $this->hasMany(ImpactAssessment::class);
    }

    /**
     * Get the modalities for this tech transfer.
     */
    public function modalities()
    {
        return $this->hasMany(Modality::class);
    }

    /**
     * Get the duration of the tech transfer in days.
     */
    public function getDurationAttribute()
    {
        if ($this->start_date && $this->end_date) {
            return $this->start_date->diffInDays($this->end_date);
        }

        return null;
    }
}
