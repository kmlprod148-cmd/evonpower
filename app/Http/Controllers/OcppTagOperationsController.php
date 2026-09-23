<?php

namespace App\Http\Controllers;

use App\Models\ChargingPoint;
use App\Services\OcppTagRemoteOperationsService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Contrôleur pour les opérations OCPP via Tag ID
 * 
 * Permet de démarrer et arrêter des sessions de charge à distance
 * en utilisant un ID Tag OCPP spécifique (ex: Open10Tag)
 */
class OcppTagOperationsController extends Controller
{
    protected OcppTagRemoteOperationsService $ocppService;

    public function __construct(OcppTagRemoteOperationsService $ocppService)
    {
        $this->middleware('auth');
        $this->ocppService = $ocppService;
    }

    /**
     * Afficher la page des opérations OCPP pour un point de charge
     */
    public function index(ChargingPoint $chargingPoint)
    {
        // Récupérer les informations du point de charge
        $status = $this->ocppService->getChargingPointStatus($chargingPoint);
        $activeTransactions = $this->ocppService->getActiveTransactions($chargingPoint);
        $cachedTransaction = $this->ocppService->getCachedTransaction($chargingPoint);
        $operationHistory = $this->ocppService->getOperationHistory($chargingPoint);

        // Informations sur le tag par défaut
        $defaultTag = config('steve.default_id_tag', 'Open10Tag');
        $tagInfo = $this->ocppService->getOcppTagInfo($defaultTag);

        return view('charging-points.ocpp-operations', [
            'chargingPoint' => $chargingPoint,
            'status' => $status,
            'activeTransactions' => $activeTransactions,
            'cachedTransaction' => $cachedTransaction,
            'operationHistory' => array_reverse(array_slice($operationHistory, -10)),
            'defaultTag' => $defaultTag,
            'tagInfo' => $tagInfo,
            'config' => $this->ocppService->getConfig()
        ]);
    }

