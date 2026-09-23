<?php

namespace App\Services\Ocpp;

use App\Enums\ReservationStatus;
use App\Models\ChargingSession;
use App\Models\Connector;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChargingSessionReconciliationService
{
    public function reconcile(): ReconciliationReport
    {
        $report = new ReconciliationReport();
        $report->started_at = now();

        $this->reconcileSessionConnectorMismatches($report);
        $this->reconcileExpiredSessions($report);
        $this->reconcileOrphanedReservations($report);

        $report->completed_at = now();

        Log::info('Reconciliation completed', [
            'resolved' => $report->resolved_count,
            'errors' => $report->error_count,
        ]);

        return $report;
    }

    protected function reconcileSessionConnectorMismatches(ReconciliationReport $report): void
    {
        $sessions = ChargingSession::where('status', ChargingSession::STATUS_ACTIVE)
            ->whereHas('chargingPoint.connectors', function ($q) {
                $q->whereIn('status', ['Available', 'Unavailable']);
            })
            ->with(['chargingPoint.connectors', 'reservation'])
            ->get();

        foreach ($sessions as $session) {
            $connector = $session->chargingPoint->connectors
                ->where('connector_id', $session->connector_id)
                ->first();

            if ($connector && in_array($connector->status, ['Available', 'Unavailable'])) {
                Log::warning('Session-connector mismatch detected', [
                    'session_id' => $session->id,
                    'connector_status' => $connector->status,
                ]);

                if ($session->reservation && $session->reservation->expires_at < now()) {
                    $session->update(['status' => ChargingSession::STATUS_COMPLETED]);
                    $session->reservation->update(['status' => ReservationStatus::EXPIRED]);
                    $report->addResolved('session_timeout_due_to_mismatch', $session->id);
                }
            }
        }
    }

    protected function reconcileExpiredSessions(ReconciliationReport $report): void
    {
        Reservation::where('status', ReservationStatus::ACTIVE)
            ->where('expires_at', '<', now())
            ->with('chargingSession')
            ->get()
            ->each(function ($reservation) use ($report) {
                if ($reservation->chargingSession && $reservation->chargingSession->isActive()) {
                    $reservation->chargingSession->update([
                        'status' => ChargingSession::STATUS_COMPLETED,
                    ]);
                    $reservation->update(['status' => ReservationStatus::EXPIRED]);
                    $report->addResolved('expired_session_stopped', $reservation->id);
                }
            });
    }

    protected function reconcileOrphanedReservations(ReconciliationReport $report): void
    {
        Reservation::where('status', ReservationStatus::RESERVED)
            ->where('updated_at', '<', now()->subHours(2))
            ->whereDoesntHave('chargingSession')
            ->get()
            ->each(function ($reservation) use ($report) {
                $reservation->update(['status' => ReservationStatus::TIMEOUT_NO_TRANSACTION]);
                $report->addResolved('orphaned_reservation_cleaned', $reservation->id);
            });
    }
}

class ReconciliationReport
{
    public int $resolved_count = 0;
    public int $error_count = 0;
    public array $resolved = [];
    public array $errors = [];
    public ?\Carbon\Carbon $started_at = null;
    public ?\Carbon\Carbon $completed_at = null;

    public function addResolved(string $type, int $id): void
    {
        $this->resolved[] = ['type' => $type, 'id' => $id];
        $this->resolved_count++;
    }

    public function addError(string $type, int $id, string $message): void
    {
        $this->errors[] = ['type' => $type, 'id' => $id, 'message' => $message];
        $this->error_count++;
    }
}