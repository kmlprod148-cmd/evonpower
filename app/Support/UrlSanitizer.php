<?php

namespace App\Support;

/**
 * Strip credentials from URLs before they reach a log file or an API response.
 *
 * P6.4: a misconfigured `STEVE_API_URL=http://user:pass@host:8180/steve` would
 * otherwise leak basic-auth credentials into:
 *   - SteVeHttpClientService::getConfig() (exposed via admin diagnostics)
 *   - Guzzle exception messages routed through Log::error
 *   - any debug log line that interpolates a URL
 *
 * `strip()` removes the userinfo segment from a single URL.
 * `scrubMessage()` finds embedded URLs in a free-form string and strips them.
 */
final class UrlSanitizer
{
    /**
     * Replace `://user:pass@` (or just `://user@`) with `://`. Returns the
     * original input unchanged when it isn't a URL we can parse.
     */
    public static function strip(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }

        // Cheap guard: only act on strings that look like a scheme://… url
        // with userinfo, otherwise short-circuit to avoid touching paths.
        if (!preg_match('#^[a-z][a-z0-9+.\-]*://[^/@\s]+@#i', $url)) {
            return $url;
        }

        return preg_replace('#^([a-z][a-z0-9+.\-]*://)[^/@\s]+@#i', '$1', $url);
    }

    /**
     * Strip credentials from any URL embedded inside a free-form string.
     * Useful for sanitising exception messages that include the offending URL.
     */
    public static function scrubMessage(?string $message): ?string
    {
        if ($message === null || $message === '') {
            return $message;
        }

        return preg_replace(
            '#([a-z][a-z0-9+.\-]*://)[^/@\s]+@#i',
            '$1',
            $message
        );
    }
}
