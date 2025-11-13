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
        {--dry-run : Only print payload without storing}
        {--batch : Enable batch mode for multiple timestamps}
        {--count=1 : Number of snapshots to collect in batch mode}
        {--step=60 : Step in minutes between batch snapshots}
        {--force : Force overwrite existing snapshots}
        {--validate : Validate data completeness before storing}
        {--output=json : Output format (json, table)}';

    protected $description = 'Collect signal snapshots with advanced validation and batch processing capabilities';

    public function __construct(
        protected FeatureBuilder $featureBuilder,
        protected SignalEngine $signalEngine
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $batchMode = $this->option('batch');
        $dryRun = $this->option('dry-run');
        $validate = $this->option('validate');
        $outputFormat = $this->option('output');

        $symbols = $this->option('symbol')
            ? [strtoupper($this->option('symbol'))]
            : collect(config('signal.symbols', ['BTC']))
                ->filter()
                ->map(fn ($symbol) => strtoupper(trim($symbol)))
                ->unique()
                ->values()
                ->all();

        $interval = $this->option('interval') ?? config('signal.default_interval', '1h');
        $count = max(1, (int) $this->option('count'));
        $step = max(1, (int) $this->option('step'));

        if ($batchMode && $count > 1) {
            return $this->handleBatchCollection($symbols, $interval, $count, $step, $dryRun, $validate, $outputFormat);
        }

        foreach ($symbols as $symbol) {
            $pair = strtoupper($this->option('pair') ?: "{$symbol}USDT");
            $this->collectForSymbol($symbol, $pair, $interval, $dryRun, $validate, $outputFormat);
        }

        return self::SUCCESS;
    }

    protected function handleBatchCollection(
        array $symbols,
        string $interval,
        int $count,
        int $step,
        bool $dryRun,
        bool $validate,
        string $outputFormat
    ): int {
        $this->info("Starting batch collection: {$count} snapshots, {$step} minute intervals");

        $results = [];
        $currentTime = now('UTC');

        foreach ($symbols as $symbol) {
            $pair = strtoupper($this->option('pair') ?: "{$symbol}USDT");
            $symbolResults = [];

            for ($i = 0; $i < $count; $i++) {
                $timestamp = $currentTime->copy()->subMinutes($i * $step);
                $result = $this->collectForSymbolAtTime($symbol, $pair, $interval, $timestamp, $dryRun, $validate);

                if ($result) {
                    $symbolResults[] = $result;
                }

                if ($i % 10 === 0) {
                    $this->line("Progress: {$i}/{$count} snapshots for {$symbol}");
                }
            }

            $results[$symbol] = $symbolResults;
        }

        $this->displayBatchResults($results, $outputFormat);
        return self::SUCCESS;
    }

    protected function displayBatchResults(array $results, string $outputFormat): void
    {
        if ($outputFormat === 'json') {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));
            return;
        }

        $this->table(['Symbol', 'Snapshots', 'Valid', 'Stored', 'Quality Score'],
            collect($results)->map(function ($symbolResults, $symbol) {
                $valid = collect($symbolResults)->filter(fn ($r) => $r['valid'])->count();
                $stored = collect($symbolResults)->filter(fn ($r) => $r['stored'])->count();
                $avgQuality = collect($symbolResults)->avg('quality_score') ?? 0;

                return [
                    $symbol,
                    count($symbolResults),
                    $valid,
                    $stored,
                    number_format($avgQuality, 2)
                ];
            })->toArray()
        );
    }

    protected function collectForSymbol(string $symbol, string $pair, string $interval, bool $dryRun = false, bool $validate = false, string $outputFormat = 'table'): void
    {
        $this->info("Collecting signal for {$symbol} ({$pair}) interval {$interval}");

        $result = $this->collectForSymbolAtTime($symbol, $pair, $interval, now('UTC'), $dryRun, $validate);

        if ($outputFormat === 'json' && $result) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        }
    }

    protected function collectForSymbolAtTime(string $symbol, string $pair, string $interval, Carbon $timestamp, bool $dryRun = false, bool $validate = false): ?array
    {
        try {
            $features = $this->featureBuilder->build($symbol, $pair, $interval, $timestamp->valueOf());
            $signal = $this->signalEngine->score($features);

            $generatedAt = isset($features['generated_at'])
                ? Carbon::parse($features['generated_at'])
                : $timestamp;

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

            // Enhanced validation
            $qualityScore = $this->calculateQualityScore($features, $signal);
            $isValid = true;

            if ($validate) {
                $isValid = $this->validateSnapshot($features, $signal);
                if (!$isValid) {
                    $this->warn("Validation failed for {$symbol} at {$generatedAt->toIso8601String()}");
                }
            }

            if ($dryRun) {
                $this->line(json_encode($payload, JSON_PRETTY_PRINT));
                return [
                    'timestamp' => $generatedAt->toIso8601String(),
                    'valid' => $isValid,
                    'stored' => false,
                    'quality_score' => $qualityScore,
                    'signal' => $signal['signal'] ?? 'NEUTRAL',
                    'score' => $signal['score'] ?? 0
                ];
            }

            $force = $this->option('force');
            if (!$force && $this->snapshotExists($symbol, $interval, $generatedAt)) {
                $this->warn("Snapshot already exists for {$symbol} at {$generatedAt->toIso8601String()}. Use --force to overwrite.");
                return null;
            }

            SignalSnapshot::updateOrCreate(
                ['symbol' => $symbol, 'interval' => $interval, 'generated_at' => $generatedAt],
                $payload
            );

            $this->info("✓ Snapshot stored for {$symbol} at {$generatedAt->toIso8601String()}");

            return [
                'timestamp' => $generatedAt->toIso8601String(),
                'valid' => $isValid,
                'stored' => true,
                'quality_score' => $qualityScore,
                'signal' => $signal['signal'] ?? 'NEUTRAL',
                'score' => $signal['score'] ?? 0,
                'confidence' => $signal['confidence'] ?? 0,
                'missing_sections' => $this->getMissingSections($features)
            ];

        } catch (\Exception $e) {
            $this->error("Failed to collect signal for {$symbol}: " . $e->getMessage());
            return null;
        }
    }

    protected function calculateQualityScore(array $features, array $signal): float
    {
        $score = 0.0;
        $maxScore = 0.0;

        // Feature completeness (40%)
        $requiredSections = ['funding', 'open_interest', 'whales', 'etf', 'sentiment', 'microstructure', 'liquidations', 'long_short', 'momentum'];
        $presentSections = 0;

        foreach ($requiredSections as $section) {
            if (!empty($features[$section])) {
                $presentSections++;
            }
        }

        $completenessScore = ($presentSections / count($requiredSections)) * 40;
        $score += $completenessScore;
        $maxScore += 40;

        // Signal confidence (30%)
        $confidence = $signal['confidence'] ?? 0;
        $score += $confidence * 30;
        $maxScore += 30;

        // Data freshness (20%)
        $freshnessScore = $this->calculateFreshnessScore($features);
        $score += $freshnessScore * 20;
        $maxScore += 20;

        // Price availability (10%)
        $priceAvailable = !empty($features['microstructure']['price']['last_close']);
        $score += $priceAvailable ? 10 : 0;
        $maxScore += 10;

        return $maxScore > 0 ? ($score / $maxScore) * 100 : 0;
    }

    protected function calculateFreshnessScore(array $features): float
    {
        $score = 1.0;

        // Check for stale data indicators
        if (!empty($features['long_short']['is_stale'])) {
            $score -= 0.3;
        }

        if (!empty($features['whales']['is_stale'])) {
            $score -= 0.2;
        }

        if (!empty($features['health']['is_degraded'])) {
            $score -= 0.4;
        }

        return max(0, $score);
    }

    protected function validateSnapshot(array $features, array $signal): bool
    {
        // Check if we have the minimum required data
        $requiredKeys = ['funding', 'microstructure'];
        foreach ($requiredKeys as $key) {
            if (empty($features[$key])) {
                return false;
            }
        }

        // Check if we have price data
        if (empty($features['microstructure']['price']['last_close'])) {
            return false;
        }

        // Check if signal has required fields
        if (empty($signal['signal']) || $signal['score'] === null) {
            return false;
        }

        // Check for extreme values that might indicate data issues
        $price = $features['microstructure']['price']['last_close'];
        if ($price <= 0 || $price > 1000000) { // Reasonable price bounds for BTC
            return false;
        }

        return true;
    }

    protected function snapshotExists(string $symbol, string $interval, Carbon $timestamp): bool
    {
        return SignalSnapshot::where('symbol', $symbol)
            ->where('interval', $interval)
            ->where('generated_at', $timestamp->toDateTimeString())
            ->exists();
    }

    protected function getMissingSections(array $features): array
    {
        $requiredSections = ['funding', 'open_interest', 'whales', 'etf', 'sentiment', 'microstructure', 'liquidations', 'long_short', 'momentum'];
        return array_filter($requiredSections, fn ($section) => empty($features[$section]));
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
