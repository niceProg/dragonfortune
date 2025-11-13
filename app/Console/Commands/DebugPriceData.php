<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DebugPriceData extends Command
{
    protected $signature = 'debug:price-data {--symbol=BTC : Symbol to check}';

    protected $description = 'Debug price data availability across all tables';

    public function handle(): int
    {
        $symbol = strtoupper($this->option('symbol'));
        $pair = "{$symbol}USDT";

        $this->info("🔍 Debugging price data for {$symbol}");
        $this->line(str_repeat('=', 60));

        // 1. Check cg_spot_coins_markets
        $this->info("\n1. cg_spot_coins_markets table:");
        $spotMarket = DB::table('cg_spot_coins_markets')
            ->where('symbol', $symbol)
            ->first();

        if ($spotMarket) {
            $this->line("   ✅ Found: current_price = {$spotMarket->current_price}");
        } else {
            $this->line("   ❌ No data found");
        }

        // 2. Check cg_spot_price_history (MAIN price table)
        $this->info("\n2. cg_spot_price_history table (CRITICAL):");
        $spotHistory = DB::table('cg_spot_price_history')
            ->where('symbol', $symbol)
            ->orderBy('time', 'desc')
            ->limit(5)
            ->get(['time', 'open', 'high', 'low', 'close', 'volume_usd']);

        if ($spotHistory->isNotEmpty()) {
            foreach ($spotHistory as $price) {
                $this->line("   ✅ {$price->time}: O={$price->open} H={$price->high} L={$price->low} C={$price->close}");
            }
        } else {
            $this->line("   ❌ NO PRICE HISTORY FOUND - THIS IS THE PROBLEM!");
        }

        // 3. Check cg_signal_dataset for price data
        $this->info("\n3. cg_signal_dataset table (with price data):");
        $signalWithPrice = DB::table('cg_signal_dataset')
            ->where('symbol', $symbol)
            ->whereNotNull('price_now')
            ->where('price_now', '>', 0)
            ->orderBy('generated_at', 'desc')
            ->limit(5)
            ->get(['generated_at', 'price_now', 'price_future']);

        if ($signalWithPrice->isNotEmpty()) {
            foreach ($signalWithPrice as $signal) {
                $this->line("   ✅ {$signal->generated_at}: price_now = {$signal->price_now}");
            }
        } else {
            $this->line("   ❌ No price data found");
        }

        // 4. Check cg_funding_rate_history
        $this->info("\n4. cg_funding_rate_history table:");
        $fundingHistory = DB::table('cg_funding_rate_history')
            ->where('pair', $pair)
            ->orderBy('time', 'desc')
            ->limit(5)
            ->get(['time', 'close']);

        if ($fundingHistory->isNotEmpty()) {
            foreach ($fundingHistory as $funding) {
                $this->line("   ✅ {$funding->time}: close = {$funding->close}");
            }
        } else {
            $this->line("   ❌ No data found");
        }

        // 5. Count total records per table
        $this->info("\n5. Record counts:");
        $tables = [
            'cg_spot_price_history' => DB::table('cg_spot_price_history')->where('symbol', $symbol)->count(),
            'cg_spot_coins_markets' => DB::table('cg_spot_coins_markets')->where('symbol', $symbol)->count(),
            'cg_spot_pairs_markets' => DB::table('cg_spot_pairs_markets')->where('symbol', $symbol)->count(),
            'cg_signal_dataset' => DB::table('cg_signal_dataset')->where('symbol', $symbol)->count(),
            'cg_signal_dataset with price_now' => DB::table('cg_signal_dataset')->where('symbol', $symbol)->whereNotNull('price_now')->where('price_now', '>', 0)->count(),
            'cg_funding_rate_history' => DB::table('cg_funding_rate_history')->where('pair', $pair)->count(),
        ];

        foreach ($tables as $table => $count) {
            $this->line("   {$table}: {$count}");
        }

        // 5. Test price retrieval for specific timestamp
        $this->info("\n5. Testing price retrieval:");
        $testTime = Carbon::parse('2024-10-15 12:00:00');
        $this->line("   Testing timestamp: {$testTime}");

        // Test various query approaches
        $signalPriceTest = DB::table('cg_signal_dataset')
            ->where('symbol', $symbol)
            ->where('generated_at', '<=', $testTime)
            ->whereNotNull('price_now')
            ->where('price_now', '>', 0)
            ->orderByDesc('generated_at')
            ->first();

        if ($signalPriceTest) {
            $this->line("   ✅ Historical signal price: {$signalPriceTest->price_now} (from {$signalPriceTest->generated_at})");
        } else {
            $this->line("   ❌ No historical signal price found");
        }

        return self::SUCCESS;
    }
}