<?php

namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Centralized read-only access layer for all cg_* market data tables.
 * Keeps raw query logic in one place so feature builders can stay focused
 * on transformations and scoring instead of SQL plumbing.
 */
class MarketDataRepository
{
    protected string $fundingTable = 'cg_funding_rate_history';
    protected string $openInterestTable = 'cg_open_interest_aggregated_history';
    protected string $whaleTransfersTable = 'cg_whale_transfer';
    protected string $etfFlowsTable = 'cg_bitcoin_etf_flows_history';
    protected string $fearGreedTable = 'cg_fear_greed_index_data_list';
    protected string $liquidationTable = 'cg_liquidation_aggregated_history';
    protected string $longShortGlobalTable = 'cg_long_short_global_account_ratio_history';
    protected string $longShortTopTable = 'cg_long_short_top_account_ratio_history';
    protected string $spotOrderbookTable = 'cg_spot_orderbook_aggregated';
    protected string $spotPriceTable = 'cg_spot_price_history';
    protected string $spotTakerVolumeTable = 'cg_spot_taker_volume_history';

    /**
     * Funding rate candles per exchange/pair.
     */
    public function latestFundingRates(
        string $pair,
        string $interval = '1h',
        array $exchanges = [],
        int $limit = 200,
        ?int $upTo = null
    ): Collection {
        $query = DB::table($this->fundingTable)
            ->select('exchange', 'pair', 'interval', 'time', 'open', 'high', 'low', 'close')
            ->where('pair', $pair)
            ->where('interval', $interval)
            ->orderByDesc('time')
            ->limit($limit);

        if (!empty($exchanges)) {
            $query->whereIn('exchange', $exchanges);
        }

        if ($upTo) {
            $query->where('time', '<=', $upTo);
        }

        return $query->get();
    }

    /**
     * Aggregated open interest candles (usd / coin units).
     */
    public function latestOpenInterest(
        string $symbol,
        string $interval = '1h',
        string $unit = 'usd',
        int $limit = 200,
        ?int $upTo = null
    ): Collection {
        $query = DB::table($this->openInterestTable)
            ->select('symbol', 'interval', 'unit', 'time', 'open', 'high', 'low', 'close')
            ->where('symbol', $symbol)
            ->where('interval', $interval)
            ->where('unit', $unit)
            ->orderByDesc('time')
            ->limit($limit);

        if ($upTo) {
            $query->where('time', '<=', $upTo);
        }

        return $query->get();
    }

    /**
     * Whale transfers (filter by symbol and optional time window).
     */
    public function latestWhaleTransfers(
        string $assetSymbol,
        ?int $sinceTimestamp = null,
        int $limit = 200,
        ?int $upTo = null
    ): Collection {
        $query = DB::table($this->whaleTransfersTable)
            ->select(
                'transaction_hash',
                'amount_usd',
                'asset_quantity',
                'asset_symbol',
                'from_address',
                'to_address',
                'blockchain_name',
                'block_timestamp',
                'created_at'
            )
            ->where('asset_symbol', strtoupper($assetSymbol))
            ->orderByDesc('block_timestamp')
            ->limit($limit);

        if ($sinceTimestamp) {
            $query->where('block_timestamp', '>=', $sinceTimestamp);
        }

        if ($upTo) {
            $query->where('block_timestamp', '<=', $upTo);
        }

        return $query->get();
    }

    public function latestEtfFlows(int $limit = 120, ?int $upTo = null): Collection
    {
        $query = DB::table($this->etfFlowsTable)
            ->select('timestamp', 'flow_usd', 'created_at')
            ->orderByDesc('timestamp')
            ->limit($limit);

        if ($upTo) {
            $query->where('timestamp', '<=', $upTo);
        }

        return $query->get();
    }

    public function fearGreedHistory(int $limit = 100, ?int $upTo = null): Collection
    {
        $query = DB::table($this->fearGreedTable)
            ->select('index_value', 'sequence_order', 'created_at')
            ->orderByDesc('id')
            ->limit($limit);

        if ($upTo) {
            $query->where('created_at', '<=', Carbon::createFromTimestampMs($upTo)->toDateTimeString());
        }

        $rows = $query->get();

        return $rows->map(function ($row) {
            $timestamp = $row->created_at
                ? Carbon::parse($row->created_at)->valueOf()
                : now()->valueOf();

            return (object) [
                'timestamp' => $timestamp,
                'value' => (int) $row->index_value,
                'value_classification' => $this->classifySentiment((int) $row->index_value),
            ];
        });
    }

    public function latestLiquidations(
        string $symbol,
        string $interval = '1h',
        int $limit = 200,
        ?int $upTo = null
    ): Collection {
        $query = DB::table($this->liquidationTable)
            ->select(
                'symbol',
                'interval',
                'time',
                'aggregated_long_liquidation_usd',
                'aggregated_short_liquidation_usd'
            )
            ->where('symbol', strtoupper($symbol))
            ->where('interval', $interval)
            ->orderByDesc('time')
            ->limit($limit);

        if ($upTo) {
            $query->where('time', '<=', $upTo);
        }

        return $query->get();
    }

