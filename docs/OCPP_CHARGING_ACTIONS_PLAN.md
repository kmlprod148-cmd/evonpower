# OCPP Charging Point Actions Implementation Plan

## Overview
This plan outlines the implementation of charging point actions (RESET, LOCK, UNLOCK CONNECTOR, START, STOP) following the existing codebase patterns and the provided API documentation.

## Current State Analysis

### Existing Components
- **DTOs Location**: `app/DTO/OCPP/`
- **Services**: `OcppOperationsService`, `SteVeHttpClientService`, `OCPPCommandService`
- **Controllers**: `OcppOperationsController`, `RemoteControlController`
- **Base Pattern**: DTOs extend `BaseDTO`, use enums for type safety, service layer with proper logging

### Existing Operations
- ✅ Change Availability (Operative/Inoperative)
- ✅ Clear Cache (partially implemented)
- ✅ Reset (partially implemented)
- ✅ Unlock Connector (partially implemented)

### Missing Operations
- ❌ Lock Connector (new)
- ❌ Remote Start (proper DTOs and full implementation)
- ❌ Remote Stop (proper DTOs and full implementation)
- ❌ Reset with proper DTOs (hard/soft)

---

## Phase 1: DTOs and Enums

### 1.1 New Enums to Create

#### `app/DTO/OCPP/ResetTypeEnum.php`
```php
<?php

namespace App\DTO\OCPP;

enum ResetTypeEnum: string
{
    case SOFT = 'Soft';
    case HARD = 'Hard';
    
    public function label(): string
    {
        return match($this) {
            self::SOFT => 'Soft Reset',
            self::HARD => 'Hard Reset',
        };
    }
    
    public function description(): string
    {
        return match($this) {
            self::SOFT => 'Graceful reset - completes current transactions',
            self::HARD => 'Immediate reset - interrupts all operations',
        };
    }
}
```

#### `app/DTO/OCPP/ConnectorLockStatusEnum.php`
```php
<?php

namespace App\DTO\OCPP;

enum ConnectorLockStatusEnum: string
{
    case UNLOCKED = 'Unlocked';
    case LOCKED = 'Locked';
    
    public function label(): string
    {
        return match($this) {
            self::UNLOCKED => 'Débloqué',
            self::LOCKED => 'Bloqué',
        };
    }
}
```

#### `app/DTO/OCPP/RemoteStartStatusEnum.php`
```php
<?php

namespace App\DTO\OCPP;

enum RemoteStartStatusEnum: string
{
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';
    
    public function isAccepted(): bool
    {
        return $this === self::ACCEPTED;
    }
    
    public function label(): string
    {
        return match($this) {
            self::ACCEPTED => 'Accepté',
            self::REJECTED => 'Rejeté',
        };
    }
}
```

#### `app/DTO/OCPP/RemoteStopStatusEnum.php`
```php
<?php

namespace App\DTO\OCPP;

enum RemoteStopStatusEnum: string
{
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';
    
    public function isAccepted(): bool
    {
        return $this === self::ACCEPTED;
    }
    
    public function label(): string
    {
        return match($this) {
            self::ACCEPTED => 'Accepté',
            self::REJECTED => 'Rejeté',
        };
    }
}
```

### 1.2 New DTOs to Create

#### `app/DTO/OCPP/ResetRequestDTO.php`
```php
<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use Illuminate\Http\Request;

class ResetRequestDTO extends BaseDTO
{
    public string $chargeBoxId;
    public ResetTypeEnum $type;

    public function __construct(array $data = [])
    {
        $this->chargeBoxId = $data['chargeBoxId'] ?? '';
        
        if (isset($data['type'])) {
            if ($data['type'] instanceof ResetTypeEnum) {
                $this->type = $data['type'];
            } elseif (is_string($data['type'])) {
                $this->type = ResetTypeEnum::from($data['type']);
            } else {
                $this->type = ResetTypeEnum::SOFT;
            }
        } else {
            $this->type = ResetTypeEnum::SOFT;
        }
    }

    public static function fromRequest(Request $request): self
    {
        return new self([
            'chargeBoxId' => $request->input('chargeBoxId'),
            'type' => $request->input('type', 'Soft'),
        ]);
    }

    public function toApiPayload(): array
    {
        return [
            'chargeBoxId' => $this->chargeBoxId,
            'type' => $this->type->value,
        ];
    }

    public function validate(): array
    {
        $errors = [];
        if (empty($this->chargeBoxId)) {
            $errors['chargeBoxId'] = 'L\'identifiant de la borne est requis';
        }
        return $errors;
    }

    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
```

#### `app/DTO/OCPP/ResetResponseDTO.php`
```php
<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use Carbon\Carbon;

class ResetResponseDTO extends BaseDTO
{
    public bool $success;
    public string $message;
    public ?string $chargeBoxId;
    public ?ResetTypeEnum $requestedType;
    public string $timestamp;

    public function __construct(array $data = [])
    {
        $this->success = $data['success'] ?? false;
        $this->message = $data['message'] ?? '';
        $this->chargeBoxId = $data['chargeBoxId'] ?? null;
        $this->timestamp = $data['timestamp'] ?? Carbon::now()->toISOString();

        if (isset($data['requestedType'])) {
            if ($data['requestedType'] instanceof ResetTypeEnum) {
                $this->requestedType = $data['requestedType'];
            } elseif (is_string($data['requestedType'])) {
                $this->requestedType = ResetTypeEnum::tryFrom($data['requestedType']);
            } else {
                $this->requestedType = null;
            }
        } else {
            $this->requestedType = null;
        }
    }

    public static function success(ResetTypeEnum $type, string $chargeBoxId): self
    {
        return new self([
            'success' => true,
            'message' => 'Réinitialisation effectuée avec succès',
            'chargeBoxId' => $chargeBoxId,
            'requestedType' => $type,
        ]);
    }

    public static function error(string $message, ?string $chargeBoxId = null): self
    {
        return new self([
            'success' => false,
            'message' => $message,
            'chargeBoxId' => $chargeBoxId,
        ]);
    }

    public function toApiResponse(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => [
                'chargeBoxId' => $this->chargeBoxId,
                'requestedType' => $this->requestedType?->value,
            ],
            'timestamp' => $this->timestamp,
        ];
    }
}
```

