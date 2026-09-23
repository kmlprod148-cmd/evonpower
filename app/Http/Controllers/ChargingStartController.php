<?php

namespace App\Http\Controllers;

use App\Jobs\StartChargingSessionJob;
use App\Jobs\StopChargingSessionJob;
use App\Models\ChargingPoint;
use App\Models\ChargingSession;
use App\Models\Connector;
use App\Models\OcppTag;
use App\Models\PricingPlan;
use App\Models\Reservation;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ChargingStartController
 *
 * Gère le démarrage des sessions de charge prépayées et postpayées
 * via le wallet (solde) de l'utilisateur connecté.
 *
 * PRÉPAYÉ  : Le wallet est débité avant le démarrage.
 *            Si la session consomme moins que prévu → remboursement au wallet.
 *
 * POSTPAYÉ : Session démarre immédiatement (pré-autorisée).
 *            Le wallet est débité au moment de l'arrêt (StopChargingSessionJob).
 */
class ChargingStartController extends Controller
{
    // =========================================================================
    // PRÉPAYÉ – Affichage
    // =========================================================================

    /**
     * GET /charging/{chargingPoint}/prepaid
     */
    public function showPrepaid(ChargingPoint $chargingPoint)
    {
        abort_if(!$chargingPoint->is_active, 404);

        $chargingPoint->loadMissing(['pricingPlan.vatRate', 'connectors', 'group', 'partner']);

        $pricingPlan = $chargingPoint->pricingPlan
            ?? PricingPlan::where('is_active', true)->first();

        abort_if(!$pricingPlan, 400, 'Aucun plan tarifaire disponible.');

        $user   = auth()->user();
        $wallet = $user->getOrCreateWallet()->fresh();

        return view('charging.prepaid', [
            'chargingPoint' => $chargingPoint,
            'pricingPlan'   => $pricingPlan,
            'currency'      => strtoupper($pricingPlan->currency ?? 'EUR'),
            'walletBalance' => (float) ($wallet->balance ?? 0),
            'user'          => $user,
        ]);
    }

    // =========================================================================
    // PRÉPAYÉ – Démarrage
    // =========================================================================

