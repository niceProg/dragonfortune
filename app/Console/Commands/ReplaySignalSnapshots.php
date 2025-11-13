<?php

namespace App\Console\Commands;

use App\Models\SignalSnapshot;
use App\Services\Signal\FeatureBuilder;
use App\Services\Signal\SignalEngine;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ReplaySignalSnapshots extends Command
{
    protected $signature = 'signal:replay 
        {--symbol=BTC : Asset symbol}
        {--pair= : Pair symbol (defaults to SYMBOLUSDT)}
        {--interval=1h : Candle interval}
        {--from= : Start datetime (UTC)}
        {--to= : End datetime (UTC)}
        {--step=60 : Step in minutes}
        {--limit=0 : Max snapshots to store (0 = unlimited)}
        {--dry-run : Only print preview}
        {--force : Overwrite existing snapshots at same timestamp}';

    protected $description = 'Rebuild historical signal snapshots from database histories';

    public function __construct(
        protected FeatureBuilder $featureBuilder,
        protected SignalEngine $signalEngine
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $symbol = strtoupper($this->option('symbol') ?? 'BTC');
        $pair = strtoupper($this->option('pair') ?: "{$symbol}USDT");
        $interval = $this->option('interval') ?? '1h';
        $fromInput = $this->option('from');
        $toInput = $this->option('to');

        if (!$fromInput || !$toInput) {
            $this->error('Please provide both --from and --to (e.g. 2025-11-05T00:00:00Z).');
            return self::INVALID;
        }

        $start = Carbon::parse($fromInput, 'UTC');
        $end = Carbon::parse($toInput, 'UTC');

        if ($end->lessThanOrEqualTo($start)) {
            $this->error('--to must be greater than --from');
            return self::INVALID;
        }

        $step = max(1, (int) $this->option('step'));
        $limit = max(0, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $this->info(sprintf(
            'Replaying signals for %s (%s) from %s to %s every %d minutes%s',
            $symbol,
            $pair,
            $start->toIso8601String(),
            $end->toIso8601String(),
            $step,
            $dryRun ? ' [DRY RUN]' : ''
        ));

        $cursor = $start->copy();
        $count = 0;

        while ($cursor->lessThanOrEqualTo($end)) {
            $timestampMs = $cursor->valueOf();

            if (!$force && $this->snapshotExists($symbol, $interval, $cursor)) {
                $cursor->addMinutes($step);
                continue;
            }

            $features = $this->featureBuilder->build($symbol, $pair, $interval, $timestampMs);
            $signal = $this->signalEngine->score($features);

            if ($dryRun) {
                $this->line(sprintf(
                    '[%s] %s -> %s (score %.2f)',
                    $cursor->toIso8601String(),
                    $symbol,
                    $signal['signal'],
                    $signal['score']
                ));
            } else {
                $this->storeSnapshot($symbol, $pair, $interval, $cursor, $features, $signal);
            }

            $count++;
            if ($limit > 0 && $count >= $limit) {
                break;
            }

            $cursor->addMinutes($step);
        }

        $this->info(sprintf('Replayed %d snapshots.', $count));
        return self::SUCCESS;
    }

    protected function snapshotExists(string $symbol, string $interval, Carbon $timestamp): bool
    {
        return SignalSnapshot::where('symbol', $symbol)
            ->where('interval', $interval)
            ->where('generated_at', $timestamp->toDateTimeString())
            ->exists();
    }

    protected function storeSnapshot(
        string $symbol,
        string $pair,
        string $interval,
        Carbon $timestamp,
        array $features,
        array $signal
    ): void {
        $key = [
            'symbol' => $symbol,
            'interval' => $interval,
            'generated_at' => $timestamp->toDateTimeString(),
        ];

        $existing = SignalSnapshot::where($key)->first();

        SignalSnapshot::updateOrCreate(
            $key,
            [
                'pair' => $pair,
                'run_id' => $timestamp->format('YmdH00') . '-replay',
                'price_now' => data_get($features, 'microstructure.price.last_close'),
                'price_future' => $existing->price_future ?? null,
                'label_direction' => $existing->label_direction ?? null,
                'label_magnitude' => $existing->label_magnitude ?? null,
                'signal_rule' => $signal['signal'] ?? 'NEUTRAL',
                'signal_score' => $signal['score'] ?? 0,
                'signal_confidence' => $signal['confidence'] ?? 0,
                'signal_reasons' => $signal['factors'] ?? [],
                'features_payload' => $features,
                'is_missing_data' => false,
            ]
        );
    }
}
