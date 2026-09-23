<?php

namespace App\Services\SteVe;

use App\Models\ChargingPoint;

/**
 * Maps a local ChargingPoint model to the SteVe REST API's ChargePointForm
 * body shape (POST /chargePoints). Keeps Eloquent out of SteVeHttpClientService,
 * which deals in arrays.
 *
 * SteVe 3.9.0-SNAPSHOT spec — fields used here:
 *   chargeBoxId                            (string, required)
 *   description, note                      (string, optional)
 *   adminAddress                           (string, optional)
 *   locationLatitude, locationLongitude    (decimal, optional)
 *   address.{street,houseNumber,zipCode,city,country}
 *   registrationStatus                     (string, optional)
 *   insertConnectorStatusAfterTransactionMsg (bool, optional)
 *
 * `chargeBoxPk` is server-assigned on create — never sent on POST.
 * `addressPk` / `empty` / `countryAlpha2OrNull` are response-side only.
 */
class ChargePointFormMapper
{
    /**
     * Build the SteVe ChargePointForm payload from a local ChargingPoint.
     *
     * Strategy for the OCPP `chargeBoxId` identifier — in priority order:
     *   1. The explicit charge_box_id column (if set by an operator).
     *   2. The steve_charging_point_id (the local CP-NNNNNN-serial format).
     *   3. The serial_number (last-resort fallback).
     *
     * Caller MUST ensure the model has been persisted (an id and a derived
     * steve_charging_point_id) before invoking this — see
     * ChargingPointService::createChargingPoint().
     */
    public static function fromModel(ChargingPoint $cp): array
    {
        $chargeBoxId = $cp->charge_box_id
            ?: $cp->steve_charging_point_id
            ?: $cp->serial_number;

        $payload = [
            'chargeBoxId' => $chargeBoxId,
        ];

        // Description — SteVe shows this in the management UI. Default to the
        // local display name so a freshly-provisioned CP is identifiable.
        if (!empty($cp->name)) {
            $payload['description'] = (string) $cp->name;
        }

        if (!empty($cp->notes)) {
            $payload['note'] = (string) $cp->notes;
        }

        // Location — only attach when BOTH coordinates are present; sending
        // one without the other puts SteVe into an inconsistent state.
        if ($cp->latitude !== null && $cp->longitude !== null) {
            $payload['locationLatitude']  = round((float) $cp->latitude, 8);
            $payload['locationLongitude'] = round((float) $cp->longitude, 8);
        }

        // Address sub-object — only emit when at least one address-ish field
        // is set, so we don't ship an `{}` and trigger SteVe's "empty: true"
        // discriminator unintentionally.
        $address = array_filter([
            'street'   => $cp->address ?: null,
            'zipCode'  => $cp->postal_code ?: null,
            'city'     => $cp->city ?: null,
            'country'  => $cp->country ?: null,
        ], static fn ($v) => $v !== null && $v !== '');

        if ($address !== []) {
            // SteVe expects the literal "UNDEFINED" sentinel when no country
            // is set — but if we have any other address field we still want
            // the object to be valid, so default the country here.
            $address['country'] = $address['country'] ?? 'UNDEFINED';
            $payload['address'] = $address;
        }

        // adminAddress — SteVe types this as a java.net.URL on the server side.
        // Plain strings (hostnames, IPs, comma-joined postal addresses) trip
        // Jackson deserialization and the whole create returns 400 "Error
        // understanding the request". Only forward an explicit, http(s) URL,
        // and skip the field otherwise — most installations leave it null.
        $adminAddress = self::readAdminAddress($cp);
        if ($adminAddress !== null && self::looksLikeHttpUrl($adminAddress)) {
            $payload['adminAddress'] = $adminAddress;
        }

        return $payload;
    }

    private static function readAdminAddress(ChargingPoint $cp): ?string
    {
        // The local schema may have either of these columns depending on
        // migration vintage; honour both without forcing a hard dependency.
        $value = $cp->admin_address ?? $cp->adminAddress ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function looksLikeHttpUrl(string $value): bool
    {
        // filter_var with FILTER_VALIDATE_URL would also accept ftp/file/etc.
        // Constrain to http(s) since that's what SteVe expects in practice.
        if (!preg_match('#^https?://#i', $value)) {
            return false;
        }
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}
