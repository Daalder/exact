<?php

namespace Daalder\Exact\Observers;

use Daalder\Exact\Jobs\PushOrderToExact;
use Pionect\Daalder\Models\Order\Order;

class OrderObserver
{
//    public function saved(Order $order)
//    {
//        if($order->orderrows->count() > 0) {
//            dispatch_now(new PushOrderToExact($order));
//        }
//    }
}
