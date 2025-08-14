<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Product;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductListResource;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    public function index(Request $request)
    {
        $query = Product::query()
                ->with(['department', 'currency' , 'user', 'user.vendor'])
                ->when($request->get('search'), function($query) use($request) {
                    $query->where('title', 'LIKE', '%' . $request->search . '%');
                })
                ->when($request->get('vendor'), function($query) use($request) {
                    $query->whereHas('user.vendor', function ($q) use ($request) {
                        $q->where('store_name', 'like', '%' . $request->vendor . '%');
                    });
                });

        // Sort
        $sort = $request->get('sort', 'latest');

        match ($sort) {
            'price_low' => $query->orderBy('price', 'asc'),
            'price_high' => $query->orderBy('price', 'desc'),
            'oldest' => $query->oldest(),
            default => $query->latest(),
        };

        $products = $query->forWebsite()->paginate(12)->withQueryString();

        return inertia('Product/Index', [
            'products' => ProductListResource::collection($products),
            'filters' => $request->only(['search', 'vendor', 'sort']),
        ]);
    }

    public function show(Product $product)
    {
        $product->load('department', 'category', 'currency', 'user.vendor', 'variationTypes', 'variations');

        return Inertia::render('Product/Show', [
            'product' => ProductResource::make($product),
            'variationOptions' => request('options', []),
        ]);
    }
}
