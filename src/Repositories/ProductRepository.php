<?php

namespace Daalder\Exact\Repositories;

use Daalder\Exact\Services\ConnectionFactory;
use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\Item;
use Pionect\Daalder\Models\Product\Product;

class ProductRepository extends \Pionect\Daalder\Models\Product\Repositories\ProductRepository
{
    /**
     * @param  string  $exactId
     * @return null
     */
    public function getProductFromExactId(string $exactId) {
        $connection = ConnectionFactory::getConnection();
        if($connection->needsAuthentication()) {
            throw new \Exception("Connection to Exact is not authenticated! Call the 'authenticate-exact' endpoint to fix this.");
        }

        $item = new Item($connection);
        $item = $item->filter("ID eq guid'$exactId'");

        if(count($item) === 0) {
            return null;
        }

        $item = $item[0];
        $product = Product::firstWhere('sku', $item->Code);
        if(is_null($product)) {
            return null;
        }

        return $product;
    }
}
