<?php

namespace App\Observers;

use App\Models\ChargingPoint;
use App\Models\User;
use App\Services\AutomaticBusinessProfileService;
use Illuminate\Support\Facades\Auth;

class ChargingPointObserver
{
    protected $automaticService;

    public function __construct(AutomaticBusinessProfileService $automaticService)
    {
        $this->automaticService = $automaticService;
    }

    /**
     * Handle the ChargingPoint "created" event.
     */
    public function created(ChargingPoint $chargingPoint): void
    {
        // Appliquer automatiquement le Business Profile du créateur
        $creator = Auth::user();
        if ($creator && $creator->hasRole('operator')) {
            $this->automaticService->applyDefaultBusinessProfileToChargingPoint($chargingPoint, $creator);
        }
    }
}