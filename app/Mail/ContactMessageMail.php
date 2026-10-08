<?php

declare(strict_types=1);

namespace App\Mail;

use App\DTOs\Contact\ContactMessageDto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class ContactMessageMail extends Mailable
{
    use Queueable;

    public function __construct(public readonly ContactMessageDto $contact) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            replyTo: [new Address($this->contact->senderEmail, $this->contact->senderName)],
            subject: 'Contact: '.$this->contact->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact_message',
        );
    }
}
