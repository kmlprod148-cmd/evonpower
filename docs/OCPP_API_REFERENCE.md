# OCPP / SteVe API Reference

Canonical surface under `/api/v1/ocpp/*` — owned by [`OcppOperationsController`](../app/Http/Controllers/OcppOperationsController.php) → [`OcppOperationsService`](../app/Services/OcppOperationsService.php) → [`SteVeHttpClientService`](../app/Services/SteVeHttpClientService.php).

All endpoints below require **Sanctum bearer auth** (`Authorization: Bearer <token>`) and **OCPP/Update permission** on the target charging point (gated by [`ChargingPointPolicy`](../app/Policies/ChargingPointPolicy.php)).

Request bodies are JSON; FormRequest classes live in [`app/Http/Requests/Ocpp/`](../app/Http/Requests/Ocpp).

---

## Authentication

```http
POST /api/auth/login    # if you have an auth endpoint exposed
# or issue a token via tinker:
#   $user->createToken('your-app-name')->plainTextToken
```

All write actions:

```http
Authorization: Bearer <sanctum-token>
Accept: application/json
Content-Type: application/json
```

---

## Remote Start Transaction

Start a charging session.

| Field | Method | Path |
|---|---|---|
| URL | POST | `/api/v1/ocpp/charging-points/{chargingPointId}/remote-start` |

**Request body**

| Field | Type | Required | Rule |
|---|---|---|---|
| `connectorId` | integer | yes | `>= 1` |
| `ocppTag`     | string  | yes | `max:64` |

**Example**

```bash
curl -X POST "$BASE/api/v1/ocpp/charging-points/42/remote-start" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"connectorId": 1, "ocppTag": "Open10Tag"}'
```

**Response 200** (accepted)

```json
{
  "success": true,
  "message": "Démarrage à distance accepté. La session de charge va commencer.",
  "status": "ACCEPTED",
  "statusLabel": "Accepté",
  "transaction": { "id": 1234, ... },
  "timestamp": "2026-05-13T12:00:00.000Z"
}
```

**Errors**

| Code | When |
|---|---|
| 401 | No / invalid Sanctum token |
| 403 | User has no `update` permission on this charging point |
| 404 | `chargingPointId` does not exist |
| 422 | `connectorId` missing/`< 1`, or `ocppTag` missing/too long |
| 400 | Charging point exists but has no `charge_box_id` (not linked to SteVe) |
| 503 | `STEVE_API_URL` is not configured on this server |
| 502 | SteVe returned an upstream error |

---

## Remote Stop Transaction

Stop an active charging session.

| Field | Method | Path |
|---|---|---|
| URL | POST | `/api/v1/ocpp/charging-points/{chargingPointId}/remote-stop` |

**Request body**

| Field | Type | Required | Rule |
|---|---|---|---|
| `transactionId` | integer | recommended | `>= 1` |

`transactionId` is optional at the validation layer because some SteVe deployments stop the chargeBoxId's only active session without needing it. **Multi-connector chargers MUST send it** to target the correct session.

**Example**

```bash
curl -X POST "$BASE/api/v1/ocpp/charging-points/42/remote-stop" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"transactionId": 1234}'
```

**Response 200**

```json
{
  "success": true,
  "message": "Arrêt à distance accepté. La session de charge va être terminée.",
  "status": "ACCEPTED",
  "statusLabel": "Accepté",
  "transactionId": 1234,
  "timestamp": "2026-05-13T12:01:00.000Z"
}
```

**Errors**: same code table as Remote Start.

---

## Reset Charging Point

| Field | Method | Path |
|---|---|---|
| URL | POST | `/api/v1/ocpp/charging-points/{chargingPointId}/reset` |

**Request body**

| Field | Type | Required | Rule |
|---|---|---|---|
| `type` | string | yes | `in: Soft, Hard` (case-insensitive) |

> ⚠️ `Hard` reboots the charger immediately. If a vehicle is plugged in, the session is interrupted.

**Example**

```bash
curl -X POST "$BASE/api/v1/ocpp/charging-points/42/reset" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"type": "Soft"}'
```

**Errors**

