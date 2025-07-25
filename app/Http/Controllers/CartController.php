<?php

namespace App\Http\Controllers;

use App\Enums\Orders\StatusEnum;
use App\Http\Requests\Cart\StoreRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\CartService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class CartController extends Controller
{
    public function __construct(protected CartService $cartService)
    {
        //
    }

    public function index()
    {
        return Inertia::render('Cart/Index', [
            'cartItems' => $this->cartService->getCartItemsGrouped(),
        ]);
    }

    public function store(StoreRequest $request, Product $product)
    {
        $data = $request->validated();

        $this->cartService->addItemToCart(
            $product,
            $data['quantity'],
            $data['option_ids'] ?: []
        );

        return back()->with('success', 'Product added to cart successfully!');
    }

    public function update(Request $request, Product $product)
    {
        $optionIds = $request->input('option_ids', []);
        $quantity = $request->input('quantity');

        $this->cartService->updateItemInCart($product, $quantity, $optionIds);

        return back()->with('success', 'Quantity updated successfully!');
    }

    public function destroy(Request $request, Product $product)
    {
        $optionIds = $request->input('option_ids');
        $this->cartService->removeItemFromCart($product, $optionIds);

        return back()->with('success', 'Product removed from cart successfully!');
    }

    public function checkout(Request $request)
    {
        Stripe::setApiKey(config('app.stripe_secret_key'));
        $vendorId = $request->input('vendor_id');
        $allCartItems = $this->cartService->getCartItemsGrouped();

        DB::beginTransaction();

        try {
            $checkoutCartItems = $allCartItems;
            if($vendorId){
                $checkoutCartItems = [$allCartItems[$vendorId]];
            }

            $orders = [];
            $lineItems = [];

            foreach($checkoutCartItems as $item) {
                $user = $item['user'];
                $cartItems = $item['items'];

                $order = Order::create([
                    'stripe_session_id' => null,
                    'user_id' => Auth::id(),
                    'vendor_user_id' => $user['id'],
                    'total_price' => $item['total_price'],
                    'status' => StatusEnum::DRAFT->value,
                ]);

                $orders[] = $order;

                foreach($cartItems as $cartItem) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $cartItem['product_id'],
                        'price' => $cartItem['price'],
                        'quantity' => $cartItem['quantity'],
                        'variation_type_option_ids' => $cartItem['option_ids']
                    ]);

                    $description = collect($cartItem['options'])
                                    ->map(function($item) {
                                        return $item['type']['name'] . ": " . $item['name'];
                                    })->implode(', ');

                    $lineItem = [
                        'price_data' => [
                            'currency' => config('app.currency'),
                            'product_data' => [
                                'name' => $cartItem['title'],
                                'images' => [$cartItem['image']],
                            ],
                            'unit_amount' => $cartItem['price'] * 100,
                        ],
                        'quantity' => $cartItem['quantity'],
                    ];

                    if($description) {
                        $lineItem['price_data']['product_data']['description'] = $description;
                    }

                    $lineItems[] = $lineItem;
                }
            }
            $session = Session::create([
                'customer_email' => Auth::user()->email,
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => route('stripe.success', []) . "?session_id={CHECKOUT_SESSION_ID}",
                'cancel_url' => route('stripe.failure', []),
            ]);

            foreach($orders as $order) {
                $order->stripe_session_id = $session->id;
                $order->save();
            }

            DB::commit();
            return redirect($session->url);
        } catch(Exception $e) {
            Log::error($e);
            DB::rollBack();
            return back()->with('error', $e->getMessage() ?: 'Something went wrong.');
        }
    }
}
