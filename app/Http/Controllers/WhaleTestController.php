<?php

namespace App\Http\Controllers;

use App\Models\WhaleTransfer;
use App\Repositories\MarketDataRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WhaleTestController extends Controller
{
    public function __construct(
        protected MarketDataRepository $marketData
    ) {}

    public function testConnection(): JsonResponse
    {
        $results = [];

        // Test 1: Basic database connection
        try {
            $connection = DB::connection();
            $results['connection'] = [
                'status' => 'success',
                'database' => $connection->getDatabaseName(),
                'host' => $connection->getConfig('host'),
                'port' => $connection->getConfig('port')
            ];
        } catch (\Exception $e) {
            $results['connection'] = [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }

        // Test 2: Check if table exists
        try {
            $tableExists = DB::getSchemaBuilder()->hasTable('cg_whale_transfer');
            $results['table_exists'] = [
                'status' => $tableExists ? 'exists' : 'not_found',
                'table_name' => 'cg_whale_transfer'
            ];
        } catch (\Exception $e) {
            $results['table_exists'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }

        // Test 3: Check table structure
        if ($results['table_exists']['status'] === 'exists') {
            try {
                $columns = DB::getSchemaBuilder()->getColumnListing('cg_whale_transfer');
                $results['table_structure'] = [
                    'status' => 'success',
                    'columns' => $columns
                ];
            } catch (\Exception $e) {
                $results['table_structure'] = [
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }

        // Test 4: Count records
        if ($results['table_exists']['status'] === 'exists') {
            try {
                $count = DB::table('cg_whale_transfer')->count();
                $results['record_count'] = [
                    'status' => 'success',
                    'total_records' => $count
                ];
            } catch (\Exception $e) {
                $results['record_count'] = [
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }

        // Test 5: Sample records (if any)
        if ($results['table_exists']['status'] === 'exists' &&
            ($results['record_count']['total_records'] ?? 0) > 0) {
            try {
                $samples = DB::table('cg_whale_transfer')
                    ->select('transaction_hash', 'asset_symbol', 'amount_usd', 'from_address', 'to_address', 'block_timestamp')
                    ->limit(3)
                    ->get();

                $results['sample_data'] = [
                    'status' => 'success',
                    'data' => $samples->toArray()
                ];
            } catch (\Exception $e) {
                $results['sample_data'] = [
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }

        // Test 6: Try the MarketDataRepository method
        try {
            $whaleData = $this->marketData->latestWhaleTransfers('BTC', null, 5);
            $results['repository_query'] = [
                'status' => 'success',
                'count' => $whaleData->count(),
                'first_record' => $whaleData->first() ? $whaleData->first()->toArray() : null
            ];
        } catch (\Exception $e) {
            $results['repository_query'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }

        return response()->json([
            'success' => true,
            'timestamp' => now()->toISOString(),
            'tests' => $results
        ]);
    }

    public function testWhaleFlowAPI(): JsonResponse
    {
        try {
            // Simulate the same call that the SignalController makes
            $marketData = app(MarketDataRepository::class);
            $whaleData = $marketData->latestWhaleTransfers('BTC', null, 10);

            return response()->json([
                'success' => true,
                'whale_data' => [
                    'count' => $whaleData->count(),
                    'data' => $whaleData->toArray(),
                    'sample_structure' => $whaleData->first() ? array_keys($whaleData->first()->toArray()) : []
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