<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderViewResource;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()
            ->select(['id', 'total_price', 'status', 'created_at', 'vendor_user_id', 'user_id'])
            ->with(['user', 'vendorUser'])
            ->where('user_id', auth()->user()->id)
            ->when($request->get('status'), function($query) use ($request) {
                $query->where('status', $request->query('status'));
            })
            ->when($request->get('start_date') && $request->get('end_date'), function($query) use ($request) {
                $query->whereBetween('created_at', [$request->query('start_date'), $request->query('end_date')]);
            })
            ->orderBy('created_at', 'desc')
            ->paginate()
            ->withQueryString();

        return Inertia::render('Order/Index', [
            'orders' => OrderResource::collection($orders),
            'filters' => $request->only(['status', 'start_date', 'end_date']),
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['orderItem', 'vendorUser']);

        return Inertia::render('Order/Show', [
            'order' => OrderViewResource::make($order),
        ]);
    }
}
