<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Partner;
use App\Models\Integrator;

class ChargingPointTestController extends Controller
{
    public function createStep1Fixed()
    {
        $groups = Group::all();
        $partners = Partner::all();
        $integrators = Integrator::all();
        
        $operators = \App\Models\User::whereHas("roles", function($q) {
            $q->where("name", "operator");
        })->with(["partner", "partner.businessProfile"])->get();

        return view("charging-points.create-step1-fixed", compact("groups", "partners", "integrators", "operators"));
    }
}