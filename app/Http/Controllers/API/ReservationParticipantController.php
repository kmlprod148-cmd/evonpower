<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\ReservationParticipant;
use App\Services\ReservationParticipantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservationParticipantController extends Controller
{
    public function index(Reservation $reservation): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !Reservation::visibleToUser($user)->where('id', $reservation->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé'], 403);
        }

        $participants = $reservation->participants()->with('user')->get()->map(function ($participant) {
            return [
                'id' => $participant->id,
                'user_id' => $participant->user_id,
                'user_name' => $participant->user?->name,
                'share_type' => $participant->share_type,
                'share_value' => (float) $participant->share_value,
                'share_amount' => (float) $participant->share_amount,
                'paid_amount' => (float) $participant->paid_amount,
                'status' => $participant->status,
                'remaining' => $participant->remaining_amount,
            ];
        });

        return response()->json([
            'success' => true,
            'reservation_id' => $reservation->id,
            'participants' => $participants,
        ]);
    }

    public function pay(Request $request, Reservation $reservation, ReservationParticipant $participant, ReservationParticipantService $service): JsonResponse
    {
        $user = Auth::user();
        if (!$user || !Reservation::visibleToUser($user)->where('id', $reservation->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Accès non autorisé'], 403);
        }

        if ($participant->reservation_id !== $reservation->id) {
            return response()->json(['success' => false, 'message' => 'Participant invalide'], 404);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|in:wallet,external',
        ]);

        try {
            $result = $service->recordParticipantPayment(
                $participant,
                (float) $validated['amount'],
                'manual',
                $validated['payment_method'] ?? 'wallet'
            );

            return response()->json(array_merge(['success' => true], $result));
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
