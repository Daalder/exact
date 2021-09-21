<?php

namespace Daalder\Exact\Observers;

use Daalder\Exact\Jobs\PushProductToExact;
use Pionect\Daalder\Models\Product\Product;

class ProductObserver
{
    public function saved(Product $product) {
        PushProductToExact::dispatch($product);
    }
}
