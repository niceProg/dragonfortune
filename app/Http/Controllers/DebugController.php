<?php

namespace App\Http\Controllers;

use App\Services\Signal\FeatureBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebugController extends Controller
{
    public function __construct(
        protected FeatureBuilder $featureBuilder
    ) {}

    public function debugWhaleData(Request $request): JsonResponse
    {
        $symbol = strtoupper($request->input('symbol', 'BTC'));
        $interval = $request->input('tf', '1h');
        $pair = strtoupper($request->input('pair', "{$symbol}USDT"));

        try {
            // Build all features
            $features = $this->featureBuilder->build($symbol, $pair, $interval);

            // Extract just the whale data
            $whaleData = $features['whales'] ?? null;

            return response()->json([
                'success' => true,
                'symbol' => $symbol,
                'whale_data' => $whaleData,
                'full_features_structure' => array_keys($features),
                'debug_info' => [
                    'feature_builder_class' => get_class($this->featureBuilder),
                    'features_built' => !empty($features),
                    'whale_key_exists' => array_key_exists('whales', $features),
                    'whale_data_type' => $whaleData ? gettype($whaleData) : 'null',
                    'whale_data_count' => is_countable($whaleData) ? count($whaleData) : 'not_countable',
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}