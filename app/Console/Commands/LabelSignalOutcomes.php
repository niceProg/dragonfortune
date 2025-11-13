<?php

namespace App\Console\Commands;

use App\Models\SignalSnapshot;
use App\Repositories\MarketDataRepository;
use Carbon\Carbon;
use Illuminate\Console\Command;

class LabelSignalOutcomes extends Command
{
    protected $signature = 'signal:label 
        {--symbol= : Limit to symbol}
        {--limit=100 : Max rows to process per run}';

    protected $description = 'Fill price_future + labels for signal snapshots once the horizon has passed';

    public function __construct(
        protected MarketDataRepository $marketData
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $symbol = $this->option('symbol') ? strtoupper($this->option('symbol')) : null;
        $limit = (int) $this->option('limit');
        $horizonHours = config('signal.label_horizon_hours', 24);
        $cutoff = now('UTC')->subHours($horizonHours);

        $query = SignalSnapshot::whereNull('price_future')
            ->where('generated_at', '<=', $cutoff)
            ->orderBy('generated_at');

        if ($symbol) {
            $query->where('symbol', $symbol);
        }

        $snapshots = $query->limit($limit)->get();

        if ($snapshots->isEmpty()) {
            $this->info('No snapshots pending labeling.');
            return self::SUCCESS;
        }

        $this->info("Processing {$snapshots->count()} snapshots (horizon {$horizonHours}h)");

        foreach ($snapshots as $snapshot) {
            $target = $snapshot->generated_at->copy()->addHours($horizonHours);
            $pair = $snapshot->pair ?: "{$snapshot->symbol}USDT";

            $priceFuture = $this->marketData->spotPriceAt($pair, $target->timestamp, $snapshot->interval);

            if ($priceFuture === null) {
                $this->warn("Failed to fetch price for {$pair} at {$target->toIso8601String()}");
                continue;
            }

            $priceNow = $snapshot->price_now;
            $delta = $priceNow ? $priceFuture - $priceNow : null;
            $pct = ($priceNow && $priceNow != 0) ? ($delta / $priceNow) * 100 : null;

            $snapshot->price_future = $priceFuture;
            $snapshot->label_direction = $this->resolveDirection($delta);
            $snapshot->label_magnitude = $pct;
            $snapshot->save();
        }

        $this->info('Labeling completed.');
        return self::SUCCESS;
    }

    protected function resolveDirection(?float $delta): ?string
    {
        if ($delta === null) {
            return null;
        }

        if ($delta > 0) {
            return 'UP';
        }

        if ($delta < 0) {
            return 'DOWN';
        }

        return 'FLAT';
    }
}
