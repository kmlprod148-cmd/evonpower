<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Models\PricingPlan;
use Illuminate\Support\Facades\Log;

class TestOperatorChargingPointCreation extends Command
{
    protected $signature = 'test:operator-charging-point-creation';
    protected $description = 'Test operator charging point creation process';

    public function handle()
    {
        $this->info('🧪 Test de création de point de charge pour les opérateurs...');

        // Trouver un opérateur
        $operator = User::role('operator')->first();
        
        if (!$operator) {
            $this->error('❌ Aucun opérateur trouvé');
            return 1;
        }

        $this->info("✅ Opérateur trouvé: {$operator->name} (ID: {$operator->id})");

        // Vérifier les permissions
        $canCreate = $operator->can('create_charging_points');
        $this->info("🔐 Peut créer des points de charge: " . ($canCreate ? '✅ Oui' : '❌ Non'));

        if (!$canCreate) {
            $this->error('❌ L\'opérateur n\'a pas la permission de créer des points de charge');
            return 1;
        }

        // Vérifier l'intégrateur
        $integrator = $operator->integrator;
        if (!$integrator) {
            $this->error('❌ L\'opérateur n\'a pas d\'intégrateur associé');
            return 1;
        }

        $this->info("✅ Intégrateur associé: {$integrator->name} (ID: {$integrator->id})");

        // Vérifier les business profiles
        $businessProfile = BusinessProfile::where('is_active', true)->first();
        if (!$businessProfile) {
            $this->error('❌ Aucun business profile actif trouvé');
            return 1;
        }

        $this->info("✅ Business profile trouvé: {$businessProfile->name} (ID: {$businessProfile->id})");

        // Vérifier les plans tarifaires
        $pricingPlan = PricingPlan::where('is_active', true)->first();
        if (!$pricingPlan) {
            $this->error('❌ Aucun plan tarifaire actif trouvé');
            return 1;
        }

        $this->info("✅ Plan tarifaire trouvé: {$pricingPlan->name} (ID: {$pricingPlan->id})");

        // Tester la création d'un point de charge
        try {
            $this->info('🔨 Test de création d\'un point de charge...');
            
            $chargingPointData = [
                'name' => 'Test Point - ' . now()->format('Y-m-d H:i:s'),
                'serial_number' => 'TEST-' . uniqid(),
                'manufacturer' => 'ABB',
                'model' => 'MOL',
                'location' => 'Test Location',
                'latitude' => 33.5731,
                'longitude' => -7.5898,
                'status' => 'online',
                'power_output' => 22,
                'user_id' => $operator->id,
                'integrator_id' => $integrator->id,
                'business_profile_id' => $businessProfile->id,
                'pricing_plan_id' => $pricingPlan->id,
                'partner_id' => $operator->partner_id ?? 1,
            ];

            $chargingPoint = ChargingPoint::create($chargingPointData);
            
            $this->info("✅ Point de charge créé avec succès !");
            $this->info("   - ID: {$chargingPoint->id}");
            $this->info("   - Nom: {$chargingPoint->name}");
            $this->info("   - Utilisateur: {$chargingPoint->user->name}");
            $this->info("   - Intégrateur: {$chargingPoint->integrator->name}");

            // Nettoyer le point de charge de test
            $chargingPoint->delete();
            $this->info("🧹 Point de charge de test supprimé");

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la création du point de charge:");
            $this->error("   {$e->getMessage()}");
            return 1;
        }

        $this->info('🎉 Test terminé avec succès !');
        return 0;
    }
}