    /**
     * Démarrer une session de charge à distance avec un ID Tag
     * 
     * @param Request $request
     * @param ChargingPoint $chargingPoint
     * @return JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function startTransaction(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'id_tag' => 'nullable|string|max:50',
            'connector_id' => 'nullable|integer|min:1|max:10',
        ]);

        try {
            // Vérifier les permissions
            $user = Auth::user();
            if (!$user->can('update', $chargingPoint) && !$user->hasRole(['admin', 'super_admin', 'operator'])) {
                return $this->respondWithError(
                    $request,
                    'Vous n\'êtes pas autorisé à effectuer cette action.',
                    403
                );
            }

            // Paramètres
            $idTag = $request->input('id_tag', config('steve.default_id_tag', 'Open10Tag'));
            $connectorId = $request->input('connector_id', 1);

            // Valider le tag OCPP
            $tagValidation = $this->ocppService->validateOcppTag($idTag);
            if (!$tagValidation['valid']) {
                return $this->respondWithError(
                    $request,
                    'Tag OCPP invalide: ' . $tagValidation['message'],
                    400
                );
            }

            Log::info('OcppTagOperationsController: Démarrage RemoteStartTransaction', [
                'user_id' => $user->id,
                'charging_point_id' => $chargingPoint->id,
                'id_tag' => $idTag,
                'connector_id' => $connectorId
            ]);

            // Exécuter la commande
            $result = $this->ocppService->remoteStartTransaction(
                $chargingPoint,
                $idTag,
                $connectorId
            );

            if ($result['success']) {
                return $this->respondWithSuccess(
                    $request,
                    '✅ ' . $result['message'],
                    $result
                );
            }

            return $this->respondWithError(
                $request,
                '❌ ' . $result['message'],
                500,
                $result
            );

        } catch (\Exception $e) {
            Log::error('OcppTagOperationsController: Exception startTransaction', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->respondWithError(
                $request,
                'Erreur lors du démarrage de la session: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Arrêter une session de charge à distance
     * 
     * @param Request $request
     * @param ChargingPoint $chargingPoint
     * @return JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function stopTransaction(Request $request, ChargingPoint $chargingPoint)
    {
        $request->validate([
            'transaction_id' => 'required|integer',
        ]);

        try {
            // Vérifier les permissions
            $user = Auth::user();
            if (!$user->can('update', $chargingPoint) && !$user->hasRole(['admin', 'super_admin', 'operator'])) {
                return $this->respondWithError(
                    $request,
                    'Vous n\'êtes pas autorisé à effectuer cette action.',
                    403
                );
            }

            $transactionId = $request->input('transaction_id');

            Log::info('OcppTagOperationsController: Démarrage RemoteStopTransaction', [
                'user_id' => $user->id,
                'charging_point_id' => $chargingPoint->id,
                'transaction_id' => $transactionId
            ]);

            // Exécuter la commande
            $result = $this->ocppService->remoteStopTransaction(
                $chargingPoint,
                $transactionId
            );

            if ($result['success']) {
                return $this->respondWithSuccess(
                    $request,
                    '✅ ' . $result['message'],
                    $result
                );
            }

            return $this->respondWithError(
                $request,
                '❌ ' . $result['message'],
                500,
                $result
            );

        } catch (\Exception $e) {
            Log::error('OcppTagOperationsController: Exception stopTransaction', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->respondWithError(
                $request,
                'Erreur lors de l\'arrêt de la session: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Arrêter toutes les transactions actives sur un point de charge
     * 
     * @param Request $request
     * @param ChargingPoint $chargingPoint
     * @return JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function stopAllTransactions(Request $request, ChargingPoint $chargingPoint)
    {
        try {
            // Vérifier les permissions
            $user = Auth::user();
            if (!$user->hasRole(['admin', 'super_admin'])) {
                return $this->respondWithError(
                    $request,
                    'Seuls les administrateurs peuvent arrêter toutes les transactions.',
                    403
                );
            }

            Log::info('OcppTagOperationsController: Arrêt de toutes les transactions', [
                'user_id' => $user->id,
                'charging_point_id' => $chargingPoint->id
            ]);

            $result = $this->ocppService->stopAllActiveTransactions($chargingPoint);

            if ($result['success']) {
                return $this->respondWithSuccess(
                    $request,
                    '✅ ' . $result['message'],
                    $result
                );
            }

            return $this->respondWithError(
                $request,
                '❌ ' . $result['message'],
                500,
                $result
            );

        } catch (\Exception $e) {
            Log::error('OcppTagOperationsController: Exception stopAllTransactions', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return $this->respondWithError(
                $request,
                'Erreur: ' . $e->getMessage(),
                500
            );
        }
    }

    /**
     * Obtenir les transactions actives d'un point de charge
     * 
     * @param ChargingPoint $chargingPoint
     * @return JsonResponse
     */
    public function getActiveTransactions(ChargingPoint $chargingPoint): JsonResponse
    {
        try {
            $activeTransactions = $this->ocppService->getActiveTransactions($chargingPoint);

            return response()->json([
                'success' => true,
                'data' => $activeTransactions,
                'count' => count($activeTransactions),
                'charging_point_id' => $chargingPoint->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le statut d'un point de charge
     * 
     * @param ChargingPoint $chargingPoint
     * @return JsonResponse
     */
    public function getStatus(ChargingPoint $chargingPoint): JsonResponse
    {
        try {
            $status = $this->ocppService->getChargingPointStatus($chargingPoint);
            $cachedTransaction = $this->ocppService->getCachedTransaction($chargingPoint);

            return response()->json([
                'success' => true,
                'status' => $status,
                'cached_transaction' => $cachedTransaction,
                'charging_point_id' => $chargingPoint->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les informations sur un tag OCPP
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTagInfo(Request $request): JsonResponse
    {
        $request->validate([
            'id_tag' => 'required|string|max:50'
        ]);

        try {
            $idTag = $request->input('id_tag');
            $tagInfo = $this->ocppService->getOcppTagInfo($idTag);
            $validation = $this->ocppService->validateOcppTag($idTag);

            return response()->json([
                'success' => true,
                'tag_info' => $tagInfo,
                'validation' => $validation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valider un tag OCPP
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function validateTag(Request $request): JsonResponse
    {
        $request->validate([
            'id_tag' => 'required|string|max:50'
        ]);

        try {
            $idTag = $request->input('id_tag');
            $validation = $this->ocppService->validateOcppTag($idTag);

            return response()->json([
                'success' => $validation['valid'],
                'validation' => $validation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lister tous les tags OCPP
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function listTags(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'ocppTagPk', 'idTag', 'parentIdTag', 'userId',
                'expired', 'inTransaction', 'blocked', 'note', 'userFilter'
            ]);

            $result = $this->ocppService->listOcppTags($filters);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouveau tag OCPP
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createTag(Request $request): JsonResponse
    {
        $request->validate([
            'id_tag' => 'required|string|max:50',
            'expiry_date' => 'nullable|date',
            'max_active_transaction_count' => 'nullable|integer|min:-1',
            'note' => 'nullable|string|max:255',
            'parent_id_tag' => 'nullable|string|max:50'
        ]);

        try {
            // Vérifier les permissions (admin uniquement)
            if (!Auth::user()->hasRole(['admin', 'super_admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seuls les administrateurs peuvent créer des tags OCPP'
                ], 403);
            }

            $tagData = [
                'idTag' => $request->input('id_tag'),
                'expiryDate' => $request->input('expiry_date'),
                'maxActiveTransactionCount' => $request->input('max_active_transaction_count', -1),
                'note' => $request->input('note'),
                'parentIdTag' => $request->input('parent_id_tag')
            ];

            $result = $this->ocppService->createOcppTag($tagData);

            if ($result['success']) {
                return response()->json($result, 201);
            }

            return response()->json($result, 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un tag OCPP
     * 
     * @param Request $request
     * @param int $ocppTagPk
     * @return JsonResponse
     */
    public function updateTag(Request $request, int $ocppTagPk): JsonResponse
    {
        $request->validate([
            'id_tag' => 'nullable|string|max:50',
            'expiry_date' => 'nullable|date',
            'max_active_transaction_count' => 'nullable|integer|min:-1',
            'note' => 'nullable|string|max:255',
            'parent_id_tag' => 'nullable|string|max:50'
        ]);

        try {
            // Vérifier les permissions (admin uniquement)
            if (!Auth::user()->hasRole(['admin', 'super_admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seuls les administrateurs peuvent modifier des tags OCPP'
                ], 403);
            }

            $tagData = array_filter([
                'idTag' => $request->input('id_tag'),
                'expiryDate' => $request->input('expiry_date'),
                'maxActiveTransactionCount' => $request->input('max_active_transaction_count'),
                'note' => $request->input('note'),
                'parentIdTag' => $request->input('parent_id_tag')
            ], fn($v) => $v !== null);

            $result = $this->ocppService->updateOcppTag($ocppTagPk, $tagData);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un tag OCPP
     * 
     * @param int $ocppTagPk
     * @return JsonResponse
     */
    public function deleteTag(int $ocppTagPk): JsonResponse
    {
        try {
            // Vérifier les permissions (admin uniquement)
            if (!Auth::user()->hasRole(['admin', 'super_admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Seuls les administrateurs peuvent supprimer des tags OCPP'
                ], 403);
            }

            $result = $this->ocppService->deleteOcppTag($ocppTagPk);

            return response()->json($result, $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les tags OCPP en transaction (actifs)
     * 
     * @return JsonResponse
     */
    public function getTagsInTransaction(): JsonResponse
    {
        try {
            $result = $this->ocppService->getTagsInTransaction();
            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les tags OCPP actifs (non expirés, non bloqués)
     * 
     * @return JsonResponse
     */
    public function getActiveTags(): JsonResponse
    {
        try {
            $result = $this->ocppService->getActiveOcppTags();
            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir l'historique des opérations
     * 
     * @param ChargingPoint $chargingPoint
     * @return JsonResponse
     */
    public function getOperationHistory(ChargingPoint $chargingPoint): JsonResponse
    {
        try {
            $history = $this->ocppService->getOperationHistory($chargingPoint);

            return response()->json([
                'success' => true,
                'history' => $history,
                'count' => count($history),
                'charging_point_id' => $chargingPoint->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Démarrer rapidement avec le tag par défaut (Open10Tag)
     * 
     * @param Request $request
     * @param ChargingPoint $chargingPoint
     * @return JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function quickStart(Request $request, ChargingPoint $chargingPoint)
    {
        $request->merge([
            'id_tag' => config('steve.default_id_tag', 'Open10Tag'),
            'connector_id' => $request->input('connector_id', 1)
        ]);

        return $this->startTransaction($request, $chargingPoint);
    }

    /**
     * Répondre avec succès
     */
    protected function respondWithSuccess(Request $request, string $message, array $data = [])
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Répondre avec erreur
     */
    protected function respondWithError(Request $request, string $message, int $status = 400, array $data = [])
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'data' => $data
            ], $status);
        }

        return redirect()->back()->with('error', $message);
    }
}