    public function latestLongShortRatio(
        string $symbol,
        string $interval = '1h',
        string $type = 'global',
        int $limit = 200,
        ?int $upTo = null
    ): Collection {
        $table = $type === 'top' ? $this->longShortTopTable : $this->longShortGlobalTable;
        $longColumn = $type === 'top' ? 'top_account_long_percent' : 'global_account_long_percent';
        $shortColumn = $type === 'top' ? 'top_account_short_percent' : 'global_account_short_percent';
        $ratioColumn = $type === 'top' ? 'top_account_long_short_ratio' : 'global_account_long_short_ratio';

        $select = [
            'interval',
            'time',
            DB::raw("{$longColumn} / 100 as long_account_ratio"),
            DB::raw("{$shortColumn} / 100 as short_account_ratio"),
            DB::raw("{$ratioColumn} as long_short_ratio"),
        ];

        $pairColumn = $this->tableHasColumn($table, 'pair') ? 'pair' : ($this->tableHasColumn($table, 'symbol') ? 'symbol' : null);
        if ($pairColumn) {
            $select[] = $pairColumn;
        }

        $query = DB::table($table)
            ->select($select)
            ->where('interval', $interval)
            ->orderByDesc('time')
            ->limit($limit);

        $pairSymbol = $this->normalizePairSymbol($symbol);
        if ($pairColumn === 'symbol') {
            $query->where('symbol', strtoupper($symbol));
        } elseif ($pairColumn === 'pair') {
            $query->where('pair', $pairSymbol);
        } else {
            $query->where('pair', $pairSymbol);
        }

        if ($upTo) {
            $query->where('time', '<=', $upTo);
        }

        return $query->get();
    }

    public function latestSpotOrderbook(
        string $symbol,
        string $interval = '1m',
        int $limit = 120,
        ?int $upTo = null
    ): Collection {
        $query = DB::table($this->spotOrderbookTable)
            ->select(
                'symbol',
                'interval',
                'time',
                'aggregated_bids_usd',
                'aggregated_asks_usd',
                'aggregated_bids_quantity',
                'aggregated_asks_quantity'
            )
            ->where('symbol', strtoupper($symbol))
            ->where('interval', $interval)
            ->orderByDesc('time')
            ->limit($limit);

        if ($upTo) {
            $query->where('time', '<=', $upTo);
        }

        return $query->get();
    }

    public function latestSpotPrices(
        string $pair,
        string $interval = '1h',
        int $limit = 500,
        ?int $upTo = null
    ): Collection {
        $query = DB::table($this->spotPriceTable)
            ->select('symbol', 'interval', 'time', 'open', 'high', 'low', 'close', 'volume_usd')
            ->where('symbol', strtoupper($pair))
            ->where('interval', $interval)
            ->orderByDesc('time')
            ->limit($limit);

        if ($upTo) {
            $query->where('time', '<=', $upTo);
        }

        return $query->get();
    }

    public function spotPriceAt(string $pair, int $targetTimestamp, string $interval = '1h'): ?float
    {
        $millis = $targetTimestamp * 1000;

        $row = DB::table($this->spotPriceTable)
            ->select('close')
            ->where('symbol', strtoupper($pair))
            ->where('interval', $interval)
            ->where('time', '>=', $millis)
            ->orderBy('time')
            ->first();

        if (!$row) {
            $row = DB::table($this->spotPriceTable)
                ->select('close')
                ->where('symbol', strtoupper($pair))
                ->where('interval', $interval)
                ->where('time', '<=', $millis)
                ->orderByDesc('time')
                ->first();
        }

        return $row ? (float) $row->close : null;
    }

    public function latestSpotTakerVolume(
        string $symbol,
        string $interval = '1h',
        array $exchanges = [],
        int $limit = 200,
        ?int $upTo = null
    ): Collection {
        $query = DB::table($this->spotTakerVolumeTable)
            ->select(
                'exchange',
                'symbol',
                'interval',
                'time',
                'aggregated_buy_volume_usd',
                'aggregated_sell_volume_usd'
            )
            ->where('symbol', strtoupper($symbol))
            ->where('interval', $interval)
            ->orderByDesc('time')
            ->limit($limit);

        if (!empty($exchanges)) {
            $query->whereIn('exchange', $exchanges);
        }

        if ($upTo) {
            $query->where('time', '<=', $upTo);
        }

        return $query->get();
    }

    /**
     * Helper to convert millisecond timestamps into Carbon when needed.
     */
    public function toCarbon(int $millis): Carbon
    {
        return Carbon::createFromTimestampMs($millis)->setTimezone('UTC');
    }

    protected function classifySentiment(?int $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return match (true) {
            $value >= 75 => 'Extreme Greed',
            $value >= 55 => 'Greed',
            $value <= 25 => 'Extreme Fear',
            $value <= 45 => 'Fear',
            default => 'Neutral',
        };
    }

    protected array $tableColumnCache = [];

    protected function tableHasColumn(string $table, string $column): bool
    {
        if (!array_key_exists($table, $this->tableColumnCache)) {
            $this->tableColumnCache[$table] = [];
        }

        if (!array_key_exists($column, $this->tableColumnCache[$table])) {
            $this->tableColumnCache[$table][$column] = Schema::hasColumn($table, $column);
        }

        return $this->tableColumnCache[$table][$column];
    }

    protected function normalizePairSymbol(string $symbol): string
    {
        $upper = strtoupper($symbol);
        foreach (['USDT', 'USDC', 'BUSD', 'USD'] as $quote) {
            if (str_ends_with($upper, $quote)) {
                return $upper;
            }
        }

        return $upper . 'USDT';
    }
}
