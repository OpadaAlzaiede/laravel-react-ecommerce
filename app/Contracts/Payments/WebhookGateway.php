<?php

declare(strict_types=1);

namespace App\Contracts\Payments;

use App\DTOs\Payments\BalanceTransactionDto;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

interface WebhookGateway
{
    /**
     * @throws SignatureVerificationException
     * @throws UnexpectedValueException
     */
    public function constructEvent(string $payload, string $signature): Event;

    public function retrieveBalanceTransaction(string $transactionId): BalanceTransactionDto;
}
