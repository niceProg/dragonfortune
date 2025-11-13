<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\CoinglassClient;

/**
 * Spot Microstructure Controller
 * Coinglass API Integration - All Spot Endpoints
 */
class SpotMicrostructureController extends Controller
{
    private CoinglassClient $client;
    private int $cacheTtl = 60; // 60 seconds

    public function __construct(CoinglassClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get supported coins list
     * No parameters needed
     */
    public function getSupportedCoins(Request $request)
    {
        $cacheKey = "spot-micro:supported-coins";

        $data = Cache::remember($cacheKey, $this->cacheTtl * 10, function () {
            Log::info('Fetching Supported Coins');

            try {
                $result = $this->client->get('/spot/supported-coins', []);

                if (!is_array($result) || ($result['code'] ?? null) !== '0') {
                    throw new \Exception('API error: ' . ($result['msg'] ?? 'Unknown error'));
                }

                $coins = $result['data'] ?? [];
                
                Log::info('✅ Supported Coins fetched', ['count' => count($coins)]);

                return [
                    'success' => true,
                    'data' => $coins,
                    'count' => count($coins),
                ];

            } catch (\Exception $e) {
                Log::error('❌ Supported Coins Error', ['error' => $e->getMessage()]);
                
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'data' => [],
                ];
            }
        });

        return response()->json($data);
    }

    /**
     * Get supported exchange pairs
     * No parameters needed
     */
    public function getSupportedExchangePairs(Request $request)
    {
        $cacheKey = "spot-micro:supported-exchange-pairs";

        $data = Cache::remember($cacheKey, $this->cacheTtl * 10, function () {
            Log::info('Fetching Supported Exchange Pairs');

            try {
                $result = $this->client->get('/spot/supported-exchange-pairs', []);

                if (!is_array($result) || ($result['code'] ?? null) !== '0') {
                    throw new \Exception('API error: ' . ($result['msg'] ?? 'Unknown error'));
                }

                $pairs = $result['data'] ?? [];
                
                Log::info('✅ Supported Exchange Pairs fetched', ['exchanges' => count($pairs)]);

                return [
                    'success' => true,
                    'data' => $pairs,
                ];

            } catch (\Exception $e) {
                Log::error('❌ Supported Exchange Pairs Error', ['error' => $e->getMessage()]);
                
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'data' => [],
                ];
            }
        });

