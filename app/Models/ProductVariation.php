<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    protected $fillable = [
        'product_id',
        'variation_type_option_ids',
        'quantity',
        'price',
    ];

    protected $casts = [
        'variation_type_option_ids' => 'json',
    ];
}
