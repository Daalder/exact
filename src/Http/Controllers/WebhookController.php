<?php

namespace Daalder\Exact\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Picqer\Financials\Exact\Connection;
use Picqer\Financials\Exact\StockPosition;
use Picqer\Financials\Exact\Webhook\Authenticatable;

class WebhookController extends Controller
{
    use Authenticatable;

    public function stockPosition(Request $request)
    {
        $authenticated = $this->authenticate(
            json_encode($request->all(), JSON_UNESCAPED_SLASHES),
            config('daalder-exact.webhook_secret')
        );

        if(!$authenticated) {
            return response()->setStatusCode(403);
        }

        $stockPositionID = $request->all()['Content']['Key'];

        /** @var Connection $connection */
        $connection = app(Connection::class);
        $stockPosition = new StockPosition($connection);
        $stockPosition = $stockPosition->find($stockPositionID);

        echo "";


//        $code = $request->get('code');
//        file_put_contents(__DIR__.'/../../../storage/oauth.json', '{"authorization_code": "'.$code.'"}');
//        return redirect()->intended();
    }
}
