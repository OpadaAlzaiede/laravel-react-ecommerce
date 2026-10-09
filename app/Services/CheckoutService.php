<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Payments\CheckoutGateway;
use App\DTOs\Checkout\CheckoutDto;
use App\Enums\Orders\StatusEnum;
use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Database\ConnectionInterface;

final class CheckoutService
{
    private const CENTS_PER_UNIT = 100;

    public function __construct(
        private readonly CartService $cartService,
        private readonly StockService $stockService,
        private readonly CheckoutGateway $checkoutGateway,
        private readonly ConnectionInterface $db,
        private readonly UrlGenerator $url,
        private readonly Config $config,
    ) {}

    public function checkout(User $customer, CheckoutDto $checkout): string
    {
        $vendorGroups = $this->vendorGroupsToCheckout($checkout->vendorId);

        return $this->db->transaction(function () use ($customer, $vendorGroups): string {
            $this->reserveStock($vendorGroups);

            $orders = [];
            $lineItems = [];

            foreach ($vendorGroups as $vendorGroup) {
                $orders[] = $this->createOrder($customer, $vendorGroup);

                foreach ($vendorGroup['items'] as $cartItem) {
                    $lineItems[] = $this->toLineItem($cartItem);
                }
            }

            $session = $this->checkoutGateway->createSession(
                customerEmail: $customer->email,
                lineItems: $lineItems,
                successUrl: $this->url->route('stripe.success').'?session_id={CHECKOUT_SESSION_ID}',
                cancelUrl: $this->url->route('stripe.failure'),
            );

            foreach ($orders as $order) {
                $order->stripe_session_id = $session->id;
                $order->save();
            }

            return $session->url;
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $vendorGroups
     */
    private function reserveStock(array $vendorGroups): void
    {
        $cartItems = collect($vendorGroups)->flatMap(static fn (array $vendorGroup): array => $vendorGroup['items']);

        $products = Product::query()
            ->with('variations')
            ->whereIn('id', $cartItems->pluck('product_id'))
            ->get()
            ->keyBy('id');

        foreach ($cartItems as $cartItem) {
            $this->stockService->reserve($products[$cartItem['product_id']], $cartItem['option_ids'], $cartItem['quantity']);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function vendorGroupsToCheckout(?int $vendorId): array
    {
        $vendorGroups = $this->cartService->getCartItemsGrouped();

        if ($vendorId === null) {
            return $vendorGroups;
        }

        if (! array_key_exists($vendorId, $vendorGroups)) {
            throw CheckoutException::vendorNotInCart($vendorId);
        }

        return [$vendorId => $vendorGroups[$vendorId]];
    }

    /**
     * @param  array<string, mixed>  $vendorGroup
     */
    private function createOrder(User $customer, array $vendorGroup): Order
    {
        $order = Order::create([
            'stripe_session_id' => null,
            'user_id' => $customer->id,
            'vendor_user_id' => $vendorGroup['user']['id'],
            'total_price' => $vendorGroup['total_price'],
            'status' => StatusEnum::DRAFT->value,
        ]);

        foreach ($vendorGroup['items'] as $cartItem) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $cartItem['product_id'],
                'price' => $cartItem['price'],
                'quantity' => $cartItem['quantity'],
                'variation_type_option_ids' => $cartItem['option_ids'],
            ]);
        }

        return $order;
    }

    /**
     * @param  array<string, mixed>  $cartItem
     * @return array<string, mixed>
     */
    private function toLineItem(array $cartItem): array
    {
        $lineItem = [
            'price_data' => [
                'currency' => $this->config->get('app.currency'),
                'product_data' => [
                    'name' => $cartItem['title'],
                ],
                'unit_amount' => (int) round($cartItem['price'] * self::CENTS_PER_UNIT),
            ],
            'quantity' => $cartItem['quantity'],
        ];

        if ($cartItem['image'] !== '') {
            $lineItem['price_data']['product_data']['images'] = [$this->url->to($cartItem['image'])];
        }

        $description = collect($cartItem['options'])
            ->map(static fn (array $option): string => $option['type']['name'].': '.$option['name'])
            ->implode(', ');

        if ($description !== '') {
            $lineItem['price_data']['product_data']['description'] = $description;
        }

        return $lineItem;
    }
}
