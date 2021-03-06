<?php

namespace Daalder\Exact\Jobs;

use Daalder\Exact\Services\ConnectionFactory;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\Item;
use Picqer\Financials\Exact\StockCount;
use Picqer\Financials\Exact\StockCountLine;
use Pionect\Daalder\Models\Product\Stock;

class PushStockToExact implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Batchable;

    /**
     * @var Stock $stock
     */
    protected $stock;

    public function __construct(Stock $stock)
    {
        $this->stock = $stock;
    }

    public function handle() {
        // Resolve Picqer Connection
        $connection = ConnectionFactory::getConnection();

        $code = $this->stock->product->sku;

        $item = new Item($connection);
        $item = $item->filter("Code eq '".$code."'");

        if(count($item) > 0) {
            $item = $item[0];
            if($item->Stock != $this->stock->in_stock) {
                $stockCount = new StockCount($connection);
                $stockCount->Status = 21;
                $stockCount->StockCountDate = today()->format('Y-m-d');

                $stockCountLine = new StockCountLine($connection);
                $stockCountLine->Item = $item->ID;
                $stockCountLine->QuantityNew = $this->stock->in_stock;
                $stockCount->StockCountLines = [
                    $stockCountLine
                ];
                $stockCount->save();
            }
        }
    }
}
