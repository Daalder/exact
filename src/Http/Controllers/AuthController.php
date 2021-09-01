<?php

namespace Daalder\Exact\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function callback(Request $request)
    {
        $code = $request->get('code');
        file_put_contents(__DIR__.'/../../../storage/oauth.json', '{"authorization_code": "'.$code.'"}');

        return redirect()->intended();
    }
}
