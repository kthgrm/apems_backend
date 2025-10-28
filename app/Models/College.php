<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class College extends Model
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
        'code',
        'name',
        'logo',
        'campus_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the campus for this college.
     */
    public function campus()
    {
        return $this->belongsTo(Campus::class);
    }

    /**
     * Get the tech transfers for this college.
     */
    public function techTransfers()
    {
        return $this->hasMany(TechTransfer::class);
    }

    /**
     * Get the awards for this college.
     */
    public function awards()
    {
        return $this->hasMany(Award::class);
    }

    /**
     * Get the engagements for this college.
     */
    public function engagements()
    {
        return $this->hasMany(Engagement::class);
    }

    /**
     * Get the impact assessments for this college.
     * College → TechTransfer → ImpactAssessment
     */
    public function impactAssessments()
    {
        return ImpactAssessment::query()
            ->whereHas('techTransfer', function ($query) {
                $query->where('college_id', $this->id);
            });
    }

    /**
     * Get the modalities for this college.
     * College → TechTransfer → Modality
     */
    public function modalities()
    {
        return Modality::query()
            ->whereHas('techTransfer', function ($query) {
                $query->where('college_id', $this->id);
            });
    }
}
