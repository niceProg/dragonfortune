<?php

namespace App\Console\Commands;

use App\Models\SignalSnapshot;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DebugSignalData extends Command
{
    protected $signature = 'signal:debug {--symbol=BTC : Symbol to check}';

    protected $description = 'Debug signal data to understand what snapshots exist and their labeling status';

    public function handle(): int
    {
        $symbol = strtoupper($this->option('symbol'));

        $this->info("Debugging signal data for {$symbol}");
        $this->line(str_repeat('=', 50));

        // 1. Total snapshots
        $totalSnapshots = SignalSnapshot::where('symbol', $symbol)->count();
        $this->line("1. Total snapshots for {$symbol}: {$totalSnapshots}");

        // 2. Labeled snapshots (with price_future)
        $labeledSnapshots = SignalSnapshot::where('symbol', $symbol)
            ->whereNotNull('price_future')
            ->count();
        $this->line("2. Labeled snapshots (price_future not null): {$labeledSnapshots}");

        // 3. Date range of all snapshots
        $dateRange = SignalSnapshot::where('symbol', $symbol)
            ->selectRaw('MIN(generated_at) as earliest, MAX(generated_at) as latest')
            ->first();

        if ($dateRange->earliest && $dateRange->latest) {
            $this->line("3. Date range of all snapshots:");
            $this->line("   Earliest: {$dateRange->earliest}");
            $this->line("   Latest: {$dateRange->latest}");
        }

        // 4. Date range of labeled snapshots
        $labeledDateRange = SignalSnapshot::where('symbol', $symbol)
            ->whereNotNull('price_future')
            ->selectRaw('MIN(generated_at) as earliest, MAX(generated_at) as latest')
            ->first();

        if ($labeledDateRange->earliest && $labeledDateRange->latest) {
            $this->line("4. Date range of labeled snapshots:");
            $this->line("   Earliest: {$labeledDateRange->earliest}");
            $this->line("   Latest: {$labeledDateRange->latest}");
        }

        // 5. Recent snapshots (last 30 days)
        $thirtyDaysAgo = now('UTC')->subDays(30);
        $recentSnapshots = SignalSnapshot::where('symbol', $symbol)
            ->where('generated_at', '>=', $thirtyDaysAgo)
            ->count();

        $recentLabeledSnapshots = SignalSnapshot::where('symbol', $symbol)
            ->where('generated_at', '>=', $thirtyDaysAgo)
            ->whereNotNull('price_future')
            ->count();

        $this->line("5. Snapshots in last 30 days: {$recentSnapshots}");
        $this->line("   Labeled snapshots in last 30 days: {$recentLabeledSnapshots}");

        // 6. October 2024 snapshots (your replay period)
        $octoberStart = Carbon::parse('2024-10-01', 'UTC');
        $octoberEnd = Carbon::parse('2024-11-01', 'UTC');

        $octoberSnapshots = SignalSnapshot::where('symbol', $symbol)
            ->whereBetween('generated_at', [$octoberStart, $octoberEnd])
            ->count();

        $octoberLabeledSnapshots = SignalSnapshot::where('symbol', $symbol)
            ->whereBetween('generated_at', [$octoberStart, $octoberEnd])
            ->whereNotNull('price_future')
            ->count();

        $this->line("6. Snapshots from October 2024 (your replay period): {$octoberSnapshots}");
        $this->line("   Labeled October 2024 snapshots: {$octoberLabeledSnapshots}");

        // 7. Sample of recent unlabeled snapshots
        $unlabeledSamples = SignalSnapshot::where('symbol', $symbol)
            ->whereNull('price_future')
            ->orderByDesc('generated_at')
            ->limit(5)
            ->get(['generated_at', 'price_now', 'price_future', 'label_direction']);

        if ($unlabeledSamples->isNotEmpty()) {
            $this->line("7. Recent unlabeled snapshots (samples):");
            foreach ($unlabeledSamples as $sample) {
                $this->line("   {$sample->generated_at}: price_now={$sample->price_now}, price_future={$sample->price_future}");
            }
        }

        // 8. Recommendations
        $this->line(str_repeat('=', 50));
        $this->info("Recommendations:");

        if ($octoberSnapshots > 0 && $octoberLabeledSnapshots === 0) {
            $this->line("- You have October 2024 snapshots but they're not labeled. Run:");
            $this->line("  php artisan signal:label --symbol={$symbol} --start=2024-10-01T00:00:00Z --end=2024-11-01T00:00:00Z --limit=1000");
        }

        if ($recentLabeledSnapshots === 0) {
            $this->line("- No labeled snapshots in last 30 days. Try backtesting October:");
            $this->line("  php artisan signal:backtest --symbol={$symbol} --start=2024-10-01T00:00:00Z --end=2024-11-01T00:00:00Z");
        } else {
            $this->line("- You have {$recentLabeledSnapshots} labeled snapshots in last 30 days. Backtest should work:");
            $this->line("  php artisan signal:backtest --symbol={$symbol} --days=30");
        }

        return self::SUCCESS;
    }
}