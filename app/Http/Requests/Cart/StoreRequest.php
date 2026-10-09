<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

use App\Rules\CompleteOptionSelection;

final class StoreRequest extends CartItemRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        return [
            ...$rules,
            'option_ids' => [...$rules['option_ids'], new CompleteOptionSelection($this->route('product'))],
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
