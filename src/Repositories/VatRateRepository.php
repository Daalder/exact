<?php

namespace Daalder\Exact\Repositories;

use Pionect\Daalder\Models\Order\Order;
use Pionect\Daalder\Models\VatRate\VatRate;

class VatRateRepository extends \Pionect\Daalder\Models\VatRate\Repositories\VatRateRepository
{
    // TODO: change return type to string|null on PHP 8
    public function getExactCodeFromVatRate(VatRate $vatRate) {
        return $vatRate->exact_code;
    }
}
