<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Orders\OrderFilterDto;
use App\Enums\Orders\StatusEnum;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class OrderService
{
    public function __construct(private readonly Gate $gate) {}

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

    /**
     * @return Collection<int, Order>
     *
     * @throws ModelNotFoundException<Order>
     * @throws AuthorizationException
     */
    public function getCheckoutOrders(User $customer, string $sessionId): Collection
    {
        $orders = Order::query()
            ->where('stripe_session_id', $sessionId)
            ->get();

        if ($orders->isEmpty()) {
            throw (new ModelNotFoundException)->setModel(Order::class);
        }

        $orders->each(fn (Order $order) => $this->gate->forUser($customer)->authorize('view', $order));

        return $orders;
    }
}
