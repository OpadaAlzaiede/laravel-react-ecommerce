<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Contracts\Payments\CheckoutGateway;
use App\DTOs\Checkout\CheckoutSessionDto;
use Carbon\CarbonImmutable;
use Stripe\StripeClient;

final class StripeCheckoutGateway implements CheckoutGateway
{
    public const SESSION_LIFETIME_MINUTES = 30;

    public function __construct(private readonly StripeClient $stripe) {}

    public function createSession(string $customerEmail, array $lineItems, string $successUrl, string $cancelUrl): CheckoutSessionDto
    {
        $session = $this->stripe->checkout->sessions->create([
            'customer_email' => $customerEmail,
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'expires_at' => CarbonImmutable::now()->addMinutes(self::SESSION_LIFETIME_MINUTES)->getTimestamp(),
        ]);

        return new CheckoutSessionDto(
            id: $session->id,
            url: $session->url,
        );
    }
}
