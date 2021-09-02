<?php

namespace Daalder\Exact\Http\Controllers;

use App\Http\Controllers\Controller;
use Daalder\Exact\Repositories\ProductRepository;
use Illuminate\Http\Request;
use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\StockPosition;
use Picqer\Financials\Exact\Webhook\Authenticatable;

class WebhookController extends Controller
{
    use Authenticatable;

    /** @var ProductRepository $productRepository */
    private $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    private function isAuthenticated() {
        return $this->authenticate(
            json_encode(request()->all(), JSON_UNESCAPED_SLASHES),
            config('daalder-exact.webhook_secret')
        );
    }

    public function stockPosition(Request $request)
    {
        // If not authenticated
        if($this->isAuthenticated() !== true) {
            // Exact calls this endpoint without a payload when first registering the webhook.
            // This call cannot be authenticated. Therefore, simply return an empty
            // 200 response so Exact finishes registering the webhook.
            return response('', 200);
        }

        $stockPositionID = $request->all()['Content']['Key'];

        /** @var Connection $connection */
        $connection = app(Connection::class);
        $stockPosition = new StockPosition($connection);
        $stockPosition = $stockPosition->filter([], '', '', ['itemId' => "guid'{$stockPositionID}'"]);

        if(count($stockPosition) > 0) {
            $stockPosition = $stockPosition[0];
            $daalderProduct = $this->productRepository->getProductFromExactId($stockPosition->ItemId);

            $this->productRepository->storeStock($daalderProduct,[
                'product_id' => $daalderProduct->id,
                'in_stock' => $stockPosition->InStock ?? 0,
                'planned_in' => $stockPosition->PlanningIn ?? 0,
                'planned_out' => $stockPosition->PlanningOut ?? 0
            ]);
        }

        return response('', 200);
    }
}