    /**
     * POST /charging/{chargingPoint}/prepaid/start
     *
     * Flux :
     * 1. Valide la sélection (type + valeur)
     * 2. Calcule le coût estimé
     * 3. Vérifie le solde wallet (atomic)
     * 4. Débite le wallet
     * 5. Crée la Reservation + Transaction (statut : PAID/confirmed)
     * 6. Résout/crée le tag OCPP
     * 7. Dispatche StartChargingSessionJob
     */
    public function startPrepaid(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'type'  => 'required|in:energy,duration',
            'value' => 'required|numeric|min:0.1|max:9999',
        ]);

        abort_if(!$chargingPoint->is_active, 400, 'Cette borne est inactive.');

        $chargingPoint->loadMissing([
            'pricingPlan.vatRate', 'connectors', 'group', 'partner', 'integrator',
        ]);

        $pricingPlan = $chargingPoint->pricingPlan
            ?? PricingPlan::where('is_active', true)->firstOrFail();

        $user     = auth()->user();
        $currency = strtoupper($pricingPlan->currency ?? 'EUR');
        $cost     = $this->calculateCost($request->type, $request->value, $pricingPlan);

        if ($cost < 0.01) {
            return back()->with('error', 'Le montant calculé est trop faible.');
        }

        // Vérification wallet (non-atomique, juste pour afficher un message tôt)
        if (!$user->hasSufficientWalletBalance($cost)) {
            $balance = number_format($user->getWalletBalance(), 2);
            return back()->with(
                'error',
                "Solde insuffisant ({$balance} {$currency} disponible, {$cost} {$currency} requis). Rechargez votre wallet."
            );
        }

        // Sélectionner le connecteur disponible
        $connector = $this->selectConnector($chargingPoint);
        if (!$connector) {
            return back()->with('error', 'Aucun connecteur disponible sur cette borne.');
        }

        // Résoudre/créer le tag OCPP
        $ocppTag = $this->resolveOcppTag($user, $chargingPoint);
        if (!$ocppTag) {
            return back()->with('error', 'Impossible de résoudre le tag OCPP pour votre compte.');
        }

        DB::beginTransaction();
        try {
            // ── 1. Débiter le wallet (atomique, avec verrou)
            $wallet = $user->getOrCreateWallet();
            $wallet->debit($cost, "Pré-paiement recharge – Borne {$chargingPoint->name}", [
                'charging_point_id' => $chargingPoint->id,
                'type'              => 'prepaid_charging',
            ]);

            // ── 2. Créer la réservation (PAID dès la création)
            $reservation = Reservation::create([
                'user_id'           => $user->id,
                'charging_point_id' => $chargingPoint->id,
                'connector_id'      => $connector->id,
                'ocpp_tag_id'       => $ocppTag->id,
                'pricing_plan_id'   => $pricingPlan->id,
                'partner_id'        => $chargingPoint->partner_id,
                'integrator_id'     => $chargingPoint->integrator_id,
                'reservation_type'  => $request->type === 'energy' ? 'kwh' : 'minute',
                'reservation_value' => $request->value,
                'start_time'        => now(),
                'estimated_cost'    => $cost,
                'amount'            => $cost,
                'prepaid_amount'    => $cost,
                'status'            => 'confirmed',
                'payment_type'      => 'cmi',       // contrainte ENUM : 'cmi' | 'offline'
                'payment_method'    => 'wallet',
                'payment_mode'      => 'prepaid',
                'payment_status'    => 'PAID',
                'confirmed_at'      => now(),
                'approved_at'       => now(),
                'approved_by'       => $user->id,
            ]);

            // ── 3. Créer la transaction (completed car wallet déjà débité)
            $txRef = 'PRE-WAL-' . strtoupper(uniqid());
            Transaction::create([
                'transaction_id'    => $txRef,
                'charging_point_id' => $chargingPoint->id,
                'reservation_id'    => $reservation->id,
                'user_id'           => $user->id,
                'pricing_plan_id'   => $pricingPlan->id,
                'start_timestamp'   => now(),
                'status'            => 'completed',
                'payment_method'    => 'wallet',
                'amount'            => $cost,
                'price_total'       => $cost,
                'currency'          => $currency,
                'meter_start'       => 0,
                'completed_at'      => now(),
                'metadata'          => [
                    'payment_mode'       => 'prepaid',
                    'payment_method'     => 'wallet',
                    'charging_type'      => $request->type,
                    'charging_value'     => $request->value,
                    'wallet_debited_at'  => now()->toIso8601String(),
                ],
            ]);

            DB::commit();

            // ── 4. Dispatcher le job de démarrage (hors transaction DB)
            StartChargingSessionJob::dispatch($reservation->id);

            Log::info('ChargingStartController: Session prépayée initiée', [
                'reservation_id'    => $reservation->id,
                'user_id'           => $user->id,
                'charging_point_id' => $chargingPoint->id,
                'cost'              => $cost,
            ]);

            return redirect()
                ->route('charging.session.live', $reservation->id)
                ->with('success', 'Paiement effectué. Démarrage de la recharge en cours…');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('ChargingStartController: Erreur startPrepaid', [
                'user_id'           => $user->id,
                'charging_point_id' => $chargingPoint->id,
                'error'             => $e->getMessage(),
            ]);

            return back()->with(
                'error',
                'Une erreur est survenue : ' . $e->getMessage()
            );
        }
    }

    // =========================================================================
    // POSTPAYÉ – Affichage
    // =========================================================================

    /**
     * GET /charging/{chargingPoint}/postpaid
     */
    public function showPostpaid(ChargingPoint $chargingPoint)
    {
        abort_if(!$chargingPoint->is_active, 404);

        $chargingPoint->loadMissing(['pricingPlan.vatRate', 'connectors', 'group', 'partner']);

        $pricingPlan = $chargingPoint->pricingPlan
            ?? PricingPlan::where('is_active', true)->first();

        $user   = auth()->user();
        $wallet = $user->getOrCreateWallet()->fresh();

        // Estimation du coût pour 1h (affichage uniquement)
        $estimatedHourCost = $pricingPlan
            ? $this->calculateCost('duration', 60, $pricingPlan)
            : 0;

        return view('charging.postpaid', [
            'chargingPoint'     => $chargingPoint,
            'pricingPlan'       => $pricingPlan,
            'currency'          => strtoupper($pricingPlan?->currency ?? 'EUR'),
            'walletBalance'     => (float) ($wallet->balance ?? 0),
            'estimatedHourCost' => $estimatedHourCost,
            'user'              => $user,
        ]);
    }

    // =========================================================================
    // POSTPAYÉ – Démarrage
    // =========================================================================

    /**
     * POST /charging/{chargingPoint}/postpaid/start
     *
     * Flux :
     * 1. Vérifie que l'utilisateur a un solde minimal (protection fraude)
     * 2. Crée la Reservation pré-autorisée (payment_status=PAID / payment_mode=postpaid)
     * 3. Dispatche StartChargingSessionJob
     * → À l'arrêt de la session, StopChargingSessionJob débite le wallet
     */
    public function startPostpaid(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'max_kwh'     => 'nullable|numeric|min:0.1|max:500',
            'max_minutes' => 'nullable|integer|min:1|max:1440',
        ]);

        abort_if(!$chargingPoint->is_active, 400, 'Cette borne est inactive.');

        $chargingPoint->loadMissing([
            'pricingPlan.vatRate', 'connectors', 'group', 'partner', 'integrator',
        ]);

        $pricingPlan = $chargingPoint->pricingPlan
            ?? PricingPlan::where('is_active', true)->firstOrFail();

        $user     = auth()->user();
        $currency = strtoupper($pricingPlan->currency ?? 'EUR');

        // Estimer le coût minimal (30 min ou 5 kWh) pour vérification de solvabilité
        $minCostEstimate = max(
            $this->calculateCost('duration', 30, $pricingPlan),
            1.00
        );

        if (!$user->hasSufficientWalletBalance($minCostEstimate)) {
            $balance = number_format($user->getWalletBalance(), 2);
            return back()->with(
                'error',
                "Solde wallet insuffisant ({$balance} {$currency}). Un minimum de " .
                number_format($minCostEstimate, 2) . " {$currency} est requis pour démarrer une session postpayée."
            );
        }

        // Sélectionner le connecteur disponible
        $connector = $this->selectConnector($chargingPoint);
        if (!$connector) {
            return back()->with('error', 'Aucun connecteur disponible sur cette borne.');
        }

        // Résoudre/créer le tag OCPP
        $ocppTag = $this->resolveOcppTag($user, $chargingPoint);
        if (!$ocppTag) {
            return back()->with('error', 'Impossible de résoudre le tag OCPP pour votre compte.');
        }

        DB::beginTransaction();
        try {
            // Calcul des limites optionnelles
            $maxKwh     = $request->filled('max_kwh')     ? (float) $request->max_kwh     : null;
            $maxMinutes = $request->filled('max_minutes') ? (int) $request->max_minutes : null;

            // Coût estimé selon les limites choisies
            $estimatedCost = 0;
            if ($maxKwh) {
                $estimatedCost = max($estimatedCost, $this->calculateCost('energy', $maxKwh, $pricingPlan));
            }
            if ($maxMinutes) {
                $estimatedCost = max($estimatedCost, $this->calculateCost('duration', $maxMinutes, $pricingPlan));
            }
            if ($estimatedCost == 0) {
                $estimatedCost = $this->calculateCost('duration', 60, $pricingPlan); // fallback 1h
            }

            // Créer la réservation pré-autorisée
            $reservation = Reservation::create([
                'user_id'           => $user->id,
                'charging_point_id' => $chargingPoint->id,
                'connector_id'      => $connector->id,
                'ocpp_tag_id'       => $ocppTag->id,
                'pricing_plan_id'   => $pricingPlan->id,
                'partner_id'        => $chargingPoint->partner_id,
                'integrator_id'     => $chargingPoint->integrator_id,
                'reservation_type'  => $maxKwh ? 'kwh' : 'minute',
                'reservation_value' => $maxKwh ?? ($maxMinutes ?? 60),
                'start_time'        => now(),
                'estimated_cost'    => $estimatedCost,
                'amount'            => $estimatedCost,
                'max_kwh'           => $maxKwh,
                'max_minutes'       => $maxMinutes,
                'status'            => 'confirmed',
                // payment_status=PAID = pré-autorisé ; paiement réel à l'arrêt
                'payment_type'      => 'cmi',
                'payment_method'    => 'wallet',
                'payment_mode'      => 'postpaid',
                'payment_status'    => 'PAID',
                'confirmed_at'      => now(),
                'approved_at'       => now(),
                'approved_by'       => $user->id,
            ]);

            // Créer la transaction en attente (sera complétée à l'arrêt)
            $txRef = 'POST-WAL-' . strtoupper(uniqid());
            Transaction::create([
                'transaction_id'    => $txRef,
                'charging_point_id' => $chargingPoint->id,
                'reservation_id'    => $reservation->id,
                'user_id'           => $user->id,
                'pricing_plan_id'   => $pricingPlan->id,
                'start_timestamp'   => now(),
                'status'            => 'pending',
                'payment_method'    => 'wallet',
                'amount'            => $estimatedCost,
                'price_total'       => $estimatedCost,
                'currency'          => $currency,
                'meter_start'       => 0,
                'metadata'          => [
                    'payment_mode'   => 'postpaid',
                    'payment_method' => 'wallet',
                    'max_kwh'        => $maxKwh,
                    'max_minutes'    => $maxMinutes,
                    'initiated_at'   => now()->toIso8601String(),
                ],
            ]);

            DB::commit();

            // Dispatcher le job de démarrage
            StartChargingSessionJob::dispatch($reservation->id);

            Log::info('ChargingStartController: Session postpayée initiée', [
                'reservation_id'    => $reservation->id,
                'user_id'           => $user->id,
                'charging_point_id' => $chargingPoint->id,
                'estimated_cost'    => $estimatedCost,
            ]);

            return redirect()
                ->route('charging.session.live', $reservation->id)
                ->with('success', 'Démarrage de la recharge postpayée en cours…');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('ChargingStartController: Erreur startPostpaid', [
                'user_id'           => $user->id,
                'charging_point_id' => $chargingPoint->id,
                'error'             => $e->getMessage(),
            ]);

            return back()->with('error', 'Une erreur est survenue : ' . $e->getMessage());
        }
    }

    // =========================================================================
    // SESSION LIVE
    // =========================================================================

    /**
     * GET /charging/session/{reservation}
     */
    public function sessionLive(Reservation $reservation)
    {
        $this->authorizeReservationAccess($reservation);

        $reservation->loadMissing(['chargingPoint', 'pricingPlan']);

        $session = ChargingSession::where('reservation_id', $reservation->id)
            ->latest('id')
            ->first();

        return view('charging.session-live', [
            'reservation'   => $reservation,
            'chargingPoint' => $reservation->chargingPoint,
            'session'       => $session,
            'user'          => auth()->user(),
        ]);
    }

    // =========================================================================
    // STATUS JSON (polling AJAX)
    // =========================================================================

    /**
     * GET /charging/session/{reservation}/status
     */
    public function sessionStatus(Reservation $reservation): JsonResponse
    {
        $this->authorizeReservationAccess($reservation);

        $reservation->refresh();
        $session = ChargingSession::where('reservation_id', $reservation->id)
            ->latest('id')
            ->first();

        $statusVal = is_object($reservation->status)
            ? $reservation->status->value
            : (string) ($reservation->status ?? '');

        $remoteStartStatus = $reservation->session_initiation_status;

        $state = match (true) {
            $session && in_array($session->status, ['active', 'in_progress'], true) => 'charging',
            $session && $session->status === 'completed'                             => 'completed',
            $statusVal === 'active'                                                  => 'charging',
            in_array($remoteStartStatus, ['processing', 'queued', 'scheduled'], true) => 'starting',
            $remoteStartStatus === 'success'                                         => 'charging',
            $remoteStartStatus === 'failed'                                          => 'failed',
            $reservation->payment_status === 'PAID' && $statusVal === 'confirmed'   => 'starting',
            default                                                                  => 'pending',
        };

        $paymentMode = $reservation->payment_mode ?? 'prepaid';
        $wallet      = auth()->user()->getOrCreateWallet()->fresh();

        // Calcul du coût en cours (session active)
        $currentCost = 0;
        $energyKwh   = 0;
        $durationMin = 0;
        if ($session && $session->started_at) {
            $durationMin = (int) now()->diffInMinutes($session->started_at);
            $energyKwh   = (float) ($session->energy_delivered ?? 0);
            $currentCost = (float) ($session->estimated_cost ?? 0);
        }

        return response()->json([
            'state'                      => $state,
            'payment_mode'               => $paymentMode,
            'reservation_status'         => $statusVal,
            'payment_status'             => $reservation->payment_status,
            'session_initiation_status'  => $remoteStartStatus,
            'wallet_balance'             => (float) $wallet->balance,
            'session'                    => $session ? [
                'id'                   => $session->id,
                'status'               => $session->status,
                'started_at'           => $session->started_at?->toIso8601String(),
                'duration_minutes'     => $durationMin,
                'energy_kwh'           => round($energyKwh, 3),
                'current_cost'         => round($currentCost, 2),
                'meter_start'          => $session->meter_start,
                'meter_stop'           => $session->meter_stop,
                'actual_cost'          => $session->actual_cost,
                'prepaid_amount'       => $session->prepaid_amount,
                'steve_transaction_id' => $session->steve_transaction_id,
            ] : null,
            'last_error'                 => $reservation->last_error,
            'charging_point'             => [
                'name' => $reservation->chargingPoint?->name,
            ],
        ]);
    }

    // =========================================================================
    // ARRÊT MANUEL
    // =========================================================================

    /**
     * POST /charging/session/{reservation}/stop
     */
    public function stopSession(Request $request, Reservation $reservation)
    {
        $this->authorizeReservationAccess($reservation);

        $session = ChargingSession::where('reservation_id', $reservation->id)
            ->whereIn('status', ['active', 'in_progress', 'initiating'])
            ->latest('id')
            ->first();

        if (!$session) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'error' => 'Aucune session active trouvée.'], 422);
            }
            return back()->with('error', 'Aucune session active à arrêter.');
        }

        StopChargingSessionJob::dispatch($session->id, 'Arrêt manuel par utilisateur');

        Log::info('ChargingStartController: Arrêt manuel demandé', [
            'reservation_id' => $reservation->id,
            'session_id'     => $session->id,
            'user_id'        => auth()->id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Arrêt de la session en cours…']);
        }

        return back()->with('success', 'La session de recharge est en cours d\'arrêt.');
    }

    // =========================================================================
    // Helpers privés
    // =========================================================================

    /**
     * Calculer le coût estimé (avec TVA) selon le plan tarifaire.
     */
    protected function calculateCost(string $type, $value, PricingPlan $plan): float
    {
        $cost = (float) ($plan->activation_fee ?? 0) + (float) ($plan->base_rate ?? 0);

        if ($type === 'energy' && $plan->price_per_kwh) {
            $cost += (float) $value * (float) $plan->price_per_kwh;
        } elseif ($type === 'duration' && $plan->price_per_minute) {
            $cost += (float) $value * (float) $plan->price_per_minute;
        }

        // TVA
        $vatRate = 0;
        if (!empty($plan->vatRate?->rate)) {
            $vatRate = (float) $plan->vatRate->rate;
        } elseif (!empty($plan->vat_rate)) {
            $vatRate = (float) $plan->vat_rate;
        }
        if ($vatRate > 0) {
            $cost *= 1 + ($vatRate / 100);
        }

        return round($cost, 2);
    }

    /**
     * Sélectionner le premier connecteur disponible (sinon le premier).
     */
    protected function selectConnector(ChargingPoint $chargingPoint): ?Connector
    {
        $connectors = $chargingPoint->connectors;
        if ($connectors->isEmpty()) {
            return null;
        }
        return $connectors->firstWhere('status', 'Available')
            ?? $connectors->first();
    }

    /**
     * Résoudre ou auto-créer un tag OCPP pour l'utilisateur.
     * Priorité : tag par défaut existant → tag actif existant → création.
     */
    protected function resolveOcppTag($user, ChargingPoint $chargingPoint): ?OcppTag
    {
        // 1. Tag par défaut actif
        $tag = OcppTag::where('user_id', $user->id)
            ->where('blocked', false)
            ->where('is_default', true)
            ->first();

        if ($tag) {
            return $tag;
        }

        // 2. N'importe quel tag actif
        $tag = OcppTag::where('user_id', $user->id)
            ->where('blocked', false)
            ->first();

        if ($tag) {
            return $tag;
        }

        // 3. Créer un tag automatique
        $tagValue = 'EVON-' . str_pad($user->id, 6, '0', STR_PAD_LEFT);
        return OcppTag::firstOrCreate(
            ['ocpp_tag' => $tagValue],
            [
                'user_id'    => $user->id,
                'blocked'    => false,
                'is_default' => true,
                'note'       => 'Auto-créé pour ' . $user->name . ' (#' . $user->id . ')',
            ]
        );
    }

    /**
     * S'assurer que la réservation appartient à l'utilisateur connecté.
     */
    protected function authorizeReservationAccess(Reservation $reservation): void
    {
        if ($reservation->user_id !== auth()->id()) {
            abort(403, 'Accès non autorisé à cette réservation.');
        }
    }
}
