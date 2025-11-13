<?php

namespace App\Console\Commands;

use App\Models\SignalSnapshot;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SignalCleanup extends Command
{
    protected $signature = 'signal:cleanup
        {--dry-run : Show what would be deleted without actually deleting}
        {--older-than=90d : Delete snapshots older than this period}
        {--unlabeled-only : Only delete unlabeled snapshots}
        {--low-quality : Delete snapshots with low quality scores}
        {--orphaned : Delete snapshots without corresponding market data}
        {--duplicates : Remove duplicate snapshots (same symbol, interval, timestamp)}
        {--keep-latest=1 : Number of latest snapshots to keep per day}
        {--symbol= : Limit cleanup to specific symbol}
        {--batch-size=1000 : Batch size for deletion}
        {--force : Skip confirmation prompts}
        {--archive : Move to archive table instead of deleting}
        {--vacuum : Run database vacuum after cleanup}';

    protected $description = 'Clean up old, unlabeled, or problematic signal snapshots';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $olderThan = $this->option('older-than');
        $unlabeledOnly = $this->option('unlabeled-only');
        $lowQuality = $this->option('low-quality');
        $orphaned = $this->option('orphaned');
        $duplicates = $this->option('duplicates');
        $keepLatest = max(1, (int) $this->option('keep-latest'));
        $symbol = $this->option('symbol');
        $batchSize = max(1, (int) $this->option('batch-size'));
        $force = $this->option('force');
        $archive = $this->option('archive');
        $vacuum = $this->option('vacuum');

        $this->info("🧹 Signal Cleanup Process");
        $this->info("⏰ " . ($dryRun ? "DRY RUN - No changes will be made" : "LIVE - Changes will be applied"));
        $this->info("🎯 Criteria: {$this->summarizeCriteria()}");

        if (!$dryRun && !$force) {
            if (!$this->confirm('This will permanently delete signal snapshots. Continue?')) {
                $this->info('Cleanup cancelled.');
                return self::SUCCESS;
            }
        }

        $totalDeleted = 0;
        $operations = [];

        // Create archive table if needed
        if ($archive && !$dryRun) {
            $this->createArchiveTable();
        }

        // Perform cleanup operations
        if ($olderThan) {
            $deleted = $this->cleanupOlderThan($olderThan, $symbol, $unlabeledOnly, $dryRun, $archive);
            $totalDeleted += $deleted;
            $operations[] = "Old snapshots ({$olderThan}): {$deleted} deleted";
        }

        if ($duplicates) {
            $deleted = $this->cleanupDuplicates($symbol, $dryRun, $archive);
            $totalDeleted += $deleted;
            $operations[] = "Duplicate snapshots: {$deleted} deleted";
        }

        if ($lowQuality) {
            $deleted = $this->cleanupLowQuality($symbol, $dryRun, $archive);
            $totalDeleted += $deleted;
            $operations[] = "Low quality snapshots: {$deleted} deleted";
        }

        if ($orphaned) {
            $deleted = $this->cleanupOrphaned($symbol, $dryRun, $archive);
            $totalDeleted += $deleted;
            $operations[] = "Orphaned snapshots: {$deleted} deleted";
        }

        if ($keepLatest > 1) {
            $deleted = $this->cleanupKeepLatest($keepLatest, $symbol, $dryRun, $archive);
            $totalDeleted += $deleted;
            $operations[] = "Keep latest {$keepLatest} per day: {$deleted} deleted";
        }

        // Summary
        $this->info("\n📊 CLEANUP SUMMARY");
        foreach ($operations as $op) {
            $this->line("  • {$op}");
        }
        $this->info("\nTotal snapshots processed: " . number_format($totalDeleted));

        if ($archive && $totalDeleted > 0) {
            $this->info("Snapshots moved to archive table instead of permanent deletion.");
        }

        // Run vacuum if requested
        if ($vacuum && !$dryRun && $totalDeleted > 0) {
            $this->runVacuum();
        }

        return self::SUCCESS;
    }

    protected function summarizeCriteria(): string
    {
        $criteria = [];
        if ($this->option('older-than')) $criteria[] = "Older than " . $this->option('older-than');
        if ($this->option('unlabeled-only')) $criteria[] = "Unlabeled only";
        if ($this->option('low-quality')) $criteria[] = "Low quality";
        if ($this->option('orphaned')) $criteria[] = "Orphaned";
        if ($this->option('duplicates')) $criteria[] = "Duplicates";
        if ($this->option('keep-latest') > 1) $criteria[] = "Keep latest " . $this->option('keep-latest') . " per day";
        if ($this->option('symbol')) $criteria[] = "Symbol: " . strtoupper($this->option('symbol'));

        return empty($criteria) ? "No criteria specified" : implode(', ', $criteria);
    }

    protected function cleanupOlderThan(string $period, ?string $symbol, bool $unlabeledOnly, bool $dryRun, bool $archive): int
    {
        $since = $this->parsePeriod($period);
        if (!$since) {
            $this->error("Invalid period format: {$period}. Use formats like 30d, 90d, 1y");
            return 0;
        }

        $query = SignalSnapshot::where('generated_at', '<', $since);
        if ($symbol) {
            $query->where('symbol', strtoupper($symbol));
        }
        if ($unlabeledOnly) {
            $query->whereNull('price_future');
        }

        return $this->executeDeletion($query, "older than {$period}", $dryRun, $archive);
    }

    protected function cleanupDuplicates(?string $symbol, bool $dryRun, bool $archive): int
    {
        $query = SignalSnapshot::select([
            'symbol', 'interval', 'generated_at', DB::raw('COUNT(*) as count'), DB::raw('MIN(id) as keep_id')
        ])
            ->when($symbol, fn($q) => $q->where('symbol', strtoupper($symbol)))
            ->groupBy(['symbol', 'interval', 'generated_at'])
            ->havingRaw('COUNT(*) > 1');

        $duplicates = $query->get();
        $deleted = 0;

        foreach ($duplicates as $duplicate) {
            $idsToDelete = SignalSnapshot::where('symbol', $duplicate->symbol)
                ->where('interval', $duplicate->interval)
                ->where('generated_at', $duplicate->generated_at)
                ->where('id', '!=', $duplicate->keep_id)
                ->pluck('id');

            if ($idsToDelete->isNotEmpty()) {
                $deleted += $this->deleteByIds($idsToDelete, "duplicates", $dryRun, $archive);
            }
        }

        return $deleted;
    }

    protected function cleanupLowQuality(?string $symbol, bool $dryRun, bool $archive): int
    {
        $query = SignalSnapshot::where(function ($q) {
            $q->where('is_missing_data', true)
                ->orWhere('signal_confidence', '<', 0.3)
                ->orWhere(function ($sub) {
                    $sub->whereNull('price_now')
                        ->orWhere('price_now', '<=', 0);
                });
        });

        if ($symbol) {
            $query->where('symbol', strtoupper($symbol));
        }

        return $this->executeDeletion($query, "low quality", $dryRun, $archive);
    }

    protected function cleanupOrphaned(?string $symbol, bool $dryRun, bool $archive): int
    {
        // Find snapshots that don't have corresponding market data
        $query = SignalSnapshot::whereDoesntHave('features_payload', function ($sub) {
            // Check if features payload has required data
            $sub->whereNotNull('features_payload->funding')
                ->whereNotNull('features_payload->microstructure');
        });

        if ($symbol) {
            $query->where('symbol', strtoupper($symbol));
        }

        return $this->executeDeletion($query, "orphaned", $dryRun, $archive);
    }

    protected function cleanupKeepLatest(int $keepLatest, ?string $symbol, bool $dryRun, bool $archive): int
    {
        $query = SignalSnapshot::select([
            'symbol', 'interval', DB::raw('DATE(generated_at) as date'),
            DB::raw('COUNT(*) as count')
        ])
            ->when($symbol, fn($q) => $q->where('symbol', strtoupper($symbol)))
            ->groupBy(['symbol', 'interval', 'date'])
            ->havingRaw('COUNT(*) > ?', [$keepLatest]);

        $excessDays = $query->get();
        $deleted = 0;

        foreach ($excessDays as $day) {
            $idsToKeep = SignalSnapshot::where('symbol', $day->symbol)
                ->where('interval', $day->interval)
                ->whereDate('generated_at', $day->date)
                ->orderBy('generated_at', 'desc')
                ->limit($keepLatest)
                ->pluck('id');

            $idsToDelete = SignalSnapshot::where('symbol', $day->symbol)
                ->where('interval', $day->interval)
                ->whereDate('generated_at', $day->date)
                ->whereNotIn('id', $idsToKeep)
                ->pluck('id');

            if ($idsToDelete->isNotEmpty()) {
                $deleted += $this->deleteByIds($idsToDelete, "excess for {$day->date}", $dryRun, $archive);
            }
        }

        return $deleted;
    }

    protected function executeDeletion($query, string $description, bool $dryRun, bool $archive): int
    {
        $count = $query->count();

        if ($count === 0) {
            $this->line("✅ No {$description} snapshots found to delete");
            return 0;
        }

        if ($dryRun) {
            $this->line("🔍 DRY RUN: Would delete {$count} {$description} snapshots");
            return $count;
        }

        if ($archive) {
            $this->line("📦 Moving {$count} {$description} snapshots to archive...");
            $this->moveToArchive($query);
        } else {
            $this->line("🗑️  Deleting {$count} {$description} snapshots...");
            $query->delete();
        }

        return $count;
    }

    protected function deleteByIds($ids, string $description, bool $dryRun, bool $archive): int
    {
        $count = $ids->count();

        if ($dryRun) {
            $this->line("🔍 DRY RUN: Would delete {$count} {$description} snapshots");
            return $count;
        }

        if ($archive) {
            $this->moveIdsToArchive($ids);
        } else {
            SignalSnapshot::whereIn('id', $ids)->delete();
        }

        return $count;
    }

    protected function createArchiveTable(): void
    {
        $this->line("📦 Creating archive table if it doesn't exist...");

        DB::statement("
            CREATE TABLE IF NOT EXISTS signal_snapshots_archive (
                LIKE signal_snapshots INCLUDING ALL
            )
        ");
    }

    protected function moveToArchive($query): void
    {
        // Move to archive in batches to avoid memory issues
        $query->orderBy('id')->chunk(1000, function ($snapshots) {
            foreach ($snapshots as $snapshot) {
                DB::table('signal_snapshots_archive')->insert($snapshot->toArray());
                $snapshot->delete();
            }
        });
    }

    protected function moveIdsToArchive($ids): void
    {
        $chunks = $ids->chunk(1000);
        foreach ($chunks as $chunk) {
            $snapshots = SignalSnapshot::whereIn('id', $chunk)->get();
            foreach ($snapshots as $snapshot) {
                DB::table('signal_snapshots_archive')->insert($snapshot->toArray());
                $snapshot->delete();
            }
        }
    }

    protected function runVacuum(): void
    {
        $this->line("🧹 Running database vacuum...");
        DB::statement('VACUUM signal_snapshots');
        $this->line("✅ Vacuum completed");
    }

    protected function parsePeriod(string $period): ?Carbon
    {
        $period = strtolower($period);

        if (preg_match('/^(\d+)d$/', $period, $matches)) {
            return now('UTC')->subDays((int) $matches[1]);
        }

        if (preg_match('/^(\d+)m$/', $period, $matches)) {
            return now('UTC')->subMonths((int) $matches[1]);
        }

        if (preg_match('/^(\d+)y$/', $period, $matches)) {
            return now('UTC')->subYears((int) $matches[1]);
        }

        return null;
    }
}