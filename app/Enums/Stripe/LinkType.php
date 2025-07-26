<?php

namespace App\Enums\Stripe;

enum LinkType: string
{
    case Onboarding = 'account_onboarding';
    case Update = 'account_update';
}
