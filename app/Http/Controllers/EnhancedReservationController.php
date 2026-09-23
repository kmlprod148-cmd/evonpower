<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Services\EnhancedReservationFeeService;
use App\Services\ReservationService;
use App\Services\BusinessProfileFeesSetupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Contrôleur amélioré pour la gestion des réservations
 * Avec récupération complète des frais appliqués
 */
class EnhancedReservationController extends Controller
{
    protected $reservationService;
    protected $feeService;
    protected $feesSetupService;

    public function __construct(
        ReservationService $reservationService,
        EnhancedReservationFeeService $feeService,
        BusinessProfileFeesSetupService $feesSetupService
    ) {
        $this->reservationService = $reservationService;
        $this->feeService = $feeService;
        $this->feesSetupService = $feesSetupService;
    }

    /**
     * Affiche la liste des réservations avec les frais détaillés
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Récupération des réservations selon le rôle
        $query = Reservation::with([
            'user:id,name,email',
            'chargingPoint:id,name,station_id,integrator_id,partner_id,user_id',
            'chargingPoint.station:id,name',
            'chargingPoint.businessProfile:id,name,integrator_id,partner_id',
            'chargingPoint.integrator:id,name',
            'chargingPoint.partner:id,name',
            'pricingPlan:id,name,rate_type,base_rate,price_per_kwh,price_per_minute,activation_fee',
            'pricingPlan.vatRate:id,rate',
            'transaction:id,reservation_id,price_total,admin_commission,integrator_commission,partner_commission'
        ]);

        // Logique de filtrage selon le rôle
        if ($user->hasRole('admin')) {
            // Admin voit TOUTES les réservations - pas de filtre
            Log::info('Admin accède à toutes les réservations', ['user_id' => $user->id]);
        } elseif ($user->hasRole('integrator')) {
            $query->whereHas('chargingPoint', function ($q) use ($user) {
                $q->where('integrator_id', $user->id);
            });
        } elseif ($user->hasRole('partner')) {
            // Les partenaires voient les réservations sur leurs bornes
            if ($user->partner_id) {
                $query->where(function($q) use ($user) {
                    // Réservations sur les bornes directement liées au partenaire
                    $q->whereHas('chargingPoint', function($cpQuery) use ($user) {
                        $cpQuery->where('partner_id', $user->partner_id);
                    })
                    // Réservations sur les bornes dans des groupes du partenaire
                    ->orWhereHas('chargingPoint.group', function($groupQuery) use ($user) {
                        $groupQuery->where('partner_id', $user->partner_id);
                    })
                    // Réservations personnelles du partenaire (fallback)
                    ->orWhere('user_id', $user->id);
                });
            } else {
                $query->where('user_id', $user->id);
            }
        } elseif ($user->hasRole('operator')) {
            // Les opérateurs voient TOUTES les réservations sur les bornes qu'ils gèrent
            // Récupérer les IDs des bornes gérées par cet opérateur (inclure soft-deleted)
            $chargingPointIds = \App\Models\ChargingPoint::withTrashed()
                ->where('user_id', $user->id)
                ->pluck('id')
                ->toArray();
            
            // Récupérer les IDs des groupes gérés par cet opérateur
            $groupIds = \App\Models\Group::where('user_id', $user->id)
                ->pluck('id')
                ->toArray();
            
            // Récupérer les IDs des bornes dans ces groupes (inclure soft-deleted)
            if (!empty($groupIds)) {
                $groupChargingPointIds = \App\Models\ChargingPoint::withTrashed()
                    ->whereIn('group_id', $groupIds)
                    ->pluck('id')
                    ->toArray();
                $chargingPointIds = array_merge($chargingPointIds, $groupChargingPointIds);
            }
            
            // Retirer les doublons
            $chargingPointIds = array_unique($chargingPointIds);
            
            // Toujours inclure les réservations personnelles de l'opérateur
            $query->where(function($q) use ($user, $chargingPointIds) {
                // Réservations personnelles de l'opérateur
                $q->where('user_id', $user->id);
                
                // TOUTES les réservations sur les bornes gérées par l'opérateur
                if (!empty($chargingPointIds)) {
                    $q->orWhereIn('charging_point_id', $chargingPointIds);
                }
            });
        } else {
            $query->where('user_id', $user->id);
        }

        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('charging_point_id')) {
            $query->where('charging_point_id', $request->charging_point_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $reservations = $query->latest()->paginate(20);

        // Debug pour l'admin
        if ($user->hasRole('admin')) {
            $totalReservations = Reservation::count();
            Log::info('Admin - Total réservations dans la DB', ['total' => $totalReservations]);
            Log::info('Admin - Réservations après filtres', ['count' => $reservations->count()]);
            
            // Si aucune réservation n'existe, créer des données de test
            if ($totalReservations == 0) {
                $this->createSampleReservations();
                // Recharger les réservations après création
                $reservations = $query->latest()->paginate(20);
            }
            
            // Configurer les frais pour les business profiles si nécessaire
            $this->ensureBusinessProfilesHaveFees();
        }

        // Ajouter les frais détaillés pour chaque réservation
        $reservations->getCollection()->transform(function ($reservation) {
            $fees = $this->feeService->getAllAppliedFees($reservation);
            $reservation->fees_breakdown = $fees;
            return $reservation;
        });

        // Données pour les filtres
        $chargingPoints = \App\Models\ChargingPoint::with('station')->get();
        $businessProfiles = \App\Models\BusinessProfile::get();

        return view('reservations.enhanced.index', compact('reservations', 'chargingPoints', 'businessProfiles'));
    }

    /**
     * Affiche les détails d'une réservation avec tous les frais
     */
    public function show(Reservation $reservation)
    {
        $this->authorize('view', $reservation);

        // Charger toutes les relations nécessaires
        $reservation->load([
            'user',
            'chargingPoint.station',
            'chargingPoint.businessProfile.integrator',
            'chargingPoint.businessProfile.partner',
            'chargingPoint.integrator',
            'chargingPoint.partner',
            'pricingPlan.vatRate',
            'transaction'
        ]);

        // Récupérer tous les frais appliqués
        $feesBreakdown = $this->feeService->getDetailedFeesBreakdown($reservation);

        // Statistiques des frais
        $feesStatistics = $this->getFeesStatistics($reservation);

        return view('reservations.enhanced.show', compact(
            'reservation',
            'feesBreakdown',
            'feesStatistics'
        ));
    }

