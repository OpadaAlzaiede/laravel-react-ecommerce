<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Common\SearchDto;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class VendorDirectoryService
{
    private const PER_PAGE = 12;

    public function paginateByProductCount(): LengthAwarePaginator
    {
        return User::query()
            ->with('vendor')
            ->withCount('products')
            ->whereHas('vendor')
            ->orderByDesc('products_count')
            ->paginate(self::PER_PAGE);
    }

    public function loadWithProducts(User $vendor, SearchDto $filters): User
    {
        return $vendor->load([
            'vendor',
            'products' => static fn (HasMany $query): HasMany => $query
                ->when($filters->search, static fn (Builder $query, string $search): Builder => $query
                    ->where('title', 'like', "%{$search}%")),
            'products.category',
            'products.currency',
        ]);
    }
}
