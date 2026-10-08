<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Orders\OrderFilterDto;
use App\Enums\Orders\StatusEnum;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class OrderService
{
    public function paginateForCustomer(User $customer, OrderFilterDto $filters): LengthAwarePaginator
    {
        return Order::query()
            ->select(['id', 'total_price', 'status', 'created_at', 'vendor_user_id', 'user_id'])
            ->with(['user', 'vendorUser'])
            ->whereBelongsTo($customer)
            ->when($filters->status, static fn (Builder $query, StatusEnum $status): Builder => $query
                ->where('status', $status->value))
            ->when($filters->hasDateRange(), static fn (Builder $query): Builder => $query
                ->whereBetween('created_at', [
                    $filters->startDate->toDateString(),
                    $filters->endDate->toDateString(),
                ]))
            ->orderByDesc('created_at')
            ->paginate()
            ->withQueryString();
    }

    public function loadForDisplay(Order $order): Order
    {
        return $order->load(['orderItem', 'vendorUser']);
    }
}
