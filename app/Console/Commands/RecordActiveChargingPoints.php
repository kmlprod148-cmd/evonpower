<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ActiveChargingPointsService;

class RecordActiveChargingPoints extends Command
{
    protected $signature = 'charging-points:record';
    protected $description = 'Enregistre les données des bornes actives';

    protected $service;

    public function __construct(ActiveChargingPointsService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle()
    {
        // Exemple de données collectées (à remplacer par une logique réelle)
        $data = [
            'reference_date' => now()->toDateString(),
            'integrator_id' => 1,
            'partner_id' => 1,
            'group_id' => 1,
            'total_charging_points' => 100,
            'active_charging_points' => 80,
            'offline_charging_points' => 10,
            'maintenance_charging_points' => 10,
            'record_type' => 'daily',
            'snapshot_date' => now(),
        ];

        $this->service->recordActiveChargingPoints($data);

        $this->info('Données des bornes actives enregistrées avec succès.');
    }
}
