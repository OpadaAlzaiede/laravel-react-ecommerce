<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Router;

final class StripeConnectService
{
    private const ACCOUNT_TYPE = 'express';

    public function __construct(
        private readonly Config $config,
        private readonly Router $router,
        private readonly UrlGenerator $url,
    ) {}

    public function onboardingUrl(User $user): ?string
    {
        if (! $user->getStripeAccountId()) {
            $user->createStripeAccount(['type' => self::ACCOUNT_TYPE]);
        }

        if ($user->isStripeAccountActive()) {
            return null;
        }

        return $user->getStripeAccountLink();
    }

    public function refreshOnboardingUrl(User $user): string
    {
        return $user->getStripeAccountLink();
    }

    public function syncAccountStatus(User $user): void
    {
        $account = $user->retrieveStripeAccount();

        $user->setStripeAccountStatus($account->details_submitted)->save();
    }

    public function onboardingCompletedUrl(): string
    {
        $completeRoute = $this->config->get('stripe_connect.routes.account.complete');

        return $this->router->has($completeRoute)
            ? $this->url->route($completeRoute)
            : $this->url->to('/');
    }
}
