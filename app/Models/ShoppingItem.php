<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ShoppingItem extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'shopping_items';

    protected $fillable = [
        'name',
        'slug',
        'image_url',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
