<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Common\SearchDto;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CategoryService
{
    private const PER_PAGE = 12;

    public function paginateByProductCount(): LengthAwarePaginator
    {
        return Category::query()
            ->withCount(['products' => static fn (Builder $query): Builder => $query->forWebsite()])
            ->orderByDesc('products_count')
            ->paginate(self::PER_PAGE);
    }

    public function loadWithProducts(Category $category, SearchDto $filters): Category
    {
        return $category->load([
            'products' => static fn (HasMany $query): HasMany => $query
                ->forWebsite()
                ->withListingData()
                ->when($filters->search, static fn (Builder $query, string $search): Builder => $query
                    ->where('title', 'like', "%{$search}%")),
            'products.user.vendor',
            'products.currency',
        ]);
    }
}
