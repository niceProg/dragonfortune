<?php

namespace App\Services\Signal;

use App\Models\SignalSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BacktestService
{
    public function __construct(
        protected AiSignalService $aiSignalService
    ) {
    }

    public function run(array $options = []): array
    {
        $symbol = strtoupper($options['symbol'] ?? 'BTC');
        $start = $this->resolveDate($options['start'] ?? null, '-30 days');
        $end = $this->resolveDate($options['end'] ?? null, 'now');

        $snapshots = SignalSnapshot::query()
            ->where('symbol', $symbol)
            ->whereNotNull('price_future')
            ->whereBetween('generated_at', [$start, $end])
            ->orderBy('generated_at')
            ->get();

        if ($snapshots->isEmpty()) {
            return [
                'symbol' => $symbol,
                'start' => $start->toIso8601ZuluString(),
                'end' => $end->toIso8601ZuluString(),
                'total' => 0,
                'metrics' => [],
                'timeline' => [],
            ];
        }

        [$metrics, $aiCache] = $this->calculateMetrics($snapshots);

        return [
            'symbol' => $symbol,
            'start' => $start->toIso8601ZuluString(),
            'end' => $end->toIso8601ZuluString(),
            'total' => $snapshots->count(),
            'metrics' => $metrics,
            'timeline' => $this->buildTimeline($snapshots, $aiCache),
        ];
    }

    protected function calculateMetrics(Collection $snapshots): array
    {
        $buy = $snapshots->filter(fn ($row) => strtoupper($row->signal_rule) === 'BUY');
        $sell = $snapshots->filter(fn ($row) => strtoupper($row->signal_rule) === 'SELL');
        $neutral = $snapshots->filter(fn ($row) => strtoupper($row->signal_rule) === 'NEUTRAL');

        $directionMatches = $snapshots->filter(function ($row) {
            $direction = strtoupper($row->signal_rule);
            if (!in_array($direction, ['BUY', 'SELL'])) {
                return false;
            }
            if ($direction === 'BUY') {
                return $row->label_direction === 'UP';
            }
            return $row->label_direction === 'DOWN';
        });

        $buyReturns = $buy->map(fn ($row) => $row->label_magnitude ?? 0)->all();
        $sellReturns = $sell->map(fn ($row) => -1 * ($row->label_magnitude ?? 0))->all();
        $allReturns = array_merge($buyReturns, $sellReturns);

        $aiCache = [];
        $alignedReturns = [];
        $strongAlignedReturns = [];

        foreach ($snapshots as $snapshot) {
            $direction = strtoupper($snapshot->signal_rule);
            if (!in_array($direction, ['BUY', 'SELL'], true)) {
                continue;
            }

            $ai = $this->aiSignalService->predict(
                $snapshot->features_payload ?? [],
                ['score' => $snapshot->signal_score]
            );

            if ($ai) {
                $key = $this->snapshotCacheKey($snapshot);
                $aiCache[$key] = $ai;

                if (strtoupper($ai['decision'] ?? '') === $direction) {
                    $return = $direction === 'BUY'
                        ? ($snapshot->label_magnitude ?? 0)
                        : -1 * ($snapshot->label_magnitude ?? 0);
                    $alignedReturns[] = $return;

                    if ($this->aiEdge($ai['probability'] ?? null) >= 0.3) {
                        $strongAlignedReturns[] = $return;
                    }
                }
            }
        }

        $metrics = [
            'win_rate' => $this->ratio($directionMatches->count(), max($buy->count() + $sell->count(), 1)),
            'buy_trades' => $buy->count(),
            'sell_trades' => $sell->count(),
            'neutral_trades' => $neutral->count(),
            'avg_return_buy_pct' => $this->average($buyReturns),
            'avg_return_sell_pct' => $this->average($sellReturns),
            'avg_return_all_pct' => $this->average($allReturns),
            'max_drawdown_pct' => $this->maxDrawdown($allReturns),
            'expectancy_pct' => $this->average($allReturns),
            'profit_factor' => $this->profitFactor($allReturns),
            'median_return_pct' => $this->median($allReturns),
            'best_trade_pct' => $this->maxValue($allReturns),
            'worst_trade_pct' => $this->minValue($allReturns),
            'ai_alignment_rate' => $this->ratio(count($alignedReturns), max($buy->count() + $sell->count(), 1)),
            'ai_filtered_trades' => count($strongAlignedReturns),
            'filtered_win_rate' => $this->winRateFromReturns($strongAlignedReturns),
            'filtered_avg_return_pct' => $this->average($strongAlignedReturns),
        ];

        return [$metrics, $aiCache];
    }

    protected function buildTimeline(Collection $snapshots, array $aiCache = []): array
    {
        $equity = 1.0;
        $peak = 1.0;
        $timeline = [];

        foreach ($snapshots as $snapshot) {
            $direction = strtoupper($snapshot->signal_rule);
            if (!in_array($direction, ['BUY', 'SELL'])) {
                continue;
            }
            $retPct = $snapshot->label_magnitude ?? 0;
            if ($direction === 'SELL') {
                $retPct *= -1;
            }
            $equity *= (1 + ($retPct / 100));
            $peak = max($peak, $equity);
            $ai = $aiCache[$this->snapshotCacheKey($snapshot)] ?? null;

            $timeline[] = [
                'generated_at' => $snapshot->generated_at->toIso8601ZuluString(),
                'signal' => $snapshot->signal_rule,
                'return_pct' => round($retPct, 3),
                'cumulative' => round(($equity - 1) * 100, 3),
                'drawdown' => round(($equity - $peak) / $peak * 100, 3),
                'ai_decision' => $ai['decision'] ?? null,
                'ai_probability' => $ai['probability'] ?? null,
            ];
        }

        return $timeline;
    }

    protected function maxDrawdown(array $returns): float
    {
        if (empty($returns)) {
            return 0.0;
        }

        $equity = 1.0;
        $peak = 1.0;
        $maxDd = 0.0;

        foreach ($returns as $ret) {
            $equity *= (1 + ($ret / 100));
            $peak = max($peak, $equity);
            $dd = ($equity - $peak) / $peak * 100;
            $maxDd = min($maxDd, $dd);
        }

        return round($maxDd, 3);
    }

    protected function average(array $values): float
    {
        $values = array_filter($values, fn ($value) => $value !== null);
        if (empty($values)) {
            return 0.0;
        }

        return round(array_sum($values) / count($values), 3);
    }

    protected function median(array $values): float
    {
        $values = array_filter($values, fn ($value) => $value !== null);
        if (empty($values)) {
            return 0.0;
        }

        sort($values);
        $count = count($values);
        $middle = intdiv($count, 2);

        if ($count % 2 === 0) {
            return round(($values[$middle - 1] + $values[$middle]) / 2, 3);
        }

        return round($values[$middle], 3);
    }

    protected function maxValue(array $values): float
    {
        $values = array_filter($values, fn ($value) => $value !== null);
        if (empty($values)) {
            return 0.0;
        }

        return round(max($values), 3);
    }

    protected function minValue(array $values): float
    {
        $values = array_filter($values, fn ($value) => $value !== null);
        if (empty($values)) {
            return 0.0;
        }

        return round(min($values), 3);
    }

    protected function ratio(int $numerator, int $denominator): float
    {
        if ($denominator === 0) {
            return 0.0;
        }

        return round($numerator / $denominator, 3);
    }

    protected function resolveDate(?string $value, string $fallback): Carbon
    {
        if ($value) {
            return Carbon::parse($value, 'UTC');
        }

        return Carbon::parse($fallback, 'UTC');
    }

    protected function profitFactor(array $returns): float
    {
        $gains = array_sum(array_filter($returns, fn ($value) => $value > 0));
        $losses = array_sum(array_map('abs', array_filter($returns, fn ($value) => $value < 0)));

        if ($losses == 0.0) {
            return $gains > 0 ? round($gains / 0.0001, 3) : 0.0;
        }

        return round($gains / $losses, 3);
    }

    protected function winRateFromReturns(array $returns): float
    {
        if (empty($returns)) {
            return 0.0;
        }

        $wins = count(array_filter($returns, fn ($value) => $value > 0));

        return round($wins / count($returns), 3);
    }

    protected function aiEdge(?float $probability): float
    {
        if ($probability === null) {
            return 0.0;
        }

        return abs($probability - 0.5) * 2;
    }

    protected function snapshotCacheKey(SignalSnapshot $snapshot): string
    {
        if ($snapshot->getKey()) {
            return 'id_' . $snapshot->getKey();
        }

        $timestamp = optional($snapshot->generated_at)->valueOf();
        if ($timestamp) {
            return 'ts_' . $timestamp;
        }

        return spl_object_hash($snapshot);
    }
}
