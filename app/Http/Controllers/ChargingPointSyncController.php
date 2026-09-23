<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Services\ChargingPointSteveSyncService;
use App\Services\SteveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChargingPointSyncController extends Controller
{
    protected ChargingPointSteveSyncService $syncService;
    protected SteveService $steveService;

    public function __construct(
        ChargingPointSteveSyncService $syncService,
        SteveService $steveService
    ) {
        $this->syncService = $syncService;
        $this->steveService = $steveService;
        $this->middleware('auth');
        $this->middleware('can:manage_charging_points')->except(['status']);
    }

    /**
     * Afficher le tableau de bord de synchronisation
     */
    public function dashboard()
    {
        try {
            // Statistiques locales
            $stats = [
                'total' => ChargingPoint::count(),
                'synced' => ChargingPoint::where('steve_sync_status', 'synced')->count(),
                'not_synced' => ChargingPoint::whereNull('steve_sync_status')
                    ->orWhere('steve_sync_status', 'not_synced')->count(),
                'failed' => ChargingPoint::where('steve_sync_status', 'failed')->count(),
                'out_of_sync' => ChargingPoint::where('steve_sync_status', 'out_of_sync')->count(),
            ];

            // Liste des bornes par statut de sync
            $chargingPoints = ChargingPoint::orderBy('steve_sync_status')
                ->orderBy('name')
                ->paginate(50);

            // Récupérer les bornes de Steve
            $steveChargePoints = $this->steveService->getChargePoints();
            $steveCount = is_array($steveChargePoints) ? count($steveChargePoints) : 0;

            return view('charging-points.sync-dashboard', compact('stats', 'chargingPoints', 'steveCount'));

        } catch (\Exception $e) {
            Log::error('ChargingPointSyncController: Error loading dashboard', [
                'error' => $e->getMessage()
            ]);

            return view('charging-points.sync-dashboard', [
                'stats' => [],
                'chargingPoints' => collect(),
                'steveCount' => 0,
                'error' => 'Erreur lors du chargement: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Synchroniser une borne spécifique vers Steve
     */
    public function syncOne(Request $request, ChargingPoint $chargingPoint)
    {
        try {
            Log::info('ChargingPointSyncController: Manual sync requested', [
                'charging_point_id' => $chargingPoint->id,
                'user_id' => auth()->id()
            ]);

            // Si la borne est déjà synchronisée, faire une mise à jour
            if (!empty($chargingPoint->steve_charging_point_id)) {
                $result = $this->syncService->syncUpdateToSteve($chargingPoint);
            } else {
                $result = $this->syncService->syncCreateToSteve($chargingPoint, true);
            }

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            if ($result['success']) {
                return redirect()
                    ->back()
                    ->with('success', $result['message'] ?? 'Borne synchronisée avec succès');
            } else {
                return redirect()
                    ->back()
                    ->with('error', $result['message'] ?? 'Échec de la synchronisation');
            }

        } catch (\Exception $e) {
            Log::error('ChargingPointSyncController: Error syncing one', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Synchroniser toutes les bornes non synchronisées
     */
    public function syncAll(Request $request)
    {
        try {
            $limit = $request->input('limit', 100);

            Log::info('ChargingPointSyncController: Bulk sync requested', [
                'limit' => $limit,
                'user_id' => auth()->id()
            ]);

            $result = $this->syncService->syncAllToSteve($limit);

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            if ($result['success']) {
                $stats = $result['results'];
                $message = sprintf(
                    'Synchronisation terminée: %d réussies, %d échouées sur %d total',
                    $stats['success'],
                    $stats['failed'],
                    $stats['total']
                );

                return redirect()
                    ->back()
                    ->with('success', $message);
            } else {
                return redirect()
                    ->back()
                    ->with('error', 'Échec de la synchronisation massive');
            }

        } catch (\Exception $e) {
            Log::error('ChargingPointSyncController: Error syncing all', [
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Importer les bornes depuis Steve
     */
    public function importFromSteve(Request $request)
    {
        try {
            $updateExisting = $request->input('update_existing', false);

            Log::info('ChargingPointSyncController: Import from Steve requested', [
                'update_existing' => $updateExisting,
                'user_id' => auth()->id()
            ]);

            $result = $this->syncService->importFromSteve($updateExisting);

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            if ($result['success']) {
                $stats = $result['results'];
                $message = sprintf(
                    'Import terminé: %d créées, %d mises à jour, %d ignorées, %d échecs sur %d total',
                    $stats['created'],
                    $stats['updated'],
                    $stats['skipped'],
                    $stats['failed'],
                    $stats['total']
                );

                return redirect()
                    ->back()
                    ->with('success', $message);
            } else {
                return redirect()
                    ->back()
                    ->with('error', 'Échec de l\'import: ' . ($result['error'] ?? 'Erreur inconnue'));
            }

        } catch (\Exception $e) {
            Log::error('ChargingPointSyncController: Error importing from Steve', [
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Vérifier le statut de synchronisation d'une borne
     */
    public function status(ChargingPoint $chargingPoint)
    {
        try {
            $status = $this->syncService->checkSyncStatus($chargingPoint);

            return response()->json([
                'success' => true,
                'status' => $status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Désynchroniser une borne (supprimer la liaison avec Steve sans supprimer la borne)
     */
    public function unsync(Request $request, ChargingPoint $chargingPoint)
    {
        try {
            Log::info('ChargingPointSyncController: Unsync requested', [
                'charging_point_id' => $chargingPoint->id,
                'user_id' => auth()->id()
            ]);

            $chargingPoint->update([
                'steve_charging_point_id' => null,
                'steve_charge_box_pk' => null,
                'steve_sync_status' => 'not_synced',
                'steve_synced_at' => null,
                'steve_sync_error' => null
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Borne désynchronisée avec succès'
                ]);
            }

            return redirect()
                ->back()
                ->with('success', 'Borne désynchronisée avec succès');

        } catch (\Exception $e) {
            Log::error('ChargingPointSyncController: Error unsyncing', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }

            return redirect()
                ->back()
                ->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
}
