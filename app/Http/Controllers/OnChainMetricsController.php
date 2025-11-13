<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\CoinglassClient;

/**
 * On-Chain Metrics Controller
 * Coinglass API Integration - Exchange Assets & Chain Data
 */
class OnChainMetricsController extends Controller
{
    private CoinglassClient $client;
    private int $cacheTtl = 60; // 60 seconds

    public function __construct(CoinglassClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get exchange assets (wallet addresses and balances)
     * Required: exchange
     * Optional: per_page, page
     */
    public function getExchangeAssets(Request $request)
    {
        $exchange = $request->query('exchange', 'Binance');
        $perPage = $request->query('per_page', 50);
        $page = $request->query('page', 1);

        $cacheKey = "onchain:exchange-assets:{$exchange}:{$perPage}:{$page}";

        $data = Cache::remember($cacheKey, $this->cacheTtl, function () use ($exchange, $perPage, $page) {
            Log::info('Fetching Exchange Assets', ['exchange' => $exchange]);

            try {
                $result = $this->client->get('/exchange/assets', [
                    'exchange' => $exchange,
                    'per_page' => $perPage,
                    'page' => $page,
                ]);

                if (!is_array($result) || ($result['code'] ?? null) !== '0') {
                    throw new \Exception('API error: ' . ($result['msg'] ?? 'Unknown error'));
                }

                return [
                    'success' => true,
                    'data' => $result['data'] ?? [],
                    'count' => count($result['data'] ?? []),
                ];

            } catch (\Exception $e) {
                Log::error('❌ Exchange Assets Error', ['error' => $e->getMessage()]);
                return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
            }
        });

        return response()->json($data);
    }

    /**
     * Get exchange balance list (aggregated by exchange)
     * Required: symbol
     */
    public function getExchangeBalanceList(Request $request)
    {
        $symbol = $request->query('symbol', 'BTC');

        $cacheKey = "onchain:balance-list:{$symbol}";

        $data = Cache::remember($cacheKey, $this->cacheTtl, function () use ($symbol) {
            Log::info('Fetching Exchange Balance List', ['symbol' => $symbol]);

            try {
                $result = $this->client->get('/exchange/balance/list', [
                    'symbol' => $symbol,
                ]);

                if (!is_array($result) || ($result['code'] ?? null) !== '0') {
                    throw new \Exception('API error: ' . ($result['msg'] ?? 'Unknown error'));
                }

                return [
                    'success' => true,
                    'data' => $result['data'] ?? [],
                    'count' => count($result['data'] ?? []),
                ];

            } catch (\Exception $e) {
                Log::error('❌ Balance List Error', ['error' => $e->getMessage()]);
                return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
            }
        });

        return response()->json($data);
    }

    /**
     * Get exchange balance chart (historical balance data)
     * Required: symbol
     */
    public function getExchangeBalanceChart(Request $request)
    {
        $symbol = $request->query('symbol', 'BTC');

        $cacheKey = "onchain:balance-chart:{$symbol}";

        $data = Cache::remember($cacheKey, $this->cacheTtl * 2, function () use ($symbol) {
            Log::info('Fetching Exchange Balance Chart', ['symbol' => $symbol]);

            try {
                $result = $this->client->get('/exchange/balance/chart', [
                    'symbol' => $symbol,
                ]);

                if (!is_array($result) || ($result['code'] ?? null) !== '0') {
                    throw new \Exception('API error: ' . ($result['msg'] ?? 'Unknown error'));
                }

                return [
                    'success' => true,
                    'data' => $result['data'] ?? [],
                ];

            } catch (\Exception $e) {
                Log::error('❌ Balance Chart Error', ['error' => $e->getMessage()]);
                return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
            }
        });

        return response()->json($data);
    }

    /**
     * Get chain transaction list (exchange inflow/outflow)
     * Required: symbol
     * Optional: start_time, min_usd, per_page, page
     */
    public function getChainTransactionList(Request $request)
    {
        $symbol = $request->query('symbol', 'USDT');
        $startTime = $request->query('start_time');
        $minUsd = $request->query('min_usd', 10000);
        $perPage = $request->query('per_page', 50);
        $page = $request->query('page', 1);

        $cacheKey = "onchain:chain-tx:{$symbol}:{$minUsd}:{$perPage}:{$page}";

        $data = Cache::remember($cacheKey, 30, function () use ($symbol, $startTime, $minUsd, $perPage, $page) {
            Log::info('Fetching Chain Transaction List', ['symbol' => $symbol]);

            try {
                $params = [
                    'symbol' => $symbol,
                    'min_usd' => $minUsd,
                    'per_page' => $perPage,
                    'page' => $page,
                ];

                if ($startTime) {
                    $params['start_time'] = $startTime;
                }

                $result = $this->client->get('/exchange/chain/tx/list', $params);

                if (!is_array($result) || ($result['code'] ?? null) !== '0') {
                    throw new \Exception('API error: ' . ($result['msg'] ?? 'Unknown error'));
                }

                return [
                    'success' => true,
                    'data' => $result['data'] ?? [],
                    'count' => count($result['data'] ?? []),
                ];

            } catch (\Exception $e) {
                Log::error('❌ Chain TX Error', ['error' => $e->getMessage()]);
                return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
            }
        });

        return response()->json($data);
    }

    /**
     * Get whale transfers (large on-chain transfers)
     * Required: symbol
     * Optional: start_time, end_time
     */
    public function getWhaleTransfers(Request $request)
    {
        $symbol = $request->query('symbol', 'BTC');
        $startTime = $request->query('start_time');
        $endTime = $request->query('end_time');

        // Default to latest ~6 months window when timestamps are not explicitly provided
        if (!$startTime || !$endTime) {
            $endTime = $endTime ?: (int) (microtime(true) * 1000);
            $startTime = $startTime ?: ($endTime - (180 * 24 * 60 * 60 * 1000));
        }

        $cacheKey = "onchain:whale-transfers:{$symbol}:{$startTime}:{$endTime}";

        $data = Cache::remember($cacheKey, 30, function () use ($symbol, $startTime, $endTime) {
            Log::info('Fetching Whale Transfers', ['symbol' => $symbol]);

            try {
                $params = [
                    'symbol' => $symbol,
                ];

                if ($startTime) $params['start_time'] = $startTime;
                if ($endTime) $params['end_time'] = $endTime;

                $result = $this->client->get('/chain/whale-transfer', $params);

                if (!is_array($result) || ($result['code'] ?? null) !== '0') {
                    throw new \Exception('API error: ' . ($result['msg'] ?? 'Unknown error'));
                }

                $items = $result['data'] ?? [];
                // Sort by time desc (latest first)
                usort($items, function ($a, $b) {
                    $ta = (int) ($a['block_timestamp'] ?? 0);
                    $tb = (int) ($b['block_timestamp'] ?? 0);
                    return $tb <=> $ta;
                });

                return [
                    'success' => true,
                    'data' => $items,
                    'count' => count($items),
                ];

            } catch (\Exception $e) {
                Log::error('❌ Whale Transfers Error', ['error' => $e->getMessage()]);
                return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
            }
        });

        return response()->json($data);
    }
}
