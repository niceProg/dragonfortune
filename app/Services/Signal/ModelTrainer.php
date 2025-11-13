<?php

namespace App\Services\Signal;

use Illuminate\Support\Facades\Storage;

class ModelTrainer
{
    protected array $featureNames = [
        'funding_heat',
        'funding_trend',
        'oi_pct_change',
        'whale_pressure',
        'whale_cex_ratio',
        'etf_flow',
        'etf_streak',
        'sentiment',
        'taker_ratio',
        'liquidation_bias',
        'volatility_24h',
    ];

    protected string $modelPath = 'signal-model.json';

    public function train(array $dataset, array $labels, int $epochs = 300, float $learningRate = 0.01): ?array
    {
        if (count($dataset) < 20) {
            return null;
        }

        $featureCount = count($this->featureNames);
        $weights = array_fill(0, $featureCount + 1, 0.0); // bias + features
        $n = count($dataset);

        for ($epoch = 0; $epoch < $epochs; $epoch++) {
            $gradients = array_fill(0, $featureCount + 1, 0.0);

            for ($i = 0; $i < $n; $i++) {
                $x = array_merge([1.0], $dataset[$i]);
                $y = $labels[$i];
                $prediction = $this->sigmoid($this->dot($weights, $x));
                $error = $prediction - $y;

                foreach ($gradients as $j => $_) {
                    $gradients[$j] += $error * $x[$j];
                }
            }

            foreach ($weights as $j => $weight) {
                $weights[$j] -= $learningRate * ($gradients[$j] / $n);
            }
        }

        return [
            'feature_names' => $this->featureNames,
            'weights' => $weights,
            'trained_at' => now('UTC')->toIso8601String(),
            'epochs' => $epochs,
            'learning_rate' => $learningRate,
        ];
    }

    public function saveModel(array $model): void
    {
        Storage::disk('local')->put($this->modelPath, json_encode($model));
    }

    public function loadModel(): ?array
    {
        if (!Storage::disk('local')->exists($this->modelPath)) {
            return null;
        }

        return json_decode(Storage::disk('local')->get($this->modelPath), true);
    }

    public function extractFeatureVector(array $payload): ?array
    {
        $funding = data_get($payload, 'funding.heat_score');
        $fundingTrend = data_get($payload, 'funding.trend_pct');
        $oi = data_get($payload, 'open_interest.pct_change_24h');
        $whale = data_get($payload, 'whales.pressure_score');
        $whaleCex = data_get($payload, 'whales.cex_ratio');
        $etf = data_get($payload, 'etf.latest_flow');
        $etfStreak = data_get($payload, 'etf.streak');
        $sentiment = data_get($payload, 'sentiment.value');
        $taker = data_get($payload, 'microstructure.taker_flow.buy_ratio');
        $longs = data_get($payload, 'liquidations.sum_24h.longs');
        $shorts = data_get($payload, 'liquidations.sum_24h.shorts');
        $volatility = data_get($payload, 'microstructure.price.volatility_24h');

        if ($funding === null && $oi === null && $whale === null) {
            return null;
        }

        $liquidationBias = null;
        if ($longs !== null && $shorts !== null && ($longs + $shorts) > 0) {
            $liquidationBias = ($shorts - $longs) / ($shorts + $longs);
        }

        return [
            $this->normalize($funding, 3),
            $this->normalize($fundingTrend, 100),
            $this->normalize($oi, 50),
            $this->normalize($whale, 3),
            $whaleCex ?? 0.5,
            $this->normalize($etf, 100_000_000),
            $this->normalize($etfStreak, 10),
            $this->normalize($sentiment, 100),
            $taker ?? 0.5,
            $liquidationBias ?? 0.0,
            $this->normalize($volatility, 10),
        ];
    }

    public function predict(array $payload): ?float
    {
        $model = $this->loadModel();
        if (!$model) {
            return null;
        }

        $vector = $this->extractFeatureVector($payload);
        if (!$vector) {
            return null;
        }

        $weights = $model['weights'];
        $x = array_merge([1.0], $vector);

        return $this->sigmoid($this->dot($weights, $x));
    }

    protected function normalize($value, float $scale): float
    {
        if ($value === null) {
            return 0.0;
        }
        return (float) $value / $scale;
    }

    protected function dot(array $weights, array $features): float
    {
        $sum = 0.0;
        foreach ($weights as $i => $weight) {
            $sum += $weight * ($features[$i] ?? 0.0);
        }

        return $sum;
    }

    protected function sigmoid(float $z): float
    {
        return 1 / (1 + exp(-$z));
    }
}
