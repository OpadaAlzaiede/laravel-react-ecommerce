<x-mail::message>
# New contact message

**From:** {{ $contact->senderName }} ({{ $contact->senderEmail }})

**Subject:** {{ $contact->subject }}

<x-mail::panel>
{{ $contact->body }}
</x-mail::panel>
</x-mail::message>
