<?php

namespace App\Services\Sms;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PhoneOtpService
{
    public const CODE_TTL_SECONDS    = 300;   // OTP valid for 5 minutes
    public const RESEND_COOLDOWN_SEC = 30;    // Min seconds between resends
    public const MAX_ATTEMPTS        = 5;     // Per-code verify attempts
    public const CODE_LENGTH         = 6;

    public function __construct(private readonly InfobipSmsClient $sms) {}

    /**
     * Generate (or refresh) an OTP for the given phone+purpose and dispatch it.
     * Returns [issued: bool, retry_after: int|null, code: string|null].
     * `code` is only populated when the configured driver is `log` (dev convenience).
     */
    public function issue(string $phoneE164, string $purpose = 'registration', ?string $ip = null): array
    {
        $now = CarbonImmutable::now();

        $existing = DB::table('phone_verification_codes')
            ->where('phone', $phoneE164)
            ->where('purpose', $purpose)
            ->first();

        if ($existing && $existing->last_sent_at) {
            // Carbon 3 (Laravel 11) returns a signed float from diffInSeconds.
            // For `now->diffInSeconds(past)` that's a large NEGATIVE number,
            // which used to flip the cooldown subtraction into a giant retry-after.
            // abs() makes the math safe under both Carbon 2 and Carbon 3.
            $elapsed = (int) abs($now->diffInSeconds(CarbonImmutable::parse($existing->last_sent_at)));
            if ($elapsed < self::RESEND_COOLDOWN_SEC) {
                return [
                    'issued'      => false,
                    'retry_after' => self::RESEND_COOLDOWN_SEC - $elapsed,
                    'code'        => null,
                ];
            }
        }

        $code = str_pad((string) random_int(0, (10 ** self::CODE_LENGTH) - 1), self::CODE_LENGTH, '0', STR_PAD_LEFT);

        DB::table('phone_verification_codes')->updateOrInsert(
            ['phone' => $phoneE164, 'purpose' => $purpose],
            [
                'code_hash'    => Hash::make($code),
                'attempts'     => 0,
                'expires_at'   => $now->addSeconds(self::CODE_TTL_SECONDS),
                'last_sent_at' => $now,
                'ip_address'   => $ip,
                'updated_at'   => $now,
                'created_at'   => $existing ? ($existing->created_at ?? $now) : $now,
            ]
        );

        $sent = $this->sms->send(
            $phoneE164,
            __('Votre code de vérification EVON est :code (valable :min min). Ne le partagez avec personne.', [
                'code' => $code,
                'min'  => (int) (self::CODE_TTL_SECONDS / 60),
            ])
        );

        return [
            'issued'      => $sent,
            'retry_after' => null,
            'code'        => config('services.infobip.driver') === 'log' ? $code : null,
        ];
    }

    /**
     * Verify the supplied code against the stored hash. Consumes the row on success.
     * Returns one of: 'ok' | 'invalid' | 'expired' | 'too_many_attempts' | 'not_found'.
     */
    public function verify(string $phoneE164, string $candidate, string $purpose = 'registration'): string
    {
        $row = DB::table('phone_verification_codes')
            ->where('phone', $phoneE164)
            ->where('purpose', $purpose)
            ->first();

        if (!$row) {
            return 'not_found';
        }

        if (CarbonImmutable::parse($row->expires_at)->isPast()) {
            DB::table('phone_verification_codes')->where('id', $row->id)->delete();
            return 'expired';
        }

        if ((int) $row->attempts >= self::MAX_ATTEMPTS) {
            DB::table('phone_verification_codes')->where('id', $row->id)->delete();
            return 'too_many_attempts';
        }

        if (!Hash::check($candidate, $row->code_hash)) {
            DB::table('phone_verification_codes')
                ->where('id', $row->id)
                ->increment('attempts');
            return 'invalid';
        }

        DB::table('phone_verification_codes')->where('id', $row->id)->delete();
        return 'ok';
    }

    public function invalidate(string $phoneE164, string $purpose = 'registration'): void
    {
        DB::table('phone_verification_codes')
            ->where('phone', $phoneE164)
            ->where('purpose', $purpose)
            ->delete();
    }
}
