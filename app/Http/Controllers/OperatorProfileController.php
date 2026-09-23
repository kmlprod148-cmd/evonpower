<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OperatorProfileController extends Controller
{
    public function show()
    {
        return view('operator.profile');
    }
}
