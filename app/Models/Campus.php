<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campus extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'logo',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the colleges for this campus.
     */
    public function colleges()
    {
        return $this->hasMany(College::class);
    }

    /**
     * Get the tech transfers for this campus.
     */
    public function techTransfers()
    {
        return $this->hasManyThrough(TechTransfer::class, College::class);
    }

    /**
     * Get the intl partners for this campus.
     */
    public function intlPartners()
    {
        return $this->hasManyThrough(IntlPartner::class, College::class);
    }

    /**
     * Get the awards for this campus.
     */
    public function awards()
    {
        return $this->hasManyThrough(Award::class, College::class);
    }
}
