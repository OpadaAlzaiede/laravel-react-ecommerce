<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VariationType extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'name',
        'type',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(VariationTypeOption::class);
    }
}
