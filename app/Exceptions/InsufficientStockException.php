<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Models\Product;
use RuntimeException;

final class InsufficientStockException extends RuntimeException
{
    public static function forProduct(Product $product, int $available): self
    {
        return new self($available > 0
            ? "Only {$available} of {$product->title} left in stock."
            : "{$product->title} is out of stock.");
    }
}
