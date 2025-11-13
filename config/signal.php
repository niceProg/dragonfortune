<?php

return [
    'symbols' => explode(',', env('SIGNAL_SYMBOLS', 'BTC')),
    'default_interval' => env('SIGNAL_INTERVAL', '1h'),
    'collector_poll_minutes' => (int) env('SIGNAL_COLLECTOR_POLL_MINUTES', 60),
    'label_horizon_hours' => (int) env('SIGNAL_LABEL_HOURS', 24),
];
