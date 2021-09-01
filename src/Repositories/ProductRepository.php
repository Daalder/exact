<?php

namespace Daalder\Exact\Repositories;

use Pionect\Daalder\Models\Product\Product;

class ProductRepository extends \Pionect\Daalder\Models\Product\Repositories\ProductRepository
{
    // TODO: change return type to int|null on PHP 8
    public function getExactIdFromProduct(Product $product) {
        return $product->exact_id;
    }

    public function setExactIdIfNotExists(Product $product, string $exactId): void {
        if(is_null($this->getExactIdFromProduct($product))) {
            $product->exact_id = $exactId;

            Product::withoutSyncingToSearch(function() use ($product) {
                $product->save();
            });
        }
    }
}
