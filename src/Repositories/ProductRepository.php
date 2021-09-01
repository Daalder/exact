<?php

namespace Daalder\Exact\Repositories;

use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\Item;
use Picqer\Financials\Exact\SalesItemPrice;
use Pionect\Daalder\Models\Country\Currency;
use Pionect\Daalder\Models\Price\Price;
use Pionect\Daalder\Models\Price\PriceType;
use Pionect\Daalder\Models\Product\Product;
use Pionect\Daalder\Models\VatRate\VatRate;

class ProductRepository extends \Pionect\Daalder\Models\Product\Repositories\ProductRepository
{
    // TODO: change return type to int|null on PHP 8
    public function getExactIdFromProduct(Product $product) {
        return $product->exact_id;
    }

    /**
     * @param  string  $exactId
     * @return mixed
     * @description Find
     */
    public function getProductFromExactId(string $exactId) {
        $product = Product::firstWhere('exact_id', $exactId);

        if(is_null($product)) {
            $product = $this->matchProductWithExactItemFromExactId($exactId);
        }

        return $product;
    }

    /**
     * @param  string  $exactId
     * @return null
     */
    public function matchProductWithExactItemFromExactId(string $exactId) {
        $connection = app(Connection::class);
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

        $this->setExactIdIfNotExists($product, $exactId);
        return $product;
    }

    public function setExactIdIfNotExists(Product $product, string $exactId): void {
        if(is_null($this->getExactIdFromProduct($product))) {
            $product->exact_id = $exactId;

            Product::withoutSyncingToSearch(function() use ($product) {
                $product->save();
            });
        }
    }

    public function updateProductFromExactItemId(string $exactItemId) {
        /** @var Connection $connection */
        $connection = app(Connection::class);
        $item = new Item($connection);
        $item = $item->find($exactItemId);

        if(!is_null($item)) {
            $daalderProduct = $this->getProductFromExactId($exactItemId);

            $daalderProduct->name = $item->Description;
            $daalderProduct->sku = $item->Code;
            $daalderProduct->ean = $item->Barcode;
//            $daalderProduct->shippingTime = $item->Barcode;

            $salesItemPrices = new SalesItemPrice($connection);
            $salesItemPrices = $salesItemPrices->filter(
                "Item eq guid'".$exactItemId."'",
                '',
                'Currency, DefaultItemUnit, DefaultItemUnitDescription, StartDate, EndDate, Price, Quantity, Unit, UnitDescription'
            );

            $vatRate = VatRate::firstWhere('exact_code', trim($item->SalesVatCode))
                ?? app(VatRateRepository::class)->fetchPreferred();

            $daalderProduct->prices()->delete();
            foreach($salesItemPrices as $salesItemPrice) {
                $daalderProduct->prices()->save(new Price([
                    'price' => $salesItemPrice->Price,
                    'price_type_id' => PriceType::fetch(PriceType::FIXED)->id,
                    'amount' => $salesItemPrice->Quantity,
                    'vat_rate_percentage' => $vatRate->percentage,
                    'currency_id' => Currency::fetch($salesItemPrice->Currency ?? 'EUR')->id,
                ]));
            }
            $daalderProduct->save();
        }
    }
}
