<?php

namespace App\Console\Commands;

use App\Models\SignalSnapshot;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SignalStatus extends Command
{
    protected $signature = 'signal:status
        {--symbol= : Filter by specific symbol}
        {--period=7d : Time period to analyze (1h, 24h, 7d, 30d, 90d)}
        {--json : Output in JSON format}
        {--detailed : Show detailed breakdown by strategy and confidence}
        {--problems : Show only problems and warnings}
        {--export= : Export status report to file}';

    protected $description = 'Comprehensive signal system status and health check';

    public function handle(): int
    {
        $symbol = $this->option('symbol');
        $period = $this->option('period');
        $json = $this->option('json');
        $detailed = $this->option('detailed');
        $problemsOnly = $this->option('problems');
        $exportFile = $this->option('export');

        $this->info("🔍 Signal System Status Report");
        $this->info("⏰ Generated: " . now('UTC')->toIso8601String());

        $status = $this->gatherStatusData($symbol, $period);

        if ($problemsOnly) {
            $this->filterProblems($status);
        }

        if ($json) {
            $this->line(json_encode($status, JSON_PRETTY_PRINT));
        } else {
            $this->displayStatus($status, $detailed);
        }

        if ($exportFile) {
            file_put_contents($exportFile, json_encode($status, JSON_PRETTY_PRINT));
            $this->info("📄 Status exported to: {$exportFile}");
        }

        return self::SUCCESS;
    }

    protected function gatherStatusData(?string $symbol, string $period): array
    {
        $now = now('UTC');
        $detailed = $this->option('detailed');
        $periodMap = [
            '1h' => $now->copy()->subHour(),
            '24h' => $now->copy()->subDay(),
            '7d' => $now->copy()->subDays(7),
            '30d' => $now->copy()->subDays(30),
            '90d' => $now->copy()->subDays(90),
        ];

        $since = $periodMap[$period] ?? $now->copy()->subDays(7);

        $data = [
            'period' => $period,
            'generated_at' => $now->toIso8601String(),
            'snapshots' => $this->getSnapshotStats($since, $symbol),
            'data_sources' => $this->checkDataSources(),
            'system_health' => $this->checkSystemHealth(),
            'alerts' => $this->generateAlerts($since, $symbol),
            'recommendations' => $this->generateRecommendations($since, $symbol),
        ];

        if ($detailed) {
            $data['detailed'] = $this->getDetailedAnalysis($since, $symbol);
        }

        return $data;
    }

    protected function getSnapshotStats(Carbon $since, ?string $symbol): array
    {
        $query = SignalSnapshot::where('generated_at', '>=', $since);
        if ($symbol) {
            $query->where('symbol', strtoupper($symbol));
        }

        $total = $query->count();
        $labeled = $query->whereNotNull('price_future')->count();
        $unlabeled = $total - $labeled;

        if ($total === 0) {
            return [
                'total' => 0,
                'labeled' => 0,
                'unlabeled' => 0,
                'labeling_rate' => 0,
                'symbols' => [],
                'symbol_count' => 0,
                'intervals' => [],
                'interval_count' => 0,
                'latest' => null,
                'signal_distribution' => [],
                'confidence_distribution' => [],
                'daily_snapshots' => [],
            ];
        }

        $symbols = $query->distinct()->pluck('symbol')->sort()->values();
        $intervals = $query->distinct()->pluck('interval')->sort()->values();
        $latest = $query->latest('generated_at')->first();

        // Signal distribution
        $signalDistribution = $query->selectRaw('signal_rule, COUNT(*) as count')
            ->groupBy('signal_rule')
            ->orderByDesc('count')
            ->pluck('count', 'signal_rule')
            ->toArray();

        // Confidence distribution
        $confidenceDistribution = $query->selectRaw(
            'CASE ' .
            'WHEN signal_confidence >= 0.8 THEN "high" ' .
            'WHEN signal_confidence >= 0.5 THEN "medium" ' .
            'ELSE "low" END as confidence_level, ' .
            'COUNT(*) as count'
        )
            ->groupBy('confidence_level')
            ->pluck('count', 'confidence_level')
            ->toArray();

        return [
            'total' => $total,
            'labeled' => $labeled,
            'unlabeled' => $unlabeled,
            'labeling_rate' => $total > 0 ? round(($labeled / $total) * 100, 2) : 0,
            'symbols' => $symbols->toArray(),
            'symbol_count' => $symbols->count(),
            'intervals' => $intervals->toArray(),
            'interval_count' => $intervals->count(),
            'latest' => $latest ? [
                'symbol' => $latest->symbol,
                'timestamp' => $latest->generated_at->toIso8601String(),
                'signal' => $latest->signal_rule,
                'score' => $latest->signal_score,
                'confidence' => $latest->signal_confidence,
                'has_label' => !is_null($latest->price_future),
            ] : null,
            'signal_distribution' => $signalDistribution,
            'confidence_distribution' => $confidenceDistribution,
            'daily_snapshots' => $this->getDailySnapshotCounts($since, $symbol),
        ];
    }

    protected function getDailySnapshotCounts(Carbon $since, ?string $symbol): array
    {
        $query = SignalSnapshot::where('generated_at', '>=', $since);
        if ($symbol) {
            $query->where('symbol', strtoupper($symbol));
        }

        return $query->selectRaw('DATE(generated_at) as date, COUNT(*) as total, COUNT(price_future) as labeled')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()
            ->keyBy('date')
            ->map(function ($item) {
                return [
                    'total' => $item->total,
                    'labeled' => $item->labeled,
                    'labeling_rate' => $item->total > 0 ? round(($item->labeled / $item->total) * 100, 2) : 0,
                ];
            })
            ->toArray();
    }

    protected function checkDataSources(): array
    {
        $tables = [
            'cg_funding_rates' => 'Funding Rates',
            'cg_open_interest' => 'Open Interest',
            'cg_spot_prices' => 'Spot Prices',
            'cg_liquidations' => 'Liquidations',
            'cg_whale_transfers' => 'Whale Transfers',
            'cg_etf_flows' => 'ETF Flows',
        ];

        $status = [];

        foreach ($tables as $table => $name) {
            try {
                $count = DB::table($table)->count();
                $latest = DB::table($table)->latest('timestamp')->first();

                $status[$table] = [
                    'name' => $name,
                    'table_exists' => true,
                    'record_count' => $count,
                    'latest_record' => $latest ? [
                        'timestamp' => $latest->timestamp,
                        'age_hours' => Carbon::createFromTimestamp($latest->timestamp)->diffInHours(now('UTC')),
                    ] : null,
                    'status' => $this->evaluateTableHealth($count, $latest),
                ];
            } catch (\Exception $e) {
                $status[$table] = [
                    'name' => $name,
                    'table_exists' => false,
                    'record_count' => 0,
                    'error' => $e->getMessage(),
                    'status' => 'error',
                    'latest_record' => null,
                ];
            }
        }

        return $status;
    }

    protected function evaluateTableHealth(int $count, $latest): string
    {
        if ($count === 0) {
            return 'empty';
        }

        if (!$latest) {
            return 'no_timestamps';
        }

        $age = Carbon::createFromTimestamp($latest->timestamp)->diffInHours(now('UTC'));

        if ($age < 1) {
            return 'excellent';
        } elseif ($age < 24) {
            return 'good';
        } elseif ($age < 72) {
            return 'warning';
        } else {
            return 'critical';
        }
    }

    protected function checkSystemHealth(): array
    {
        return [
            'labeling_lag' => $this->calculateLabelingLag(),
            'snapshot_frequency' => $this->calculateSnapshotFrequency(),
            'data_completeness' => $this->calculateDataCompleteness(),
            'performance' => $this->checkPerformanceMetrics(),
        ];
    }

    protected function calculateLabelingLag(): array
    {
        $unlabeled = SignalSnapshot::whereNull('price_future')
            ->where('generated_at', '<=', now('UTC')->subHours(48))
            ->orderBy('generated_at', 'asc')
            ->first();

        if (!$unlabeled) {
            return ['status' => 'healthy', 'oldest_unlabeled_hours' => 0];
        }

        $lagHours = $unlabeled->generated_at->diffInHours(now('UTC'));

        return [
            'status' => $lagHours > 72 ? 'critical' : ($lagHours > 48 ? 'warning' : 'healthy'),
            'oldest_unlabeled_hours' => $lagHours,
            'oldest_unlabeled_timestamp' => $unlabeled->generated_at->toIso8601String(),
        ];
    }

    protected function calculateSnapshotFrequency(): array
    {
        $last24h = SignalSnapshot::where('generated_at', '>=', now('UTC')->subHours(24))->count();
        $expectedPerDay = 24; // Assuming hourly snapshots

        $actualPerDay = $last24h;
        $efficiency = ($actualPerDay / $expectedPerDay) * 100;

        return [
            'snapshots_last_24h' => $actualPerDay,
            'expected_per_day' => $expectedPerDay,
            'efficiency_percent' => round($efficiency, 2),
            'status' => $efficiency >= 90 ? 'excellent' : ($efficiency >= 70 ? 'good' : 'needs_improvement'),
        ];
    }

    protected function calculateDataCompleteness(): array
    {
        $total = SignalSnapshot::where('generated_at', '>=', now('UTC')->subDays(7))->count();
        $withLabels = SignalSnapshot::where('generated_at', '>=', now('UTC')->subDays(7))
            ->whereNotNull('price_future')
            ->count();

        $completeness = $total > 0 ? ($withLabels / $total) * 100 : 0;

        return [
            'total_snapshots_7d' => $total,
            'labeled_snapshots_7d' => $withLabels,
            'completeness_percent' => round($completeness, 2),
            'status' => $completeness >= 80 ? 'excellent' : ($completeness >= 60 ? 'good' : 'needs_attention'),
        ];
    }

    protected function checkPerformanceMetrics(): array
    {
        return [
            'db_connection' => $this->testDatabaseConnection(),
            'memory_usage' => memory_get_usage(true) / 1024 / 1024, // MB
            'peak_memory' => memory_get_peak_usage(true) / 1024 / 1024, // MB
        ];
    }

    protected function testDatabaseConnection(): array
    {
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $responseTime = round((microtime(true) - $start) * 1000, 2); // ms

            return [
                'status' => 'connected',
                'response_time_ms' => $responseTime,
                'status_quality' => $responseTime < 10 ? 'excellent' : ($responseTime < 50 ? 'good' : 'slow'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
                'status_quality' => 'critical',
            ];
        }
    }

    protected function generateAlerts(Carbon $since, ?string $symbol): array
    {
        $alerts = [];

        // Check for missing recent snapshots
        $recentCount = SignalSnapshot::where('generated_at', '>=', now('UTC')->subHours(6))->count();
        if ($recentCount < 3) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Low snapshot activity: only {$recentCount} snapshots in last 6 hours",
                'severity' => 'medium',
            ];
        }

        // Check labeling lag
        $unlabeledHours = SignalSnapshot::whereNull('price_future')
            ->where('generated_at', '<=', now('UTC')->subHours(48))
            ->count();

        if ($unlabeledHours > 100) {
            $alerts[] = [
                'type' => 'critical',
                'message' => "Significant labeling backlog: {$unlabeledHours} snapshots older than 48 hours",
                'severity' => 'high',
            ];
        }

        // Check data source health
        $dataSources = $this->checkDataSources();
        foreach ($dataSources as $table => $source) {
            if ($source['status'] === 'critical' || $source['status'] === 'error') {
                $alerts[] = [
                    'type' => 'error',
                    'message' => "Data source issue: {$source['name']} - {$source['status']}",
                    'severity' => 'high',
                    'table' => $table,
                ];
            }
        }

        return $alerts;
    }

    protected function generateRecommendations(Carbon $since, ?string $symbol): array
    {
        $recommendations = [];

        $stats = $this->getSnapshotStats($since, $symbol);

        // Labeling recommendations
        if ($stats['unlabeled'] > 0) {
            $recommendations[] = [
                'type' => 'action',
                'message' => "Run signal labeling: php artisan signal:label --symbol=" . ($symbol ?? 'all') . " --limit={$stats['unlabeled']}",
                'priority' => 'high',
            ];
        }

        // Data collection recommendations
        $dataSources = $this->checkDataSources();
        foreach ($dataSources as $table => $source) {
            if ($source['status'] === 'warning' || $source['status'] === 'critical') {
                $recommendations[] = [
                    'type' => 'investigate',
                    'message' => "Check {$source['name']} data source - appears stale or missing",
                    'priority' => 'medium',
                    'table' => $table,
                ];
            }
        }

        // System performance recommendations
        $performance = $this->checkPerformanceMetrics();
        if ($performance['memory_usage'] > 512) { // 512 MB
            $recommendations[] = [
                'type' => 'optimization',
                'message' => "High memory usage detected ({$performance['memory_usage']} MB). Consider optimizing queries.",
                'priority' => 'low',
            ];
        }

        return $recommendations;
    }

    protected function getDetailedAnalysis(Carbon $since, ?string $symbol): array
    {
        $query = SignalSnapshot::where('generated_at', '>=', $since);
        if ($symbol) {
            $query->where('symbol', strtoupper($symbol));
        }

        // Performance by confidence level
        $performanceByConfidence = $query->whereNotNull('price_future')
            ->selectRaw(
                'CASE ' .
                'WHEN signal_confidence >= 0.8 THEN "high" ' .
                'WHEN signal_confidence >= 0.5 THEN "medium" ' .
                'ELSE "low" END as confidence_level, ' .
                'AVG(label_magnitude) as avg_return, ' .
                'COUNT(*) as count, ' .
                'SUM(CASE WHEN label_direction = "UP" AND signal_rule = "BUY" THEN 1 ELSE 0 END) as correct_buys, ' .
                'SUM(CASE WHEN label_direction = "DOWN" AND signal_rule = "SELL" THEN 1 ELSE 0 END) as correct_sells'
            )
            ->groupBy('confidence_level')
            ->get();

        // Signal accuracy over time
        $accuracyOverTime = $query->whereNotNull('price_future')
            ->selectRaw('DATE(generated_at) as date, COUNT(*) as total, ' .
                'SUM(CASE WHEN ((label_direction = "UP" AND signal_rule = "BUY") OR (label_direction = "DOWN" AND signal_rule = "SELL")) THEN 1 ELSE 0 END) as correct')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        return [
            'performance_by_confidence' => $performanceByConfidence->map(function ($row) {
                $correctSignals = $row->correct_buys + $row->correct_sells;
                return [
                    'confidence_level' => $row->confidence_level,
                    'avg_return' => round($row->avg_return, 2),
                    'count' => $row->count,
                    'accuracy_rate' => $row->count > 0 ? round(($correctSignals / $row->count) * 100, 2) : 0,
                ];
            })->toArray(),
            'accuracy_over_time' => $accuracyOverTime->map(function ($row) {
                return [
                    'date' => $row->date,
                    'accuracy_rate' => $row->total > 0 ? round(($row->correct / $row->total) * 100, 2) : 0,
                    'total_trades' => $row->total,
                ];
            })->toArray(),
        ];
    }

    protected function filterProblems(array &$status): void
    {
        // Filter to show only problems
        $problematicSources = [];
        foreach ($status['data_sources'] as $table => $source) {
            if (in_array($source['status'], ['error', 'critical', 'empty'])) {
                $problematicSources[$table] = $source;
            }
        }
        $status['data_sources'] = $problematicSources;

        // Keep only alerts (they're all problems)
        // Remove detailed analysis unless it has issues
        if (isset($status['detailed'])) {
            $status['detailed'] = []; // Remove detailed analysis for problems-only view
        }
    }

    protected function displayStatus(array $status, bool $detailed): void
    {
        $this->info("\n📊 SNAPSHOT OVERVIEW");
        $this->table(['Metric', 'Value'], [
            ['Total Snapshots', number_format($status['snapshots']['total'])],
            ['Labeled Snapshots', number_format($status['snapshots']['labeled'])],
            ['Unlabeled Snapshots', number_format($status['snapshots']['unlabeled'])],
            ['Labeling Rate', $status['snapshots']['labeling_rate'] . '%'],
            ['Symbols', $status['snapshots']['symbol_count']],
            ['Intervals', $status['snapshots']['interval_count']],
        ]);

        $this->info("\n📈 SIGNAL DISTRIBUTION");
        $signalData = [];
        foreach ($status['snapshots']['signal_distribution'] as $signal => $count) {
            $signalData[] = [$signal, $count];
        }
        $this->table(['Signal', 'Count'], $signalData);

        $this->info("\n💾 DATA SOURCES STATUS");
        $dataSourceData = [];
        foreach ($status['data_sources'] as $table => $source) {
            $age = $source['latest_record']['age_hours'] ?? 'N/A';
            $dataSourceData[] = [
                $source['name'],
                number_format($source['record_count'] ?? 0),
                $source['status'],
                $age === 'N/A' ? 'N/A' : $age . 'h'
            ];
        }
        $this->table(['Data Source', 'Records', 'Status', 'Age'], $dataSourceData);

        if (!empty($status['alerts'])) {
            $this->info("\n⚠️  ALERTS");
            foreach ($status['alerts'] as $alert) {
                $this->line("  [" . strtoupper($alert['type']) . "] " . $alert['message']);
            }
        }

        if (!empty($status['recommendations'])) {
            $this->info("\n💡 RECOMMENDATIONS");
            foreach ($status['recommendations'] as $rec) {
                $priorityIcon = $rec['priority'] === 'high' ? '🔴' : ($rec['priority'] === 'medium' ? '🟡' : '🟢');
                $this->line("  {$priorityIcon} " . $rec['message']);
            }
        }

        if ($detailed && isset($status['detailed'])) {
            $this->displayDetailedAnalysis($status['detailed']);
        }
    }

    protected function displayDetailedAnalysis(array $detailed): void
    {
        $this->info("\n🔍 DETAILED ANALYSIS");

        if (!empty($detailed['performance_by_confidence'])) {
            $this->info("\nPerformance by Confidence Level:");
            $this->table(['Confidence', 'Avg Return', 'Count', 'Accuracy Rate'],
                collect($detailed['performance_by_confidence'])->map(function ($data) {
                    return [
                        $data['confidence_level'],
                        $data['avg_return'] . '%',
                        $data['count'],
                        $data['accuracy_rate'] . '%'
                    ];
                })->toArray()
            );
        }

        if (!empty($detailed['accuracy_over_time'])) {
            $this->info("\nRecent Accuracy Trend:");
            $this->table(['Date', 'Accuracy Rate', 'Total Trades'],
                array_slice(array_map(function ($data) {
                    return [
                        $data['date'],
                        $data['accuracy_rate'] . '%',
                        $data['total_trades']
                    ];
                }, $detailed['accuracy_over_time']), 0, 10) // Show last 10 days
            );
        }
    }
}