<?php

namespace App\Console\Commands;

use App\Models\SignalSnapshot;
use App\Models\SignalAnalytics;
use App\Services\Signal\AiSignalService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ShowSignalHistory extends Command
{
    protected $signature = 'signal:history
        {--symbol=BTC : Symbol to show}
        {--limit=50 : Number of entries to display}
        {--direction= : Filter by label outcome (UP/DOWN/SIDEWAYS/PENDING)}
        {--start= : Start date (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)}
        {--end= : End date (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)}
        {--days=30 : Last N days (alternative to start/end)}
        {--overview : Show comprehensive overview instead of table}
        {--export= : Export to file (json,csv,html)}
        {--group=day : Group results by day/week/month}
        {--save : Save analytics data to database instead of displaying}';

    protected $description = 'Display comprehensive signal history with analytics and AI predictions';

    public function __construct(
        protected AiSignalService $aiSignalService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $symbol = strtoupper($this->option('symbol') ?? 'BTC');
        $limit = min(500, max(10, (int) $this->option('limit')));
        $direction = $this->option('direction');
        $overview = $this->option('overview');
        $groupBy = $this->option('group');
        $exportFile = $this->option('export');
        $saveToDb = $this->option('save');

        // Parse date range
        $start = $this->option('start') ? Carbon::parse($this->option('start')) : null;
        $end = $this->option('end') ? Carbon::parse($this->option('end')) : now('UTC');

        if ($this->option('days')) {
            $start = $end->copy()->subDays((int) $this->option('days'));
        } elseif (!$start) {
            $start = $end->copy()->subDays(30);
        }

        if ($overview) {
            return $this->showOverview($symbol, $start, $end, $groupBy, $exportFile, $saveToDb);
        }

        return $this->showDetailedHistory($symbol, $start, $end, $limit, $direction, $exportFile, $saveToDb);
    }

    protected function showOverview($symbol, $start, $end, $groupBy, $exportFile, $saveToDb = false): int
    {
        $this->info("📊 SIGNAL HISTORY OVERVIEW");
        $this->info("Symbol: {$symbol}");
        $this->info("Period: {$start->toIso8601String()} to {$end->toIso8601String()}");
        $this->line(str_repeat('=', 60));

        // Get statistics
        $stats = $this->getSignalStats($symbol, $start, $end, $groupBy);

        // Prepare analytics data for database
        $analyticsData = [
            'symbol' => $symbol,
            'period' => [
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'days' => $start->diffInDays($end),
            ],
            'statistics' => $stats,
            'generated_at' => now()->toIso8601String(),
        ];

        // Save to database if requested
        if ($saveToDb) {
            SignalAnalytics::storeAnalytics(
                $symbol,
                'history',
                $analyticsData,
                [
                    'period_start' => $start,
                    'period_end' => $end,
                    'type' => 'overview'
                ],
                [
                    'symbol' => $symbol,
                    'days' => $start->diffInDays($end),
                    'overview' => true
                ]
            );
            $this->info("✅ Signal history analytics saved to database for {$symbol}");
            return self::SUCCESS;
        }

        // Display overview
        $this->displayOverviewStats($stats, $symbol);
        $this->displaySignalDistribution($stats);
        $this->displayPerformanceMetrics($stats);
        $this->displayTrendAnalysis($symbol, $start, $end);

        // Export if requested
        if ($exportFile) {
            $this->exportOverview($stats, $symbol, $start, $end, $exportFile);
        }

        return self::SUCCESS;
    }

    protected function getSignalStats($symbol, $start, $end, $groupBy)
    {
        $query = SignalSnapshot::where('symbol', $symbol)
            ->whereBetween('generated_at', [$start, $end]);

        $snapshots = $query->get();

        return [
            'total_signals' => $snapshots->count(),
            'labeled_signals' => $snapshots->whereNotNull('label_direction')->count(),
            'by_direction' => $snapshots->whereNotNull('label_direction')->groupBy('label_direction'),
            'by_signal_rule' => $snapshots->groupBy('signal_rule'),
            'daily_stats' => $snapshots->groupBy(function($item) {
                return Carbon::parse($item->generated_at)->format('Y-m-d');
            }),
            'avg_score' => $snapshots->avg('signal_score'),
            'avg_confidence' => $snapshots->avg('signal_confidence'),
            'missing_data' => $snapshots->where('is_missing_data', true)->count(),
            'first_signal' => $snapshots->min('generated_at'),
            'last_signal' => $snapshots->max('generated_at'),
        ];
    }

    protected function displayOverviewStats($stats, $symbol): void
    {
        $this->info("\n📈 GENERAL STATISTICS");
        $this->table(['Metric', 'Value'], [
            ['Total Signals', number_format($stats['total_signals'])],
            ['Labeled Signals', number_format($stats['labeled_signals'])],
            ['Labeling Rate', number_format($stats['total_signals'] > 0 ? ($stats['labeled_signals'] / $stats['total_signals']) * 100 : 0, 2) . '%'],
            ['Average Score', number_format($stats['avg_score'], 3)],
            ['Average Confidence', number_format($stats['avg_confidence'], 3)],
            ['Missing Data', number_format($stats['missing_data'])],
            ['Period Start', $stats['first_signal'] ? Carbon::parse($stats['first_signal'])->toIso8601String() : 'N/A'],
            ['Period End', $stats['last_signal'] ? Carbon::parse($stats['last_signal'])->toIso8601String() : 'N/A'],
        ]);
    }

    protected function displaySignalDistribution($stats): void
    {
        $this->info("\n🎯 SIGNAL DISTRIBUTION");
        if ($stats['by_direction']->isNotEmpty()) {
            $distribution = $stats['by_direction']->map(function($group, $direction) {
                return [$direction, $group->count(), number_format(($group->count() / $stats['labeled_signals']) * 100, 1) . '%'];
            });
            $this->table(['Direction', 'Count', 'Percentage'], $distribution->toArray());
        }

        $this->info("\n⚙️ SIGNAL RULES");
        if ($stats['by_signal_rule']->isNotEmpty()) {
            $rules = $stats['by_signal_rule']->map(function($group, $rule) {
                return [$rule, $group->count(), number_format($group->avg('signal_score'), 3), number_format($group->avg('signal_confidence'), 3)];
            });
            $this->table(['Rule', 'Count', 'Avg Score', 'Avg Confidence'], $rules->toArray());
        }
    }

    protected function displayPerformanceMetrics($stats): void
    {
        $this->info("\n💹 PERFORMANCE ANALYSIS");

        $labeled = SignalSnapshot::whereNotNull('label_direction')
            ->whereNotNull('price_now')
            ->whereNotNull('price_future')
            ->whereBetween('generated_at', [$stats['first_signal'], $stats['last_signal']])
            ->get();

        if ($labeled->isNotEmpty()) {
            $wins = 0;
            $totalReturn = 0;
            $winningReturns = [];
            $losingReturns = [];

            foreach ($labeled as $signal) {
                $returnPct = (($signal->price_future - $signal->price_now) / $signal->price_now) * 100;
                $totalReturn += $returnPct;

                if ($returnPct > 0) {
                    $wins++;
                    $winningReturns[] = $returnPct;
                } else {
                    $losingReturns[] = $returnPct;
                }
            }

            $winRate = ($wins / $labeled->count()) * 100;
            $avgReturn = $totalReturn / $labeled->count();
            $avgWin = count($winningReturns) > 0 ? collect($winningReturns)->avg() : 0;
            $avgLoss = count($losingReturns) > 0 ? collect($losingReturns)->avg() : 0;

            $this->table(['Metric', 'Value'], [
                ['Win Rate', number_format($winRate, 2) . '%'],
                ['Average Return', number_format($avgReturn, 3) . '%'],
                ['Average Win', number_format($avgWin, 3) . '%'],
                ['Average Loss', number_format($avgLoss, 3) . '%'],
                ['Profit Factor', $avgLoss < 0 ? number_format(abs($avgWin / $avgLoss), 2) : 'N/A'],
                ['Total Analyzed', number_format($labeled->count())],
            ]);
        }
    }

    protected function displayTrendAnalysis($symbol, $start, $end): void
    {
        $this->info("\n📅 TREND ANALYSIS");

        $weeklyStats = SignalSnapshot::where('symbol', $symbol)
            ->whereBetween('generated_at', [$start, $end])
            ->whereNotNull('label_direction')
            ->selectRaw('DATE(generated_at) as date, COUNT(*) as count')
            ->groupBy(DB::raw('DATE(generated_at)'))
            ->orderBy('date')
            ->get();

        if ($weeklyStats->isNotEmpty()) {
            $trendData = $weeklyStats->map(function($stat) {
                return [$stat->date, $stat->count];
            });
            $this->table(['Date', 'Signal Count'], $trendData->toArray());
        }
    }

    protected function showDetailedHistory($symbol, $start, $end, $limit, $direction, $exportFile, $saveToDb = false): int
    {
        $query = SignalSnapshot::where('symbol', $symbol)
            ->whereBetween('generated_at', [$start, $end])
            ->orderByDesc('generated_at');

        if ($direction) {
            if (Str::upper($direction) === 'PENDING') {
                $query->whereNull('label_direction');
            } else {
                $query->where('label_direction', strtoupper($direction));
            }
        }

        $rows = $query->limit($limit)->get();

        if ($rows->isEmpty()) {
            $this->warn('No signal entries found matching criteria.');
            return self::SUCCESS;
        }

        $tableData = $rows->map(function (SignalSnapshot $snapshot) {
            $ai = $this->aiSignalService->predict(
                $snapshot->features_payload ?? [],
                ['score' => $snapshot->signal_score]
            );

            $returnPct = ($snapshot->price_future && $snapshot->price_now)
                ? (($snapshot->price_future - $snapshot->price_now) / $snapshot->price_now) * 100
                : null;

            return [
                optional($snapshot->generated_at)->format('Y-m-d H:i'),
                $snapshot->signal_rule,
                number_format($snapshot->signal_score, 2),
                $snapshot->label_direction ?? 'PENDING',
                $returnPct !== null ? number_format($returnPct, 2) . '%' : '--',
                $ai ? number_format($ai['probability'] * 100, 2) . '%' : '--',
                $ai['decision'] ?? '--',
                $snapshot->is_missing_data ? '❌' : '✅',
            ];
        });

        $this->table(
            ['Time (UTC)', 'Signal', 'Score', 'Outcome', 'Δ Price', 'AI Prob', 'AI Decision', 'Data Quality'],
            $tableData->toArray()
        );

        // Prepare analytics data for database
        $analyticsData = [
            'symbol' => $symbol,
            'period' => [
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'days' => $start->diffInDays($end),
            ],
            'detailed_history' => $tableData->toArray(),
            'metadata' => [
                'limit' => $limit,
                'direction' => $direction,
                'total_rows' => $rows->count(),
            ],
            'generated_at' => now()->toIso8601String(),
        ];

        // Save to database if requested
        if ($saveToDb) {
            SignalAnalytics::storeAnalytics(
                $symbol,
                'history',
                $analyticsData,
                [
                    'period_start' => $start,
                    'period_end' => $end,
                    'type' => 'detailed'
                ],
                [
                    'symbol' => $symbol,
                    'limit' => $limit,
                    'direction' => $direction,
                    'detailed' => true
                ]
            );
            $this->info("✅ Detailed signal history saved to database for {$symbol}");
            return self::SUCCESS;
        }

        // Export if requested
        if ($exportFile) {
            $this->exportDetailedHistory($tableData, $symbol, $start, $end, $exportFile);
        }

        return self::SUCCESS;
    }

    protected function exportOverview($stats, $symbol, $start, $end, $filename): void
    {
        $exportData = [
            'overview' => [
                'symbol' => $symbol,
                'period' => [
                    'start' => $start->toIso8601String(),
                    'end' => $end->toIso8601String(),
                ],
                'statistics' => [
                    'total_signals' => $stats['total_signals'],
                    'labeled_signals' => $stats['labeled_signals'],
                    'labeling_rate' => $stats['total_signals'] > 0 ? ($stats['labeled_signals'] / $stats['total_signals']) * 100 : 0,
                    'avg_score' => $stats['avg_score'],
                    'avg_confidence' => $stats['avg_confidence'],
                    'missing_data_count' => $stats['missing_data'],
                ],
            ],
            'exported_at' => now()->toIso8601String(),
        ];

        file_put_contents($filename, json_encode($exportData, JSON_PRETTY_PRINT));
        $this->info("✅ Overview exported to: {$filename}");
    }

    protected function exportDetailedHistory($tableData, $symbol, $start, $end, $filename): void
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            $csv = "Time,Signal,Score,Outcome,Price Change,AI Probability,AI Decision,Data Quality\n";
            foreach ($tableData as $row) {
                $csv .= implode(',', $row) . "\n";
            }
            file_put_contents($filename, $csv);
        } elseif ($extension === 'html') {
            $html = $this->generateHtmlReport($tableData, $symbol, $start, $end);
            file_put_contents($filename, $html);
        } else {
            file_put_contents($filename, json_encode($tableData, JSON_PRETTY_PRINT));
        }

        $this->info("✅ History exported to: {$filename}");
    }

    protected function generateHtmlReport($tableData, $symbol, $start, $end): string
    {
        $html = "<html><head><title>Signal History Report - {$symbol}</title>";
        $html .= "<style>body{font-family:Arial,sans-serif;margin:20px;}table{border-collapse:collapse;width:100%;}";
        $html .= "th,td{border:1px solid #ddd;padding:8px;text-align:left;}";
        $html .= "th{background-color:#f2f2f2;}</style></head><body>";
        $html .= "<h1>Signal History Report - {$symbol}</h1>";
        $html .= "<p>Period: {$start->toIso8601String()} to {$end->toIso8601String()}</p>";
        $html .= "<table><tr><th>Time (UTC)</th><th>Signal</th><th>Score</th><th>Outcome</th><th>Δ Price</th><th>AI Prob</th><th>AI Decision</th><th>Data Quality</th></tr>";

        foreach ($tableData as $row) {
            $html .= "<tr><td>" . implode("</td><td>", $row) . "</td></tr>";
        }

        $html .= "</table></body></html>";
        return $html;
    }
}
