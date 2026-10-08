<?php

declare(strict_types=1);

namespace App\Http\Requests\Order;

use App\DTOs\Orders\OrderFilterDto;
use App\Enums\Orders\StatusEnum;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexRequest extends FormRequest
{
    private const DATE_FORMAT = 'Y-m-d';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(StatusEnum::class)],
            'start_date' => ['nullable', 'date_format:'.self::DATE_FORMAT],
            'end_date' => ['nullable', 'date_format:'.self::DATE_FORMAT, 'after_or_equal:start_date'],
        ];
    }

    public function toDto(): OrderFilterDto
    {
        return new OrderFilterDto(
            status: $this->enum('status', StatusEnum::class),
            startDate: $this->dateOrNull('start_date'),
            endDate: $this->dateOrNull('end_date'),
        );
    }

    private function dateOrNull(string $key): ?CarbonImmutable
    {
        $value = $this->validated($key);

        return $value === null
            ? null
            : CarbonImmutable::createFromFormat(self::DATE_FORMAT, $value)->startOfDay();
    }
}
