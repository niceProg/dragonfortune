<?php

namespace App\Console\Commands;

use App\Models\SignalSnapshot;
use App\Services\Signal\ModelTrainer;
use Illuminate\Console\Command;

class TrainSignalModel extends Command
{
    protected $signature = 'signal:train
        {--symbol=BTC : Symbol to train on}
        {--limit=5000 : Maximum snapshots}
        {--epochs=300 : Training epochs}
        {--lr=0.01 : Learning rate}';

    protected $description = 'Train logistic model for AI signal prediction';

    public function __construct(protected ModelTrainer $trainer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $symbol = strtoupper($this->option('symbol') ?? 'BTC');
        $limit = (int) $this->option('limit');
        $epochs = (int) $this->option('epochs');
        $lr = (float) $this->option('lr');

        $snapshots = SignalSnapshot::where('symbol', $symbol)
            ->whereNotNull('price_future')
            ->orderBy('generated_at')
            ->limit($limit)
            ->get();

        if ($snapshots->isEmpty()) {
            $this->error('No labeled snapshots available. Run signal:collect and signal:label first.');
            return self::FAILURE;
        }

        $dataset = [];
        $labels = [];

        foreach ($snapshots as $snapshot) {
            $payload = $snapshot->features_payload ?? [];
            $vector = $this->trainer->extractFeatureVector($payload);
            if (!$vector) {
                continue;
            }

            $dataset[] = $vector;
            $labels[] = $snapshot->label_direction === 'UP' ? 1 : 0;
        }

        if (count($dataset) < 20) {
            $this->error('Not enough usable snapshots to train.');
            return self::FAILURE;
        }

        $model = $this->trainer->train($dataset, $labels, $epochs, $lr);

        if (!$model) {
            $this->error('Training failed.');
            return self::FAILURE;
        }

        $this->trainer->saveModel($model);
        $this->info(sprintf('Model trained with %d samples and saved (%s).', count($dataset), $model['trained_at']));

        return self::SUCCESS;
    }
}
