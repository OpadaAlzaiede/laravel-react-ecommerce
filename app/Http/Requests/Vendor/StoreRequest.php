<?php

declare(strict_types=1);

namespace App\Http\Requests\Vendor;

use App\DTOs\Vendors\VendorDetailsDto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreRequest extends FormRequest
{
    private const STORE_NAME_PATTERN = "/^[\\pL\\pN][\\pL\\pN &'.-]*$/u";

    private const STORE_NAME_MAX_LENGTH = 100;

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
            'store_name' => [
                'required',
                'string',
                'max:'.self::STORE_NAME_MAX_LENGTH,
                'regex:'.self::STORE_NAME_PATTERN,
                Rule::unique('vendors', 'store_name')->ignore($this->user()->id, 'user_id'),
            ],
            'store_address' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'store_name.regex' => 'The store name may only contain letters, numbers, spaces and & \' . -',
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
