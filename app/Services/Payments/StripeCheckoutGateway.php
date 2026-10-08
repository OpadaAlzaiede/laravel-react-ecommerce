<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Contracts\Payments\CheckoutGateway;
use App\DTOs\Checkout\CheckoutSessionDto;
use Stripe\StripeClient;

final class StripeCheckoutGateway implements CheckoutGateway
{
    public function __construct(private readonly StripeClient $stripe) {}

    public function createSession(string $customerEmail, array $lineItems, string $successUrl, string $cancelUrl): CheckoutSessionDto
    {
        $session = $this->stripe->checkout->sessions->create([
            'customer_email' => $customerEmail,
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);

        return new CheckoutSessionDto(
            id: $session->id,
            url: $session->url,
        );
    }
}
