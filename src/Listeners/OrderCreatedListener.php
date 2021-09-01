<?php namespace Daalder\Exact\Listeners;

use Daalder\Exact\Jobs\PushOrderToExact;
use Pionect\Daalder\Events\Order\OrderCreated;

class OrderCreatedListener
{
    public function handle(OrderCreated $event)
    {
        dispatch_now(new PushOrderToExact($event->order));
    }
}
