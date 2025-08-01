<?php

namespace App\Models;

use App\Enums\Users\VendorStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vendor extends Model
{
    protected $primaryKey = 'user_id';

    protected $fillable = [
        'store_name',
        'store_address',
        'status',
        'user_id',
        'cover_image'
    ];

    public function scopeEligibleForPayout(Builder $query): Builder
    {
        return $query->where('status', VendorStatusEnum::APPROVED)
                ->join('users', 'users.id', '=', 'vendors.user_id')
                ->where('users.stripe_account_active', true);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
