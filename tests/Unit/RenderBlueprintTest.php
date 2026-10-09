<?php

use Symfony\Component\Yaml\Yaml;

function renderEnvironment(): array
{
    $blueprint = Yaml::parseFile(dirname(__DIR__, 2).'/render.yaml');

    return collect($blueprint['services'][0]['envVars'])
        ->filter(fn (array $variable) => array_key_exists('value', $variable))
        ->pluck('value', 'key')
        ->all();
}

test('the production blueprint only sends session cookies over https', function () {
    expect(renderEnvironment())->toHaveKey('SESSION_SECURE_COOKIE', 'true');
});

test('the production blueprint disables debug mode', function () {
    expect(renderEnvironment())
        ->toHaveKey('APP_ENV', 'production')
        ->toHaveKey('APP_DEBUG', 'false');
});
