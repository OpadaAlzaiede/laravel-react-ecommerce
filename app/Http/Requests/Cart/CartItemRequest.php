<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

use App\DTOs\Cart\CartItemDto;
use Illuminate\Foundation\Http\FormRequest;

abstract class CartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'option_ids' => ['nullable', 'array'],
            'option_ids.*' => ['integer'],
        ];
    }

    public function toDto(): CartItemDto
    {
        return new CartItemDto(
            optionIds: array_map('intval', $this->validated('option_ids') ?? []),
            quantity: (int) ($this->validated('quantity') ?? 1),
        );
    }
}
