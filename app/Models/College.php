<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class College extends Model
{
    use HasFactory;

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
     * Get the international partners for this college.
     */
    public function intlPartners()
    {
        return $this->hasMany(IntlPartner::class);
    }
}
