<?php

namespace Tests\Unit;

use App\Repositories\MarketDataRepository;
use App\Services\Signal\FeatureBuilder;
use Illuminate\Support\Collection;
use Tests\TestCase;

class FeatureBuilderTest extends TestCase
{
    public function test_build_returns_complete_feature_snapshot(): void
    {
        $fakeRepo = new class extends MarketDataRepository {
            public function latestFundingRates(string $pair, string $interval = '1h', array $exchanges = [], int $limit = 200, ?int $upTo = null): Collection
            {
                $now = now()->valueOf();
                $rows = [];
                for ($i = 0; $i < 60; $i++) {
                    $rows[] = (object) [
                        'exchange' => 'Binance',
                        'pair' => $pair,
                        'interval' => $interval,
                        'time' => $now - ($i * 60_000),
                        'open' => '0.0010',
                        'high' => '0.0015',
                        'low' => '0.0010',
                        'close' => $i === 0 ? '0.01' : '0.0010',
                    ];
                }
                return collect($rows);
            }

            public function latestOpenInterest(string $symbol, string $interval = '1h', string $unit = 'usd', int $limit = 200, ?int $upTo = null): Collection
            {
                $now = now()->valueOf();
                return collect(range(0, 30))->map(function ($i) use ($symbol, $interval, $unit, $now) {
                    return (object) [
                        'symbol' => $symbol,
                        'interval' => $interval,
                        'unit' => $unit,
                        'time' => $now - ($i * 60 * 60 * 1000),
                        'open' => '60000000000',
                        'high' => '61000000000',
                        'low' => '59000000000',
                        'close' => (string) (60000000000 + ($i * 1_000_000)),
                    ];
                });
            }

            public function latestWhaleTransfers(string $assetSymbol, ?int $sinceTimestamp = null, int $limit = 200, ?int $upTo = null): Collection
            {
                $timestamp = now()->timestamp;
                return collect([
                    (object) [
                        'amount_usd' => '10000000',
                        'asset_quantity' => '100',
                        'asset_symbol' => $assetSymbol,
                        'from_address' => 'unknown wallet',
                        'to_address' => 'Binance hot wallet',
                        'blockchain_name' => 'bitcoin',
                        'block_timestamp' => $timestamp,
                    ],
                    (object) [
                        'amount_usd' => '8000000',
                        'asset_quantity' => '80',
                        'asset_symbol' => $assetSymbol,
                        'from_address' => 'Binance hot wallet',
                        'to_address' => 'unknown wallet',
                        'blockchain_name' => 'bitcoin',
                        'block_timestamp' => $timestamp - 3600,
                    ],
                ]);
            }

            public function latestEtfFlows(int $limit = 120, ?int $upTo = null): Collection
            {
                $now = now()->valueOf();
                return collect(range(0, 10))->map(fn ($i) => (object) [
                    'timestamp' => $now - ($i * 86_400_000),
                    'flow_usd' => $i === 0 ? '150000000' : '50000000',
                    'created_at' => now(),
                ]);
            }

            public function fearGreedHistory(int $limit = 100, ?int $upTo = null): Collection
            {
                return collect(range(0, 10))->map(fn ($i) => (object) [
                    'timestamp' => now()->valueOf() - ($i * 86_400_000),
                    'value' => 60 - $i,
                    'value_classification' => 'Greed',
                ]);
            }

            public function latestSpotOrderbook(string $symbol, string $interval = '1m', int $limit = 120, ?int $upTo = null): Collection
            {
                return collect([
                    (object) [
                        'symbol' => $symbol,
                        'interval' => $interval,
                        'time' => now()->valueOf(),
                        'aggregated_bids_usd' => '5000000',
                        'aggregated_asks_usd' => '3000000',
                        'aggregated_bids_quantity' => '250',
                        'aggregated_asks_quantity' => '150',
                    ],
                ]);
            }

            public function latestSpotTakerVolume(string $symbol, string $interval = '1h', array $exchanges = [], int $limit = 200, ?int $upTo = null): Collection
            {
                return collect(range(0, 30))->map(fn ($i) => (object) [
                    'exchange' => 'Binance',
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'time' => now()->valueOf() - ($i * 60 * 60 * 1000),
                    'aggregated_buy_volume_usd' => '1000000',
                    'aggregated_sell_volume_usd' => $i % 2 === 0 ? '800000' : '600000',
                ]);
            }

            public function latestSpotPrices(string $pair, string $interval = '1h', int $limit = 500, ?int $upTo = null): Collection
            {
                return collect(range(0, 30))->map(fn ($i) => (object) [
                    'symbol' => $pair,
                    'interval' => $interval,
                    'time' => now()->valueOf() - ($i * 60 * 60 * 1000),
                    'open' => '60000',
                    'high' => '60500',
                    'low' => '59500',
                    'close' => (string) (60000 + $i * 10),
                    'volume_usd' => '1000',
                ]);
            }

            public function latestLiquidations(string $symbol, string $interval = '1h', int $limit = 200, ?int $upTo = null): Collection
            {
                return collect(range(0, 24))->map(fn ($i) => (object) [
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'time' => now()->valueOf() - ($i * 60 * 60 * 1000),
                    'aggregated_long_liquidation_usd' => '2000000',
                    'aggregated_short_liquidation_usd' => '1500000',
                ]);
            }
        };

        $builder = new FeatureBuilder($fakeRepo);
        $features = $builder->build('BTC', 'BTCUSDT', '1h');

        $this->assertSame('BTC', $features['symbol']);
        $this->assertArrayHasKey('funding', $features);
        $this->assertArrayHasKey('open_interest', $features);
        $this->assertArrayHasKey('whales', $features);
        $this->assertNotNull($features['funding']['heat_score']);
        $this->assertNotNull($features['open_interest']['pct_change_24h']);
        $this->assertNotNull($features['microstructure']['orderbook']['imbalance']);
    }
}
