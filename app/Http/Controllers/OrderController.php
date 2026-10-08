<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Order\IndexRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderViewResource;
use App\Models\Order;
use App\Services\OrderService;
use Inertia\Inertia;
use Inertia\Response;

final class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    public function index(IndexRequest $request): Response
    {
        $filters = $request->toDto();

        return Inertia::render('Order/Index', [
            'orders' => OrderResource::collection($this->orderService->paginateForCustomer($request->user(), $filters)),
            'filters' => $filters->toArray(),
        ]);
    }

    public function show(Order $order): Response
    {
        return Inertia::render('Order/Show', [
            'order' => OrderViewResource::make($this->orderService->loadForDisplay($order)),
        ]);
    }
}
