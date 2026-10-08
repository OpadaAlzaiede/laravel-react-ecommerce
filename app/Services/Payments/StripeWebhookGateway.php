<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Contracts\Payments\WebhookGateway;
use App\DTOs\Payments\BalanceTransactionDto;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Webhook;

final class StripeWebhookGateway implements WebhookGateway
{
    private const STRIPE_FEE_TYPE = 'stripe_fee';

    public function __construct(
        private readonly StripeClient $stripe,
        private readonly ?string $endpointSecret,
    ) {}

    public function constructEvent(string $payload, string $signature): Event
    {
        return Webhook::constructEvent($payload, $signature, (string) $this->endpointSecret);
    }

    public function retrieveBalanceTransaction(string $transactionId): BalanceTransactionDto
    {
        $balanceTransaction = $this->stripe->balanceTransactions->retrieve($transactionId);

        $stripeFee = 0;

        foreach ($balanceTransaction->fee_details as $feeDetail) {
            if ($feeDetail->type === self::STRIPE_FEE_TYPE) {
                $stripeFee = $feeDetail->amount;
            }
        }

        return new BalanceTransactionDto(
            amount: $balanceTransaction->amount,
            stripeFee: $stripeFee,
        );
    }
}
