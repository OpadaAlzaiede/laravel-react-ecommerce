<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

final class StoreRequest extends CartItemRequest
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

    protected function prepareForValidation(): void
    {
        $this->mergeIfMissing([
            'quantity' => 1,
        ]);
    }
}