#### `app/DTO/OCPP/LockConnectorRequestDTO.php`
```php
<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use Illuminate\Http\Request;

class LockConnectorRequestDTO extends BaseDTO
{
    public string $chargeBoxId;
    public int $connectorId;

    public function __construct(array $data = [])
    {
        $this->chargeBoxId = $data['chargeBoxId'] ?? '';
        $this->connectorId = (int) ($data['connectorId'] ?? 0);
    }

    public static function fromRequest(Request $request): self
    {
        return new self([
            'chargeBoxId' => $request->input('chargeBoxId'),
            'connectorId' => (int) $request->input('connectorId', 0),
        ]);
    }

    public function toApiPayload(): array
    {
        return [
            'chargePointId' => $this->chargeBoxId,
            'connectorId' => $this->connectorId,
        ];
    }

    public function validate(): array
    {
        $errors = [];
        if (empty($this->chargeBoxId)) {
            $errors['chargeBoxId'] = 'L\'identifiant de la borne est requis';
        }
        if ($this->connectorId < 1) {
            $errors['connectorId'] = 'L\'identifiant du connecteur doit être positif';
        }
        return $errors;
    }

    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
```

#### `app/DTO/OCPP/LockConnectorResponseDTO.php`
```php
<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use Carbon\Carbon;

class LockConnectorResponseDTO extends BaseDTO
{
    public bool $success;
    public string $message;
    public ?string $chargeBoxId;
    public ?int $connectorId;
    public string $timestamp;

    public function __construct(array $data = [])
    {
        $this->success = $data['success'] ?? false;
        $this->message = $data['message'] ?? '';
        $this->chargeBoxId = $data['chargeBoxId'] ?? null;
        $this->connectorId = isset($data['connectorId']) ? (int) $data['connectorId'] : null;
        $this->timestamp = $data['timestamp'] ?? Carbon::now()->toISOString();
    }

    public static function success(string $chargeBoxId, int $connectorId): self
    {
        return new self([
            'success' => true,
            'message' => 'Connecteur verrouillé avec succès',
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => $connectorId,
        ]);
    }

    public static function error(string $message, ?string $chargeBoxId = null, ?int $connectorId = null): self
    {
        return new self([
            'success' => false,
            'message' => $message,
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => $connectorId,
        ]);
    }

    public function toApiResponse(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'data' => [
                'chargeBoxId' => $this->chargeBoxId,
                'connectorId' => $this->connectorId,
            ],
            'timestamp' => $this->timestamp,
        ];
    }
}
```

#### `app/DTO/OCPP/UnlockConnectorRequestDTO.php` (Update Existing)
Add proper validation and API payload methods.

#### `app/DTO/OCPP/UnlockConnectorResponseDTO.php` (Update Existing)
Add proper response formatting.

#### `app/DTO/OCPP/RemoteStartRequestDTO.php`
```php
<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use Illuminate\Http\Request;

class RemoteStartRequestDTO extends BaseDTO
{
    public string $chargeBoxId;
    public int $connectorId;
    public string $ocppTag;

    public function __construct(array $data = [])
    {
        $this->chargeBoxId = $data['chargeBoxId'] ?? '';
        $this->connectorId = (int) ($data['connectorId'] ?? 1);
        $this->ocppTag = $data['ocppTag'] ?? '';
    }

    public static function fromRequest(Request $request): self
    {
        return new self([
            'chargeBoxId' => $request->input('chargeBoxId'),
            'connectorId' => (int) $request->input('connectorId', 1),
            'ocppTag' => $request->input('ocppTag'),
        ]);
    }

    public function toApiPayload(): array
    {
        return [
            'chargeBoxId' => $this->chargeBoxId,
            'connectorId' => $this->connectorId,
            'ocppTag' => $this->ocppTag,
        ];
    }

    public function validate(): array
    {
        $errors = [];
        if (empty($this->chargeBoxId)) {
            $errors['chargeBoxId'] = 'L\'identifiant de la borne est requis';
        }
        if ($this->connectorId < 1) {
            $errors['connectorId'] = 'L\'identifiant du connecteur doit être positif';
        }
        if (empty($this->ocppTag)) {
            $errors['ocppTag'] = 'Le tag OCPP est requis';
        }
        return $errors;
    }

    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
```

#### `app/DTO/OCPP/RemoteStartResponseDTO.php`
```php
<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use Carbon\Carbon;

class RemoteStartResponseDTO extends BaseDTO
{
    public bool $success;
    public string $message;
    public ?RemoteStartStatusEnum $status;
    public ?array $transaction;
    public string $timestamp;

    public function __construct(array $data = [])
    {
        $this->success = $data['success'] ?? false;
        $this->message = $data['message'] ?? '';
        $this->transaction = $data['transaction'] ?? null;
        $this->timestamp = $data['timestamp'] ?? Carbon::now()->toISOString();

        if (isset($data['status'])) {
            if ($data['status'] instanceof RemoteStartStatusEnum) {
                $this->status = $data['status'];
            } elseif (is_string($data['status'])) {
                $this->status = RemoteStartStatusEnum::tryFrom($data['status']);
            } else {
                $this->status = null;
            }
        } else {
            $this->status = null;
        }
    }

    public static function accepted(array $transaction = null): self
    {
        return new self([
            'success' => true,
            'message' => 'Démarrage à distance accepté',
            'status' => RemoteStartStatusEnum::ACCEPTED,
            'transaction' => $transaction,
        ]);
    }

    public static function rejected(string $message): self
    {
        return new self([
            'success' => false,
            'message' => $message,
            'status' => RemoteStartStatusEnum::REJECTED,
            'transaction' => null,
        ]);
    }

    public function isAccepted(): bool
    {
        return $this->status === RemoteStartStatusEnum::ACCEPTED;
    }

    public function toApiResponse(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'status' => $this->status?->value,
            'transaction' => $this->transaction,
            'timestamp' => $this->timestamp,
        ];
    }
}
```

