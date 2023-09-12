<?php

namespace Daalder\Exact\Jobs;

use App\Models\ProductAttribute\Set;
use Daalder\Exact\Services\ConnectionFactory;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Picqer\Financials\Exact\StockPosition;
use Pionect\Daalder\Models\Product\Repositories\ProductRepository;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Picqer\Financials\Exact\Item;
use Pionect\Daalder\Models\Product\Product;
use App\Models\Warehouse\Warehouse;

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

        $stockPosition = new StockPosition($connection);
        $stockPosition = $stockPosition->filter([], '', '', ['itemId' => "guid'{$item->ID}'"]);

        // Get the Exact Item or return
        if (count($stockPosition) > 0) {
            /** @var StockPosition $stockPosition */
            $stockPosition = $stockPosition[0];
        } else {
            $this->fail(
                'Exact StockPosition not found for Daalder Product with id ' . $this->product->id .
                ' and sku ' . $this->product->sku . ' / Exact Item with ID ' . $item->ID
            );
        }

        $this->storeStock($stockPosition);
    }

    /**
     * @param $stockPosition
     * @return void
     */
    private function storeStock($stockPosition): void
    {
        $channableWarehouse = Warehouse::firstOrCreate(['code' => 'channable'], ['name' => 'Channable']);
        $defaultWarehouse = Warehouse::defaultWarehouse();

        $ticketAttributeSetId = Set::tickets()->id;
        $isTicket = $this->product->productattributeset_id == $ticketAttributeSetId;

        $stock = $stockPosition->InStock ?? 0;
        $plannedIn = $stockPosition->PlanningIn ?? 0;
        $plannedOut = $stockPosition->PlanningOut ?? 0;

        $warehouses = [$channableWarehouse, $defaultWarehouse];
        $stockParams = ['warehouses' => []];

        foreach ($warehouses as $warehouse) {
            $isChannableWarehouse = $warehouse->code === 'channable';

            if ($isTicket && $isChannableWarehouse) {
                continue;
            }

            $inStock = $isTicket
                ? $stock
                : $this->calculateInStock($stock, $isChannableWarehouse);

            $stockParams['warehouses'][] = $this->storeProductStock($warehouse->id, $inStock, $plannedIn, $plannedOut);
        }

        if (count($stockParams['warehouses']) > 0) {
            $this->productRepository->storeStock($this->product, $stockParams);
        }
    }

    /**
     * @param int $stock
     * @param bool $isChannableWarehouse
     * @return int
     */
    private function calculateInStock(int $stock, bool $isChannableWarehouse): int
    {
        $channableStock = floor($stock * 0.25);

        return $isChannableWarehouse ? $channableStock : $stock - $channableStock;
    }

    /**
     * @param int $warehouseId
     * @param int $inStock
     * @param int $plannedIn
     * @param int $plannedOut
     * @return array
     */
    private function storeProductStock(int $warehouseId, int $inStock, int $plannedIn, int $plannedOut): array
    {
        return [
            'product_id' => $this->product->id,
            'id' => $warehouseId,
            'in_stock' => $inStock,
            'planned_in' => $plannedIn,
            'planned_out' => $plannedOut
        ];
    }

}
