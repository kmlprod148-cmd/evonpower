<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\ChargingPoint;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class GuestReservationController extends Controller
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Afficher la liste des réservations d'invités
     */
    public function index()
    {
        // Récupérer les réservations d'invités depuis la base de données
        $reservations = DB::table('reservations')
            ->join('charging_points', 'reservations.charging_point_id', '=', 'charging_points.id')
            ->join('stations', 'charging_points.station_id', '=', 'stations.id')
            ->whereNotNull('reservations.guest_email')
            ->select(
                'reservations.*',
                'charging_points.name as charging_point_name',
                'charging_points.power_output',
                'stations.name as station_name',
                'stations.address as station_address'
            )
            ->orderBy('reservations.start_time', 'desc')
            ->paginate(10);

        return view('guest.reservations', compact('reservations'));
    }

    /**
     * Afficher le formulaire de création
     */
    public function create()
    {
        // Récupérer les points de charge disponibles
        $chargingPoints = DB::table('charging_points')
            ->join('stations', 'charging_points.station_id', '=', 'stations.id')
            ->where('charging_points.status', 'online')
            ->select(
                'charging_points.id',
                'charging_points.name',
                'charging_points.power_output',
                'charging_points.connector_type',
                'stations.name as station_name',
                'stations.address as station_address',
                'stations.city'
            )
            ->get();

        return view('guest.reservations.create', compact('chargingPoints'));
    }

    /**
     * Stocker une nouvelle réservation
     */
    public function store(Request $request)
    {
        $request->validate([
            'charging_point_id' => 'required|exists:charging_points,id',
            'start_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required',
            'duration_minutes' => 'required|integer|min:30|max:480',
            'energy_kwh' => 'required|numeric|min:5|max:100',
            'guest_email' => 'required|email',
            'guest_phone' => 'required|string|min:10',
            'payment_type' => 'required|in:card,wallet,subscription',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            // Calculer les coûts
            $chargingPoint = ChargingPoint::with('pricingPlan')
                ->findOrFail($request->charging_point_id);

            if (!$chargingPoint->pricingPlan) {
                throw new \Exception('Charging point has no associated pricing plan.');
            }

            $energyCost = $request->energy_kwh * $chargingPoint->pricingPlan->price_per_kwh;
            $totalCost = $energyCost + $chargingPoint->pricingPlan->activation_fee;

            // Créer la réservation avec seulement les colonnes existantes
            $reservationData = [
                'status' => 'confirmed',
                'estimated_cost' => $totalCost,
                'estimated_energy' => $request->energy_kwh,
                'estimated_duration' => $request->duration_minutes,
                'notes' => $request->notes,
                'charging_point_id' => $request->charging_point_id,
                'pricing_plan_id' => $chargingPoint->pricingPlan->id,
                'start_time' => $this->parseDateTime($request->start_date, $request->start_time),
                'end_time' => $this->parseDateTime($request->start_date, $request->start_time)->addMinutes($request->duration_minutes),
                'reservation_type' => 'kwh', // Utiliser 'kwh' au lieu de 'standard'
                'reservation_value' => $request->energy_kwh,
                'max_duration' => $request->duration_minutes + 60,
                'max_energy' => $request->energy_kwh + 10,
                'payment_type' => 'cmi', // Utiliser 'cmi' au lieu de 'card'
                'order_id' => 'ORDER-' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                'guest_email' => $request->guest_email,
                'guest_phone' => $request->guest_phone,
            ];
            
            // Ajouter les colonnes supplémentaires seulement si elles existent
            if (Schema::hasColumn('reservations', 'confirmed_at')) {
                $reservationData['confirmed_at'] = now();
            }
            if (Schema::hasColumn('reservations', 'amount')) {
                $reservationData['amount'] = $totalCost;
            }
            if (Schema::hasColumn('reservations', 'energy_kwh')) {
                $reservationData['energy_kwh'] = $request->energy_kwh;
            }
            if (Schema::hasColumn('reservations', 'duration_minutes')) {
                $reservationData['duration_minutes'] = $request->duration_minutes;
            }
            
            $reservation = Reservation::create($reservationData);

            // Trigger transaction creation
            $adminIntegratorTransaction = $this->transactionService->createAdminIntegratorTransaction($reservation);
            if ($adminIntegratorTransaction) {
                $this->transactionService->createIntegratorOperatorTransaction($reservation, $adminIntegratorTransaction);
            } else {
                Log::warning("Admin->Integrator transaction failed for reservation ID {$reservation->id}. Skipping Integrator->Operator transaction.");
            }

            return redirect()->route('charging-points.thank-you', $reservation)
                ->with('success', 'Réservation créée avec succès !');

        } catch (\Exception $e) {
            Log::error("Error creating reservation: " . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors de la création de la réservation : ' . $e->getMessage());
        }
    }

    /**
     * Afficher une réservation spécifique
     */
    public function show($id)
    {
        $reservation = Reservation::with(['chargingPoint.station', 'chargingPoint.pricingPlan'])
            ->where('id', $id)
            ->first();

        if (!$reservation) {
            return redirect()->route('guest.reservations.index')
                ->with('error', 'Réservation non trouvée');
        }

        return view('guest.reservations.show', compact('reservation'));
    }

    /**
     * Afficher le formulaire d'édition
     */
    public function edit($id)
    {
        $reservation = DB::table('reservations')
            ->join('charging_points', 'reservations.charging_point_id', '=', 'charging_points.id')
            ->join('stations', 'charging_points.station_id', '=', 'stations.id')
            ->where('reservations.id', $id)
            ->select(
                'reservations.*',
                'charging_points.name as charging_point_name',
                'charging_points.power_output',
                'stations.name as station_name'
            )
            ->first();

        if (!$reservation) {
            return redirect()->route('guest.reservations.index')
                ->with('error', 'Réservation non trouvée');
        }

        // Récupérer les points de charge disponibles
        $chargingPoints = DB::table('charging_points')
            ->join('stations', 'charging_points.station_id', '=', 'stations.id')
            ->where('charging_points.status', 'online')
            ->select(
                'charging_points.id',
                'charging_points.name',
                'charging_points.power_output',
                'stations.name as station_name'
            )
            ->get();

        return view('guest.reservations.edit', compact('reservation', 'chargingPoints'));
    }

    /**
     * Mettre à jour une réservation
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'charging_point_id' => 'required|exists:charging_points,id',
            'start_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required',
            'duration_minutes' => 'required|integer|min:30|max:480',
            'energy_kwh' => 'required|numeric|min:5|max:100',
            'guest_email' => 'required|email',
            'guest_phone' => 'required|string|min:10',
            'payment_type' => 'required|in:card,wallet,subscription',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            // Vérifier que la réservation existe
            $reservation = Reservation::find($id);
            if (!$reservation) {
                return redirect()->route('guest.reservations.index')
                    ->with('error', 'Réservation non trouvée');
            }

            // Calculer les nouveaux coûts
            $chargingPoint = ChargingPoint::with('pricingPlan')
                ->findOrFail($request->charging_point_id);

            if (!$chargingPoint->pricingPlan) {
                throw new \Exception('Charging point has no associated pricing plan.');
            }

            $pricingPlan = $chargingPoint->pricingPlan;

            // VALIDATION CRITIQUE: Vérifier la limite maximale de durée
            if ($pricingPlan->max_duration && $request->duration_minutes > $pricingPlan->max_duration) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', "La durée de réservation ne peut pas dépasser {$pricingPlan->max_duration} minutes selon le plan tarifaire associé à ce point de charge.");
            }

            // VALIDATION: Vérifier la limite d'énergie basée sur max_duration
            if ($pricingPlan->max_duration && $chargingPoint->power_output) {
                $maxEnergy = $chargingPoint->power_output * ($pricingPlan->max_duration / 60);
                if ($request->energy_kwh > $maxEnergy) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', "La quantité d'énergie ne peut pas dépasser " . number_format($maxEnergy, 2) . " kWh (équivalent à {$pricingPlan->max_duration} minutes) selon le plan tarifaire associé à ce point de charge.");
                }
            }

            $energyCost = $request->energy_kwh * $pricingPlan->price_per_kwh;
            $totalCost = $energyCost + $pricingPlan->activation_fee;

            // Mettre à jour la réservation avec les valeurs correctes de max_duration et max_energy
            $reservation->update([
                'estimated_cost' => $totalCost,
                'estimated_energy' => $request->energy_kwh,
                'estimated_duration' => $request->duration_minutes,
                'notes' => $request->notes,
                'charging_point_id' => $request->charging_point_id,
                'start_time' => $this->parseDateTime($request->start_date, $request->start_time),
                'end_time' => $this->parseDateTime($request->start_date, $request->start_time)->addMinutes($request->duration_minutes),
                'amount' => $totalCost,
                'reservation_value' => $request->reservation_type === 'kwh' ? $request->energy_kwh : $request->duration_minutes,
                'energy_kwh' => $request->energy_kwh,
                'duration_minutes' => $request->duration_minutes,
                'max_duration' => $pricingPlan->max_duration, // Utiliser la limite du plan, pas une valeur calculée
                'max_energy' => $pricingPlan->max_duration && $chargingPoint->power_output ? 
                    ($chargingPoint->power_output * ($pricingPlan->max_duration / 60)) : null,
                'payment_type' => $request->payment_type,
                'guest_email' => $request->guest_email,
                'guest_phone' => $request->guest_phone,
            ]);

            return redirect()->route('guest.reservations.show', $id)
                ->with('success', 'Réservation mise à jour avec succès !');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erreur lors de la mise à jour de la réservation : ' . $e->getMessage());
        }
    }

    /**
     * Parse date and time safely
     */
    private function parseDateTime($date, $time)
    {
        try {
            if ($time === 'immediate') {
                return now();
            }
            return Carbon::parse($date . ' ' . $time);
        } catch (\Exception $e) {
            \Log::warning('Invalid date/time format, using current time', [
                'date' => $date,
                'time' => $time,
                'error' => $e->getMessage()
            ]);
            return now();
        }
    }

    /**
     * Supprimer une réservation
     */
    public function destroy($id)
    {
        try {
            $reservation = Reservation::find($id);
            
            if (!$reservation) {
                return redirect()->route('guest.reservations.index')
                    ->with('error', 'Réservation non trouvée');
            }

            // Vérifier si la réservation peut être annulée
            if (in_array($reservation->status, ['completed', 'cancelled'])) {
                return redirect()->route('guest.reservations.index')
                    ->with('error', 'Cette réservation ne peut pas être annulée');
            }

            // Marquer comme annulée
            $reservation->update([
                'status' => 'cancelled',
            ]);

            return redirect()->route('guest.reservations.index')
                ->with('success', 'Réservation annulée avec succès !');

        } catch (\Exception $e) {
            return redirect()->route('guest.reservations.index')
                ->with('error', 'Erreur lors de l\'annulation de la réservation : ' . $e->getMessage());
        }
    }
}
