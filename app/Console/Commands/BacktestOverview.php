<?php

namespace App\Console\Commands;

use App\Models\SignalSnapshot;
use App\Models\SignalAnalytics;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BacktestOverview extends Command
{
    protected $signature = 'backtest:overview
        {--symbol=BTC : Symbol to analyze}
        {--start= : Start date (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)}
        {--end= : End date (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)}
        {--days=30 : Last N days (alternative to start/end)}
        {--strategies=rule,ai,ensemble : Backtest strategies to analyze}
        {--export= : Export to file (json,csv,html)}
        {--compare : Compare multiple strategies}
        {--summary : Show summary only}
        {--detailed : Show detailed trade-by-trade analysis}
        {--save : Save backtest data to database instead of displaying}';

    protected $description = 'Generate comprehensive backtest overview and analysis';

    public function handle(): int
    {
        $symbol = strtoupper($this->option('symbol') ?? 'BTC');
        $strategies = $this->parseStrategies($this->option('strategies') ?? 'rule');
        $start = $this->option('start') ? Carbon::parse($this->option('start')) : null;
        $end = $this->option('end') ? Carbon::parse($this->option('end')) : now('UTC');
        $days = (int) $this->option('days');
        $exportFile = $this->option('export');
        $compare = $this->option('compare');
        $summary = $this->option('summary');
        $detailed = $this->option('detailed');
        $saveToDb = $this->option('save');

        if ($days && !$start) {
            $start = $end->copy()->subDays($days);
        } elseif (!$start) {
            $start = $end->copy()->subDays(30);
        }

        $this->info("📊 BACKTEST OVERVIEW");
        $this->info("Symbol: {$symbol}");
        $this->info("Period: {$start->toIso8601String()} to {$end->toIso8601String()}");
        $this->info("Strategies: " . implode(', ', $strategies));
        $this->line(str_repeat('=', 70));

        // Get labeled snapshots
        $snapshots = SignalSnapshot::where('symbol', $symbol)
            ->whereBetween('generated_at', [$start, $end])
            ->whereNotNull('label_direction')
            ->whereNotNull('price_now')
            ->whereNotNull('price_future')
            ->orderBy('generated_at')
            ->get();

        if ($snapshots->isEmpty()) {
            $this->warn('No labeled snapshots found for the specified period and symbol.');
            $this->info('Tip: Run `php artisan signal:label` first to label your signal data.');
            return self::SUCCESS;
        }

        // Analyze different strategies
        $results = [];
        foreach ($strategies as $strategy) {
            $results[$strategy] = $this->analyzeStrategy($snapshots, $strategy);
        }

        // Display results
        if ($summary) {
            $this->displaySummary($results, $symbol);
        } elseif ($detailed) {
            $this->displayDetailedAnalysis($results, $snapshots, $symbol, $start, $end);
        } else {
            $this->displayOverview($results, $symbol, $start, $end);
        }

        if ($compare && count($results) > 1) {
            $this->compareStrategies($results);
        }

        // Prepare backtest analytics data for database
        $backtestData = [
            'symbol' => $symbol,
            'period' => [
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
                'days' => $start->diffInDays($end),
            ],
            'strategies' => $results,
            'metadata' => [
                'total_strategies' => count($strategies),
                'total_snapshots' => $snapshots->count(),
                'mode' => $summary ? 'summary' : ($detailed ? 'detailed' : 'overview'),
                'compare' => $compare,
            ],
            'generated_at' => now()->toIso8601String(),
        ];

        // Save to database if requested
        if ($saveToDb) {
            SignalAnalytics::storeAnalytics(
                $symbol,
                'backtest',
                $backtestData,
                [
                    'period_start' => $start,
                    'period_end' => $end,
                    'type' => $summary ? 'summary' : ($detailed ? 'detailed' : 'overview')
                ],
                [
                    'symbol' => $symbol,
                    'strategies' => $strategies,
                    'summary' => $summary,
                    'detailed' => $detailed,
                    'compare' => $compare,
                    'days' => $days,
                ]
            );
            $this->info("✅ Backtest overview saved to database for {$symbol}");
            return self::SUCCESS;
        }

        // Display results
        if ($summary) {
            $this->displaySummary($results, $symbol);
        } elseif ($detailed) {
            $this->displayDetailedAnalysis($results, $snapshots, $symbol, $start, $end);
        } else {
            $this->displayOverview($results, $symbol, $start, $end);
        }

        if ($compare && count($results) > 1) {
            $this->compareStrategies($results);
        }

        // Export if requested
        if ($exportFile) {
            $this->exportResults($results, $symbol, $start, $end, $exportFile);
        }

        return self::SUCCESS;
    }

    protected function parseStrategies(string $input): array
    {
        $available = ['rule', 'ai', 'ensemble', 'buy-and-hold', 'random'];
        $requested = explode(',', $input);
        return array_intersect($available, array_map('trim', $requested));
    }

    protected function analyzeStrategy($snapshots, $strategy): array
    {
        $trades = [];
        $wins = 0;
        $totalReturn = 0;
        $maxDrawdown = 0;
        $currentDrawdown = 0;
        $peak = 1; // Starting portfolio value

        foreach ($snapshots as $snapshot) {
            $signal = $this->getSignalForStrategy($snapshot, $strategy);
            $actualReturn = (($snapshot->price_future - $snapshot->price_now) / $snapshot->price_now) * 100;

            if ($signal['action'] !== 'HOLD') {
                // Simulate trade
                $tradeReturn = $this->calculateTradeReturn($snapshot, $signal, $strategy);
                $trades[] = [
                    'timestamp' => $snapshot->generated_at,
                    'signal_rule' => $snapshot->signal_rule,
                    'signal_action' => $signal['action'],
                    'entry_price' => $snapshot->price_now,
                    'exit_price' => $snapshot->price_future,
                    'actual_return' => $actualReturn,
                    'trade_return' => $tradeReturn,
                    'win' => $tradeReturn > 0,
                ];

                if ($tradeReturn > 0) {
                    $wins++;
                }

                $totalReturn += $tradeReturn;

                // Update portfolio value and calculate drawdown
                $peak = max($peak, 1 + ($totalReturn / 100));
                $currentDrawdown = $peak - (1 + ($totalReturn / 100));
                $maxDrawdown = max($maxDrawdown, $currentDrawdown);
            }
        }

        $totalTrades = count($trades);
        $winRate = $totalTrades > 0 ? ($wins / $totalTrades) * 100 : 0;
        $profitFactor = $this->calculateProfitFactor($trades);

        return [
            'strategy' => $strategy,
            'total_trades' => $totalTrades,
            'wins' => $wins,
            'losses' => $totalTrades - $wins,
            'win_rate' => $winRate,
            'total_return' => $totalReturn,
            'avg_return' => $totalTrades > 0 ? $totalReturn / $totalTrades : 0,
            'max_drawdown' => $maxDrawdown,
            'sharpe_ratio' => $this->calculateSharpeRatio($trades),
            'profit_factor' => $profitFactor,
            'trades' => $trades,
        ];
    }

    protected function getSignalForStrategy($snapshot, $strategy)
    {
        $signalRule = $snapshot->signal_rule;
        $confidence = $snapshot->signal_confidence;

        switch ($strategy) {
            case 'rule':
                $action = $signalRule;
                break;
            case 'ai':
                $action = $confidence > 0.6 ? $signalRule : 'HOLD';
                break;
            case 'ensemble':
                $action = ($confidence > 0.7) ? $signalRule : 'HOLD';
                break;
            case 'buy-and-hold':
                $action = 'BUY'; // Always long
                break;
            case 'random':
                $actions = ['BUY', 'SELL', 'HOLD'];
                $action = $actions[array_rand($actions)];
                break;
            default:
                $action = 'HOLD';
        }

        return ['action' => $action, 'confidence' => $confidence];
    }

    protected function calculateTradeReturn($snapshot, $signal, $strategy)
    {
        $entryPrice = $snapshot->price_now;
        $exitPrice = $snapshot->price_future;
        $actualReturn = (($exitPrice - $entryPrice) / $entryPrice) * 100;

        // Adjust based on strategy and signal
        if ($signal['action'] === 'BUY') {
            return $actualReturn;
        } elseif ($signal['action'] === 'SELL') {
            return -$actualReturn; // Opposite of BUY
        } else { // HOLD
            return 0; // No trade
        }
    }

    protected function calculateProfitFactor($trades): float
    {
        $wins = collect($trades)->where('win', true);
        $losses = collect($trades)->where('win', false);

        if ($losses->isEmpty()) {
            return $wins->sum('trade_return') > 0 ? INF : 0;
        }

        return abs($wins->sum('trade_return') / $losses->sum('trade_return'));
    }

    protected function calculateSharpeRatio($trades): float
    {
        $returns = collect($trades)->pluck('trade_return');
        if ($returns->count() < 2) return 0;

        $avgReturn = $returns->avg();
        $stdDev = $returns->stdDev();

        return $stdDev > 0 ? ($avgReturn / $stdDev) * sqrt(252) : 0; // Annualized Sharpe
    }

    protected function displayOverview($results, $symbol, $start, $end): void
    {
        $this->info("\n📈 BACKTEST PERFORMANCE OVERVIEW");

        $overviewData = collect($results)->map(function ($result) {
            return [
                'Strategy' => strtoupper($result['strategy']),
                'Total Trades' => $result['total_trades'],
                'Win Rate' => number_format($result['win_rate'], 1) . '%',
                'Total Return' => number_format($result['total_return'], 2) . '%',
                'Avg Return' => number_format($result['avg_return'], 2) . '%',
                'Max Drawdown' => number_format($result['max_drawdown'], 2) . '%',
                'Sharpe Ratio' => number_format($result['sharpe_ratio'], 2),
                'Profit Factor' => number_format($result['profit_factor'], 2),
            ];
        });

        $this->table([
            'Strategy', 'Trades', 'Win Rate', 'Total Return', 'Avg Return', 'Max DD', 'Sharpe', 'Profit Factor'
        ], $overviewData->toArray());
    }

    protected function displaySummary($results, $symbol): void
    {
        $this->info("\n📋 PERFORMANCE SUMMARY");

        foreach ($results as $result) {
            $this->line("🎯 {$result['strategy']}:");
            $this->line("   Trades: {$result['total_trades']} | Win Rate: " . number_format($result['win_rate'], 1) . "%");
            $this->line("   Total Return: " . number_format($result['total_return'], 2) . "% | Sharpe: " . number_format($result['sharpe_ratio'], 2));
            $this->line("   Profit Factor: " . number_format($result['profit_factor'], 2) . " | Max DD: " . number_format($result['max_drawdown'], 2) . "%");
            $this->line("");
        }
    }

    protected function displayDetailedAnalysis($results, $snapshots, $symbol, $start, $end): void
    {
        foreach ($results as $result) {
            $this->info("\n🔍 DETAILED ANALYSIS: " . strtoupper($result['strategy']));
            $this->line("─" . str_repeat("─", 50));

            $this->info("Performance Metrics:");
            $this->table(['Metric', 'Value'], [
                ['Total Trades', $result['total_trades']],
                ['Winning Trades', $result['wins']],
                ['Losing Trades', $result['losses']],
                ['Win Rate', number_format($result['win_rate'], 1) . '%'],
                ['Total Return', number_format($result['total_return'], 2) . '%'],
                ['Average Return per Trade', number_format($result['avg_return'], 2) . '%'],
                ['Maximum Drawdown', number_format($result['max_drawdown'], 2) . '%'],
                ['Sharpe Ratio', number_format($result['sharpe_ratio'], 2)],
                ['Profit Factor', number_format($result['profit_factor'], 2)],
            ]);

            // Show top 10 trades
            $topTrades = collect($result['trades'])
                ->sortByDesc('trade_return')
                ->take(10);

            if ($topTrades->isNotEmpty()) {
                $this->info("\nTop 10 Trades:");
                $tradeTable = $topTrades->map(function ($trade) {
                    return [
                        $trade['timestamp'],
                        $trade['signal_action'],
                        number_format($trade['entry_price'], 2),
                        number_format($trade['exit_price'], 2),
                        number_format($trade['trade_return'], 2) . '%',
                        $trade['win'] ? '✅' : '❌',
                    ];
                });
                $this->table(['Time', 'Action', 'Entry', 'Exit', 'Return', 'Result'], $tradeTable->toArray());
            }
        }
    }

    protected function compareStrategies($results): void
    {
        $this->info("\n⚖️ STRATEGY COMPARISON");

        $comparisonData = [];
        foreach ($results as $result) {
            $comparisonData[] = [
                'Strategy' => strtoupper($result['strategy']),
                'Win Rate' => number_format($result['win_rate'], 1) . '%',
                'Total Return' => number_format($result['total_return'], 2) . '%',
                'Sharpe' => number_format($result['sharpe_ratio'], 2),
                'Profit Factor' => number_format($result['profit_factor'], 2),
                'Rank' => 0, // Will be calculated below
            ];
        }

        // Calculate rankings
        usort($comparisonData, function ($a, $b) {
            $scoreA = ($a['Total Return'] * 0.4) + ($a['Sharpe'] * 30) + ($a['Profit Factor'] * 20);
            $scoreB = ($b['Total Return'] * 0.4) + ($b['Sharpe'] * 30) + ($b['Profit Factor'] * 20);
            return $scoreB <=> $scoreA;
        });

        foreach ($comparisonData as $index => &$data) {
            $data['Rank'] = $index + 1;
        }

        $this->table([
            'Rank', 'Strategy', 'Win Rate', 'Total Return', 'Sharpe', 'Profit Factor'
        ], $comparisonData);
    }

    protected function exportResults($results, $symbol, $start, $end, $filename): void
    {
        $exportData = [
            'backtest_overview' => [
                'symbol' => $symbol,
                'period' => [
                    'start' => $start->toIso8601String(),
                    'end' => $end->toIso8601String(),
                ],
                'strategies' => $results,
                'summary' => [
                    'best_strategy' => collect($results)->sortByDesc('sharpe_ratio')->first()['strategy'],
                    'best_total_return' => collect($results)->sortByDesc('total_return')->first()['strategy'],
                    'best_win_rate' => collect($results)->sortByDesc('win_rate')->first()['strategy'],
                ],
                'exported_at' => now()->toIso8601String(),
            ],
        ];

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($extension === 'csv') {
            $this->exportCsv($results, $filename);
        } elseif ($extension === 'html') {
            $this->exportHtml($results, $symbol, $start, $end, $filename);
        } else {
            file_put_contents($filename, json_encode($exportData, JSON_PRETTY_PRINT));
        }

        $this->info("✅ Backtest overview exported to: {$filename}");
    }

    protected function exportCsv($results, $filename): void
    {
        $csv = "Strategy,Total Trades,Wins,Losses,Win Rate,Total Return,Avg Return,Max Drawdown,Sharpe Ratio,Profit Factor\n";

        foreach ($results as $result) {
            $csv .= implode(',', [
                $result['strategy'],
                $result['total_trades'],
                $result['wins'],
                $result['losses'],
                number_format($result['win_rate'], 2),
                number_format($result['total_return'], 2),
                number_format($result['avg_return'], 2),
                number_format($result['max_drawdown'], 2),
                number_format($result['sharpe_ratio'], 2),
                number_format($result['profit_factor'], 2)
            ]) . "\n";
        }

        file_put_contents($filename, $csv);
    }

    protected function exportHtml($results, $symbol, $start, $end, $filename): void
    {
        $html = "<html><head><title>Backtest Overview - {$symbol}</title>";
        $html .= "<style>body{font-family:Arial,sans-serif;margin:20px;}table{border-collapse:collapse;width:100%;margin-bottom:20px;}";
        $html .= "th,td{border:1px solid #ddd;padding:8px;text-align:left;}";
        $html .= "th{background-color:#f2f2f2;}</style></head><body>";

        $html .= "<h1>Backtest Overview Report - {$symbol}</h1>";
        $html .= "<p>Period: {$start->toIso8601String()} to {$end->toIso8601String()}</p>";

        foreach ($results as $result) {
            $html .= "<h2>" . strtoupper($result['strategy']) . " Strategy</h2>";
            $html .= "<table><tr><th>Metric</th><th>Value</th></tr>";
            $html .= "<tr><td>Total Trades</td><td>" . $result['total_trades'] . "</td></tr>";
            $html .= "<tr><td>Win Rate</td><td>" . number_format($result['win_rate'], 1) . "%</td></tr>";
            $html .= "<tr><td>Total Return</td><td>" . number_format($result['total_return'], 2) . "%</td></tr>";
            $html .= "<tr><td>Sharpe Ratio</td><td>" . number_format($result['sharpe_ratio'], 2) . "</td></tr>";
            $html .= "<tr><td>Profit Factor</td><td>" . number_format($result['profit_factor'], 2) . "</td></tr>";
            $html .= "<tr><td>Max Drawdown</td><td>" . number_format($result['max_drawdown'], 2) . "%</td></tr>";
            $html .= "</table>";
        }

        $html .= "</body></html>";
        file_put_contents($filename, $html);
    }
}