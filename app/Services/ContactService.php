<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Contact\ContactMessageDto;
use App\Mail\ContactMessageMail;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Mail\Mailer;

final class ContactService
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly Config $config,
    ) {}

    public function send(ContactMessageDto $contact): void
    {
        $this->mailer
            ->to($this->config->get('mail.from.address'))
            ->send(new ContactMessageMail($contact));
    }
}
