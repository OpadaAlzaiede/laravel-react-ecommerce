<?php

declare(strict_types=1);

namespace App\Http\Requests\Contact;

use App\DTOs\Contact\ContactMessageDto;
use Illuminate\Foundation\Http\FormRequest;

final class SendRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    public function toDto(): ContactMessageDto
    {
        return new ContactMessageDto(
            senderName: $this->validated('name'),
            senderEmail: $this->validated('email'),
            subject: $this->validated('subject'),
            body: $this->validated('message'),
        );
    }
}
