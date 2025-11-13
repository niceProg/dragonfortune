<?php

namespace Tests\Unit;

use App\Models\SignalSnapshot;
use App\Services\Signal\BacktestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BacktestServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_backtest_metrics_are_calculated(): void
    {
        SignalSnapshot::factory()->count(10)->create([
            'symbol' => 'BTC',
            'signal_rule' => 'BUY',
            'label_direction' => 'UP',
            'label_magnitude' => 2.5,
            'generated_at' => now()->subDays(5),
        ]);

        SignalSnapshot::factory()->count(5)->create([
            'symbol' => 'BTC',
            'signal_rule' => 'SELL',
            'label_direction' => 'DOWN',
            'label_magnitude' => 1.5,
            'generated_at' => now()->subDays(4),
        ]);

        $service = $this->app->make(BacktestService::class);

        $results = $service->run(['symbol' => 'BTC']);

        $this->assertSame(15, $results['total']);
        $this->assertEquals(1.0, $results['metrics']['win_rate']);
        $this->assertEquals(10, $results['metrics']['buy_trades']);
        $this->assertEquals(5, $results['metrics']['sell_trades']);
    }
}
