<?php

namespace Daalder\Exact\Console;

use App\Console\Kernel as ConsoleKernel;
use Daalder\Exact\Jobs\RegisterWebhooks;
use Illuminate\Console\Scheduling\Schedule;

class Kernel extends ConsoleKernel {
    /**
     * Define the package's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        parent::schedule($schedule);

        $schedule->job(RegisterWebhooks::class)->daily();
    }
}
