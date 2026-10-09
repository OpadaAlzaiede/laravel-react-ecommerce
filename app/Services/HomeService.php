<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Home\HomePageDto;
use App\Enums\Roles\RoleEnum;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class HomeService
{
    private const NEW_PRODUCTS_LIMIT = 4;

    private const PRODUCTS_PER_PAGE = 12;

    private const FEATURED_PRODUCTS_LIMIT = 10;

    private const CATEGORIES_LIMIT = 8;

    public function getHomePage(): HomePageDto
    {
        return new HomePageDto(
            newProducts: $this->websiteProducts()
                ->orderByDesc('created_at')
                ->limit(self::NEW_PRODUCTS_LIMIT)
                ->get(),
            products: $this->websiteProducts()
                ->paginate(self::PRODUCTS_PER_PAGE),
            featuredProducts: $this->websiteProducts()
                ->where('is_featured', true)
                ->limit(self::FEATURED_PRODUCTS_LIMIT)
                ->get(),
            categories: Category::query()
                ->withCount(['products' => static fn (Builder $query): Builder => $query->forWebsite()])
                ->orderByDesc('products_count')
                ->limit(self::CATEGORIES_LIMIT)
                ->get(),
            vendors: User::query()
                ->with('vendor')
                ->whereHas('roles', static fn (Builder $query): Builder => $query
                    ->where('name', RoleEnum::VENDOR->value))
                ->get(),
        );
    }

    private function websiteProducts(): Builder
    {
        return Product::query()
            ->withListingData()
            ->forWebsite();
    }
}
