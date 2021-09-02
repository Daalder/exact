<?php namespace Daalder\Exact\Commands;

use Daalder\Exact\Jobs\PushProductToExact as PushProductToExactJob;
use Illuminate\Console\Command;
use Pionect\Daalder\Models\Product\Product;

class PushProductToExact extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'exact:push-product {sku}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pushes a Daalder product to Exact. Matches Daalder sku and Exact Item Code. Creates a new Exact Item if no matches are found.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $sku = $this->argument('sku');
        $product = Product::firstWhere('sku', $sku);

        if(is_null($product)) {
            $this->error("Daalder product with sku '". $sku . "' not found. Exiting...");
            return;
        }

        PushProductToExactJob::dispatchNow($product);
    }
}
