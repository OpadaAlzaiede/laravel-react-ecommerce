<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\Stripe\LinkType;
use App\Services\Interfaces\StripeConnect;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Stripe\Account;
use Stripe\Balance;
use Stripe\StripeClient;
use Stripe\Transfer;

trait Payable
{
    protected static StripeClient $stripe;

    protected Account $stripe_connect_account;

    protected static function bootPayable(): void
    {
        static::$stripe = App::make(StripeConnect::class);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    public function createStripeAccount(array $details): static
    {
        $this->stripe_connect_account = static::$stripe->accounts->create($details);

        $this->setStripeAccountId($this->stripe_connect_account->id)->save();

        return $this;
    }

    public function retrieveStripeAccount(): Account
    {
        return $this->stripe_connect_account = static::$stripe->accounts->retrieve($this->getStripeAccountId());
    }

    public function getStripeAccountId(): ?string
    {
        return $this->{$this->getStripeAccountIdColumn()};
    }

    public function isStripeAccountActive(): bool
    {
        return (bool) $this->{$this->getStripeAccountStatusColumn()};
    }

    public function getStripeAccountLink(LinkType $type = LinkType::Onboarding): string
    {
        $link = static::$stripe->accountLinks->create([
            'account' => $this->getStripeAccountId(),
            'refresh_url' => URL::route(Config::get('stripe_connect.routes.account.refresh')),
            'return_url' => URL::route(Config::get('stripe_connect.routes.account.return')),
            'type' => $type->value,
        ]);

        return $link->url;
    }

    public function transfer(int $amount, string $currency): Transfer
    {
        return static::$stripe->transfers->create([
            'amount' => $amount,
            'currency' => $currency,
            'destination' => $this->getStripeAccountId(),
        ]);
    }

    public function getAccountBalance(): Balance
    {
        return static::$stripe->balance->retrieve([], [
            'stripe_account' => $this->getStripeAccountId(),
        ]);
    }

    public function setStripeAccountStatus(bool $status): static
    {
        $this->{$this->getStripeAccountStatusColumn()} = $status;

        return $this;
    }

    protected function getStripeAccountIdColumn(): string
    {
        return Config::get('stripe_connect.payable.account_id_column');
    }

    protected function setStripeAccountId(string $id): static
    {
        $this->{$this->getStripeAccountIdColumn()} = $id;

        return $this;
    }

    protected function getStripeAccountStatusColumn(): string
    {
        return Config::get('stripe_connect.payable.account_status_column');
    }
}
