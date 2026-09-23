<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BusinessProfileFeesSetupService;
use App\Models\BusinessProfile;
use App\Models\ChargingPoint;

/**
 * Commande pour configurer les frais des business profiles
 */
class SetupBusinessProfileFeesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'business-profiles:setup-fees 
                            {--force : Forcer la configuration même si des frais existent}
                            {--create-missing : Créer des business profiles pour les points de charge sans profil}';

    /**
     * The console command description.
     */
    protected $description = 'Configure les frais par défaut pour tous les business profiles';

    protected $feesSetupService;

    public function __construct(BusinessProfileFeesSetupService $feesSetupService)
    {
        parent::__construct();
        $this->feesSetupService = $feesSetupService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Configuration des frais des business profiles...');

        try {
            $force = $this->option('force');
            $createMissing = $this->option('create-missing');

            // Vérifier les business profiles existants
            $businessProfiles = BusinessProfile::all();
            $this->info("📊 Business profiles trouvés: {$businessProfiles->count()}");

            if ($businessProfiles->count() > 0) {
                $this->info('⚙️ Configuration des frais pour les business profiles existants...');
                $updatedCount = $this->feesSetupService->setupDefaultFees();
                $this->info("✅ {$updatedCount} business profiles configurés");
            }

            // Créer des business profiles pour les points de charge sans profil
            if ($createMissing) {
                $chargingPointsWithoutProfile = ChargingPoint::whereNull('business_profile_id')->get();
                $this->info("🔌 Points de charge sans business profile: {$chargingPointsWithoutProfile->count()}");

                if ($chargingPointsWithoutProfile->count() > 0) {
                    $this->info('🏗️ Création des business profiles pour les points de charge...');
                    $createdCount = $this->feesSetupService->setupFeesForChargingPointsWithoutBusinessProfile();
                    $this->info("✅ {$createdCount} business profiles créés pour les points de charge");
                }
            }

            // Afficher un résumé des frais configurés
            $this->displayFeesSummary();

            $this->info('🎉 Configuration des frais terminée avec succès !');

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la configuration des frais: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * Affiche un résumé des frais configurés
     */
    private function displayFeesSummary()
    {
        $this->info('📋 Résumé des frais configurés:');
        
        $summary = $this->feesSetupService->getFeesSummary();
        
        if (empty($summary)) {
            $this->warn('⚠️ Aucun business profile trouvé');
            return;
        }

        $headers = [
            'ID',
            'Nom',
            'Admin Fixe',
            'Admin %',
            'Intégrateur Fixe',
            'Intégrateur %',
            'Partenaire Fixe',
            'Partenaire %',
            'Maintenance',
            'Terminal',
            'Base'
        ];

        $rows = [];
        foreach ($summary as $profile) {
            $rows[] = [
                $profile['id'],
                $profile['name'],
                $profile['admin_fee_fixed'] . '€',
                $profile['admin_fee_percentage'] . '%',
                $profile['integrator_fee_fixed'] . '€',
                $profile['integrator_fee_percentage'] . '%',
                $profile['partner_fee_fixed'] . '€',
                $profile['partner_fee_percentage'] . '%',
                $profile['maintenance_fee_amount'] . ($profile['maintenance_fee_type'] === 'percentage' ? '%' : '€'),
                $profile['terminal_fee_amount'] . '€',
                $profile['base_fee_amount'] . '€'
            ];
        }

        $this->table($headers, $rows);
    }
}
