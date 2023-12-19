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
     * @var Collection $stockParams
     */
    protected Collection $stockParams;

    public function __construct(Product $product, Collection $stockParams)
    {
        $this->product = $product;
        $this->stockParams = $stockParams;
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
    public function getStock(): Collection
    {
        return $this->stockParams;
    }

    /**
     * @param Collection $stockParams
     * @return void
     */
    public function setStock(Collection $stockParams): void
    {
        $this->stockParams = $stockParams;
    }
}
