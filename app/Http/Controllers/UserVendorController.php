<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Resources\VendorUserResource;
use Inertia\Inertia;

class UserVendorController extends Controller
{
    public function index(Request $request)
    {
        $vendors = User::query()
                    ->with(['vendor'])
                    ->withCount('products')
                    ->whereHas('vendor')
                    ->orderBy('products_count', 'desc')
                    ->paginate(12);

        return Inertia::render('Vendor/Index', [
            'vendors' => VendorUserResource::collection($vendors),
            'filters' => $request->only(['search']),
        ]);
    }

    public function show(Request $request, User $vendor)
    {
        $vendor->load(['vendor', 'products' => function($query) use($request) {
            $query->where('title', 'LIKE', '%' . $request->get('search') . '%');
        },'products.category', 'products.currency']);

        return Inertia::render('Vendor/Show', [
            'vendor' => VendorUserResource::make($vendor),
            'filters' => $request->only(['search']),
        ]);
    }
}
