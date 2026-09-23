<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\BusinessProfile;
use App\Services\TransactionCalculator;

class DebugIntegratorShare extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:integrator-share {id=1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Débogue la part intégrateur pour une transaction';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $transactionId = $this->argument('id');
        
        $this->info("🔍 Débogage de la part intégrateur pour la transaction ID {$transactionId}...");
        $this->newLine();

        try {
            // Récupérer la transaction
            $transaction = Transaction::with([
                'chargingPoint', 
                'chargingPoint.businessProfile',
                'chargingPoint.integrator',
                'chargingPoint.integrator.businessProfile',
                'chargingPoint.user'
            ])->find($transactionId);
            
            if (!$transaction) {
                $this->error("❌ Transaction ID {$transactionId} non trouvée");
                return 1;
            }
            
            $this->info("📋 Transaction:");
            $this->info("  - Montant: {$transaction->price_total} {$transaction->currency}");
            $this->info("  - Status: {$transaction->status}");
            
            $chargingPoint = $transaction->chargingPoint;
            if (!$chargingPoint) {
                $this->error("❌ Point de charge non trouvé");
                return 1;
            }
            
            $this->info("🔌 Point de charge:");
            $this->info("  - ID: {$chargingPoint->id}");
            $this->info("  - Nom: {$chargingPoint->name}");
            $this->info("  - Business Profile ID: {$chargingPoint->business_profile_id}");
            $this->info("  - Integrator ID: " . ($chargingPoint->integrator_id ?? 'null'));
            $this->info("  - User ID: " . ($chargingPoint->user_id ?? 'null'));
            
            // Vérifier le business profile du point de charge
            $bp = $chargingPoint->businessProfile;
            if ($bp) {
                $this->info("💼 Business Profile du point de charge:");
                $this->info("  - ID: {$bp->id}");
                $this->info("  - Nom: {$bp->name}");
                $this->info("  - Integrator Fee Percentage: " . ($bp->integrator_fee_percentage ?? 'null'));
                $this->info("  - Integrator Fee Fixed: " . ($bp->integrator_fee_fixed ?? 'null'));
            } else {
                $this->warn("⚠️ Pas de business profile pour le point de charge");
            }
            
            // Vérifier l'intégrateur associé
            if ($chargingPoint->integrator_id) {
                $integrator = $chargingPoint->integrator;
                if ($integrator) {
                    $this->info("👤 Intégrateur associé:");
                    $this->info("  - ID: {$integrator->id}");
                    $this->info("  - Nom: {$integrator->name}");
                    $this->info("  - Business Profile ID: " . ($integrator->business_profile_id ?? 'null'));
                    
                    if ($integrator->businessProfile) {
                        $integratorBp = $integrator->businessProfile;
                        $this->info("💼 Business Profile de l'intégrateur:");
                        $this->info("  - ID: {$integratorBp->id}");
                        $this->info("  - Nom: {$integratorBp->name}");
                        $this->info("  - Integrator Fee Percentage: " . ($integratorBp->integrator_fee_percentage ?? 'null'));
                        $this->info("  - Integrator Fee Fixed: " . ($integratorBp->integrator_fee_fixed ?? 'null'));
                    } else {
                        $this->warn("⚠️ Pas de business profile pour l'intégrateur");
                    }
                } else {
                    $this->warn("⚠️ Intégrateur non trouvé malgré l'ID {$chargingPoint->integrator_id}");
                }
            }
            
            // Vérifier l'utilisateur du point de charge
            if ($chargingPoint->user_id) {
                $user = $chargingPoint->user;
                if ($user) {
                    $this->info("👤 Utilisateur du point de charge:");
                    $this->info("  - ID: {$user->id}");
                    $this->info("  - Nom: {$user->name}");
                    $this->info("  - Rôles: " . $user->getRoleNames()->implode(', '));
                    
                    if ($user->hasRole('integrator')) {
                        $this->info("  - ✅ C'est un intégrateur");
                        if ($user->businessProfile) {
                            $userBp = $user->businessProfile;
                            $this->info("💼 Business Profile de l'utilisateur:");
                            $this->info("  - ID: {$userBp->id}");
                            $this->info("  - Nom: {$userBp->name}");
                            $this->info("  - Integrator Fee Percentage: " . ($userBp->integrator_fee_percentage ?? 'null'));
                            $this->info("  - Integrator Fee Fixed: " . ($userBp->integrator_fee_fixed ?? 'null'));
                        }
                    }
                }
            }
            
            $this->newLine();
            
            // Tester le calcul
            $this->info("🧮 Test du calcul de la part intégrateur...");
            $calculator = new TransactionCalculator();
            $result = $calculator->calculate($transaction);
            
            $this->table(
                ['Rôle', 'Montant', 'Pourcentage'],
                [
                    ['Admin', number_format($result['admin'], 2) . ' €', $this->getPercentage($result['admin'], $result['total'])],
                    ['Intégrateur', number_format($result['integrator'], 2) . ' €', $this->getPercentage($result['integrator'], $result['total'])],
                    ['Opérateur', number_format($result['operator'], 2) . ' €', $this->getPercentage($result['operator'], $result['total'])],
                    ['Total', number_format($result['total'], 2) . ' €', '100%'],
                ]
            );
            
            // Analyse de la part intégrateur
            $this->newLine();
            if ($result['integrator'] > 0) {
                $this->info("✅ Part intégrateur calculée: {$result['integrator']} €");
            } else {
                $this->warn("⚠️ Part intégrateur = 0 €");
                $this->info("💡 Causes possibles:");
                $this->info("  1. Pas d'intégrateur associé au point de charge");
                $this->info("  2. Business profile sans frais intégrateur configurés");
                $this->info("  3. Utilisateur du point de charge n'est pas un intégrateur");
                $this->newLine();
                $this->info("🔧 Solutions:");
                $this->info("  1. Associer un intégrateur au point de charge");
                $this->info("  2. Configurer des frais intégrateur dans le business profile");
                $this->info("  3. Exécuter: php artisan fix:transaction-repartition {$transactionId}");
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Calcule le pourcentage
     */
    protected function getPercentage(float $amount, float $total): string
    {
        if ($total == 0) {
            return '0%';
        }
        return number_format(($amount / $total) * 100, 1) . '%';
    }
}
