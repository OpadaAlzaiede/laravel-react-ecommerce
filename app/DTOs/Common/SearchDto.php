<?php

declare(strict_types=1);

namespace App\DTOs\Common;

final readonly class SearchDto
{
    public function __construct(
        public ?string $search = null,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_filter([
            'search' => $this->search,
        ], static fn (?string $value): bool => $value !== null);
    }
}