- 422 if `type` is anything other than `Soft` / `Hard` (was silently downgraded to Soft before audit Bug #2).
- Other codes match Remote Start.

---

## Unlock Connector

| Field | Method | Path |
|---|---|---|
| URL | POST | `/api/v1/ocpp/charging-points/{chargingPointId}/connectors/{connectorId}/unlock` |

No request body required.

**Example**

```bash
curl -X POST "$BASE/api/v1/ocpp/charging-points/42/connectors/1/unlock" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"
```

> **Note:** the corresponding `.../lock` endpoint was **removed** (audit Bug #12). OCPP 1.6 has no `LockConnector` command; the previous implementation sent `ChangeAvailability(Inoperative)` under a misleading name. To mark a connector inoperative use `POST .../disable` or `POST .../availability` with `{"type":"Inoperative"}`.

---

## Change Availability

| Field | Method | Path |
|---|---|---|
| Whole charge point | POST | `/api/v1/ocpp/charging-points/{chargingPointId}/availability` |
| One connector | POST | `/api/v1/ocpp/charging-points/{chargingPointId}/connectors/{connectorId}/availability` |

**Request body**

| Field | Type | Required | Rule |
|---|---|---|---|
| `type` | string | yes | `in: Operative, Inoperative` (case-insensitive) |

Shorthand routes that don't need a body:

- `POST .../enable` — sets `Operative`
- `POST .../disable` — sets `Inoperative`

---

## Get Transactions

| Field | Method | Path |
|---|---|---|
| URL | GET | `/api/steve-transactions` |

Validated by [`SteveTransactionFilterRequest`](../app/Http/Requests/Ocpp/SteveTransactionFilterRequest.php).

**Query parameters**

| Param | Type | Rule |
|---|---|---|
| `transactionPk` | integer | `>= 1` |
| `type` | enum | `ACTIVE \| COMPLETED \| EVERYTHING` |
| `periodType` | enum | `TODAY \| LAST_7 \| LAST_30 \| ALL` |
| `chargeBoxId` | string | `max:64` |
| `ocppIdTag` | string | `max:64` |
| `userId` | integer | `>= 1` |
| `from`, `to` | date | `to >= from` |
| `page` | integer | `>= 1` |
| `perPage` | integer | `1..200` |

**Example**

```bash
curl "$BASE/api/steve-transactions?type=ACTIVE&chargeBoxId=CB-001" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"
```

**Response codes**

| Code | When |
|---|---|
| 200 | SteVe returned data |
| **502** | SteVe is up but returned an error / network failed (was 200 + `success:false` before audit Bug #6) |
| 503 | `STEVE_API_URL` not configured |

Related: `/api/steve-transactions/active`, `/api/steve-transactions/statistics`, `/api/steve-transactions/charge-box/{id}/summary`, `/api/steve-transactions/tag/{tag}/summary`.

---

## Connector Status

| Field | Method | Path |
|---|---|---|
| URL | GET | `/api/v1/charging-points/{chargingPointId}/connectors/{connectorId}` |

Returns the cached connector status plus a live reachability check.

**Errors**

- 400 with `{"error": {"code": "CONNECTION_ERROR", ...}}` when SteVe is reachable but the charger is offline
- 404 if the charging point doesn't exist
- 500 was a pre-fix bug; audit Bug #5 added the missing `use App\Models\ChargingPoint;` import that caused PHP to resolve the class to `App\Http\Controllers\ChargingPoint`

---

## SteVe / OCPP Error Conventions

| Where | What it means |
|---|---|
| `401 Unauthenticated` | No token / invalid token / expired Sanctum token |
| `403` | Policy denied (not the owner / not admin) |
| `422` (with `errors`) | FormRequest validation failed; field-keyed messages |
| `400` (with `error`) | Business validation failed (e.g., CP has no `charge_box_id`) |
| `502 Bad Gateway` | SteVe is reachable but returned an error / timeout |
| `503 Service Unavailable` | SteVe is not configured (`STEVE_API_URL=` empty) — `code: steve_configuration_missing` |
| `500` | Bug — should not happen. Report it. |

---

## Environment Variables

Required for SteVe communication:

```dotenv
STEVE_API_URL=http://your-steve-host:8180/steve
# Both naming conventions are accepted; .env.example uses the *_API_* form
STEVE_API_USER=your-api-user        # alias: STEVE_USERNAME
STEVE_API_PASSWORD=your-api-pass    # alias: STEVE_PASSWORD
STEVE_TIMEOUT=30
STEVE_RETRY_ATTEMPTS=3
STEVE_DEFAULT_ID_TAG=Open10Tag
```

When `STEVE_API_URL` is empty, every OCPP write endpoint returns **503** with `code: steve_configuration_missing` (introduced by audit fix P1.4).

---

## Legacy / Deprecated Endpoints

These still exist but are **not the canonical surface**. They duplicate functionality of `/api/v1/ocpp/*` with inconsistent payload conventions (snake_case vs camelCase) and looser validation.

| Path | Status |
|---|---|
| `/api/steve/*` | legacy — `SteveApiController` |
| `/api/commands/{id}/{start,stop,unlock,reset}` | legacy — `ChargePointActionController` (snake_case payloads) |
| `/api/ocpp-tag-operations/*` | legacy — `OcppTagOperationsController` |
| `/api/api/steve/*` | **REMOVED** in this branch (audit Bug #7) |

Plan calls for converting these to thin adapters that delegate to `OcppOperationsController` and eventually deleting them.

---

## Regression Suite

Run the canonical regression tests with:

```bash
DB_CONNECTION=sqlite vendor/bin/phpunit tests/Feature/Ocpp
```

Expected: **21 tests, 34 assertions, all green**.

These tests lock in the fixes for live-audit Bugs #1, #2, #3, #4, #5, #6, #7, #9, #10, #11, #13, plus P1.4 (503 on unconfigured SteVe).
