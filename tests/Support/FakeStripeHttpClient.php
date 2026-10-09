<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;
use Stripe\HttpClient\ClientInterface;
use Stripe\Util\CaseInsensitiveArray;

final class FakeStripeHttpClient implements ClientInterface
{
    /**
     * @var array<int, array{endpoint: string, params: mixed, headers: array<int, string>}>
     */
    public array $requests = [];

    /**
     * @param  array<string, array<string, mixed>>  $responses
     */
    public function __construct(private array $responses) {}

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $endpoint = strtoupper($method).' '.parse_url($absUrl, PHP_URL_PATH);
        $this->requests[] = ['endpoint' => $endpoint, 'params' => $params, 'headers' => $headers];

        if (! array_key_exists($endpoint, $this->responses)) {
            throw new RuntimeException("Unexpected Stripe request: {$endpoint}");
        }

        return [json_encode($this->responses[$endpoint]), 200, new CaseInsensitiveArray([])];
    }

    /**
     * @return array<int, string>
     */
    public function endpoints(): array
    {
        return array_column($this->requests, 'endpoint');
    }

    public function header(int $requestIndex, string $name): ?string
    {
        foreach ($this->requests[$requestIndex]['headers'] as $header) {
            [$headerName, $value] = array_map('trim', explode(':', $header, 2));

            if (strcasecmp($headerName, $name) === 0) {
                return $value;
            }
        }

        return null;
    }
}
