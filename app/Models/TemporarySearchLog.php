<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class TemporarySearchLog extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'temporary_search_logs';

    protected $fillable = [
        'family_id',
        'search_term',
        'not_found',
    ];

    protected $casts = [
        'not_found' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function family()
    {
        return $this->belongsTo(Family::class, 'family_id');
    }
}
