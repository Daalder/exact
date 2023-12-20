<?php

namespace Daalder\Exact\Events;

use Pionect\Daalder\Models\Product\Product;
use Illuminate\Support\Collection;

class ExactProductStockPulled
{

    /**
     * @var Product $product
     */
    protected Product $product;

    /**
     * @var Collection $exactItemWarehousesData
     */
    protected Collection $exactItemWarehousesData;

    public function __construct(Product $product, Collection $exactItemWarehousesData)
    {
        $this->product = $product;
        $this->exactItemWarehousesData = $exactItemWarehousesData;
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
    * @return Collection
     */
    public function getExactItemWarehousesData(): Collection
    {
        return $this->exactItemWarehousesData;
    }

    /**
     * @param Collection $exactItemWarehousesData
     * @return void
     */
    public function setExactItemWarehousesData(Collection $exactItemWarehousesData): void
    {
        $this->exactItemWarehousesData = $exactItemWarehousesData;
    }
}
