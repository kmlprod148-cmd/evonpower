<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reservation;
use App\Models\User;
use App\Models\ChargingPoint;
use App\Enums\ReservationStatus;
use Carbon\Carbon;

class DemoReservationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('📅 Création des réservations de démonstration...');

        // Récupérer les utilisateurs de démo
        $users = User::where('email', 'like', '%@demo.evonpower.com')->get();
        $chargingPoints = ChargingPoint::all();

        if ($users->isEmpty() || $chargingPoints->isEmpty()) {
            $this->command->warn('⚠️ Aucun utilisateur ou borne de recharge trouvé. Exécutez d\'abord DemoUsersSeeder et DemoDataSeeder.');
            return;
        }

        $this->createDemoReservations($users, $chargingPoints);

        $this->command->info('✅ Réservations de démonstration créées avec succès !');
    }

    private function createDemoReservations($users, $chargingPoints): void
    {
        $reservations = [
            [
                'user_id' => $users->where('email', 'premium@demo.evonpower.com')->first()->id,
                'charging_point_id' => $chargingPoints->where('name', 'Borne Premium Paris')->first()->id,
                'start_time' => Carbon::now()->addHours(2),
                'end_time' => Carbon::now()->addHours(3),
                'status' => ReservationStatus::PENDING,
                'estimated_cost' => 25.00,
                'notes' => 'Réservation premium pour démonstration',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $users->where('email', 'standard@demo.evonpower.com')->first()->id,
                'charging_point_id' => $chargingPoints->where('name', 'Borne Standard Lyon')->first()->id,
                'start_time' => Carbon::now()->addHours(4),
                'end_time' => Carbon::now()->addHours(5),
                'status' => ReservationStatus::CONFIRMED,
                'estimated_cost' => 15.00,
                'notes' => 'Réservation standard confirmée',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $users->where('email', 'economy@demo.evonpower.com')->first()->id,
                'charging_point_id' => $chargingPoints->where('name', 'Borne Économique Marseille')->first()->id,
                'start_time' => Carbon::now()->addHours(6),
                'end_time' => Carbon::now()->addHours(7),
                'status' => ReservationStatus::CANCELLED,
                'estimated_cost' => 10.00,
                'notes' => 'Réservation économique annulée',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $users->where('email', 'enterprise@demo.evonpower.com')->first()->id,
                'charging_point_id' => $chargingPoints->where('name', 'Borne Entreprise Nantes')->first()->id,
                'start_time' => Carbon::now()->addHours(8),
                'end_time' => Carbon::now()->addHours(10),
                'status' => ReservationStatus::PENDING,
                'estimated_cost' => 40.00,
                'notes' => 'Réservation entreprise en attente',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $users->where('email', 'premium@demo.evonpower.com')->first()->id,
                'charging_point_id' => $chargingPoints->where('name', 'Borne Standard Lyon')->first()->id,
                'start_time' => Carbon::now()->subHours(2),
                'end_time' => Carbon::now()->subHour(),
                'status' => ReservationStatus::COMPLETED,
                'estimated_cost' => 18.00,
                'actual_cost' => 17.50,
                'notes' => 'Réservation terminée avec succès',
                'created_at' => Carbon::now()->subHours(3),
                'updated_at' => Carbon::now()->subHour(),
            ],
            [
                'user_id' => $users->where('email', 'standard@demo.evonpower.com')->first()->id,
                'charging_point_id' => $chargingPoints->where('name', 'Borne Économique Marseille')->first()->id,
                'start_time' => Carbon::now()->subHours(4),
                'end_time' => Carbon::now()->subHours(3),
                'status' => ReservationStatus::COMPLETED,
                'estimated_cost' => 12.00,
                'actual_cost' => 11.80,
                'notes' => 'Réservation économique terminée',
                'created_at' => Carbon::now()->subHours(5),
                'updated_at' => Carbon::now()->subHours(3),
            ],
            [
                'user_id' => $users->where('email', 'enterprise@demo.evonpower.com')->first()->id,
                'charging_point_id' => $chargingPoints->where('name', 'Borne Premium Paris')->first()->id,
                'start_time' => Carbon::now()->addHours(12),
                'end_time' => Carbon::now()->addHours(14),
                'status' => ReservationStatus::PENDING,
                'estimated_cost' => 35.00,
                'notes' => 'Réservation entreprise future',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $users->where('email', 'economy@demo.evonpower.com')->first()->id,
                'charging_point_id' => $chargingPoints->where('name', 'Borne Entreprise Nantes')->first()->id,
                'start_time' => Carbon::now()->addHours(16),
                'end_time' => Carbon::now()->addHours(18),
                'status' => ReservationStatus::PENDING,
                'estimated_cost' => 45.00,
                'notes' => 'Réservation longue durée',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($reservations as $reservationData) {
            try {
                Reservation::create($reservationData);
                $this->command->info("✅ Réservation créée: {$reservationData['notes']} - Statut: {$reservationData['status']->value}");
            } catch (\Exception $e) {
                $this->command->error("❌ Erreur lors de la création de la réservation: " . $e->getMessage());
            }
        }
    }
}
