<?php

namespace Daalder\Exact\Http\Controllers;

use App\Http\Controllers\Controller;
use Daalder\Exact\Jobs\PullStock;
use Daalder\Exact\Repositories\ProductRepository;
use Daalder\Exact\Services\ConnectionFactory;
use Exception;
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

    /**
     * @throws Exception
     */
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
        $daalderProduct = $this->productRepository->getProductFromExactId($stockPositionID);

        if($daalderProduct) {
            PullStock::dispatch($daalderProduct, $stockPositionID);
        }

        return response('', 200);
    }
}
