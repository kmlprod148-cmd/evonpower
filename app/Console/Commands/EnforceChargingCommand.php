<?php

namespace App\Console\Commands;

use App\Models\ChargingSession;
use App\Services\ChargingSessionManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnforceChargingCommand extends Command
{
    protected $signature = 'evon:enforce-charging';
    protected $description = 'Applique les règles d’arrêt automatique (temps / solde / kWh) sur les sessions actives.';

    public function handle(ChargingSessionManager $manager): int
    {
        $sessions = ChargingSession::query()
            ->whereIn('status', ['active', 'in_progress'])
            ->with(['reservation', 'chargingPoint', 'user'])
            ->get();

        $checked = 0;
        $stopped = 0;

        foreach ($sessions as $session) {
            $checked++;

            $reservation = $session->reservation;
            if (!$reservation) {
                continue;
            }

            $reason = $this->shouldStopReason($session);
            if ($reason === null) {
                continue;
            }

            $result = $manager->stopChargingSession($session, $reason);
            if (($result['success'] ?? false) === true) {
                $stopped++;
            }
        }

        Log::info('EnforceChargingCommand: completed', [
            'checked' => $checked,
            'stopped' => $stopped,
        ]);

        $this->info("Sessions vérifiées: {$checked}, sessions arrêtées: {$stopped}");

        return Command::SUCCESS;
    }

    /**
     * Retourne un stop_reason (string) si la session doit être arrêtée, sinon null.
     */
    protected function shouldStopReason(ChargingSession $session): ?string
    {
        $reservation = $session->reservation;

        // 1) Fin de fenêtre de réservation
        if ($reservation?->end_time && now()->greaterThan($reservation->end_time)) {
            return 'reservation_end_time';
        }

        // 2) Limite max_duration (minutes)
        if ($reservation?->max_duration && $session->started_at) {
            $elapsedMinutes = $session->started_at->diffInMinutes(now());
            if ($elapsedMinutes >= (int) $reservation->max_duration) {
                return 'max_duration_reached';
            }
        }

        // 3) Limite minutes (reservation_type=minute)
        if (($reservation?->reservation_type === 'minute') && $reservation->reservation_value && $session->started_at) {
            $elapsedMinutes = $session->started_at->diffInMinutes(now());
            if ($elapsedMinutes >= (int) $reservation->reservation_value) {
                return 'reservation_minutes_reached';
            }
        }

        // 4) Limite kWh (reservation_type=kwh) via session.actual_energy si disponible
        // NOTE: la mesure temps réel dépend de la collecte des meterValues; on stoppe si la valeur est déjà connue.
        if (($reservation?->reservation_type === 'kwh') && $reservation->reservation_value !== null) {
            $energy = $session->actual_energy ?? null;
            if ($energy !== null && (float) $energy >= (float) $reservation->reservation_value) {
                return 'reservation_kwh_reached';
            }
        }

        // 5) Postpaid: solde insuffisant (seuil minimal)
        if ($reservation && method_exists($reservation, 'isPostpaid') && $reservation->isPostpaid()) {
            $user = $reservation->user;
            if ($user && method_exists($user, 'getOrCreateWallet')) {
                $wallet = $user->getOrCreateWallet();
                $min = $reservation->getMinThreshold();
                if (method_exists($wallet, 'hasSufficientBalance') && !$wallet->hasSufficientBalance($min)) {
                    return 'insufficient_balance';
                }
            }
        }

        return null;
    }
}


