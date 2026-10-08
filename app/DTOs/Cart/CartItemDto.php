<?php

declare(strict_types=1);

namespace App\DTOs\Cart;

final readonly class CartItemDto
{
    /**
     * @param  array<int, int>  $optionIds  variation type id => option id
     */
    public function __construct(
        public array $optionIds = [],
        public int $quantity = 1,
    ) {}
}
