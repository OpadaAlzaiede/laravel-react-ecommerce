<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Common\SearchDto;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class VendorDirectoryService
{
    private const PER_PAGE = 12;

    public function paginateByProductCount(): LengthAwarePaginator
    {
        return User::query()
            ->with('vendor')
            ->withCount(['products' => static fn (Builder $query): Builder => $query->forWebsite()])
            ->whereHas('vendor', static fn (Builder $query): Builder => $query->approved())
            ->orderByDesc('products_count')
            ->paginate(self::PER_PAGE);
    }

    /**
     * @throws ModelNotFoundException<User>
     */
    public function loadWithProducts(User $vendor, SearchDto $filters): User
    {
        if (! $vendor->vendor()->approved()->exists()) {
            throw (new ModelNotFoundException)->setModel(User::class, [$vendor->id]);
        }

        return $vendor->load([
            'vendor',
            'products' => static fn (HasMany $query): HasMany => $query
                ->forWebsite()
                ->when($filters->search, static fn (Builder $query, string $search): Builder => $query
                    ->where('title', 'like', "%{$search}%")),
            'products.category',
            'products.currency',
        ]);
    }
}
