<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Products\ProductFilterDto;
use App\Enums\Products\ProductSortEnum;
use App\Enums\Products\ProductStatusEnum;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class ProductService
{
    private const PER_PAGE = 12;

    public function paginateForWebsite(ProductFilterDto $filters): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['department', 'currency', 'user', 'user.vendor'])
            ->when($filters->search, static fn (Builder $query, string $search): Builder => $query
                ->where('title', 'like', "%{$search}%"))
            ->when($filters->vendor, static fn (Builder $query, string $vendor): Builder => $query
                ->whereHas('user.vendor', static fn (Builder $vendorQuery): Builder => $vendorQuery
                    ->where('store_name', 'like', "%{$vendor}%")));

        $this->applySort($query, $filters->sort);

        return $query
            ->forWebsite()
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /**
     * @throws ModelNotFoundException<Product>
     */
    public function loadForDisplay(Product $product): Product
    {
        if ($product->status !== ProductStatusEnum::PUBLISHED->value) {
            throw (new ModelNotFoundException)->setModel(Product::class, [$product->id]);
        }

        return $product->load([
            'department',
            'category',
            'currency',
            'user.vendor',
            'variationTypes',
            'variations',
        ]);
    }

    private function applySort(Builder $query, ProductSortEnum $sort): void
    {
        match ($sort) {
            ProductSortEnum::PRICE_LOW => $query->orderBy('price'),
            ProductSortEnum::PRICE_HIGH => $query->orderByDesc('price'),
            ProductSortEnum::OLDEST => $query->oldest(),
            ProductSortEnum::LATEST => $query->latest(),
        };
    }
}
