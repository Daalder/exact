<?php

namespace Daalder\Exact\Http\Controllers;

use App\Http\Controllers\Controller;
use Daalder\Exact\Services\ConnectionFactory;
use Illuminate\Http\Request;
use Picqer\Financials\Exact\Connection;

class AuthController extends Controller
{
    public function callback(Request $request)
    {
        $code = $request->get('code');

        // If storage/exact directory doesn't exist yet
        if(file_exists(storage_path('exact')) === false) {
            // Create directory
            mkdir(storage_path('exact'));
        }
        // Store authorization code
        file_put_contents(storage_path('exact/oauth.json'), '{"authorization_code": "'.$code.'"}');

        return redirect()->intended();
    }

    public function authenticateExact(Request $request) {
        /** @var Connection $connection */
        $connection = ConnectionFactory::getConnection();

        return response()->json([
            'auth_url' => $connection->getAuthUrl()
        ]);
    }
}
