<?php

declare(strict_types=1);

namespace App\DTOs\Contact;

final readonly class ContactMessageDto
{
    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $subject,
        public string $body,
    ) {}
}
