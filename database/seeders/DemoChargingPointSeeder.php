<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ChargingPoint;
use App\Models\Partner;
use App\Models\Station;
use App\Models\PricingPlan;
use App\Models\Group;

class DemoChargingPointSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un partenaire de démonstration si inexistant
        $partner = Partner::firstOrCreate([
            'id' => 1
        ], [
            'name' => 'Partenaire Démo',
            'email' => 'demo@partner.com',
        ]);

        // Créer une station de démonstration si inexistante
        $station = Station::firstOrCreate([
            'id' => 1
        ], [
            'name' => 'Station Démo',
            'address' => '123 Rue de la Recharge',
        ]);

        // Créer un plan tarifaire de démonstration si inexistant
        $pricingPlan = PricingPlan::firstOrCreate([
            'id' => 1
        ], [
            'name' => 'Plan Démo',
            'rate_type' => 'mixed',
            'price_per_kwh' => 0.5,
            'price_per_minute' => 0.2,
            'activation_fee' => 1.0,
            'currency' => 'EUR',
            'is_active' => true,
        ]);

        // Get a group (required by model validation)
        $group = Group::first();
        if (!$group) {
            throw new \Exception('Aucun groupe trouvé. Veuillez exécuter DemoGroupSeeder avant DemoChargingPointSeeder.');
        }

        // Créer une borne de recharge de démonstration
        ChargingPoint::updateOrCreate([
            'id' => 7
        ], [
            'name' => 'Borne Démo',
            'serial_number' => 'DEMO-0001',
            'power_output' => 22,
            'partner_id' => $partner->id,
            'station_id' => $station->id,
            'pricing_plan_id' => $pricingPlan->id,
            'group_id' => $group->id, // Required: ChargingPoint must belong to a group
            'status' => 'online',
            'latitude' => 31.7917,
            'longitude' => -7.0926,
            'address' => '123 Rue de la Recharge',
            'city' => 'Marrakech',
            'country' => 'Maroc',
            // Ajoutez d'autres champs nécessaires selon votre migration
        ]);
    }
} 