#### `app/DTO/OCPP/RemoteStopRequestDTO.php`
```php
<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use Illuminate\Http\Request;

class RemoteStopRequestDTO extends BaseDTO
{
    public string $chargeBoxId;

    public function __construct(array $data = [])
    {
        $this->chargeBoxId = $data['chargeBoxId'] ?? '';
    }

    public static function fromRequest(Request $request): self
    {
        return new self([
            'chargeBoxId' => $request->input('chargeBoxId'),
        ]);
    }

    public function toApiPayload(): array
    {
        return [
            'chargeBoxId' => $this->chargeBoxId,
        ];
    }

    public function validate(): array
    {
        $errors = [];
        if (empty($this->chargeBoxId)) {
            $errors['chargeBoxId'] = 'L\'identifiant de la borne est requis';
        }
        return $errors;
    }

    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
```

#### `app/DTO/OCPP/RemoteStopResponseDTO.php`
```php
<?php

namespace App\DTO\OCPP;

use App\Core\DTOs\BaseDTO;
use Carbon\Carbon;

class RemoteStopResponseDTO extends BaseDTO
{
    public bool $success;
    public string $message;
    public ?RemoteStopStatusEnum $status;
    public ?array $transaction;
    public string $timestamp;

    public function __construct(array $data = [])
    {
        $this->success = $data['success'] ?? false;
        $this->message = $data['message'] ?? '';
        $this->transaction = $data['transaction'] ?? null;
        $this->timestamp = $data['timestamp'] ?? Carbon::now()->toISOString();

        if (isset($data['status'])) {
            if ($data['status'] instanceof RemoteStopStatusEnum) {
                $this->status = $data['status'];
            } elseif (is_string($data['status'])) {
                $this->status = RemoteStopStatusEnum::tryFrom($data['status']);
            } else {
                $this->status = null;
            }
        } else {
            $this->status = null;
        }
    }

    public static function accepted(array $transaction = null): self
    {
        return new self([
            'success' => true,
            'message' => 'Arrêt à distance accepté',
            'status' => RemoteStopStatusEnum::ACCEPTED,
            'transaction' => $transaction,
        ]);
    }

    public static function rejected(string $message): self
    {
        return new self([
            'success' => false,
            'message' => $message,
            'status' => RemoteStopStatusEnum::REJECTED,
            'transaction' => null,
        ]);
    }

    public function isAccepted(): bool
    {
        return $this->status === RemoteStopStatusEnum::ACCEPTED;
    }

    public function toApiResponse(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'status' => $this->status?->value,
            'transaction' => $this->transaction,
            'timestamp' => $this->timestamp,
        ];
    }
}
```

---

## Phase 2: Service Layer Extensions

### 2.1 Extend `OcppOperationsService`

Add the following methods to `app/Services/OcppOperationsService.php`:

