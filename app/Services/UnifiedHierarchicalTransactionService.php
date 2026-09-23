<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TransactionHierarchy;
use App\Models\BusinessProfile;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\Group;
use App\Models\Partner;
use App\Models\Integrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class UnifiedHierarchicalTransactionService
{
    /**
     * Traiter une transaction hiérarchique complète selon vos spécifications
     * 
     * LOGIQUE IMPLÉMENTÉE :
     * 1. Borne → Groupe → Opérateur → Intégrateur → Admin
     * 2. Admin → Intégrateur : L'Admin prend sa part selon BusinessProfile, déduite de l'intégrateur
     * 3. Intégrateur → Opérateur : L'intégrateur prend sa part, le reste va à l'opérateur
     * 4. Sauvegarde complète dans les tables transactions avec vérification de cohérence
     * 
     * @param int $chargingPointId ID de la borne
     * @param float $totalAmount Montant total de la transaction
     * @param array $metadata Métadonnées supplémentaires
     * @return array
     */
    public function processHierarchicalTransaction(int $chargingPointId, float $totalAmount, array $metadata = []): array
    {
        try {
            DB::beginTransaction();

            // 1️⃣ RÉCUPÉRER LA HIÉRARCHIE COMPLÈTE
            $hierarchy = $this->getCompleteHierarchy($chargingPointId);
            
            if (!$hierarchy) {
                throw new Exception('Impossible de récupérer la hiérarchie complète pour cette borne');
            }

            // 2️⃣ RÉCUPÉRER LES BUSINESS PROFILES
            $businessProfiles = $this->getBusinessProfilesForHierarchy($hierarchy);

            // 3️⃣ CALCULER LES PARTS SELON LES BUSINESS PROFILES
            $calculation = $this->calculateSharesWithBusinessProfiles($totalAmount, $businessProfiles, $hierarchy);

            // 4️⃣ CRÉER LA TRANSACTION PRINCIPALE
            $mainTransaction = $this->createMainTransaction($chargingPointId, $totalAmount, $calculation, $hierarchy, $metadata);

            // 5️⃣ CRÉER LES DÉTAILS DE TRANSACTION
            $transactionDetail = $this->createTransactionDetail($mainTransaction, $calculation, $hierarchy);

            // 6️⃣ CRÉER LES TRANSACTIONS HIÉRARCHIQUES
            $hierarchicalTransactions = $this->createHierarchicalTransactions($mainTransaction, $calculation, $hierarchy);

            // 7️⃣ METTRE À JOUR LES WALLETS AVEC VÉRIFICATION DE COHÉRENCE
            $this->updateWalletsWithValidation($hierarchy, $calculation, $mainTransaction);

            DB::commit();

            Log::info('Transaction hiérarchique traitée avec succès', [
                'charging_point_id' => $chargingPointId,
                'transaction_id' => $mainTransaction->id,
                'total_amount' => $totalAmount,
                'admin_share' => $calculation['admin_share'],
                'integrator_share' => $calculation['integrator_share'],
                'operator_share' => $calculation['operator_share']
            ]);

            return [
                'success' => true,
                'main_transaction' => $mainTransaction,
                'transaction_detail' => $transactionDetail,
                'hierarchical_transactions' => $hierarchicalTransactions,
                'calculation' => $calculation,
                'hierarchy' => $hierarchy
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors du traitement de la transaction hiérarchique', [
                'charging_point_id' => $chargingPointId,
                'total_amount' => $totalAmount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Récupérer la hiérarchie complète : Borne → Groupe → Opérateur → Intégrateur → Admin
     */
    public function getCompleteHierarchy(int $chargingPointId): ?array
    {
        $chargingPoint = ChargingPoint::with([
            'group.partner.integrator.admin',
            'group.partner.integrator.businessProfile', // Charger le Business Profile de l'intégrateur
            // Le modèle n'a pas de relation 'operator'; l'opérateur est le 'user'
            'user'
        ])->find($chargingPointId);

        if (!$chargingPoint) {
            return null;
        }

        $hierarchy = [
            'charging_point' => $chargingPoint,
            'group' => $chargingPoint->group,
            'partner' => $chargingPoint->group?->partner,
            'integrator' => $chargingPoint->group?->partner?->integrator,
            'admin' => $chargingPoint->group?->partner?->integrator?->admin,
            // L'opérateur est récupéré avec fallback via getOperator()
            'operator' => $chargingPoint->getOperator()
        ];

        // Vérifier que tous les niveaux sont présents
        if (!$hierarchy['integrator'] || !$hierarchy['admin'] || !$hierarchy['operator']) {
            Log::warning('Hiérarchie incomplète détectée', [
                'charging_point_id' => $chargingPointId,
                'has_integrator' => !is_null($hierarchy['integrator']),
                'has_admin' => !is_null($hierarchy['admin']),
                'has_operator' => !is_null($hierarchy['operator'])
            ]);
            return null;
        }

        return $hierarchy;
    }

    /**
     * Récupérer les Business Profiles pour la hiérarchie
     * 
     * LOGIQUE CORRIGÉE :
     * 1. Business Profile Admin → Intégrateur : Récupéré depuis l'intégrateur, appliqué par son admin créateur
     * 2. Business Profile Intégrateur → Opérateur : Récupéré depuis l'opérateur/partenaire, appliqué par son intégrateur
     */
    public function getBusinessProfilesForHierarchy(array $hierarchy): array
    {
        $profiles = [
            'admin_integrator' => null,
            'integrator_operator' => null
        ];

        // 1. BUSINESS PROFILE ADMIN → INTÉGRATEUR
        // Récupéré depuis l'intégrateur, appliqué par son admin créateur
        if ($hierarchy['admin'] && $hierarchy['integrator']) {
            // PRIORITÉ 1 : Utiliser le Business Profile attaché directement à l'intégrateur (business_profile_id)
            // C'est la façon standard d'attacher un Business Profile Admin à un intégrateur
            if ($hierarchy['integrator']->business_profile_id) {
                $profiles['admin_integrator'] = BusinessProfile::where('id', $hierarchy['integrator']->business_profile_id)
                    ->where('is_active', true)
                    ->first();
            }

            // PRIORITÉ 2 : Chercher le profil créé par l'admin pour cet intégrateur (avec integrator_id)
            if (!$profiles['admin_integrator']) {
                $profiles['admin_integrator'] = BusinessProfile::where('created_by_id', $hierarchy['admin']->id)
                    ->where('created_by_type', 'admin')
                    ->where('integrator_id', $hierarchy['integrator']->id)
                    ->where('is_active', true)
                    ->orderBy('created_at', 'desc')
                    ->first();
            }
        }

        // 2. BUSINESS PROFILE INTÉGRATEUR → OPÉRATEUR (strict, pas de fallback générique)
        // Récupéré depuis l'opérateur/partenaire, appliqué par son intégrateur
        if ($hierarchy['integrator'] && $hierarchy['partner']) {
            // Chercher le profil créé par l'intégrateur pour ce partenaire
            $profiles['integrator_operator'] = BusinessProfile::where('created_by_id', $hierarchy['integrator']->id)
                ->where('created_by_type', 'integrator')
                ->where('partner_id', $hierarchy['partner']->id)
                ->where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->first();
        }

        // 3. PROFILS PAR DÉFAUT SI AUCUN TROUVÉ
        if (!$profiles['admin_integrator']) {
            $profiles['admin_integrator'] = BusinessProfile::where('name', 'Business Profile Standard')
                ->where('is_active', true)
                ->first();
        }

        if (!$profiles['integrator_operator']) {
            $profiles['integrator_operator'] = $profiles['admin_integrator'];
        }

        // 4. Validation finale: échec uniquement si aucun profil n'est disponible
        if (!$profiles['admin_integrator'] || !$profiles['integrator_operator']) {
            throw new Exception('Business Profiles requis manquants: admin_integrator ou integrator_operator introuvable');
        }

        return $profiles;
    }

    /**
     * Calculer les parts selon les Business Profiles
     * 
     * LOGIQUE CORRIGÉE :
     * 1. Calculer les frais variables selon les configurations des Business Profiles
     * 2. Les parts correspondent exactement aux frais calculés
     * 3. Application selon la hiérarchie : Admin → Intégrateur → Opérateur
     */
    public function calculateSharesWithBusinessProfiles(
        float $totalAmount, 
        array $businessProfiles, 
        array $hierarchy
    ): array {
        $adminProfile = $businessProfiles['admin_integrator'];
        $integratorProfile = $businessProfiles['integrator_operator'];

        if (!$adminProfile || !$integratorProfile) {
            throw new Exception('Business Profiles manquants pour le calcul des parts');
        }

        // LOGIQUE HIÉRARCHIQUE CORRIGÉE SELON LA SPÉCIFICATION :
        // FRAIS = FRAIS CONFIG (Transaction) + CHARGE (Recharge)
        // 
        // 1. Frais Intégrateur = Config Transaction + Config Charge (calculé sur le Total)
        //    - Utilise le Business Profile Intégrateur → Opérateur
        //    - IMPORTANT : Les integrator_fee_* ne sont PAS inclus dans les part fees
        // 
        // 2. Frais Admin = Config Transaction + Config Charge (calculé sur le TOTAL HTC)
        //    - Utilise le Business Profile Admin → Intégrateur
        //    - IMPORTANT : Calculé sur le TOTAL, PAS sur les frais intégrateur
        //    - IMPORTANT : Les admin_fee_* ne sont PAS inclus dans les frais admin pour BP "Admin → Intégrateur"
        //    - Mais déduit de la part intégrateur (pas du total directement)
        // 
        // 3. Opérateur = Total - Frais Intégrateur
        // 4. Intégrateur Net = Frais Intégrateur - Frais Admin
        // 5. Admin = Frais Admin

        // Étape 1: Calculer les Frais Intégrateur sur le montant total
        // IMPORTANT : Utilise le Business Profile Intégrateur → Opérateur
        // Frais Intégrateur = Config Transaction (BP Intégrateur→Opérateur) + Config Charge (BP Intégrateur→Opérateur)
        // Les integrator_fee_* ne sont PAS inclus
        $integratorFees = $this->calculateBusinessProfileFees($integratorProfile, $totalAmount, 'integrator');
        $integratorFeesAmount = round($integratorFees['total_fees'], 2);

        // Étape 2: Calculer les Frais Admin sur le TOTAL (pas sur les frais intégrateur)
        // IMPORTANT : Utilise le Business Profile Admin → Intégrateur
        // Frais Admin = Config Transaction (BP Admin→Intégrateur) + Config Charge (BP Admin→Intégrateur)
        // IMPORTANT : Les admin_fee_* ne sont PAS inclus dans les frais admin pour BP "Admin → Intégrateur"
        // Calculé sur le TOTAL HTC de la réservation, mais déduit de la part intégrateur
        $adminFees = $this->calculateBusinessProfileFees($adminProfile, $totalAmount, 'admin');
        $adminFeesAmount = round($adminFees['total_fees'], 2);

        // Étape 3: Calculer les parts selon la logique hiérarchique
        // Opérateur = Total - Frais Intégrateur
        $operatorShare = round($totalAmount - $integratorFeesAmount, 2);
        
        // Intégrateur Net = Frais Intégrateur - Frais Admin
        $integratorShare = round($integratorFeesAmount - $adminFeesAmount, 2);
        
        // Admin = Frais Admin
        $adminShare = $adminFeesAmount;

        // Avertissement si les frais admin dépassent les frais intégrateur (mais pas de limitation)
        if ($adminFeesAmount > $integratorFeesAmount) {
            Log::warning('Frais admin supérieurs aux frais intégrateur (part intégrateur sera négative)', [
                'admin_fees' => $adminFeesAmount,
                'integrator_fees' => $integratorFeesAmount,
                'note' => 'Aucune limitation appliquée - les frais sont calculés selon la configuration'
            ]);
        }

        // Vérifier que l'opérateur ne reçoit pas un montant négatif
        if ($operatorShare < 0) {
            Log::warning('Part opérateur négative détectée, ajustement appliqué', [
                'total_amount' => $totalAmount,
                'integrator_fees' => $integratorFeesAmount,
                'operator_share' => $operatorShare
            ]);
            $operatorShare = 0;
            // Ajuster les frais intégrateur si nécessaire
            $integratorFeesAmount = $totalAmount;
            // Recalculer la part admin sur le total (toujours sur le total, pas sur les frais intégrateur)
            $adminFees = $this->calculateBusinessProfileFees($adminProfile, $totalAmount, 'admin');
            $adminFeesAmount = round($adminFees['total_fees'], 2);
            $adminShare = $adminFeesAmount;
            $integratorShare = round($integratorFeesAmount - $adminFeesAmount, 2);
        }

        // Avertissement si la part intégrateur est négative (mais pas de limitation - c'est la responsabilité de la configuration)
        if ($integratorShare < 0) {
            Log::warning('Part intégrateur négative détectée (les frais admin dépassent les frais intégrateur)', [
                'integrator_fees' => $integratorFeesAmount,
                'admin_fees' => $adminFeesAmount,
                'integrator_share' => $integratorShare,
                'note' => 'Aucun ajustement automatique - vérifier la configuration des Business Profiles'
            ]);
        }

        // Validation finale : S'assurer que la somme des parts correspond exactement au total
        $calculatedTotal = round($adminShare + $integratorShare + $operatorShare, 2);
        $totalAmountRounded = round($totalAmount, 2);
        $difference = abs($calculatedTotal - $totalAmountRounded);
        
        if ($difference > 0.01) {
            // Ajuster la part de l'opérateur pour corriger la différence (car c'est le reste)
            $operatorShare = round($operatorShare - ($calculatedTotal - $totalAmountRounded), 2);
            
            Log::info('Ajustement de cohérence des montants', [
                'total_amount' => $totalAmountRounded,
                'calculated_total_avant' => $calculatedTotal,
                'difference' => $difference,
                'operator_share_ajuste' => $operatorShare
            ]);
        }

        // Calculer les pourcentages réels par rapport au total de la transaction
        $adminPercentage = $totalAmount > 0 ? ($adminShare / $totalAmount) * 100 : 0;
        $integratorPercentage = $totalAmount > 0 ? ($integratorShare / $totalAmount) * 100 : 0;
        $operatorPercentage = $totalAmount > 0 ? ($operatorShare / $totalAmount) * 100 : 0;

        return [
            'total_amount' => $totalAmount,
            'admin_share' => $adminShare,
            'integrator_share' => $integratorShare,
            'operator_share' => $operatorShare,
            // Frais bruts (avant déduction) pour les wallets
            'integrator_fees_amount' => $integratorFeesAmount,
            'admin_fees_amount' => $adminFeesAmount,
            'admin_fees_breakdown' => $adminFees,
            'integrator_fees_breakdown' => $integratorFees,
            // Pourcentages réels calculés par rapport au total de la transaction
            'admin_percentage' => round($adminPercentage, 4),
            'integrator_percentage' => round($integratorPercentage, 4),
            'operator_percentage' => round($operatorPercentage, 4),
            // Pourcentages des business profiles (pour référence)
            'admin_profile_percentage' => $adminFees['percentage_used'],
            'integrator_profile_percentage' => $integratorFees['percentage_used'],
            'admin_fixed' => $adminFees['fixed_used'],
            'integrator_fixed' => $integratorFees['fixed_used'],
            'business_profiles' => [
                'admin_integrator_id' => $adminProfile->id,
                'integrator_operator_id' => $integratorProfile->id
            ]
        ];
    }

    /**
     * Calculer les frais variables selon un Business Profile
     * 
     * LOGIQUE CORRIGÉE SELON LA SPÉCIFICATION :
     * FRAIS = FRAIS CONFIG (Transaction) + CHARGE (Recharge)
     * 
     * Pour la Part Intégrateur :
     * - Utilise le Business Profile Intégrateur → Opérateur
     * - Frais Intégrateur = Config Transaction + Config Charge (SANS integrator_fee_*)
     * 
     * Pour la Part Admin :
     * - Utilise le Business Profile Admin → Intégrateur
     * - Frais Admin = Config Transaction + Config Charge (SANS admin_fee_*)
     * - Les admin_fee_* ne sont PAS inclus dans les frais admin pour BP "Admin → Intégrateur"
     * - Calculé sur le TOTAL HTC (pas sur les frais intégrateur)
     * - Mais déduit de la part intégrateur (pas du total directement)
     * 
     * LOGIQUE HIÉRARCHIQUE :
     * - Opérateur = Total - Frais Intégrateur
     * - Intégrateur Net = Frais Intégrateur - Frais Admin (Frais Admin déduit de la part intégrateur)
     * - Admin = Frais Admin (calculé sur le TOTAL HTC)
     */
    protected function calculateBusinessProfileFees(BusinessProfile $profile, float $totalAmount, string $role): array
    {
        $fees = [
            'activation_fee' => 0,        // Frais d'activation (base_fee_amount)
            'transaction_fee' => 0,       // Frais de transaction (transaction_fee_config)
            'charge_fee' => 0,           // Frais de recharge (charge_fee_config)
            'role_fee' => 0,             // Frais spécifiques au rôle (admin_fee_* uniquement)
            'total_fees' => 0,
            'percentage_used' => 0,
            'fixed_used' => 0,
            'breakdown' => [],
            'calculation_details' => []
        ];

        // 1. FRAIS D'ACTIVATION (base_fee_amount) - RÉCUPÉRÉ DU BUSINESS PROFILE
        $fees['activation_fee'] = (float) ($profile->base_fee_amount ?? 0);
        $fees['breakdown']['activation'] = [
            'type' => 'fixed',
            'amount' => $fees['activation_fee'],
            'source' => 'base_fee_amount'
        ];

        // 2. FRAIS DE TRANSACTION (transaction_fee_config) - RÉCUPÉRÉ DU BUSINESS PROFILE
        $transactionConfig = is_string($profile->transaction_fee_config) 
            ? json_decode($profile->transaction_fee_config, true) 
            : $profile->transaction_fee_config;

        if ($transactionConfig && is_array($transactionConfig)) {
            $transactionFixed = (float) ($transactionConfig['fixed_amount'] ?? 0);
            $transactionPercentage = (float) ($transactionConfig['percentage'] ?? 0);
            $transactionPerKwh = (float) ($transactionConfig['per_kwh_fee'] ?? 0);
            $transactionPerMinute = (float) ($transactionConfig['per_minute_fee'] ?? 0);
            
            $fees['transaction_fee'] = $transactionFixed + ($totalAmount * $transactionPercentage / 100);
            
            $fees['breakdown']['transaction'] = [
                'type' => 'mixed',
                'fixed_amount' => $transactionFixed,
                'percentage' => $transactionPercentage,
                'per_kwh' => $transactionPerKwh,
                'per_minute' => $transactionPerMinute,
                'calculated' => $fees['transaction_fee'],
                'source' => 'transaction_fee_config'
            ];

            $fees['calculation_details']['transaction'] = [
                'formula' => "{$transactionFixed} + ({$totalAmount} × {$transactionPercentage}%)",
                'result' => $fees['transaction_fee']
            ];
        }

        // 3. FRAIS DE RECHARGE (charge_fee_config) - RÉCUPÉRÉ DU BUSINESS PROFILE
        $chargeConfig = is_string($profile->charge_fee_config) 
            ? json_decode($profile->charge_fee_config, true) 
            : $profile->charge_fee_config;

        if ($chargeConfig && is_array($chargeConfig)) {
            $chargeFixed = (float) ($chargeConfig['fixed_amount'] ?? 0);
            $chargePercentage = (float) ($chargeConfig['percentage'] ?? 0);
            $chargePerKwh = (float) ($chargeConfig['per_kwh_fee'] ?? 0);
            $chargePerMinute = (float) ($chargeConfig['per_minute_fee'] ?? 0);
            
            $fees['charge_fee'] = $chargeFixed + ($totalAmount * $chargePercentage / 100);
            
            $fees['breakdown']['charge'] = [
                'type' => 'mixed',
                'fixed_amount' => $chargeFixed,
                'percentage' => $chargePercentage,
                'per_kwh' => $chargePerKwh,
                'per_minute' => $chargePerMinute,
                'calculated' => $fees['charge_fee'],
                'source' => 'charge_fee_config'
            ];

            $fees['calculation_details']['charge'] = [
                'formula' => "{$chargeFixed} + ({$totalAmount} × {$chargePercentage}%)",
                'result' => $fees['charge_fee']
            ];
        }

        // 4. FRAIS SPÉCIFIQUES SELON LE RÔLE - UNIQUEMENT POUR ADMIN
        // IMPORTANT : Les frais intégrateur (integrator_fee_*) ne sont PAS inclus dans les part fees
        // Les part fees intégrateur = Config Transaction + Config Charge uniquement
        if ($role === 'admin') {
            $adminFixed = (float) ($profile->admin_fee_fixed ?? 0);
            $adminPercentage = (float) ($profile->admin_fee_percentage ?? 0);
            
            // Les frais admin sont calculés sur les frais intégrateur (totalAmount = frais intégrateur)
            $fees['role_fee'] = $adminFixed + ($totalAmount * $adminPercentage / 100);
            $fees['percentage_used'] = $adminPercentage;
            $fees['fixed_used'] = $adminFixed;
            
            $fees['breakdown']['admin'] = [
                'type' => 'role_specific',
                'fixed_amount' => $adminFixed,
                'percentage' => $adminPercentage,
                'calculated' => $fees['role_fee'],
                'source' => 'admin_fee_*',
                'note' => 'Calculé sur la part intégrateur (frais intégrateur)'
            ];

            $fees['calculation_details']['admin'] = [
                'formula' => "{$adminFixed} + ({$totalAmount} × {$adminPercentage}%)",
                'result' => $fees['role_fee'],
                'base_amount' => $totalAmount,
                'description' => 'Frais Admin calculés sur les frais intégrateur'
            ];

        } elseif ($role === 'integrator') {
            // Pour l'intégrateur, on garde les valeurs à 0 car les part fees = Config Transaction + Config Charge uniquement
            // Les integrator_fee_* ne sont PAS inclus dans les part fees
            $integratorFixed = (float) ($profile->integrator_fee_fixed ?? 0);
            $integratorPercentage = (float) ($profile->integrator_fee_percentage ?? 0);
            
            // On stocke les valeurs pour information mais elles ne sont PAS incluses dans total_fees
            $fees['percentage_used'] = $integratorPercentage;
            $fees['fixed_used'] = $integratorFixed;
            
            $fees['breakdown']['integrator'] = [
                'type' => 'role_specific',
                'fixed_amount' => $integratorFixed,
                'percentage' => $integratorPercentage,
                'calculated' => 0,
                'source' => 'integrator_fee_*',
                'note' => 'NON inclus dans les part fees. Part fees = Config Transaction + Config Charge uniquement'
            ];

            $fees['calculation_details']['integrator'] = [
                'formula' => "Non inclus dans part fees",
                'result' => 0,
                'description' => 'Les integrator_fee_* ne sont pas inclus dans les part fees'
            ];
        }

        // 5. TOTAL DES FRAIS SELON LA LOGIQUE CORRIGÉE
        // Les frais d'activation sont exclus du calcul des parts admin/intégrateur
        
        if ($role === 'integrator') {
            // Pour l'intégrateur : FRAIS = CONFIG TRANSACTION + CONFIG CHARGE (SANS integrator_fee_*)
            $fees['total_fees'] = $fees['transaction_fee'] + $fees['charge_fee'];
        } elseif ($role === 'admin') {
            // Pour l'admin : FRAIS = CONFIG TRANSACTION + CONFIG CHARGE (SANS admin_fee_*)
            // Selon la spécification Business Profile "Admin → Intégrateur" :
            // La part admin est uniquement Config Transaction + Config Charge
            // Les admin_fee_* (role_fee) ne sont PAS inclus dans les frais admin pour ce type de BP
            $fees['total_fees'] = $fees['transaction_fee'] + $fees['charge_fee'];
        } else {
            // Par défaut : Config Transaction + Config Charge
            $fees['total_fees'] = $fees['transaction_fee'] + $fees['charge_fee'];
        }

        // 6. VALIDATION ET LOGGING
        $this->logFeeCalculation($profile, $totalAmount, $role, $fees);

        return $fees;
    }

    /**
     * Logger le calcul des frais
     */
    protected function logFeeCalculation(BusinessProfile $profile, float $totalAmount, string $role, array $fees): void
    {
        Log::info("Calcul des frais Business Profile - {$role}", [
            'business_profile_id' => $profile->id,
            'business_profile_name' => $profile->name,
            'role' => $role,
            'total_amount' => $totalAmount,
            'fees_breakdown' => $fees['breakdown'],
            'total_fees_calculated' => $fees['total_fees'],
            'calculation_details' => $fees['calculation_details']
        ]);
    }

    /**
     * Créer la transaction principale
     * 
     * Sauvegarde toutes les informations nécessaires :
     * - charging_point_id : ID de la borne
     * - user_id : ID de l'opérateur (propriétaire de la borne)
     * - amount : Montant total de la transaction
     * - hierarchy_data : Données complètes de la hiérarchie (Admin, Intégrateur, Opérateur, Groupe)
     * - metadata : Métadonnées avec calcul et type
     */
    protected function createMainTransaction(int $chargingPointId, float $totalAmount, array $calculation, array $hierarchy, array $metadata): Transaction
    {
        return Transaction::create([
            'reference_id' => uniqid('HIER_'),
            'transaction_id' => uniqid('HIER_'),
            'charging_point_id' => $chargingPointId,
            'user_id' => $hierarchy['operator']->id ?? null, // ID de l'opérateur
            'amount' => $totalAmount,
            'amount_ht' => $totalAmount,
            'currency' => 'EUR',
            'type' => 'hierarchical_transaction',
            'status' => 'completed',
            'description' => "Transaction hiérarchique - Borne #{$chargingPointId}",
            'hierarchy_data' => [
                'admin_id' => $hierarchy['admin']->id ?? null,
                'integrator_id' => $hierarchy['integrator']->id ?? null,
                'operator_id' => $hierarchy['operator']->id ?? null,
                'partner_id' => $hierarchy['partner']->id ?? null,
                'group_id' => $hierarchy['group']->id ?? null,
                'charging_point_id' => $chargingPointId,
                'hierarchy_complete' => true
            ],
            'metadata' => array_merge($metadata, [
                'calculation' => $calculation,
                'transaction_type' => 'hierarchical',
                'parts' => [
                    'admin_share' => $calculation['admin_share'],
                    'integrator_share' => $calculation['integrator_share'],
                    'operator_share' => $calculation['operator_share'],
                ],
                'business_profiles' => $calculation['business_profiles']
            ])
        ]);
    }

    /**
     * Créer les détails de transaction avec parts
     */
    protected function createTransactionDetail(
        Transaction $transaction, 
        array $calculation, 
        array $hierarchy
    ): TransactionDetail {
        // Calculer les frais totaux pour l'affichage
        $totalFees = $calculation['admin_share'] + $calculation['integrator_share'];
        
        return TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'transaction_fee_percentage' => 0.0, // Les frais sont dans les parts
            'transaction_fee_fixed' => 0.0,
            'transaction_fee_total' => $totalFees,
            'admin_share_amount' => $calculation['admin_share'],
            'integrator_share_amount' => $calculation['integrator_share'],
            'operator_share_amount' => $calculation['operator_share'],
            'admin_share_percentage' => $calculation['admin_percentage'],
            'integrator_share_percentage' => $calculation['integrator_percentage'],
            'admin_creator_id' => $hierarchy['admin']->id,
            'integrator_creator_id' => $hierarchy['integrator']->id,
            'operator_id' => $hierarchy['operator']->id,
            'admin_paid' => false,
            'integrator_paid' => false,
            'operator_paid' => false,
            'calculation_details' => [
                'business_profiles' => $calculation['business_profiles'] ?? [],
                'admin_fixed' => $calculation['admin_fixed'] ?? 0,
                'integrator_fixed' => $calculation['integrator_fixed'] ?? 0,
                'admin_fees_breakdown' => $calculation['admin_fees_breakdown'] ?? [],
                'integrator_fees_breakdown' => $calculation['integrator_fees_breakdown'] ?? [],
                'hierarchy' => [
                    'admin_id' => $hierarchy['admin']->id,
                    'integrator_id' => $hierarchy['integrator']->id,
                    'operator_id' => $hierarchy['operator']->id,
                    'charging_point_id' => $hierarchy['charging_point']->id
                ],
                'fees_calculation_method' => 'business_profile_variable_fees',
                'total_fees_calculated' => $totalFees,
                'operator_net_amount' => $calculation['operator_share']
            ]
        ]);
    }

    /**
     * Créer les transactions hiérarchiques
     */
    protected function createHierarchicalTransactions(
        Transaction $mainTransaction, 
        array $calculation, 
        array $hierarchy
    ): array {
        $hierarchicalTransactions = [];

        // Transaction Admin ↔ Intégrateur
        if ($calculation['admin_share'] > 0) {
            $hierarchicalTransactions['admin_integrator'] = TransactionHierarchy::create([
                'original_transaction_id' => $mainTransaction->id,
                'payer_id' => $hierarchy['integrator']->id,
                'payer_type' => 'integrator',
                'payee_id' => $hierarchy['admin']->id,
                'payee_type' => 'admin',
                'amount' => $calculation['admin_share'],
                'currency' => 'EUR',
                'transaction_type' => 'admin_integrator',
                'status' => 'completed',
                'description' => "Part Admin - Transaction #{$mainTransaction->id}",
                'metadata' => [
                    'charging_point_id' => $mainTransaction->charging_point_id,
                    'business_profile_id' => $calculation['business_profiles']['admin_integrator_id']
                ]
            ]);
        }

        // Transaction Intégrateur ↔ Opérateur
        if ($calculation['integrator_share'] > 0) {
            $hierarchicalTransactions['integrator_operator'] = TransactionHierarchy::create([
                'original_transaction_id' => $mainTransaction->id,
                'payer_id' => $hierarchy['operator']->id,
                'payer_type' => 'operator',
                'payee_id' => $hierarchy['integrator']->id,
                'payee_type' => 'integrator',
                'amount' => $calculation['integrator_share'],
                'currency' => 'EUR',
                'transaction_type' => 'integrator_operator',
                'status' => 'completed',
                'description' => "Part Intégrateur - Transaction #{$mainTransaction->id}",
                'metadata' => [
                    'charging_point_id' => $mainTransaction->charging_point_id,
                    'business_profile_id' => $calculation['business_profiles']['integrator_operator_id']
                ]
            ]);
        }

        return $hierarchicalTransactions;
    }

    /**
     * Mettre à jour les wallets avec vérification de cohérence
     * 
     * LOGIQUE CORRIGÉE SELON LA HIÉRARCHIE :
     * 1. Admin → Intégrateur : L'Admin prend sa part selon BusinessProfile, déduite de la balance de l'intégrateur
     * 2. Intégrateur → Opérateur : L'intégrateur prend sa part selon BusinessProfile, le reste va à l'opérateur (net hors taxes)
     * 
     * FLUX DE FONDS :
     * - Le montant total de la transaction provient du client (déjà traité)
     * - L'opérateur reçoit le montant total (crédit)
     * - L'intégrateur prend sa part : débit de l'opérateur + crédit de l'intégrateur
     * - L'admin prend sa part sur la part intégrateur : débit de l'intégrateur + crédit de l'admin
     */
    protected function updateWalletsWithValidation(
        array $hierarchy, 
        array $calculation, 
        Transaction $transaction
    ): void {
        $operatorWallet = $this->getOrCreateWallet($hierarchy['operator']);
        $integratorWallet = $this->getOrCreateWallet($hierarchy['integrator']->user ?? $hierarchy['integrator']);
        $adminWallet = $this->getOrCreateWallet($hierarchy['admin']);

        // 1. CRÉDITER L'OPÉRATEUR DU MONTANT TOTAL (revenu brut)
        // Le client a payé le montant total, qui arrive sur le compte de l'opérateur
        $this->creditWallet($operatorWallet, $calculation['total_amount'], "Revenu transaction - Transaction #{$transaction->id}");

        // 2. LOGIQUE HIÉRARCHIQUE : Intégrateur → Opérateur
        // Calculer les frais intégrateur (brut, avant déduction admin)
        $integratorFeesAmount = $calculation['integrator_fees_amount'] ?? ($calculation['integrator_share'] + $calculation['admin_share']);
        
        // Débiter l'opérateur des frais intégrateur (sur le total)
        $this->debitWallet($operatorWallet, $integratorFeesAmount, "Frais Intégrateur - Transaction #{$transaction->id}");
        
        // Créditer l'intégrateur des frais intégrateur (brut)
        $this->creditWallet($integratorWallet, $integratorFeesAmount, "Frais Intégrateur (brut) - Transaction #{$transaction->id}");

        // 3. LOGIQUE HIÉRARCHIQUE : Admin → Intégrateur
        // Calculer les frais admin (sur les frais intégrateur)
        $adminFeesAmount = $calculation['admin_fees_amount'] ?? $calculation['admin_share'];
        
        // Débiter l'intégrateur des frais admin (car l'admin prend sa part sur les frais intégrateur)
        $this->debitWallet($integratorWallet, $adminFeesAmount, "Déduction Frais Admin - Transaction #{$transaction->id}");
        
        // Créditer l'admin des frais admin
        $this->creditWallet($adminWallet, $adminFeesAmount, "Frais Admin - Transaction #{$transaction->id}");

        // 4. CALCULER LES BALANCES NETTES FINALES
        
        // Opérateur net : Total - Frais Intégrateur = operator_share
        $operatorNetBalance = $calculation['operator_share'];
        // Intégrateur net : Frais Intégrateur - Frais Admin = integrator_share
        $integratorNetBalance = $calculation['integrator_share'];
        // Admin net : Frais Admin = admin_share
        $adminNetBalance = $calculation['admin_share'];

        // 5. VALIDATION DE COHÉRENCE : Total = Opérateur + Intégrateur + Admin
        $totalDistributed = $operatorNetBalance + $integratorNetBalance + $adminNetBalance;
        $difference = abs($calculation['total_amount'] - $totalDistributed);
        
        if ($difference > 0.01) { // Tolérance de 1 centime pour les arrondis
            throw new Exception("Incohérence dans la répartition : Total={$calculation['total_amount']}, Distribué={$totalDistributed}, Différence={$difference}");
        }

        Log::info('Wallets mis à jour selon la hiérarchie', [
            'transaction_id' => $transaction->id,
            'total_amount' => $calculation['total_amount'],
            'hierarchy_logic' => [
                'admin_integrator' => [
                    'admin_takes' => $calculation['admin_share'],
                    'integrator_pays' => $calculation['admin_share'],
                    'integrator_net' => $integratorNetBalance
                ],
                'integrator_operator' => [
                    'integrator_fees' => $integratorFeesAmount,
                    'integrator_takes' => $calculation['integrator_share'],
                    'operator_gets' => $calculation['operator_share'],
                    'operator_net' => $operatorNetBalance
                ]
            ],
            'wallet_operations' => [
                'operator_credit_total' => $calculation['total_amount'],
                'operator_debit_integrator_fees' => $integratorFeesAmount,
                'integrator_credit_fees' => $integratorFeesAmount,
                'integrator_debit_admin_fees' => $adminFeesAmount,
                'admin_credit_fees' => $adminFeesAmount
            ],
            'net_balances' => [
                'operator' => $operatorNetBalance,
                'integrator' => $integratorNetBalance,
                'admin' => $adminNetBalance
            ],
            'validation' => [
                'total_distributed' => $totalDistributed,
                'difference' => $difference,
                'is_balanced' => $difference <= 0.01
            ]
        ]);
    }

    /**
     * Récupérer ou créer un wallet
     */
    protected function getOrCreateWallet($owner): Wallet
    {
        $wallet = Wallet::where('owner_type', get_class($owner))
            ->where('owner_id', $owner->id)
            ->first();

        if (!$wallet) {
            $wallet = Wallet::create([
                'owner_type' => get_class($owner),
                'owner_id' => $owner->id,
                'balance' => 0.00,
                'currency' => 'EUR',
                'is_active' => true,
                'name' => 'Wallet Principal'
            ]);
        }

        return $wallet;
    }

    /**
     * Débiter un wallet
     */
    protected function debitWallet(Wallet $wallet, float $amount, string $description): WalletTransaction
    {
        if ($wallet->balance < $amount) {
            throw new Exception("Solde insuffisant. Requis: {$amount}, Disponible: {$wallet->balance}");
        }

        return $wallet->debit($amount, $description, [
            'transaction_type' => 'hierarchical_payment',
            'status' => 'completed'
        ]);
    }

    /**
     * Créditer un wallet
     */
    protected function creditWallet(Wallet $wallet, float $amount, string $description): WalletTransaction
    {
        return $wallet->credit($amount, $description, [
            'transaction_type' => 'hierarchical_share',
            'status' => 'completed'
        ]);
    }

    /**
     * Obtenir le résumé d'une transaction hiérarchique
     */
    public function getTransactionSummary(int $transactionId): array
    {
        $transaction = Transaction::find($transactionId);
        
        if (!$transaction) {
            return ['success' => false, 'message' => 'Transaction non trouvée'];
        }

        $transactionDetail = TransactionDetail::where('transaction_id', $transaction->id)->first();
        $hierarchicalTransactions = TransactionHierarchy::where('original_transaction_id', $transaction->id)->get();

        return [
            'success' => true,
            'transaction' => $transaction,
            'transaction_detail' => $transactionDetail,
            'hierarchical_transactions' => $hierarchicalTransactions,
            'summary' => [
                'total_amount' => $transaction->amount,
                'admin_share' => $transactionDetail?->admin_share_amount ?? 0,
                'integrator_share' => $transactionDetail?->integrator_share_amount ?? 0,
                'operator_share' => $transactionDetail?->operator_share_amount ?? 0,
                'admin_percentage' => $transactionDetail?->admin_share_percentage ?? 0,
                'integrator_percentage' => $transactionDetail?->integrator_share_percentage ?? 0
            ]
        ];
    }
}
