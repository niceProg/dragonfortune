<?php

namespace Database\Factories;

use App\Models\SignalSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SignalSnapshot>
 */
class SignalSnapshotFactory extends Factory
{
    protected $model = SignalSnapshot::class;

    public function definition(): array
    {
        $generated = $this->faker->dateTimeBetween('-60 days', '-2 days');
        $priceNow = $this->faker->numberBetween(20000, 80000);
        $future = $priceNow * (1 + $this->faker->randomFloat(4, -0.05, 0.05));
        $deltaPct = (($future - $priceNow) / $priceNow) * 100;
        $direction = $deltaPct > 0 ? 'UP' : ($deltaPct < 0 ? 'DOWN' : 'FLAT');

        return [
            'symbol' => 'BTC',
            'pair' => 'BTCUSDT',
            'interval' => '1h',
            'run_id' => $generated->format('YmdH') . '-' . Str::lower(Str::random(6)),
            'generated_at' => $generated,
            'price_now' => $priceNow,
            'price_future' => $future,
            'label_direction' => $direction,
            'label_magnitude' => $deltaPct,
            'signal_rule' => $this->faker->randomElement(['BUY', 'SELL', 'NEUTRAL']),
            'signal_score' => $this->faker->randomFloat(2, -3, 3),
            'signal_confidence' => $this->faker->randomFloat(2, 0, 1),
            'signal_reasons' => [
                ['reason' => 'Test reason', 'weight' => 1, 'context' => []],
            ],
            'features_payload' => ['mock' => true],
            'is_missing_data' => false,
        ];
    }
}
