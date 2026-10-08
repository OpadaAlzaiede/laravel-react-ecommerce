<x-mail::message>
# New contact message

**From:** {{ $senderName }} ({{ $senderEmail }})

**Subject:** {{ $messageSubject }}

<x-mail::panel>
{{ $body }}
</x-mail::panel>
</x-mail::message>
