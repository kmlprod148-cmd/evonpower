<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = \App\Models\Account::all();
        return view('accounts.index', compact('accounts'));
    }
}
