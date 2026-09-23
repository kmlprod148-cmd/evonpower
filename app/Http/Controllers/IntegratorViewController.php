<?php

namespace App\Http\Controllers;

use App\Models\Integrator;
use Illuminate\Http\Request;

class IntegratorViewController extends Controller
{
    public function index()
    {
        \Illuminate\Support\Facades\Log::info('IntegratorViewController@index');
        $integrators = Integrator::paginate(15);
        return view('integrators.index', compact('integrators'));
    }

    public function show(Integrator $integrator)
    {
        $this->authorize('view', $integrator);
        return view('integrators.show', compact('integrator'));
    }
}