```php
// =========================================================================
// RESET OPERATIONS
// =========================================================================

/**
 * Reset la borne (Soft ou Hard)
 */
public function resetChargePoint(ResetRequestDTO $request): ResetResponseDTO
{
    // Validation
    if (!$request->isValid()) {
        return ResetResponseDTO::error(
            'Données de requête invalides: ' . implode(', ', $request->validate()),
            $request->chargeBoxId
        );
    }

    // Vérification du verrouillage
    $lockKey = $this->getOperationLockKey($request->chargeBoxId, 0);
    if (Cache::has($lockKey)) {
        return ResetResponseDTO::error(
            'Une opération est déjà en cours pour cette borne',
            $request->chargeBoxId
        );
    }

    try {
        Cache::put($lockKey, true, self::OPERATION_LOCK_TTL);

        Log::info('OcppOperationsService: Sending Reset request', [
            'chargeBoxId' => $request->chargeBoxId,
            'type' => $request->type->value,
        ]);

        $response = $this->steveClient->reset($request->chargeBoxId, $request->type->value);

        Log::info('OcppOperationsService: Reset response received', [
            'chargeBoxId' => $request->chargeBoxId,
            'success' => $response['success'] ?? false,
        ]);

        return $response['success'] 
            ? ResetResponseDTO::success($request->type, $request->chargeBoxId)
            : ResetResponseDTO::error($response['message'] ?? 'Erreur lors de la réinitialisation', $request->chargeBoxId);

    } catch (Exception $e) {
        Log::error('OcppOperationsService: Reset failed', [
            'chargeBoxId' => $request->chargeBoxId,
            'error' => $e->getMessage(),
        ]);

        return ResetResponseDTO::error(
            'Erreur lors de la communication avec la borne: ' . $e->getMessage(),
            $request->chargeBoxId
        );
    } finally {
        Cache::forget($lockKey);
    }
}

// =========================================================================
// LOCK/UNLOCK CONNECTOR OPERATIONS
// =========================================================================

/**
 * Verrouille un connecteur
 */
public function lockConnector(LockConnectorRequestDTO $request): LockConnectorResponseDTO
{
    if (!$request->isValid()) {
        return LockConnectorResponseDTO::error(
            'Données de requête invalides',
            $request->chargeBoxId,
            $request->connectorId
        );
    }

    $lockKey = $this->getOperationLockKey($request->chargeBoxId, $request->connectorId);
    if (Cache::has($lockKey)) {
        return LockConnectorResponseDTO::error(
            'Une opération est déjà en cours pour ce connecteur',
            $request->chargeBoxId,
            $request->connectorId
        );
    }

    try {
        Cache::put($lockKey, true, self::OPERATION_LOCK_TTL);

        Log::info('OcppOperationsService: Sending LockConnector request', [
            'chargeBoxId' => $request->chargeBoxId,
            'connectorId' => $request->connectorId,
        ]);

        // Note: SteVe may not have a direct LockConnector command
        // This might need to use ChangeAvailability to Inoperative as a workaround
        $response = $this->steveClient->changeAvailability(
            $request->chargeBoxId,
            $request->connectorId,
            'Inoperative'
        );

        return $response['success']
            ? LockConnectorResponseDTO::success($request->chargeBoxId, $request->connectorId)
            : LockConnectorResponseDTO::error($response['message'] ?? 'Erreur lors du verrouillage', $request->chargeBoxId, $request->connectorId);

    } catch (Exception $e) {
        Log::error('OcppOperationsService: LockConnector failed', [
            'chargeBoxId' => $request->chargeBoxId,
            'connectorId' => $request->connectorId,
            'error' => $e->getMessage(),
        ]);

        return LockConnectorResponseDTO::error(
            'Erreur lors de la communication avec la borne: ' . $e->getMessage(),
            $request->chargeBoxId,
            $request->connectorId
        );
    } finally {
        Cache::forget($lockKey);
    }
}

/**
 * Déverrouille un connecteur (mise à jour avec DTO)
 */
public function unlockConnector(UnlockConnectorRequestDTO $request): UnlockConnectorResponseDTO
{
    if (!$request->isValid()) {
        return UnlockConnectorResponseDTO::error(
            'Données de requête invalides',
            $request->chargeBoxId,
            $request->connectorId
        );
    }

    $lockKey = $this->getOperationLockKey($request->chargeBoxId, $request->connectorId);
    if (Cache::has($lockKey)) {
        return UnlockConnectorResponseDTO::error(
            'Une opération est déjà en cours pour ce connecteur',
            $request->chargeBoxId,
            $request->connectorId
        );
    }

    try {
        Cache::put($lockKey, true, self::OPERATION_LOCK_TTL);

        Log::info('OcppOperationsService: Sending UnlockConnector request', [
            'chargeBoxId' => $request->chargeBoxId,
            'connectorId' => $request->connectorId,
        ]);

        $response = $this->steveClient->unlockConnector($request->chargeBoxId, $request->connectorId);

        Log::info('OcppOperationsService: UnlockConnector response received', [
            'chargeBoxId' => $request->chargeBoxId,
            'connectorId' => $request->connectorId,
            'success' => $response['success'] ?? false,
        ]);

        return $response['success']
            ? UnlockConnectorResponseDTO::success($request->chargeBoxId, $request->connectorId)
            : UnlockConnectorResponseDTO::error($response['message'] ?? 'Erreur lors du déverrouillage', $request->chargeBoxId, $request->connectorId);

    } catch (Exception $e) {
        Log::error('OcppOperationsService: UnlockConnector failed', [
            'chargeBoxId' => $request->chargeBoxId,
            'connectorId' => $request->connectorId,
            'error' => $e->getMessage(),
        ]);

        return UnlockConnectorResponseDTO::error(
            'Erreur lors de la communication avec la borne: ' . $e->getMessage(),
            $request->chargeBoxId,
            $request->connectorId
        );
    } finally {
        Cache::forget($lockKey);
    }
}

// =========================================================================
// REMOTE START/STOP OPERATIONS
// =========================================================================

/**
 * Démarre une session de charge à distance
 */
public function remoteStart(RemoteStartRequestDTO $request): RemoteStartResponseDTO
{
    if (!$request->isValid()) {
        return RemoteStartResponseDTO::rejected(
            'Données de requête invalides: ' . implode(', ', $request->validate())
        );
    }

    $lockKey = $this->getOperationLockKey($request->chargeBoxId, $request->connectorId);
    if (Cache::has($lockKey)) {
        return RemoteStartResponseDTO::rejected(
            'Une opération est déjà en cours pour ce connecteur'
        );
    }

    try {
        Cache::put($lockKey, true, self::OPERATION_LOCK_TTL);

        Log::info('OcppOperationsService: Sending RemoteStart request', [
            'chargeBoxId' => $request->chargeBoxId,
            'connectorId' => $request->connectorId,
            'ocppTag' => $request->ocppTag,
        ]);

        $response = $this->steveClient->remoteStartTransaction($request->chargeBoxId, [
            'connector_id' => $request->connectorId,
            'id_tag' => $request->ocppTag,
        ]);

        Log::info('OcppOperationsService: RemoteStart response received', [
            'chargeBoxId' => $request->chargeBoxId,
            'success' => $response['success'] ?? false,
        ]);

        if ($response['success']) {
            return RemoteStartResponseDTO::accepted($response['data']['transaction'] ?? null);
        }

        return RemoteStartResponseDTO::rejected($response['message'] ?? 'La demande a été rejetée');

    } catch (Exception $e) {
        Log::error('OcppOperationsService: RemoteStart failed', [
            'chargeBoxId' => $request->chargeBoxId,
            'connectorId' => $request->connectorId,
            'error' => $e->getMessage(),
        ]);

        return RemoteStartResponseDTO::rejected(
            'Erreur lors de la communication avec la borne: ' . $e->getMessage()
        );
    } finally {
        Cache::forget($lockKey);
    }
}

/**
 * Arrête une session de charge à distance
 */
public function remoteStop(RemoteStopRequestDTO $request): RemoteStopResponseDTO
{
    if (!$request->isValid()) {
        return RemoteStopResponseDTO::rejected(
            'Données de requête invalides: ' . implode(', ', $request->validate())
        );
    }

    $lockKey = $this->getOperationLockKey($request->chargeBoxId, 0);
    if (Cache::has($lockKey)) {
        return RemoteStopResponseDTO::rejected(
            'Une opération est déjà en cours pour cette borne'
        );
    }

    try {
        Cache::put($lockKey, true, self::OPERATION_LOCK_TTL);

        Log::info('OcppOperationsService: Sending RemoteStop request', [
            'chargeBoxId' => $request->chargeBoxId,
        ]);

        $response = $this->steveClient->remoteStopTransaction($request->chargeBoxId);

        Log::info('OcppOperationsService: RemoteStop response received', [
            'chargeBoxId' => $request->chargeBoxId,
            'success' => $response['success'] ?? false,
        ]);

        if ($response['success']) {
            return RemoteStopResponseDTO::accepted($response['data']['transaction'] ?? null);
        }

        return RemoteStopResponseDTO::rejected($response['message'] ?? 'La demande a été rejetée');

    } catch (Exception $e) {
        Log::error('OcppOperationsService: RemoteStop failed', [
            'chargeBoxId' => $request->chargeBoxId,
            'error' => $e->getMessage(),
        ]);

        return RemoteStopResponseDTO::rejected(
            'Erreur lors de la communication avec la borne: ' . $e->getMessage()
        );
    } finally {
        Cache::forget($lockKey);
    }
}
```

