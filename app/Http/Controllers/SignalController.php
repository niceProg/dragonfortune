<?php

namespace App\Http\Controllers;

use App\Models\SignalSnapshot;
use App\Models\SignalAnalytics;
use App\Services\Signal\AiSignalService;
use App\Services\Signal\BacktestService;
use App\Services\Signal\FeatureBuilder;
use App\Services\Signal\SignalEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SignalController extends Controller
{
    public function __construct(
        protected FeatureBuilder $featureBuilder,
        protected SignalEngine $signalEngine,
        protected BacktestService $backtestService,
        protected AiSignalService $aiSignalService
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        $symbol = strtoupper($request->input('symbol', 'BTC'));
        $interval = $request->input('tf', '1h');
        $pair = strtoupper($request->input('pair', "{$symbol}USDT"));

        $features = $this->featureBuilder->build($symbol, $pair, $interval);
        $signal = $this->signalEngine->score($features);
        $ai = $this->aiSignalService->predict($features, $signal);

        return response()->json([
            'success' => true,
            'symbol' => $symbol,
            'pair' => $pair,
            'interval' => $interval,
            'generated_at' => $features['generated_at'] ?? now('UTC')->toIso8601ZuluString(),
            'signal' => $signal,
            'ai' => $ai,
            'features' => $features,
        ]);
    }

    public function backtest(Request $request): JsonResponse
    {
        $symbol = strtoupper($request->input('symbol', 'BTC'));
        $days = (int) $request->input('days', 30);
        $start = $request->input('start');
        $end = $request->input('end');

        $startDate = $start ?: now('UTC')->subDays($days)->toIso8601String();
        $endDate = $end ?: now('UTC')->toIso8601String();

        $results = $this->backtestService->run([
            'symbol' => $symbol,
            'start' => $startDate,
            'end' => $endDate,
        ]);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $symbol = strtoupper($request->input('symbol', 'BTC'));
        $limit = min(200, max(10, (int) $request->input('limit', 50)));

        $rows = SignalSnapshot::where('symbol', $symbol)
            ->orderByDesc('generated_at')
            ->limit($limit)
            ->get();

        $history = $rows->map(function (SignalSnapshot $snapshot) {
            $ai = $this->aiSignalService->predict(
                $snapshot->features_payload ?? [],
                ['score' => $snapshot->signal_score]
            );

            return [
                'generated_at' => optional($snapshot->generated_at)->toIso8601ZuluString(),
                'signal' => $snapshot->signal_rule,
                'score' => $snapshot->signal_score,
                'confidence' => $snapshot->signal_confidence,
                'ai_probability' => $ai['probability'] ?? null,
                'ai_decision' => $ai['decision'] ?? null,
                'ai_confidence' => $ai['confidence'] ?? null,
                'price_now' => $snapshot->price_now,
                'price_future' => $snapshot->price_future,
                'label_direction' => $snapshot->label_direction,
            ];
        });

        return response()->json([
            'success' => true,
            'symbol' => $symbol,
            'history' => $history,
        ]);
    }

    public function getAnalytics(Request $request): JsonResponse
    {
        $symbol = strtoupper($request->input('symbol', 'BTC'));
        $hours = (int) $request->input('hours', 24);

        // Get latest signal history analytics
        $historyAnalytics = SignalAnalytics::getLatest($symbol, 'history', $hours);
        $backtestAnalytics = SignalAnalytics::getLatest($symbol, 'backtest', $hours);

        return response()->json([
            'success' => true,
            'symbol' => $symbol,
            'hours' => $hours,
            'history' => $historyAnalytics ? [
                'data' => $historyAnalytics->data,
                'generated_at' => $historyAnalytics->generated_at->toIso8601String(),
                'metadata' => $historyAnalytics->metadata,
            ] : null,
            'backtest' => $backtestAnalytics ? [
                'data' => $backtestAnalytics->data,
                'generated_at' => $backtestAnalytics->generated_at->toIso8601String(),
                'metadata' => $backtestAnalytics->metadata,
            ] : null,
        ]);
    }

    public function getAnalyticsHistory(Request $request): JsonResponse
    {
        $symbol = strtoupper($request->input('symbol', 'BTC'));
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $historyAnalytics = SignalAnalytics::getHistoryForPeriod($symbol, $startDate, $endDate);
        $backtestAnalytics = SignalAnalytics::getBacktestForPeriod($symbol, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'symbol' => $symbol,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'history_analytics' => $historyAnalytics->map(function ($analytics) {
                return [
                    'id' => $analytics->id,
                    'data' => $analytics->data,
                    'generated_at' => $analytics->generated_at->toIso8601String(),
                    'metadata' => $analytics->metadata,
                ];
            }),
            'backtest_analytics' => $backtestAnalytics->map(function ($analytics) {
                return [
                    'id' => $analytics->id,
                    'data' => $analytics->data,
                    'generated_at' => $analytics->generated_at->toIso8601String(),
                    'metadata' => $analytics->metadata,
                ];
            }),
        ]);
    }
}
