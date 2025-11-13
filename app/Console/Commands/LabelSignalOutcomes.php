<?php

namespace App\Console\Commands;

use App\Models\SignalSnapshot;
use App\Repositories\MarketDataRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LabelSignalOutcomes extends Command
{
    protected $signature = 'signal:label
        {--symbol=BTC : Asset symbol}
        {--start= : Start datetime (UTC) - if not provided, uses recent snapshots}
        {--end= : End datetime (UTC) - if not provided, uses latest snapshots}
        {--horizon=24h : Lookahead period for outcome labeling (e.g., 1h, 4h, 24h, 72h)}
        {--limit=100 : Maximum number of snapshots to process}
        {--force : Re-label already labeled snapshots}
        {--batch=50 : Batch size for processing}
        {--validate : Validate labeling quality and show statistics}
        {--strategies=basic : Comma-separated list of labeling strategies (basic,breakout,mean-reversion,momentum)}
        {--min-accuracy=70 : Minimum accuracy threshold for labeling (percent)}
        {--skip-errors : Continue processing even if some snapshots fail}
        {--output=table : Output format (table,json,csv)}';

    protected $description = 'Advanced signal labeling with multiple strategies, validation, and quality metrics';

    public function __construct(
        protected MarketDataRepository $marketData
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $symbol = strtoupper($this->option('symbol') ?? 'BTC');
        $startInput = $this->option('start');
        $endInput = $this->option('end');
        $horizonInput = $this->option('horizon') ?? '24h';
        $limit = max(1, (int) $this->option('limit'));
        $force = $this->option('force');
        $batchSize = max(1, (int) $this->option('batch'));
        $validate = $this->option('validate');
        $strategiesInput = $this->option('strategies') ?? 'basic';
        $minAccuracy = max(0, min(100, (int) $this->option('min-accuracy')));
        $skipErrors = $this->option('skip-errors');
        $outputFormat = $this->option('output');

        // Parse horizon
        $horizonHours = $this->parseHorizon($horizonInput);
        if (!$horizonHours) {
            $this->error("Invalid horizon format: {$horizonInput}. Use formats like 1h, 24h, 72h");
            return self::INVALID;
        }

        // Parse strategies
        $strategies = $this->parseStrategies($strategiesInput);

        // Calculate date range
        if ($startInput || $endInput) {
            $start = $startInput ? Carbon::parse($startInput, 'UTC') : null;
            $end = $endInput ? Carbon::parse($endInput, 'UTC') : now('UTC');
        } else {
            $end = now('UTC');
            $start = $end->copy()->subDays(30); // Default to last 30 days
        }

        // Ensure we have data that's old enough for horizon
        $cutoff = $end->copy()->subHours($horizonHours);

        $this->info("Labeling snapshots for {$symbol}");
        $this->info("Date range: {$start->toIso8601String()} to {$end->toIso8601String()}");
        $this->info("Horizon: {$horizonInput} ({$horizonHours}h)");
        $this->info("Cutoff date: {$cutoff->toIso8601String()}");
        $this->info("Strategies: " . implode(', ', $strategies));
        $this->info("Batch size: {$batchSize}");

        // Get snapshots to process
        $query = SignalSnapshot::where('symbol', $symbol)
            ->where('generated_at', '<=', $cutoff) // Only label snapshots old enough
            ->orderByDesc('generated_at');

        // Apply date range if specified, but prioritize cutoff
        if ($start && $end) {
            $query->whereBetween('generated_at', [$start, min($cutoff, $end)]);
        } else {
            $query->where('generated_at', '>=', $cutoff->copy()->subDays(30)); // Default last 30 days
        }

        if (!$force) {
            $query->whereNull('price_future');
        }

        $snapshots = $query->limit($limit)->get();

        if ($snapshots->isEmpty()) {
            $this->info('No snapshots found matching criteria.');
            $this->info('Debug: Query conditions:');
            $this->info("  - Symbol: {$symbol}");
            $this->info("  - Generated_at <= {$cutoff->toIso8601String()}");
            if ($start && $end) {
                $this->info("  - Between: {$start->toIso8601String()} and " . min($cutoff, $end)->toIso8601String());
            }
            $this->info("  - Only unlabeled: " . ($force ? 'false' : 'true'));

            // Check what snapshots actually exist
            $totalSnapshots = SignalSnapshot::where('symbol', $symbol)->count();
            $this->info("Total {$symbol} snapshots in database: {$totalSnapshots}");

            return self::SUCCESS;
        }

        $results = $this->processBatch($snapshots, $strategies, $horizonHours, $batchSize, $skipErrors);

        if ($validate) {
            $this->validateAndShowStatistics($results);
        }

        $this->displayResults($results, $outputFormat);
        return self::SUCCESS;
    }

    protected function parseHorizon(string $horizon): ?int
    {
        if (preg_match('/^(\d+)h$/i', $horizon, $matches)) {
            return (int) $matches[1];
        }
        if (preg_match('/^(\d+)d$/i', $horizon, $matches)) {
            return (int) $matches[1] * 24;
        }
        return null;
    }

    protected function parseStrategies(string $input): array
    {
        $available = ['basic', 'breakout', 'mean-reversion', 'momentum'];
        $requested = explode(',', $input);
        return array_intersect($available, array_map('trim', $requested));
    }

    protected function processBatch(
        \Illuminate\Database\Eloquent\Collection $snapshots,
        array $strategies,
        int $horizonHours,
        int $batchSize,
        bool $skipErrors
    ): array {
        $results = [];
        $chunks = $snapshots->chunk($batchSize);

        foreach ($chunks as $index => $chunk) {
            $this->info("Processing batch " . ($index + 1) . "/" . $chunks->count() . " (" . $chunk->count() . " snapshots)");

            foreach ($chunk as $snapshot) {
                try {
                    $result = $this->labelSnapshotAdvanced($snapshot, $strategies, $horizonHours);
                    $results[] = $result;

                    if ($result['labeled']) {
                        $this->line("✓ {$snapshot->generated_at}: {$result['direction']} ({$result['magnitude']}%)");
                    } else {
                        $this->warn("✗ {$snapshot->generated_at}: {$result['error']}");
                    }

                } catch (\Exception $e) {
                    if ($skipErrors) {
                        $results[] = [
                            'timestamp' => $snapshot->generated_at,
                            'labeled' => false,
                            'error' => $e->getMessage(),
                            'direction' => null,
                            'magnitude' => null
                        ];
                        $this->warn("⚠️  Error processing {$snapshot->generated_at}: {$e->getMessage()}");
                    } else {
                        throw $e;
                    }
                }
            }
        }

        return $results;
    }

    protected function labelSnapshotAdvanced(SignalSnapshot $snapshot, array $strategies, int $horizonHours): array
    {
        $timestamp = $snapshot->generated_at;
        $futureTime = Carbon::parse($timestamp, 'UTC')->addHours($horizonHours);

        // Skip if future time hasn't arrived yet
        if ($futureTime->isFuture()) {
            return [
                'timestamp' => $timestamp,
                'labeled' => false,
                'error' => 'Future time not reached',
                'direction' => null,
                'magnitude' => null
            ];
        }

        // Get current and future prices
        $currentPrice = $snapshot->price_now;

        // If current price is missing, try to get it from our price data sources
        if ($currentPrice === null || $currentPrice <= 0) {
            $currentPrice = $this->getPriceAt($snapshot->symbol, Carbon::parse($snapshot->generated_at), $snapshot->interval ?? '1h');

            // Update the snapshot if we found a price
            if ($currentPrice && $currentPrice > 0) {
                $snapshot->update(['price_now' => $currentPrice]);
            }
        }

        $futurePrice = $this->getPriceAt($snapshot->symbol, $futureTime, $snapshot->interval ?? '1h');

        if ($currentPrice === null || $futurePrice === null || $currentPrice <= 0) {
            return [
                'timestamp' => $timestamp,
                'labeled' => false,
                'error' => 'Price data unavailable (current: ' . ($currentPrice ?? 'null') . ', future: ' . ($futurePrice ?? 'null') . ')',
                'direction' => null,
                'magnitude' => null
            ];
        }

        // Calculate basic return
        $return = (($futurePrice - $currentPrice) / $currentPrice) * 100;

        // Apply labeling strategies
        $labels = [];
        foreach ($strategies as $strategy) {
            $label = $this->applyStrategy($strategy, $currentPrice, $futurePrice, $return, $snapshot, $futureTime);
            $labels[$strategy] = $label;
        }

        // Use ensemble or fallback to basic
        $finalLabel = $this->ensembleLabels($labels) ?? $labels['basic'];

        // Update snapshot
        $snapshot->update([
            'price_future' => $futurePrice,
            'label_direction' => $finalLabel['direction'],
            'label_magnitude' => $finalLabel['magnitude'],
            'labeled_at' => now('UTC')
        ]);

        return [
            'timestamp' => $timestamp,
            'labeled' => true,
            'error' => null,
            'direction' => $finalLabel['direction'],
            'magnitude' => $finalLabel['magnitude'],
            'return' => $return,
            'strategies' => $labels
        ];
    }

    protected function applyStrategy(string $strategy, float $currentPrice, float $futurePrice, float $return, SignalSnapshot $snapshot, Carbon $futureTime): array
    {
        switch ($strategy) {
            case 'basic':
                return $this->basicLabeling($return);

            case 'breakout':
                return $this->breakoutLabeling($currentPrice, $futurePrice, $snapshot, $futureTime);

            case 'mean-reversion':
                return $this->meanReversionLabeling($currentPrice, $futurePrice, $snapshot, $futureTime);

            case 'momentum':
                return $this->momentumLabeling($return, $snapshot);

            default:
                return $this->basicLabeling($return);
        }
    }

    protected function basicLabeling(float $return): array
    {
        if ($return > 0.5) {
            return ['direction' => 'UP', 'magnitude' => $return];
        } elseif ($return < -0.5) {
            return ['direction' => 'DOWN', 'magnitude' => $return];
        } else {
            return ['direction' => 'SIDEWAYS', 'magnitude' => $return];
        }
    }

    protected function breakoutLabeling(float $currentPrice, float $futurePrice, SignalSnapshot $snapshot, Carbon $futureTime): array
    {
        // Simplified breakout labeling
        $return = (($futurePrice - $currentPrice) / $currentPrice) * 100;

        if ($return > 2.0) {
            return ['direction' => 'UP', 'magnitude' => $return];
        } elseif ($return < -2.0) {
            return ['direction' => 'DOWN', 'magnitude' => $return];
        } else {
            return ['direction' => 'SIDEWAYS', 'magnitude' => $return];
        }
    }

    protected function meanReversionLabeling(float $currentPrice, float $futurePrice, SignalSnapshot $snapshot, Carbon $futureTime): array
    {
        // Simplified mean reversion labeling
        $return = (($futurePrice - $currentPrice) / $currentPrice) * 100;

        // Check if there's reversion
        $signal = $snapshot->signal_rule;
        if ($signal === 'BUY' && $return < 0) {
            return ['direction' => 'UP', 'magnitude' => $return]; // Corrected
        } elseif ($signal === 'SELL' && $return > 0) {
            return ['direction' => 'DOWN', 'magnitude' => $return]; // Corrected
        } else {
            return $this->basicLabeling($return);
        }
    }

    protected function momentumLabeling(float $return, SignalSnapshot $snapshot): array
    {
        // Simplified momentum labeling
        $signal = $snapshot->signal_rule;
        $confidence = $snapshot->signal_score;

        if ($confidence > 1.5) {
            $expectedDirection = $signal === 'BUY' ? 'UP' : ($signal === 'SELL' ? 'DOWN' : 'SIDEWAYS');
        } else {
            $expectedDirection = $this->basicLabeling($return)['direction'];
        }

        return [
            'direction' => $expectedDirection,
            'magnitude' => $return,
            'confidence_based' => $confidence > 1.5
        ];
    }

    protected function ensembleLabels(array $labels): ?array
    {
        if (empty($labels)) {
            return null;
        }

        $directions = array_count_values(array_column($labels, 'direction'));
        $majorityDirection = array_keys($directions, max($directions))[0];

        // Average magnitude for majority direction
        $majorityLabels = array_filter($labels, fn ($l) => $l['direction'] === $majorityDirection);
        $avgMagnitude = collect($majorityLabels)->avg('magnitude') ?? 0;

        return [
            'direction' => $majorityDirection,
            'magnitude' => $avgMagnitude,
            'confidence' => $directions[$majorityDirection] / count($labels),
            'voting' => $directions
        ];
    }

    protected function getPriceAt(string $symbol, Carbon $timestamp, string $interval = '1h'): ?float
    {
        try {
            $pair = "{$symbol}USDT";

            // First try the MarketDataRepository
            $price = $this->marketData->spotPriceAt($pair, $timestamp->valueOf(), $interval);
            if ($price) {
                return (float) $price;
            }

            // PRIMARY: Get historical price from cg_spot_price_history table
            $historicalPrice = DB::table('cg_spot_price_history')
                ->where('symbol', $symbol)
                ->where('time', '<=', $timestamp->timestamp)
                ->orderByDesc('time')
                ->first();

            if ($historicalPrice && $historicalPrice->close) {
                return (float) $historicalPrice->close;
            }

            // Fallback 1: Try to get price from cg_spot_coins_markets table (current prices)
            $currentPrice = DB::table('cg_spot_coins_markets')
                ->where('symbol', $symbol)
                ->first();

            if ($currentPrice && $currentPrice->current_price) {
                return (float) $currentPrice->current_price;
            }

            // Fallback 2: Try to get historical price from aggregated volume history
            $historicalPrice = DB::table('cg_spot_aggregated_taker_volume_history')
                ->where('symbol', $symbol)
                ->where('time', '<=', $timestamp->timestamp)
                ->orderByDesc('time')
                ->first();

            if ($historicalPrice) {
                // This table doesn't have direct price, so try other sources
            }

            // Fallback 3: Check if we have any price data in signal features
            $signalPrice = DB::table('cg_signal_dataset')
                ->where('symbol', $symbol)
                ->where('generated_at', '<=', $timestamp)
                ->whereNotNull('price_now')
                ->where('price_now', '>', 0)
                ->orderByDesc('generated_at')
                ->first();

            if ($signalPrice && $signalPrice->price_now) {
                return (float) $signalPrice->price_now;
            }

            // Fallback 4: Try futures data if spot not available
            $futuresPrice = DB::table('cg_funding_rate_history')
                ->where('pair', $pair)
                ->where('time', '<=', $timestamp->timestamp)
                ->orderByDesc('time')
                ->first();

            if ($futuresPrice && $futuresPrice->close) {
                return (float) $futuresPrice->close;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function validateAndShowStatistics(array $results): void
    {
        $labeled = collect($results)->filter(fn ($r) => $r['labeled']);
        $byDirection = $labeled->groupBy('direction');

        $this->info("\n=== Labeling Statistics ===");
        $this->info("Total processed: " . count($results));
        $this->info("Successfully labeled: " . $labeled->count());
        $this->info("Success rate: " . number_format($labeled->count() / count($results) * 100, 1) . "%");

        foreach ($byDirection as $direction => $items) {
            $avgMag = $items->avg('magnitude');
            $this->info("{$direction}: {$items->count()} (avg magnitude: " . number_format($avgMag, 2) . "%)");
        }
    }

    protected function displayResults(array $results, string $format): void
    {
        switch ($format) {
            case 'json':
                $this->line(json_encode($results, JSON_PRETTY_PRINT));
                break;
            case 'csv':
                $this->displayCsvResults($results);
                break;
            default:
                $this->displayTableResults($results);
                break;
        }
    }

    protected function displayTableResults(array $results): void
    {
        $tableData = collect($results)->map(function ($result) {
            return [
                $result['timestamp'],
                $result['labeled'] ? '✓' : '✗',
                $result['direction'] ?? '--',
                $result['magnitude'] ? number_format($result['magnitude'], 2) . '%' : '--',
                $result['error'] ?? ''
            ];
        })->toArray();

        $this->table(['Timestamp', 'Labeled', 'Direction', 'Magnitude', 'Error'], $tableData);
    }

    protected function displayCsvResults(array $results): void
    {
        $csv = "timestamp,labeled,direction,magnitude,error\n";
        foreach ($results as $result) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s\n",
                $result['timestamp'],
                $result['labeled'] ? '1' : '0',
                $result['direction'] ?? '',
                $result['magnitude'] ?? '',
                str_replace(',', ';', $result['error'] ?? '')
            );
        }
        $this->line($csv);
    }

    protected function resolveDirection(?float $delta): ?string
    {
        if ($delta === null) {
            return null;
        }

        if ($delta > 0) {
            return 'UP';
        }

        if ($delta < 0) {
            return 'DOWN';
        }

        return 'FLAT';
    }
}
