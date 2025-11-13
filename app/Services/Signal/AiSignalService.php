<?php

namespace App\Services\Signal;

class AiSignalService
{
    public function __construct(
        protected ModelTrainer $trainer
    ) {
    }

    public function predict(array $featuresPayload, ?array $signalMeta = null): ?array
    {
        $probability = $this->trainer->predict($featuresPayload);
        if ($probability === null) {
            $probability = $this->fallbackProbability($signalMeta['score'] ?? null);
        }

        if ($probability === null) {
            return null;
        }

        return [
            'probability' => $probability,
            'decision' => $this->decisionFromProbability($probability),
            'confidence' => $this->confidenceFromProbability($probability),
        ];
    }

    protected function fallbackProbability(?float $score): ?float
    {
        if ($score === null) {
            return null;
        }

        $clamped = max(min($score, 5), -5);
        return 1 / (1 + exp(-$clamped));
    }

    protected function decisionFromProbability(float $probability): string
    {
        if ($probability >= 0.55) {
            return 'BUY';
        }

        if ($probability <= 0.45) {
            return 'SELL';
        }

        return 'NEUTRAL';
    }

    protected function confidenceFromProbability(float $probability): float
    {
        return round(abs($probability - 0.5) * 2, 3);
    }
}