---

## Phase 3: Controller Endpoints

### 3.1 Extend `OcppOperationsController`

Add the following methods to `app/Http/Controllers/OcppOperationsController.php`:

```php
// =========================================================================
// RESET ENDPOINTS
// =========================================================================

/**
 * Réinitialise la borne
 * 
 * POST /api/ocpp/charging-points/{chargingPoint}/reset
 * Body: { "type": "Soft" | "Hard" }
 */
public function resetChargePoint(Request $request, int $chargingPointId): JsonResponse
{
    try {
        $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
        $this->authorize('update', $chargingPoint);

        $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

        if (!$chargeBoxId) {
            return response()->json([
                'success' => false,
                'error' => 'Point de charge non configuré pour SteVe',
            ], 400);
        }

        $dto = ResetRequestDTO::fromRequest($request);
        $response = $this->ocppService->resetChargePoint($dto);

        return response()->json($response->toApiResponse(), $response->success ? 200 : 400);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'error' => 'Point de charge non trouvé',
        ], 404);
    } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
        return response()->json([
            'success' => false,
            'error' => 'Non autorisé',
        ], 403);
    } catch (\Exception $e) {
        Log::error('OcppOperationsController: resetChargePoint failed', [
            'chargingPointId' => $chargingPointId,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Erreur lors de la réinitialisation',
        ], 500);
    }
}

// =========================================================================
// LOCK/UNLOCK ENDPOINTS
// =========================================================================

/**
 * Verrouille un connecteur
 * 
 * POST /api/ocpp/charging-points/{chargingPoint}/connectors/{connectorId}/lock
 */
public function lockConnector(
    Request $request,
    int $chargingPointId,
    int $connectorId
): JsonResponse {
    try {
        $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
        $this->authorize('update', $chargingPoint);

        $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

        if (!$chargeBoxId) {
            return response()->json([
                'success' => false,
                'error' => 'Point de charge non configuré pour SteVe',
            ], 400);
        }

        $dto = new LockConnectorRequestDTO([
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => $connectorId,
        ]);

        $response = $this->ocppService->lockConnector($dto);

        return response()->json($response->toApiResponse(), $response->success ? 200 : 400);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'error' => 'Point de charge non trouvé',
        ], 404);
    } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
        return response()->json([
            'success' => false,
            'error' => 'Non autorisé',
        ], 403);
    } catch (\Exception $e) {
        Log::error('OcppOperationsController: lockConnector failed', [
            'chargingPointId' => $chargingPointId,
            'connectorId' => $connectorId,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Erreur lors du verrouillage',
        ], 500);
    }
}

/**
 * Déverrouille un connecteur (mise à jour)
 * 
 * POST /api/ocpp/charging-points/{chargingPoint}/connectors/{connectorId}/unlock
 */
public function unlockConnector(
    Request $request,
    int $chargingPointId,
    int $connectorId
): JsonResponse {
    try {
        $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
        $this->authorize('update', $chargingPoint);

        $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

        if (!$chargeBoxId) {
            return response()->json([
                'success' => false,
                'error' => 'Point de charge non configuré pour SteVe',
            ], 400);
        }

        $dto = new UnlockConnectorRequestDTO([
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => $connectorId,
        ]);

        $response = $this->ocppService->unlockConnector($dto);

        return response()->json($response->toApiResponse(), $response->success ? 200 : 400);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'error' => 'Point de charge non trouvé',
        ], 404);
    } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
        return response()->json([
            'success' => false,
            'error' => 'Non autorisé',
        ], 403);
    } catch (\Exception $e) {
        Log::error('OcppOperationsController: unlockConnector failed', [
            'chargingPointId' => $chargingPointId,
            'connectorId' => $connectorId,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Erreur lors du déverrouillage',
        ], 500);
    }
}

// =========================================================================
// REMOTE START/STOP ENDPOINTS
// =========================================================================

/**
 * Démarre une session de charge à distance
 * 
 * POST /api/ocpp/charging-points/{chargingPoint}/remote-start
 * Body: { "connectorId": 1, "ocppTag": "TAG-001" }
 */
public function remoteStart(Request $request, int $chargingPointId): JsonResponse
{
    try {
        $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
        $this->authorize('update', $chargingPoint);

        $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

        if (!$chargeBoxId) {
            return response()->json([
                'success' => false,
                'error' => 'Point de charge non configuré pour SteVe',
            ], 400);
        }

        // Validation
        $validated = $request->validate([
            'connectorId' => 'required|integer|min:1',
            'ocppTag' => 'required|string',
        ]);

        $dto = new RemoteStartRequestDTO([
            'chargeBoxId' => $chargeBoxId,
            'connectorId' => (int) $validated['connectorId'],
            'ocppTag' => $validated['ocppTag'],
        ]);

        $response = $this->ocppService->remoteStart($dto);

        return response()->json($response->toApiResponse(), $response->success ? 200 : 400);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'error' => 'Point de charge non trouvé',
        ], 404);
    } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
        return response()->json([
            'success' => false,
            'error' => 'Non autorisé',
        ], 403);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'error' => 'Données invalides',
            'errors' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        Log::error('OcppOperationsController: remoteStart failed', [
            'chargingPointId' => $chargingPointId,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Erreur lors du démarrage à distance',
        ], 500);
    }
}

/**
 * Arrête une session de charge à distance
 * 
 * POST /api/ocpp/charging-points/{chargingPoint}/remote-stop
 */
public function remoteStop(Request $request, int $chargingPointId): JsonResponse
{
    try {
        $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
        $this->authorize('update', $chargingPoint);

        $chargeBoxId = $chargingPoint->charge_box_id ?? $chargingPoint->steve_charging_point_id;

        if (!$chargeBoxId) {
            return response()->json([
                'success' => false,
                'error' => 'Point de charge non configuré pour SteVe',
            ], 400);
        }

        $dto = RemoteStopRequestDTO::fromRequest($request);
        $response = $this->ocppService->remoteStop($dto);

        return response()->json($response->toApiResponse(), $response->success ? 200 : 400);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'error' => 'Point de charge non trouvé',
        ], 404);
    } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
        return response()->json([
            'success' => false,
            'error' => 'Non autorisé',
        ], 403);
    } catch (\Exception $e) {
        Log::error('OcppOperationsController: remoteStop failed', [
            'chargingPointId' => $chargingPointId,
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'error' => 'Erreur lors de l\'arrêt à distance',
        ], 500);
    }
}
```

