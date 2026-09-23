<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Jobs\StartChargingSessionJob;
use App\Events\ReservationApproved;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

/**
 * Contrôleur pour l'approbation des réservations
 * 
 * FLUX D'APPROBATION:
 * 1. Admin approuve manuellement OU
 * 2. Paiement carte de crédit approuve automatiquement
 * 3. → Réservation status = APPROVED
 * 4. → À l'heure start_time, démarrage automatique via Job
 */
class ReservationApprovalController extends Controller
{
    /**
     * Afficher la liste des réservations en attente d'approbation
     */
    public function index(Request $request)
    {
        // Vérifier les permissions
        Gate::authorize('approve', Reservation::class);

        $reservations = Reservation::with([
            'user',
            'chargingPoint',
            'connector',
            'pricingPlan',
            'ocppTag'
        ])
        ->whereIn('status', ['pending', 'pending_confirmation'])
        ->where(function ($query) {
            $query->whereIn('payment_status', ['PENDING', 'PAID', 'PAYE', 'pending', 'paid', 'paye']);
        })
        ->visibleToUser(auth()->user())
        ->orderBy('created_at', 'desc')
        ->paginate(20);

        return view('reservations.approval.index', compact('reservations'));
    }

    /**
     * Afficher le formulaire d'approbation d'une réservation
     */
    public function show(Reservation $reservation)
    {
        Gate::authorize('approve', $reservation);

        $reservation->load([
            'user.ocppTags',
            'chargingPoint',
            'connector',
            'pricingPlan',
            'ocppTag',
            'chargingSessions'
        ]);

        return view('reservations.approval.show', compact('reservation'));
    }

    /**
     * Approuver une réservation manuellement (admin)
     */
    public function approve(Request $request, Reservation $reservation)
    {
        Gate::authorize('approve', $reservation);

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        Log::info("ReservationApprovalController: Approbation admin", [
            'reservation_id' => $reservation->id,
            'admin_id' => auth()->id(),
        ]);

        try {
            // Approuver la réservation
            $reservation->approve(auth()->user());

            // Déclencher l'événement pour l'auto RemoteStart
            event(new ReservationApproved($reservation->fresh(), (string) auth()->id(), 'admin'));

            // Ajouter des notes si fournies
            if ($request->notes) {
                $reservation->update([
                    'notes' => ($reservation->notes ?? '') . "\n[" . now() . "] Admin: " . $request->notes
                ]);
            }

            Log::info("ReservationApprovalController: Réservation approuvée", [
                'reservation_id' => $reservation->id,
            ]);

            // Si l'heure de début est maintenant, démarrer immédiatement
            if ($reservation->canStartNow()) {
                Log::info("ReservationApprovalController: Démarrage immédiat", [
                    'reservation_id' => $reservation->id,
                ]);

                StartChargingSessionJob::dispatch($reservation->id);

                return redirect()
                    ->route('reservations.approval.index')
                    ->with('success', 'Réservation approuvée et session de charge démarrée.');
            }

            return redirect()
                ->route('reservations.approval.index')
                ->with('success', 'Réservation approuvée avec succès. La session démarrera à l\'heure prévue.');

        } catch (\Exception $e) {
            Log::error("ReservationApprovalController: Erreur lors de l'approbation", [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', "Erreur lors de l'approbation: {$e->getMessage()}");
        }
    }

    /**
     * Rejeter une réservation
     */
    public function reject(Request $request, Reservation $reservation)
    {
        Gate::authorize('approve', $reservation);

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        Log::info("ReservationApprovalController: Rejet", [
            'reservation_id' => $reservation->id,
            'admin_id' => auth()->id(),
            'reason' => $request->reason,
        ]);

        try {
            $reservation->update([
                'status' => 'cancelled',
                'payment_status' => 'FAILED',
                'notes' => ($reservation->notes ?? '') . "\n[" . now() . "] Rejeté par admin: " . $request->reason,
            ]);

            // TODO: Rembourser si prépayé
            // TODO: Envoyer notification à l'utilisateur

            return redirect()
                ->route('reservations.approval.index')
                ->with('success', 'Réservation rejetée.');

        } catch (\Exception $e) {
            Log::error("ReservationApprovalController: Erreur lors du rejet", [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', "Erreur lors du rejet: {$e->getMessage()}");
        }
    }

    /**
     * Démarrer manuellement une session approuvée
     * (utile pour déboguer ou forcer un démarrage)
     */
    public function startManually(Reservation $reservation)
    {
        Gate::authorize('approve', $reservation);

        Log::info("ReservationApprovalController: Démarrage manuel", [
            'reservation_id' => $reservation->id,
            'admin_id' => auth()->id(),
        ]);

        try {
            if (!$reservation->isApproved()) {
                return redirect()
                    ->back()
                    ->with('error', 'La réservation doit être approuvée avant de pouvoir démarrer.');
            }

            // Dispatcher le job de démarrage
            StartChargingSessionJob::dispatch($reservation->id);

            return redirect()
                ->back()
                ->with('success', 'Job de démarrage dispatché. Consultez les logs pour le suivi.');

        } catch (\Exception $e) {
            Log::error("ReservationApprovalController: Erreur lors du démarrage manuel", [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->back()
                ->with('error', "Erreur: {$e->getMessage()}");
        }
    }

    /**
     * Approuver par lot (bulk approval)
     */
    public function bulkApprove(Request $request)
    {
        Gate::authorize('approve', Reservation::class);

        $request->validate([
            'reservation_ids' => 'required|array',
            'reservation_ids.*' => 'exists:reservations,id',
        ]);

        Log::info("ReservationApprovalController: Approbation par lot", [
            'count' => count($request->reservation_ids),
            'admin_id' => auth()->id(),
        ]);

        $approved = 0;
        $failed = 0;

        foreach ($request->reservation_ids as $id) {
            try {
                $reservation = Reservation::findOrFail($id);
                
                // Vérifier les permissions
                if (!Gate::allows('approve', $reservation)) {
                    $failed++;
                    continue;
                }

                $reservation->approve(auth()->user());
                event(new ReservationApproved($reservation->fresh(), (string) auth()->id(), 'admin'));
                $approved++;

                // Démarrer si possible
                if ($reservation->canStartNow()) {
                    StartChargingSessionJob::dispatch($reservation->id);
                }

            } catch (\Exception $e) {
                Log::error("ReservationApprovalController: Erreur approbation lot", [
                    'reservation_id' => $id,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
            }
        }

        return redirect()
            ->route('reservations.approval.index')
            ->with('success', "{$approved} réservation(s) approuvée(s). {$failed} échec(s).");
    }
}

