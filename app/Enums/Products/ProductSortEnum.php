<?php

declare(strict_types=1);

namespace App\Enums\Products;

enum ProductSortEnum: string
{
    case LATEST = 'latest';
    case OLDEST = 'oldest';
    case PRICE_LOW = 'price_low';
    case PRICE_HIGH = 'price_high';
}
