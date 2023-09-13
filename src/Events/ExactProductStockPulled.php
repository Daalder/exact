<?php

namespace Daalder\Exact\Events;

use Pionect\Daalder\Models\Product\Product;

class ExactProductStockPulled
{

    /**
     * @var Product $product
     */
    protected Product $product;

    /**
     * @var array
     */
    protected array $stockParams;

    public function __construct(Product $product, array $stockParams)
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
     * @return array
     */
    public function getStockParams(): array
    {
        return $this->stockParams;
    }

    /**
     * @param array $stockParams
     * @return void
     */
    public function setStockParams(array $stockParams): void
    {
        $this->stockParams = $stockParams;
    }
}