    /**
     * Crée une nouvelle réservation avec calcul des frais
     */
    public function store(Request $request)
    {
        $request->validate([
            'charging_point_id' => 'required|exists:charging_points,id',
            'pricing_plan_id' => 'required|exists:pricing_plans,id',
            'reservation_type' => 'required|in:kwh,minute',
            'reservation_value' => 'required|numeric|min:0.01',
            'start_time' => 'nullable|date|after:now',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $chargingPoint = ChargingPoint::findOrFail($request->charging_point_id);
            $pricingPlan = PricingPlan::findOrFail($request->pricing_plan_id);

            // Créer la réservation
            $reservationData = $request->only([
                'charging_point_id',
                'pricing_plan_id',
                'reservation_type',
                'reservation_value',
                'start_time',
                'notes'
            ]);

            $reservation = $this->reservationService->createReservation(
                $reservationData,
                $chargingPoint
            );

            // Calculer et sauvegarder les frais
            $fees = $this->feeService->getAllAppliedFees($reservation);
            
            // Mettre à jour la réservation avec les frais calculés
            $reservation->update([
                'estimated_cost' => $fees['net_amount'] + $fees['total_fees'],
                'actual_cost' => $fees['net_amount'] + $fees['total_fees']
            ]);

            DB::commit();

            Log::info('Réservation créée avec succès', [
                'reservation_id' => $reservation->id,
                'total_fees' => $fees['total_fees'],
                'net_amount' => $fees['net_amount']
            ]);

            return redirect()
                ->route('reservations.enhanced.show', $reservation)
                ->with('success', 'Réservation créée avec succès. Frais calculés: ' . number_format($fees['total_fees'], 2) . '€');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de la création de la réservation', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Erreur lors de la création de la réservation: ' . $e->getMessage());
        }
    }

