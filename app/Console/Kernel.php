<?php

namespace App\Console;

use App\Console\Commands\CollectSignalSnapshot;
use App\Console\Commands\LabelSignalOutcomes;
use App\Console\Commands\ReplaySignalSnapshots;
use App\Console\Commands\ShowSignalHistory;
use App\Console\Commands\TrainSignalModel;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        CollectSignalSnapshot::class,
        LabelSignalOutcomes::class,
        ReplaySignalSnapshots::class,
        ShowSignalHistory::class,
        TrainSignalModel::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('signal:collect')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('signal:label')
            ->hourlyAt(30)
            ->withoutOverlapping()
            ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
