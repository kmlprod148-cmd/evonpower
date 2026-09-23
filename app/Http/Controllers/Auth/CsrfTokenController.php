<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

class CsrfTokenController extends Controller
{
    public function token()
    {
        return response()->json(['csrf_token' => csrf_token()]);
    }
}
