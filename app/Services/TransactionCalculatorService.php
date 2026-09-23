<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\ChargingPoint;
use App\Models\Group;
use App\Models\User;
use App\Models\Integrator;
use App\Models\BusinessProfile;
use App\Models\TransactionRepartition;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service central Laravel pour le calcul hiérarchique des transactions
 * 
 * Récupère la hiérarchie depuis ChargingPoint et applique deux niveaux de Business Profiles:
 * - Integrator → Operator debit: Le paiement total de l'opérateur est réduit selon le Business Profile de l'intégrateur
 * - Admin → Integrator debit: Les frais de l'admin sont déduits de la part de l'intégrateur
 * 
 * Calcule les gains hors taxes (HT) et met à jour tous les soldes de wallet atomiquement.
 * Sauvegarde les enregistrements TransactionRepartition pour la traçabilité.
 */
class TransactionCalculatorService
{
    /**
     * Process a transaction with hierarchical fee calculation
     * 
     * @param Transaction $transaction
     * @return array
     * @throws Exception
     */
    public function process(Transaction $transaction): array
    {
        try {
            DB::beginTransaction();

            // Step 1: Retrieve hierarchy from ChargingPoint
            $hierarchy = $this->retrieveHierarchy($transaction->chargingPoint);
            
            if (!$hierarchy) {
                throw new Exception('Unable to retrieve hierarchy from ChargingPoint');
            }

            // Step 2: Retrieve business profiles with validation
            $businessProfiles = $this->getBusinessProfiles($hierarchy);
            
            // Validate that at least one business profile is available for calculation
            $this->validateBusinessProfiles($businessProfiles, $hierarchy);

            // Step 3: Calculate fees and shares
            $calculation = $this->calculateFeesAndShares($transaction, $businessProfiles);

            // Step 4: Update wallet balances atomically
            $this->updateWalletBalances($hierarchy, $calculation, $transaction);

            // Step 5: Save transaction repartition record
            $repartition = $this->saveTransactionRepartition($transaction, $calculation);

            DB::commit();

            Log::info('Transaction processed successfully with hierarchical calculation', [
                'transaction_id' => $transaction->id,
                'amount_ht' => $transaction->amount_ht,
                'operator_share' => $calculation['operator_share'],
                'integrator_share' => $calculation['integrator_share'],
                'admin_share' => $calculation['admin_share'],
                'repartition_id' => $repartition->id
            ]);

            return [
                'success' => true,
                'transaction' => $transaction,
                'calculation' => $calculation,
                'repartition' => $repartition,
                'hierarchy' => $hierarchy
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Transaction processing failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Récupère la hiérarchie depuis ChargingPoint
     * 
     * Hiérarchie attendue: Operator → Integrator → Admin
     * 
     * @param ChargingPoint $chargingPoint
     * @return array|null
     */
    protected function retrieveHierarchy(ChargingPoint $chargingPoint): ?array
    {
        // Charger les relations nécessaires
        $chargingPoint->load([
            'group.user', 
            'group.partner.integrator',
            'integrator',
            'partner.integrator'
        ]);

        $group = $chargingPoint->group;
        if (!$group) {
            Log::warning('Aucun groupe trouvé pour le point de charge', [
                'charging_point_id' => $chargingPoint->id
            ]);
            return null;
        }

        // Récupérer l'opérateur (peut venir de group->user ou group->operator)
        $operator = $group->user ?? null;
        
        // Si l'opérateur n'est pas trouvé directement, chercher via le partner
        if (!$operator && $group->partner_id) {
            // L'opérateur peut être lié via le partner
            $operator = User::where('partner_id', $group->partner_id)
                ->whereHas('roles', function($q) {
                    $q->where('name', 'operator');
                })
                ->first();
        }

        if (!$operator) {
            Log::warning('Aucun opérateur trouvé pour le groupe', [
                'group_id' => $group->id,
                'partner_id' => $group->partner_id
            ]);
            return null;
        }

        // Récupérer l'intégrateur
        $integrator = null;
        
        // 1. Depuis l'opérateur directement
        if ($operator->integrator_id) {
            $integrator = Integrator::find($operator->integrator_id);
        }
        
        // 2. Depuis le ChargingPoint
        if (!$integrator && $chargingPoint->integrator_id) {
            $integrator = $chargingPoint->integrator;
        }
        
        // 3. Depuis le Partner du groupe
        if (!$integrator && $group->partner && $group->partner->integrator_id) {
            $integrator = $group->partner->integrator;
        }

        // Récupérer l'admin depuis l'intégrateur
        $admin = null;
        if ($integrator && $integrator->created_by) {
            $admin = User::find($integrator->created_by);
            // Vérifier que c'est bien un admin
            if ($admin && !$admin->hasRole(['admin', 'super_admin'])) {
                $admin = null;
            }
        }

        return [
            'charging_point' => $chargingPoint,
            'group' => $group,
            'operator' => $operator,
            'integrator' => $integrator,
            'admin' => $admin
        ];
    }

    /**
     * Récupère les Business Profiles pour tous les niveaux de la hiérarchie
     * 
     * Chaque niveau peut avoir son propre Business Profile qui définit:
     * - Des frais fixes ou en pourcentage
     * - Des parts de revenus (revenue shares)
     * 
     * Hiérarchie: Admin → Integrator → Operator/Partner
     * 
     * Business Profiles récupérés:
     * - operator_profile: Business Profile de l'opérateur (si défini directement)
     * - partner_profile: Business Profile du partenaire (si applicable)
     * - integrator_profile: Business Profile de l'intégrateur (s'applique à l'opérateur)
     * - admin_profile: Business Profile de l'admin (s'applique à l'intégrateur)
     * 
     * @param array $hierarchy
     * @return array
     */
    protected function getBusinessProfiles(array $hierarchy): array
    {
        $operatorProfile = null;
        $partnerProfile = null;
        $integratorProfile = null;
        $adminProfile = null;

        // Business Profile de l'opérateur (direct ou via User)
        if ($hierarchy['operator']) {
            $operator = $hierarchy['operator'];
            $operator->load(['directBusinessProfile', 'integrator.businessProfile']);
            
            // Priorité 1: Business Profile direct de l'utilisateur
            if ($operator->business_profile_id) {
                $operatorProfile = $operator->directBusinessProfile;
            }
            
            // Priorité 2: Si pas de BP direct, chercher via l'intégrateur
            if (!$operatorProfile && $operator->integrator_id) {
                $integrator = $operator->integrator;
                if ($integrator && $integrator->businessProfile) {
                    // Pour l'opérateur, on utilise le BP de l'intégrateur comme référence
                    // mais on ne le stocke pas dans operatorProfile car c'est pour le calcul intégrateur
                }
            }
        }

        // Business Profile du partenaire (si applicable)
        if ($hierarchy['partner']) {
            $partner = $hierarchy['partner'];
            $partner->load('businessProfile');
            
            if ($partner->businessProfile) {
                $partnerProfile = $partner->businessProfile;
            }
        }

        // Business Profile de l'intégrateur (s'applique à l'opérateur)
        // L'intégrateur peut avoir un Business Profile directement ou via le ChargingPoint
        if ($hierarchy['integrator']) {
            $integrator = $hierarchy['integrator'];
            $integrator->load('businessProfile');
            
            if ($integrator->businessProfile) {
                $integratorProfile = $integrator->businessProfile;
            } else {
                // Chercher via le ChargingPoint
                $chargingPoint = $hierarchy['charging_point'];
                if ($chargingPoint && $chargingPoint->business_profile_id) {
                    $integratorProfile = BusinessProfile::find($chargingPoint->business_profile_id);
                }
            }
        }

        // Business Profile Admin (s'applique à l'intégrateur)
        // L'admin peut avoir un Business Profile configuré directement ou via l'intégrateur
        if ($hierarchy['admin']) {
            $admin = $hierarchy['admin'];
            $admin->load('directBusinessProfile');
            
            // Priorité 1: Business Profile direct de l'admin
            if ($admin->business_profile_id) {
                $adminProfile = $admin->directBusinessProfile;
            }
            
            // Priorité 2: Chercher un Business Profile configuré pour Admin→Integrator
            if (!$adminProfile && $hierarchy['integrator']) {
                $adminProfile = BusinessProfile::where('integrator_id', $hierarchy['integrator']->id ?? null)
                    ->where('is_active', true)
                    ->where(function($q) {
                        $q->whereNotNull('admin_fee_percentage')
                          ->orWhereNotNull('admin_fee_fixed')
                          ->orWhereNotNull('owner_commission');
                    })
                    ->first();
            }
            
            // Priorité 3: Business Profile global de l'admin via la méthode businessProfile() du User
            // Note: Cette méthode retourne un objet mock, on doit extraire le BP réel
            if (!$adminProfile) {
                try {
                    // Essayer d'accéder au BP via la méthode du User (qui peut être null)
                    $adminBP = $admin->directBusinessProfile;
                    if ($adminBP) {
                        $adminProfile = $adminBP;
                    }
                } catch (\Exception $e) {
                    // Ignorer si la méthode n'est pas disponible
                    Log::debug('Impossible de récupérer le BP de l\'admin via businessProfile()', [
                        'admin_id' => $admin->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        $profiles = [
            'operator_profile' => $operatorProfile,
            'partner_profile' => $partnerProfile,
            'integrator_profile' => $integratorProfile,
            'admin_profile' => $adminProfile
        ];

        // Log the retrieved profiles for traceability
        $this->logBusinessProfilesRetrieval($profiles, $hierarchy);

        return $profiles;
    }

    /**
     * Valide que les Business Profiles récupérés sont cohérents et applicables
     * 
     * @param array $businessProfiles
     * @param array $hierarchy
     * @throws Exception
     */
    protected function validateBusinessProfiles(array $businessProfiles, array $hierarchy): void
    {
        $errors = [];

        // Vérifier qu'au moins un Business Profile est disponible pour le calcul
        $hasAnyProfile = false;
        foreach ($businessProfiles as $profile) {
            if ($profile !== null) {
                $hasAnyProfile = true;
                break;
            }
        }

        if (!$hasAnyProfile) {
            $errors[] = 'Aucun Business Profile disponible pour le calcul des frais';
        }

        // Valider que les Business Profiles sont actifs
        foreach ($businessProfiles as $profileType => $profile) {
            if ($profile && !$profile->is_active) {
                Log::warning("Business Profile inactif détecté lors du calcul", [
                    'profile_type' => $profileType,
                    'profile_id' => $profile->id,
                    'profile_name' => $profile->name
                ]);
            }
        }

        // Vérifier la cohérence de la hiérarchie
        if ($hierarchy['operator'] && !$businessProfiles['integrator_profile'] && !$businessProfiles['operator_profile']) {
            Log::warning('Opérateur sans Business Profile (intégrateur ou direct)', [
                'operator_id' => $hierarchy['operator']->id,
                'operator_name' => $hierarchy['operator']->name
            ]);
        }

        if ($hierarchy['integrator'] && !$businessProfiles['integrator_profile']) {
            Log::warning('Intégrateur sans Business Profile', [
                'integrator_id' => $hierarchy['integrator']->id,
                'integrator_name' => $hierarchy['integrator']->name ?? 'N/A'
            ]);
        }

        // Lancer une exception si erreur critique
        if (!empty($errors)) {
            throw new Exception('Erreurs de validation des Business Profiles: ' . implode(', ', $errors));
        }
    }

    /**
     * Enregistre les Business Profiles récupérés pour traçabilité
     * 
     * @param array $profiles
     * @param array $hierarchy
     */
    protected function logBusinessProfilesRetrieval(array $profiles, array $hierarchy): void
    {
        $profileIds = [];
        $profileNames = [];

        foreach ($profiles as $type => $profile) {
            if ($profile) {
                $profileIds[$type] = $profile->id;
                $profileNames[$type] = $profile->name;
            }
        }

        Log::info('Business Profiles récupérés pour le calcul', [
            'hierarchy' => [
                'operator_id' => $hierarchy['operator']->id ?? null,
                'integrator_id' => $hierarchy['integrator']->id ?? null,
                'admin_id' => $hierarchy['admin']->id ?? null,
                'partner_id' => $hierarchy['partner']->id ?? null,
            ],
            'business_profiles' => $profileIds,
            'business_profile_names' => $profileNames
        ]);
    }

    /**
     * Calcule les frais et parts basés sur les Business Profiles
     * 
     * Logique:
     * 1. Le montant total HT payé par l'opérateur
     * 2. Frais intégrateur déduits du total HT (Integrator → Operator debit)
     * 3. Frais admin déduits de la part intégrateur (Admin → Integrator debit)
     * 
     * @param Transaction $transaction
     * @param array $businessProfiles
     * @return array
     */
    protected function calculateFeesAndShares(Transaction $transaction, array $businessProfiles): array
    {
        // Récupérer le montant HT (hors taxes)
        // Priorité: amount_ht > (price_total - price_tax) > amount
        $totalPaidHT = $transaction->amount_ht ?? 0;
        
        if ($totalPaidHT <= 0 && $transaction->price_total) {
            $taxAmount = $transaction->price_tax ?? 0;
            $totalPaidHT = $transaction->price_total - $taxAmount;
        }
        
        if ($totalPaidHT <= 0) {
            $totalPaidHT = $transaction->amount ?? 0;
        }

        if ($totalPaidHT <= 0) {
            Log::warning('Montant HT invalide pour la transaction', [
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
                'price_total' => $transaction->price_total,
                'price_tax' => $transaction->price_tax,
                'amount_ht' => $transaction->amount_ht
            ]);
            throw new Exception('Montant HT invalide pour la transaction');
        }

        // Log des Business Profiles utilisés pour le calcul
        Log::debug('Application des Business Profiles pour le calcul', [
            'transaction_id' => $transaction->id,
            'total_paid_ht' => $totalPaidHT,
            'integrator_profile_id' => $businessProfiles['integrator_profile']->id ?? null,
            'admin_profile_id' => $businessProfiles['admin_profile']->id ?? null,
            'operator_profile_id' => $businessProfiles['operator_profile']->id ?? null,
            'partner_profile_id' => $businessProfiles['partner_profile']->id ?? null,
        ]);

        // Étape 1: Calculer les frais intégrateur (appliqués à l'opérateur)
        // Ces frais réduisent le paiement total de l'opérateur
        $integratorFee = $this->calculateIntegratorFee($totalPaidHT, $businessProfiles['integrator_profile']);
        
        // Validation: les frais intégrateur ne doivent pas dépasser le montant total
        if ($integratorFee > $totalPaidHT) {
            Log::warning('Frais intégrateur supérieur au montant total HT', [
                'transaction_id' => $transaction->id,
                'total_paid_ht' => $totalPaidHT,
                'integrator_fee' => $integratorFee,
                'integrator_profile_id' => $businessProfiles['integrator_profile']->id ?? null
            ]);
            $integratorFee = $totalPaidHT; // Limiter au montant total
        }

        // Étape 2: Calculer les frais admin (appliqués à l'intégrateur)
        // Ces frais sont déduits de la part de l'intégrateur
        $adminFee = $this->calculateAdminFee($integratorFee, $businessProfiles['admin_profile']);
        
        // Validation: les frais admin ne doivent pas dépasser les frais intégrateur
        if ($adminFee > $integratorFee) {
            Log::warning('Frais admin supérieur aux frais intégrateur', [
                'transaction_id' => $transaction->id,
                'integrator_fee' => $integratorFee,
                'admin_fee' => $adminFee,
                'admin_profile_id' => $businessProfiles['admin_profile']->id ?? null
            ]);
            $adminFee = $integratorFee; // Limiter aux frais intégrateur
        }

        // Étape 3: Calculer les parts nettes
        // Part opérateur = total HT - frais intégrateur
        $operatorShare = $totalPaidHT - $integratorFee;
        
        // Part intégrateur = frais intégrateur - frais admin
        $integratorShare = $integratorFee - $adminFee;
        
        // Part admin = frais admin
        $adminShare = $adminFee;

        // Vérifier la cohérence (arrondi à 2 décimales)
        $operatorShare = round(max(0, $operatorShare), 2);
        $integratorShare = round(max(0, $integratorShare), 2);
        $adminShare = round(max(0, $adminShare), 2);
        $integratorFee = round($integratorFee, 2);
        $adminFee = round($adminFee, 2);

        // Validation: operator_share + integrator_share + admin_share doit égaler total_paid_ht
        $totalDistributed = $operatorShare + $integratorShare + $adminShare;
        $difference = abs($totalPaidHT - $totalDistributed);
        
        if ($difference > 0.01) {
            Log::warning('Différence de répartition détectée', [
                'transaction_id' => $transaction->id,
                'total_paid_ht' => $totalPaidHT,
                'total_distributed' => $totalDistributed,
                'difference' => $difference
            ]);
            // Ajuster la part opérateur pour corriger la différence d'arrondi
            $operatorShare = round($operatorShare + ($totalPaidHT - $totalDistributed), 2);
        }

        return [
            'total_paid_ht' => $totalPaidHT,
            'integrator_fee' => $integratorFee,
            'admin_fee' => $adminFee,
            'operator_share' => $operatorShare,
            'integrator_share' => $integratorShare,
            'admin_share' => $adminShare
        ];
    }

    /**
     * Calcule les frais intégrateur basés sur le Business Profile
     * 
     * Ces frais sont déduits du paiement total de l'opérateur
     * 
     * @param float $totalPaidHT
     * @param BusinessProfile|null $profile
     * @return float
     */
    protected function calculateIntegratorFee(float $totalPaidHT, ?BusinessProfile $profile): float
    {
        if (!$profile) {
            Log::debug('Aucun Business Profile intégrateur disponible, frais = 0');
            return 0.0;
        }

        // Valider que le profil est actif
        if (!$profile->is_active) {
            Log::warning('Business Profile intégrateur inactif utilisé pour le calcul', [
                'profile_id' => $profile->id,
                'profile_name' => $profile->name
            ]);
        }

        $fee = 0.0;
        $calculationDetails = [];

        // Frais fixe intégrateur
        if ($profile->integrator_fee_fixed && $profile->integrator_fee_fixed > 0) {
            $fixedFee = (float) $profile->integrator_fee_fixed;
            $fee += $fixedFee;
            $calculationDetails['integrator_fee_fixed'] = $fixedFee;
        }

        // Frais pourcentage intégrateur
        if ($profile->integrator_fee_percentage && $profile->integrator_fee_percentage > 0) {
            $percentageFee = $totalPaidHT * ((float) $profile->integrator_fee_percentage / 100);
            $fee += $percentageFee;
            $calculationDetails['integrator_fee_percentage'] = [
                'percentage' => (float) $profile->integrator_fee_percentage,
                'amount' => $percentageFee
            ];
        }

        // Commission intégrateur (pourcentage)
        if ($profile->integrator_commission && $profile->integrator_commission > 0) {
            $commissionFee = $totalPaidHT * ((float) $profile->integrator_commission / 100);
            $fee += $commissionFee;
            $calculationDetails['integrator_commission'] = [
                'percentage' => (float) $profile->integrator_commission,
                'amount' => $commissionFee
            ];
        }

        // Configuration transaction_fee_config
        if ($profile->transaction_fee_config) {
            $config = is_string($profile->transaction_fee_config) 
                ? json_decode($profile->transaction_fee_config, true) 
                : $profile->transaction_fee_config;
            
            if (is_array($config)) {
                // Configuration intégrateur
                if (isset($config['integrator']['percentage']) && $config['integrator']['percentage'] > 0) {
                    $configPercentageFee = $totalPaidHT * ((float) $config['integrator']['percentage'] / 100);
                    $fee += $configPercentageFee;
                    $calculationDetails['transaction_fee_config_integrator_percentage'] = $configPercentageFee;
                }
                if (isset($config['integrator']['fixed']) && $config['integrator']['fixed'] > 0) {
                    $configFixedFee = (float) $config['integrator']['fixed'];
                    $fee += $configFixedFee;
                    $calculationDetails['transaction_fee_config_integrator_fixed'] = $configFixedFee;
                }
                // Support pour l'ancien format
                if (isset($config['percentage']) && $config['percentage'] > 0) {
                    $legacyPercentageFee = $totalPaidHT * ((float) $config['percentage'] / 100);
                    $fee += $legacyPercentageFee;
                    $calculationDetails['transaction_fee_config_legacy_percentage'] = $legacyPercentageFee;
                }
            }
        }

        $finalFee = round($fee, 2);

        // Log détaillé du calcul
        Log::debug('Calcul des frais intégrateur', [
            'profile_id' => $profile->id,
            'total_paid_ht' => $totalPaidHT,
            'calculation_details' => $calculationDetails,
            'final_fee' => $finalFee
        ]);

        return $finalFee;
    }

    /**
     * Calcule les frais admin basés sur le Business Profile
     * 
     * Ces frais sont déduits de la part de l'intégrateur
     * IMPORTANT: Les frais admin sont calculés sur les frais intégrateur, pas sur le montant total
     * 
     * @param float $integratorFee Montant des frais intégrateur (base de calcul pour les frais admin)
     * @param BusinessProfile|null $profile Business Profile de l'admin
     * @return float
     */
    protected function calculateAdminFee(float $integratorFee, ?BusinessProfile $profile): float
    {
        if (!$profile) {
            Log::debug('Aucun Business Profile admin disponible, frais = 0', [
                'integrator_fee' => $integratorFee
            ]);
            return 0.0;
        }

        // Valider que le profil est actif
        if (!$profile->is_active) {
            Log::warning('Business Profile admin inactif utilisé pour le calcul', [
                'profile_id' => $profile->id,
                'profile_name' => $profile->name
            ]);
        }

        $fee = 0.0;
        $calculationDetails = [];

        // Frais fixe admin
        if ($profile->admin_fee_fixed && $profile->admin_fee_fixed > 0) {
            $fixedFee = (float) $profile->admin_fee_fixed;
            $fee += $fixedFee;
            $calculationDetails['admin_fee_fixed'] = $fixedFee;
        }

        // Frais pourcentage admin (calculé sur les frais intégrateur)
        if ($profile->admin_fee_percentage && $profile->admin_fee_percentage > 0) {
            $percentageFee = $integratorFee * ((float) $profile->admin_fee_percentage / 100);
            $fee += $percentageFee;
            $calculationDetails['admin_fee_percentage'] = [
                'percentage' => (float) $profile->admin_fee_percentage,
                'base_amount' => $integratorFee,
                'amount' => $percentageFee
            ];
        }

        // Commission propriétaire (pourcentage calculé sur les frais intégrateur)
        if ($profile->owner_commission && $profile->owner_commission > 0) {
            $commissionFee = $integratorFee * ((float) $profile->owner_commission / 100);
            $fee += $commissionFee;
            $calculationDetails['owner_commission'] = [
                'percentage' => (float) $profile->owner_commission,
                'base_amount' => $integratorFee,
                'amount' => $commissionFee
            ];
        }

        // Configuration charge_fee_config
        if ($profile->charge_fee_config) {
            $config = is_string($profile->charge_fee_config) 
                ? json_decode($profile->charge_fee_config, true) 
                : $profile->charge_fee_config;
            
            if (is_array($config)) {
                // Configuration admin
                if (isset($config['admin']['percentage']) && $config['admin']['percentage'] > 0) {
                    $configPercentageFee = $integratorFee * ((float) $config['admin']['percentage'] / 100);
                    $fee += $configPercentageFee;
                    $calculationDetails['charge_fee_config_admin_percentage'] = [
                        'percentage' => (float) $config['admin']['percentage'],
                        'base_amount' => $integratorFee,
                        'amount' => $configPercentageFee
                    ];
                }
                if (isset($config['admin']['fixed']) && $config['admin']['fixed'] > 0) {
                    $configFixedFee = (float) $config['admin']['fixed'];
                    $fee += $configFixedFee;
                    $calculationDetails['charge_fee_config_admin_fixed'] = $configFixedFee;
                }
                // Support pour l'ancien format
                if (isset($config['percentage']) && $config['percentage'] > 0) {
                    $legacyPercentageFee = $integratorFee * ((float) $config['percentage'] / 100);
                    $fee += $legacyPercentageFee;
                    $calculationDetails['charge_fee_config_legacy_percentage'] = [
                        'percentage' => (float) $config['percentage'],
                        'base_amount' => $integratorFee,
                        'amount' => $legacyPercentageFee
                    ];
                }
            }
        }

        $finalFee = round($fee, 2);

        // Validation: les frais admin ne doivent pas dépasser les frais intégrateur
        if ($finalFee > $integratorFee) {
            Log::warning('Frais admin calculés supérieurs aux frais intégrateur (sera limité)', [
                'profile_id' => $profile->id,
                'integrator_fee' => $integratorFee,
                'calculated_admin_fee' => $finalFee,
                'calculation_details' => $calculationDetails
            ]);
            $finalFee = $integratorFee; // Limiter aux frais intégrateur
        }

        // Log détaillé du calcul
        Log::debug('Calcul des frais admin', [
            'profile_id' => $profile->id,
            'integrator_fee' => $integratorFee,
            'calculation_details' => $calculationDetails,
            'final_fee' => $finalFee
        ]);

        return $finalFee;
    }

    /**
     * Met à jour les soldes des wallets atomiquement
     * 
     * Utilise les wallets au lieu des balances directes pour garantir l'intégrité
     * 
     * @param array $hierarchy
     * @param array $calculation
     * @param Transaction $transaction
     * @return void
     */
    protected function updateWalletBalances(array $hierarchy, array $calculation, Transaction $transaction): void
    {
        $operator = $hierarchy['operator'];
        $integrator = $hierarchy['integrator'];
        $admin = $hierarchy['admin'];

        // Opérateur: débiter le montant total HT
        $operatorWallet = WalletService::getOrCreateWallet($operator);
        $operatorWallet->debit(
            $calculation['total_paid_ht'],
            "Transaction #{$transaction->id} - Paiement opérateur",
            [
                'transaction_id' => $transaction->id,
                'type' => 'operator_payment',
                'amount_ht' => $calculation['total_paid_ht']
            ]
        );

        // Intégrateur: créditer sa part (après déduction admin)
        if ($integrator && $calculation['integrator_share'] > 0) {
            $integratorWallet = WalletService::getOrCreateWallet($integrator);
            $integratorWallet->credit(
                $calculation['integrator_share'],
                "Transaction #{$transaction->id} - Part intégrateur",
                [
                    'transaction_id' => $transaction->id,
                    'type' => 'integrator_share',
                    'integrator_fee' => $calculation['integrator_fee'],
                    'admin_fee' => $calculation['admin_fee']
                ]
            );
        }

        // Admin: créditer sa part
        if ($admin && $calculation['admin_share'] > 0) {
            $adminWallet = WalletService::getOrCreateWallet($admin);
            $adminWallet->credit(
                $calculation['admin_share'],
                "Transaction #{$transaction->id} - Part admin",
                [
                    'transaction_id' => $transaction->id,
                    'type' => 'admin_share',
                    'admin_fee' => $calculation['admin_fee']
                ]
            );
        }

        Log::info('Soldes wallets mis à jour atomiquement', [
            'transaction_id' => $transaction->id,
            'operator_wallet_id' => $operatorWallet->id,
            'operator_balance_change' => -$calculation['total_paid_ht'],
            'integrator_wallet_id' => $integrator ? WalletService::getOrCreateWallet($integrator)->id : null,
            'integrator_balance_change' => $calculation['integrator_share'],
            'admin_wallet_id' => $admin ? WalletService::getOrCreateWallet($admin)->id : null,
            'admin_balance_change' => $calculation['admin_share']
        ]);
    }

    /**
     * Save transaction repartition record
     * 
     * @param Transaction $transaction
     * @param array $calculation
     * @return TransactionRepartition
     */
    protected function saveTransactionRepartition(Transaction $transaction, array $calculation): TransactionRepartition
    {
        // Ne pas inclure total_amount car la colonne n'existe pas dans la base de données
        // Le total peut être calculé dynamiquement via getTotalAmount()
        try {
            $repartition = TransactionRepartition::create([
                'transaction_id' => $transaction->id,
                'admin_amount' => $calculation['admin_share'],
                'integrator_amount' => $calculation['integrator_share'],
                'operator_amount' => $calculation['operator_share'],
                // 'total_amount' retiré - calculé dynamiquement
                'integrator_fee' => $calculation['integrator_fee'] ?? null,
                'admin_fee' => $calculation['admin_fee'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            Log::info('Transaction repartition record created', [
                'repartition_id' => $repartition->id,
                'transaction_id' => $transaction->id
            ]);

            return $repartition;
        } catch (\Illuminate\Database\QueryException $e) {
            // Si l'erreur est due à total_amount, réessayer sans cette colonne
            if (str_contains($e->getMessage(), 'total_amount')) {
                Log::warning('Tentative de création de répartition avec total_amount (colonne inexistante)', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);
                // Réessayer sans total_amount
                return TransactionRepartition::create([
                    'transaction_id' => $transaction->id,
                    'admin_amount' => $calculation['admin_share'],
                    'integrator_amount' => $calculation['integrator_share'],
                    'operator_amount' => $calculation['operator_share'],
                    'integrator_fee' => $calculation['integrator_fee'] ?? null,
                    'admin_fee' => $calculation['admin_fee'] ?? null,
                ]);
            }
            throw $e;
        }
    }
}
