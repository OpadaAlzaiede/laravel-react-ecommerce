<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Orders\StatusEnum;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder;

final class StockService
{
    public function __construct(private readonly ConnectionInterface $db) {}

    /**
     * @param  array<int, int>  $optionIds
     *
     * @throws InsufficientStockException
     */
    public function reserve(Product $product, array $optionIds, int $quantity): void
    {
        $stockRow = $this->stockRow($product, $optionIds);

        if ($stockRow === null || $stockRow->quantity === null) {
            return;
        }

        $reserved = $stockRow->newQuery()
            ->whereKey($stockRow->getKey())
            ->whereNotNull('quantity')
            ->where('quantity', '>=', $quantity)
            ->decrement('quantity', $quantity);

        if ($reserved === 0) {
            $available = (int) $stockRow->newQuery()->whereKey($stockRow->getKey())->lockForUpdate()->value('quantity');

            throw InsufficientStockException::forProduct($product, max(0, $available));
        }
    }

    /**
     * @param  array<int, int>  $optionIds
     */
    public function release(Product $product, array $optionIds, int $quantity): void
    {
        $stockRow = $this->stockRow($product, $optionIds);

        if ($stockRow === null) {
            return;
        }

        $stockRow->newQuery()
            ->whereKey($stockRow->getKey())
            ->whereNotNull('quantity')
            ->increment('quantity', $quantity);
    }

    /**
     * @param  Builder<Order>  $orders
     */
    public function cancelDraftOrders(Builder $orders): int
    {
        return $this->db->transaction(function () use ($orders): int {
            $draftOrders = $orders
                ->with('orderItem.product.variations')
                ->where('status', StatusEnum::DRAFT->value)
                ->lockForUpdate()
                ->get();

            foreach ($draftOrders as $order) {
                $order->orderItem->each(fn (OrderItem $orderItem) => $this->release(
                    $orderItem->product,
                    $orderItem->variation_type_option_ids ?? [],
                    $orderItem->quantity,
                ));

                $order->status = StatusEnum::CANCELLED->value;
                $order->save();
            }

            return $draftOrders->count();
        });
    }

    /**
     * @param  array<int, int>  $optionIds
     */
    private function stockRow(Product $product, array $optionIds): Product|ProductVariation|null
    {
        if ($optionIds === []) {
            return $product;
        }

        $product->loadMissing('variations');

        return $product->variationForOptions($optionIds);
    }
}
