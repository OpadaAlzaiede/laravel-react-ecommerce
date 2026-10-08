<?php

declare(strict_types=1);

namespace App\DTOs\Home;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final readonly class HomePageDto
{
    /**
     * @param  Collection<int, Product>  $newProducts
     * @param  LengthAwarePaginator<int, Product>  $products
     * @param  Collection<int, Product>  $featuredProducts
     * @param  Collection<int, Category>  $categories
     * @param  Collection<int, User>  $vendors
     */
    public function __construct(
        public Collection $newProducts,
        public LengthAwarePaginator $products,
        public Collection $featuredProducts,
        public Collection $categories,
        public Collection $vendors,
    ) {}
}
