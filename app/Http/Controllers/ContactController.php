<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Contact\SendRequest;
use App\Services\ContactService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class ContactController extends Controller
{
    public function __construct(private readonly ContactService $contactService) {}

    public function show(): Response
    {
        return Inertia::render('Contact');
    }

    public function send(SendRequest $request): RedirectResponse
    {
        $this->contactService->send($request->toDto());

        return back()->with('success', 'Thank you! We will get back to you soon.');
    }
}
