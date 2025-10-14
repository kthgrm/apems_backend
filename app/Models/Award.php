<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    protected $fillable = [
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
    ];

    protected $casts = [
        'date_received' => 'date',
        'is_archived' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'attachment_paths' => 'array',
    ];
}
