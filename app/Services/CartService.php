<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Cart\CartItemDto;
use App\Exceptions\InsufficientStockException;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\VariationTypeOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CartService
{
    private const COOKIE_NAME = 'cartItems';

    private const COOKIE_LIFETIME = 60 * 24 * 365;

    /**
     * @var array<int, array<string, mixed>>|null
     */
    private ?array $cachedCartItems = null;

    public function addItemToCart(Product $product, CartItemDto $item): void
    {
        $optionIds = $item->optionIds ?: $product->getFirstOptionsMap();
        $price = (float) $product->getPriceForOptions($optionIds);

        $this->ensureInStock($product, $optionIds, $this->quantityInCart($product->id, $optionIds) + $item->quantity);

        if (Auth::check()) {
            $this->saveItemToDatabase($product->id, $item->quantity, $price, $optionIds);
        } else {
            $this->saveItemToCookies($product->id, $item->quantity, $price, $optionIds);
        }

        $this->cachedCartItems = null;
    }

    public function updateItemInCart(Product $product, CartItemDto $item): void
    {
        $this->ensureInStock($product, $item->optionIds, $item->quantity);

        if (Auth::check()) {
            $this->updateItemQuantityInDatabase($product->id, $item->quantity, $item->optionIds);
        } else {
            $this->updateItemQuantityInCookies($product->id, $item->quantity, $item->optionIds);
        }

        $this->cachedCartItems = null;
    }

    /**
     * @param  array<int, int>  $optionIds
     *
     * @throws InsufficientStockException
     */
    public function ensureInStock(Product $product, array $optionIds, int $quantity): void
    {
        $product->loadMissing('variations');
        $available = $product->getStockForOptions($optionIds);

        if ($available !== null && $quantity > $available) {
            throw InsufficientStockException::forProduct($product, $available);
        }
    }

    public function removeItemFromCart(Product $product, CartItemDto $item): void
    {
        if (Auth::check()) {
            $this->removeItemFromDatabase($product->id, $item->optionIds);
        } else {
            $this->removeItemFromCookies($product->id, $item->optionIds);
        }

        $this->cachedCartItems = null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCartItems(): array
    {
        if ($this->cachedCartItems !== null) {
            return $this->cachedCartItems;
        }

        $cartItems = Auth::check() ? $this->getCartItemsFromDatabase() : $this->getCartItemsFromCookies();

        $products = Product::with('user.vendor', 'currency', 'variations')
            ->whereIn('id', array_column($cartItems, 'product_id'))
            ->forWebsite()
            ->get()
            ->keyBy('id');

        $options = VariationTypeOption::with('variationType')
            ->whereIn('id', collect($cartItems)->flatMap(static fn (array $cartItem): array => array_values($cartItem['option_ids'])))
            ->get()
            ->keyBy('id');

        $cartItemData = [];

        foreach ($cartItems as $cartItem) {
            $product = $products->get($cartItem['product_id']);
            $cartItemOptions = collect($cartItem['option_ids'])->map(static fn (int|string $optionId): ?VariationTypeOption => $options->get((int) $optionId));

            if ($product === null || $cartItemOptions->contains(null)) {
                continue;
            }

            $cartItemData[] = [
                'id' => $cartItem['id'],
                'product_id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'price' => $product->getPriceForOptions($cartItem['option_ids']),
                'currency' => $product->currency->symbol,
                'quantity' => $cartItem['quantity'],
                'option_ids' => $cartItem['option_ids'],
                'options' => $cartItemOptions->map(static fn (VariationTypeOption $option): array => [
                    'id' => $option->id,
                    'name' => $option->name,
                    'type' => [
                        'id' => $option->variationType->id,
                        'name' => $option->variationType->name,
                    ],
                ])->values()->all(),
                'image' => $this->imageFor($product, $cartItemOptions),
                'user' => [
                    'id' => $product->created_by,
                    'name' => $product->user->vendor?->store_name ?? $product->user->name,
                ],
            ];
        }

        return $this->cachedCartItems = $cartItemData;
    }

    /**
     * @param  Collection<int, VariationTypeOption>  $options
     */
    private function imageFor(Product $product, Collection $options): string
    {
        foreach ($options as $option) {
            $imageUrl = $option->getFirstMediaUrl('images', 'small');

            if ($imageUrl !== '') {
                return $imageUrl;
            }
        }

        return $product->getFirstImageUrl('images', 'small');
    }

    public function getTotalQuantity(): int
    {
        $totalQuantity = 0;

        foreach ($this->getCartItems() as $cartItem) {
            $totalQuantity += $cartItem['quantity'];
        }

        return $totalQuantity;
    }

    public function getTotalPrice(): float
    {
        $totalPrice = 0;

        foreach ($this->getCartItems() as $cartItem) {
            $totalPrice += $cartItem['price'] * $cartItem['quantity'];
        }

        return $totalPrice;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCartItemsGrouped(): array
    {
        $cartItems = $this->getCartItems();

        return collect($cartItems)->groupBy(fn ($item) => $item['user']['id'])
            ->map(fn ($items, $userId) => [
                'user' => $items->first()['user'],
                'items' => $items->toArray(),
                'total_quantity' => $items->sum('quantity'),
                'total_price' => $items->sum(fn ($item) => $item['price'] * $item['quantity']),
            ])
            ->toArray();
    }

    /**
     * @param  array<int, int>  $optionIds
     */
    private function quantityInCart(int $productId, array $optionIds): int
    {
        $wantedOptionIds = array_values($optionIds);
        sort($wantedOptionIds);

        return (int) collect($this->getCartItems())
            ->filter(static function (array $cartItem) use ($productId, $wantedOptionIds): bool {
                $cartOptionIds = array_values($cartItem['option_ids']);
                sort($cartOptionIds);

                return $cartItem['product_id'] === $productId && $cartOptionIds == $wantedOptionIds;
            })
            ->sum('quantity');
    }

    public function moveCartItemsToDatabase(int $userId): void
    {
        $cartItems = $this->getCartItemsFromCookies();

        foreach ($cartItems as $cartItem) {
            $existingCartItem = CartItem::where('user_id', $userId)
                ->where('product_id', $cartItem['product_id'])
                ->where('variation_type_option_ids', json_encode($cartItem['option_ids']))
                ->first();

            if ($existingCartItem) {
                $existingCartItem->update([
                    'quantity' => $existingCartItem->quantity + $cartItem['quantity'],
                    'price' => $cartItem['price'],
                ]);
            } else {
                CartItem::create([
                    'user_id' => $userId,
                    'product_id' => $cartItem['product_id'],
                    'quantity' => $cartItem['quantity'],
                    'price' => $cartItem['price'],
                    'variation_type_option_ids' => $cartItem['option_ids'],
                ]);
            }
        }

        Cookie::queue(self::COOKIE_NAME, '', -1);

        $this->cachedCartItems = null;
    }

    /**
     * @param  array<int, int>  $optionIds
     */
    private function updateItemQuantityInDatabase(int $productId, int $quantity, array $optionIds): void
    {
        $userId = Auth::id();
        $cartItem = CartItem::where('user_id', $userId)
            ->where('product_id', $productId)
            ->whereJsonContains('variation_type_option_ids', $optionIds)
            ->first();

        if ($cartItem) {
            $cartItem->update([
                'quantity' => $quantity,
            ]);
        }
    }

    /**
     * @param  array<int, int>  $optionIds
     */
    private function updateItemQuantityInCookies(int $productId, int $quantity, array $optionIds): void
    {
        $cartItems = $this->getCartItemsFromCookies();
        ksort($optionIds);
        $itemKey = $productId.'_'.json_encode($optionIds);

        if (isset($cartItems[$itemKey])) {
            $cartItems[$itemKey]['quantity'] = $quantity;
        }

        Cookie::queue(self::COOKIE_NAME, json_encode($cartItems), self::COOKIE_LIFETIME);
    }

    /**
     * @param  array<int, int>  $optionIds
     */
    private function saveItemToDatabase(int $productId, int $quantity, float $price, array $optionIds): void
    {
        $userId = Auth::id();
        ksort($optionIds);

        $cartItem = CartItem::where('user_id', $userId)
            ->where('product_id', $productId)
            ->whereJsonContains('variation_type_option_ids', $optionIds)
            ->first();

        if ($cartItem) {
            $cartItem->update([
                'quantity' => DB::raw('quantity + '.$quantity),
            ]);
        } else {
            CartItem::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price,
                'variation_type_option_ids' => $optionIds,
            ]);
        }
    }

    /**
     * @param  array<int, int>  $optionIds
     */
    private function saveItemToCookies(int $productId, int $quantity, float $price, array $optionIds): void
    {
        $cartItems = $this->getCartItemsFromCookies();
        ksort($optionIds);

        $itemKey = $productId.'_'.json_encode($optionIds);

        if (isset($cartItems[$itemKey])) {
            $cartItems[$itemKey]['quantity'] += $quantity;
            $cartItems[$itemKey]['price'] = $price;
        } else {
            $cartItems[$itemKey] = [
                'id' => (string) Str::uuid(),
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $price,
                'option_ids' => $optionIds,
            ];
        }

        Cookie::queue(self::COOKIE_NAME, json_encode($cartItems), self::COOKIE_LIFETIME);
    }

    /**
     * @param  array<int, int>  $optionIds
     */
    private function removeItemFromDatabase(int $productId, array $optionIds): void
    {
        $userId = Auth::id();
        ksort($optionIds);

        CartItem::where('user_id', $userId)
            ->where('product_id', $productId)
            ->whereJsonContains('variation_type_option_ids', $optionIds)
            ->delete();
    }

    /**
     * @param  array<int, int>  $optionIds
     */
    private function removeItemFromCookies(int $productId, array $optionIds): void
    {
        $cartItems = $this->getCartItemsFromCookies();
        ksort($optionIds);
        $cartKey = $productId.'_'.json_encode($optionIds, JSON_NUMERIC_CHECK);

        unset($cartItems[$cartKey]);

        Cookie::queue(self::COOKIE_NAME, json_encode($cartItems), self::COOKIE_LIFETIME);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCartItemsFromDatabase(): array
    {
        $userId = Auth::id();

        return CartItem::where('user_id', $userId)->get()
            ->map(static function (CartItem $cartItem): array {
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

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getCartItemsFromCookies(): array
    {
        return json_decode((string) Cookie::get(self::COOKIE_NAME, '[]'), true) ?: [];
    }
}
