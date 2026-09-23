<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Transaction;
use App\Models\Reservation;
use App\Models\ChargingPoint;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * PartnerAnalyticsController
 *
 * Gère l'analyse des statistiques, transactions, et demandes de paiement d'un partenaire,
 * ainsi que la validation des paiements associés.
 */
class PartnerAnalyticsController extends Controller
{
    /**
     * Dashboard analytique principal d'un partenaire.
     * Affiche les stats générales, graphiques de revenus, transactions récentes,
     * et les demandes de paiement en attente de validation.
     */
    public function index(Request $request, Partner $partner)
    {
        $this->authorizePartnerAccess($partner);

        $period = $request->get('period', '30'); // jours
        $startDate = Carbon::now()->subDays((int) $period);
        $endDate = Carbon::now();

        // ====================================================================
        // STATISTIQUES GLOBALES
        // ====================================================================
        $stats = $this->buildStats($partner, $startDate, $endDate);

        // ====================================================================
        // DONNÉES DES GRAPHIQUES (revenus par jour sur la période)
        // ====================================================================
        $revenueChartData = $this->buildRevenueChartData($partner, $startDate, $endDate);

        // ====================================================================
        // TRANSACTIONS RÉCENTES
        // ====================================================================
        $transactions = $this->getPartnerTransactions($partner)
            ->with(['user', 'chargingPoint'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // ====================================================================
        // DEMANDES DE PAIEMENT (réservations en attente de paiement)
        // ====================================================================
        $pendingPayments = $this->getPendingPaymentRequests($partner);

        // ====================================================================
        // DISTRIBUTIONS PAR BORNE
        // ====================================================================
        $chargingPointStats = $this->getChargingPointDistribution($partner, $startDate, $endDate);

        // ====================================================================
        // KPIs COMPARATIFS (période actuelle vs période précédente)
        // ====================================================================
        $previousStart = (clone $startDate)->subDays((int) $period);
        $previousEnd   = clone $startDate;
        $kpis = $this->buildKpis($partner, $startDate, $endDate, $previousStart, $previousEnd);

        return view('partners.analytics', compact(
            'partner',
            'stats',
            'revenueChartData',
            'transactions',
            'pendingPayments',
            'chargingPointStats',
            'kpis',
            'period'
        ));
    }

    /**
     * Détail d'une transaction spécifique d'un partenaire
     */
    public function transactionDetail(Request $request, Partner $partner, Transaction $transaction)
    {
        $this->authorizePartnerAccess($partner);

        // Vérifier que la transaction appartient au partenaire
        $this->ensureTransactionBelongsToPartner($partner, $transaction);

        $transaction->load(['user', 'chargingPoint', 'repartition', 'transactionDetail']);

        return view('partners.transaction-detail', compact('partner', 'transaction'));
    }

    /**
     * Liste de toutes les demandes de paiement d'un partenaire (avec filtres)
     */
    public function paymentRequests(Request $request, Partner $partner)
    {
        $this->authorizePartnerAccess($partner);

        $status = $request->get('status', 'all');
        $period = $request->get('period', '90');
        $startDate = Carbon::now()->subDays((int) $period);

        $query = $this->getPendingPaymentRequestsQuery($partner)
            ->with(['user', 'chargingPoint', 'transaction']);

        // Filtre par statut de paiement
        if ($status !== 'all') {
            $query->where('payment_status', strtoupper($status));
        }

        // Filtre par date
        $query->where('created_at', '>=', $startDate);

        $paymentRequests = $query->orderBy('created_at', 'desc')->paginate(20);

        // Résumé des montants
        $summary = [
            'total_pending'    => $this->getPendingPaymentRequestsQuery($partner)->whereIn('payment_status', ['PENDING', null])->sum('estimated_cost'),
            'total_paid'       => $this->getPendingPaymentRequestsQuery($partner)->where('payment_status', 'PAID')->sum('actual_cost'),
            'total_failed'     => $this->getPendingPaymentRequestsQuery($partner)->where('payment_status', 'FAILED')->sum('estimated_cost'),
            'count_pending'    => $this->getPendingPaymentRequestsQuery($partner)->whereIn('payment_status', ['PENDING', null])->count(),
            'count_paid'       => $this->getPendingPaymentRequestsQuery($partner)->where('payment_status', 'PAID')->count(),
        ];

        return view('partners.payment-requests', compact('partner', 'paymentRequests', 'summary', 'status', 'period'));
    }

    /**
     * Valider un paiement (confirmer une réservation depuis le partenaire)
     */
    public function validatePayment(Request $request, Partner $partner, Reservation $reservation)
    {
        $this->authorizePartnerAccess($partner);
        $this->ensureReservationBelongsToPartner($partner, $reservation);

        $request->validate([
            'action' => 'required|in:approve,reject',
            'notes'  => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $user = Auth::user();

            if ($request->action === 'approve') {
                // Approuver la réservation
                $reservation->update([
                    'status'        => 'confirmed',
                    'payment_status'=> 'PAID',
                    'approved_at'   => now(),
                    'approved_by'   => $user->id,
                ]);

                // Mettre à jour la transaction associée si elle existe
                if ($reservation->transaction) {
                    $reservation->transaction->update([
                        'status'       => 'completed',
                        'processed_at' => now(),
                    ]);
                }

                $message = 'Le paiement a été validé avec succès.';

                Log::info('PartnerAnalyticsController: Payment validated by partner', [
                    'partner_id'     => $partner->id,
                    'reservation_id' => $reservation->id,
                    'validated_by'   => $user->id,
                ]);

            } else {
                // Rejeter la réservation
                $reservation->update([
                    'status'         => 'canceled',
                    'payment_status' => 'FAILED',
                ]);

                if ($reservation->transaction) {
                    $reservation->transaction->update([
                        'status'         => 'failed',
                        'failed_at'      => now(),
                        'failure_reason' => $request->notes ?? 'Rejeté par le partenaire',
                    ]);
                }

                $message = 'La demande de paiement a été rejetée.';

                Log::info('PartnerAnalyticsController: Payment rejected by partner', [
                    'partner_id'     => $partner->id,
                    'reservation_id' => $reservation->id,
                    'rejected_by'    => $user->id,
                ]);
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'action'  => $request->action,
                ]);
            }

            return redirect()
                ->route('partners.analytics.payment-requests', $partner)
                ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PartnerAnalyticsController: Error validating payment', [
                'partner_id'     => $partner->id,
                'reservation_id' => $reservation->id,
                'error'          => $e->getMessage(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Une erreur est survenue lors de la validation.');
        }
    }

    /**
     * Export des transactions en CSV
     */
    public function exportTransactions(Request $request, Partner $partner)
    {
        $this->authorizePartnerAccess($partner);

        $period = $request->get('period', '30');
        $startDate = Carbon::now()->subDays((int) $period);

        $transactions = $this->getPartnerTransactions($partner)
            ->with(['user', 'chargingPoint'])
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'transactions_partenaire_' . $partner->id . '_' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, [
                'ID Transaction',
                'Date',
                'Client',
                'Borne',
                'Montant (€)',
                'Statut',
                'Énergie (kWh)',
                'Durée (min)',
            ], ';');

            foreach ($transactions as $tx) {
                fputcsv($file, [
                    $tx->id,
                    $tx->created_at?->format('d/m/Y H:i'),
                    $tx->user?->name ?? 'Inconnu',
                    $tx->chargingPoint?->name ?? 'Inconnu',
                    number_format($tx->amount ?? 0, 2, ',', ' '),
                    ucfirst($tx->status ?? ''),
                    number_format($tx->energy_consumed_wh ? $tx->energy_consumed_wh / 1000 : 0, 2, ',', ' '),
                    $tx->duration_minutes ?? 0,
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Données en temps réel pour les graphiques (AJAX)
     */
    public function chartData(Request $request, Partner $partner)
    {
        $this->authorizePartnerAccess($partner);

        $period = $request->get('period', '30');
        $startDate = Carbon::now()->subDays((int) $period);
        $endDate = Carbon::now();

        return response()->json([
            'revenue'       => $this->buildRevenueChartData($partner, $startDate, $endDate),
            'transactions'  => $this->buildTransactionCountChartData($partner, $startDate, $endDate),
            'stats'         => $this->buildStats($partner, $startDate, $endDate),
        ]);
    }

    // ========================================================================
    // MÉTHODES PRIVÉES — Construction des données
    // ========================================================================

    private function buildStats(Partner $partner, Carbon $startDate, Carbon $endDate): array
    {
        $query = $this->getPartnerTransactions($partner);

        $totalRevenue = (clone $query)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $totalTransactions = (clone $query)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $completedTransactions = (clone $query)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $pendingTransactions = (clone $query)
            ->where('status', 'pending')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $totalEnergyWh = (clone $query)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('energy_consumed_wh');

        $chargingPoints = ChargingPoint::where(function($q) use ($partner) {
            $this->applyPartnerChargingPointFilter($q, $partner);
        });

        $totalChargingPoints    = (clone $chargingPoints)->count();
        $activeChargingPoints   = (clone $chargingPoints)->where('status', 'online')->count();
        $pendingPaymentsCount   = $this->getPendingPaymentRequestsQuery($partner)->count();
        $pendingPaymentsAmount  = $this->getPendingPaymentRequestsQuery($partner)->sum('estimated_cost');

        $avgTransactionValue = $completedTransactions > 0
            ? round($totalRevenue / $completedTransactions, 2)
            : 0;

        return [
            'total_revenue'            => round($totalRevenue, 2),
            'total_transactions'       => $totalTransactions,
            'completed_transactions'   => $completedTransactions,
            'pending_transactions'     => $pendingTransactions,
            'total_energy_kwh'         => round(($totalEnergyWh ?? 0) / 1000, 2),
            'total_charging_points'    => $totalChargingPoints,
            'active_charging_points'   => $activeChargingPoints,
            'pending_payments_count'   => $pendingPaymentsCount,
            'pending_payments_amount'  => round($pendingPaymentsAmount ?? 0, 2),
            'avg_transaction_value'    => $avgTransactionValue,
            'success_rate'             => $totalTransactions > 0
                ? round(($completedTransactions / $totalTransactions) * 100, 1)
                : 0,
        ];
    }

    private function buildRevenueChartData(Partner $partner, Carbon $startDate, Carbon $endDate): array
    {
        $days = $startDate->diffInDays($endDate);
        $groupBy = $days <= 30 ? 'day' : ($days <= 90 ? 'week' : 'month');

        $query = $this->getPartnerTransactions($partner)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate]);

        $format = match($groupBy) {
            'day'   => '%Y-%m-%d',
            'week'  => '%Y-%u',
            'month' => '%Y-%m',
        };

        $rawData = $query
            ->select(
                DB::raw("DATE_FORMAT(created_at, '{$format}') as period"),
                DB::raw('SUM(amount) as revenue'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->keyBy('period');

        // Générer toutes les périodes de la plage
        $labels = [];
        $revenues = [];
        $counts = [];

        $current = clone $startDate;
        while ($current <= $endDate) {
            $key = match($groupBy) {
                'day'   => $current->format('Y-m-d'),
                'week'  => $current->format('Y-W'),
                'month' => $current->format('Y-m'),
            };

            $label = match($groupBy) {
                'day'   => $current->format('d/m'),
                'week'  => 'S' . $current->format('W'),
                'month' => $current->locale('fr')->isoFormat('MMM YY'),
            };

            $labels[]   = $label;
            $revenues[] = round((float) ($rawData[$key]->revenue ?? 0), 2);
            $counts[]   = (int) ($rawData[$key]->count ?? 0);

            $current->add(1, $groupBy === 'day' ? 'day' : ($groupBy === 'week' ? 'week' : 'month'));
        }

        return [
            'labels'   => $labels,
            'revenues' => $revenues,
            'counts'   => $counts,
        ];
    }

    private function buildTransactionCountChartData(Partner $partner, Carbon $startDate, Carbon $endDate): array
    {
        $statusData = $this->getPartnerTransactions($partner)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'labels' => ['Complétée', 'En attente', 'Échouée'],
            'data'   => [
                $statusData['completed'] ?? 0,
                $statusData['pending']   ?? 0,
                $statusData['failed']    ?? 0,
            ],
            'colors' => ['#10b981', '#f59e0b', '#ef4444'],
        ];
    }

    private function buildKpis(
        Partner $partner,
        Carbon $currentStart,
        Carbon $currentEnd,
        Carbon $previousStart,
        Carbon $previousEnd
    ): array {
        $currentRevenue  = $this->getPartnerTransactions($partner)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->sum('amount');

        $previousRevenue = $this->getPartnerTransactions($partner)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('amount');

        $currentCount  = $this->getPartnerTransactions($partner)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->count();

        $previousCount = $this->getPartnerTransactions($partner)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$previousStart, $previousEnd])
            ->count();

        return [
            'revenue_change'     => $this->calculateChange($currentRevenue, $previousRevenue),
            'transaction_change' => $this->calculateChange($currentCount, $previousCount),
            'current_revenue'    => round($currentRevenue, 2),
            'previous_revenue'   => round($previousRevenue, 2),
        ];
    }

    private function getChargingPointDistribution(Partner $partner, Carbon $startDate, Carbon $endDate): array
    {
        return $this->getPartnerTransactions($partner)
            ->with('chargingPoint')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('charging_point_id', DB::raw('SUM(amount) as revenue'), DB::raw('COUNT(*) as count'))
            ->groupBy('charging_point_id')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'name'    => $item->chargingPoint?->name ?? 'Borne #' . $item->charging_point_id,
                    'revenue' => round($item->revenue, 2),
                    'count'   => $item->count,
                ];
            })
            ->toArray();
    }

    private function getPendingPaymentRequests(Partner $partner)
    {
        return $this->getPendingPaymentRequestsQuery($partner)
            ->with(['user', 'chargingPoint'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    private function getPendingPaymentRequestsQuery(Partner $partner)
    {
        return Reservation::where(function ($q) use ($partner) {
            $q->whereHas('chargingPoint', function ($cpQ) use ($partner) {
                $this->applyPartnerChargingPointFilter($cpQ, $partner);
            });
        })->where(function ($q) {
            $q->whereIn('payment_status', ['PENDING', 'FAILED'])
              ->orWhereNull('payment_status');
        })->whereIn('status', ['pending', 'pending_confirmation']);
    }

    private function getPartnerTransactions(Partner $partner)
    {
        return Transaction::whereHas('chargingPoint', function ($q) use ($partner) {
            $this->applyPartnerChargingPointFilter($q, $partner);
        });
    }

    private function applyPartnerChargingPointFilter($query, Partner $partner): void
    {
        $query->where(function ($q) use ($partner) {
            // Bornes directement liées au partenaire
            $q->whereHas('groups', function ($gq) use ($partner) {
                $gq->where('partner_id', $partner->id);
            });

            // Bornes via les users du partenaire
            $partnerUserIds = $partner->users()->pluck('id');
            if ($partnerUserIds->isNotEmpty()) {
                $q->orWhereIn('user_id', $partnerUserIds);
            }

            // Fallback : business profile du partenaire
            if ($partner->business_profile_id) {
                $q->orWhere('business_profile_id', $partner->business_profile_id);
            }
        });
    }

    private function calculateChange(float $current, float $previous): array
    {
        if ($previous == 0) {
            return ['value' => $current > 0 ? 100 : 0, 'positive' => true];
        }

        $change = (($current - $previous) / $previous) * 100;

        return [
            'value'    => round(abs($change), 1),
            'positive' => $change >= 0,
        ];
    }

    private function authorizePartnerAccess(Partner $partner): void
    {
        $user = Auth::user();

        // Admin voit tout
        if ($user->hasRole(['admin', 'super_admin', 'Admin', 'Super-Admin'])) {
            return;
        }

        // Intégrateur voit ses partenaires
        if ($user->hasRole(['integrator', 'Integrator'])) {
            $integratorId = $user->integrator_id;
            if ($partner->integrator_id !== $integratorId) {
                abort(403, 'Accès non autorisé à ce partenaire.');
            }
            return;
        }

        // Partenaire voit uniquement son propre profil
        if ($user->hasRole(['partner'])) {
            $userPartner = $user->partner ?? null;
            if (!$userPartner || $userPartner->id !== $partner->id) {
                abort(403, 'Accès non autorisé.');
            }
            return;
        }

        abort(403, 'Accès non autorisé.');
    }

    private function ensureTransactionBelongsToPartner(Partner $partner, Transaction $transaction): void
    {
        $partnerChargingPointIds = ChargingPoint::where(function ($q) use ($partner) {
            $this->applyPartnerChargingPointFilter($q, $partner);
        })->pluck('id');

        if (!$partnerChargingPointIds->contains($transaction->charging_point_id)) {
            abort(403, 'Cette transaction n\'appartient pas à ce partenaire.');
        }
    }

    private function ensureReservationBelongsToPartner(Partner $partner, Reservation $reservation): void
    {
        if (!$reservation->chargingPoint) {
            abort(404, 'Réservation sans borne associée.');
        }

        $partnerChargingPointIds = ChargingPoint::where(function ($q) use ($partner) {
            $this->applyPartnerChargingPointFilter($q, $partner);
        })->pluck('id');

        if (!$partnerChargingPointIds->contains($reservation->charging_point_id)) {
            abort(403, 'Cette réservation n\'appartient pas à ce partenaire.');
        }
    }
}
