<?php

namespace Daalder\Exact\Observers;

use Daalder\Exact\Jobs\PushStockToExact;
use Pionect\Daalder\Models\Product\Stock;

class StockObserver
{
    public function saved(Stock $stock) {
//        PushStockToExact::dispatchNow($stock);
    }
}
