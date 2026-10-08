<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\DTOs\Products\ProductFilterDto;
use App\Enums\Products\ProductSortEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IndexRequest extends FormRequest
{
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
            'search' => ['nullable', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', Rule::enum(ProductSortEnum::class)],
        ];
    }

    public function toDto(): ProductFilterDto
    {
        return new ProductFilterDto(
            search: $this->validated('search'),
            vendor: $this->validated('vendor'),
            sort: $this->enum('sort', ProductSortEnum::class) ?? ProductSortEnum::LATEST,
        );
    }
}
