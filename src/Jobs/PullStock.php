<?php

namespace Daalder\Exact\Jobs;

use Daalder\Exact\Events\BeforeProductStockSaved;
use Daalder\Exact\Events\ExactProductStockPulled;
use Daalder\Exact\Services\ConnectionFactory;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Support\Arr;
use Picqer\Financials\Exact\InventoryItemWarehouse;
use Picqer\Financials\Exact\ItemWarehouse;
use Picqer\Financials\Exact\StockPosition;
use Pionect\Daalder\Models\Product\Repositories\ProductRepository;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Picqer\Financials\Exact\Item;
use Pionect\Daalder\Models\Product\Product;

class PullStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Batchable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;

    /**
     * @var Product $product ;
     */
    protected $product;

    /**
     * @var ProductRepository $productRepository ;
     */
    protected $productRepository;

    public function __construct(Product $product)
    {
        $this->product = $product;
        $this->productRepository = app(ProductRepository::class);
    }

    public function middleware()
    {
        return [
            // If the job fails two times in five minutes, wait five minutes before retrying
            // If the job fails before the threshold has been reached, wait 0 to 5 minutes before retrying
            (new ThrottlesExceptions(2, 5))->backoff(rand(0, 5))
        ];
    }

    public function handle()
    {
        // Resolve Picqer Connection
        $connection = ConnectionFactory::getConnection();

        // Filter Exact items based on Daalder product sku
        $code = $this->product->sku;
        $item = new Item($connection);
        $item = $item->filter("Code eq '" . $code . "'");

        // Get the Exact Item or return
        if (count($item) > 0) {
            /** @var Item $item */
            $item = $item[0];
        } else {
            $this->fail(
                'Exact Item not found for Daalder Product with id ' . $this->product->id .
                ' and sku ' . $this->product->sku
            );
        }

        $itemWarehouse = new ItemWarehouse($connection);
        $select = "CurrentStock, PlannedStockIn, PlannedStockOut, WarehouseCode";
        $itemWarehouse = $itemWarehouse->filter("Item eq guid'" . $item->ID . "'", '', $select);
        $stock = [];


        // Get the Exact Item or return
        if (count($itemWarehouse) > 0) {
            /** @var ItemWarehouse $itemWarehouse */

            $stockCollection = collect($itemWarehouse);
            $exactProductStock = new ExactProductStockPulled($this->product, $stockCollection);
            event($exactProductStock);
            $stock = $exactProductStock->getStock();

            $stock = $stock->reduce(function ($carry, $warehouseStock) {
                $carry['InStock'] += $warehouseStock->CurrentStock;
                $carry['PlanningIn'] += $warehouseStock->PlannedStockIn;
                $carry['PlanningOut'] += $warehouseStock->PlannedStockOut;
                return $carry;
            }, ['InStock' => 0, 'PlanningIn' => 0, 'PlanningOut' => 0]);
        } else {
            $this->fail(
                'Exact StockPosition not found for Daalder Product with id ' . $this->product->id .
                ' and sku ' . $this->product->sku . ' / Exact Item with ID ' . $item->ID
            );
        }

        $stockParams = [
            'in_stock' => Arr::get($stock, 'InStock', 0),
            'planned_in' => Arr::get($stock, 'PlanningIn', 0),
            'planned_out' => Arr::get($stock, 'PlanningOut', 0)
        ];

        $beforeProductStockSaved = new BeforeProductStockSaved($this->product, $stockParams);
        event($beforeProductStockSaved);
        $stockParams = $beforeProductStockSaved->getStock();

        $this->productRepository->storeStock($this->product, $stockParams);
    }
}
