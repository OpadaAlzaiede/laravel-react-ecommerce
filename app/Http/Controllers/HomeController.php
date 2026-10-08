<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Contact\SendRequest;
use App\Http\Resources\ProductListResource;
use App\Http\Resources\VendorUserResource;
use App\Mail\ContactMessageMail;
use App\Services\HomeService;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    public function __construct(private readonly HomeService $homeService) {}

    public function home(): Response
    {
        $homePage = $this->homeService->getHomePage();

        return Inertia::render('Home', [
            'products' => ProductListResource::collection($homePage->products),
            'newProducts' => ProductListResource::collection($homePage->newProducts),
            'featuredProducts' => ProductListResource::collection($homePage->featuredProducts),
            'categories' => $homePage->categories,
            'vendors' => VendorUserResource::collection($homePage->vendors),
        ]);
    }

    public function about(): Response
    {
        return Inertia::render('About');
    }

    public function contact(): Response
    {
        return Inertia::render('Contact');
    }

    public function sendContact(SendRequest $request)
    {
        $data = $request->validated();

        Mail::to(config('mail.from.address'))->send(new ContactMessageMail(
            $data['name'],
            $data['email'],
            $data['subject'],
            $data['message'],
        ));

        return back()->with('success', 'Thank you! We will get back to you soon.');
    }
}
