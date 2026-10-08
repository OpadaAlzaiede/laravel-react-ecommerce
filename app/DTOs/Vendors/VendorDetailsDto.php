<?php

declare(strict_types=1);

namespace App\DTOs\Vendors;

final readonly class VendorDetailsDto
{
    public function __construct(
        public string $storeName,
        public ?string $storeAddress = null,
    ) {}
}
