<?php namespace Daalder\Exact\Listeners;

use Daalder\Exact\Jobs\RegisterWebhooks;
use Pionect\Daalder\Events\Interval\DailyEvent;

class DailyEventListener
{
    public function handle(DailyEvent $event)
    {
        dispatch(new RegisterWebhooks());
    }
}
