<?php

namespace Daalder\Exact\Jobs;

use Daalder\Exact\Services\ConnectionFactory;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Money\Money;
use Picqer\Financials\Exact\SalesItemPrice;
use Pionect\Daalder\Models\Price\Price;
use Pionect\Daalder\Models\VatRate\Repositories\VatRateRepository;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\Item;
use Pionect\Daalder\Models\Product\Product;
use Pionect\Daalder\Services\MoneyFactory;

class PushProductToExact implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Batchable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * @var Product $product;
     */
    protected $product;

    public function __construct(Product $product)
    {
        $this->product = $product->fresh();
    }

    public function middleware()
    {
        return [
            // If the job fails two times in five minutes, wait five minutes before retrying
            // If the job fails before the threshold has been reached, wait 0 to 5 minutes before retrying
            (new ThrottlesExceptions(2, 5))->backoff(rand(0, 5))
        ];
    }

    public function handle() {
        // Resolve Picqer Connection
        $connection = ConnectionFactory::getConnection();

        $code = $this->product->sku;

        $item = new Item($connection);
        $item = $item->filter("Code eq '".$code."'");

        if(count($item) > 0) {
            /** @var Item $item */
            $item = $item[0];
        } else {
            $item = new Item($connection);
            $item->Code = $code;
        }

        $vatRateCode = $this->product->getActiveVatRate()->exact_code;

        $item->Description = $this->product->name;
        $item->Barcode = $this->product->ean;
        $item->SalesVatCode = $vatRateCode;
        $item->save();

        $salesItemPrices = new SalesItemPrice($connection);
        $salesItemPrices = $salesItemPrices->filter(
            "Item eq guid'".$item->ID."'",
            '',
            'ID, Item, Currency, Price, Quantity, Unit'
        );
        $salesItemPrices = collect($salesItemPrices);

        $matchedSalesItemPrices = collect();

        /** @var Price $price */
        foreach($this->product->prices as $price) {
            $formattedPrice = $price->price;
            if($formattedPrice instanceof Money) {
                $formattedPrice = MoneyFactory::toString($formattedPrice);
            }

            $salesItemPrice = $salesItemPrices->firstWhere('Quantity', $price->amount);
            if(is_null($salesItemPrice)) {
                $salesItemPrice = new SalesItemPrice($connection);
                $salesItemPrice->Item = $item->ID;
            } else {
                $matchedSalesItemPrices->push($salesItemPrice->ID);
            }

            $salesItemPrice->Currency = $price->currency->code;
            $salesItemPrice->Price = $formattedPrice;
            $salesItemPrice->Quantity = $price->amount;
            $salesItemPrice->Unit = 'pc';
            $salesItemPrice->save();
        }

        $notMatchedSalesItemPrices = $salesItemPrices->whereNotIn('ID', $matchedSalesItemPrices);
        foreach($notMatchedSalesItemPrices as $salesItemPrice) {
            $salesItemPrice->delete();
        }
    }
}
