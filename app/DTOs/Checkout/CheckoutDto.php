<?php

declare(strict_types=1);

namespace App\DTOs\Checkout;

final readonly class CheckoutDto
{
    public function __construct(
        public ?int $vendorId = null,
    ) {}
}