---

## Phase 4: Routes

### 4.1 Add Routes to `routes/api.php`

```php
// OCPP Operations Routes
Route::prefix('ocpp')->group(function () {
    
    // Charge Point Operations
    Route::prefix('charging-points/{chargingPoint}')->group(function () {
        
        // Reset
        Route::post('/reset', [OcppOperationsController::class, 'resetChargePoint']);
        
        // Remote Start/Stop
        Route::post('/remote-start', [OcppOperationsController::class, 'remoteStart']);
        Route::post('/remote-stop', [OcppOperationsController::class, 'remoteStop']);
        
        // Connector Operations
        Route::prefix('connectors/{connectorId}')->group(function () {
            Route::post('/lock', [OcppOperationsController::class, 'lockConnector']);
            Route::post('/unlock', [OcppOperationsController::class, 'unlockConnector']);
        });
    });
});
```

---

## Error Handling and Edge Cases

### 7.1 Error Categories

| Error Category | Description | HTTP Status | User Message |
|----------------|-------------|-------------|--------------|
| Validation Error | Invalid input data | 422 | "Données invalides: [details]" |
| Not Found | Charging point/connector not found | 404 | "Point de charge non trouvé" |
| Unauthorized | User lacks permissions | 403 | "Non autorisé" |
| Conflict | Operation already in progress | 409 | "Une opération est déjà en cours" |
| Timeout | SteVe server timeout | 504 | "Le serveur SteVe ne répond pas" |
| Connection Error | Network issues | 500 | "Erreur de connexion" |
| SteVe Error | SteVe returns error | 400/500 | Raw SteVe message |

### 7.2 Service Layer Error Handling

```php
// Pattern for all service methods
public function operationName(OperationRequestDTO $request): OperationResponseDTO
{
    // 1. Validate DTO
    if (!$request->isValid()) {
        return OperationResponseDTO::error(
            'Données de requête invalides: ' . implode(', ', $request->validate()),
            $request->chargeBoxId
        );
    }

    // 2. Check for concurrent operations (lock)
    $lockKey = $this->getOperationLockKey($request->chargeBoxId, $request->connectorId);
    if (Cache::has($lockKey)) {
        return OperationResponseDTO::error(
            'Une opération est déjà en cours pour ce connecteur. Veuillez patienter.',
            $request->chargeBoxId,
            $request->connectorId
        );
    }

    // 3. Acquire lock
    try {
        Cache::put($lockKey, true, self::OPERATION_LOCK_TTL);

        // 4. Execute operation
        $response = $this->steveClient->operation($request->toApiPayload());

        // 5. Handle response
        if ($response['success']) {
            return OperationResponseDTO::success(...);
        }
        return OperationResponseDTO::error(
            $response['message'] ?? 'Opération échouée',
            $request->chargeBoxId
        );

    } catch (ConnectionException $e) {
        Log::error('SteVe connection failed', [...]);
        return OperationResponseDTO::error(
            'Le serveur SteVe ne répond pas. Veuillez réessayer.',
            $request->chargeBoxId
        );
    } catch (Exception $e) {
        Log::error('Operation failed', [...]);
        return OperationResponseDTO::error(
            'Erreur technique: ' . $e->getMessage(),
            $request->chargeBoxId
        );
    } finally {
        // 6. Always release lock
        Cache::forget($lockKey);
    }
}
```

### 7.3 Concurrent Operation Handling

#### Lock Mechanism
- **Lock Key Format**: `ocpp_operation_lock:{chargeBoxId}:{connectorId}`
- **Lock TTL**: 30 seconds (configurable via `OPERATION_LOCK_TTL`)
- **Purpose**: Prevent duplicate operations on the same connector

```php
protected function getOperationLockKey(string $chargeBoxId, int $connectorId): string
{
    return "ocpp_operation_lock:{$chargeBoxId}:{$connectorId}";
}
```

#### Retry Logic
For transient errors, implement exponential backoff:

```php
public function makeRequestWithRetry(string $endpoint, array $data, int $maxRetries = 3): array
{
    $attempt = 0;
    $delay = 1000; // milliseconds

    while ($attempt < $maxRetries) {
        try {
            $response = $this->makeRequest('POST', $endpoint, $data);
            return ['success' => true, 'data' => $response];
        } catch (Exception $e) {
            $attempt++;
            if ($attempt >= $maxRetries) {
                return ['success' => false, 'message' => $e->getMessage()];
            }
            usleep($delay * 1000 * $attempt); // Exponential backoff
        }
    }

    return ['success' => false, 'message' => 'Max retries exceeded'];
}
```

### 7.4 Operation-Specific Edge Cases

#### RESET Operation
| Edge Case | Handling |
|-----------|----------|
| Active transaction during Hard Reset | Log warning, proceed with reset (transaction will be terminated)
| Active transaction during Soft Reset | Return error "Soft reset not possible with active transactions. Use Hard reset."
| Charger offline | Return error "La borne est hors ligne. Impossible de réinitialiser."
| Multiple reset requests | Lock mechanism prevents concurrent resets |

#### LOCK/UNLOCK Connector
| Edge Case | Handling |
|-----------|----------|
| Lock during active charging session | Change availability to Inoperative (blocks new sessions)
| Unlock during active session | Allow unlock (may interrupt charging)
| Connector already locked | Return success (idempotent operation)
| Connector not found | Return error "Connecteur non trouvé"

