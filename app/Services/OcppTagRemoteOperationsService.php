<?php

namespace App\Services;

use App\DTO\OCPP\RemoteStartRequestDTO;
use App\DTO\OCPP\RemoteStopRequestDTO;
use App\Models\ChargingPoint;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

/**
 * Service pour les opérations de démarrage/arrêt à distance via OCPP Tag ID
 *
 * Owns tag-aware orchestration: idTag default-fill, tag validation, per-CP
 * operation-history ring, active-transaction cache. The actual OCPP transport
 * (HTTP call to SteVe, retry, idempotency lock, session state machine, audit)
 * was moved to OcppOperationsService in Slice B.3 — both remote-start/stop here
 * now delegate to that single canonical layer. SteVeClient is kept for the
 * tag-CRUD methods further down which still speak directly to /ocppTags.
 */
class OcppTagRemoteOperationsService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected int $timeout;
    protected string $defaultIdTag;
    protected OcppOperationsService $ocppOps;

    public function __construct(?OcppOperationsService $ocppOps = null)
    {
        $this->baseUrl = config('steve.api_url', '');
        $this->username = config('steve.username', '');
        $this->password = config('steve.password', '');
        $this->timeout = config('steve.timeout', 30);
        $this->defaultIdTag = config('steve.default_id_tag', 'Open10Tag');
        $this->ocppOps = $ocppOps ?? app(OcppOperationsService::class);
    }

    /**
     * Construire l'URL de l'endpoint API OCPP Tags
     * Gère les différentes configurations possibles de baseUrl
     * 
     * @param string $path Chemin après /ocppTags (ex: '', '/123', '?filter=x')
     * @return string URL complète
     */
    protected function buildOcppTagsEndpoint(string $path = ''): string
    {
        $apiUrl = rtrim($this->baseUrl, '/');
        
        // Si baseUrl se termine par /api/v1, utiliser directement /ocppTags
        if (str_ends_with($apiUrl, '/api/v1')) {
            return $apiUrl . '/ocppTags' . $path;
        }
        
        // Si baseUrl contient /steve/, ajouter /api/v1
        if (str_contains($apiUrl, '/steve')) {
            return $apiUrl . '/api/v1/ocppTags' . $path;
        }
        
        // Sinon, ajouter le chemin complet /steve/api/v1
        return $apiUrl . '/steve/api/v1/ocppTags' . $path;
    }

    /**
     * Démarrer une transaction à distance avec un ID Tag OCPP
     * 
     * @param ChargingPoint $chargingPoint Le point de charge cible
     * @param string $idTag L'ID Tag OCPP à utiliser (ex: Open10Tag)
     * @param int $connectorId L'ID du connecteur (défaut: 1)
     * @param array $options Options supplémentaires
     * @return array Résultat de l'opération
     */
    public function remoteStartTransaction(
        ChargingPoint $chargingPoint,
        string $idTag = null,
        int $connectorId = 1,
        array $options = []
    ): array {
        $idTag = $idTag ?? $this->defaultIdTag;
        $startTime = microtime(true);

        try {
            $validation = $this->validateChargingPoint($chargingPoint);
            if (!$validation['valid']) {
                return $this->buildErrorResponse($validation['message'], $startTime);
            }

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            Log::info('OcppTagRemoteOperationsService: Démarrage RemoteStartTransaction', [
                'charge_box_id' => $chargeBoxId,
                'id_tag'        => $idTag,
                'connector_id'  => $connectorId,
                'options'       => $options,
            ]);

            // Slice B.3: delegate the OCPP dispatch to the canonical layer.
            // OcppOperationsService owns the cache lock, idempotency guard,
            // ChargingSession promotion, and ChargePointCommand audit row —
            // we keep only the tag-aware response-shape and the per-CP
            // operation history + active-transaction cache below.
            $dto = new RemoteStartRequestDTO([
                'chargeBoxId' => $chargeBoxId,
                'connectorId' => $connectorId,
                'ocppTag'     => $idTag,
            ]);

            $context = array_filter([
                'idempotencyKey' => $options['idempotency_key'] ?? null,
                'reservationId'  => $options['reservation_id']  ?? null,
                'autoStarted'    => $options['auto_started']    ?? null,
            ], static fn ($v) => $v !== null);

            $remoteResponse = $this->ocppOps->remoteStart($dto, $context);
            $envelope       = $remoteResponse->toApiResponse();
            $envelope['ok'] = $remoteResponse->isAccepted();

            if ($remoteResponse->isAccepted()) {
                $extras = [
                    'charge_box_id'  => $chargeBoxId,
                    'id_tag'         => $idTag,
                    'connector_id'   => $connectorId,
                    'transaction_id' => $remoteResponse->getTransactionId(),
                    'steve_status'   => $remoteResponse->status?->value ?? 'ACCEPTED',
                ];

                $this->logOperation('RemoteStartTransaction', $chargingPoint, $idTag, $envelope, true);
                $this->cacheActiveTransaction($chargingPoint, $idTag, [
                    'data' => ['transaction' => ['id' => $remoteResponse->getTransactionId()]],
                ]);

                return $this->buildSuccessResponse(
                    $remoteResponse->message ?: 'Transaction démarrée avec succès',
                    $remoteResponse->transaction,
                    $startTime,
                    $extras,
                );
            }

            $this->logOperation('RemoteStartTransaction', $chargingPoint, $idTag, $envelope, false);
            return $this->buildErrorResponse(
                'Échec du démarrage de la transaction: ' . $remoteResponse->message,
                $startTime,
                [
                    'data'       => $envelope,
                    'error'      => $remoteResponse->message,
                    'error_code' => $remoteResponse->errorCode,
                ],
            );

        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            // Surface configuration problems verbatim so operators can fix them
            // rather than chasing a vague "transaction failed" message.
            return $this->buildErrorResponse('SteVe non configuré: ' . $e->getMessage(), $startTime);
        } catch (Exception $e) {
            Log::error('OcppTagRemoteOperationsService: Exception RemoteStartTransaction', [
                'charging_point_id' => $chargingPoint->id,
                'id_tag'            => $idTag,
                'error'             => $e->getMessage(),
                'trace'             => $e->getTraceAsString(),
            ]);

            return $this->buildErrorResponse(
                'Erreur technique: ' . $e->getMessage(),
                $startTime,
            );
        }
    }

    /**
     * Arrêter une transaction à distance
     * 
     * @param ChargingPoint $chargingPoint Le point de charge cible
     * @param int|string $transactionId L'ID de la transaction à arrêter
     * @param array $options Options supplémentaires
     * @return array Résultat de l'opération
     */
    public function remoteStopTransaction(
        ChargingPoint $chargingPoint,
        $transactionId,
        array $options = []
    ): array {
        $startTime = microtime(true);

        try {
            $validation = $this->validateChargingPoint($chargingPoint);
            if (!$validation['valid']) {
                return $this->buildErrorResponse($validation['message'], $startTime);
            }

            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            Log::info('OcppTagRemoteOperationsService: Démarrage RemoteStopTransaction', [
                'charge_box_id'  => $chargeBoxId,
                'transaction_id' => $transactionId,
                'options'        => $options,
            ]);

            // Slice B.3: same delegation pattern as remoteStartTransaction.
            // The transactionId is required for audit/state-machine pairing
            // even though SteVe resolves the active txn server-side from
            // chargeBoxId. OcppOperationsService::remoteStop() handles all of
            // that, plus the per-CB cache lock and ChargingSession transition.
            $dto = new RemoteStopRequestDTO([
                'chargeBoxId'   => $chargeBoxId,
                'transactionId' => (int) $transactionId,
            ]);

            $context = array_filter([
                'idempotencyKey' => $options['idempotency_key'] ?? null,
                'reservationId'  => $options['reservation_id']  ?? null,
                'reason'         => $options['reason']          ?? null,
            ], static fn ($v) => $v !== null);

            $remoteResponse = $this->ocppOps->remoteStop($dto, $context);
            $envelope       = $remoteResponse->toApiResponse();
            $envelope['ok'] = $remoteResponse->isAccepted();

            if ($remoteResponse->isAccepted()) {
                $this->logOperation('RemoteStopTransaction', $chargingPoint, null, $envelope, true);
                $this->clearCachedTransaction($chargingPoint);

                return $this->buildSuccessResponse(
                    $remoteResponse->message ?: 'Transaction arrêtée avec succès',
                    $envelope['data'] ?? null,
                    $startTime,
                    [
                        'charge_box_id'  => $chargeBoxId,
                        'transaction_id' => $transactionId,
                        'steve_status'   => $remoteResponse->status?->value ?? 'ACCEPTED',
                    ],
                );
            }

            $this->logOperation('RemoteStopTransaction', $chargingPoint, null, $envelope, false);
            return $this->buildErrorResponse(
                'Échec de l\'arrêt de la transaction: ' . $remoteResponse->message,
                $startTime,
                [
                    'data'       => $envelope,
                    'error'      => $remoteResponse->message,
                    'error_code' => $remoteResponse->errorCode,
                ],
            );

        } catch (\App\Exceptions\SteVeConfigurationException $e) {
            return $this->buildErrorResponse('SteVe non configuré: ' . $e->getMessage(), $startTime);
        } catch (Exception $e) {
            Log::error('OcppTagRemoteOperationsService: Exception RemoteStopTransaction', [
                'charging_point_id' => $chargingPoint->id,
                'transaction_id'    => $transactionId,
                'error'             => $e->getMessage(),
                'trace'             => $e->getTraceAsString(),
            ]);

            return $this->buildErrorResponse(
                'Erreur technique: ' . $e->getMessage(),
                $startTime,
            );
        }
    }

    /**
     * Démarrer une charge avec le TAG ID par défaut (Open10Tag)
     * 
     * @param ChargingPoint $chargingPoint
     * @param int $connectorId
     * @return array
     */
    public function startWithDefaultTag(ChargingPoint $chargingPoint, int $connectorId = 1): array
    {
        return $this->remoteStartTransaction($chargingPoint, $this->defaultIdTag, $connectorId);
    }

    /**
     * Arrêter toutes les transactions actives sur un point de charge
     * 
     * @param ChargingPoint $chargingPoint
     * @return array
     */
    public function stopAllActiveTransactions(ChargingPoint $chargingPoint): array
    {
        $startTime = microtime(true);
        $results = [];

        try {
            // Récupérer les transactions actives
            $activeTransactions = $this->getActiveTransactions($chargingPoint);

            if (empty($activeTransactions)) {
                return $this->buildSuccessResponse(
                    'Aucune transaction active à arrêter',
                    [],
                    $startTime,
                    ['stopped_count' => 0]
                );
            }

            $stoppedCount = 0;
            foreach ($activeTransactions as $transaction) {
                $transactionId = $transaction['transactionPk'] ?? $transaction['transactionId'] ?? null;
                if ($transactionId) {
                    $stopResult = $this->remoteStopTransaction($chargingPoint, $transactionId);
                    $results[] = [
                        'transaction_id' => $transactionId,
                        'result' => $stopResult
                    ];
                    if ($stopResult['success']) {
                        $stoppedCount++;
                    }
                }
            }

            return $this->buildSuccessResponse(
                "{$stoppedCount} transaction(s) arrêtée(s) sur " . count($activeTransactions),
                $results,
                $startTime,
                ['stopped_count' => $stoppedCount, 'total_active' => count($activeTransactions)]
            );

        } catch (Exception $e) {
            Log::error('OcppTagRemoteOperationsService: Exception stopAllActiveTransactions', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);

            return $this->buildErrorResponse(
                'Erreur lors de l\'arrêt des transactions: ' . $e->getMessage(),
                $startTime
            );
        }
    }

    /**
     * Obtenir les transactions actives d'un point de charge depuis Steve
     * 
     * @param ChargingPoint $chargingPoint
     * @return array
     */
    public function getActiveTransactions(ChargingPoint $chargingPoint): array
    {
        try {
            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            $endpoints = [
                '/steve/api/v1/transactions?chargeBoxId=' . urlencode($chargeBoxId) . '&type=ACTIVE',
                '/api/v1/transactions?chargeBoxId=' . urlencode($chargeBoxId) . '&active=true',
            ];

            foreach ($endpoints as $endpoint) {
                try {
                    $response = Http::timeout($this->timeout)
                        ->withBasicAuth($this->username, $this->password)
                        ->withHeaders([
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json'
                        ])
                        ->get($this->baseUrl . $endpoint);

                    if ($response->successful()) {
                        $data = $response->json();
                        Log::info('OcppTagRemoteOperationsService: Transactions actives récupérées', [
                            'charge_box_id' => $chargeBoxId,
                            'count' => is_array($data) ? count($data) : 0
                        ]);
                        return is_array($data) ? $data : [];
                    }
                } catch (Exception $e) {
                    Log::debug("Endpoint {$endpoint} failed: " . $e->getMessage());
                    continue;
                }
            }

            return [];

        } catch (Exception $e) {
            Log::error('OcppTagRemoteOperationsService: Exception getActiveTransactions', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Obtenir les informations sur un tag OCPP par son idTag
     * 
     * @param string $idTag L'identifiant du tag (ex: Open10Tag)
     * @return array
     */
    public function getOcppTagInfo(string $idTag): array
    {
        try {
            // Utiliser le endpoint avec filtre par idTag selon l'API Steve
            $endpoint = $this->buildOcppTagsEndpoint('?idTag=' . urlencode($idTag));

            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->get($endpoint);

            if ($response->successful()) {
                $data = $response->json();
                
                // L'API retourne un tableau, on prend le premier élément
                if (is_array($data) && count($data) > 0) {
                    $tag = $data[0];
                    return [
                        'success' => true,
                        'data' => $tag,
                        'message' => 'Tag OCPP trouvé'
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => 'Tag OCPP non trouvé: ' . $idTag
                ];
            }

            Log::warning('OcppTagRemoteOperationsService: Échec récupération tag OCPP', [
                'id_tag' => $idTag,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Tag OCPP non trouvé: ' . $idTag,
                'status_code' => $response->status()
            ];

        } catch (Exception $e) {
            Log::error('OcppTagRemoteOperationsService: Exception getOcppTagInfo', [
                'id_tag' => $idTag,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la récupération du tag OCPP'
            ];
        }
    }

    /**
     * Obtenir les informations sur un tag OCPP par son ocppTagPk (primary key)
     * 
     * @param int $ocppTagPk La clé primaire du tag dans la base Steve
     * @return array
     */
    public function getOcppTagByPk(int $ocppTagPk): array
    {
        try {
            $endpoint = $this->buildOcppTagsEndpoint('/' . $ocppTagPk);

            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->get($endpoint);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Tag OCPP trouvé'
                ];
            }

            return [
                'success' => false,
                'message' => 'Tag OCPP non trouvé avec PK: ' . $ocppTagPk,
                'status_code' => $response->status()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la récupération du tag OCPP'
            ];
        }
    }

    /**
     * Lister tous les tags OCPP avec des filtres optionnels
     * 
     * @param array $filters Filtres possibles: ocppTagPk, idTag, parentIdTag, userId, expired, inTransaction, blocked, note, userFilter
     * @return array
     */
    public function listOcppTags(array $filters = []): array
    {
        try {
            $queryString = http_build_query($filters);
            $endpoint = $this->buildOcppTagsEndpoint($queryString ? '?' . $queryString : '');

            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->get($endpoint);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data' => $data,
                    'count' => is_array($data) ? count($data) : 0,
                    'message' => 'Tags OCPP récupérés'
                ];
            }

            return [
                'success' => false,
                'message' => 'Échec de la récupération des tags OCPP',
                'status_code' => $response->status()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la récupération des tags OCPP'
            ];
        }
    }

    /**
     * Créer un nouveau tag OCPP
     * 
     * @param array $tagData Données du tag: idTag (requis), expiryDate, maxActiveTransactionCount, note, parentIdTag
     * @return array
     */
    public function createOcppTag(array $tagData): array
    {
        try {
            if (empty($tagData['idTag'])) {
                return [
                    'success' => false,
                    'message' => 'Le champ idTag est requis'
                ];
            }

            $payload = [
                'idTag' => $tagData['idTag'],
                'expiryDate' => $tagData['expiryDate'] ?? null,
                'maxActiveTransactionCount' => $tagData['maxActiveTransactionCount'] ?? -1,
                'note' => $tagData['note'] ?? null,
                'parentIdTag' => $tagData['parentIdTag'] ?? null,
            ];

            // Supprimer les valeurs null
            $payload = array_filter($payload, fn($v) => $v !== null);

            $endpoint = $this->buildOcppTagsEndpoint();
            Log::info('OcppTagRemoteOperationsService: Création tag OCPP', [
                'payload' => $payload,
                'endpoint' => $endpoint
            ]);

            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->post($endpoint, $payload);

            if ($response->successful()) {
                Log::info('OcppTagRemoteOperationsService: Tag OCPP créé avec succès', [
                    'response' => $response->json()
                ]);
                
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Tag OCPP créé avec succès'
                ];
            }

            Log::error('OcppTagRemoteOperationsService: Échec création tag OCPP', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Échec de la création du tag OCPP',
                'status_code' => $response->status(),
                'error' => $response->json()['message'] ?? $response->body()
            ];

        } catch (Exception $e) {
            Log::error('OcppTagRemoteOperationsService: Exception createOcppTag', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la création du tag OCPP'
            ];
        }
    }

    /**
     * Mettre à jour un tag OCPP existant
     * 
     * @param int $ocppTagPk La clé primaire du tag
     * @param array $tagData Données à mettre à jour: idTag, expiryDate, maxActiveTransactionCount, note, parentIdTag
     * @return array
     */
    public function updateOcppTag(int $ocppTagPk, array $tagData): array
    {
        try {
            $payload = [
                'idTag' => $tagData['idTag'] ?? null,
                'expiryDate' => $tagData['expiryDate'] ?? null,
                'maxActiveTransactionCount' => $tagData['maxActiveTransactionCount'] ?? null,
                'note' => $tagData['note'] ?? null,
                'parentIdTag' => $tagData['parentIdTag'] ?? null,
            ];

            // Supprimer les valeurs null
            $payload = array_filter($payload, fn($v) => $v !== null);

            $endpoint = $this->buildOcppTagsEndpoint('/' . $ocppTagPk);
            Log::info('OcppTagRemoteOperationsService: Mise à jour tag OCPP', [
                'ocppTagPk' => $ocppTagPk,
                'payload' => $payload,
                'endpoint' => $endpoint
            ]);

            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->put($endpoint, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Tag OCPP mis à jour avec succès'
                ];
            }

            return [
                'success' => false,
                'message' => 'Échec de la mise à jour du tag OCPP',
                'status_code' => $response->status(),
                'error' => $response->json()['message'] ?? $response->body()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la mise à jour du tag OCPP'
            ];
        }
    }

    /**
     * Supprimer un tag OCPP
     * 
     * @param int $ocppTagPk La clé primaire du tag
     * @return array
     */
    public function deleteOcppTag(int $ocppTagPk): array
    {
        try {
            $endpoint = $this->buildOcppTagsEndpoint('/' . $ocppTagPk);
            Log::info('OcppTagRemoteOperationsService: Suppression tag OCPP', [
                'ocppTagPk' => $ocppTagPk,
                'endpoint' => $endpoint
            ]);

            $response = Http::timeout($this->timeout)
                ->withBasicAuth($this->username, $this->password)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ])
                ->delete($endpoint);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Tag OCPP supprimé avec succès'
                ];
            }

            return [
                'success' => false,
                'message' => 'Échec de la suppression du tag OCPP',
                'status_code' => $response->status(),
                'error' => $response->json()['message'] ?? $response->body()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la suppression du tag OCPP'
            ];
        }
    }

    /**
     * Obtenir les tags OCPP en transaction
     * 
     * @return array
     */
    public function getTagsInTransaction(): array
    {
        return $this->listOcppTags(['inTransaction' => 'TRUE']);
    }

    /**
     * Obtenir les tags OCPP non expirés
     * 
     * @return array
     */
    public function getActiveOcppTags(): array
    {
        return $this->listOcppTags(['expired' => 'FALSE', 'blocked' => 'FALSE']);
    }

    /**
     * Obtenir les tags OCPP bloqués
     * 
     * @return array
     */
    public function getBlockedOcppTags(): array
    {
        return $this->listOcppTags(['blocked' => 'TRUE']);
    }

    /**
     * Vérifier si un tag OCPP est valide et autorisé
     * 
     * Structure du tag selon l'API Steve:
     * - activeTransactionCount: int - Nombre de transactions actives
     * - blocked: bool - Si le tag est bloqué
     * - expiryDate: string - Date d'expiration (ISO 8601)
     * - idTag: string - L'identifiant du tag
     * - inTransaction: bool - Si le tag est en transaction
     * - maxActiveTransactionCount: int - Max transactions actives (0=bloqué, -1=illimité)
     * - note: string - Note associée
     * - ocppTagPk: int - Clé primaire
     * - parentIdTag: string - Tag parent
     * - parentOcppTagPk: int - PK du tag parent
     * - userPk: int - PK de l'utilisateur associé
     * 
     * @param string $idTag
     * @return array
     */
    public function validateOcppTag(string $idTag): array
    {
        $tagInfo = $this->getOcppTagInfo($idTag);

        if (!$tagInfo['success']) {
            return [
                'valid' => false,
                'message' => 'Tag OCPP non trouvé: ' . $idTag,
                'id_tag' => $idTag
            ];
        }

        $tag = $tagInfo['data'];

        // Vérifier si le tag est bloqué
        if (isset($tag['blocked']) && $tag['blocked'] === true) {
            return [
                'valid' => false,
                'message' => 'Tag OCPP bloqué',
                'id_tag' => $idTag,
                'tag_info' => $tag
            ];
        }

        // Vérifier la date d'expiration
        if (!empty($tag['expiryDate'])) {
            try {
                $expiryDate = Carbon::parse($tag['expiryDate']);
                if ($expiryDate->isPast()) {
                    return [
                        'valid' => false,
                        'message' => 'Tag OCPP expiré depuis le ' . $expiryDate->format('d/m/Y H:i'),
                        'expiry_date' => $expiryDate->format('Y-m-d H:i:s'),
                        'id_tag' => $idTag,
                        'tag_info' => $tag
                    ];
                }
            } catch (Exception $e) {
                Log::warning('OcppTagRemoteOperationsService: Erreur parsing date expiration', [
                    'expiry_date' => $tag['expiryDate'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Vérifier le nombre maximum de transactions actives
        $maxActive = $tag['maxActiveTransactionCount'] ?? -1;
        $activeCount = $tag['activeTransactionCount'] ?? 0;

        // maxActiveTransactionCount = 0 signifie bloqué
        if ($maxActive === 0) {
            return [
                'valid' => false,
                'message' => 'Tag OCPP bloqué (maxActiveTransactionCount = 0)',
                'id_tag' => $idTag,
                'tag_info' => $tag
            ];
        }

        // maxActiveTransactionCount > 0 signifie limité
        // maxActiveTransactionCount < 0 (ex: -1) signifie illimité
        if ($maxActive > 0 && $activeCount >= $maxActive) {
            return [
                'valid' => false,
                'message' => "Nombre maximum de transactions actives atteint ({$activeCount}/{$maxActive})",
                'active_count' => $activeCount,
                'max_active' => $maxActive,
                'id_tag' => $idTag,
                'tag_info' => $tag
            ];
        }

        return [
            'valid' => true,
            'message' => 'Tag OCPP valide et autorisé',
            'id_tag' => $idTag,
            'active_count' => $activeCount,
            'max_active' => $maxActive,
            'in_transaction' => $tag['inTransaction'] ?? false,
            'expiry_date' => $tag['expiryDate'] ?? null,
            'note' => $tag['note'] ?? null,
            'ocpp_tag_pk' => $tag['ocppTagPk'] ?? null,
            'tag_info' => $tag
        ];
    }

    /**
     * Obtenir le statut actuel d'un point de charge depuis Steve
     * 
     * @param ChargingPoint $chargingPoint
     * @return array
     */
    public function getChargingPointStatus(ChargingPoint $chargingPoint): array
    {
        try {
            $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

            $endpoints = [
                '/steve/api/v1/chargepoints/' . urlencode($chargeBoxId) . '/status',
                '/steve/api/v1/chargepoints/' . urlencode($chargeBoxId),
                '/api/v1/chargepoints/' . urlencode($chargeBoxId) . '/connectorStatus',
            ];

            foreach ($endpoints as $endpoint) {
                try {
                    $response = Http::timeout($this->timeout)
                        ->withBasicAuth($this->username, $this->password)
                        ->withHeaders([
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json'
                        ])
                        ->get($this->baseUrl . $endpoint);

                    if ($response->successful()) {
                        return [
                            'success' => true,
                            'data' => $response->json(),
                            'charge_box_id' => $chargeBoxId
                        ];
                    }
                } catch (Exception $e) {
                    continue;
                }
            }

            return [
                'success' => false,
                'message' => 'Impossible de récupérer le statut du point de charge'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Erreur lors de la récupération du statut'
            ];
        }
    }

    /**
     * Valider qu'un point de charge peut recevoir des commandes
     */
    protected function validateChargingPoint(ChargingPoint $chargingPoint): array
    {
        $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

        if (empty($chargeBoxId)) {
            return [
                'valid' => false,
                'message' => 'Le point de charge n\'a pas d\'identifiant Steve/ChargeBox configuré'
            ];
        }

        if (empty($this->baseUrl)) {
            return [
                'valid' => false,
                'message' => 'L\'URL de l\'API Steve n\'est pas configurée'
            ];
        }

        return ['valid' => true];
    }

    /**
     * Essayer plusieurs endpoints jusqu'à ce qu'un réussisse
     */
    protected function tryEndpoints(string $method, array $endpoints, array $payload): array
    {
        $lastError = null;

        foreach ($endpoints as $endpoint) {
            try {
                $url = $this->baseUrl . $endpoint;

                Log::debug('OcppTagRemoteOperationsService: Essai endpoint', [
                    'url' => $url,
                    'method' => $method
                ]);

                $request = Http::timeout($this->timeout)
                    ->withBasicAuth($this->username, $this->password)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ]);

                if ($method === 'POST') {
                    $response = $request->post($url, $payload);
                } else {
                    $response = $request->get($url);
                }

                $responseData = $response->json();
                $status = $responseData['status'] ?? null;

                // Vérifier si la réponse est un succès
                if ($response->successful()) {
                    // Pour OCPP, vérifier si le statut est "Accepted"
                    if ($status === 'Accepted' || $status === 'accepted' || $status === null) {
                        return [
                            'success' => true,
                            'data' => $responseData,
                            'status_code' => $response->status(),
                            'endpoint' => $endpoint
                        ];
                    }
                }

                // Si rejeté explicitement, retourner l'erreur
                if ($status === 'Rejected' || $status === 'rejected') {
                    return [
                        'success' => false,
                        'data' => $responseData,
                        'status_code' => $response->status(),
                        'error' => 'Commande rejetée par la borne: ' . ($responseData['message'] ?? 'Raison inconnue'),
                        'endpoint' => $endpoint
                    ];
                }

                $lastError = [
                    'endpoint' => $endpoint,
                    'status_code' => $response->status(),
                    'response' => $responseData
                ];

            } catch (Exception $e) {
                Log::debug("Endpoint {$endpoint} failed: " . $e->getMessage());
                $lastError = [
                    'endpoint' => $endpoint,
                    'error' => $e->getMessage()
                ];
                continue;
            }
        }

        return [
            'success' => false,
            'error' => 'Tous les endpoints ont échoué',
            'last_error' => $lastError
        ];
    }

    /**
     * Logger une opération
     */
    protected function logOperation(
        string $operation,
        ChargingPoint $chargingPoint,
        ?string $idTag,
        array $response,
        bool $success
    ): void {
        $logData = [
            'operation' => $operation,
            'charging_point_id' => $chargingPoint->id,
            'charge_box_id' => $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id,
            'id_tag' => $idTag,
            'success' => $success,
            'response' => $response,
            'timestamp' => now()->toISOString()
        ];

        if ($success) {
            Log::info("OcppTagRemoteOperationsService: {$operation} réussi", $logData);
        } else {
            Log::warning("OcppTagRemoteOperationsService: {$operation} échoué", $logData);
        }

        // Stocker dans l'historique
        $cacheKey = "ocpp_operations_{$chargingPoint->id}";
        $history = Cache::get($cacheKey, []);
        $history[] = $logData;

        // Garder les 50 dernières opérations
        if (count($history) > 50) {
            $history = array_slice($history, -50);
        }

        Cache::put($cacheKey, $history, 3600);
    }

    /**
     * Mettre en cache une transaction active
     */
    protected function cacheActiveTransaction(ChargingPoint $chargingPoint, string $idTag, array $response): void
    {
        $cacheKey = "active_transaction_{$chargingPoint->id}";
        $transactionId =
            $response['data']['transaction']['id'] ??
            $response['data']['transactionId'] ??
            null;
        Cache::put($cacheKey, [
            'id_tag' => $idTag,
            'transaction_id' => $transactionId,
            'started_at' => now()->toISOString()
        ], 86400); // 24 heures
    }

    /**
     * Supprimer le cache de transaction
     */
    protected function clearCachedTransaction(ChargingPoint $chargingPoint): void
    {
        $cacheKey = "active_transaction_{$chargingPoint->id}";
        Cache::forget($cacheKey);
    }

    /**
     * Obtenir la transaction mise en cache
     */
    public function getCachedTransaction(ChargingPoint $chargingPoint): ?array
    {
        $cacheKey = "active_transaction_{$chargingPoint->id}";
        return Cache::get($cacheKey);
    }

    /**
     * Obtenir l'historique des opérations d'un point de charge
     */
    public function getOperationHistory(ChargingPoint $chargingPoint): array
    {
        $cacheKey = "ocpp_operations_{$chargingPoint->id}";
        return Cache::get($cacheKey, []);
    }

    /**
     * Construire une réponse de succès
     */
    protected function buildSuccessResponse(
        string $message,
        $data,
        float $startTime,
        array $extra = []
    ): array {
        $duration = (microtime(true) - $startTime) * 1000;

        return array_merge([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'duration_ms' => round($duration, 2),
            'timestamp' => now()->toISOString()
        ], $extra);
    }

    /**
     * Construire une réponse d'erreur
     */
    protected function buildErrorResponse(
        string $message,
        float $startTime,
        array $extra = []
    ): array {
        $duration = (microtime(true) - $startTime) * 1000;

        return array_merge([
            'success' => false,
            'message' => $message,
            'duration_ms' => round($duration, 2),
            'timestamp' => now()->toISOString()
        ], $extra);
    }

    /**
     * Obtenir la configuration du service
     */
    public function getConfig(): array
    {
        return [
            'base_url' => $this->baseUrl,
            'timeout' => $this->timeout,
            'default_id_tag' => $this->defaultIdTag,
            'username' => $this->username
        ];
    }
}

