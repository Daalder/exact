<?php namespace Daalder\Exact\Listeners;

use Daalder\Exact\Jobs\PushOrderToExact;
use Pionect\Daalder\Events\Payment\PaymentConfirmed;

class PaymentConfirmedListener
{
    public function handle(PaymentConfirmed $event)
    {
        dispatch(new PushOrderToExact($event->payment->order));
    }
}
