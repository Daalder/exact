<?php

namespace Daalder\Exact\Jobs;

use Daalder\Exact\Events\ExactProductStockPulled;
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

        $stockParams = [
            'product_id' => $this->product->id,
            'in_stock' => $stockPosition->InStock ?? 0,
            'planned_in' => $stockPosition->PlanningIn ?? 0,
            'planned_out' => $stockPosition->PlanningOut ?? 0
        ];

        $exactProductStock = new ExactProductStockPulled($this->product, $stockParams);
        event($exactProductStock);
        $stockParams = $exactProductStock->getStockParams();

        $this->productRepository->storeStock($this->product, $stockParams);
    }
}
