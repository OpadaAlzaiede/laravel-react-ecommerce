<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

final class ShowRequest extends FormRequest
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
            'options' => ['nullable', 'array'],
            'options.*' => ['integer'],
        ];
    }

    /**
     * @return array<int|string, int>
     */
    public function selectedOptions(): array
    {
        return array_map('intval', $this->validated('options') ?? []);
    }
}
