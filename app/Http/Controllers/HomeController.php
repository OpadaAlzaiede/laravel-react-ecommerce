<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use App\Models\Product;
use App\Models\Category;
use App\Enums\Roles\RoleEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\VendorUserResource;
use App\Http\Resources\ProductListResource;

class HomeController extends Controller
{
    public function home()
    {
        $newProducts = Product::query()
            ->with(['department', 'currency' , 'user', 'user.vendor'])
            ->forWebsite()
            ->orderBy('created_at', 'desc')
            ->limit(4)
            ->get();

        $products = Product::query()
                ->with(['department', 'currency' , 'user', 'user.vendor'])
                ->forWebsite()
                ->paginate(12);

        $featuredProducts = Product::query()
            ->with(['department', 'currency' , 'user', 'user.vendor'])
            ->forWebsite()
            ->where('is_featured', true)
            ->limit(10)
            ->get();

        $categories = Category::withCount('products')->orderBy('products_count', 'desc')->limit(8)->get();

        $vendors = User::with('vendor')->whereHas('roles', fn($query) => $query->where('name', RoleEnum::VENDOR->value))->get();

        return Inertia::render('Home', [
            'products' => ProductListResource::collection($products),
            'newProducts' => ProductListResource::collection($newProducts),
            'featuredProducts' => ProductListResource::collection($featuredProducts),
            'categories' => $categories,
            'vendors' => VendorUserResource::collection($vendors),
        ]);
    }

    public function about()
    {
        return Inertia::render('About');
    }

    public function contact()
    {
        return Inertia::render('Contact');
    }
}
