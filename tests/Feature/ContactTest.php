<?php

use App\DTOs\Contact\ContactMessageDto;
use App\Mail\ContactMessageMail;
use Illuminate\Support\Facades\Mail;

test('the contact form sends the message to the store inbox', function () {
    Mail::fake();

    $this->from(route('contact'))
        ->post(route('contact.send'), [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'subject' => 'Order question',
            'message' => 'Where is my order?',
        ])
        ->assertRedirect(route('contact'))
        ->assertSessionHas('success');

    Mail::assertSent(ContactMessageMail::class, function (ContactMessageMail $mail) {
        return $mail->hasTo(config('mail.from.address'))
            && $mail->hasReplyTo('jane@example.com')
            && $mail->contact->subject === 'Order question'
            && $mail->contact->body === 'Where is my order?';
    });
});

test('the contact form validates its fields', function () {
    Mail::fake();

    $this->from(route('contact'))
        ->post(route('contact.send'), ['email' => 'not-an-email'])
        ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

    Mail::assertNothingSent();
});

test('the contact message email renders', function () {
    $mail = new ContactMessageMail(new ContactMessageDto('Jane Doe', 'jane@example.com', 'Order question', 'Where is my order?'));

    $mail->assertSeeInHtml('Jane Doe')
        ->assertSeeInHtml('Order question')
        ->assertSeeInHtml('Where is my order?');
});

test('the contact page renders', function () {
    $this->get(route('contact'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Contact'));
});
