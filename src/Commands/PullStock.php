<?php namespace Daalder\Exact\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Bus;
use Pionect\Daalder\Models\Product\Repositories\ProductRepository;

class PullStock extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'exact:pull-stock {skus?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulls the the stock for all Daalder products from Exact. Optionally limits the sync to one product. Matches Daalder sku and Exact Item Code.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $skus = $this->argument('skus');

        /** @var Builder $query */
        $query = app(ProductRepository::class)->newQuery();

        if($skus) {
            $query->whereIn('sku', $skus);
        }

        $batch = [];

        $query->each(function($product) use (&$batch) {
            $batch[] = new \Daalder\Exact\Jobs\PullStock($product);
        }, 100);

        Bus::batch($batch)
            ->name('Daalder Exact - Pull Stock')
            ->dispatch();

        $this->info('Dispatched a stock sync for '. count($batch) . ' product(s).');

    }
}
