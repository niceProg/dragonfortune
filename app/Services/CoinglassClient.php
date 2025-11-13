<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CoinglassClient
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeoutSeconds;
    private int $maxRetries;

    public function __construct(
        ?string $baseUrl = null,
        ?string $apiKey = null,
        ?int $timeoutSeconds = null,
        ?int $maxRetries = null
    ) {
        // Prefer existing env COINGLASS_API_URL (which may already include '/api')
        $this->baseUrl = rtrim($baseUrl ?? config('services.coinglass.base_url', env('COINGLASS_API_URL', 'https://open-api-v4.coinglass.com/api')), '/');
        $this->apiKey = $apiKey ?? config('services.coinglass.key', env('COINGLASS_API_KEY', ''));
        $this->timeoutSeconds = $timeoutSeconds ?? (int) (config('services.coinglass.timeout', env('COINGLASS_TIMEOUT', 15)));
        $this->maxRetries = $maxRetries ?? (int) (config('services.coinglass.retries', env('COINGLASS_RETRIES', 2)));
    }

    public function get(string $path, array $query = [])
    {
        $url = $this->buildUrl($path, $query);

        $attempt = 0;
        $lastException = null;

        while ($attempt <= $this->maxRetries) {
            try {
                $attempt++;
                $response = Http::timeout($this->timeoutSeconds)
                    ->withHeaders($this->getAuthHeaders())
                    ->withOptions([
                        'verify' => config('app.env') === 'production'
                    ])
                    ->acceptJson()
                    ->get($url);

                if ($response->successful()) {
                    return $response->json();
                }

                if ($response->status() === 429) {
                    $retryAfter = (int) $response->header('Retry-After', 1);
                    sleep(max(1, $retryAfter));
                    continue;
                }

                if ($response->serverError() && $attempt <= $this->maxRetries) {
                    sleep(min(4, $attempt));
                    continue;
                }

                return [
                    'success' => false,
                    'error' => [
                        'code' => $response->status(),
                        'message' => $response->json('error') ?? $response->body(),
                    ],
                ];
            } catch (\Throwable $e) {
                $lastException = $e;
                Log::warning('Coinglass request error', [
                    'path' => $path,
                    'query' => $query,
                    'attempt' => $attempt,
                    'message' => $e->getMessage(),
                ]);
                if ($attempt <= $this->maxRetries) {
                    sleep(min(4, $attempt));
                }
            }
        }

        return [
            'success' => false,
            'error' => [
                'code' => 500,
                'message' => 'Failed to fetch from Coinglass',
                'details' => $lastException ? $lastException->getMessage() : null,
            ],
        ];
    }

    private function buildUrl(string $path, array $query = []): string
    {
        $path = ltrim($path, '/');
        $url = $this->baseUrl . '/' . $path;
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }
        return $url;
    }

    private function getAuthHeaders(): array
    {
        return [
            // Coinglass v4 expects CG-API-KEY per docs
            'CG-API-KEY' => $this->apiKey,
        ];
    }
}


