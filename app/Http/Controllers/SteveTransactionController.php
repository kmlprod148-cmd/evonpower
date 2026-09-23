<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Services\SteveTransactionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

/**
 * Contrôleur pour gérer les transactions OCPP depuis l'API Steve
 * 
 * Permet de visualiser, filtrer et analyser les transactions de charge
 * enregistrées dans le serveur Steve
 */
class SteveTransactionController extends Controller
{
    protected SteveTransactionService $steveTransactionService;

    public function __construct(SteveTransactionService $steveTransactionService)
    {
        $this->middleware('auth');
        $this->steveTransactionService = $steveTransactionService;
    }

    /**
     * Afficher la page de gestion des transactions Steve
     */
    public function index(Request $request)
    {
        // Récupérer les filtres depuis la requête
        $filters = $request->only([
            'transactionPk', 'type', 'periodType', 'chargeBoxId',
            'ocppIdTag', 'userId', 'from', 'to'
        ]);

        // Valeurs par défaut
        if (empty($filters['type'])) {
            $filters['type'] = 'ALL';
        }
        if (empty($filters['periodType'])) {
            $filters['periodType'] = 'LAST_30';
        }

        // Récupérer les transactions
        $transactionsResult = $this->steveTransactionService->getTransactions($filters);
        $transactions = $transactionsResult['success'] ? $transactionsResult['data'] : [];

        // Récupérer les statistiques
        $statsResult = $this->steveTransactionService->getTransactionStatistics($filters);
        $stats = $statsResult['success'] ? $statsResult['statistics'] : null;

        // Récupérer les charging points pour le filtre
        $chargingPoints = ChargingPoint::select('id', 'name', 'charge_box_id', 'steve_charging_point_id')
            ->whereNotNull('charge_box_id')
            ->orWhereNotNull('steve_charging_point_id')
            ->orderBy('name')
            ->get();

        return view('steve-transactions.index', [
            'transactions' => $transactions,
            'stats' => $stats,
            'filters' => $filters,
            'chargingPoints' => $chargingPoints,
            'config' => $this->steveTransactionService->getConfig()
        ]);
    }

    /**
     * Afficher les détails d'une transaction
     */
    public function show(int $transactionPk)
    {
        $result = $this->steveTransactionService->getTransactionByPk($transactionPk);

        if (!$result['success'] || empty($result['data'])) {
            return redirect()->route('steve-transactions.index')
                ->with('error', 'Transaction non trouvée');
        }

        $transaction = $result['data'][0] ?? null;

        if (!$transaction) {
            return redirect()->route('steve-transactions.index')
                ->with('error', 'Transaction non trouvée');
        }

        return view('steve-transactions.show', [
            'transaction' => $transaction
        ]);
    }

    /**
     * API: Récupérer les transactions avec filtres
     */
    public function apiGetTransactions(\App\Http\Requests\Ocpp\SteveTransactionFilterRequest $request): JsonResponse
    {
        $result = $this->steveTransactionService->getTransactions($request->steveFilters());

        return response()->json($result, $this->statusForSteveResult($result));
    }

    /**
     * API: Récupérer les transactions actives
     */
    public function apiGetActiveTransactions(Request $request): JsonResponse
    {
        $chargeBoxId = $request->input('chargeBoxId');
        $ocppIdTag = $request->input('ocppIdTag');

        $result = $this->steveTransactionService->getActiveTransactions($chargeBoxId, $ocppIdTag);

        return response()->json($result, $this->statusForSteveResult($result));
    }

    /**
     * Map a SteVe service result envelope to an HTTP status (Bug #6).
     *
     * The service wraps every call in ['success' => bool, ...]; when the call
     * failed because SteVe is down, unreachable, or returned an upstream error,
     * callers of OUR API need an HTTP-level signal, not HTTP 200 with a
     * success:false body. 502 is the standard "bad gateway" for upstream failure.
     */
    protected function statusForSteveResult(mixed $result): int
    {
        if (is_array($result) && array_key_exists('success', $result) && $result['success'] === false) {
            return 502;
        }

        return 200;
    }

    /**
     * API: Récupérer les statistiques
     */
    public function apiGetStatistics(\App\Http\Requests\Ocpp\SteveTransactionFilterRequest $request): JsonResponse
    {
        $result = $this->steveTransactionService->getTransactionStatistics($request->steveFilters());

        return response()->json($result, $this->statusForSteveResult($result));
    }

    /**
     * API: Récupérer le résumé d'un point de charge
     */
    public function apiGetChargeBoxSummary(string $chargeBoxId): JsonResponse
    {
        $result = $this->steveTransactionService->getChargeBoxSummary($chargeBoxId);

        return response()->json($result, $this->statusForSteveResult($result));
    }

