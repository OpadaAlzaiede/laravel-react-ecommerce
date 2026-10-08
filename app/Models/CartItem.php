<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'price',
        'variation_type_option_ids',
        'is_saved_for_later',
    ];

    protected $casts = [
        'variation_type_option_ids' => 'json',
        'is_saved_for_later' => 'boolean',
    ];
}
