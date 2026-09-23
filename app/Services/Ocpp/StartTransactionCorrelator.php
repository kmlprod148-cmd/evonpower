<?php

namespace App\Services\Ocpp;

use App\Models\Reservation;
use App\Models\OcppTag;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StartTransactionCorrelator
{
    public function findMatchingReservation(array $payload, string $chargeBoxId): ?Reservation
    {
        $connectorId = $payload['connectorId'] ?? null;
        $idTag = $payload['idTag'] ?? null;
        $timestamp = isset($payload['timestamp']) ? Carbon::parse($payload['timestamp']) : now();

        return DB::transaction(function () use ($chargeBoxId, $connectorId, $idTag, $timestamp) {
            $query = Reservation::where('status', 'RESERVED')
                ->whereHas('chargingPoint', function ($q) use ($chargeBoxId) {
                    $q->where('charge_box_id', $chargeBoxId);
                })
                ->whereNull('transaction_id');

            if ($connectorId) {
                $query->where('connector_id', $connectorId);
            }

            $tag = OcppTag::where('tag', $idTag)->first();
            if ($tag) {
                $query->where('ocpp_tag_id', $tag->id);
            }

            $query->where('start_time', '<=', $timestamp)
                ->where('expires_at', '>=', $timestamp);

            return $query->lockForUpdate()->first();
        });
    }

    public function correlateMatch(Reservation $reservation, array $transactionData): void
    {
        $reservation->update([
            'transaction_id' => $transactionData['transactionId'] ?? null,
            'status' => 'SESSION_INITIATED',
            'actual_start_time' => now(),
        ]);
    }
}