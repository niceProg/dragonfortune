<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CoinglassController extends Controller
{
    private $apiKey = 'f78a531eb0ef4d06ba9559ec16a6b0c2';
    private $baseUrl = 'https://open-api-v4.coinglass.com';

    /**
     * Get Global Account Long/Short Ratio History from Coinglass
     */
    public function getGlobalAccountRatio(Request $request)
    {
        try {
            $exchange = $request->input('exchange', 'Binance');
            $symbol = $request->input('symbol', 'BTCUSDT');
            $interval = $request->input('interval', '1h');
            $limit = $request->input('limit', 1000);
            $startTime = $request->input('start_time');
            $endTime = $request->input('end_time');

            // Build cache key
            $cacheKey = "coinglass_global_account_{$exchange}_{$symbol}_{$interval}_{$startTime}_{$endTime}";

            // Check cache first (5 minutes)
            if (Cache::has($cacheKey)) {
                Log::info('Returning cached Coinglass Global Account Ratio data');
                return response()->json([
                    'success' => true,
                    'data' => Cache::get($cacheKey),
                    'source' => 'cache'
                ]);
            }

            // Build API parameters
            $params = [
                'exchange' => $exchange,
                'symbol' => $symbol,
                'interval' => $interval,
                'limit' => $limit
            ];

            if ($startTime) {
                $params['start_time'] = $startTime;
            }
            if ($endTime) {
                $params['end_time'] = $endTime;
            }

            Log::info('Fetching Global Account Ratio from Coinglass', [
                'url' => $this->baseUrl . '/api/futures/global-long-short-account-ratio/history',
                'params' => $params
            ]);

            // Make API request to Coinglass
            $response = Http::withHeaders([
                'CG-API-KEY' => $this->apiKey,
                'accept' => 'application/json'
            ])->get($this->baseUrl . '/api/futures/global-long-short-account-ratio/history', $params);

            if (!$response->successful()) {
                Log::error('Coinglass Global Account Ratio API failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Coinglass API request failed',
                    'message' => 'HTTP ' . $response->status() . ': ' . $response->body()
                ], $response->status());
            }

            $data = $response->json();

            // Check if Coinglass returned success
            if (!isset($data['code']) || $data['code'] !== '0') {
                Log::error('Coinglass Global Account Ratio API returned error', [
                    'response' => $data
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Coinglass API error',
                    'message' => $data['msg'] ?? 'Unknown error'
                ], 400);
            }

            // Transform data if needed
            $transformedData = $data['data'] ?? [];

            // Cache the result for 5 minutes
            Cache::put($cacheKey, $transformedData, 300);

            Log::info('Successfully fetched Global Account Ratio from Coinglass', [
                'data_points' => count($transformedData)
            ]);

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'source' => 'coinglass',
                'meta' => [
                    'exchange' => $exchange,
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'count' => count($transformedData)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getGlobalAccountRatio', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Top Account Long/Short Ratio History from Coinglass
     */
    public function getTopAccountRatio(Request $request)
    {
        try {
            $exchange = $request->input('exchange', 'Binance');
            $symbol = $request->input('symbol', 'BTCUSDT');
            $interval = $request->input('interval', '1h');
            $limit = $request->input('limit', 1000);
            $startTime = $request->input('start_time');
            $endTime = $request->input('end_time');

            // Build cache key
            $cacheKey = "coinglass_top_account_{$exchange}_{$symbol}_{$interval}_{$startTime}_{$endTime}";

            // Check cache first (5 minutes)
            if (Cache::has($cacheKey)) {
                return response()->json([
                    'success' => true,
                    'data' => Cache::get($cacheKey),
                    'source' => 'cache'
                ]);
            }

            // Build API parameters
            $params = [
                'exchange' => $exchange,
                'symbol' => $symbol,
                'interval' => $interval,
                'limit' => $limit
            ];

            if ($startTime) {
                $params['start_time'] = $startTime;
            }
            if ($endTime) {
                $params['end_time'] = $endTime;
            }

            // Make API request to Coinglass
            $response = Http::withHeaders([
                'CG-API-KEY' => $this->apiKey,
                'accept' => 'application/json'
            ])->get($this->baseUrl . '/api/futures/top-long-short-account-ratio/history', $params);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Coinglass API request failed',
                    'message' => 'HTTP ' . $response->status()
                ], $response->status());
            }

            $data = $response->json();

            if (!isset($data['code']) || $data['code'] !== '0') {
                return response()->json([
                    'success' => false,
                    'error' => 'Coinglass API error',
                    'message' => $data['msg'] ?? 'Unknown error'
                ], 400);
            }

            $transformedData = $data['data'] ?? [];

            // Cache the result for 5 minutes
            Cache::put($cacheKey, $transformedData, 300);

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'source' => 'coinglass'
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getTopAccountRatio', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Top Position Long/Short Ratio History from Coinglass
     */
    public function getTopPositionRatio(Request $request)
    {
        try {
            $exchange = $request->input('exchange', 'Binance');
            $symbol = $request->input('symbol', 'BTCUSDT');
            $interval = $request->input('interval', '1h');
            $limit = $request->input('limit', 1000);
            $startTime = $request->input('start_time');
            $endTime = $request->input('end_time');

            // Build cache key
            $cacheKey = "coinglass_top_position_{$exchange}_{$symbol}_{$interval}_{$startTime}_{$endTime}";

            // Check cache first (5 minutes)
            if (Cache::has($cacheKey)) {
                return response()->json([
                    'success' => true,
                    'data' => Cache::get($cacheKey),
                    'source' => 'cache'
                ]);
            }

            // Build API parameters
            $params = [
                'exchange' => $exchange,
                'symbol' => $symbol,
                'interval' => $interval,
                'limit' => $limit
            ];

            if ($startTime) {
                $params['start_time'] = $startTime;
            }
            if ($endTime) {
                $params['end_time'] = $endTime;
            }

            // Make API request to Coinglass
            $response = Http::withHeaders([
                'CG-API-KEY' => $this->apiKey,
                'accept' => 'application/json'
            ])->get($this->baseUrl . '/api/futures/top-long-short-position-ratio/history', $params);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Coinglass API request failed',
                    'message' => 'HTTP ' . $response->status()
                ], $response->status());
            }

            $data = $response->json();

            if (!isset($data['code']) || $data['code'] !== '0') {
                return response()->json([
                    'success' => false,
                    'error' => 'Coinglass API error',
                    'message' => $data['msg'] ?? 'Unknown error'
                ], 400);
            }

            $transformedData = $data['data'] ?? [];

            // Cache the result for 5 minutes
            Cache::put($cacheKey, $transformedData, 300);

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'source' => 'coinglass'
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getTopPositionRatio', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Net Position History from Coinglass
     */
    public function getNetPosition(Request $request)
    {
        try {
            $exchange = $request->input('exchange', 'Binance');
            $symbol = $request->input('symbol', 'BTCUSDT');
            $interval = $request->input('interval', '1h');
            $limit = $request->input('limit', 1000);
            $startTime = $request->input('start_time');
            $endTime = $request->input('end_time');

            // Build cache key
            $cacheKey = "coinglass_net_position_{$exchange}_{$symbol}_{$interval}_{$startTime}_{$endTime}";

            // Check cache first (5 minutes)
            if (Cache::has($cacheKey)) {
                return response()->json([
                    'success' => true,
                    'data' => Cache::get($cacheKey),
                    'source' => 'cache'
                ]);
            }

            // Build API parameters
            $params = [
                'exchange' => $exchange,
                'symbol' => $symbol,
                'interval' => $interval,
                'limit' => $limit
            ];

            if ($startTime) {
                $params['start_time'] = $startTime;
            }
            if ($endTime) {
                $params['end_time'] = $endTime;
            }

            // Make API request to Coinglass
            $response = Http::withHeaders([
                'CG-API-KEY' => $this->apiKey,
                'accept' => 'application/json'
            ])->get($this->baseUrl . '/api/futures/v2/net-position/history', $params);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Coinglass API request failed',
                    'message' => 'HTTP ' . $response->status()
                ], $response->status());
            }

            $data = $response->json();

            if (!isset($data['code']) || $data['code'] !== '0') {
                return response()->json([
                    'success' => false,
                    'error' => 'Coinglass API error',
                    'message' => $data['msg'] ?? 'Unknown error'
                ], 400);
            }

            $transformedData = $data['data'] ?? [];

            // Cache the result for 5 minutes
            Cache::put($cacheKey, $transformedData, 300);

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'source' => 'coinglass'
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getNetPosition', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Liquidation Coin List from Coinglass
     */
    public function getLiquidationCoinList(Request $request)
    {
        try {
            $exchange = $request->input('exchange', 'Binance');

            // Build cache key
            $cacheKey = "coinglass_liquidation_coinlist_{$exchange}";

            // Check cache first (2 minutes for liquidation data)
            if (Cache::has($cacheKey)) {
                Log::info('Returning cached Coinglass Liquidation Coin List data');
                return response()->json([
                    'success' => true,
                    'data' => Cache::get($cacheKey),
                    'source' => 'cache'
                ]);
            }

            // Build API parameters
            $params = [
                'exchange' => $exchange
            ];

            Log::info('Fetching Liquidation Coin List from Coinglass', [
                'url' => $this->baseUrl . '/api/futures/liquidation/coin-list',
                'params' => $params
            ]);

            // Make API request to Coinglass
            $response = Http::withHeaders([
                'CG-API-KEY' => $this->apiKey,
                'accept' => 'application/json'
            ])->get($this->baseUrl . '/api/futures/liquidation/coin-list', $params);

            if (!$response->successful()) {
                Log::error('Coinglass Liquidation Coin List API failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Coinglass API request failed',
                    'message' => 'HTTP ' . $response->status() . ': ' . $response->body()
                ], $response->status());
            }

            $data = $response->json();

            // Check if Coinglass returned success
            if (!isset($data['code']) || $data['code'] !== '0') {
                Log::error('Coinglass Liquidation Coin List API returned error', [
                    'response' => $data
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Coinglass API error',
                    'message' => $data['msg'] ?? 'Unknown error'
                ], 400);
            }

            // Transform data if needed
            $transformedData = $data['data'] ?? [];

            // Cache the result for 2 minutes (liquidation data changes frequently)
            Cache::put($cacheKey, $transformedData, 120);

            Log::info('Successfully fetched Liquidation Coin List from Coinglass', [
                'data_points' => count($transformedData)
            ]);

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'source' => 'coinglass',
                'meta' => [
                    'exchange' => $exchange,
                    'count' => count($transformedData)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getLiquidationCoinList', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Liquidation Aggregated History from Coinglass
     */
    public function getLiquidationAggregatedHistory(Request $request)
    {
        try {
            $exchangeList = $request->input('exchange_list', 'Binance');
            $symbol = $request->input('symbol', 'BTC');
            $interval = $request->input('interval', '1h');
            $limit = $request->input('limit', 100);
            $startTime = $request->input('start_time');
            $endTime = $request->input('end_time');

            // Build cache key
            $cacheKey = "coinglass_liquidation_aggregated_{$exchangeList}_{$symbol}_{$interval}_{$startTime}_{$endTime}";

            // Check cache first (5 minutes for aggregated data)
            if (Cache::has($cacheKey)) {
                Log::info('Returning cached Coinglass Liquidation Aggregated History data');
                return response()->json([
                    'success' => true,
                    'data' => Cache::get($cacheKey),
                    'source' => 'cache'
                ]);
            }

            // Build API parameters
            $params = [
                'exchange_list' => $exchangeList,
                'symbol' => $symbol,
                'interval' => $interval,
                'limit' => $limit
            ];

            if ($startTime) {
                $params['start_time'] = $startTime;
            }
            if ($endTime) {
                $params['end_time'] = $endTime;
            }

            Log::info('Fetching Liquidation Aggregated History from Coinglass', [
                'url' => $this->baseUrl . '/api/futures/liquidation/aggregated-history',
                'params' => $params
            ]);

            // Make API request to Coinglass
            $response = Http::withHeaders([
                'CG-API-KEY' => $this->apiKey,
                'accept' => 'application/json'
            ])->get($this->baseUrl . '/api/futures/liquidation/aggregated-history', $params);

            if (!$response->successful()) {
                Log::error('Coinglass Liquidation Aggregated History API failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Coinglass API request failed',
                    'message' => 'HTTP ' . $response->status() . ': ' . $response->body()
                ], $response->status());
            }

            $data = $response->json();

            // Check if Coinglass returned success
            if (!isset($data['code']) || $data['code'] !== '0') {
                Log::error('Coinglass Liquidation Aggregated History API returned error', [
                    'response' => $data
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Coinglass API error',
                    'message' => $data['msg'] ?? 'Unknown error'
                ], 400);
            }

            // Transform data if needed
            $transformedData = $data['data'] ?? [];

            // Cache the result for 5 minutes
            Cache::put($cacheKey, $transformedData, 300);

            Log::info('Successfully fetched Liquidation Aggregated History from Coinglass', [
                'data_points' => count($transformedData)
            ]);

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'source' => 'coinglass',
                'meta' => [
                    'exchange_list' => $exchangeList,
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'count' => count($transformedData)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getLiquidationAggregatedHistory', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Liquidation Exchange List from Coinglass
     */
    public function getLiquidationExchangeList(Request $request)
    {
        try {
            $symbol = $request->input('symbol', 'BTC');
            $range = $request->input('range', '1h');

            // Build cache key
            $cacheKey = "coinglass_liquidation_exchange_{$symbol}_{$range}";

            // Check cache first (2 minutes for exchange data)
            if (Cache::has($cacheKey)) {
                Log::info('Returning cached Coinglass Liquidation Exchange List data');
                return response()->json([
                    'success' => true,
                    'data' => Cache::get($cacheKey),
                    'source' => 'cache'
                ]);
            }

            // Build API parameters
            $params = [
                'symbol' => $symbol,
                'range' => $range
            ];

            Log::info('Fetching Liquidation Exchange List from Coinglass', [
                'url' => $this->baseUrl . '/api/futures/liquidation/exchange-list',
                'params' => $params
            ]);

            // Make API request to Coinglass
            $response = Http::withHeaders([
                'CG-API-KEY' => $this->apiKey,
                'accept' => 'application/json'
            ])->get($this->baseUrl . '/api/futures/liquidation/exchange-list', $params);

            if (!$response->successful()) {
                Log::error('Coinglass Liquidation Exchange List API failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Coinglass API request failed',
                    'message' => 'HTTP ' . $response->status() . ': ' . $response->body()
                ], $response->status());
            }

            $data = $response->json();

            // Check if Coinglass returned success
            if (!isset($data['code']) || $data['code'] !== '0') {
                Log::error('Coinglass Liquidation Exchange List API returned error', [
                    'response' => $data
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Coinglass API error',
                    'message' => $data['msg'] ?? 'Unknown error'
                ], 400);
            }

            // Transform data if needed
            $transformedData = $data['data'] ?? [];

            // Cache the result for 2 minutes
            Cache::put($cacheKey, $transformedData, 120);

            Log::info('Successfully fetched Liquidation Exchange List from Coinglass', [
                'data_points' => count($transformedData)
            ]);

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'source' => 'coinglass',
                'meta' => [
                    'symbol' => $symbol,
                    'range' => $range,
                    'count' => count($transformedData)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getLiquidationExchangeList', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Liquidation History from Coinglass
     */
    public function getLiquidationHistory(Request $request)
    {
        try {
            $exchange = $request->input('exchange', 'Binance');
            $symbol = $request->input('symbol', 'BTCUSDT');
            $interval = $request->input('interval', '1h');
            $limit = $request->input('limit', 100);
            $startTime = $request->input('start_time');
            $endTime = $request->input('end_time');

            // Build cache key
            $cacheKey = "coinglass_liquidation_history_{$exchange}_{$symbol}_{$interval}_{$startTime}_{$endTime}";

            // Check cache first (1 minute for pair data)
            if (Cache::has($cacheKey)) {
                Log::info('Returning cached Coinglass Liquidation History data');
                return response()->json([
                    'success' => true,
                    'data' => Cache::get($cacheKey),
                    'source' => 'cache'
                ]);
            }

            // Build API parameters
            $params = [
                'exchange' => $exchange,
                'symbol' => $symbol,
                'interval' => $interval,
                'limit' => $limit
            ];

            if ($startTime) {
                $params['start_time'] = $startTime;
            }
            if ($endTime) {
                $params['end_time'] = $endTime;
            }

            Log::info('Fetching Liquidation History from Coinglass', [
                'url' => $this->baseUrl . '/api/futures/liquidation/history',
                'params' => $params
            ]);

            // Make API request to Coinglass
            $response = Http::withHeaders([
                'CG-API-KEY' => $this->apiKey,
                'accept' => 'application/json'
            ])->get($this->baseUrl . '/api/futures/liquidation/history', $params);

            if (!$response->successful()) {
                Log::error('Coinglass Liquidation History API failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Coinglass API request failed',
                    'message' => 'HTTP ' . $response->status() . ': ' . $response->body()
                ], $response->status());
            }

            $data = $response->json();

            // Check if Coinglass returned success
            if (!isset($data['code']) || $data['code'] !== '0') {
                Log::error('Coinglass Liquidation History API returned error', [
                    'response' => $data
                ]);

                return response()->json([
                    'success' => false,
                    'error' => 'Coinglass API error',
                    'message' => $data['msg'] ?? 'Unknown error'
                ], 400);
            }

            // Transform data if needed
            $transformedData = $data['data'] ?? [];

            // Cache the result for 1 minute
            Cache::put($cacheKey, $transformedData, 60);

            Log::info('Successfully fetched Liquidation History from Coinglass', [
                'data_points' => count($transformedData)
            ]);

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'source' => 'coinglass',
                'meta' => [
                    'exchange' => $exchange,
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'count' => count($transformedData)
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getLiquidationHistory', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Taker Buy/Sell Volume Ratio from Coinglass
     */
    public function getTakerBuySell(Request $request)
    {
        try {
            $symbol = $request->input('symbol', 'BTC');
            $range = $request->input('range', '1h');

            // Build cache key
            $cacheKey = "coinglass_taker_buysell_{$symbol}_{$range}";

            // Check cache first (5 minutes)
            if (Cache::has($cacheKey)) {
                return response()->json([
                    'success' => true,
                    'data' => Cache::get($cacheKey),
                    'source' => 'cache'
                ]);
            }

            // Build API parameters
            $params = [
                'symbol' => $symbol,
                'range' => $range
            ];

            // Make API request to Coinglass
            $response = Http::withHeaders([
                'CG-API-KEY' => $this->apiKey,
                'accept' => 'application/json'
            ])->get($this->baseUrl . '/api/futures/taker-buy-sell-volume/exchange-list', $params);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Coinglass API request failed',
                    'message' => 'HTTP ' . $response->status()
                ], $response->status());
            }

            $data = $response->json();

            if (!isset($data['code']) || $data['code'] !== '0') {
                return response()->json([
                    'success' => false,
                    'error' => 'Coinglass API error',
                    'message' => $data['msg'] ?? 'Unknown error'
                ], 400);
            }

            $transformedData = $data['data'] ?? [];

            // Cache the result for 5 minutes
            Cache::put($cacheKey, $transformedData, 300);

            return response()->json([
                'success' => true,
                'data' => $transformedData,
                'source' => 'coinglass'
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getTakerBuySell', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Liquidation Summary (24h totals) from Coinglass
     * This aggregates data from exchange-list endpoint
     */
    public function getLiquidationSummary(Request $request)
    {
        try {
            $symbol = $request->input('symbol', 'BTC');
            $range = '24h';

            // Build cache key
            $cacheKey = "coinglass_liquidation_summary_{$symbol}_{$range}";

            // Check cache first (1 minute for summary data)
            if (Cache::has($cacheKey)) {
                Log::info('Returning cached Coinglass Liquidation Summary data');
                return response()->json([
                    'success' => true,
                    'data' => Cache::get($cacheKey),
                    'source' => 'cache'
                ]);
            }

            // Build API parameters
            $params = [
                'symbol' => $symbol,
                'range' => $range
            ];

            Log::info('Fetching Liquidation Summary from Coinglass', [
                'url' => $this->baseUrl . '/api/futures/liquidation/exchange-list',
                'params' => $params
            ]);

            // Make API request to Coinglass
            $response = Http::withHeaders([
                'CG-API-KEY' => $this->apiKey,
                'accept' => 'application/json'
            ])->get($this->baseUrl . '/api/futures/liquidation/exchange-list', $params);

            if (!$response->successful()) {
                Log::error('Coinglass Liquidation Summary API failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                // Return fallback demo data
                return $this->getFallbackLiquidationSummary();
            }

            $data = $response->json();

            // Check if Coinglass returned success
            if (!isset($data['code']) || $data['code'] !== '0') {
                Log::error('Coinglass Liquidation Summary API returned error', [
                    'response' => $data
                ]);

                // Return fallback demo data
                return $this->getFallbackLiquidationSummary();
            }

            // Get the "All" exchange data which contains totals
            $exchangeData = $data['data'] ?? [];
            $allExchangeData = collect($exchangeData)->firstWhere('exchange', 'All');

            if (!$allExchangeData) {
                Log::warning('No "All" exchange data found in response');
                return $this->getFallbackLiquidationSummary();
            }

            // Build summary
            $summary = [
                'total' => floatval($allExchangeData['liquidation_usd'] ?? 0),
                'long' => floatval($allExchangeData['longLiquidation_usd'] ?? 0),
                'short' => floatval($allExchangeData['shortLiquidation_usd'] ?? 0),
                'symbol' => $symbol,
                'range' => $range
            ];

            // Cache the result for 1 minute
            Cache::put($cacheKey, $summary, 60);

            Log::info('Successfully fetched Liquidation Summary from Coinglass', [
                'total' => $summary['total'],
                'long' => $summary['long'],
                'short' => $summary['short']
            ]);

            return response()->json([
                'success' => true,
                'data' => $summary,
                'source' => 'coinglass'
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getLiquidationSummary', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return fallback demo data
            return $this->getFallbackLiquidationSummary();
        }
    }

    /**
     * Get fallback liquidation summary data
     */
    private function getFallbackLiquidationSummary()
    {
        $summary = [
            'total' => 45000000,
            'long' => 25000000,
            'short' => 20000000,
            'symbol' => 'BTC',
            'range' => '24h'
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
            'source' => 'fallback'
        ]);
    }
}