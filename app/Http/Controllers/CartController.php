<?php

namespace App\Http\Controllers;

use App\Http\Requests\Cart\StoreRequest;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

    public function checkout()
    {

    }
}
