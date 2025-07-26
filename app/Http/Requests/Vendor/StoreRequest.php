<?php

namespace App\Http\Requests\Vendor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'regex:/^[a-z0-9-]+$/', Rule::unique('vendors', 'store_name')->ignore($this->user()->id, 'user_id')],
            'store_address' => ['nullable', 'string'],
        ];
    }


    public function messages(): array
    {
        return [
            'store_name.regex' => 'The store name must be alphanumeric and dashes only.',
        ];
    }
}
