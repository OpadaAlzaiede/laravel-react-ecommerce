<?php

namespace App\Services;

use App\Models\Product;
use App\Models\CartItem;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\VariationTypeOption;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class CartService
{
    private ?array $cachedCartItems = null;
    protected const COOKIE_NAME = 'cartItems';
    protected const COOKIE_LIFETIME = 60 * 24 * 365;

    public function addItemToCart(Product $product, int $quantity = 1, $optionIds = null)
    {
        if(is_null($optionIds))
        {
            $optionIds = $product->getFirstOptionsMap();
        }

        $price = $product->getPriceForOptions($optionIds);

        if(Auth::check()) {
            $this->saveItemToDatabase($product->id, $quantity, $price, $optionIds);
        } else {
            $this->saveItemToCookies($product->id, $quantity, $price, $optionIds);
        }
    }

    public function updateItemInCart(Product $product, int $quantity = 1, $optionIds = null)
    {
        if(Auth::check()) {
            $this->updateItemQuantityInDatabase($product->id, $quantity, $optionIds);
        } else {
            $this->updateItemQuantityInCookies($product->id, $quantity, $optionIds);
        }
    }

    public function removeItemFromCart(Product $product, $optionIds = null)
    {
        if(Auth::check()) {
            $this->removeItemFromDatabase($product->id, $optionIds);
        } else {
            $this->removeItemFromCookies($product->id, $optionIds);
        }
    }

    public function getCartItems(): array
    {
        try {

            if(is_null($this->cachedCartItems)) {

                if(Auth::check()) {
                    $cartItems = $this->getCartItemsFromDatabase();
                } else {
                    $cartItems = $this->getCartItemsFromCookies();
                }

                $productIds = collect($cartItems)->map(fn($item) => $item['product_id']);
                $products = Product::with('user.vendor', 'currency')
                            ->whereIn('id', $productIds)
                            ->forWebsite()
                            ->get()
                            ->keyBy('id');

                $cartItemData = [];

                foreach($cartItems as $key => $cartItem) {
                    $product = data_get($products, $cartItem['product_id']);
                    if(! $product) continue;

                    $optionInfo = [];
                    $options = VariationTypeOption::with('variationType')
                        ->whereIn('id', $cartItem['option_ids'])
                        ->get()
                        ->keyBy('id');

                    $imageUrl = null;


                    foreach($cartItem['option_ids'] as $optionId) {
                        $option = data_get($options, $optionId);
                        if(! $imageUrl) {
                            $imageUrl = $option->getFirstMediaUrl('images', 'small');
                        }
                        $optionInfo[] = [
                            'id' => $option->id,
                            'name' => $option->name,
                            'type' => [
                                'id' => $option->variationType->id,
                                'name' => $option->variationType->name,
                            ]
                        ];
                    }

                    $cartItemData[] = [
                        'id' => $cartItem['id'],
                        'product_id' => $product->id,
                        'title' => $product->title,
                        'slug' => $product->slug,
                        'price' => $cartItem['price'],
                        'currency' => $product->currency->symbol,
                        'quantity' => $cartItem['quantity'],
                        'option_ids' => $cartItem['option_ids'],
                        'options' => $optionInfo,
                        'image' => $imageUrl ?: $product->getFirstImageUrl('images', 'small'),
                        'user' => [
                            'id' => $product->created_by,
                            'name' => $product->user->vendor->store_name,
                        ]
                    ];
                }

                $this->cachedCartItems = $cartItemData;
            }

            return $this->cachedCartItems;
        } catch(\Exception $e) {
            Log::error($e->getMessage() . PHP_EOL . $e->getTraceAsString());
        }

        return [];
    }

    public function getTotalQuantity(): int
    {
        $totalQuantity = 0;

        foreach($this->getCartItems() as $cartItem) {
            $totalQuantity += $cartItem['quantity'];
        }

        return $totalQuantity;
    }

    public function getTotalPrice(): float
    {
        $totalPrice = 0;

        foreach($this->getCartItems() as $cartItem) {
            $totalPrice += $cartItem['price'] * $cartItem['quantity'];
        }

        return $totalPrice;
    }

    public function getCartItemsGrouped()
    {
        $cartItems = $this->getCartItems();

        return collect($cartItems)->groupBy(fn($item) => $item['user']['id'])
                ->map(fn($items, $userId) => [
                    'user' => $items->first()['user'],
                    'items' => $items->toArray(),
                    'total_quantity' => $items->sum('quantity'),
                    'total_price' => $items->sum(fn($item) => $item['price'] * $item['quantity'])
                ])
                ->toArray();
    }

    public function moveCartItemsToDatabase($userId): void
    {
        $cartItems = $this->getCartItemsFromCookies();

        foreach($cartItems as $itemKey => $cartItem) {

            $existingCartItem = CartItem::where('user_id', $userId)
                ->where('product_id', $cartItem['product_id'])
                ->where('variation_type_option_ids', json_encode($cartItem['option_ids']))
                ->first();

            if($existingCartItem) {
                $existingCartItem->update([
                    'quantity' => $existingCartItem->quantity + $cartItem['quantity'],
                    'price' => $cartItem['price']
                ]);
            } else {
                CartItem::create([
                    'user_id' => $userId,
                    'product_id' => $cartItem['product_id'],
                    'quantity' => $cartItem['quantity'],
                    'price' => $cartItem['price'],
                    'variation_type_option_ids' => $cartItem['option_ids']
                ]);
            }
        }

        Cookie::queue(self::COOKIE_NAME, '', -1);
    }

    protected function updateItemQuantityInDatabase(int $productId, int $quantity, $optionIds): void
    {
        $userId = Auth::id();
        $cartItem = CartItem::where('user_id', $userId)
                        ->where('product_id', $productId)
                        ->whereJsonContains('variation_type_option_ids', $optionIds)
                        ->first();

        if($cartItem) {
            $cartItem->update([
                'quantity' => $quantity,
            ]);
        }
    }

    protected function updateItemQuantityInCookies(int $productId, int $quantity, $optionIds): void
    {
        $cartItems = $this->getCartItemsFromCookies();
        ksort($optionIds);
        $itemKey = $productId . '_' . json_encode($optionIds);

        if(isset($cartItems[$itemKey])) {
            $cartItems[$itemKey]['quantity'] = $quantity;
        }

        Cookie::queue(self::COOKIE_NAME, json_encode($cartItems), self::COOKIE_LIFETIME);
    }

    protected function saveItemToDatabase(int $productId, int $quantity, float $price, $optionIds): void
    {
        $userId = Auth::id();
        ksort($optionIds);

        $cartItem = CartItem::where('user_id', $userId)
                            ->where('product_id', $productId)
                            ->whereJsonContains('variation_type_option_ids', $optionIds)
                            ->first();

        if($cartItem) {
            $cartItem->update([
                'quantity' => DB::raw('quantity + ' . $quantity)
            ]);
        } else {
            CartItem::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price,
                'variation_type_option_ids' => $optionIds
            ]);
        }
    }

    protected function saveItemToCookies(int $productId, int $quantity, float $price, $optionIds): void
    {
        $cartItems = $this->getCartItemsFromCookies();
        ksort($optionIds);

        $itemKey = $productId . '_' . json_encode($optionIds);

        if(isset($cartItems[$itemKey])) {
            $cartItems[$itemKey]['quantity'] += $quantity;
            $cartItems[$itemKey]['price'] = $price;
        } else {
            $cartItems[$itemKey] = [
                'id' => Str::uuid(),
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price,
                'option_ids' => $optionIds
            ];
        }

        Cookie::queue(self::COOKIE_NAME, json_encode($cartItems), self::COOKIE_LIFETIME);
    }

    protected function removeItemFromDatabase(int $productId, $optionIds): void
    {
        $userId = Auth::id();
        ksort($optionIds);

        CartItem::where('user_id', $userId)
                ->where('product_id', $productId)
                ->whereJsonContains('variation_type_option_ids', $optionIds)
                ->delete();
    }

    protected function removeItemFromCookies(int $productId, $optionIds): void
    {
        $cartItems = $this->getCartItemsFromCookies();
        ksort($optionIds);
        $cartKey = $productId . '_' . json_encode($optionIds, JSON_NUMERIC_CHECK);

        unset($cartItems[$cartKey]);

        Cookie::queue(self::COOKIE_NAME, json_encode($cartItems), self::COOKIE_LIFETIME);
    }

    protected function getCartItemsFromDatabase()
    {
        $userId = Auth::id();

        return CartItem::where('user_id', $userId)->get()
                    ->map(function($cartItem) {
                        return [
                            'id' => $cartItem->id,
                            'product_id' => $cartItem->product_id,
                            'quantity' => $cartItem->quantity,
                            'price' => $cartItem->price,
                            'option_ids' => $cartItem->variation_type_option_ids,
                        ];
                    })
                    ->toArray();
    }

    private function getCartItemsFromCookies()
    {
        return json_decode(Cookie::get(self::COOKIE_NAME, '[]'), true);
    }
}
