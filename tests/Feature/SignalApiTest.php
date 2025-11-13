<?php

namespace Tests\Feature;

use App\Services\Signal\FeatureBuilder;
use App\Services\Signal\SignalEngine;
use Tests\TestCase;

class SignalApiTest extends TestCase
{
    public function test_signal_endpoint_returns_payload(): void
    {
        $features = [
            'generated_at' => now('UTC')->toIso8601ZuluString(),
            'microstructure' => [
                'price' => ['last_close' => 68000],
                'taker_flow' => ['buy_ratio' => 0.6],
            ],
        ];

        $signal = [
            'signal' => 'SELL',
            'score' => -2.5,
            'confidence' => 0.8,
            'reasons' => ['Funding overheated'],
            'factors' => [['reason' => 'Funding overheated', 'weight' => -2]],
        ];

        $builderMock = $this->mock(FeatureBuilder::class, function ($mock) use ($features) {
            $mock->shouldReceive('build')
                ->once()
                ->with('BTC', 'BTCUSDT', '1h')
                ->andReturn($features);
        });

        $engineMock = $this->mock(SignalEngine::class, function ($mock) use ($features, $signal) {
            $mock->shouldReceive('score')
                ->once()
                ->with($features)
                ->andReturn($signal);
        });

        $response = $this->getJson('/api/signal/analytics?symbol=BTC&tf=1h');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'symbol' => 'BTC',
                'signal' => $signal,
                'features' => $features,
            ]);

        $response->assertJsonStructure([
            'signal' => ['signal', 'score', 'confidence', 'reasons', 'factors'],
        ]);
    }
}
