<?php

declare(strict_types=1);

namespace App\Http\Requests\Vendor;

use App\DTOs\Vendors\VendorDetailsDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreRequest extends FormRequest
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
            'store_name' => ['required', 'string', 'regex:/^[a-z0-9-]+$/', Rule::unique('vendors', 'store_name')->ignore($this->user()->id, 'user_id')],
            'store_address' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'store_name.regex' => 'The store name must be alphanumeric and dashes only.',
        ];
    }

    public function toDto(): VendorDetailsDto
    {
        return new VendorDetailsDto(
            storeName: $this->validated('store_name'),
            storeAddress: $this->validated('store_address'),
        );
    }
}
