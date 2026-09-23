<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\ChargingStation;
use App\Services\ReservationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GuestReservationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:guest {station : The ID of the charging station} {--duration=30 : The duration of the reservation in minutes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a guest reservation for a charging station';

    /**
     * Execute the console command.
     */
    public function handle(ReservationService $reservationService)
    {
        $stationId = $this->argument('station');
        $duration = $this->option('duration');

        $station = ChargingStation::find($stationId);

        if (!$station) {
            $this->error('Charging station not found.');
            return 1;
        }

        $guestUser = User::firstOrCreate(
            ['email' => 'guest@example.com'],
            [
                'name' => 'Guest User',
                'password' => Hash::make(Str::random(12)),
            ]
        );

        $this->info('Creating reservation...');
        $bar = $this->output->createProgressBar(3);
        $bar->start();

        try {
            $bar->advance();

            $reservation = $reservationService->createReservation($guestUser, $station, $duration);
            $bar->advance();

            $this->line("\n");
            $this->info('Reservation created successfully!');
            $this->table(
                ['ID', 'Station', 'User', 'Start Time', 'Duration', 'Amount', 'Status'],
                [
                    [
                        $reservation->id,
                        $station->name,
                        $guestUser->name,
                        $reservation->start_time,
                        "{$reservation->duration_minutes} minutes",
                        "{$reservation->amount} EUR",
                        $reservation->status,
                    ],
                ]
            );
            $bar->finish();
            $this->line("\n");

            return 0;
        } catch (\Exception $e) {
            $bar->finish();
            $this->error("\nFailed to create reservation: {$e->getMessage()}");
            return 1;
        }
    }
}
