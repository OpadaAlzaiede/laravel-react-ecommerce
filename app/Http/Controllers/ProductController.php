<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductListResource;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::query()
                ->with(['department', 'currency' , 'user'])
                ->published()
                ->paginate(12);

        return Inertia::render('Home', [
            'products' => ProductListResource::collection($products),
        ]);
    }

    public function show(Product $product)
    {
        $product->load('department', 'category', 'currency', 'user', 'variationTypes', 'variations');

        return Inertia::render('Product/Show', [
            'product' => ProductResource::make($product),
            'variationOptions' => request('options', []),
        ]);
    }
}
