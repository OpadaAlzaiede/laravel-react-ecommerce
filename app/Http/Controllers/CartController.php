<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Exceptions\InsufficientStockException;
use App\Http\Requests\Cart\CheckoutRequest;
use App\Http\Requests\Cart\DestroyRequest;
use App\Http\Requests\Cart\StoreRequest;
use App\Http\Requests\Cart\UpdateRequest;
use App\Models\Product;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Log\LoggerInterface;
use Throwable;

final class CartController extends Controller
{
    private const CHECKOUT_FAILED_MESSAGE = 'We could not start the payment. Please try again.';

    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutService $checkoutService,
        private readonly LoggerInterface $logger,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Cart/Index', [
            'cartItems' => $this->cartService->getCartItemsGrouped(),
        ]);
    }

    public function store(StoreRequest $request, Product $product): RedirectResponse
    {
        try {
            $this->cartService->addItemToCart($product, $request->toDto());
        } catch (InsufficientStockException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Product added to cart successfully!');
    }

    public function update(UpdateRequest $request, Product $product): RedirectResponse
    {
        try {
            $this->cartService->updateItemInCart($product, $request->toDto());
        } catch (InsufficientStockException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Quantity updated successfully!');
    }

    public function destroy(DestroyRequest $request, Product $product): RedirectResponse
    {
        $this->cartService->removeItemFromCart($product, $request->toDto());

        return back()->with('success', 'Product removed from cart successfully!');
    }

    public function checkout(CheckoutRequest $request): RedirectResponse
    {
        try {
            $checkoutUrl = $this->checkoutService->checkout($request->user(), $request->toDto());
        } catch (CheckoutException|InsufficientStockException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);

            return back()->with('error', self::CHECKOUT_FAILED_MESSAGE);
        }

        return redirect()->away($checkoutUrl);
    }
}
