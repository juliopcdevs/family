<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class CalendarEvent extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'calendar_events';

    protected $fillable = [
        'family_id',
        'title',
        'date',
        'time',
        'created_by',
    ];

    protected $casts = [
        'date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function family()
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
