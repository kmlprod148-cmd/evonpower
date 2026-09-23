<?php

namespace App\Services;

use App\Models\User;
use App\Models\ChargingPoint;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\Partner;
use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class IntegratorIsolationService
{
    /**
     * Vérifier l'isolation complète d'un intégrateur
     */
    public function verifyIntegratorIsolation(User $integrator): array
    {
        if (!$integrator->hasRole('integrator') || !$integrator->integrator_id) {
            return [
                'is_isolated' => false,
                'errors' => ['L\'utilisateur n\'est pas un intégrateur valide']
            ];
        }

        $errors = [];
        $integratorId = $integrator->integrator_id;

        // 1. Vérifier l'isolation des utilisateurs
        $userErrors = $this->verifyUserIsolation($integrator, $integratorId);
        $errors = array_merge($errors, $userErrors);

        // 2. Vérifier l'isolation des points de charge
        $chargingPointErrors = $this->verifyChargingPointIsolation($integrator, $integratorId);
        $errors = array_merge($errors, $chargingPointErrors);

        // 3. Vérifier l'isolation des réservations
        $reservationErrors = $this->verifyReservationIsolation($integrator, $integratorId);
        $errors = array_merge($errors, $reservationErrors);

        // 4. Vérifier l'isolation des transactions
        $transactionErrors = $this->verifyTransactionIsolation($integrator, $integratorId);
        $errors = array_merge($errors, $transactionErrors);

        // 5. Vérifier l'isolation des partenaires
        $partnerErrors = $this->verifyPartnerIsolation($integrator, $integratorId);
        $errors = array_merge($errors, $partnerErrors);

        return [
            'is_isolated' => empty($errors),
            'errors' => $errors,
            'integrator_id' => $integratorId,
            'checked_at' => now()
        ];
    }

    /**
     * Vérifier l'isolation des utilisateurs
     */
    private function verifyUserIsolation(User $integrator, $integratorId): array
    {
        $errors = [];

        // Vérifier que l'intégrateur ne peut voir que ses opérateurs
        $unauthorizedUsers = User::where('integrator_id', '!=', $integratorId)
            ->whereHas('roles', function($query) {
                $query->where('name', 'operator');
            })
            ->where(function($query) use ($integrator) {
                // Simuler un accès non autorisé
                $query->where('id', '!=', $integrator->id);
            })
            ->count();

        if ($unauthorizedUsers > 0) {
            $errors[] = "L'intégrateur peut potentiellement accéder à {$unauthorizedUsers} opérateurs d'autres intégrateurs";
        }

        return $errors;
    }

    /**
     * Vérifier l'isolation des points de charge
     */
    private function verifyChargingPointIsolation(User $integrator, $integratorId): array
    {
        $errors = [];

        // Vérifier que l'intégrateur ne peut voir que ses points de charge
        $unauthorizedChargingPoints = ChargingPoint::where('integrator_id', '!=', $integratorId)
            ->whereHas('user', function($query) use ($integratorId) {
                $query->where('integrator_id', '!=', $integratorId);
            })
            ->count();

        if ($unauthorizedChargingPoints > 0) {
            $errors[] = "L'intégrateur peut potentiellement accéder à {$unauthorizedChargingPoints} points de charge d'autres intégrateurs";
        }

        return $errors;
    }

    /**
     * Vérifier l'isolation des réservations
     */
    private function verifyReservationIsolation(User $integrator, $integratorId): array
    {
        $errors = [];

        // Vérifier que l'intégrateur ne peut voir que ses réservations et celles de ses opérateurs
        $unauthorizedReservations = Reservation::whereHas('user', function($query) use ($integratorId) {
                $query->where('integrator_id', '!=', $integratorId)
                      ->where('id', '!=', $integrator->id);
            })
            ->count();

        if ($unauthorizedReservations > 0) {
            $errors[] = "L'intégrateur peut potentiellement accéder à {$unauthorizedReservations} réservations d'autres intégrateurs";
        }

        return $errors;
    }

    /**
     * Vérifier l'isolation des transactions
     */
    private function verifyTransactionIsolation(User $integrator, $integratorId): array
    {
        $errors = [];

        // Vérifier que l'intégrateur ne peut voir que ses transactions
        $unauthorizedTransactions = Transaction::whereHas('user', function($query) use ($integratorId) {
                $query->where('integrator_id', '!=', $integratorId)
                      ->where('id', '!=', $integrator->id);
            })
            ->whereHas('chargingPoint', function($query) use ($integratorId) {
                $query->where('integrator_id', '!=', $integratorId);
            })
            ->count();

        if ($unauthorizedTransactions > 0) {
            $errors[] = "L'intégrateur peut potentiellement accéder à {$unauthorizedTransactions} transactions d'autres intégrateurs";
        }

        return $errors;
    }

    /**
     * Vérifier l'isolation des partenaires
     */
    private function verifyPartnerIsolation(User $integrator, $integratorId): array
    {
        $errors = [];

        // Vérifier que l'intégrateur ne peut voir que ses partenaires
        $unauthorizedPartners = Partner::where('integrator_id', '!=', $integratorId)->count();

        if ($unauthorizedPartners > 0) {
            $errors[] = "L'intégrateur peut potentiellement accéder à {$unauthorizedPartners} partenaires d'autres intégrateurs";
        }

        return $errors;
    }

    /**
     * Forcer l'isolation d'un intégrateur (en cas de problème détecté)
     */
    public function enforceIntegratorIsolation(User $integrator): array
    {
        if (!$integrator->hasRole('integrator') || !$integrator->integrator_id) {
            return [
                'success' => false,
                'message' => 'L\'utilisateur n\'est pas un intégrateur valide'
            ];
        }

        try {
            DB::beginTransaction();

            $integratorId = $integrator->integrator_id;
            $actions = [];

            // 1. Révoquer tous les accès non autorisés aux utilisateurs
            $revokedUsers = User::where('integrator_id', '!=', $integratorId)
                ->whereHas('roles', function($query) {
                    $query->where('name', 'operator');
                })
                ->update(['integrator_id' => null]);
            
            if ($revokedUsers > 0) {
                $actions[] = "Révoqué l'accès à {$revokedUsers} opérateurs non autorisés";
            }

            // 2. Révoquer tous les accès non autorisés aux points de charge
            $revokedChargingPoints = ChargingPoint::where('integrator_id', '!=', $integratorId)
                ->whereHas('user', function($query) use ($integratorId) {
                    $query->where('integrator_id', '!=', $integratorId);
                })
                ->update(['integrator_id' => null]);
            
            if ($revokedChargingPoints > 0) {
                $actions[] = "Révoqué l'accès à {$revokedChargingPoints} points de charge non autorisés";
            }

            // 3. Révoquer tous les accès non autorisés aux partenaires
            $revokedPartners = Partner::where('integrator_id', '!=', $integratorId)
                ->update(['integrator_id' => null]);
            
            if ($revokedPartners > 0) {
                $actions[] = "Révoqué l'accès à {$revokedPartners} partenaires non autorisés";
            }

            DB::commit();

            // Log de l'audit
            Log::warning('Integrator isolation enforced', [
                'integrator_id' => $integratorId,
                'integrator_name' => $integrator->name,
                'actions' => $actions,
                'enforced_at' => now()
            ]);

            return [
                'success' => true,
                'message' => 'Isolation forcée avec succès',
                'actions' => $actions
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Error enforcing integrator isolation', [
                'integrator_id' => $integratorId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'application de l\'isolation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier l'isolation globale de tous les intégrateurs
     */
    public function verifyGlobalIsolation(): array
    {
        $integrators = User::whereHas('roles', function($query) {
            $query->where('name', 'integrator');
        })->get();

        $results = [];
        $totalErrors = 0;

        foreach ($integrators as $integrator) {
            $verification = $this->verifyIntegratorIsolation($integrator);
            $results[] = [
                'integrator_id' => $integrator->id,
                'integrator_name' => $integrator->name,
                'is_isolated' => $verification['is_isolated'],
                'errors' => $verification['errors']
            ];
            
            if (!$verification['is_isolated']) {
                $totalErrors += count($verification['errors']);
            }
        }

        return [
            'total_integrators' => $integrators->count(),
            'isolated_integrators' => collect($results)->where('is_isolated', true)->count(),
            'non_isolated_integrators' => collect($results)->where('is_isolated', false)->count(),
            'total_errors' => $totalErrors,
            'results' => $results,
            'checked_at' => now()
        ];
    }
}
