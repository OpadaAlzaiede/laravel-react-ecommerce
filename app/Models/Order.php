<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = ['total_price', 'status', 'stripe_session_id', 'user_id', 'online_payment_commission', 'website_commission', 'vendor_subtotal', 'payment_intent'];

    public function orderItem(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vendorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_user_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'vendor_user_id', 'user_id');
    }

    public function scopeForVendor(Builder $query): Builder
    {
        return $query->where('vendor_user_id', auth()->user()->id);
    }
}
