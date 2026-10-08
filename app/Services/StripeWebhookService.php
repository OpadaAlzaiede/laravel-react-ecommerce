<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Payments\WebhookGateway;
use App\Enums\Orders\StatusEnum;
use App\Mail\CheckoutCompletedMail;
use App\Mail\NewOrderMail;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Database\Eloquent\Collection;
use Psr\Log\LoggerInterface;
use Stripe\Event;
use Stripe\StripeObject;

final class StripeWebhookService
{
    private const CHARGE_UPDATED = 'charge.updated';

    private const CHECKOUT_SESSION_COMPLETED = 'checkout.session.completed';

    public function __construct(
        private readonly WebhookGateway $webhookGateway,
        private readonly Mailer $mailer,
        private readonly Config $config,
        private readonly LoggerInterface $logger,
    ) {}

    public function constructEvent(string $payload, string $signature): Event
    {
        return $this->webhookGateway->constructEvent($payload, $signature);
    }

    public function handle(Event $event): void
    {
        match ($event->type) {
            self::CHARGE_UPDATED => $this->handleChargeUpdated($event->data->object),
            self::CHECKOUT_SESSION_COMPLETED => $this->handleCheckoutSessionCompleted($event->data->object),
            default => $this->logger->info('Received unknown Stripe event type '.$event->type),
        };
    }

    private function handleChargeUpdated(StripeObject $charge): void
    {
        $balanceTransaction = $this->webhookGateway->retrieveBalanceTransaction($charge['balance_transaction']);
        $platformFeePercent = $this->config->get('app.platform_fee_percent');

        $orders = Order::query()
            ->where('payment_intent', $charge['payment_intent'])
            ->get();

        foreach ($orders as $order) {
            $vendorShare = $order->total_price / $balanceTransaction->amount;

            $order->online_payment_commission = $vendorShare * $balanceTransaction->stripeFee;
            $order->website_commission = ($order->total_price - $order->online_payment_commission) / 100 * $platformFeePercent;
            $order->vendor_subtotal = $order->total_price - $order->online_payment_commission - $order->website_commission;
            $order->save();

            $this->mailer->to($order->vendorUser)->send(new NewOrderMail($order));
        }

        $this->mailer->to($orders[0]->user)->send(new CheckoutCompletedMail($orders));
    }

    private function handleCheckoutSessionCompleted(StripeObject $session): void
    {
        $orders = Order::query()
            ->with('orderItem.product')
            ->where('stripe_session_id', $session['id'])
            ->get();

        foreach ($orders as $order) {
            $order->payment_intent = $session['payment_intent'];
            $order->status = StatusEnum::PAID->value;
            $order->save();

            $order->orderItem->each(fn (OrderItem $orderItem) => $this->decreaseStock($orderItem));
        }

        $this->removePurchasedItemsFromCart($orders);
    }

    private function decreaseStock(OrderItem $orderItem): void
    {
        $product = $orderItem->product;
        $optionIds = $orderItem->variation_type_option_ids;

        if (! $optionIds) {
            if ($product->quantity !== null) {
                $product->quantity -= $orderItem->quantity;
                $product->save();
            }

            return;
        }

        sort($optionIds);

        $variation = $product->variations()
            ->whereJsonContains('variation_type_option_ids', $optionIds)
            ->first();

        if ($variation !== null && $variation->quantity !== null) {
            $variation->quantity -= $orderItem->quantity;
            $variation->save();
        }
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    private function removePurchasedItemsFromCart(Collection $orders): void
    {
        $customerId = $orders->last()?->user_id;

        if ($customerId === null) {
            return;
        }

        CartItem::query()
            ->where('user_id', $customerId)
            ->whereIn('product_id', $orders->flatMap->orderItem->pluck('product_id'))
            ->delete();
    }
}
