<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * CryptoQuant API Client
 * 
 * Provides on-chain and exchange data for crypto assets
 * Docs: https://cryptoquant.com/docs
 */
class CryptoQuantClient
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
        $this->baseUrl = rtrim($baseUrl ?? env('CRYPTOQUANT_API_URL', 'https://api.cryptoquant.com'), '/');
        $this->apiKey = $apiKey ?? env('CRYPTOQUANT_API_KEY', 'jED5yIBUPyzpeRTodjcSPGiltvvdAaJQmV1op1ED3v4UkDorgm6O20rRTq3yKWloyebmxw');
        $this->timeoutSeconds = $timeoutSeconds ?? (int) env('CRYPTOQUANT_TIMEOUT', 15);
        $this->maxRetries = $maxRetries ?? (int) env('CRYPTOQUANT_RETRIES', 2);
    }

    public function get(string $path, array $query = [])
    {
        $url = $this->buildUrl($path, $query);

        $attempt = 0;
        $lastException = null;

        while ($attempt <= $this->maxRetries) {
            try {
                $attempt++;
                
                Log::info('CryptoQuant API request', [
                    'url' => $url,
                    'attempt' => $attempt,
                ]);
                
                $response = Http::timeout($this->timeoutSeconds)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Accept' => 'application/json',
                    ])
                    ->withOptions([
                        'verify' => config('app.env') === 'production'
                    ])
                    ->get($url);

                if ($response->successful()) {
                    $data = $response->json();
                    Log::info('✅ CryptoQuant API success', [
                        'url' => $url,
                        'status' => $response->status(),
                    ]);
                    return $data;
                }

                // Rate limit
                if ($response->status() === 429) {
                    $retryAfter = (int) $response->header('Retry-After', 2);
                    Log::warning('⚠️ CryptoQuant rate limit', ['retry_after' => $retryAfter]);
                    sleep(max(1, $retryAfter));
                    continue;
                }

                // Server error: retry
                if ($response->serverError() && $attempt <= $this->maxRetries) {
                    Log::warning('⚠️ CryptoQuant server error, retrying', [
                        'status' => $response->status(),
                        'attempt' => $attempt,
                    ]);
                    sleep(min(2, $attempt));
                    continue;
                }

                // Client error
                Log::error('❌ CryptoQuant API error', [
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => [
                        'code' => $response->status(),
                        'message' => $response->json('message') ?? $response->body(),
                    ],
                ];
            } catch (\Throwable $e) {
                $lastException = $e;
                Log::error('❌ CryptoQuant exception', [
                    'url' => $url,
                    'attempt' => $attempt,
                    'message' => $e->getMessage(),
                ]);
                
                if ($attempt <= $this->maxRetries) {
                    sleep(min(2, $attempt));
                }
            }
        }

        return [
            'success' => false,
            'error' => [
                'code' => 500,
                'message' => 'Failed to fetch from CryptoQuant API after retries',
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
}

