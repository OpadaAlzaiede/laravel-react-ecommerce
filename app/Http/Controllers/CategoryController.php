<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{

    public function index(Request $request)
    {
        $categories = Category::query()
            ->withCount('products')
            ->orderBy('products_count', 'DESC')
            ->paginate(12);

        return inertia('Category/Index', [
            'categories' => CategoryResource::collection($categories),
        ]);
    }

    public function show(Request $request, Category $category)
    {
        $category->load(['products' => function($query) use($request) {
            $query->where('title', 'LIKE', '%' . $request->get('search') . '%');
        }, 'products.user.vendor', 'products.currency']);

        return Inertia::render('Category/Show', [
            'category' => CategoryResource::make($category),
            'filters' => $request->only(['search']),
        ]);
    }
}
