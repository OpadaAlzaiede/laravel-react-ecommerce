<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('demo:prepare', function () {
    if (env('DEMO_RESET_ON_BOOT', false)) {
        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);

        return;
    }

    $this->call('migrate', ['--force' => true]);

    if (\App\Models\User::query()->doesntExist()) {
        $this->call('db:seed', ['--force' => true]);
    }
})->purpose('Migrate and seed the database for the public demo');
