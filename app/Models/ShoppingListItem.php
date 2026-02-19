<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ShoppingListItem extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'shopping_list_items';

    protected $fillable = [
        'family_id',
        'item_name',
        'item_slug',
        'is_predefined',
        'image_url',
        'is_in_cart',
        'usage_count',
        'last_used_at',
        'added_by',
    ];

    protected $casts = [
        'is_predefined' => 'boolean',
        'is_in_cart' => 'boolean',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function family()
    {
        return $this->belongsTo(Family::class, 'family_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
