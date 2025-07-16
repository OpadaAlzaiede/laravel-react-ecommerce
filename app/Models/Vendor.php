<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = [
        'store_name',
        'store_address',
        'status',
        'user_id',
        'cover_image'
    ];
}
