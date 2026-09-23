<?php

namespace App\Support;

use App\Services\AppCurrencyService;
use Throwable;

final class AppCurrency
{
    public static function code(): string
    {
        try {
            $currency = app(AppCurrencyService::class)->getDefaultCurrency();
        } catch (Throwable) {
            $currency = config('app.currency', 'EUR');
        }

        $currency = strtoupper((string) $currency);

        return $currency !== '' ? $currency : 'EUR';
    }

    public static function symbol(?string $currency = null): string
    {
        $currency = strtoupper($currency ?: self::code());

        try {
            return app(AppCurrencyService::class)->getCurrencySymbol($currency);
        } catch (Throwable) {
            return match ($currency) {
                'USD' => '$',
                'EUR' => "\u{20AC}",
                default => $currency,
            };
        }
    }

    public static function zero(?string $currency = null): string
    {
        return self::format(0, $currency);
    }

    public static function format(float|int|string|null $amount, ?string $currency = null, int $decimals = 2): string
    {
        $amount = (float) ($amount ?? 0);

        try {
            return app(AppCurrencyService::class)->formatAmount($amount, $currency, $decimals);
        } catch (Throwable) {
            return number_format($amount, $decimals, ',', ' ') . ' ' . self::symbol($currency);
        }
    }
}
