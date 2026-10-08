<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Category\ShowRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Inertia\Inertia;
use Inertia\Response;

final class CategoryController extends Controller
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function index(): Response
    {
        return Inertia::render('Category/Index', [
            'categories' => CategoryResource::collection($this->categoryService->paginateByProductCount()),
        ]);
    }

    public function show(ShowRequest $request, Category $category): Response
    {
        $filters = $request->toDto();

        return Inertia::render('Category/Show', [
            'category' => CategoryResource::make($this->categoryService->loadWithProducts($category, $filters)),
            'filters' => $filters->toArray(),
        ]);
    }
}
