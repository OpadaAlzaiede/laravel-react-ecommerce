<?php

declare(strict_types=1);

namespace App\DTOs\Payments;

final readonly class BalanceTransactionDto
{
    public function __construct(
        public int $amount,
        public int $stripeFee,
    ) {}
}