    /**
     * API: Récupérer le résumé d'un tag OCPP
     */
    public function apiGetTagSummary(string $ocppIdTag): JsonResponse
    {
        $result = $this->steveTransactionService->getTagSummary($ocppIdTag);

        return response()->json($result, $this->statusForSteveResult($result));
    }

    /**
     * Exporter les transactions en CSV
     */
    public function export(Request $request)
    {
        $filters = $request->only([
            'transactionPk', 'type', 'periodType', 'chargeBoxId',
            'ocppIdTag', 'userId', 'from', 'to'
        ]);

        $result = $this->steveTransactionService->exportTransactionsToCsv($filters);

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        $filename = $result['filename'];
        $csvData = $result['csv_data'];

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($csvData) {
            $file = fopen('php://output', 'w');
            
            // BOM UTF-8 pour Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            foreach ($csvData as $row) {
                fputcsv($file, $row, ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Synchroniser les transactions Steve vers la base locale
     */
    public function sync(Request $request)
    {
        try {
            // Vérifier les permissions (admin uniquement)
            if (!Auth::user()->hasRole(['admin', 'super_admin'])) {
                return redirect()->back()
                    ->with('error', 'Seuls les administrateurs peuvent synchroniser les transactions');
            }

            $chargeBoxId = $request->input('chargeBoxId');

            Log::info('SteveTransactionController: Démarrage synchronisation', [
                'user_id' => Auth::id(),
                'charge_box_id' => $chargeBoxId
            ]);

            $result = $this->steveTransactionService->syncTransactionsToLocal($chargeBoxId);

            if ($result['success']) {
                $message = "✅ Synchronisation terminée: {$result['synced_count']} nouvelles, {$result['updated_count']} mises à jour";
                
                if (!empty($result['errors'])) {
                    $message .= " (" . count($result['errors']) . " erreurs)";
                }

                return redirect()->back()->with('success', $message);
            }

            return redirect()->back()->with('error', '❌ ' . $result['message']);

        } catch (\Exception $e) {
            Log::error('SteveTransactionController: Exception sync', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Vider le cache des transactions
     */
    public function clearCache(): JsonResponse
    {
        try {
            // Vérifier les permissions
            if (!Auth::user()->hasRole(['admin', 'super_admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission refusée'
                ], 403);
            }

            $this->steveTransactionService->clearTransactionsCache();

            return response()->json([
                'success' => true,
                'message' => 'Cache vidé avec succès'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher les transactions d'un point de charge spécifique
     */
    public function chargingPointTransactions(ChargingPoint $chargingPoint, Request $request)
    {
        $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

        if (!$chargeBoxId) {
            return redirect()->back()
                ->with('error', 'Ce point de charge n\'a pas d\'identifiant Steve configuré');
        }

        // Récupérer le résumé
        $summaryResult = $this->steveTransactionService->getChargeBoxSummary($chargeBoxId);
        $summary = $summaryResult['success'] ? $summaryResult['summary'] : null;

        // Filtres additionnels
        $type = $request->input('type', 'ALL');
        $periodType = $request->input('periodType', 'LAST_30');

        $filters = [
            'chargeBoxId' => $chargeBoxId,
            'type' => $type,
            'periodType' => $periodType
        ];

        $transactionsResult = $this->steveTransactionService->getTransactions($filters);
        $transactions = $transactionsResult['success'] ? $transactionsResult['data'] : [];

        return view('steve-transactions.charging-point', [
            'chargingPoint' => $chargingPoint,
            'chargeBoxId' => $chargeBoxId,
            'summary' => $summary,
            'transactions' => $transactions,
            'filters' => $filters
        ]);
    }

    /**
     * Afficher les transactions d'un tag OCPP spécifique
     */
    public function tagTransactions(string $ocppIdTag, Request $request)
    {
        // Récupérer le résumé
        $summaryResult = $this->steveTransactionService->getTagSummary($ocppIdTag);
        $summary = $summaryResult['success'] ? $summaryResult['summary'] : null;

        // Filtres additionnels
        $type = $request->input('type', 'ALL');

        $filters = [
            'ocppIdTag' => $ocppIdTag,
            'type' => $type
        ];

        $transactionsResult = $this->steveTransactionService->getTransactions($filters);
        $transactions = $transactionsResult['success'] ? $transactionsResult['data'] : [];

        return view('steve-transactions.tag', [
            'ocppIdTag' => $ocppIdTag,
            'summary' => $summary,
            'transactions' => $transactions,
            'filters' => $filters
        ]);
    }
}

