<?php

declare(strict_types=1);

namespace App\DTOs\Checkout;

final readonly class CheckoutSessionDto
{
    public function __construct(
        public string $id,
        public string $url,
    ) {}
}
