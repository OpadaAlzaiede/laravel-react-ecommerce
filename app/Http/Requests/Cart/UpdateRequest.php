<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

final class UpdateRequest extends CartItemRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
