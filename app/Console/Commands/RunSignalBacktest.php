<?php

namespace App\Console\Commands;

use App\Services\Signal\BacktestService;
use Illuminate\Console\Command;

class RunSignalBacktest extends Command
{
    protected $signature = 'signal:backtest 
        {--symbol=BTC : Symbol to evaluate}
        {--start= : ISO start date}
        {--end= : ISO end date}
        {--days=30 : Lookback days if start not provided}';

    protected $description = 'Run rule-based signal backtest over cg_signal_dataset';

    public function __construct(
        protected BacktestService $backtestService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $symbol = strtoupper($this->option('symbol') ?? 'BTC');
        $end = $this->option('end') ?: now('UTC')->toIso8601String();
        $start = $this->option('start') ?: now('UTC')->subDays((int) $this->option('days'))->toIso8601String();

        $results = $this->backtestService->run([
            'symbol' => $symbol,
            'start' => $start,
            'end' => $end,
        ]);

        if ($results['total'] === 0) {
            $this->warn('No labeled snapshots available for the selected window.');
            return self::SUCCESS;
        }

        $this->info("Backtest {$symbol} {$results['start']} → {$results['end']} ({$results['total']} snapshots)");
        $metrics = $results['metrics'];

        $rows = [
            ['Win Rate', $this->formatRatio($metrics['win_rate'])],
            ['Buy Trades', $metrics['buy_trades']],
            ['Sell Trades', $metrics['sell_trades']],
            ['Neutral Trades', $metrics['neutral_trades']],
            ['Avg Return BUY', $this->formatPercent($metrics['avg_return_buy_pct'])],
            ['Avg Return SELL', $this->formatPercent($metrics['avg_return_sell_pct'])],
            ['Avg Return ALL', $this->formatPercent($metrics['avg_return_all_pct'])],
            ['Expectancy', $this->formatPercent($metrics['expectancy_pct'])],
            ['Max Drawdown', $this->formatPercent($metrics['max_drawdown_pct'])],
            ['Profit Factor', number_format($metrics['profit_factor'], 2)],
            ['Median Return', $this->formatPercent($metrics['median_return_pct'])],
            ['Best Trade', $this->formatPercent($metrics['best_trade_pct'])],
            ['Worst Trade', $this->formatPercent($metrics['worst_trade_pct'])],
            ['AI Alignment Rate', $this->formatRatio($metrics['ai_alignment_rate'])],
            ['Filtered Trades (AI+Rule)', $metrics['ai_filtered_trades']],
            ['Filtered Win Rate', $this->formatRatio($metrics['filtered_win_rate'])],
            ['Filtered Avg Return', $this->formatPercent($metrics['filtered_avg_return_pct'])],
        ];

        $this->table(['Metric', 'Value'], $rows);

        return self::SUCCESS;
    }

    protected function formatRatio(float $value): string
    {
        return number_format($value * 100, 2) . '%';
    }

    protected function formatPercent(float $value): string
    {
        return number_format($value, 2) . '%';
    }
}
