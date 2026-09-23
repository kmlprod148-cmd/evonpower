<?php

namespace App\Console\Commands;

use App\Enums\ReservationStatus;
use App\Models\ChargingPoint;
use App\Models\PricingPlan;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Console\Command;

class CreateTestReservation extends Command
{
    protected $signature = 'reservations:create-test {user_id=13 : ID du client}';
    protected $description = 'Crée une réservation de test pour un client (pour vérifier la visibilité)';

    public function handle(): int
    {
        $userId = (int) $this->argument('user_id');
        $user = User::find($userId);

        if (!$user) {
            $this->error("Utilisateur #{$userId} introuvable.");
            return 1;
        }

        $chargingPoint = ChargingPoint::first();
        $pricingPlan = $chargingPoint?->pricingPlan ?? PricingPlan::where('is_active', true)->first();

        if (!$chargingPoint || !$pricingPlan) {
            $this->error('Aucune borne ou plan tarifaire trouvé. Créez d\'abord des données de base.');
            return 1;
        }

        $cost = 5.00;

        $reservation = Reservation::create([
            'user_id' => $user->id,
            'charging_point_id' => $chargingPoint->id,
            'pricing_plan_id' => $pricingPlan->id,
            'reservation_type' => 'minute',
            'reservation_value' => 10,
            'start_time' => now(),
            'estimated_cost' => $cost,
            'amount' => $cost,
            'status' => ReservationStatus::CONFIRMED,
            'confirmed_at' => now(),
            'payment_status' => 'PAID',
            'payment_method' => 'prepaid_credit',
            'payment_type' => 'cmi',
            'guest_email' => $user->email,
            'guest_phone' => $user->phone,
        ]);

        $this->info("Réservation de test créée : #{$reservation->id}");
        $this->line("  - Client: {$user->name} (ID: {$user->id})");
        $this->line("  - Borne: {$chargingPoint->name} (ID: {$chargingPoint->id})");
        $this->line("  - Montant: {$cost} EUR");
        $this->newLine();
        $this->info('Exécutez: php artisan reservations:diagnose ' . $userId . ' pour vérifier.');

        return 0;
    }
}