        return response()->json($data);
    }

    /**
     * Get coins markets data
     * Params: per_page (default: 100), page (default: 1)
     * Supports: 5m, 15m, 30m, 1h, 4h, 12h, 24h, 1w
     */
    public function getCoinsMarkets(Request $request)
    {
        $perPage = $request->query('per_page', 100);
        $page = $request->query('page', 1);
        
        $cacheKey = "spot-micro:coins-markets:{$perPage}:{$page}";

        $data = Cache::remember($cacheKey, $this->cacheTtl, function () use ($perPage, $page) {
            Log::info('Fetching Coins Markets', ['per_page' => $perPage, 'page' => $page]);

            try {
                $result = $this->client->get('/spot/coins-markets', [
                    'per_page' => $perPage,
                    'page' => $page,
                ]);

                if (!is_array($result) || ($result['code'] ?? null) !== '0') {
                    throw new \Exception('API error: ' . ($result['msg'] ?? 'Unknown error'));
                }

                $coins = $result['data'] ?? [];
                
                Log::info('✅ Coins Markets fetched', ['count' => count($coins)]);

                return [
                    'success' => true,
                    'data' => $coins,
                    'count' => count($coins),
                    'per_page' => $perPage,
                    'page' => $page,
                ];

            } catch (\Exception $e) {
                Log::error('❌ Coins Markets Error', ['error' => $e->getMessage()]);
                
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'data' => [],
                ];
            }
        });

        return response()->json($data);
    }

    /**
     * Get pairs markets data
     * Required param: symbol (e.g., BTC)
     * Supports: 1h, 4h, 12h, 24h, 1w
     */
    public function getPairsMarkets(Request $request)
    {
        $symbol = $request->query('symbol', 'BTC');
        
        $cacheKey = "spot-micro:pairs-markets:{$symbol}";

        $data = Cache::remember($cacheKey, $this->cacheTtl, function () use ($symbol) {
            Log::info('Fetching Pairs Markets', ['symbol' => $symbol]);

            try {
                $result = $this->client->get('/spot/pairs-markets', [
                    'symbol' => $symbol,
                ]);

                if (!is_array($result) || ($result['code'] ?? null) !== '0') {
                    throw new \Exception('API error: ' . ($result['msg'] ?? 'Unknown error'));
                }

                $pairs = $result['data'] ?? [];
                
                Log::info('✅ Pairs Markets fetched', ['count' => count($pairs)]);

                return [
                    'success' => true,
                    'data' => $pairs,
                    'count' => count($pairs),
                    'symbol' => $symbol,
                ];

            } catch (\Exception $e) {
                Log::error('❌ Pairs Markets Error', ['error' => $e->getMessage()]);
                
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'data' => [],
                ];
            }
        });

        return response()->json($data);
    }

    /**
     * Get price history (OHLCV)
     * Required params: exchange, symbol, interval
     * Optional: limit, start_time, end_time
     * Intervals: 1m, 3m, 5m, 15m, 30m, 1h, 4h, 6h, 8h, 12h, 1d, 1w
     */
    public function getPriceHistory(Request $request)
    {
        $exchange = $request->query('exchange', 'Binance');
        $symbol = $request->query('symbol', 'BTCUSDT');
        $interval = $request->query('interval', '1h');
        $limit = $request->query('limit', 100);
        $startTime = $request->query('start_time');
        $endTime = $request->query('end_time');

        $cacheKey = "spot-micro:price-history:{$exchange}:{$symbol}:{$interval}:{$limit}";

        $data = Cache::remember($cacheKey, $this->cacheTtl, function () use ($exchange, $symbol, $interval, $limit, $startTime, $endTime) {
            Log::info('Fetching Price History', [
                'exchange' => $exchange,
                'symbol' => $symbol,
                'interval' => $interval,
                'limit' => $limit
            ]);

            try {
                $params = [
                    'exchange' => $exchange,
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'limit' => $limit,
                ];

                if ($startTime) {
                    $params['start_time'] = $startTime;
                }
                if ($endTime) {
                    $params['end_time'] = $endTime;
                }

                $result = $this->client->get('/spot/price/history', $params);

                if (!is_array($result) || ($result['code'] ?? null) !== '0') {
                    throw new \Exception('API error: ' . ($result['msg'] ?? 'Unknown error'));
                }

                $history = $result['data'] ?? [];
                
                Log::info('✅ Price History fetched', ['count' => count($history)]);

                return [
                    'success' => true,
                    'data' => $history,
                    'count' => count($history),
                    'exchange' => $exchange,
                    'symbol' => $symbol,
                    'interval' => $interval,
                ];

            } catch (\Exception $e) {
                Log::error('❌ Price History Error', ['error' => $e->getMessage()]);
                
                return [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'data' => [],
                ];
            }
        });

        return response()->json($data);
    }

    /**
     * Get orderbook ask-bids history
     * Required: exchange, symbol, interval
     * Optional: limit, start_time, end_time, range
     */
    public function getOrderbookAskBidsHistory(Request $request)
    {
        $exchange = $request->query('exchange', 'Binance');
        $symbol = $request->query('symbol', 'BTCUSDT');
        $interval = $request->query('interval', '1d');
        $limit = $request->query('limit', 100);
        $range = $request->query('range', '1');
        $startTime = $request->query('start_time');
        $endTime = $request->query('end_time');

        $cacheKey = "spot-micro:orderbook-ask-bids:{$exchange}:{$symbol}:{$interval}:{$range}";

        $data = Cache::remember($cacheKey, $this->cacheTtl, function () use ($exchange, $symbol, $interval, $limit, $range, $startTime, $endTime) {
            Log::info('Fetching Orderbook Ask-Bids History');

            try {
                $params = [
                    'exchange' => $exchange,
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'limit' => $limit,
                    'range' => $range,
                ];

                if ($startTime) $params['start_time'] = $startTime;
                if ($endTime) $params['end_time'] = $endTime;

                $result = $this->client->get('/spot/orderbook/ask-bids-history', $params);

                if (!is_array($result) || ($result['code'] ?? null) !== '0') {
                    throw new \Exception('API error: ' . ($result['msg'] ?? 'Unknown error'));
                }

                return [
                    'success' => true,
                    'data' => $result['data'] ?? [],
                    'count' => count($result['data'] ?? []),
                ];

            } catch (\Exception $e) {
                Log::error('❌ Orderbook Ask-Bids Error', ['error' => $e->getMessage()]);
                return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
            }
        });

        return response()->json($data);
    }

    /**
     * Get aggregated orderbook ask-bids history
     * Required: exchange_list, symbol, interval
     * Optional: limit, start_time, end_time, range
     */
    public function getAggregatedOrderbookHistory(Request $request)
    {
        $exchangeList = $request->query('exchange_list', 'Binance');
        $symbol = $request->query('symbol', 'BTC');
        $interval = $request->query('interval', '1h');
        $limit = $request->query('limit', 100);
        $range = $request->query('range', '1');
        $startTime = $request->query('start_time');
        $endTime = $request->query('end_time');

        $cacheKey = "spot-micro:orderbook-aggregated:{$exchangeList}:{$symbol}:{$interval}:{$range}";

        $data = Cache::remember($cacheKey, $this->cacheTtl, function () use ($exchangeList, $symbol, $interval, $limit, $range, $startTime, $endTime) {
            Log::info('Fetching Aggregated Orderbook History');

            try {
                $params = [
                    'exchange_list' => $exchangeList,
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'limit' => $limit,
                    'range' => $range,
                ];

                if ($startTime) $params['start_time'] = $startTime;
                if ($endTime) $params['end_time'] = $endTime;

                $result = $this->client->get('/spot/orderbook/aggregated-ask-bids-history', $params);

                if (!is_array($result) || ($result['code'] ?? null) !== '0') {
                    throw new \Exception('API error: ' . ($result['msg'] ?? 'Unknown error'));
                }

                return [
                    'success' => true,
                    'data' => $result['data'] ?? [],
                    'count' => count($result['data'] ?? []),
                ];

            } catch (\Exception $e) {
                Log::error('❌ Aggregated Orderbook Error', ['error' => $e->getMessage()]);
                return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
            }
        });

        return response()->json($data);
    }

    /**
     * Get orderbook history
     * Required: exchange, symbol, interval
     * Optional: limit, start_time, end_time
     */
    public function getOrderbookHistory(Request $request)
    {
        $exchange = $request->query('exchange', 'Binance');
        $symbol = $request->query('symbol', 'BTCUSDT');
        $interval = $request->query('interval', '1h');
        $limit = $request->query('limit', 100);
        $startTime = $request->query('start_time');
        $endTime = $request->query('end_time');

        $cacheKey = "spot-micro:orderbook-history:{$exchange}:{$symbol}:{$interval}";

        $data = Cache::remember($cacheKey, $this->cacheTtl, function () use ($exchange, $symbol, $interval, $limit, $startTime, $endTime) {
            Log::info('Fetching Orderbook History');

            try {
                $params = [
                    'exchange' => $exchange,
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'limit' => $limit,
                ];

                if ($startTime) $params['start_time'] = $startTime;
                if ($endTime) $params['end_time'] = $endTime;

                $result = $this->client->get('/spot/orderbook/history', $params);

                if (!is_array($result) || ($result['code'] ?? null) !== '0') {
                    throw new \Exception('API error: ' . ($result['msg'] ?? 'Unknown error'));
                }

                return [
                    'success' => true,
                    'data' => $result['data'] ?? [],
                    'count' => count($result['data'] ?? []),
                ];

            } catch (\Exception $e) {
                Log::error('❌ Orderbook History Error', ['error' => $e->getMessage()]);
                return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
            }
        });

        return response()->json($data);
    }

    /**
     * Get large limit orders (current)
     * Required: exchange, symbol
     */
    public function getLargeLimitOrder(Request $request)
    {
        $exchange = $request->query('exchange', 'Binance');
        $symbol = $request->query('symbol', 'BTCUSDT');

        $cacheKey = "spot-micro:large-limit-order:{$exchange}:{$symbol}";

        $data = Cache::remember($cacheKey, 30, function () use ($exchange, $symbol) {
            Log::info('Fetching Large Limit Orders');

            try {
                $result = $this->client->get('/spot/orderbook/large-limit-order', [
                    'exchange' => $exchange,
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
                Log::error('❌ Large Limit Order Error', ['error' => $e->getMessage()]);
                return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
            }
        });

        return response()->json($data);
    }

    /**
     * Get large limit order history
     * Required: exchange, symbol, start_time, end_time, state
     */
    public function getLargeLimitOrderHistory(Request $request)
    {
        $exchange = $request->query('exchange', 'Binance');
        $symbol = $request->query('symbol', 'BTCUSDT');
        $startTime = $request->query('start_time');
        $endTime = $request->query('end_time');
        $state = $request->query('state', '1');

        if (!$startTime || !$endTime) {
            return response()->json([
                'success' => false,
                'error' => 'start_time and end_time are required',
                'data' => []
            ]);
        }

        $cacheKey = "spot-micro:large-limit-order-history:{$exchange}:{$symbol}:{$state}:{$startTime}:{$endTime}";

        $data = Cache::remember($cacheKey, $this->cacheTtl, function () use ($exchange, $symbol, $startTime, $endTime, $state) {
            Log::info('Fetching Large Limit Order History');

            try {
                $result = $this->client->get('/spot/orderbook/large-limit-order-history', [
                    'exchange' => $exchange,
                    'symbol' => $symbol,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'state' => $state,
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
                Log::error('❌ Large Limit Order History Error', ['error' => $e->getMessage()]);
                return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
            }
        });

        return response()->json($data);
    }

    /**
     * Get taker buy/sell volume history
     * Required: exchange, symbol, interval
     * Optional: limit, start_time, end_time
     */
    public function getTakerBuySellVolumeHistory(Request $request)
    {
        $exchange = $request->query('exchange', 'Binance');
        $symbol = $request->query('symbol', 'BTCUSDT');
        $interval = $request->query('interval', '1h');
        $limit = $request->query('limit', 100);
        $startTime = $request->query('start_time');
        $endTime = $request->query('end_time');

        $cacheKey = "spot-micro:taker-volume:{$exchange}:{$symbol}:{$interval}";

        $data = Cache::remember($cacheKey, $this->cacheTtl, function () use ($exchange, $symbol, $interval, $limit, $startTime, $endTime) {
            Log::info('Fetching Taker Buy/Sell Volume History');

            try {
                $params = [
                    'exchange' => $exchange,
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'limit' => $limit,
                ];

                if ($startTime) $params['start_time'] = $startTime;
                if ($endTime) $params['end_time'] = $endTime;

                $result = $this->client->get('/spot/taker-buy-sell-volume/history', $params);

                if (!is_array($result) || ($result['code'] ?? null) !== '0') {
                    throw new \Exception('API error: ' . ($result['msg'] ?? 'Unknown error'));
                }

                return [
                    'success' => true,
                    'data' => $result['data'] ?? [],
                    'count' => count($result['data'] ?? []),
                ];

            } catch (\Exception $e) {
                Log::error('❌ Taker Volume Error', ['error' => $e->getMessage()]);
                return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
            }
        });

        return response()->json($data);
    }

    /**
     * Get aggregated taker buy/sell volume history
     * Required: exchange_list, symbol, interval
     * Optional: limit, start_time, end_time, unit
     */
    public function getAggregatedTakerVolumeHistory(Request $request)
    {
        $exchangeList = $request->query('exchange_list', 'Binance');
        $symbol = $request->query('symbol', 'BTC');
        $interval = $request->query('interval', '1h');
        $limit = $request->query('limit', 100);
        $unit = $request->query('unit', 'usd');
        $startTime = $request->query('start_time');
        $endTime = $request->query('end_time');

        $cacheKey = "spot-micro:aggregated-taker:{$exchangeList}:{$symbol}:{$interval}:{$unit}";

        $data = Cache::remember($cacheKey, $this->cacheTtl, function () use ($exchangeList, $symbol, $interval, $limit, $unit, $startTime, $endTime) {
            Log::info('Fetching Aggregated Taker Volume History');

            try {
                $params = [
                    'exchange_list' => $exchangeList,
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'limit' => $limit,
                    'unit' => $unit,
                ];

                if ($startTime) $params['start_time'] = $startTime;
                if ($endTime) $params['end_time'] = $endTime;

                $result = $this->client->get('/spot/aggregated-taker-buy-sell-volume/history', $params);

                if (!is_array($result) || ($result['code'] ?? null) !== '0') {
                    throw new \Exception('API error: ' . ($result['msg'] ?? 'Unknown error'));
                }

                return [
                    'success' => true,
                    'data' => $result['data'] ?? [],
                    'count' => count($result['data'] ?? []),
                ];

            } catch (\Exception $e) {
                Log::error('❌ Aggregated Taker Volume Error', ['error' => $e->getMessage()]);
                return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
            }
        });

        return response()->json($data);
    }

    /**
     * Get futures volume footprint history
     * Required: exchange, symbol, interval
     * Optional: limit, start_time, end_time
     */
    public function getVolumeFootprintHistory(Request $request)
    {
        $exchange = $request->query('exchange', 'Binance');
        $symbol = $request->query('symbol', 'BTCUSDT');
        $interval = $request->query('interval', '1h');
        $limit = $request->query('limit', 100);
        $startTime = $request->query('start_time');
        $endTime = $request->query('end_time');

        $cacheKey = "spot-micro:footprint:{$exchange}:{$symbol}:{$interval}";

        $data = Cache::remember($cacheKey, $this->cacheTtl, function () use ($exchange, $symbol, $interval, $limit, $startTime, $endTime) {
            Log::info('Fetching Volume Footprint History');

            try {
                $params = [
                    'exchange' => $exchange,
                    'symbol' => $symbol,
                    'interval' => $interval,
                    'limit' => $limit,
                ];

                if ($startTime) $params['start_time'] = $startTime;
                if ($endTime) $params['end_time'] = $endTime;

                $result = $this->client->get('/futures/volume/footprint-history', $params);

                if (!is_array($result) || ($result['code'] ?? null) !== '0') {
                    throw new \Exception('API error: ' . ($result['msg'] ?? 'Unknown error'));
                }

                return [
                    'success' => true,
                    'data' => $result['data'] ?? [],
                    'count' => count($result['data'] ?? []),
                ];

            } catch (\Exception $e) {
                Log::error('❌ Volume Footprint Error', ['error' => $e->getMessage()]);
                return ['success' => false, 'error' => $e->getMessage(), 'data' => []];
            }
        });

        return response()->json($data);
    }
}
