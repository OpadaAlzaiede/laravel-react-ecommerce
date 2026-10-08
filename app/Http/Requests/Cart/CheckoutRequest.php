<?php

declare(strict_types=1);

namespace App\Http\Requests\Cart;

use App\DTOs\Checkout\CheckoutDto;
use Illuminate\Foundation\Http\FormRequest;

final class CheckoutRequest extends FormRequest
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
            'vendor_id' => ['nullable', 'integer'],
        ];
    }

    public function toDto(): CheckoutDto
    {
        $vendorId = $this->validated('vendor_id');

        return new CheckoutDto(
            vendorId: $vendorId === null ? null : (int) $vendorId,
        );
    }
}