#### Remote Start
| Edge Case | Handling |
|-----------|----------|
| Connector not available (charging/faulted) | Return error "Connecteur indisponible"
| Invalid OCPP tag | Return error "Tag OCPP invalide ou expiré"
| No transaction ID returned | Return error "Erreur: transaction non créée"
| Max active transactions reached | Return error "Nombre maximum de transactions atteint"
| Multiple start requests on same connector | Lock mechanism prevents duplicates |

#### Remote Stop
| Edge Case | Handling |
|-----------|----------|
| No active transaction on connector | Return error "Aucune transaction active sur ce connecteur"
| Transaction already stopped | Return success (idempotent)
| Stop from different user | Allow (anyone can stop if authorized)
| Billing in progress | Log warning, proceed with stop |

### 7.5 Timeout Configuration

```php
// config/steve.php
return [
    'timeout' => env('STEVE_TIMEOUT', 30),          // HTTP timeout in seconds
    'retry_attempts' => env('STEVE_RETRY_ATTEMPTS', 3),  // Number of retries
    'retry_delay' => env('STEVE_RETRY_DELAY', 1000),    // Delay between retries (ms)
    'operation_lock_ttl' => env('STEVE_LOCK_TTL', 30),   // Lock TTL in seconds
];
```

### 7.6 Audit Logging

All operations should log:

```php
Log::info('OCPP Operation executed', [
    'operation' => 'resetChargePoint',
    'chargeBoxId' => $request->chargeBoxId,
    'connectorId' => $request->connectorId ?? 0,
    'user_id' => auth()->id(),
    'type' => $request->type->value ?? null,
    'ocppTag' => $request->ocppTag ?? null,
    'success' => $response->success,
    'timestamp' => now()->toISOString(),
]);
```

### 7.7 User Feedback Messages

| Operation | Success Message | Error Message |
|-----------|-----------------|---------------|
| Reset (Soft) | "Réinitialisation douce initiée. La borne va redémarrer proprement." | "La réinitialisation a échoué. La borne est peut-être hors ligne." |
| Reset (Hard) | "Réinitialisation forcée initiée. Toutes les sessions seront terminées." | "La réinitialisation a échoué. Veuillez réessayer." |
| Lock Connector | "Connecteur verrouillé avec succès. Les nouvelles sessions sont bloquées." | "Le verrouillage a échoué. Le connecteur est peut-être en cours d'utilisation." |
| Unlock Connector | "Connecteur déverrouillé avec succès." | "Le déverrouillage a échoué. La borne est hors ligne." |
| Remote Start | "Démarrage à distance accepté. La session de charge va commencer." | "Le démarrage a été rejeté. Le connecteur est peut-être indisponible." |
| Remote Stop | "Arrêt à distance accepté. La session va être terminée." | "L'arrêt a été rejeté. Aucune transaction active sur ce connecteur." |

### 7.8 Frontend Error Handling Guidelines

```javascript
// Example frontend error handling
async function executeOCPPOperation(endpoint, data) {
    try {
        const response = await axios.post(endpoint, data);
        
        if (response.data.success) {
            showToast(response.data.message, 'success');
            // Refresh connector status
            await refreshConnectorStatus();
        } else {
            showToast(response.data.message, 'warning');
        }
    } catch (error) {
        if (error.response?.status === 409) {
            showToast('Une opération est déjà en cours. Veuillez patienter.', 'error');
        } else if (error.response?.status === 422) {
            showToast('Données invalides: ' + error.response.data.errors.join(', '), 'error');
        } else if (error.response?.status === 404) {
            showToast('Point de charge non trouvé', 'error');
        } else {
            showToast('Erreur de connexion. Veuillez réessayer.', 'error');
        }
    }
}
```

---

## Phase 5: HTTP Client Extensions (If Needed)

### 5.1 Check `SteVeHttpClientService`

The following methods may already exist:
- ✅ `reset($chargePointId, $type)`
- ✅ `unlockConnector($chargePointId, $connectorId)`
- ✅ `remoteStartTransaction($chargePointId, $params)`
- ✅ `remoteStopTransaction($chargePointId, $transactionId)`

If `lockConnector` doesn't exist, it can be added as:
```php
/**
 * Lock connector (using ChangeAvailability to Inoperative as workaround)
 */
public function lockConnector(string $chargePointId, int $connectorId): array
{
    return $this->changeAvailability($chargePointId, $connectorId, 'Inoperative');
}
```

---

## Phase 6: Summary of Files to Create/Modify

### New Files to Create:
1. `app/DTO/OCPP/ResetTypeEnum.php`
2. `app/DTO/OCPP/ConnectorLockStatusEnum.php`
3. `app/DTO/OCPP/RemoteStartStatusEnum.php`
4. `app/DTO/OCPP/RemoteStopStatusEnum.php`
5. `app/DTO/OCPP/ResetRequestDTO.php`
6. `app/DTO/OCPP/ResetResponseDTO.php`
7. `app/DTO/OCPP/LockConnectorRequestDTO.php`
8. `app/DTO/OCPP/LockConnectorResponseDTO.php`
9. `app/DTO/OCPP/RemoteStartRequestDTO.php`
10. `app/DTO/OCPP/RemoteStartResponseDTO.php`
11. `app/DTO/OCPP/RemoteStopRequestDTO.php`
12. `app/DTO/OCPP/RemoteStopResponseDTO.php`

### Files to Modify:
1. `app/DTO/OCPP/UnlockConnectorRequestDTO.php` - Add validation and API payload
2. `app/DTO/OCPP/UnlockConnectorResponseDTO.php` - Add response formatting
3. `app/Services/OcppOperationsService.php` - Add new methods
4. `app/Http/Controllers/OcppOperationsController.php` - Add new endpoints
5. `routes/api.php` - Add new routes
6. `app/Services/SteVeHttpClientService.php` - Add lockConnector method if needed

---

## Performance Considerations

### 8.1 Request Timeout Optimization

