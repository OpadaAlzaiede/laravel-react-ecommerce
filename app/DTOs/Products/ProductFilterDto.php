<?php

declare(strict_types=1);

namespace App\DTOs\Products;

use App\Enums\Products\ProductSortEnum;

final readonly class ProductFilterDto
{
    public function __construct(
        public ?string $search = null,
        public ?string $vendor = null,
        public ProductSortEnum $sort = ProductSortEnum::LATEST,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
            'vendor' => $this->vendor,
            'sort' => $this->sort->value,
        ], static fn (?string $value): bool => $value !== null);
    }
}
