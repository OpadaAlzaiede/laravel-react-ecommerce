<?php

declare(strict_types=1);

namespace App\Contracts\Payments;

use App\DTOs\Checkout\CheckoutSessionDto;

interface CheckoutGateway
{
    /**
     * @param  array<int, array<string, mixed>>  $lineItems
     */
    public function createSession(string $customerEmail, array $lineItems, string $successUrl, string $cancelUrl): CheckoutSessionDto;
}
