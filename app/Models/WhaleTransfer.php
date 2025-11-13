<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhaleTransfer extends Model
{
    use HasFactory;

    protected $table = 'cg_whale_transfer';

    protected $fillable = [
        'transaction_hash',
        'amount_usd',
        'asset_quantity',
        'asset_symbol',
        'from_address',
        'to_address',
        'blockchain_name',
        'block_height',
        'block_timestamp',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'amount_usd' => 'decimal:8',
        'asset_quantity' => 'decimal:18',
        'block_height' => 'integer',
        'block_timestamp' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope a query to only include transfers for a specific asset symbol.
     */
    public function scopeForSymbol($query, string $symbol)
    {
        return $query->where('asset_symbol', strtoupper($symbol));
    }

    /**
     * Scope a query to only include transfers since a specific timestamp.
     */
    public function scopeSince($query, int $timestamp)
    {
        return $query->where('block_timestamp', '>=', $timestamp);
    }

    /**
     * Scope a query to only include transfers up to a specific timestamp.
     */
    public function scopeUpTo($query, int $timestamp)
    {
        return $query->where('block_timestamp', '<=', $timestamp);
    }

    /**
     * Scope a query to order by block timestamp descending.
     */
    public function scopeLatest($query)
    {
        return $query->orderByDesc('block_timestamp');
    }

    /**
     * Check if transfer is an inflow to an exchange (to address matches exchange patterns).
     */
    public function isExchangeInflow(): bool
    {
        $exchangeKeywords = [
            'binance', 'coinbase', 'kraken', 'bitfinex', 'bitstamp',
            'bybit', 'okx', 'okex', 'deribit', 'kucoin', 'mexc',
            'huobi', 'gate', 'gemini'
        ];

        $toAddress = strtolower($this->to_address ?? '');

        foreach ($exchangeKeywords as $keyword) {
            if (str_contains($toAddress, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if transfer is an outflow from an exchange (from address matches exchange patterns).
     */
    public function isExchangeOutflow(): bool
    {
        $exchangeKeywords = [
            'binance', 'coinbase', 'kraken', 'bitfinex', 'bitstamp',
            'bybit', 'okx', 'okex', 'deribit', 'kucoin', 'mexc',
            'huobi', 'gate', 'gemini'
        ];

        $fromAddress = strtolower($this->from_address ?? '');

        foreach ($exchangeKeywords as $keyword) {
            if (str_contains($fromAddress, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get transfer direction (inflow/outflow/unknown).
     */
    public function getDirectionAttribute(): string
    {
        if ($this->isExchangeInflow()) {
            return 'inflow';
        }

        if ($this->isExchangeOutflow()) {
            return 'outflow';
        }

        return 'unknown';
    }
}