<?php

namespace App\Console\Commands;

use App\Models\SignalSnapshot;
use App\Services\Signal\FeatureBuilder;
use App\Services\Signal\SignalEngine;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReplaySignalSnapshots extends Command
{
    protected $signature = 'signal:replay
        {--symbol=BTC : Asset symbol}
        {--pair= : Pair symbol (defaults to SYMBOLUSDT)}
        {--interval=1h : Candle interval}
        {--start= : Start datetime (UTC) - if not provided, retrieves from database for training}
        {--end= : End datetime (UTC) - if not provided, retrieves from database for training}
        {--from= : Start datetime (UTC) - deprecated, use --start instead}
        {--to= : End datetime (UTC) - deprecated, use --end instead}
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

        // Try new parameter names first, then fall back to old ones
        $startInput = $this->option('start') ?: $this->option('from');
        $endInput = $this->option('end') ?: $this->option('to');

        // If no dates provided, retrieve from database for training
        if (!$startInput || !$endInput) {
            return $this->handleDatabaseReplay($symbol, $pair, $interval);
        }

        $start = Carbon::parse($startInput, 'UTC');
        $end = Carbon::parse($endInput, 'UTC');

        if ($end->lessThanOrEqualTo($start)) {
            $this->error('--end must be greater than --start');
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

    protected function handleDatabaseReplay(string $symbol, string $pair, string $interval): int
    {
        $this->info('No date range provided. Retrieving available data from database for training...');

        // Get date range from existing database records
        $dateRange = $this->getDatabaseDateRange($symbol);

        if (!$dateRange) {
            $this->error('No historical data found in database for symbol: ' . $symbol);
            return self::INVALID;
        }

        $start = $dateRange['start'];
        $end = $dateRange['end'];

        $this->info(sprintf(
            'Found data range from %s to %s',
            $start->toIso8601String(),
            $end->toIso8601String()
        ));

        $step = max(1, (int) $this->option('step'));
        $limit = max(0, (int) $this->option('limit'));
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $cursor = $start->copy();
        $count = 0;

        while ($cursor->lessThanOrEqualTo($end)) {
            $timestampMs = $cursor->valueOf();

            if (!$force && $this->snapshotExists($symbol, $interval, $cursor)) {
                $cursor->addMinutes($step);
                continue;
            }

            try {
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
            } catch (\Exception $e) {
                $this->warn(sprintf(
                    'Skipping %s due to error: %s',
                    $cursor->toIso8601String(),
                    $e->getMessage()
                ));
            }

            $cursor->addMinutes($step);
        }

        $this->info(sprintf('Replayed %d snapshots from database.', $count));
        return self::SUCCESS;
    }

    protected function getDatabaseDateRange(string $symbol): ?array
    {
        // Check multiple data sources to find the date range
        $dataSources = [
            'funding_rates' => 'timestamp',
            'spot_prices' => 'time',
            'open_interest' => 'time',
            'liquidations' => 'time',
            'whale_transfers' => 'block_timestamp',
        ];

        $earliest = null;
        $latest = null;

        foreach ($dataSources as $table => $timeColumn) {
            try {
                $tableName = $this->getTableName($table);
                if (!$this->tableExists($tableName)) {
                    continue;
                }

                $query = "SELECT
                    MIN({$timeColumn}) as min_time,
                    MAX({$timeColumn}) as max_time
                    FROM {$tableName}";

                if ($table === 'whale_transfers') {
                    $query .= " WHERE asset_symbol = ?";
                    $result = DB::select($query, [$symbol]);
                } else {
                    $result = DB::select($query);
                }

                if ($result && !empty($result[0]->min_time) && !empty($result[0]->max_time)) {
                    $minTime = $this->parseDatabaseTime($result[0]->min_time);
                    $maxTime = $this->parseDatabaseTime($result[0]->max_time);

                    if ($minTime && $maxTime) {
                        if (!$earliest || $minTime->lessThan($earliest)) {
                            $earliest = $minTime;
                        }
                        if (!$latest || $maxTime->greaterThan($latest)) {
                            $latest = $maxTime;
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->warn("Could not check table {$table}: " . $e->getMessage());
            }
        }

        if ($earliest && $latest) {
            return [
                'start' => $earliest,
                'end' => $latest
            ];
        }

        return null;
    }

    protected function getTableName(string $dataSource): ?string
    {
        $tableMap = [
            'funding_rates' => 'cg_funding_rates',
            'spot_prices' => 'cg_spot_prices',
            'open_interest' => 'cg_open_interest',
            'liquidations' => 'cg_liquidations',
            'whale_transfers' => 'cg_whale_transfers',
        ];

        return $tableMap[$dataSource] ?? null;
    }

    protected function tableExists(string $tableName): bool
    {
        try {
            return Schema::hasTable($tableName);
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function parseDatabaseTime($timeValue): ?Carbon
    {
        if (!$timeValue) {
            return null;
        }

        try {
            // Handle different timestamp formats
            if (is_numeric($timeValue)) {
                // Unix timestamp (seconds or milliseconds)
                if ($timeValue > 1_000_000_000_000) {
                    // Milliseconds
                    return Carbon::createFromTimestampMs($timeValue, 'UTC');
                } else {
                    // Seconds
                    return Carbon::createFromTimestamp($timeValue, 'UTC');
                }
            } else {
                // String format
                return Carbon::parse($timeValue, 'UTC');
            }
        } catch (\Exception $e) {
            return null;
        }
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
