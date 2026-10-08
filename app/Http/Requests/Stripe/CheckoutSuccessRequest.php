<?php

declare(strict_types=1);

namespace App\Http\Requests\Stripe;

use Illuminate\Foundation\Http\FormRequest;

final class CheckoutSuccessRequest extends FormRequest
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
            'session_id' => ['required', 'string', 'max:255'],
        ];
    }

    public function sessionId(): string
    {
        return $this->validated('session_id');
    }
}
