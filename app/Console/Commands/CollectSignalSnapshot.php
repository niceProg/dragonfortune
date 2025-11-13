<?php

namespace App\Console\Commands;

use App\Models\SignalSnapshot;
use App\Services\Signal\FeatureBuilder;
use App\Services\Signal\SignalEngine;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CollectSignalSnapshot extends Command
{
    protected $signature = 'signal:collect 
        {--symbol= : Asset symbol to collect (defaults to configured list)}
        {--pair= : Derivatives pair (defaults to SYMBOLUSDT)}
        {--interval= : Candle interval (defaults to config)}
        {--dry-run : Only print payload without storing}';

    protected $description = 'Collects the latest signal snapshot and stores it into cg_signal_dataset';

    public function __construct(
        protected FeatureBuilder $featureBuilder,
        protected SignalEngine $signalEngine
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $symbols = $this->option('symbol')
            ? [strtoupper($this->option('symbol'))]
            : collect(config('signal.symbols', ['BTC']))
                ->filter()
                ->map(fn ($symbol) => strtoupper(trim($symbol)))
                ->unique()
                ->values()
                ->all();

        $interval = $this->option('interval') ?? config('signal.default_interval', '1h');

        foreach ($symbols as $symbol) {
            $pair = strtoupper($this->option('pair') ?: "{$symbol}USDT");
            $this->collectForSymbol($symbol, $pair, $interval);
        }

        return self::SUCCESS;
    }

    protected function collectForSymbol(string $symbol, string $pair, string $interval): void
    {
        $this->info("Collecting signal for {$symbol} ({$pair}) interval {$interval}");

        $features = $this->featureBuilder->build($symbol, $pair, $interval);
        $signal = $this->signalEngine->score($features);

        $generatedAt = isset($features['generated_at'])
            ? Carbon::parse($features['generated_at'])
            : now('UTC');

        $payload = [
            'symbol' => $symbol,
            'pair' => $pair,
            'interval' => $interval,
            'run_id' => $generatedAt->format('YmdH00') . '-' . Str::lower(Str::random(6)),
            'generated_at' => $generatedAt,
            'price_now' => data_get($features, 'microstructure.price.last_close'),
            'price_future' => null,
            'label_direction' => null,
            'label_magnitude' => null,
            'signal_rule' => $signal['signal'] ?? 'NEUTRAL',
            'signal_score' => $signal['score'] ?? 0,
            'signal_confidence' => $signal['confidence'] ?? 0,
            'signal_reasons' => $signal['factors'] ?? [],
            'features_payload' => $features,
            'is_missing_data' => $this->hasMissingSections($features),
        ];

        if ($this->option('dry-run')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT));
            return;
        }

        SignalSnapshot::create($payload);
        $this->info("Snapshot stored for {$symbol}");
    }

    protected function hasMissingSections(array $features): bool
    {
        $keys = [
            'funding',
            'open_interest',
            'whales',
            'etf',
            'sentiment',
            'microstructure',
            'liquidations',
            'long_short',
            'momentum',
        ];
        foreach ($keys as $key) {
            if (empty($features[$key])) {
                return true;
            }
        }

        return false;
    }
}
