<?php

namespace App\Console\Commands;

use App\Models\SignalSnapshot;
use App\Services\Signal\AiSignalService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ShowSignalHistory extends Command
{
    protected $signature = 'signal:history
        {--symbol=BTC : Symbol to show}
        {--limit=25 : Number of entries}
        {--direction= : Filter by label outcome (UP/DOWN/FLAT/PENDING)}';

    protected $description = 'Display recent signal snapshots with outcomes and AI probability';

    public function __construct(
        protected AiSignalService $aiSignalService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $symbol = strtoupper($this->option('symbol') ?? 'BTC');
        $limit = min(200, max(5, (int) $this->option('limit')));
        $direction = $this->option('direction');

        $query = SignalSnapshot::where('symbol', $symbol)
            ->orderByDesc('generated_at');

        if ($direction) {
            if (Str::upper($direction) === 'PENDING') {
                $query->whereNull('label_direction');
            } else {
                $query->where('label_direction', strtoupper($direction));
            }
        }

        $rows = $query->limit($limit)->get();

        if ($rows->isEmpty()) {
            $this->warn('No signal entries found.');
            return self::SUCCESS;
        }

        $table = $rows->map(function (SignalSnapshot $snapshot) {
            $ai = $this->aiSignalService->predict(
                $snapshot->features_payload ?? [],
                ['score' => $snapshot->signal_score]
            );
            $returnPct = ($snapshot->price_future && $snapshot->price_now)
                ? (($snapshot->price_future - $snapshot->price_now) / $snapshot->price_now) * 100
                : null;

            return [
                optional($snapshot->generated_at)->format('Y-m-d H:i'),
                $snapshot->signal_rule,
                number_format($snapshot->signal_score, 2),
                $snapshot->label_direction ?? 'PENDING',
                $returnPct !== null ? number_format($returnPct, 2) . '%' : '--',
                $ai ? number_format($ai['probability'] * 100, 2) . '%' : '--',
                $ai['decision'] ?? '--',
            ];
        });

        $this->table(
            ['Time (UTC)', 'Signal', 'Score', 'Outcome', 'Δ Price', 'AI Prob', 'AI Decision'],
            $table
        );

        return self::SUCCESS;
    }
}
