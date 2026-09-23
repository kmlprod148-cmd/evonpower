<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use Illuminate\Http\Request;

class PartnerViewController extends Controller
{
    public function index()
    {
        $partners = Partner::paginate(15);
        return view('partners.index', compact('partners'));
    }

    public function show($id)
    {
        $partner = Partner::findOrFail($id);
        return view('partners.show', compact('partner'));
    }
}