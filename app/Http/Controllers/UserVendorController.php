<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Vendor\ShowRequest;
use App\Http\Resources\VendorUserResource;
use App\Models\User;
use App\Services\VendorDirectoryService;
use Inertia\Inertia;
use Inertia\Response;

final class UserVendorController extends Controller
{
    public function __construct(private readonly VendorDirectoryService $vendorDirectoryService) {}

    public function index(): Response
    {
        return Inertia::render('Vendor/Index', [
            'vendors' => VendorUserResource::collection($this->vendorDirectoryService->paginateByProductCount()),
        ]);
    }

    public function show(ShowRequest $request, User $vendor): Response
    {
        $filters = $request->toDto();

        return Inertia::render('Vendor/Show', [
            'vendor' => VendorUserResource::make($this->vendorDirectoryService->loadWithProducts($vendor, $filters)),
            'filters' => $filters->toArray(),
        ]);
    }
}
