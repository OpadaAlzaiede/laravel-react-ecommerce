<?php

declare(strict_types=1);

namespace App\DTOs\Orders;

use App\Enums\Orders\StatusEnum;
use Carbon\CarbonImmutable;

final readonly class OrderFilterDto
{
    public function __construct(
        public ?StatusEnum $status = null,
        public ?CarbonImmutable $startDate = null,
        public ?CarbonImmutable $endDate = null,
    ) {}

    public function hasDateRange(): bool
    {
        return $this->startDate !== null && $this->endDate !== null;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_filter([
            'status' => $this->status?->value,
            'start_date' => $this->startDate?->toDateString(),
            'end_date' => $this->endDate?->toDateString(),
        ], static fn (?string $value): bool => $value !== null);
    }
}
