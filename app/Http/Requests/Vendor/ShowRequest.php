<?php

declare(strict_types=1);

namespace App\Http\Requests\Vendor;

use App\DTOs\Common\SearchDto;
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
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function toDto(): SearchDto
    {
        return new SearchDto(
            search: $this->validated('search'),
        );
    }
}
