<?php

namespace App\Console\Commands;

use App\Models\SignalSnapshot;
use App\Services\Signal\BacktestService;
use App\Repositories\MarketDataRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RunSignalBacktest extends Command
{
    protected $signature = 'signal:backtest
        {--symbol=BTC : Symbol to evaluate}
        {--start= : ISO start date (YYYY-MM-DD or YYYY-MM-DDTHH:MM:SSZ)}
        {--end= : ISO end date (YYYY-MM-DD or YYYY-MM-DDTHH:MM:SSZ)}
        {--days=30 : Lookback days if start not provided}
        {--interval=1h : Candle interval for analysis}
        {--strategies=rule : Comma-separated strategies (rule,ai,ensemble,benchmark)}
        {--metrics=all : Comma-separated metrics to show (win_rate,profit_factor,max_dd,expectancy,sharpe)}
        {--benchmark : Include benchmark (buy-and-hold) comparison}
        {--output=table : Output format (table,json,csv,html)}
        {--export= : Export detailed results to file}
        {--group-by=week : Group results by period (day,week,month)}
        {--filter-signal= : Filter by signal type (BUY,SELL,NEUTRAL)}
        {--min-confidence=0 : Minimum signal confidence threshold}
        {--detail : Show detailed trade-by-trade analysis}
        {--risk-free-rate=0.02 : Annual risk-free rate for Sharpe ratio calculation}';

    protected $description = 'Advanced backtesting engine with multiple strategies, risk metrics, and detailed analysis';

    public function __construct(
        protected BacktestService $backtestService,
        protected MarketDataRepository $marketData
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $symbol = strtoupper($this->option('symbol') ?? 'BTC');
        $startInput = $this->option('start');
        $endInput = $this->option('end');
        $days = max(1, (int) $this->option('days'));
        $interval = $this->option('interval') ?? '1h';
        $strategiesInput = $this->option('strategies') ?? 'rule';
        $metricsInput = $this->option('metrics') ?? 'all';
        $includeBenchmark = $this->option('benchmark');
        $outputFormat = $this->option('output');
        $exportFile = $this->option('export');
        $groupBy = $this->option('group-by');
        $filterSignal = $this->option('filter-signal');
        $minConfidence = max(0, min(100, (float) $this->option('min-confidence')) / 100);
        $showDetail = $this->option('detail');
        $riskFreeRate = (float) $this->option('risk-free-rate');

        // Parse dates
        $end = $endInput ? Carbon::parse($endInput, 'UTC') : now('UTC');
        $start = $startInput ? Carbon::parse($startInput, 'UTC') : $end->copy()->subDays($days);

        // Parse strategies
        $strategies = $this->parseStrategies($strategiesInput);

        // Parse metrics
        $metricsToShow = $this->parseMetrics($metricsInput);

        $this->info("🚀 Advanced Backtest Analysis for {$symbol}");
        $this->info("📅 Period: {$start->toIso8601String()} to {$end->toIso8601String()}");
        $this->info("📊 Interval: {$interval} | Strategies: " . implode(', ', $strategies));
        $this->info("🎯 Min Confidence: " . ($minConfidence * 100) . "% | Group By: {$groupBy}");

        $results = [];
        $benchmarkResults = null;

        // Run backtest for each strategy
        foreach ($strategies as $strategy) {
            $this->line("\n" . "🔄 Running {$strategy} strategy backtest...");

            $strategyResults = $this->runStrategyBacktest($symbol, $start, $end, $interval, $strategy, $filterSignal, $minConfidence);
            $results[$strategy] = $strategyResults;

            if ($strategyResults['total'] === 0) {
                $this->warn("⚠️  No labeled snapshots found for {$strategy} strategy");
                continue;
            }

            $this->info("✅ {$strategy}: {$strategyResults['total']} trades analyzed");
        }

        // Run benchmark if requested
        if ($includeBenchmark) {
            $this->line("\n🔄 Running buy-and-hold benchmark...");
            $benchmarkResults = $this->runBenchmark($symbol, $start, $end);
            $this->info("✅ Benchmark: " . ($benchmarkResults['total_return'] ?? 0) . "% return");
        }

        // Display results
        $this->displayAdvancedResults($results, $benchmarkResults, $metricsToShow, $outputFormat, $groupBy);

        // Show detailed analysis if requested
        if ($showDetail && !empty($results)) {
            $this->showDetailedAnalysis($results);
        }

        // Export results if requested
        if ($exportFile) {
            $this->exportResults($results, $benchmarkResults, $exportFile, $outputFormat);
        }

        return self::SUCCESS;
    }

    protected function parseStrategies(string $input): array
    {
        $available = ['rule', 'ai', 'ensemble', 'benchmark'];
        $requested = explode(',', $input);
        return array_intersect($available, array_map('trim', $requested));
    }

    protected function parseMetrics(string $input): array
    {
        $available = [
            'win_rate', 'profit_factor', 'max_dd', 'expectancy', 'sharpe',
            'sortino', 'calmar', 'total_return', 'avg_return', 'volatility',
            'trades_count', 'best_trade', 'worst_trade', 'win_streak', 'lose_streak'
        ];

        if ($input === 'all') {
            return $available;
        }

        $requested = explode(',', $input);
        return array_intersect($available, array_map('trim', $requested));
    }

    protected function runStrategyBacktest(string $symbol, Carbon $start, Carbon $end, string $interval, string $strategy, ?string $filterSignal, float $minConfidence): array
    {
        $baseOptions = [
            'symbol' => $symbol,
            'start' => $start->toIso8601String(),
            'end' => $end->toIso8601String(),
            'interval' => $interval,
            'strategy' => $strategy,
            'filter_signal' => $filterSignal,
            'min_confidence' => $minConfidence,
        ];

        switch ($strategy) {
            case 'ai':
                return $this->runAIBacktest($baseOptions);
            case 'ensemble':
                return $this->runEnsembleBacktest($baseOptions);
            case 'benchmark':
                return $this->runBenchmark($symbol, $start, $end);
            default:
                return $this->backtestService->run($baseOptions);
        }
    }

    protected function runAIBacktest(array $options): array
    {
        // Enhanced AI backtest with probability thresholds
        $symbol = $options['symbol'];
        $start = Carbon::parse($options['start'], 'UTC');
        $end = Carbon::parse($options['end'], 'UTC');

        $snapshots = SignalSnapshot::query()
            ->where('symbol', $symbol)
            ->whereNotNull('price_future')
            ->whereBetween('generated_at', [$start, $end])
            ->where('signal_confidence', '>=', $options['min_confidence'])
            ->orderBy('generated_at')
            ->get();

        if ($snapshots->isEmpty()) {
            return $this->emptyResults($options);
        }

        $aiResults = $this->executeAIBacktest($snapshots, $options);
        return $this->calculateAdvancedMetrics($aiResults, $options);
    }

    protected function runEnsembleBacktest(array $options): array
    {
        // Combine rule-based and AI strategies
        $ruleResults = $this->backtestService->run($options);
        $aiResults = $this->runAIBacktest($options);

        // Simple ensemble: take signals where both agree or use weighted average
        $ensembleResults = $this->combineStrategies($ruleResults, $aiResults, $options);
        return $this->calculateAdvancedMetrics($ensembleResults, $options);
    }

    protected function runBenchmark(string $symbol, Carbon $start, Carbon $end): array
    {
        // Buy and hold strategy
        $startPrice = $this->getPriceAt($symbol, $start);
        $endPrice = $this->getPriceAt($symbol, $end);

        if (!$startPrice || !$endPrice) {
            return ['total_return' => 0, 'volatility' => 0, 'sharpe' => 0];
        }

        $totalReturn = (($endPrice - $startPrice) / $startPrice) * 100;
        $volatility = $this->calculateVolatility($symbol, $start, $end);
        $riskFreeRate = 0.02; // 2% annual
        $sharpe = $volatility > 0 ? (($totalReturn - $riskFreeRate) / $volatility) : 0;

        return [
            'total_return' => $totalReturn,
            'volatility' => $volatility,
            'sharpe' => $sharpe,
            'max_drawdown' => $this->calculateMaxDrawdown($symbol, $start, $end),
            'total' => 1
        ];
    }

    protected function displayAdvancedResults(array $results, ?array $benchmark, array $metrics, string $format, string $groupBy): void
    {
        switch ($format) {
            case 'json':
                $this->line(json_encode([
                    'strategies' => $results,
                    'benchmark' => $benchmark
                ], JSON_PRETTY_PRINT));
                break;
            case 'csv':
                $this->displayCsvResults($results, $benchmark, $metrics);
                break;
            case 'html':
                $this->displayHtmlResults($results, $benchmark, $metrics);
                break;
            default:
                $this->displayTableResults($results, $benchmark, $metrics, $groupBy);
                break;
        }
    }

    protected function displayTableResults(array $results, ?array $benchmark, array $metrics, string $groupBy): void
    {
        $this->info("\n📊 BACKTEST RESULTS SUMMARY");

        // Strategy comparison table
        $comparisonData = [];
        foreach ($results as $strategy => $data) {
            if ($data['total'] === 0) continue;

            $row = [$strategy];
            foreach ($metrics as $metric) {
                $value = $this->getMetricValue($data, $metric);
                $row[] = $value;
            }
            $comparisonData[] = $row;
        }

        // Add benchmark if available
        if ($benchmark) {
            $row = ['Benchmark (Buy&Hold)'];
            foreach ($metrics as $metric) {
                $value = $this->getMetricValue($benchmark, $metric);
                $row[] = $value;
            }
            $comparisonData[] = $row;
        }

        $headers = ['Strategy'];
        foreach ($metrics as $metric) {
            $headers[] = $this->formatMetricName($metric);
        }

        if (!empty($comparisonData)) {
            $this->table($headers, $comparisonData);
        }

        // Performance ranking
        $this->showPerformanceRanking($results, $benchmark);
    }

    protected function showPerformanceRanking(array $results, ?array $benchmark): void
    {
        $this->info("\n🏆 PERFORMANCE RANKING");

        $performanceData = [];
        foreach ($results as $strategy => $data) {
            if ($data['total'] === 0) continue;
            $performanceData[$strategy] = $data['total_return'] ?? 0;
        }

        if ($benchmark) {
            $performanceData['Benchmark'] = $benchmark['total_return'] ?? 0;
        }

        arsort($performanceData);

        $rankingData = [];
        foreach ($performanceData as $strategy => $return) {
            $rankingData[] = [
                $strategy,
                number_format($return, 2) . '%',
                $this->getPerformanceGrade($return)
            ];
        }

        $this->table(['Strategy', 'Total Return', 'Grade'], $rankingData);
    }

    protected function showDetailedAnalysis(array $results): void
    {
        $this->info("\n🔍 DETAILED TRADE ANALYSIS");

        foreach ($results as $strategy => $data) {
            if ($data['total'] === 0 || empty($data['timeline'])) continue;

            $this->info("\n{$strategy} Strategy - Recent Trades:");

            $recentTrades = array_slice($data['timeline'], -10);
            $tradeData = [];

            foreach ($recentTrades as $trade) {
                $tradeData[] = [
                    substr($trade['generated_at'], 0, 16),
                    $trade['signal'],
                    number_format($trade['return_pct'], 2) . '%',
                    number_format($trade['cumulative'], 2) . '%',
                    number_format($trade['drawdown'], 2) . '%',
                    $trade['ai_decision'] ?? '--'
                ];
            }

            $this->table(['Time', 'Signal', 'Return', 'Cumulative', 'DD', 'AI'], $tradeData);
        }
    }

    protected function getMetricValue(array $data, string $metric): string
    {
        switch ($metric) {
            case 'win_rate':
                return number_format(($data['win_rate'] ?? 0) * 100, 2) . '%';
            case 'profit_factor':
                return number_format($data['profit_factor'] ?? 0, 2);
            case 'max_dd':
                return number_format($data['max_drawdown_pct'] ?? 0, 2) . '%';
            case 'expectancy':
                return number_format($data['expectancy_pct'] ?? 0, 2) . '%';
            case 'sharpe':
                return number_format($data['sharpe_ratio'] ?? $this->calculateSharpe($data), 2);
            case 'total_return':
                return number_format($data['total_return'] ?? $this->calculateTotalReturn($data), 2) . '%';
            case 'avg_return':
                return number_format($data['avg_return_all_pct'] ?? 0, 2) . '%';
            case 'volatility':
                return number_format($data['volatility'] ?? $this->calculateVolatilityFromTrades($data), 2) . '%';
            case 'trades_count':
                return (string) ($data['total'] ?? 0);
            default:
                return '--';
        }
    }

    protected function formatMetricName(string $metric): string
    {
        return ucwords(str_replace('_', ' ', $metric));
    }

    protected function getPerformanceGrade(float $return): string
    {
        if ($return > 20) return 'A+';
        if ($return > 10) return 'A';
        if ($return > 5) return 'B+';
        if ($return > 0) return 'B';
        if ($return > -5) return 'C';
        return 'D';
    }

    // Helper methods for advanced calculations
    protected function calculateSharpe(array $data): float
    {
        $returns = array_column($data['timeline'] ?? [], 'return_pct');
        if (empty($returns)) return 0;

        $avgReturn = array_sum($returns) / count($returns);
        $stdDev = sqrt(array_sum(array_map(fn($r) => pow($r - $avgReturn, 2), $returns)) / count($returns));

        return $stdDev > 0 ? ($avgReturn / $stdDev) * sqrt(252) : 0; // Annualized
    }

    protected function calculateTotalReturn(array $data): float
    {
        $timeline = $data['timeline'] ?? [];
        return !empty($timeline) ? end($timeline)['cumulative'] ?? 0 : 0;
    }

    protected function calculateVolatilityFromTrades(array $data): float
    {
        $returns = array_column($data['timeline'] ?? [], 'return_pct');
        if (empty($returns)) return 0;

        $avgReturn = array_sum($returns) / count($returns);
        return sqrt(array_sum(array_map(fn($r) => pow($r - $avgReturn, 2), $returns)) / count($returns)) * sqrt(252);
    }

    protected function exportResults(array $results, ?array $benchmark, string $filename, string $format): void
    {
        $exportData = [
            'timestamp' => now()->toIso8601String(),
            'strategies' => $results,
            'benchmark' => $benchmark,
            'metadata' => [
                'symbol' => $this->option('symbol'),
                'period' => $this->option('start') . ' to ' . $this->option('end'),
                'interval' => $this->option('interval')
            ]
        ];

        $content = $format === 'json' ? json_encode($exportData, JSON_PRETTY_PRINT) : $this->convertToCsv($exportData);

        file_put_contents($filename, $content);
        $this->info("📄 Results exported to: {$filename}");
    }

    protected function formatRatio(float $value): string
    {
        return number_format($value * 100, 2) . '%';
    }

    protected function formatPercent(float $value): string
    {
        return number_format($value, 2) . '%';
    }

    // Additional helper methods for the advanced backtest functionality
    protected function emptyResults(array $options): array
    {
        return [
            'symbol' => $options['symbol'],
            'start' => $options['start'],
            'end' => $options['end'],
            'total' => 0,
            'metrics' => [],
            'timeline' => [],
        ];
    }

    protected function executeAIBacktest(\Illuminate\Database\Eloquent\Collection $snapshots, array $options): array
    {
        $results = [];
        $equity = 1.0;
        $peak = 1.0;

        foreach ($snapshots as $snapshot) {
            // AI signal evaluation would go here
            $aiDecision = $this->evaluateAISignal($snapshot);
            $return = $this->calculateReturn($snapshot, $aiDecision);

            $equity *= (1 + ($return / 100));
            $peak = max($peak, $equity);
            $drawdown = ($equity - $peak) / $peak * 100;

            $results[] = [
                'generated_at' => $snapshot->generated_at->toIso8601String(),
                'signal' => $snapshot->signal_rule,
                'ai_decision' => $aiDecision,
                'return_pct' => $return,
                'cumulative' => ($equity - 1) * 100,
                'drawdown' => $drawdown,
                'confidence' => $snapshot->signal_confidence,
            ];
        }

        return $results;
    }

    protected function evaluateAISignal(SignalSnapshot $snapshot): string
    {
        // Simplified AI evaluation - in reality this would use the AiSignalService
        return $snapshot->signal_rule === 'BUY' ? 'LONG' : ($snapshot->signal_rule === 'SELL' ? 'SHORT' : 'FLAT');
    }

    protected function calculateReturn(SignalSnapshot $snapshot, string $aiDecision): float
    {
        $currentPrice = $snapshot->price_now;
        $futurePrice = $snapshot->price_future;

        if (!$currentPrice || !$futurePrice) {
            return 0;
        }

        $actualReturn = (($futurePrice - $currentPrice) / $currentPrice) * 100;

        // Apply AI decision logic
        if ($aiDecision === 'LONG') {
            return $actualReturn;
        } elseif ($aiDecision === 'SHORT') {
            return -$actualReturn;
        } else {
            return 0; // FLAT position
        }
    }

    protected function calculateAdvancedMetrics(array $aiResults, array $options): array
    {
        if (empty($aiResults)) {
            return $this->emptyResults($options);
        }

        $returns = array_column($aiResults, 'return_pct');
        $buyTrades = array_filter($aiResults, fn($r) => $r['ai_decision'] === 'LONG');
        $sellTrades = array_filter($aiResults, fn($r) => $r['ai_decision'] === 'SHORT');

        $wins = array_filter($returns, fn($r) => $r > 0);
        $gains = array_sum(array_filter($returns, fn($r) => $r > 0));
        $losses = abs(array_sum(array_filter($returns, fn($r) => $r < 0)));

        $metrics = [
            'win_rate' => count($wins) / count($returns),
            'buy_trades' => count($buyTrades),
            'sell_trades' => count($sellTrades),
            'neutral_trades' => 0,
            'avg_return_buy_pct' => collect($buyTrades)->avg('return_pct') ?? 0,
            'avg_return_sell_pct' => collect($sellTrades)->avg('return_pct') ?? 0,
            'avg_return_all_pct' => array_sum($returns) / count($returns),
            'expectancy_pct' => array_sum($returns) / count($returns),
            'max_drawdown_pct' => min(array_column($aiResults, 'drawdown')),
            'profit_factor' => $losses > 0 ? $gains / $losses : ($gains > 0 ? 999 : 0),
            'median_return_pct' => $this->median($returns),
            'best_trade_pct' => max($returns),
            'worst_trade_pct' => min($returns),
        ];

        return [
            'symbol' => $options['symbol'],
            'start' => $options['start'],
            'end' => $options['end'],
            'total' => count($aiResults),
            'metrics' => $metrics,
            'timeline' => $aiResults,
        ];
    }

    protected function combineStrategies(array $ruleResults, array $aiResults, array $options): array
    {
        // Simple ensemble implementation
        return $ruleResults; // Placeholder - would implement logic to combine strategies
    }

    protected function getPriceAt(string $symbol, Carbon $timestamp): ?float
    {
        try {
            $pair = "{$symbol}USDT";
            $price = $this->marketData->spotPriceAt($pair, $timestamp->valueOf(), '1h');
            return $price ? (float) $price : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function calculateVolatility(string $symbol, Carbon $start, Carbon $end): float
    {
        // Simplified volatility calculation
        return 0.0; // Placeholder - would implement actual volatility calculation
    }

    protected function calculateMaxDrawdown(string $symbol, Carbon $start, Carbon $end): float
    {
        // Simplified max drawdown calculation
        return 0.0; // Placeholder - would implement actual max drawdown calculation
    }

    protected function median(array $values): float
    {
        sort($values);
        $count = count($values);
        $middle = floor($count / 2);

        if ($count % 2 === 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }

        return $values[$middle];
    }

    protected function displayCsvResults(array $results, ?array $benchmark, array $metrics): void
    {
        $csv = "Strategy," . implode(',', array_map(fn($m) => $this->formatMetricName($m), $metrics)) . "\n";

        foreach ($results as $strategy => $data) {
            if ($data['total'] === 0) continue;
            $row = [$strategy];
            foreach ($metrics as $metric) {
                $row[] = $this->getMetricValue($data, $metric);
            }
            $csv .= implode(',', $row) . "\n";
        }

        $this->line($csv);
    }

    protected function displayHtmlResults(array $results, ?array $benchmark, array $metrics): void
    {
        // Basic HTML report
        $html = "<html><head><title>Backtest Report</title></head><body>";
        $html .= "<h1>Backtest Analysis Report</h1>";

        foreach ($results as $strategy => $data) {
            if ($data['total'] === 0) continue;
            $html .= "<h2>{$strategy} Strategy</h2>";
            $html .= "<table border='1'>";
            foreach ($metrics as $metric) {
                $html .= "<tr><td>{$this->formatMetricName($metric)}</td><td>{$this->getMetricValue($data, $metric)}</td></tr>";
            }
            $html .= "</table>";
        }

        $html .= "</body></html>";
        $this->line($html);
    }

    protected function convertToCsv(array $data): string
    {
        $csv = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $csv .= $this->convertToCsv($value);
            } else {
                $csv .= "{$key}: {$value}\n";
            }
        }
        return $csv;
    }
}
