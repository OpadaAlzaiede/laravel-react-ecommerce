<?php

namespace App\Http\Controllers;

use Stripe\Webhook;
use Inertia\Inertia;
use App\Models\Order;
use App\Models\CartItem;
use Stripe\StripeClient;
use App\Mail\NewOrderMail;
use Illuminate\Http\Request;
use UnexpectedValueException;
use App\Enums\Orders\StatusEnum;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Http\Resources\OrderViewResource;
use App\Mail\CheckoutCompletedMail;
use Stripe\Exception\SignatureVerificationException;

class StripeController extends Controller
{
    public function success(Request $request)
    {
        $user = auth()->user();
        $sessionId = $request->get('session_id');
        $orders = Order::where('stripe_session_id', $sessionId)->get();

        if($orders->isEmpty()) {
            abort(404);
        }

        foreach($orders as $order) {
            if($order->user_id !== $user->id) {
                abort(403);
            }
        }

        return Inertia::render('Stripe/Success', [
            'orders' => OrderViewResource::collection($orders)->collection->toArray()
        ]);
    }

    public function failure()
    {
        return Inertia::render('Stripe/Failure');
    }

    public function webhook(Request $request)
    {
        $stripe = new StripeClient(config('app.stripe_secret_key'));
        $endpointSecret = config('app.stripe_webhook_secret');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $event = null;

        try {
            $event = Webhook::constructEvent(
                $payload, $sigHeader, $endpointSecret
            );
        } catch(UnexpectedValueException | SignatureVerificationException $e) {
            Log::error($e);
            return response('Invalid payload', 400);
        }

        switch ($event->type) {
            case 'charge.updated':
                $charge = $event->data->object;
                $transactionId = $charge['balance_transaction'];
                $paymentIntent = $charge['payment_intent'];
                $balanceTransaction = $stripe->balanceTransactions->retrieve($transactionId);

                $orders = Order::where('payment_intent', $paymentIntent)->get();
                $totalAmount = $balanceTransaction['amount'];
                $stripeFee = 0;
                foreach($balanceTransaction['fee_details'] as $fee_detail) {
                    if($fee_detail['type'] == 'stripe_fee') {
                        $stripeFee = $fee_detail['amount'];
                    }
                }
                $platformFeePercent = config('app.platform_fee_percent');
                foreach($orders as $order) {
                    $vendorShare = $order->total_price / $totalAmount;
                    $order->online_payment_commission = $vendorShare * $stripeFee;
                    $order->website_commission = ($order->total_price - $order->online_payment_commission) / 100 * $platformFeePercent;
                    $order->vendor_subtotal = $order->total_price - $order->online_payment_commission - $order->website_commission;
                    $order->save();

                    Mail::to($order->vendorUser)->send(new NewOrderMail($order));
                }

                Mail::to($orders[0]->user)->send(new CheckoutCompletedMail($orders));
                break;
            case 'checkout.session.completed':
                $session = $event->data->object;
                $pi = $session['payment_intent'];
                $orders = Order::query()->with(['orderItem'])
                            ->where(['stripe_session_id' => $session['id']])
                            ->get();

                $productsToDeleteFromCart = [];
                foreach($orders as $order) {
                    $order->payment_intent = $pi;
                    $order->status = StatusEnum::PAID;
                    $order->save();

                    $productsToDeleteFromCart = [
                        ...$productsToDeleteFromCart,
                        ...$order->orderItem->map(fn($item) => $item->product_id)->toArray()
                    ];

                    foreach($order->orderItem as $orderItem) {
                        $options = $orderItem->variation_type_option_ids;
                        $product = $orderItem->product;
                        if($options) {
                            sort($options);
                            $variation = $product->variations()->whereJsonContains('variation_type_option_ids', $options)->first();
                            if($variation && $variation->quantity !== null) {
                                $variation->quantity -= $orderItem->quantity;
                                $variation->save();
                            }
                        } else if($product->quantity !== null) {
                            $product->quantity -= $orderItem->quantity;
                            $product->save();
                        }
                    }
                }

                CartItem::query()
                    ->where('user_id', $order->user_id)
                    ->whereIn('product_id', $productsToDeleteFromCart)
                    ->delete();

                break;
            default:
                echo 'Received unknown event type ' . $event->type;
        }

        return response('Webhook processed successfully', 200);
    }

    public function connect()
    {
        if(! auth()->user()->getStripeAccountId()) {
            auth()->user()->createStripeAccount(['type' => 'express']);
        }

        if(! auth()->user()->isStripeAccountActive()) {
            return redirect(auth()->user()->getStripeAccountLink());
        }

        return back()->with('success', 'You are already connected to Stripe');
    }
}