    /**
     * Met à jour une réservation
     */
    public function update(Request $request, Reservation $reservation)
    {
        $this->authorize('update', $reservation);

        $request->validate([
            'reservation_type' => 'sometimes|in:kwh,minute',
            'reservation_value' => 'sometimes|numeric|min:0.01',
            'start_time' => 'sometimes|date',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            // Récupérer le point de charge et le plan tarifaire pour validation
            $chargingPoint = $reservation->chargingPoint;
            $pricingPlan = $reservation->pricingPlan ?? ($chargingPoint ? $chargingPoint->pricingPlan : null);

            // VALIDATION CRITIQUE: Vérifier la limite maximale si reservation_value est modifié
            if ($request->has('reservation_value') && $request->has('reservation_type') && $pricingPlan) {
                $reservationType = $request->input('reservation_type');
                $reservationValue = (float) $request->input('reservation_value');

                if ($reservationType === 'minute' && $pricingPlan->max_duration) {
                    if ($reservationValue > $pricingPlan->max_duration) {
                        DB::rollBack();
                        return back()
                            ->withInput()
                            ->with('error', "La durée de réservation ne peut pas dépasser {$pricingPlan->max_duration} minutes selon le plan tarifaire associé à ce point de charge.");
                    }
                } elseif ($reservationType === 'kwh' && $pricingPlan->max_duration && $chargingPoint && $chargingPoint->power_output) {
                    $estimatedDurationFromEnergy = ($reservationValue / $chargingPoint->power_output) * 60;
                    if ($estimatedDurationFromEnergy > $pricingPlan->max_duration) {
                        $maxEnergy = $chargingPoint->power_output * ($pricingPlan->max_duration / 60);
                        DB::rollBack();
                        return back()
                            ->withInput()
                            ->with('error', "La quantité d'énergie ne peut pas dépasser " . number_format($maxEnergy, 2) . " kWh (équivalent à {$pricingPlan->max_duration} minutes) selon le plan tarifaire associé à ce point de charge.");
                    }
                }
            }

            $reservation->update($request->only([
                'reservation_type',
                'reservation_value',
                'start_time',
                'notes'
            ]));

            // Recalculer les frais après mise à jour
            $fees = $this->feeService->getAllAppliedFees($reservation);
            
            $reservation->update([
                'estimated_cost' => $fees['net_amount'] + $fees['total_fees'],
                'actual_cost' => $fees['net_amount'] + $fees['total_fees']
            ]);

            DB::commit();

            return redirect()
                ->route('reservations.enhanced.show', $reservation)
                ->with('success', 'Réservation mise à jour avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de la mise à jour de la réservation', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour: ' . $e->getMessage());
        }
    }

    /**
     * Supprime une réservation
     */
    public function destroy(Reservation $reservation)
    {
        $this->authorize('delete', $reservation);

        try {
            $reservation->delete();

            return redirect()
                ->route('reservations.enhanced.index')
                ->with('success', 'Réservation supprimée avec succès');

        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression de la réservation', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->with('error', 'Erreur lors de la suppression: ' . $e->getMessage());
        }
    }

    /**
     * Récupère les statistiques des frais pour une réservation
     */
    private function getFeesStatistics(Reservation $reservation): array
    {
        $fees = $this->feeService->getAllAppliedFees($reservation);
        
        $totalCost = $reservation->estimated_cost;
        $totalFees = $fees['total_fees'];
        
        return [
            'total_cost' => $totalCost,
            'total_fees' => $totalFees,
            'net_amount' => $fees['net_amount'],
            'fee_percentage' => $totalCost > 0 ? round(($totalFees / $totalCost) * 100, 2) : 0,
            'fees_by_category' => [
                'reservation' => $fees['reservation_fees']['total'],
                'business_profile' => $fees['business_profile_fees']['total'],
                'transaction' => $fees['transaction_fees']['total'],
                'activation' => $fees['activation_fees']['total'],
                'commission' => $fees['commission_fees']['total']
            ]
        ];
    }

    /**
     * Exporte les frais d'une réservation en PDF
     */
    public function exportFees(Reservation $reservation)
    {
        $this->authorize('view', $reservation);

        $feesBreakdown = $this->feeService->getDetailedFeesBreakdown($reservation);
        
        // TODO: Implémenter l'export PDF
        return response()->json($feesBreakdown);
    }

    /**
     * API endpoint pour récupérer les frais d'une réservation
     */
    public function getFeesApi(Reservation $reservation)
    {
        $this->authorize('view', $reservation);

        $feesBreakdown = $this->feeService->getDetailedFeesBreakdown($reservation);
        
        return response()->json([
            'success' => true,
            'data' => $feesBreakdown
        ]);
    }

    /**
     * Crée des données de test pour l'admin (route publique)
     */
    public function createSampleData()
    {
        $user = Auth::user();
        
        if (!$user->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Accès non autorisé'
            ], 403);
        }

        try {
            $this->createSampleReservations();
            
            return response()->json([
                'success' => true,
                'message' => 'Données de test créées avec succès'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création des données de test', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création des données de test'
            ], 500);
        }
    }

