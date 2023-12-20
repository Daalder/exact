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
     * @var Collection $stockData
     */
    protected Collection $stockData;

    public function __construct(Product $product, Collection $stockData)
    {
        $this->product = $product;
        $this->stockData = $stockData;
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
    public function getStockData(): Collection
    {
        return $this->stockData;
    }

    /**
     * @param Collection $stockData
     * @return void
     */
    public function setStockData(Collection $stockData): void
    {
        $this->stockData = $stockData;
    }
}
