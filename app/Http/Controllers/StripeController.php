<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Stripe\CheckoutSuccessRequest;
use App\Http\Resources\OrderViewResource;
use App\Services\OrderService;
use App\Services\StripeWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

final class StripeController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly StripeWebhookService $webhookService,
        private readonly LoggerInterface $logger,
    ) {}

    public function success(CheckoutSuccessRequest $request): Response
    {
        $orders = $this->orderService->getCheckoutOrders($request->user(), $request->sessionId());

        return Inertia::render('Stripe/Success', [
            'orders' => OrderViewResource::collection($orders)->collection->toArray(),
        ]);
    }

    public function failure(): Response
    {
        return Inertia::render('Stripe/Failure');
    }

    public function webhook(Request $request): HttpResponse
    {
        try {
            $event = $this->webhookService->constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
            );
        } catch (UnexpectedValueException|SignatureVerificationException $exception) {
            $this->logger->error($exception->getMessage(), ['exception' => $exception]);

            return response('Invalid payload', 400);
        }

        $this->webhookService->handle($event);

        return response('Webhook processed successfully', 200);
    }
}