    /**
     * Crée des réservations de test pour l'admin
     */
    private function createSampleReservations()
    {
        try {
            DB::beginTransaction();

            // Vérifier s'il y a des utilisateurs, points de charge et plans tarifaires
            $users = \App\Models\User::limit(3)->get();
            $chargingPoints = \App\Models\ChargingPoint::limit(3)->get();
            $pricingPlans = \App\Models\PricingPlan::limit(3)->get();

            if ($users->isEmpty() || $chargingPoints->isEmpty() || $pricingPlans->isEmpty()) {
                Log::warning('Données manquantes pour créer des réservations de test');
                return;
            }

            // Créer des réservations de test
            $sampleReservations = [
                [
                    'user_id' => $users->first()->id,
                    'charging_point_id' => $chargingPoints->first()->id,
                    'pricing_plan_id' => $pricingPlans->first()->id,
                    'reservation_type' => 'kwh',
                    'reservation_value' => 15.5,
                    'estimated_cost' => 25.50,
                    'actual_cost' => 25.50,
                    'status' => 'confirmed',
                    'start_time' => now()->addHours(2),
                    'notes' => 'Réservation de test - Admin'
                ],
                [
                    'user_id' => $users->skip(1)->first()?->id ?? $users->first()->id,
                    'charging_point_id' => $chargingPoints->skip(1)->first()?->id ?? $chargingPoints->first()->id,
                    'pricing_plan_id' => $pricingPlans->skip(1)->first()?->id ?? $pricingPlans->first()->id,
                    'reservation_type' => 'minute',
                    'reservation_value' => 30,
                    'estimated_cost' => 18.75,
                    'actual_cost' => 18.75,
                    'status' => 'pending',
                    'start_time' => now()->addHours(4),
                    'notes' => 'Réservation de test - Minutes'
                ],
                [
                    'user_id' => $users->skip(2)->first()?->id ?? $users->first()->id,
                    'charging_point_id' => $chargingPoints->skip(2)->first()?->id ?? $chargingPoints->first()->id,
                    'pricing_plan_id' => $pricingPlans->skip(2)->first()?->id ?? $pricingPlans->first()->id,
                    'reservation_type' => 'kwh',
                    'reservation_value' => 8.2,
                    'estimated_cost' => 12.30,
                    'actual_cost' => 12.30,
                    'status' => 'completed',
                    'start_time' => now()->subHours(2),
                    'notes' => 'Réservation de test - Terminée'
                ]
            ];

            foreach ($sampleReservations as $reservationData) {
                Reservation::create($reservationData);
            }

            DB::commit();
            
            Log::info('Réservations de test créées avec succès', ['count' => count($sampleReservations)]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur lors de la création des réservations de test', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * S'assure que les business profiles ont des frais configurés
     */
    private function ensureBusinessProfilesHaveFees()
    {
        try {
            // Vérifier s'il y a des business profiles sans frais
            $businessProfilesWithoutFees = BusinessProfile::where(function($query) {
                $query->whereNull('admin_fee_fixed')
                      ->whereNull('admin_fee_percentage')
                      ->whereNull('integrator_fee_fixed')
                      ->whereNull('integrator_fee_percentage');
            })->get();

            if ($businessProfilesWithoutFees->count() > 0) {
                Log::info('Configuration des frais pour les business profiles', [
                    'count' => $businessProfilesWithoutFees->count()
                ]);

                $this->feesSetupService->setupDefaultFees();
            }

            // S'assurer que tous les points de charge ont un business profile
            $chargingPointsWithoutProfile = ChargingPoint::whereNull('business_profile_id')->get();
            
            if ($chargingPointsWithoutProfile->count() > 0) {
                Log::info('Configuration des business profiles pour les points de charge', [
                    'count' => $chargingPointsWithoutProfile->count()
                ]);

                $this->feesSetupService->setupFeesForChargingPointsWithoutBusinessProfile();
            }

        } catch (\Exception $e) {
            Log::error('Erreur lors de la configuration des frais', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