| Operation | Expected Duration | Recommended Timeout |
|-----------|-------------------|---------------------|
| Reset (Soft) | 5-30 seconds | 30 seconds |
| Reset (Hard) | 10-60 seconds | 60 seconds |
| Lock/Unlock Connector | 2-10 seconds | 15 seconds |
| Remote Start | 3-15 seconds | 20 seconds |
| Remote Stop | 3-15 seconds | 20 seconds |

### 8.2 Caching Strategy

#### Operation Lock Cache
- **Purpose**: Prevent concurrent operations on the same resource
- **TTL**: 30 seconds (prevents stale locks)
- **Backend**: Laravel Cache (Redis or File)

```php
// config/cache.php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'lock_connection' => 'lock',
    ],
],
```

#### Response Caching (Optional)
For read-heavy endpoints, consider caching responses:

```php
// Cache connector status for 10 seconds
$status = Cache::remember(
    "connector_status:{$chargeBoxId}:{$connectorId}",
    10,
    fn() => $this->getConnectorStatus($chargeBoxId, $connectorId)
);
```

### 8.3 Database Considerations

#### Indexed Fields
Ensure the following fields are indexed in the `charging_points` table:
- `charge_box_id` (unique index)
- `steve_charging_point_id` (index)
- `status` (index)

#### Connector Table Indexes
- `charging_point_id` (foreign key)
- `connector_id` (composite index with charging_point_id)
- `status` (index)

### 8.4 Connection Pooling

The `SteVeHttpClientService` uses Laravel's HTTP client with connection pooling:

```php
// Optimal settings for SteVe API calls
Http::timeout(config('steve.timeout', 30))
    ->withBasicAuth($username, $password)
    ->pool([
        // Pool related requests
    ]);
```

### 8.5 Batch Operations

For operations affecting multiple connectors, implement batch processing:

```php
public function batchReset(array $chargeBoxIds, ResetTypeEnum $type = ResetTypeEnum::SOFT): array
{
    $results = [];
    
    foreach ($chargeBoxIds as $index => $chargeBoxId) {
        // Rate limiting: 100ms between requests
        if ($index > 0) {
            usleep(100000);
        }
        
        $results[$chargeBoxId] = $this->resetChargePoint(
            new ResetRequestDTO(['chargeBoxId' => $chargeBoxId, 'type' => $type])
        );
    }
    
    return $results;
}
```

### 8.6 Memory Management

For operations returning large datasets:

```php
// Use chunking for large result sets
Connector::where('charging_point_id', $chargingPointId)
    ->chunk(100, function ($connectors) {
        foreach ($connectors as $connector) {
            // Process connector
        }
    });
```

### 8.7 Monitoring and Metrics

Track operation performance:

```php
use Illuminate\Support\Facades\Metric;

public function resetChargePoint(ResetRequestDTO $request): ResetResponseDTO
{
    $timer = Metric::timer('ocpp.reset.duration');
    
    try {
        // ... operation logic
        $timer->publish();
    } catch (Exception $e) {
        Metric::increment('ocpp.reset.errors');
        throw $e;
    }
}
```

### 8.8 Frontend Performance

#### Request Debouncing
Prevent rapid-fire requests:

```javascript
let resetTimeout = null;

function scheduleReset(chargingPointId, type) {
    clearTimeout(resetTimeout);
    resetTimeout = setTimeout(() => {
        executeOCPPOperation(`/api/ocpp/charging-points/${chargingPointId}/reset`, { type });
    }, 500);
}
```

#### Optimistic Updates
Update UI immediately, revert on error:

```javascript
async function lockConnector(chargingPointId, connectorId) {
    // Optimistic update
    setConnectorStatus(chargingPointId, connectorId, 'locking');
    
    try {
        await executeOCPPOperation(
            `/api/ocpp/charging-points/${chargingPointId}/connectors/${connectorId}/lock`
        );
        setConnectorStatus(chargingPointId, connectorId, 'locked');
    } catch (error) {
        // Revert on error
        setConnectorStatus(chargingPointId, connectorId, 'unlocked');
        showToast('Échec du verrouillage', 'error');
    }
}
```

#### Lazy Loading
Load connector details only when needed:

```javascript
// Load connector details on demand
async function loadConnectorDetails(chargingPointId, connectorId) {
    const cacheKey = `connector:${chargingPointId}:${connectorId}`;
    
    if (cache.has(cacheKey)) {
        return cache.get(cacheKey);
    }
    
    const response = await axios.get(
        `/api/ocpp/charging-points/${chargingPointId}/connectors/${connectorId}`
    );
    cache.set(cacheKey, response.data, 300); // 5 minute cache
    return response.data;
}
```

---

## API Endpoints Summary

| Operation | Method | Endpoint | Request Body |
|-----------|--------|----------|--------------|
| Reset (Soft) | POST | `/api/ocpp/charging-points/{id}/reset` | `{"type": "Soft"}` |
| Reset (Hard) | POST | `/api/ocpp/charging-points/{id}/reset` | `{"type": "Hard"}` |
| Lock Connector | POST | `/api/ocpp/charging-points/{id}/connectors/{connectorId}/lock` | `{}` |
| Unlock Connector | POST | `/api/ocpp/charging-points/{id}/connectors/{connectorId}/unlock` | `{}` |
| Remote Start | POST | `/api/ocpp/charging-points/{id}/remote-start` | `{"connectorId": 1, "ocppTag": "TAG-001"}` |
| Remote Stop | POST | `/api/ocpp/charging-points/{id}/remote-stop` | `{}` |

---

## Response Format

All endpoints return JSON responses in the following format:

```json
{
  "success": true,
  "message": "Opération réussie",
  "data": {
    "chargeBoxId": "CB-001",
    "connectorId": 1,
    "status": "ACCEPTED"
  },
  "timestamp": "2026-02-02T18:00:00.000Z"
}
```

---

## Next Steps

1. Review and approve this plan
2. Switch to Code mode to implement the changes
3. Test each endpoint individually
4. Perform integration testing with the SteVe server
5. Update documentation
