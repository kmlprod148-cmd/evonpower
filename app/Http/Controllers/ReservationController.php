<?php

namespace App\Http\Controllers;

use App\Events\ReservationApproved;
use App\Http\Requests\StoreReservationRequest;
use App\Jobs\StartChargingSessionJob;
use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Services\CreditPaymentService;
use App\Services\ReservationPaymentApprovalService;
use App\Services\ReservationService;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReservationController extends Controller
{
    protected $reservationService;
    protected $creditPaymentService;
    protected ReservationPaymentApprovalService $approvalService;

    public function __construct(
        ReservationService $reservationService,
        CreditPaymentService $creditPaymentService,
        ReservationPaymentApprovalService $approvalService
    ) {
        $this->reservationService = $reservationService;
        $this->creditPaymentService = $creditPaymentService;
        $this->approvalService = $approvalService;
    }

    public function confirm(Request $request, Reservation $reservation)
    {
        // Vérification pour les clients et utilisateurs publics : ne peuvent confirmer que leurs propres réservations
        $user = auth()->user();
        $isPublicClient = $user && (($user->hasRole('client') || ($user->hasRole('user') && !$user->hasAnyRole(['admin', 'super_admin', 'integrator', 'operator', 'partner']))));
        
        if ($isPublicClient) {
            if ($reservation->user_id !== $user->id) {
                \Log::warning('Client/User attempted to confirm another user\'s reservation', [
                    'user_id' => $user->id,
                    'user_roles' => $user->getRoleNames()->toArray(),
                    'reservation_id' => $reservation->id,
                    'reservation_user_id' => $reservation->user_id
                ]);
                
                return redirect()->back()->with('error', 'Vous n\'êtes pas autorisé à confirmer cette réservation.');
            }
            
            // Vérifier que le balance est suffisant avant de confirmer
            $wallet = $user->getOrCreateWallet();
            $estimatedCost = (float) ($reservation->estimated_cost ?? 0);
            $currentBalance = (float) $wallet->balance;
            
            if ($estimatedCost > 0 && $currentBalance < $estimatedCost) {
                \Log::warning('Client/User attempted to confirm reservation with insufficient balance', [
                    'user_id' => $user->id,
                    'reservation_id' => $reservation->id,
                    'balance' => $currentBalance,
                    'required' => $estimatedCost
                ]);
                
                return redirect()->back()->with('error', "Solde insuffisant. Solde actuel: " . number_format($currentBalance, 2) . " EUR, Coût requis: " . number_format($estimatedCost, 2) . " EUR.");
            }
        }
        
        // Validate the request to ensure a valid status is provided.
        $request->validate([
            'status' => ['required', \Illuminate\Validation\Rule::in([\App\Enums\ReservationStatus::CONFIRMED->value, \App\Enums\ReservationStatus::CANCELED->value])],
        ]);

        // Check if the reservation is in a state that allows confirmation or cancellation.
        if ($reservation->status !== \App\Enums\ReservationStatus::PENDING && $reservation->status !== \App\Enums\ReservationStatus::PENDING_CONFIRMATION) {
            return redirect()->back()->with('error', 'Cette réservation ne peut pas être confirmée ou annulée à ce moment.');
        }

        // Si l'admin approuve la réservation, traiter le paiement par crédit si nécessaire
        if ($request->status === \App\Enums\ReservationStatus::CONFIRMED->value) {
            // Vérifier si la réservation doit être payée par crédit
            $paymentMethod = $reservation->payment_method ?? $reservation->payment_type;
            // Track whether CreditPaymentService already fired ReservationApproved + handled remote start
            $creditServiceHandledApproval = false;

            if (in_array($paymentMethod, ['credit', 'prepaid_credit']) && $reservation->user_id) {
                // Vérifier que l'utilisateur existe et est connecté
                $user = $reservation->user;

                if (!$user) {
                    return redirect()->back()->with('error', 'Impossible de traiter le paiement par crédit : utilisateur introuvable.');
                }

                // Vérifier que le paiement n'a pas déjà été traité (idempotence)
                if ($reservation->isPaidByBalance()) {
                    Log::info('Paiement par crédit déjà traité pour la réservation', [
                        'reservation_id' => $reservation->id,
                        'payment_status' => $reservation->payment_status,
                        'payment_mode' => $reservation->payment_mode
                    ]);
                    // Service already fired the event on the original payment — do not re-fire
                    $creditServiceHandledApproval = true;
                } else {
                    // Traiter le paiement par crédit
                    try {
                        DB::beginTransaction();

                        // Calculer le coût estimé si nécessaire
                        if (!$reservation->estimated_cost || $reservation->estimated_cost <= 0) {
                            $this->processTransactionCalculation($reservation);
                            $reservation->refresh();
                        }

                        // Traiter le paiement prépayé
                        // NOTE: processPrepaidPayment() already fires ReservationApproved internally,
                        // so we must NOT fire it again below to avoid a duplicate charging session start.
                        $paymentResult = $this->creditPaymentService->processPrepaidPayment($reservation);

                        if (!$paymentResult['success']) {
                            DB::rollBack();
                            return redirect()->back()->with('error', $paymentResult['error'] ?? 'Erreur lors du paiement par crédit. Vérifiez que votre solde est suffisant.');
                        }

                        DB::commit();
                        $creditServiceHandledApproval = true;

                        Log::info('Paiement par crédit traité avec succès lors de la confirmation', [
                            'reservation_id' => $reservation->id,
                            'user_id' => $user->id,
                            'amount' => $reservation->estimated_cost,
                            'remaining_balance' => $paymentResult['remaining_balance'] ?? 0
                        ]);

                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Erreur lors du traitement du paiement par crédit lors de la confirmation', [
                            'reservation_id' => $reservation->id,
                            'user_id' => $reservation->user_id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);

                        return redirect()->back()->with('error', 'Erreur lors du paiement par crédit : ' . $e->getMessage());
                    }
                }
            } else {
                // Pour les autres méthodes de paiement (Stripe, CMI, etc.), traiter le calcul de la transaction
                $this->processTransactionCalculation($reservation);
            }

            // Mettre à jour le statut à CONFIRMED avec approved_by = opérateur de la borne
            // Pour les paiements par crédit, approved_at/approved_by sont déjà définis par
            // CreditPaymentService — on les écrase uniquement s'ils sont absents.
            $updateData = [
                'status'       => \App\Enums\ReservationStatus::CONFIRMED,
                'confirmed_at' => now(),
            ];
            if (!$reservation->approved_at) {
                $updateData['approved_at'] = now();
                $updateData['approved_by'] = $this->approvalService->resolveChargingPointOperatorId($reservation);
            }
            $reservation->update($updateData);
            $reservation->refresh();

            // Fire ReservationApproved so AutoRemoteStart listener can trigger the charging session.
            // Skip when CreditPaymentService already fired the event to prevent a duplicate remote start.
            if (!$creditServiceHandledApproval && $reservation->isApproved()) {
                event(new ReservationApproved($reservation, (string) auth()->id(), 'manual_confirm'));

                if ($reservation->canStartNow()) {
                    StartChargingSessionJob::dispatch($reservation->id);
                }
            }

            return redirect()->back()->with('success', 'Réservation approuvée avec succès.');
        } else {
            // Si annulation/refus, mettre à jour le statut
            $reservation->update(['status' => \App\Enums\ReservationStatus::CANCELED]);
            
            return redirect()->back()->with('success', 'Réservation refusée.');
        }
    }

    /**
     * Approuver une réservation par le propriétaire du point de charge
     * Traite le paiement par crédit/solde si la réservation est offline ou credit
     */
    public function approveByOwner(Request $request, Reservation $reservation)
    {
        // Vérifier que l'utilisateur est le propriétaire du point de charge
        $chargingPoint = $reservation->chargingPoint;
        $user = auth()->user();
        
        // Vérifier si l'utilisateur est le propriétaire du point de charge (opérateur, partenaire, intégrateur)
        $isOwner = $this->canApproveReservationAsOwner($reservation, $user);
        
        if (!$isOwner) {
            return redirect()->back()->with('error', 'Vous n\'êtes pas autorisé à approuver cette réservation.');
        }
        
        $statusValue = $reservation->status instanceof \App\Enums\ReservationStatus
            ? $reservation->status->value
            : $reservation->status;
        
        // Vérifier que la réservation est en attente ou confirmée sans transaction complète
        // 'confirmed' est autorisé pour re-déclencher le calcul si la transaction a échoué précédemment
        if (!in_array($statusValue, ['pending', 'pending_confirmation', 'confirmed'])) {
            return redirect()->back()->with('error', 'Cette réservation ne peut pas être approuvée.');
        }
        
        // Si paiement par crédit/offline : débiter le solde du client avant de confirmer
        $paymentMethod = $reservation->payment_method ?? $reservation->payment_type;
        if (in_array($paymentMethod, ['credit', 'offline', 'prepaid_credit']) && $reservation->user_id) {
            $clientUser = $reservation->user;
            if ($clientUser) {
                try {
                    DB::beginTransaction();
                    
                    if (!$reservation->estimated_cost || $reservation->estimated_cost <= 0) {
                        $this->processTransactionCalculation($reservation);
                        $reservation->refresh();
                    }
                    
                    $paymentResult = $this->creditPaymentService->processPrepaidPayment($reservation);
                    
                    if (!$paymentResult['success']) {
                        DB::rollBack();
                        return redirect()->back()->with('error', $paymentResult['error'] ?? 'Erreur lors du paiement par crédit. Vérifiez que le solde du client est suffisant.');
                    }
                    
                    // Marquer comme approuvée par le propriétaire
                    $reservation->update([
                        'approved_by' => $user->id,
                        'approved_at' => now(),
                    ]);
                    
                    DB::commit();
                    
                    Log::info('Paiement par crédit traité lors de l\'approbation par le propriétaire', [
                        'reservation_id' => $reservation->id,
                        'approved_by' => $user->id,
                        'client_id' => $clientUser->id,
                        'amount' => $reservation->estimated_cost,
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Erreur paiement par crédit lors de l\'approbation propriétaire', [
                        'reservation_id' => $reservation->id,
                        'error' => $e->getMessage(),
                    ]);
                    return redirect()->back()->with('error', 'Erreur lors du paiement par crédit : ' . $e->getMessage());
                }
            }
        } else {
            // Pour les autres méthodes : mettre à jour le statut et traiter la transaction
            $updateData = [
                'approved_by' => $user->id,
                'approved_at' => now(),
                'payment_status' => 'PAID',
            ];
            // Only transition to confirmed if not already confirmed (avoids re-confirming stuck reservations)
            if (!in_array($statusValue, ['confirmed'])) {
                $updateData['status'] = \App\Enums\ReservationStatus::CONFIRMED;
                $updateData['confirmed_at'] = now();
            }
            $reservation->update($updateData);
            $this->processTransactionCalculation($reservation);
        }
        
        return redirect()->back()->with('success', 'Réservation approuvée avec succès.');
    }
    
    /**
     * Vérifie si l'utilisateur peut approuver une réservation en tant que propriétaire du point de charge
     */
    private function canApproveReservationAsOwner(Reservation $reservation, $user): bool
    {
        $chargingPoint = $reservation->chargingPoint;
        if (!$chargingPoint) {
            return false;
        }
        
        // Opérateur direct du point de charge
        if ($chargingPoint->user_id === $user->id) {
            return true;
        }
        
        // Opérateur via groupe
        if ($chargingPoint->group_id) {
            $group = \App\Models\Group::find($chargingPoint->group_id);
            if ($group && $group->user_id === $user->id) {
                return true;
            }
        }
        
        // Partenaire
        if ($chargingPoint->partner_id) {
            $partner = $chargingPoint->partner;
            if ($partner && $partner->user_id === $user->id) {
                return true;
            }
        }
        
        // Intégrateur (via charging point ou partenaire)
        if ($chargingPoint->integrator_id && $user->integrator_id && $chargingPoint->integrator_id === $user->integrator_id) {
            return true;
        }
        
        $integrator = \App\Models\Integrator::where('user_id', $user->id)->first();
        if ($integrator && $chargingPoint->integrator_id === $integrator->id) {
            return true;
        }
        
        return false;
    }

    /**
     * Rejeter une réservation par le propriétaire du point de charge
     */
    public function rejectByOwner(Request $request, Reservation $reservation)
    {
        $user = auth()->user();
        $isOwner = $this->canApproveReservationAsOwner($reservation, $user);
        
        if (!$isOwner) {
            return redirect()->back()->with('error', 'Vous n\'êtes pas autorisé à rejeter cette réservation.');
        }
        
        $statusValue = $reservation->status instanceof \App\Enums\ReservationStatus
            ? $reservation->status->value
            : $reservation->status;
        
        if (!in_array($statusValue, ['pending', 'pending_confirmation'])) {
            return redirect()->back()->with('error', 'Cette réservation ne peut pas être rejetée.');
        }
        
        $rejectionNote = $request->input('rejection_reason', 'Rejeté par le propriétaire');
        $reservation->update([
            'status' => \App\Enums\ReservationStatus::CANCELED,
            'notes' => ($reservation->notes ?? '') . "\n[" . now() . "] Rejeté: " . $rejectionNote,
        ]);
        
        return redirect()->back()->with('success', 'Réservation rejetée.');
    }

    /**
     * Afficher les réservations en attente d'approbation pour le propriétaire
     * (opérateur, partenaire ou intégrateur du point de charge)
     * Inclut les réservations offline/credit faites par les clients
     */
    public function pendingApprovals()
    {
        $user = auth()->user();
        
        // Récupérer les IDs des points de charge que l'utilisateur peut gérer
        $chargingPointIds = $this->getChargingPointIdsForApproval($user);
        
        if (empty($chargingPointIds)) {
            $pendingReservations = collect();
            $allReservations = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
            Log::info('ReservationController pendingApprovals: Aucun point de charge trouvé pour l\'utilisateur', [
                'user_id' => $user->id,
                'roles' => $user->getRoleNames()->toArray(),
                'partner_id' => $user->partner_id ?? null,
                'integrator_id' => $user->integrator_id ?? null,
            ]);
        } else {
            // Réservations en attente (pending ou pending_confirmation)
            // Exclure celles déjà payées par solde (auto-approuvées, pas de bouton Approuver)
            $pendingReservations = Reservation::whereIn('charging_point_id', $chargingPointIds)
                ->whereIn('status', ['pending', 'pending_confirmation'])
                ->whereRaw("UPPER(COALESCE(payment_status, '')) NOT IN ('PAID', 'PAYE')")
                ->with(['chargingPoint', 'user', 'pricingPlan'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Toutes les réservations (clients) sur les bornes gérées par l'opérateur/partenaire/intégrateur
            $allReservations = Reservation::whereIn('charging_point_id', $chargingPointIds)
                ->with(['chargingPoint', 'user', 'pricingPlan'])
                ->orderBy('created_at', 'desc')
                ->paginate(20);
        }

        $allReservations = $allReservations ?? new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);

        return view('reservations.pending-approvals', compact('pendingReservations', 'allReservations'));
    }
    
    /**
     * Récupère les IDs des points de charge que l'utilisateur peut approuver
     * Partenaire et Intégrateur : voient les réservations des clients sur leurs bornes
     */
    private function getChargingPointIdsForApproval($user): array
    {
        $chargingPointIds = [];
        $userRoles = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        
        // Admin : tous les points de charge
        if (in_array('admin', $userRoles) || in_array('super_admin', $userRoles)) {
            return ChargingPoint::withTrashed()->pluck('id')->toArray();
        }
        
        // Opérateur : points de charge directs + groupes + intégrateur
        if (in_array('operator', $userRoles)) {
            $chargingPointIds = ChargingPoint::withTrashed()
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('created_by', $user->id)
                      ->orWhere('created_by_id', $user->id);
                })
                ->pluck('id')
                ->toArray();
            
            $groupIds = \App\Models\Group::where('user_id', $user->id)->pluck('id')->toArray();
            if (!empty($groupIds)) {
                $groupCpIds = ChargingPoint::withTrashed()
                    ->whereIn('group_id', $groupIds)
                    ->pluck('id')
                    ->toArray();
                $chargingPointIds = array_merge($chargingPointIds, $groupCpIds);
            }
            
            if ($user->integrator_id) {
                $integratorCpIds = ChargingPoint::withTrashed()
                    ->where('integrator_id', $user->integrator_id)
                    ->pluck('id')
                    ->toArray();
                $chargingPointIds = array_merge($chargingPointIds, $integratorCpIds);
            }
        }
        
        // Partenaire : bornes directes + bornes dans les groupes du partenaire (whereHas pour robustesse)
        if (in_array('partner', $userRoles) && $user->partner_id) {
            $partnerCpIds = ChargingPoint::withTrashed()
                ->where(function ($q) use ($user) {
                    $q->where('partner_id', $user->partner_id)
                      ->orWhereHas('group', function ($gq) use ($user) {
                          $gq->where('partner_id', $user->partner_id);
                      });
                })
                ->pluck('id')
                ->toArray();
            $chargingPointIds = array_merge($chargingPointIds, $partnerCpIds);
        }
        
        // Intégrateur : bornes directes + bornes des partenaires + bornes des groupes
        if (in_array('integrator', $userRoles)) {
            $integratorId = $user->integrator_id;
            if (!$integratorId) {
                $integrator = \App\Models\Integrator::where('user_id', $user->id)->first();
                $integratorId = $integrator?->id;
            }
            if ($integratorId) {
                $integratorCpIds = ChargingPoint::withTrashed()
                    ->where('integrator_id', $integratorId)
                    ->pluck('id')
                    ->toArray();
                
                $partnerIds = \App\Models\Partner::where('integrator_id', $integratorId)->pluck('id')->toArray();
                if (!empty($partnerIds)) {
                    $partnerCpIds = ChargingPoint::withTrashed()
                        ->whereIn('partner_id', $partnerIds)
                        ->pluck('id')
                        ->toArray();
                    $integratorCpIds = array_merge($integratorCpIds, $partnerCpIds);
                }
                
                $groupIds = \App\Models\Group::where('integrator_id', $integratorId)->pluck('id')->toArray();
                if (!empty($groupIds)) {
                    $groupCpIds = ChargingPoint::withTrashed()
                        ->whereIn('group_id', $groupIds)
                        ->pluck('id')
                        ->toArray();
                    $integratorCpIds = array_merge($integratorCpIds, $groupCpIds);
                }
                
                $chargingPointIds = array_merge($chargingPointIds, $integratorCpIds);
            }
        }
        
        return array_unique($chargingPointIds);
    }

    /**
     * Traite le calcul logique de la transaction lors de l'approbation
     * Utilise le service centralisé ReservationTransactionService pour garantir la cohérence
     */
    private function processTransactionCalculation(Reservation $reservation)
    {
        try {
            // Si la réservation est approuvée ou confirmée, utiliser le service centralisé
            // qui crée automatiquement TransactionDetail avec parts cohérentes
            $statusValue = $reservation->status instanceof \App\Enums\ReservationStatus 
                ? $reservation->status->value 
                : $reservation->status;
            if (in_array($statusValue, ['confirmed', 'completed'])) {
                // Utiliser le service centralisé de calcul des coûts d'abord
                $costService = app(\App\Services\ReservationCostCalculationService::class);
                
                // Calculer le coût estimé avec validation
                $estimatedCost = $costService->calculateReservationCost($reservation);
                
                // Si le coût est nul ou négatif, utiliser le calcul avec valeurs par défaut
                if ($estimatedCost <= 0) {
                    $estimatedCost = $costService->calculateCostWithDefaults($reservation, 1.0);
                    \Log::warning("Coût nul détecté pour la réservation #{$reservation->id}, utilisation des valeurs par défaut: {$estimatedCost}€");
                }
                
                // Vérifier que nous avons un montant valide
                if ($estimatedCost <= 0) {
                    throw new \Exception("Impossible de calculer un montant valide pour la réservation #{$reservation->id}. Vérifiez le plan tarifaire et les données de réservation.");
                }
                
                // Mettre à jour la réservation avec le coût calculé
                $reservation->update([
                    'estimated_cost' => $estimatedCost,
                    'actual_cost' => $estimatedCost,
                    'amount' => $estimatedCost,
                    'total_amount' => $estimatedCost, // Pour compatibilité avec ReservationTransactionService
                ]);
                
                // Utiliser ReservationTransactionService pour créer toutes les transactions avec parts cohérentes
                // Ce service garantit que admin_share + integrator_share + operator_share = total_amount
                // ET synchronise automatiquement les balances (admin, intégrateur, opérateur)
                $reservationTransactionService = app(\App\Services\ReservationTransactionService::class);
                $transactionResult = $reservationTransactionService->processReservationTransaction($reservation);
                
                if ($transactionResult['success']) {
                    \Log::info("✅ Transaction complète créée avec parts cohérentes et balances synchronisées pour la réservation", [
                        'reservation_id' => $reservation->id,
                        'transaction_id' => $transactionResult['main_transaction']->id ?? null,
                        'transaction_detail_id' => $transactionResult['transaction_detail']->id ?? null,
                        'admin_share' => $transactionResult['transaction_detail']->admin_share_amount ?? 0,
                        'integrator_share' => $transactionResult['transaction_detail']->integrator_share_amount ?? 0,
                        'operator_share' => $transactionResult['transaction_detail']->operator_share_amount ?? 0,
                        'total_amount' => $estimatedCost,
                        'note' => 'Les balances admin, intégrateur et opérateur ont été automatiquement synchronisées'
                    ]);
                    
                    // Vérification supplémentaire : s'assurer que les balances sont bien synchronisées
                    // (processReservationTransaction appelle déjà synchronizeBalancesAfterTransactionDetail,
                    // mais on fait une vérification pour être sûr)
                    if (isset($transactionResult['hierarchy']) && isset($transactionResult['main_transaction'])) {
                        try {
                            $balanceSyncService = app(\App\Services\BalanceSynchronizationService::class);
                            $hierarchy = $transactionResult['hierarchy'];
                            
                            // Synchronisation supplémentaire pour garantir la cohérence
                            if (isset($hierarchy['admin']) && $hierarchy['admin']) {
                                $adminUser = is_object($hierarchy['admin']) ? $hierarchy['admin'] : \App\Models\User::find($hierarchy['admin']);
                                if ($adminUser && $adminUser->hasRole(['admin', 'super_admin'])) {
                                    $balanceSyncService->synchronizeUserBalance($adminUser);
                                }
                            }
                            
                            if (isset($hierarchy['integrator']) && $hierarchy['integrator']) {
                                $integrator = $hierarchy['integrator'];
                                $integratorUser = is_object($integrator) && method_exists($integrator, 'user') 
                                    ? $integrator->user 
                                    : (is_object($integrator) && $integrator instanceof \App\Models\User ? $integrator : null);
                                if ($integratorUser && $integratorUser->hasRole('integrator')) {
                                    $balanceSyncService->synchronizeUserBalance($integratorUser);
                                }
                            }
                            
                            if (isset($hierarchy['operator']) && $hierarchy['operator']) {
                                $operatorUser = is_object($hierarchy['operator']) ? $hierarchy['operator'] : \App\Models\User::find($hierarchy['operator']);
                                if ($operatorUser && $operatorUser->hasRole(['operator', 'partner'])) {
                                    $balanceSyncService->synchronizeUserBalance($operatorUser);
                                }
                            }
                        } catch (\Exception $syncError) {
                            // Ne pas faire échouer le processus si la synchronisation supplémentaire échoue
                            // car elle a déjà été faite dans processReservationTransaction
                            \Log::warning('Synchronisation supplémentaire des balances échouée (non bloquante)', [
                                'reservation_id' => $reservation->id,
                                'error' => $syncError->getMessage()
                            ]);
                        }
                    }
                } else {
                    throw new \Exception('Échec de la création des transactions avec répartition: ' . ($transactionResult['error'] ?? 'Erreur inconnue'));
                }
                
                return;
            }
            
            // Pour les autres statuts, utiliser l'ancien système (compatibilité)
            // Utiliser le service centralisé de calcul des coûts
            $costService = app(\App\Services\ReservationCostCalculationService::class);
            
            // Calculer le coût estimé avec validation
            $estimatedCost = $costService->calculateReservationCost($reservation);
            
            // Si le coût est nul ou négatif, utiliser le calcul avec valeurs par défaut
            if ($estimatedCost <= 0) {
                $estimatedCost = $costService->calculateCostWithDefaults($reservation, 1.0);
                \Log::warning("Coût nul détecté pour la réservation #{$reservation->id}, utilisation des valeurs par défaut: {$estimatedCost}€");
            }
            
            // Vérifier que nous avons un montant valide
            if ($estimatedCost <= 0) {
                throw new \Exception("Impossible de calculer un montant valide pour la réservation #{$reservation->id}. Vérifiez le plan tarifaire et les données de réservation.");
            }
            
            // Utiliser le service de répartition des revenus
            $revenueService = app(\App\Services\RevenueDistributionCalculationService::class);
            $repartitionBreakdown = $revenueService->calculateRevenueDistribution($reservation, $estimatedCost);

            // Créer ou mettre à jour la transaction avec les données complètes
            $transactionData = [
                'user_id' => $reservation->user_id,
                'charging_point_id' => $reservation->charging_point_id,
                'pricing_plan_id' => $reservation->pricing_plan_id,
                'reservation_id' => $reservation->id,
                'amount' => $estimatedCost,
                'price_total' => $estimatedCost,
                'currency' => $reservation->pricingPlan->currency ?? 'EUR',
                'status' => 'pending',
                'repartition_breakdown' => $repartitionBreakdown,
                'business_profile_fee_breakdown' => $repartitionBreakdown['fees'] ?? null,
                'admin_commission' => $repartitionBreakdown['distribution']['admin_commission'] ?? 0,
                'integrator_commission' => $repartitionBreakdown['distribution']['integrator_commission'] ?? 0,
                'partner_commission' => $repartitionBreakdown['distribution']['partner_commission'] ?? 0,
                'meter_start' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Créer la transaction si elle n'existe pas
            if (!$reservation->transaction) {
                $transaction = $reservation->transaction()->create($transactionData);
            } else {
                // Mettre à jour la transaction existante
                $reservation->transaction->update($transactionData);
                $transaction = $reservation->transaction;
            }

            // Mettre à jour la réservation avec le coût calculé
            $reservation->update([
                'estimated_cost' => $estimatedCost,
                'actual_cost' => $estimatedCost,
                'amount' => $estimatedCost,
            ]);

            \Log::info("Transaction calculée et créée pour la réservation #{$reservation->id}", [
                'estimated_cost' => $estimatedCost,
                'transaction_id' => $transaction->id ?? 'N/A',
                'calculation_method' => 'legacy_service'
            ]);

        } catch (\Exception $e) {
            \Log::error("Erreur lors du calcul de la transaction pour la réservation #{$reservation->id}: " . $e->getMessage(), [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Calcule le coût estimé de la réservation
     */
    private function calculateEstimatedCost(Reservation $reservation, $pricingPlan)
    {
        $cost = 0;

        // Frais d'activation
        if ($pricingPlan->activation_fee) {
            $cost += $pricingPlan->activation_fee;
        }

        // Tarif de base
        if ($pricingPlan->base_rate) {
            $cost += $pricingPlan->base_rate;
        }

        // Calcul selon le type de réservation
        if ($reservation->reservation_type === 'kwh' && $pricingPlan->price_per_kwh) {
            $cost += $reservation->reservation_value * $pricingPlan->price_per_kwh;
        } elseif ($reservation->reservation_type === 'minute' && $pricingPlan->price_per_minute) {
            $cost += $reservation->reservation_value * $pricingPlan->price_per_minute;
        }

        // Appliquer la TVA si configurée
        if ($pricingPlan->vatRate && $pricingPlan->vatRate->rate > 0) {
            $cost = $cost * (1 + ($pricingPlan->vatRate->rate / 100));
        }

        return round($cost, 2);
    }

    /**
     * Calcule la répartition des revenus entre les parties prenantes
     */
    private function calculateRevenueRepartition($totalAmount, $chargingPoint, $pricingPlan)
    {
        // Utiliser le service de calcul détaillé des frais
        $feeCalculationService = app(\App\Services\DetailedFeeCalculationService::class);
        $calculation = $feeCalculationService->calculateChargingPointFees($chargingPoint, $totalAmount);
        
        // Retourner la répartition au format attendu
        $repartition = [
            'admin' => $calculation['distribution']['admin_part'],
            'integrator' => $calculation['distribution']['integrator_part'],
            'partner' => $calculation['distribution']['partner_part'],
            'operator' => $calculation['distribution']['operator_part'],
            'total_fees' => $calculation['fees']['total_fees'],
            'available_revenue' => $calculation['distribution']['available_revenue'],
            'breakdown' => $calculation['breakdown']
        ];

        return $repartition;
        $totalRepartition = array_sum($repartition);
        if (abs($totalRepartition - $baseAmount) > 0.01) {
            // Ajuster l'opérateur pour compenser les différences d'arrondi
            $repartition['operator'] = round($baseAmount - array_sum(array_diff_key($repartition, ['operator' => 0])), 2);
        }

        return $repartition;
    }

    /**
     * Vérifie si l'utilisateur est un client (user ou client, sans rôle système).
     * Inclut aussi les utilisateurs sans rôle ou avec rôles personnalisés (fallback pour afficher leurs réservations).
     */
    private function isClientUser($user): bool
    {
        if (!$user) {
            return false;
        }
        $userRoles = $user->getRoleNames()->map(fn($r) => strtolower($r))->toArray();
        $systemRoles = ['admin', 'super_admin', 'integrator', 'operator', 'partner'];
        $clientRoles = ['user', 'client'];
        $hasSystemRole = !empty(array_intersect($userRoles, $systemRoles));
        $hasClientRole = !empty(array_intersect($userRoles, $clientRoles));
        // Client si : a un rôle client OU n'a aucun rôle système (fallback pour utilisateurs sans rôle)
        return !$hasSystemRole && ($hasClientRole || empty($userRoles));
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // unify reservations view for authenticated users and guests
        // accept optional search term for email or phone
        $search = $request->input('search');
        $user = auth()->user();
        $query = Reservation::query();

        // Appliquer le filtrage hiérarchique selon le rôle
        $adminRoles = ['admin', 'Admin', 'super_admin', 'super_admin', 'Super Admin', 'Super-Admin'];

        if ($user->hasRole($adminRoles)) {
            // Les admins voient toutes les réservations (pas de restriction de base)
        } elseif ($user->hasRole('integrator')) {
            // Les intégrateurs voient toutes les réservations sur leurs bornes (clients + opérateurs)
            $integratorId = $user->integrator_id ?? \App\Models\Integrator::where('user_id', $user->id)->value('id');
            if ($integratorId) {
                $query->whereHas('chargingPoint', function($cp) use ($integratorId) {
                    $cp->where('integrator_id', $integratorId)
                       ->orWhereHas('partner', function($p) use ($integratorId) {
                           $p->where('integrator_id', $integratorId);
                       })
                       ->orWhereHas('group', function($g) use ($integratorId) {
                           $g->where('integrator_id', $integratorId)
                             ->orWhereHas('partner', function($p) use ($integratorId) {
                                 $p->where('integrator_id', $integratorId);
                             });
                       });
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($user->hasRole('partner')) {
            // Les partenaires voient les réservations des bornes de leur partenaire
            $query->where(function($q) use ($user) {
                // Réservations sur les bornes directement liées au partenaire
                $q->whereHas('chargingPoint', function($cp) use ($user) {
                    $cp->where('partner_id', $user->partner_id);
                })
                // Réservations sur les bornes dans des groupes du partenaire
                ->orWhereHas('chargingPoint.group', function($groupQuery) use ($user) {
                    $groupQuery->where('partner_id', $user->partner_id);
                })
                // Réservations personnelles du partenaire (fallback)
                ->orWhere('user_id', $user->id);
            });
        } elseif ($user->hasRole('operator')) {
            // Les opérateurs voient TOUTES les réservations sur les bornes qu'ils gèrent (clients inclus)
            $groupIds = \App\Models\Group::where('user_id', $user->id)->pluck('id')->toArray();
            $query->where(function($q) use ($user, $groupIds) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('chargingPoint', function($cp) use ($user, $groupIds) {
                      $cp->withTrashed()->where(function($cpq) use ($user, $groupIds) {
                          $cpq->where('user_id', $user->id)
                              ->orWhere('created_by', $user->id)
                              ->orWhere('created_by_id', $user->id);
                          if (!empty($groupIds)) {
                              $cpq->orWhereIn('group_id', $groupIds);
                          }
                          if ($user->integrator_id) {
                              $cpq->orWhere('integrator_id', $user->integrator_id);
                          }
                      });
                  });
            });
        } else {
            // Clients et tous les autres : leurs réservations (user_id) + réservations invitées (guest_email/guest_phone)
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if (!empty(trim($user->email ?? ''))) {
                    $q->orWhereRaw('LOWER(TRIM(guest_email)) = ?', [strtolower(trim($user->email))]);
                }
                if (!empty(trim($user->phone ?? ''))) {
                    $q->orWhere('guest_phone', trim($user->phone));
                }
            });
            
            // Filtres pour les clients
            $statusFilter = $request->input('status');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');
            
            // Filtre par statut
            if ($statusFilter) {
                $query->where('status', $statusFilter);
            }
            
            // Filtre par date de début
            if ($dateFrom) {
                $query->whereDate('start_time', '>=', $dateFrom);
            }
            
            // Filtre par date de fin
            if ($dateTo) {
                $query->whereDate('start_time', '<=', $dateTo);
            }
            
            // Tri
            $allowedSortFields = ['created_at', 'start_time', 'estimated_cost', 'status'];
            $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'created_at';
            $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? strtolower($sortOrder) : 'desc';
            $query->orderBy($sortBy, $sortOrder);
            
            // Pour les clients, la recherche filtre parmi leurs réservations (scope déjà appliqué ci-dessus)
            if (!empty($search)) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->whereHas('chargingPoint', function ($cp) use ($search) {
                        $cp->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('pricingPlan', function ($pp) use ($search) {
                        $pp->where('name', 'like', "%{$search}%");
                    })
                    ->orWhere('status', 'like', "%{$search}%");
                });
            }
        }

        // Appliquer le filtre de recherche pour admin/integrator/partner/operator (les clients sont déjà traités ci-dessus)
        $hasSystemRole = $user->hasRole(['admin', 'super_admin', 'integrator', 'operator', 'partner']);
        if (!empty($search) && $hasSystemRole) {
            $query->where(function ($q) use ($search) {
                $q->where('guest_email', 'like', "%{$search}%")
                  ->orWhere('guest_phone', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('email', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('chargingPoint', function ($cp) use ($search) {
                      $cp->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Optimiser les requêtes avec eager loading selon le rôle
        if ($hasSystemRole) {
            // Admin, intégrateur, opérateur, partenaire
            $reservations = $query->with(['chargingPoint', 'pricingPlan', 'user'])
                ->orderBy('created_at', 'desc')
                ->paginate(10);
        } else {
            // Clients : tri déjà appliqué, eager loading optimisé
            $reservations = $query->with([
                'chargingPoint.station',
                'chargingPoint.group',
                'pricingPlan.vatRate',
                'transaction'
            ])->paginate(10);
        }
        
        // Statuts disponibles pour les filtres (clients uniquement)
        $statuses = $hasSystemRole ? [] : \App\Enums\ReservationStatus::cases();
        
        return view('reservations.index', [
            'reservations' => $reservations,
            'search' => $search,
            'statusFilter' => $request->input('status'),
            'dateFrom' => $request->input('date_from'),
            'dateTo' => $request->input('date_to'),
            'sortBy' => $request->input('sort_by', 'created_at'),
            'sortOrder' => $request->input('sort_order', 'desc'),
            'statuses' => $statuses,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, $chargingPointId)
    {
        // Ensure we always return JSON for AJAX requests
        if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json') {
            $request->headers->set('Accept', 'application/json');
        }
        
        \Log::info('Reservation store method called', [
            'charging_point_id' => $chargingPointId,
            'request_data' => $request->all(),
            'request_headers' => $request->headers->all(),
            'user_id' => auth()->id(),
            'method' => $request->method(),
            'url' => $request->url(),
            'csrf_token_from_header' => $request->header('X-CSRF-TOKEN'),
            'csrf_token_from_input' => $request->input('_token'),
            'session_token' => $request->session()->token(),
            'session_id' => $request->session()->getId(),
            'is_ajax' => $request->ajax(),
            'wants_json' => $request->wantsJson(),
            'accept_header' => $request->header('Accept')
        ]);
        
        // Récupérer le point de charge
        $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
        
        try {
            // Debug: Log the incoming request data before validation
            \Log::info('Reservation validation - Raw request data:', [
                'reservation_type' => $request->input('reservation_type'),
                'reservation_value' => $request->input('reservation_value'),
                'payment_type' => $request->input('payment_type'),
                'pricing_plan_id' => $request->input('pricing_plan_id'),
                'charging_point_id' => $request->input('charging_point_id'),
                'all_data' => $request->all()
            ]);
            
            // Vérification préliminaire des données critiques
            if (!$request->has('pricing_plan_id') || !$request->has('charging_point_id')) {
                return response()->json([
                    'message' => 'Données de réservation manquantes. Veuillez rafraîchir la page et réessayer.',
                    'error' => 'missing_critical_data'
                ], 400);
            }
            
            // Vérification de l'existence des entités liées
            $chargingPointExists = \App\Models\ChargingPoint::where('id', $request->charging_point_id)->exists();
            $pricingPlanExists = \App\Models\PricingPlan::where('id', $request->pricing_plan_id)->exists();
            
            if (!$chargingPointExists) {
                return response()->json([
                    'message' => 'Le point de charge sélectionné n\'existe pas.',
                    'error' => 'charging_point_not_found'
                ], 404);
            }
            
            if (!$pricingPlanExists) {
                return response()->json([
                    'message' => 'Le plan tarifaire sélectionné n\'existe pas.',
                    'error' => 'pricing_plan_not_found'
                ], 404);
            }
            
            // Vérification préliminaire pour les clients : balance ne doit pas être négative
            $user = auth()->user();
            if ($user && $user->hasRole('client')) {
                $wallet = $user->getOrCreateWallet();
                $currentBalance = (float) $wallet->balance;
                
                if ($currentBalance < 0) {
                    return response()->json([
                        'message' => 'Votre solde est insuffisant. Veuillez recharger votre compte avant de créer une réservation.',
                        'error' => 'insufficient_balance_negative',
                        'current_balance' => $currentBalance
                    ], 422);
                }
            }
            
          // Validate the request data
          if ($request->filled('participants') && is_string($request->input('participants'))) {
              $decodedParticipants = json_decode($request->input('participants'), true);
              if (json_last_error() === JSON_ERROR_NONE) {
                  $request->merge(['participants' => $decodedParticipants]);
              }
          }

          $validated = $request->validate([
              'pricing_plan_id' => 'required|exists:pricing_plans,id',
              'reservation_type' => 'required|string|in:kwh,minute',
              'reservation_value' => 'required|numeric|min:0.01',
              'payment_method' => 'required|string|in:cmi,stripe,credit,prepaid_credit,postpaid_credit,offline',
              'start_time' => 'nullable|string',
              'guest_email' => 'nullable|email',
              'guest_phone' => 'nullable|string',
              'charging_point_id' => 'required|exists:charging_points,id',
              'status' => 'nullable|string|in:pending,confirmed',
              'participants' => 'nullable|array',
              'participants.*.user_id' => 'required_with:participants|exists:users,id',
              'participants.*.share_type' => 'required_with:participants|in:percentage,fixed',
              'participants.*.share_value' => 'required_with:participants|numeric|min:0',
          ], [
                'pricing_plan_id.required' => 'L\'identifiant du plan tarifaire est requis.',
                'pricing_plan_id.exists' => 'Le plan tarifaire sélectionné n\'existe pas.',
                'reservation_type.required' => 'Le type de réservation est requis.',
                'reservation_type.string' => 'Le type de réservation doit être une chaîne de caractères.',
                'reservation_type.in' => 'Le type de réservation doit être "kwh" ou "minute" uniquement.',
                'reservation_value.required' => 'La valeur de réservation est requise.',
                'reservation_value.numeric' => 'La valeur de réservation doit être un nombre.',
                'reservation_value.min' => 'La valeur de réservation doit être supérieure à 0.',
                'payment_method.required' => 'La méthode de paiement est requise.',
                'payment_method.string' => 'La méthode de paiement doit être une chaîne de caractères.',
                'payment_method.in' => 'La méthode de paiement doit être "cmi", "stripe", "credit", "prepaid_credit", "postpaid_credit" ou "offline".',
                'guest_email.email' => 'L\'adresse email invité doit être valide.',
                'charging_point_id.required' => 'L\'identifiant du point de charge est requis.',
                'charging_point_id.exists' => 'Le point de charge sélectionné n\'existe pas.',
            ]);
            
            // Debug: Log the validated data
            \Log::info('Reservation validation - Validated data:', $validated);

            // Get the pricing plan from the request
            $plan = PricingPlan::find($validated['pricing_plan_id']);
            if (!$plan) {
                return response()->json([
                    'message' => 'Le plan tarifaire sélectionné n\'existe pas.',
                    'error' => 'invalid_pricing_plan'
                ], 422);
            }
            
            // Vérifier la disponibilité du point de charge
            if (!$chargingPoint->is_available) {
                return response()->json([
                    'message' => 'Ce point de charge n\'est pas disponible pour le moment.',
                    'error' => 'charging_point_unavailable'
                ], 422);
            }

            // Gardes de période de validité
            $now = now();
            if ($plan->valid_from && $now->lt($plan->valid_from)) {
                return response()->json([
                    'message' => 'Ce plan tarifaire n\'est pas encore disponible.',
                    'error' => 'plan_not_started'
                ], 422);
            }
            if ($plan->valid_until && $now->gt($plan->valid_until)) {
                return response()->json([
                    'message' => 'Ce plan tarifaire n\'est plus disponible.',
                    'error' => 'plan_expired'
                ], 422);
            }

            $value = $validated['reservation_value'];
            $reservationType = $validated['reservation_type'];

            // Additional server-side validation for limits
            if ($reservationType === 'minute' && $plan && $plan->max_duration) {
                if ($value > $plan->max_duration) {
                    return response()->json([
                        'message' => "La durée de réservation ne peut pas dépasser {$plan->max_duration} minutes.",
                        'error' => 'limit_exceeded'
                    ], 422);
                }
            } elseif ($reservationType === 'kwh' && $plan && $plan->max_duration && $chargingPoint->power_output) {
                $maxEnergy = $chargingPoint->power_output * ($plan->max_duration / 60);
                if ($value > $maxEnergy) {
                    return response()->json([
                        'message' => "La quantité d'énergie ne peut pas dépasser " . number_format($maxEnergy, 2) . " kWh.",
                        'error' => 'limit_exceeded'
                    ], 422);
                }
            }

            // Prepare data for the service
            $reservationData = [
                'charging_point_id' => $chargingPoint->id,
                'pricing_plan_id' => $plan->id,
                'reservation_type' => $reservationType,
                'reservation_value' => (float) $value,
                'payment_method' => $validated['payment_method'],
                'payment_type' => $validated['payment_method'], // Alias pour compatibilité
                'start_time' => $validated['start_time'] ?? null,
                'guest_email' => $validated['guest_email'] ?? null,
                'guest_phone' => $validated['guest_phone'] ?? null,
                'participants' => $validated['participants'] ?? null,
            ];

            // Add type-specific data
            if ($reservationType === 'kwh') {
                $reservationData['energy_kwh'] = (float) $value;
            } elseif ($reservationType === 'minute') {
                $reservationData['duration_minutes'] = (float) $value;
            }

            // Create the reservation using the service
            $result = $this->reservationService->createReservation($reservationData, $chargingPoint);

            if ($result['status'] === 'redirect') {
                return redirect()->away($result['url']);
            } elseif ($result['status'] === 'success') {
                // Handle successful reservation creation
                if ($result['reservation']) {
                    // For AJAX requests, return JSON with redirect URL
                    if ($request->ajax() || $request->wantsJson() || $request->header('Accept') === 'application/json') {
                        $reservation = $result['reservation'];
                        $response = [
                            'success' => true,
                            'message' => $result['message'],
                            'reservation' => $reservation,
                            'reservation_id' => $reservation->id,
                            'redirect_url' => route('reservations.thank-you', $reservation),
                            'payment_processed' => $result['payment_processed'] ?? false,
                        ];
                        if (isset($result['remaining_balance'])) {
                            $response['remaining_balance'] = $result['remaining_balance'];
                        }
                        if (isset($result['formatted_balance'])) {
                            $response['formatted_balance'] = $result['formatted_balance'];
                        }
                        return response()->json($response, 201);
                    } else {
                        // For regular web requests, redirect to thank you page
                        return redirect()->route('reservations.thank-you', $result['reservation'])
                            ->with('success', $result['message']);
                    }
                } else {
                    return response()->json([
                        'success' => true,
                        'message' => $result['message'],
                        'order_id' => $result['order_id'] ?? null,
                        'reservation' => $result['reservation'] ?? null
                    ], 201);
                }
            } else {
                return response()->json([
                    'message' => $result['message'] ?? 'Erreur lors de la création de la réservation',
                    'error' => $result['error'] ?? null
                ], 500);
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            \Log::error('Database error in reservation controller: ' . $e->getMessage(), [
                'charging_point_id' => $chargingPoint->id,
                'request_data' => $request->all(),
                'exception' => $e
            ]);

            $errorMessage = 'Erreur de connexion à la base de données.';
            if (str_contains($e->getMessage(), 'Connection refused') || 
                str_contains($e->getMessage(), 'server has gone away')) {
                $errorMessage = 'Le serveur de base de données est temporairement indisponible.';
            }

            return response()->json([
                'message' => $errorMessage,
                'error' => 'database_connection_error',
                'details' => 'Veuillez vérifier votre connexion internet et réessayer.'
            ], 503); // Service Unavailable
        } catch (\Exception $e) {
            \Log::error('Error creating reservation: ' . $e->getMessage(), [
                'charging_point_id' => $chargingPoint->id,
                'request_data' => $request->all(),
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            // Ensure we always return JSON, even for unexpected errors
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la création de la réservation.',
                'error' => 'server_error',
                'details' => 'Veuillez vérifier votre connexion internet et réessayer.',
                'debug_info' => config('app.debug') ? [
                    'error_message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Reservation $reservation)
    {
        try {
            // Vérification pour les clients et utilisateurs publics (rôle 'user') : ne peuvent voir que leurs propres réservations
            $user = auth()->user();
            if ($user && (($user->hasRole('client') || ($user->hasRole('user') && !$user->hasAnyRole(['admin', 'super_admin', 'integrator', 'operator', 'partner']))))) {
                if ($reservation->user_id !== $user->id) {
                    \Log::warning('Client/User attempted to view another user\'s reservation', [
                        'user_id' => $user->id,
                        'user_roles' => $user->getRoleNames()->toArray(),
                        'reservation_id' => $reservation->id,
                        'reservation_user_id' => $reservation->user_id
                    ]);
                    
                    abort(403, 'Vous n\'êtes pas autorisé à voir cette réservation.');
                }
            }
            
            // Rafraîchir depuis la DB pour avoir les données à jour (payment_status, etc.)
            $reservation->refresh();
            $reservation->load(['chargingPoint.station', 'pricingPlan.vatRate', 'transaction']);

            // Corriger statut réservation + transaction si payée (sync directe en base)
            $reservation->ensureTransactionConfirmedIfPaid();
            $reservation->refresh();
            $reservation->load(['transaction', 'chargingPoint.station', 'pricingPlan.vatRate']);

            // Paiement automatique par solde (une seule fois) : si client, réservation en attente, solde suffisant, méthode crédit
            $paymentMethod = $reservation->payment_method ?? $reservation->payment_type;
            $isClientViewingOwn = $user && ($user->hasRole('client') || ($user->hasRole('user') && !$user->hasAnyRole(['admin', 'super_admin', 'integrator', 'operator', 'partner']))) && $reservation->user_id === $user->id;
            $statusValue = $reservation->status instanceof \App\Enums\ReservationStatus ? $reservation->status->value : $reservation->status;
            $isPending = in_array($statusValue, ['pending', 'pending_confirmation']);
            $canAutoPayByBalance = $isClientViewingOwn && $isPending && !$reservation->isPaidByBalance()
                && in_array($paymentMethod, ['credit', 'prepaid_credit', 'offline']);

            if ($canAutoPayByBalance) {
                $wallet = $user->getOrCreateWallet();
                $wallet->refresh();
                $estimatedCost = (float) ($reservation->estimated_cost ?? $reservation->amount ?? 0);
                if ($estimatedCost > 0 && $wallet->hasSufficientBalance($estimatedCost)) {
                    try {
                        $paymentResult = $this->creditPaymentService->processPrepaidPayment($reservation);
                        if ($paymentResult['success']) {
                            return redirect()->route('reservations.show', $reservation)
                                ->with('success', $paymentResult['already_paid'] ?? false ? 'Réservation déjà payée' : 'Paiement par solde effectué avec succès');
                        }
                    } catch (\Exception $e) {
                        \Log::warning('Auto-paiement solde échoué', ['reservation_id' => $reservation->id, 'error' => $e->getMessage()]);
                    }
                }
            }

            // Synchroniser transaction + réservation si payée par solde (évite "En attente")
            $reservation->ensureTransactionConfirmedIfPaid();
            $reservation->refresh();
            $reservation->load(['transaction', 'chargingPoint.station', 'pricingPlan.vatRate']);

            // Rafraîchir le solde client pour affichage à jour (réservations payées par solde)
            if ($user && $reservation->user_id === $user->id && $reservation->isPaidByBalance()) {
                $user->unsetRelation('wallet');
                $user->getOrCreateWallet()->refresh();
            }

            return view('reservations.show', compact('reservation'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('Reservation not found', [
                'reservation_id' => $reservation->id ?? 'unknown',
                'user_id' => auth()->id(),
                'url' => request()->url()
            ]);
            
            return redirect()->route('reservations.index')
                ->with('error', 'Cette réservation n\'existe pas ou a été supprimée.');
        } catch (\Exception $e) {
            \Log::error('Error showing reservation: ' . $e->getMessage(), [
                'reservation_id' => $reservation->id ?? 'unknown',
                'user_id' => auth()->id(),
                'exception' => $e
            ]);
            
            return redirect()->route('reservations.index')
                ->with('error', 'Une erreur est survenue lors de l\'affichage de la réservation.');
        }
    }

    /**
     * Display the thank you page after successful reservation creation.
     * Uses {id} to avoid 404 from implicit model binding when reservation doesn't exist.
     */
    public function thankYou(Request $request, int $id)
    {
        try {
            $reservation = $this->findReservationForThankYou($id);

            if (!$reservation) {
                $existsInDb = \DB::table('reservations')->where('id', $id)->exists();
                \Log::warning('Reservation does not exist for thank-you page', [
                    'reservation_id' => $id,
                    'user_id' => auth()->check() ? auth()->id() : null,
                    'url' => request()->url(),
                    'exists_in_raw_table' => $existsInDb
                ]);

                return view('reservations.thank-you', [
                    'reservation' => null,
                    'reservation_not_found' => true,
                    'reservation_id_requested' => $id,
                    'thankYouStatus' => null,
                ]);
            }

            $reservation = $this->loadThankYouRelations($reservation);

            return view('reservations.thank-you', [
                'reservation' => $reservation,
                'thankYouStatus' => $this->buildThankYouStatus($reservation, $request->query('session_id')),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::warning('Reservation not found for thank you page', [
                'reservation_id' => $id,
                'user_id' => auth()->check() ? auth()->id() : null,
                'url' => request()->url(),
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return view('reservations.thank-you', [
                'reservation' => null,
                'reservation_not_found' => true,
                'reservation_id_requested' => $id,
                'thankYouStatus' => null,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error showing thank you page', [
                'reservation_id' => $id,
                'user_id' => auth()->check() ? auth()->id() : null,
                'url' => request()->url(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if (config('app.debug')) {
                return response()->json([
                    'error' => 'Erreur lors de l\'affichage de la page de confirmation',
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ], 500);
            }
            
            return redirect()->route('home')
                ->with('error', 'Une erreur est survenue lors de l\'affichage de la page de confirmation.');
        }
    }

    public function thankYouStatus(Request $request, int $id)
    {
        try {
            $reservation = $this->findReservationForThankYou($id);

            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Réservation introuvable.',
                ], 404);
            }

            $reservation = $this->loadThankYouRelations($reservation);

            return response()->json([
                'success' => true,
                'status' => $this->buildThankYouStatus($reservation, $request->query('session_id')),
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error loading thank-you status', [
                'reservation_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de récupérer le statut de la réservation.',
            ], 500);
        }
    }

    protected function findReservationForThankYou(int $id): ?Reservation
    {
        $reservation = Reservation::find($id)
            ?? Reservation::withoutGlobalScopes()->find($id)
            ?? Reservation::on(config('database.default'))->find($id);

        if (!$reservation) {
            for ($i = 0; $i < 3 && !$reservation; $i++) {
                usleep(150000);
                $reservation = Reservation::withoutGlobalScopes()->find($id);
            }
        }

        if (!$reservation) {
            $row = \DB::table('reservations')->where('id', $id)->first();
            if ($row) {
                $reservation = Reservation::hydrate([(array) $row])->first();
                if ($reservation) {
                    $reservation->exists = true;
                }
            }
        }

        return $reservation;
    }

    protected function loadThankYouRelations(Reservation $reservation): Reservation
    {
        try {
            $reservation->load([
                'chargingPoint.station',
                'pricingPlan.vatRate',
                'transaction',
                'activeChargingSession',
                'chargingSessions' => function ($query) {
                    $query->latest('started_at');
                },
                'latestAutoRemoteStartLog',
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Error loading thank-you relations', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);
        }

        $reservation->ensureTransactionConfirmedIfPaid();
        $reservation->refresh();
        $reservation->load([
            'chargingPoint.station',
            'pricingPlan.vatRate',
            'transaction',
            'activeChargingSession',
            'chargingSessions' => function ($query) {
                $query->latest('started_at');
            },
            'latestAutoRemoteStartLog',
        ]);

        return $reservation;
    }

    protected function buildThankYouStatus(Reservation $reservation, ?string $sessionId = null): array
    {
        $transaction = $reservation->transaction;
        $chargingSession = $reservation->activeChargingSession
            ?: $reservation->chargingSessions->first();
        $latestLog = $reservation->latestAutoRemoteStartLog;

        $paymentConfirmed = $reservation->isPaid()
            || strtolower((string) ($transaction->status ?? '')) === 'completed';

        $remoteStartStatus = $this->determineThankYouRemoteStartStatus($reservation, $chargingSession, $latestLog, $paymentConfirmed);
        $remoteStartMessage = $this->resolveRemoteStartMessage($remoteStartStatus, $reservation, $latestLog);

        return [
            'reservation_id' => $reservation->id,
            'reservation_status' => $reservation->status instanceof \App\Enums\ReservationStatus
                ? $reservation->status->value
                : $reservation->status,
            'display_status' => $reservation->getDisplayStatusLabel(),
            'payment' => [
                'status' => strtoupper((string) ($reservation->payment_status ?? 'PENDING')),
                'confirmed' => $paymentConfirmed,
                'confirmed_at' => ($reservation->payment_confirmed_at ?? $transaction?->completed_at)?->toIso8601String(),
                'gateway_transaction_id' => $reservation->payment_gateway_transaction_id,
                'transaction_status' => $transaction?->status,
                'stripe_session_id' => $transaction?->stripe_session_id ?? $sessionId,
                'amount' => (float) ($transaction?->amount ?? $reservation->estimated_cost ?? $reservation->amount ?? 0),
                'currency' => $transaction?->currency ?? 'EUR',
            ],
            'remote_start' => [
                'status' => $remoteStartStatus,
                'message' => $remoteStartMessage,
                'initiated_at' => ($reservation->session_initiated_at ?? $chargingSession?->started_at)?->toIso8601String(),
                'last_error' => $reservation->last_error ?? $latestLog?->error_message,
                'charging_session_id' => $chargingSession?->id ?? $reservation->charging_session_id,
                'charging_session_uuid' => $chargingSession?->session_id,
                'steve_transaction_id' => $chargingSession?->steve_transaction_id ?? $latestLog?->transaction_id,
                'latest_log_status' => $latestLog?->status,
                'latest_log_message' => $latestLog?->message ?? $latestLog?->error_message,
                'scheduled_for' => $reservation->start_time?->toIso8601String(),
            ],
            'should_poll' => !$paymentConfirmed || in_array($remoteStartStatus, ['queued', 'processing'], true),
        ];
    }

    protected function determineThankYouRemoteStartStatus(
        Reservation $reservation,
        $chargingSession,
        $latestLog,
        bool $paymentConfirmed
    ): string {
        if (!$paymentConfirmed) {
            return 'awaiting_payment';
        }

        if ($chargingSession && $chargingSession->isActive()) {
            return 'success';
        }

        $status = (string) ($reservation->session_initiation_status ?? '');
        if ($status !== '') {
            return $status;
        }

        if ($latestLog) {
            return match ($latestLog->status) {
                'success' => 'success',
                'failed', 'error' => 'failed',
                'retry' => 'processing',
                'skipped' => 'scheduled',
                default => 'queued',
            };
        }

        if ($reservation->isApproved()) {
            return $reservation->canStartNow() ? 'queued' : 'scheduled';
        }

        return 'awaiting_approval';
    }

    protected function resolveRemoteStartMessage(string $status, Reservation $reservation, $latestLog): string
    {
        return match ($status) {
            'awaiting_payment' => 'Votre paiement est en cours de confirmation par Stripe. Cette page se mettra à jour automatiquement.',
            'awaiting_approval' => 'Le paiement est confirmé, mais la réservation attend encore sa validation finale avant le démarrage à distance.',
            'queued' => 'La borne a été mise en file pour un démarrage à distance via SteVe.',
            'scheduled' => 'Le démarrage à distance sera déclenché automatiquement à l\'heure prévue de votre réservation.',
            'processing' => 'Le démarrage à distance est en cours de traitement.',
            'success' => 'La commande de démarrage à distance a été acceptée et la session de charge est active.',
            'failed' => $reservation->last_error
                ?: $latestLog?->error_message
                ?: 'Le démarrage à distance a échoué. Une intervention manuelle peut être nécessaire.',
            default => 'Le statut du démarrage à distance est en cours de mise à jour.',
        };
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create($chargingPointId)
    {
        $chargingPoint = ChargingPoint::with('pricingPlan.vatRate')->findOrFail($chargingPointId);
        $plan = $chargingPoint->pricingPlan;

        if (!$plan) {
            return redirect()->back()->with('error', 'Pricing plan not found for this charging point.');
        }

        // Ensure VAT rate is loaded, use default if none set
        if (!$plan->vatRate) {
            $plan->vatRate = \App\Models\VatRate::getDefault();
        }

        $limits = [
            'max_duration' => $plan->max_duration ?? null,
            'max_energy' => null, // Initialize as null
            'reservation_options' => [
                'kwh' => [], // Will be populated based on plan type
                'minute' => [], // Will be populated based on plan type
            ],
        ];

        // Calculate max_energy if max_duration and charging point power output are available
        if ($plan->max_duration && $chargingPoint->power_output) {
            $limits['max_energy'] = $chargingPoint->power_output * ($plan->max_duration / 60); // Convert minutes to hours
        }

        // Generate reservation options based on plan rate_type
        $this->generateReservationOptions($plan, $chargingPoint, $limits);

        return view('reservations.create', compact('chargingPoint', 'plan', 'limits'));
    }

    /**
     * Generate appropriate reservation options based on plan rate_type and limits
     */
    private function generateReservationOptions($plan, $chargingPoint, &$limits)
    {
        $rateType = $plan->rate_type;
        $maxDuration = $plan->max_duration;
        $maxEnergy = $limits['max_energy'];
        $powerOutput = $chargingPoint->power_output;

        \Log::info('Generating reservation options', [
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'rate_type' => $rateType,
            'max_duration' => $maxDuration,
            'max_energy' => $maxEnergy,
            'power_output' => $powerOutput
        ]);

        // Clear existing options
        $limits['reservation_options']['kwh'] = [];
        $limits['reservation_options']['minute'] = [];

        switch ($rateType) {
            case 'time':
            case 'minute':
                // Plan is time-based, show only minute options
                $this->generateMinuteOptions($maxDuration, $limits['reservation_options']['minute']);
                \Log::info('Generated minute options', $limits['reservation_options']['minute']);
                break;

            case 'energy':
            case 'kwh':
                // Plan is energy-based, show only kWh options
                $this->generateKwhOptions($maxEnergy, $powerOutput, $limits['reservation_options']['kwh']);
                \Log::info('Generated kWh options', $limits['reservation_options']['kwh']);
                break;

            case 'mixed':
            case 'both':
                // Plan supports both, show both options but respect limits
                $this->generateMinuteOptions($maxDuration, $limits['reservation_options']['minute']);
                $this->generateKwhOptions($maxEnergy, $powerOutput, $limits['reservation_options']['kwh']);
                \Log::info('Generated both options', $limits['reservation_options']);
                break;

            default:
                // Default behavior: show both options
                $this->generateMinuteOptions($maxDuration, $limits['reservation_options']['minute']);
                $this->generateKwhOptions($maxEnergy, $powerOutput, $limits['reservation_options']['kwh']);
                \Log::info('Generated default options', $limits['reservation_options']);
                break;
        }
    }

    /**
     * Generate minute-based reservation options
     */
    private function generateMinuteOptions($maxDuration, &$minuteOptions)
    {
        if ($maxDuration) {
            // Generate options based on max duration
            $step = max(15, round($maxDuration / 8)); // At least 15 minutes, max 8 options
            for ($i = $step; $i <= $maxDuration; $i += $step) {
                $minuteOptions[] = $i;
            }
            // Ensure max duration is included
            if (!in_array($maxDuration, $minuteOptions)) {
                $minuteOptions[] = $maxDuration;
            }
        } else {
            // Default options if no max duration
            $minuteOptions = [15, 30, 60, 90, 120, 180];
        }
        
        // Sort and ensure unique values
        $minuteOptions = array_unique($minuteOptions);
        sort($minuteOptions);
    }

    /**
     * Generate kWh-based reservation options
     */
    private function generateKwhOptions($maxEnergy, $powerOutput, &$kwhOptions)
    {
        if ($maxEnergy) {
            // Generate options based on max energy
            $step = max(5, round($maxEnergy / 8)); // At least 5 kWh, max 8 options
            for ($i = $step; $i <= $maxEnergy; $i += $step) {
                $kwhOptions[] = $i;
            }
            // Ensure max energy is included
            if (!in_array($maxEnergy, $kwhOptions)) {
                $kwhOptions[] = $maxEnergy;
            }
        } else {
            // Default options if no max energy
            $kwhOptions = [10, 20, 30, 50, 75, 100];
        }
        
        // Sort and ensure unique values
        $kwhOptions = array_unique($kwhOptions);
        sort($kwhOptions);
    }

    public function guestIndex()
    {
        return view('reservations.guest-index', [
            'pageTitle' => 'Accès aux Réservations',
        ]);
    }

    public function guestSearch(Request $request)
    {
        $request->validate([
            'email' => 'required_without:phone|nullable|email',
            'phone' => 'required_without:email|nullable|string',
        ]);

        $query = Reservation::with(['chargingPoint', 'pricingPlan', 'user']);

        // Rechercher dans les champs guest_email et guest_phone
        if ($request->filled('email')) {
            $query->where(function($q) use ($request) {
                $q->where('guest_email', 'like', '%' . $request->email . '%')
                  ->orWhereHas('user', function($userQuery) use ($request) {
                      $userQuery->where('email', 'like', '%' . $request->email . '%');
                  });
            });
        }

        if ($request->filled('phone')) {
            $query->where(function($q) use ($request) {
                $q->where('guest_phone', 'like', '%' . $request->phone . '%')
                  ->orWhereHas('user', function($userQuery) use ($request) {
                      $userQuery->where('phone', 'like', '%' . $request->phone . '%');
                  });
            });
        }

        $reservations = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'reservations' => $reservations,
        ]);
    }

    public function accessReservationsForm()
    {
        // TODO: Implement this method
    }

    public function searchReservations()
    {
        // TODO: Implement this method
    }

    /**
     * Calculate cost for public users (AJAX)
     */
    public function calculateCost(Request $request, $chargingPointId)
    {
        $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
        
        try {
            $validated = $request->validate([
                'reservation_type' => 'required|in:minute,kwh',
                'reservation_value' => 'required|numeric|min:0.1',
            ]);

            $plan = $chargingPoint->pricingPlan;
            if (!$plan) {
                return response()->json([
                    'error' => 'Aucun plan tarifaire actif trouvé pour cette borne.'
                ], 404);
            }

            // Calculate estimated cost
            $estimatedCost = 0;
            $estimatedDuration = 0;
            $estimatedEnergy = 0;

            // Activation fee
            if ($plan->activation_fee) {
                $estimatedCost += $plan->activation_fee;
            }

            // Base rate
            if ($plan->base_rate) {
                $estimatedCost += $plan->base_rate;
            }

            // Calculate based on reservation type
            if ($validated['reservation_type'] === 'kwh') {
                $estimatedEnergy = $validated['reservation_value'];
                if ($plan->price_per_kwh) {
                    $estimatedCost += $estimatedEnergy * $plan->price_per_kwh;
                }
                if ($chargingPoint->power_output && $chargingPoint->power_output > 0) {
                    $estimatedDuration = ($estimatedEnergy / $chargingPoint->power_output) * 60;
                }
            } else {
                $estimatedDuration = $validated['reservation_value'];
                if ($plan->price_per_minute) {
                    $estimatedCost += $estimatedDuration * $plan->price_per_minute;
                }
                if ($chargingPoint->power_output && $chargingPoint->power_output > 0) {
                    $estimatedEnergy = $chargingPoint->power_output * ($estimatedDuration / 60);
                }
            }

            // Apply VAT if configured
            if ($plan->vatRate && $plan->vatRate->rate > 0) {
                $estimatedCost = $estimatedCost * (1 + ($plan->vatRate->rate / 100));
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'estimated_cost' => round($estimatedCost, 2),
                    'estimated_duration' => round($estimatedDuration),
                    'estimated_energy' => round($estimatedEnergy, 2),
                    'currency' => $plan->currency ?? 'EUR',
                    'plan_name' => $plan->name,
                    'power_output' => $chargingPoint->power_output,
                    'max_duration' => $plan->max_duration,
                    'max_energy' => $plan->max_duration && $chargingPoint->power_output ? 
                        round(($plan->max_duration / 60) * $chargingPoint->power_output, 2) : null,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error calculating cost: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors du calcul du coût.'
            ], 500);
        }
    }

    /**
     * Get plan limits for validation
     */
    public function getPlanLimits($chargingPointId)
    {
        $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
        
        try {
            $plan = $chargingPoint->pricingPlan;
            if (!$plan) {
                return response()->json([
                    'error' => 'Aucun plan tarifaire actif trouvé.'
                ], 404);
            }

            $limits = [
                'max_duration' => $plan->max_duration,
                'max_energy' => null,
            ];

            if ($plan->max_duration && $chargingPoint->power_output) {
                $limits['max_energy'] = round(($plan->max_duration / 60) * $chargingPoint->power_output, 2);
            }

            return response()->json([
                'success' => true,
                'data' => $limits
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting plan limits: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la récupération des limites du plan.'
            ], 500);
        }
    }
    public function startCharging(Request $request, Reservation $reservation)
    {
        $reservation->update(['status' => \App\Enums\ReservationStatus::ACTIVE]);
        $reservation->transaction()->create([
            'user_id' => $reservation->user_id,
            'charging_point_id' => $reservation->charging_point_id,
            'pricing_plan_id' => $reservation->pricing_plan_id,
            'reservation_id' => $reservation->id,
            'start_timestamp' => now(),
            'meter_start' => 0,
            'currency' => 'EUR',
        ]);
        return redirect()->back()->with('success', 'Charging started successfully for reservation ' . $reservation->id);
    }

    public function startCharge(Request $request, Reservation $reservation)
    {
        // Vérifier que la réservation est confirmée
        if ($reservation->status !== \App\Enums\ReservationStatus::CONFIRMED) {
            return redirect()->back()->with('error', 'La réservation doit être confirmée pour démarrer la charge.');
        }

        // Mettre à jour le statut de la réservation
        $reservation->update(['status' => \App\Enums\ReservationStatus::ACTIVE]);

        // Créer une transaction si elle n'existe pas déjà
        if (!$reservation->transaction) {
            $reservation->transaction()->create([
                'user_id' => $reservation->user_id,
                'charging_point_id' => $reservation->charging_point_id,
                'pricing_plan_id' => $reservation->pricing_plan_id,
                'reservation_id' => $reservation->id,
                'start_timestamp' => now(),
                'meter_start' => 0,
                'currency' => 'EUR',
                'status' => 'in_progress',
            ]);
        }

        return redirect()->back()->with('success', 'La charge a été démarrée avec succès pour la réservation #' . $reservation->id);
    }

    public function stopCharge(Request $request, Reservation $reservation)
    {
        // Vérifier que la réservation est active
        if ($reservation->status !== \App\Enums\ReservationStatus::ACTIVE) {
            return redirect()->back()->with('error', 'La réservation doit être active pour terminer la charge.');
        }

        // Mettre à jour le statut de la réservation
        $reservation->update(['status' => \App\Enums\ReservationStatus::COMPLETED]);

        // Mettre à jour la transaction si elle existe
        if ($reservation->transaction) {
            $reservation->transaction->update([
                'stop_timestamp' => now(),
                'status' => 'completed',
            ]);
        }

        return redirect()->back()->with('success', 'La charge a été terminée avec succès pour la réservation #' . $reservation->id);
    }

    public function downloadInvoice(Reservation $reservation)
    {
        // Check if user is authorized to download
        $user = auth()->user();
        $isOwner = $this->canApproveReservationAsOwner($reservation, $user);
        
        if (!$isOwner && $reservation->user_id !== $user->id) {
            return redirect()->back()->with('error', 'Vous n\'êtes pas autorisé à télécharger cette facture.');
        }

        // We try to get the invoice from the session metadata
        $session = \App\Models\ChargingSession::where('metadata->reservation_id', $reservation->id)
                                              ->orWhere('metadata->steve_transaction_id', optional($reservation->transaction)->transaction_id)
                                              ->orWhere('id', optional(optional($reservation->transaction)->metadata)['postpaid_session_id'] ?? 0)
                                              ->first();
        if (!$session) {
            return redirect()->back()->with('error', 'Aucune session trouvée pour cette r&eacute;servation.');
        }

        $invoiceService = app(\App\Services\PostpaidInvoiceService::class);
        $invoice = $invoiceService->getInvoice($session);

        if (!$invoice) {
            // Tentative de regénération si session completée
            if ($session->status === 'completed' && $session->isPostpaid()) {
                try {
                    $invoice = $invoiceService->generateInvoice($session);
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', 'Erreur lors de la génération de la facture: ' . $e->getMessage());
                }
            }
            
            if (!$invoice) {
                return redirect()->back()->with('error', 'Aucune facture disponible pour cette réservation.');
            }
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', ['invoice' => $invoice]);
        return $pdf->download('facture_' . $invoice['invoice_number'] . '.pdf');
    }
    public function cancel(Request $request, Reservation $reservation)
    {
        // Vérification pour les clients et utilisateurs publics : ne peuvent annuler que leurs propres réservations
        $user = auth()->user();
        if ($user && (($user->hasRole('client') || ($user->hasRole('user') && !$user->hasAnyRole(['admin', 'super_admin', 'integrator', 'operator', 'partner']))))) {
            if ($reservation->user_id !== $user->id) {
                \Log::warning('Client/User attempted to cancel another user\'s reservation', [
                    'user_id' => $user->id,
                    'user_roles' => $user->getRoleNames()->toArray(),
                    'reservation_id' => $reservation->id,
                    'reservation_user_id' => $reservation->user_id
                ]);
                
                return redirect()->route('reservations.index')
                    ->with('error', 'Vous n\'êtes pas autorisé à annuler cette réservation.');
            }
        }
        
        $reservation->update(['status' => \App\Enums\ReservationStatus::CANCELED]);
        return redirect()->back()->with('success', 'Réservation annulée avec succès.');
    }

    public function endCharging(Request $request, Reservation $reservation)
    {
        $reservation->update([
            'status' => \App\Enums\ReservationStatus::COMPLETED,
            'actual_duration' => $request->input('duration'),
            'actual_energy' => $request->input('energy'),
            'actual_cost' => $request->input('cost'),
        ]);
        if ($reservation->transaction) {
            $reservation->transaction->update(['stop_timestamp' => now()]);
        }
        return redirect()->back()->with('success', 'Session de charge terminée avec succès.');
    }
}
