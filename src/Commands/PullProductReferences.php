<?php namespace Daalder\Exact\Commands;

use Illuminate\Console\Command;
use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\Item;
use Pionect\Daalder\Models\Product\Product;

class PullProductReferences extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'exact:pull-product-references';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Matches Daalder products to Exact Items using the Daalder Product skus and saves the matched Exact Item IDs to the corresponding Daalder Products';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $connection = app(Connection::class);
        $totalMatchedCount = 0;

        Product::query()
            ->chunk(100, function($products) use ($connection, &$totalMatchedCount) {
                $matchPairs = [];

                foreach($products as $product) {
                    try {
                        $item = new Item($connection);
                    } catch (\Exception $e) {
                        $this->error('Failed to connect to Exact API. See error below.');
                        $this->error($e->getMessage());
                    }

                    try {
                        $item = $item->filter("Code eq '$product->sku'");
                    } catch (\Exception $e) {
                        $this->error('Failed to filter for Exact Item with code (sku): '. $product->sku . '. See error below.');
                        $this->error($e->getMessage());
                        continue;
                    }

                    if(count($item) === 0) {
                        $this->error('Failed to find Exact Item with code (sku): '. $product->sku);
                        continue;
                    }

                    if(count($item) > 1) {
                        $this->warning('Found '.count($item).' Exact Items with code (sku): '. $product->sku .'. Using the first one...');
                    }
                    $item = $item[0];

                    $matchPairs[] = [
                        'id' => $product->id,
                        'exact_id' => $item->ID,
                    ];
                }

                Product::upsert($matchPairs, ['id'], ['exact_id']);
                $totalMatchedCount += count($matchPairs);
            });

        $this->info('Matched ' . $totalMatchedCount . ' Daalder Products/Exact Items.');
    }
}
