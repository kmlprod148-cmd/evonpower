<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Console\Command;

class DiagnoseClientReservations extends Command
{
    protected $signature = 'reservations:diagnose {user_id? : ID de l\'utilisateur client}';
    protected $description = 'Diagnostique pourquoi les réservations ne s\'affichent pas pour un client';

    public function handle(): int
    {
        $userId = $this->argument('user_id');
        if (!$userId) {
            $userId = $this->ask('Entrez l\'ID de l\'utilisateur client');
        }

        $user = User::find($userId);
        if (!$user) {
            $this->error("Utilisateur #{$userId} introuvable.");
            return 1;
        }

        $this->info("=== Diagnostic des réservations pour: {$user->name} (ID: {$user->id}) ===");
        $this->newLine();

        $roles = $user->getRoleNames()->toArray();
        $this->line("Rôles: " . implode(', ', $roles));
        $this->line("integrator_id: " . ($user->integrator_id ?? 'null') . " | partner_id: " . ($user->partner_id ?? 'null'));
        $this->newLine();

        // Nombre exact comme sur la page /reservations (scope visibleToUser)
        $visibleCount = Reservation::query()->visibleToUser($user)->count();
        $this->info("Réservations visibles (page /reservations): <fg=green>{$visibleCount}</>");
        $this->newLine();

        // Réservations par user_id
        $byUserId = Reservation::where('user_id', $user->id)->count();
        $this->line("Réservations avec user_id = {$user->id}: <fg=green>{$byUserId}</>");

        // Réservations par guest_email
        $email = trim($user->email ?? '');
        $byEmail = 0;
        if ($email) {
            $byEmail = Reservation::whereRaw('LOWER(TRIM(guest_email)) = ?', [strtolower($email)])->count();
            $this->line("Réservations avec guest_email = '{$email}': <fg=green>{$byEmail}</>");
        } else {
            $this->line("Email utilisateur vide - pas de correspondance guest_email possible");
        }

        // Réservations par guest_phone
        $phone = trim($user->phone ?? '');
        $byPhone = 0;
        if ($phone) {
            $byPhone = Reservation::where('guest_phone', $phone)->count();
            $this->line("Réservations avec guest_phone = '{$phone}': <fg=green>{$byPhone}</>");
        } else {
            $this->line("Téléphone utilisateur vide - pas de correspondance guest_phone possible");
        }

        $total = $byUserId + $byEmail + $byPhone;
        $this->newLine();
        $this->info("Total réservations accessibles: {$total}");
        $this->newLine();

        if ($visibleCount === 0) {
            $this->warn("Aucune réservation trouvée. Vérifiez que:");
            $this->line("  - Les réservations ont bien user_id = {$user->id} OU");
            $this->line("  - guest_email correspond à '{$email}' OU");
            $this->line("  - guest_phone correspond à '{$phone}'");
            if (in_array('operator', array_map('strtolower', $roles))) {
                $this->line("  - Opérateur: les bornes ont user_id/created_by/group_id/integrator_id correct");
            }
            $this->newLine();
            $recent = Reservation::latest()->take(5)->get(['id', 'user_id', 'charging_point_id', 'guest_email', 'guest_phone', 'status', 'created_at']);
            if ($recent->isNotEmpty()) {
                $this->line("Dernières réservations en base:");
                $this->table(['ID', 'user_id', 'cp_id', 'guest_email', 'guest_phone', 'status', 'created_at'], $recent->map(fn ($r) => [$r->id, $r->user_id, $r->charging_point_id, substr($r->guest_email ?? '-', 0, 25), substr($r->guest_phone ?? '-', 0, 12), is_object($r->status) ? $r->status->value : $r->status, $r->created_at->format('Y-m-d H:i')]));
            } else {
                $this->line("Aucune réservation en base de données.");
            }
        }

        return 0;
    }
}
