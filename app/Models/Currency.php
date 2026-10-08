<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'symbol',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
