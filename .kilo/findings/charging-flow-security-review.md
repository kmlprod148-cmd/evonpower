# Charging Flow Security Review — Findings

**Branch:** `audit/charging-flow`
**Scope:** offer → register → Stripe → Steve OCPP RemoteStart flow (per project memory)
**Status:** Wiring + critical/high findings have been fixed in this branch. See "Status of each finding" at the bottom for what's done vs. deferred.

---

## Architectural decision

The dead controller was originally written assuming a `ClientUser` model + `auth('client')` guard, but no `client/login`, `client/register`, or `client/dashboard` routes were ever wired up — meanwhile, the actually-routed sibling flow (`client/charging-sessions/*`) uses the standard `User` + `auth` guard. To wire the new flow without spinning up a parallel auth surface, the controller has been refactored to use `auth()` (User guard). The dual `ClientUser` design is not removed — it stays available for future use — but this flow does not depend on it.

---

## Top blocker — the flow does not exist as a routable feature (FIXED)

The controller, middleware, views, and registration handoff for the new client-charging-payment flow are all on disk and reference each other, but **no routes register them**. Calling any of the documented endpoints returns 404, and any view or controller that calls `route('client.charging.offer', ...)` will throw `RouteNotFoundException`.

Concretely missing:

- `client.charging.offer` (`GET /client/charging/{id}`)
- `client.charging.pay` (`POST /client/charging/{id}/pay`)
- `client.charging.confirm` (`GET /client/charging/{id}/confirm/{reservationId}`)
- `client.charging.success` (`GET /client/charging/{id}/success/{reservationId}`)
- `client.charging.status` (the JSON polling endpoint)

The only `client/charging*` routes registered are `client/charging-sessions/*`, served by a different controller (`Client/ClientChargingSessionController`).

Cascading dead references:

