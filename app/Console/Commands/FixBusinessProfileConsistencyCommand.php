<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BusinessProfileConsistencyService;
use App\Models\BusinessProfile;

/**
 * Commande pour corriger les incohérences dans les business profiles
 */
class FixBusinessProfileConsistencyCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'business-profiles:fix-consistency 
                            {--validate-only : Valider sans corriger}
                            {--force : Forcer la correction même si les profils sont cohérents}
                            {--profile-id= : Corriger un profil spécifique}';

    /**
     * The console command description.
     */
    protected $description = 'Corrige les incohérences dans les business profiles';

    protected $consistencyService;

    public function __construct(BusinessProfileConsistencyService $consistencyService)
    {
        parent::__construct();
        $this->consistencyService = $consistencyService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Correction des incohérences dans les business profiles...');
        $this->info('============================================================');

        try {
            // Validation uniquement
            if ($this->option('validate-only')) {
                return $this->validateBusinessProfiles();
            }

            // Profil spécifique
            if ($profileId = $this->option('profile-id')) {
                return $this->fixSpecificProfile($profileId);
            }

            // Correction complète
            return $this->fixAllProfiles();

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la correction: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Valide tous les business profiles
     */
    private function validateBusinessProfiles(): int
    {
        $this->info('🔍 Validation des business profiles...');
        
        $results = $this->consistencyService->validateAllBusinessProfiles();
        
        $this->info("📊 Résultats de validation:");
        $this->info("   Total des profils: {$results['total']}");
        $this->info("   Profils cohérents: {$results['consistent']}");
        $this->info("   Profils incohérents: {$results['inconsistent']}");
        
        if ($results['inconsistent'] > 0) {
            $this->warn("⚠️  {$results['inconsistent']} profils incohérents détectés:");
            
            foreach ($results['inconsistent_details'] as $detail) {
                $this->warn("   Profil #{$detail['business_profile_id']} ({$detail['name']}):");
                $this->warn("     - Total pourcentages: {$detail['total_percentage']}%");
                $this->warn("     - Total frais fixes: {$detail['total_fixed_fees']}€");
                $this->warn("     - Problèmes:");
                foreach ($detail['issues'] as $issue) {
                    $this->warn("       * {$issue}");
                }
            }
            
            $this->info("\n💡 Utilisez --force pour corriger ces incohérences");
        } else {
            $this->info('✅ Tous les profils sont cohérents!');
        }

        return 0;
    }

    /**
     * Corrige un profil spécifique
     */
    private function fixSpecificProfile(int $profileId): int
    {
        $this->info("🔧 Correction du profil #{$profileId}...");
        
        try {
            $businessProfile = BusinessProfile::find($profileId);
            
            if (!$businessProfile) {
                $this->error("❌ Profil #{$profileId} non trouvé");
                return 1;
            }

            // Valider avant correction
            $validation = $this->consistencyService->validateBusinessProfileConsistency($businessProfile);
            
            if ($validation['is_consistent'] && !$this->option('force')) {
                $this->info("✅ Profil #{$profileId} déjà cohérent");
                return 0;
            }

            if (!$validation['is_consistent']) {
                $this->warn("⚠️  Problèmes détectés:");
                foreach ($validation['issues'] as $issue) {
                    $this->warn("   - {$issue}");
                }
            }

            $this->consistencyService->fixBusinessProfileInconsistencies($businessProfile);
            
            // Valider après correction
            $finalValidation = $this->consistencyService->validateBusinessProfileConsistency($businessProfile);
            
            if ($finalValidation['is_consistent']) {
                $this->info("✅ Profil #{$profileId} corrigé avec succès");
                $this->displayProfileDetails($businessProfile);
            } else {
                $this->error("❌ Profil #{$profileId} toujours incohérent après correction");
                $this->warn("Problèmes restants:");
                foreach ($finalValidation['issues'] as $issue) {
                    $this->warn("   - {$issue}");
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la correction du profil #{$profileId}: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Corrige tous les profils
     */
    private function fixAllProfiles(): int
    {
        $this->info('🔧 Correction de tous les business profiles...');
        
        // D'abord valider
        $validationResults = $this->consistencyService->validateAllBusinessProfiles();
        
        if ($validationResults['inconsistent'] == 0 && !$this->option('force')) {
            $this->info('✅ Tous les profils sont déjà cohérents!');
            return 0;
        }

        if ($validationResults['inconsistent'] > 0) {
            $this->warn("⚠️  {$validationResults['inconsistent']} profils incohérents détectés");
            
            if (!$this->confirm('Voulez-vous corriger ces incohérences?')) {
                $this->info('❌ Correction annulée par l\'utilisateur');
                return 0;
            }
        }

        // Afficher une barre de progression
        $totalProfiles = BusinessProfile::count();
        $progressBar = $this->output->createProgressBar($totalProfiles);
        $progressBar->start();

        $results = [
            'total_processed' => 0,
            'fixed' => 0,
            'errors' => 0,
            'details' => []
        ];

        try {
            $businessProfiles = BusinessProfile::all();
            
            foreach ($businessProfiles as $businessProfile) {
                $results['total_processed']++;
                
                try {
                    $this->consistencyService->fixBusinessProfileInconsistencies($businessProfile);
                    $results['fixed']++;
                    $results['details'][] = [
                        'business_profile_id' => $businessProfile->id,
                        'name' => $businessProfile->name,
                        'status' => 'fixed'
                    ];
                    
                } catch (\Exception $e) {
                    $results['errors']++;
                    $results['details'][] = [
                        'business_profile_id' => $businessProfile->id,
                        'name' => $businessProfile->name,
                        'status' => 'error',
                        'error' => $e->getMessage()
                    ];
                }
                
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            // Afficher les résultats
            $this->displayResults($results);

            return 0;

        } catch (\Exception $e) {
            $progressBar->finish();
            $this->newLine();
            $this->error('❌ Erreur lors de la correction: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Affiche les détails d'un profil
     */
    private function displayProfileDetails(BusinessProfile $businessProfile): void
    {
        $this->info("📊 Détails du profil:");
        $this->info("   - Nom: {$businessProfile->name}");
        $this->info("   - Admin fixe: {$businessProfile->admin_fee_fixed}€");
        $this->info("   - Admin %: {$businessProfile->admin_fee_percentage}%");
        $this->info("   - Intégrateur fixe: {$businessProfile->integrator_fee_fixed}€");
        $this->info("   - Intégrateur %: {$businessProfile->integrator_fee_percentage}%");
        $this->info("   - Partenaire fixe: {$businessProfile->partner_fee_fixed}€");
        $this->info("   - Partenaire %: {$businessProfile->partner_fee_percentage}%");
        $this->info("   - Terminal: {$businessProfile->terminal_fee_amount}€");
        $this->info("   - Base: {$businessProfile->base_fee_amount}€");
        
        $totalPercentage = $businessProfile->admin_fee_percentage + 
                          $businessProfile->integrator_fee_percentage + 
                          $businessProfile->partner_fee_percentage;
        $this->info("   - Total %: {$totalPercentage}%");
    }

    /**
     * Affiche les résultats de la correction
     */
    private function displayResults(array $results): void
    {
        $this->info("\n📊 Résultats de la correction:");
        $this->info("   Total traité: {$results['total_processed']}");
        $this->info("   Corrigés: {$results['fixed']}");
        $this->info("   Erreurs: {$results['errors']}");

        if ($results['errors'] > 0) {
            $this->warn("\n⚠️  Détails des erreurs:");
            foreach ($results['details'] as $detail) {
                if ($detail['status'] === 'error') {
                    $this->warn("   Profil #{$detail['business_profile_id']} ({$detail['name']}): {$detail['error']}");
                }
            }
        }

        if ($results['fixed'] > 0) {
            $this->info("\n✅ {$results['fixed']} profils corrigés avec succès!");
        }

        // Validation finale
        $this->info("\n🔍 Validation finale...");
        $finalValidation = $this->consistencyService->validateAllBusinessProfiles();
        
        $this->info("📊 État final:");
        $this->info("   Profils cohérents: {$finalValidation['consistent']}");
        $this->info("   Profils incohérents: {$finalValidation['inconsistent']}");

        if ($finalValidation['inconsistent'] == 0) {
            $this->info("\n🎉 Tous les profils sont maintenant cohérents!");
        } else {
            $this->warn("\n⚠️  {$finalValidation['inconsistent']} profils restent incohérents");
        }
    }
}
