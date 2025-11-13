<?php

namespace App\Console\Commands;

use App\Models\SignalSnapshot;
use App\Repositories\MarketDataRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
        if ($start->greaterThan($cutoff)) {
            $start = $cutoff;
        }

        $this->info("Labeling snapshots for {$symbol}");
        $this->info("Date range: {$start->toIso8601String()} to {$end->toIso8601String()}");
        $this->info("Horizon: {$horizonInput} ({$horizonHours}h)");
        $this->info("Strategies: " . implode(', ', $strategies));
        $this->info("Batch size: {$batchSize}");

        // Get snapshots to process
        $query = SignalSnapshot::where('symbol', $symbol)
            ->whereBetween('generated_at', [$start, $end])
            ->where('generated_at', '<=', $cutoff) // Only label snapshots old enough
            ->orderByDesc('generated_at');

        if (!$force) {
            $query->whereNull('price_future');
        }

        $snapshots = $query->limit($limit)->get();

        if ($snapshots->isEmpty()) {
            $this->info('No snapshots found matching criteria.');
            return self::SUCCESS;
        }

        $results = $this->processBatch($snapshots, $strategies, $horizonHours, $batchSize, $skipErrors);

        if ($validate) {
            $this->validateAndShowStatistics($results);
        }

        $this->displayResults($results, $outputFormat);
        return self::SUCCESS;
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