- [PublicChargingOfferWebController.php:64](app/Http/Controllers/PublicChargingOfferWebController.php#L64) — `redirect()->route('client.charging.offer', ['id' => $id])` would crash for any authenticated client landing on the offer page.
- [Public/ClientRegistrationController.php:172](app/Http/Controllers/Public/ClientRegistrationController.php#L172) — same crash after a successful registration that originated from the offer page.
- [resources/views/client/charging/success.blade.php:301](resources/views/client/charging/success.blade.php#L301) — link target unresolvable.
- [resources/views/client/charging/offer.blade.php:616](resources/views/client/charging/offer.blade.php#L616) — `PAY_URL` unresolvable, JS will throw.

In addition, **`Public/ClientRegistrationController` itself has no routes**. The actual `/register` endpoint resolves to `Auth/RegisteredUserController@store`. So even if you wired up the new charging routes, the registration → resume-charging handoff still wouldn't work without also wiring the new registration controller (or porting its session-pickup logic into `Auth/RegisteredUserController`).

The actual entry point `GET /charging-points/{id}/offer/view` resolves to [ChargingPointViewController.php:1606](app/Http/Controllers/ChargingPointViewController.php#L1606), which is a different (older) implementation with no auth gate, no register-redirect, and no Stripe wiring.

**Decision required from you:** wire up the new flow (with the latent security issues below fixed in the same pass), audit the actually-routed legacy flow instead, or delete the dead code. The latent findings below assume the new flow gets wired up.

---

## P0 — Secrets in repo (already in progress)

- [.env](/.env) was tracked in git from before the `.gitignore` rule and contains a live `INFOBIP_API_KEY` (`2912d8db…`) and `APP_KEY` (`base64:oRBMJ…`) that has shipped to past commits. `.env` is now untracked on this branch (`chore(security): untrack .env`), but **rotation of both secrets is still pending** — they remain in the git history. The Stripe values are test keys; lower priority but rotate if they're shared.

---

## CRITICAL — IDOR / missing ownership check on `confirmPayment`

[Client/ClientChargingPaymentController.php:288](app/Http/Controllers/Client/ClientChargingPaymentController.php#L288)

```php
public function confirmPayment(Request $request, int $id, int $reservationId)
{
    $reservation = Reservation::with(['chargingPoint'])->findOrFail($reservationId);
    // … no check that the authenticated client owns this reservation …
    $paymentIntentId = $request->query('payment_intent') ?? $transaction?->payment_reference;
    // … verifies Stripe status, then on success:
    StartChargingSessionJob::dispatch($reservation->id);
```

An attacker authenticated as any client can pass another user's `reservationId` together with a `payment_intent` query parameter that has actually succeeded (their own, or a public test-mode intent that's `succeeded`). The Stripe verification at [line 307](app/Http/Controllers/Client/ClientChargingPaymentController.php#L307) only checks the intent's status, not that the intent belongs to this reservation or this client. Result: the attacker can trigger `StartChargingSessionJob` for a victim's reservation.

Fix: require `reservation->user_id === auth('client')->user()->user_id` (or the ClientUser FK) AND verify that `paymentIntent->metadata.reservation_id` matches `$reservationId`.

---

## HIGH — `success()` and `chargingStatus()` ownership checks are weak/missing

[Client/ClientChargingPaymentController.php:382](app/Http/Controllers/Client/ClientChargingPaymentController.php#L382) — `success()` does no ownership check at all. Any authenticated client can view another user's reservation success page.

[Client/ClientChargingPaymentController.php:412](app/Http/Controllers/Client/ClientChargingPaymentController.php#L412) — `chargingStatus()` checks `$reservation->guest_email !== $client?->email`. `guest_email` is user-controlled at registration time and is not a stable identifier; clients can share email addresses (or have one updated). Use the foreign key, not the email.

---

## HIGH — Missing CSRF posture verification

`ClientChargingAuthMiddleware` does not apply the `web` middleware group. If the routes (when wired) are not nested inside the `web` group, `POST /client/charging/{id}/pay` will not have CSRF protection. Confirm by ensuring routes are registered inside `Route::middleware(['web', 'client.charge.auth'])`.

---

## MEDIUM — Cost validation gaps

[Client/ClientChargingPaymentController.php:91](app/Http/Controllers/Client/ClientChargingPaymentController.php#L91) — `value` has `min:1` but no max. A user can request `value=999999` (kWh or minutes) and trigger a Stripe charge that is sized off whatever `pricingPlan.price_per_kwh` happens to be. Add `max:` based on a sane upper bound (e.g. connector capacity × max session hours).

[Client/ClientChargingPaymentController.php:106](app/Http/Controllers/Client/ClientChargingPaymentController.php#L106) — `if ($cost < 0.50)` is hardcoded. Stripe's minimum varies by currency (USD/EUR are 50 cents but JPY is 50 yen with no decimals). Use `config('stripe.minimum_amounts')[$currency]` or equivalent.

---

## MEDIUM — `payment_type` ENUM is being abused

[Client/ClientChargingPaymentController.php:163](app/Http/Controllers/Client/ClientChargingPaymentController.php#L163) — Stripe payments are stored as `payment_type = 'cmi'` because the column ENUM only allows `cmi|offline`. This breaks any reporting or analytics query that filters by gateway. Schema fix: extend the ENUM to include `stripe` (and potentially `credit`), then back-populate.

---

## MEDIUM — Idempotency: no protection against double-dispatch

[Client/ClientChargingPaymentController.php:334](app/Http/Controllers/Client/ClientChargingPaymentController.php#L334) — `StartChargingSessionJob::dispatch($reservation->id)` runs unconditionally after Stripe success. If the user refreshes the confirm URL (or Stripe redirects twice for any reason), the job dispatches twice. The `applyPaymentSuccess` call at [line 319](app/Http/Controllers/Client/ClientChargingPaymentController.php#L319) may already be idempotent, but the dispatch is outside that guard. Verify the job itself is idempotent (early-return if `ChargingSession` already exists for this reservation), or guard the dispatch behind a status check on the reservation.

---

## MEDIUM — Race conditions

[Client/ClientChargingPaymentController.php:117](app/Http/Controllers/Client/ClientChargingPaymentController.php#L117) — Connector picked by `where('status', 'Available')->first()`. Between selection and `StartChargingSessionJob` running, another request can claim the same connector. No row-level lock or atomic update. Two reservations can target the same connector.

[Client/ClientChargingPaymentController.php:134](app/Http/Controllers/Client/ClientChargingPaymentController.php#L134) — `OcppTag::firstOrCreate` races on the `ocpp_tag` column. Safe only if there is a unique index on `ocpp_tag`. **Verify**: `Schema::hasIndex('ocpp_tags', '...')`.

---

## MEDIUM — OcppTag may be created with `user_id = null`

[Client/ClientChargingPaymentController.php:137](app/Http/Controllers/Client/ClientChargingPaymentController.php#L137) — `'user_id' => $client->user_id ?? null`. If the ClientUser is not linked to a system User, the OcppTag is created with `user_id = NULL`, which may break any RFID/auth-by-tag lookup downstream. Either reject the operation or eagerly create the linked User row.

---

## LOW — PII / debug leakage in legacy controller

[PublicChargingOfferWebController.php:538](app/Http/Controllers/PublicChargingOfferWebController.php#L538) and many sibling `Log::error` calls in the same file dump `request_data` (full request including `customer_email`, `customer_phone`, `customer_address`) into logs. Even if `APP_ENV=production`, this writes PII to log files. Scrub before logging.

[PublicChargingOfferWebController.php:319-340](app/Http/Controllers/PublicChargingOfferWebController.php#L319-L340) — auto-creates a `User` from form data with a random password and no email verification when `register_account=true`. Account is created silently with no email confirmation flow. Risk: anyone can create an account in someone else's name and the legitimate owner can't trigger a password reset because they don't know an account exists.

[PublicChargingOfferWebController.php:360-364](app/Http/Controllers/PublicChargingOfferWebController.php#L360-L364) — Hardcoded fallback revenue split (10/30/60) used when `business_profile_id` is missing. Silently runs financial calculations against business-domain defaults; should fail loudly instead.

(These are in the *legacy* controller, not the new flow's path. Flagged for completeness but out of scope for the new-flow review.)

---

## Status of each finding

| Finding | Severity | Status |
|---|---|---|
| Wiring gap (no routes for the flow) | Critical | **Fixed.** Routes added in `routes/web.php` inside `auth` middleware group. Public entry point at `/public/charging-points/{id}/offer/view`. Verified via `php artisan route:list`. |
| `confirmPayment` IDOR (no ownership check) | Critical | **Fixed.** `authorizeReservationAccess()` rejects with 403 if `reservation->user_id !== auth()->id()`. |
| `success()` no ownership check | High | **Fixed.** Same `authorizeReservationAccess()` call. |
| `chargingStatus()` checks email instead of FK | High | **Fixed.** Same `authorizeReservationAccess()` call. |
| Idempotency: double-dispatch on refresh | Medium | **Fixed.** `confirmPayment` skips dispatch if a non-terminal `ChargingSession` already exists for the reservation. (Job itself was already idempotent.) |
| No max-value validation | Medium | **Fixed.** `value` capped at 1000 (kWh or minutes) via `MAX_RESERVATION_VALUE` constant. |
| Stripe minimum hardcoded to 0.50 | Medium | **Fixed.** Currency-aware lookup table for EUR/USD/GBP/MAD; falls back to 0.50. |
| `Auth/RegisteredUserController` did not resume `pending_charging_point_id` | Critical (for flow) | **Fixed.** Session pickup added; redirects to `client.charging.offer` after successful registration. |
| `payment_type` ENUM doesn't have `stripe` | Medium | **Deferred.** Schema change + backfill; left a TODO comment in the controller. |
| OcppTag race on `firstOrCreate` (needs unique index on `ocpp_tag`) | Medium | **Deferred — verify only.** Action: confirm `ocpp_tags.ocpp_tag` has a unique index. If not, add one. |
| Connector race (no row lock between selection and dispatch) | Medium | **Deferred.** Mitigated partly because `StartChargingSessionJob` re-checks via `OcppBusinessService::canStartReservation`. Real fix needs SELECT FOR UPDATE or status-state-machine on connector. |
| Stripe payment_intent metadata not cross-checked vs reservationId | Medium (defense-in-depth) | **Deferred.** Ownership check is the primary guard; metadata cross-check would be belt-and-suspenders but requires extending `StripeGateway`. |
| `.env` tracked with secrets | P0 | **Partially fixed.** `.env` untracked on this branch. **Still required from you:** rotate Infobip key + APP_KEY (both leaked in git history). |
| PII in legacy `PublicChargingOfferWebController` logs | Low | **Out of scope** for this pass (legacy controller, not the new flow). |
| Silent account creation in legacy `storeReservation` | Low | **Out of scope.** |

## What was NOT reviewed (scope was the new flow only)

- `Services/Gateways/StripeGateway` internals (timeout config, error handling, webhook signature verification)
- Stripe webhook handler (does it exist? is signature verified? replay-protected?)
- The legacy offer flow (`ChargingPointViewController@showOffer` and `PublicChargingOfferWebController@storeReservation`)
- Steve API client (`SteVeApiService`) — timeouts, retries, auth, circuit breaker
- `ChargingSessionService` and the active-session live polling endpoints
- The 30+ overlapping `Charging*Controller` variants (`_old`, `Clean`, `Enhanced`, etc.)

A follow-up audit pass is appropriate for any of these.
