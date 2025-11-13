<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class SignalAnalytics extends Model
{
    use HasFactory;

    protected $table = 'cg_signal_analytics';

    protected $fillable = [
        'symbol',
        'type',
        'data',
        'metadata',
        'period_start',
        'period_end',
        'parameters',
        'generated_at',
    ];

    protected $casts = [
        'data' => 'array',
        'metadata' => 'array',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'generated_at' => 'datetime',
    ];

    public static function storeAnalytics(string $symbol, string $type, array $data, array $metadata = null, array $parameters = null): self
    {
        return self::create([
            'symbol' => $symbol,
            'type' => $type,
            'data' => $data,
            'metadata' => $metadata,
            'period_start' => $metadata['period_start'] ?? now(),
            'period_end' => $metadata['period_end'] ?? now(),
            'parameters' => $parameters ? json_encode($parameters) : null,
            'generated_at' => now(),
        ]);
    }

    public static function getLatest(string $symbol, string $type, int $hours = 24)
    {
        return self::where('symbol', $symbol)
            ->where('type', $type)
            ->where('generated_at', '>=', now()->subHours($hours))
            ->orderBy('generated_at', 'desc')
            ->first();
    }

    public static function getHistoryForPeriod(string $symbol, string $startDate, string $endDate)
    {
        return self::where('symbol', $symbol)
            ->where('type', 'history')
            ->whereBetween('generated_at', [$startDate, $endDate])
            ->orderBy('generated_at', 'desc')
            ->get();
    }

    public static function getBacktestForPeriod(string $symbol, string $startDate, string $endDate)
    {
        return self::where('symbol', $symbol)
            ->where('type', 'backtest')
            ->whereBetween('generated_at', [$startDate, $endDate])
            ->orderBy('generated_at', 'desc')
            ->get();
    }

    public function getDataAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }
}