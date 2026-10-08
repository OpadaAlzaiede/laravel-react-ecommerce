<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ProductListResource;
use App\Http\Resources\VendorUserResource;
use App\Services\HomeService;
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
}
