<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SignalSnapshot extends Model
{
    use HasFactory;

    protected $table = 'cg_signal_dataset';

    protected $fillable = [
        'symbol',
        'pair',
        'interval',
        'run_id',
        'generated_at',
        'price_now',
        'price_future',
        'label_direction',
        'label_magnitude',
        'signal_rule',
        'signal_score',
        'signal_confidence',
        'signal_reasons',
        'features_payload',
        'is_missing_data',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'price_now' => 'float',
        'price_future' => 'float',
        'label_magnitude' => 'float',
        'signal_score' => 'float',
        'signal_confidence' => 'float',
        'signal_reasons' => 'array',
        'features_payload' => 'array',
        'is_missing_data' => 'boolean',
    ];
}
