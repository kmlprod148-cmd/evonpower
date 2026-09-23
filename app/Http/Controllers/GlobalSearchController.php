<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Models\ChargingSession;
use App\Models\Reservation;
use App\Models\Transaction;
use App\Models\Client;
use App\Models\OcppTag;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class GlobalSearchController extends Controller
{
    /**
     * Handle global search requests
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([
                'results' => [],
                'total' => 0
            ]);
        }

        $results = [];
        $user = Auth::user();
        
        // Search Charging Points
        $chargingPoints = ChargingPoint::where('name', 'like', "%{$query}%")
            ->orWhere('serial_number', 'like', "%{$query}%")
            ->orWhere('identifier', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function ($cp) {
                return [
                    'type' => 'charging_point',
                    'id' => $cp->id,
                    'title' => $cp->name,
                    'subtitle' => $cp->serial_number ?? $cp->identifier ?? 'N/A',
                    'url' => route('charging-points.show', $cp->id),
                    'icon' => 'bolt'
                ];
            });
        $results = array_merge($results, $chargingPoints->toArray());

        // Search Reservations (if user has permission)
        if ($user->can('view reservations') || $user->hasRole('admin') || $user->hasRole('super-admin')) {
            $reservations = Reservation::where('id', 'like', "%{$query}%")
                ->orWhere('guest_email', 'like', "%{$query}%")
                ->orWhere('guest_phone', 'like', "%{$query}%")
                ->limit(5)
                ->get()
                ->map(function ($res) {
                    return [
                        'type' => 'reservation',
                        'id' => $res->id,
                        'title' => 'Reservation #' . $res->id,
                        'subtitle' => $res->guest_email ?? $res->guest_phone ?? 'N/A',
                        'url' => route('reservations.show', $res->id),
                        'icon' => 'calendar'
                    ];
                });
            $results = array_merge($results, $reservations->toArray());
        }

        // Search Transactions (if user has permission)
        if ($user->can('view transactions') || $user->hasRole('admin') || $user->hasRole('super-admin')) {
            $transactions = Transaction::where('transaction_id', 'like', "%{$query}%")
                ->orWhere('reference', 'like', "%{$query}%")
                ->limit(5)
                ->get()
                ->map(function ($txn) {
                    return [
                        'type' => 'transaction',
                        'id' => $txn->id,
                        'title' => 'Transaction #' . ($txn->transaction_id ?? $txn->id),
                        'subtitle' => $txn->reference ?? number_format($txn->amount, 2) . ' ' . ($txn->currency ?? 'EUR'),
                        'url' => route('transactions.show', $txn->id),
                        'icon' => 'credit-card'
                    ];
                });
            $results = array_merge($results, $transactions->toArray());
        }

        // Search Clients (if user has permission)
        if ($user->can('view clients') || $user->hasRole('admin') || $user->hasRole('super-admin')) {
            $clients = Client::where('name', 'like', "%{$query}%")
                ->orWhere('email', 'like', "%{$query}%")
                ->orWhere('phone', 'like', "%{$query}%")
                ->limit(5)
                ->get()
                ->map(function ($client) {
                    return [
                        'type' => 'client',
                        'id' => $client->id,
                        'title' => $client->name,
                        'subtitle' => $client->email ?? $client->phone ?? 'N/A',
                        'url' => route('clients.show', $client->id),
                        'icon' => 'users'
                    ];
                });
            $results = array_merge($results, $clients->toArray());
        }

        // Search OCPP Tags
        $ocppTags = OcppTag::where('id_tag', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function ($tag) {
                return [
                    'type' => 'ocpp_tag',
                    'id' => $tag->id,
                    'title' => $tag->id_tag,
                    'subtitle' => $tag->blocked ? 'Blocked' : 'Active',
                    'url' => route('ocpp-tags.show', $tag->id),
                    'icon' => 'tag'
                ];
            });
        $results = array_merge($results, $ocppTags->toArray());

        // Sort by relevance (exact matches first, then starts with, then contains)
        usort($results, function ($a, $b) {
            $query = request()->input('q', '');
            
            $aExact = stripos($a['title'], $query) === 0;
            $bExact = stripos($b['title'], $query) === 0;
            
            if ($aExact && !$bExact) return -1;
            if (!$aExact && $bExact) return 1;
            
            return 0;
        });

        // Limit total results
        $results = array_slice($results, 0, 10);

        return response()->json([
            'results' => $results,
            'total' => count($results),
            'query' => $query
        ]);
    }
}
