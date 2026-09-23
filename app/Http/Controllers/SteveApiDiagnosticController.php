<?php

namespace App\Http\Controllers;

use App\Services\SteVeConnectionTestService;
use Illuminate\Http\Request;

class SteveApiDiagnosticController extends Controller
{
    protected $steveService;

    public function __construct(SteVeConnectionTestService $steveService)
    {
        $this->steveService = $steveService;
    }

    public function index()
    {
        return view('steve-diagnostic.index', ['results' => null]);
    }

    public function testConnection()
    {
        $results = $this->steveService->testFullConnection();
        return view('steve-diagnostic.index', compact('results'));
    }
}

