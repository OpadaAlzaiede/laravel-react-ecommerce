<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Product\IndexRequest;
use App\Http\Requests\Product\ShowRequest;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Inertia\Inertia;
use Inertia\Response;

final class ProductController extends Controller
{
    public function __construct(private readonly ProductService $productService) {}

    public function index(IndexRequest $request): Response
    {
        $filters = $request->toDto();

        return Inertia::render('Product/Index', [
            'products' => ProductListResource::collection($this->productService->paginateForWebsite($filters)),
            'filters' => $filters->toArray(),
        ]);
    }

    public function show(ShowRequest $request, Product $product): Response
    {
        return Inertia::render('Product/Show', [
            'product' => ProductResource::make($this->productService->loadForDisplay($product)),
            'variationOptions' => $request->selectedOptions(),
        ]);
    }
}
