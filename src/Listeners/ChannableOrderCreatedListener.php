<?php namespace Daalder\Exact\Listeners;

use App\Events\Order\ChannableOrderCreated;
use Daalder\Exact\Jobs\PushOrderToExact;

class ChannableOrderCreatedListener
{
    public function handle(ChannableOrderCreated $event): void
    {
        dispatch(new PushOrderToExact($event->getOrder()));
    }
}
