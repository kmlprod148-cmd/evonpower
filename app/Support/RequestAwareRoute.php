<?php

namespace App\Support;

use Illuminate\Http\Request;

class RequestAwareRoute
{
    public static function to(?Request $request, string $routeName, mixed $parameters = []): string
    {
        $basePrefix = static::extractBasePrefixFromRequest($request);

        if ($basePrefix !== '') {
            // Return absolute URL so redirect()->to() treats it as-is and doesn't
            // prepend the forced root URL (URL::forceRootUrl) a second time.
            return route($routeName, $parameters, true);
        }

        return route($routeName, $parameters, false);
    }

    public static function extractBasePrefixFromRequest(?Request $request): string
    {
        if ($request === null) {
            return '';
        }

        $requestUri = strtok((string) $request->getRequestUri(), '?') ?: '';

        if ($requestUri === '' || $requestUri === '/') {
            return '';
        }

        $normalizedUri = '/' . ltrim($requestUri, '/');

        if ($normalizedUri === '/public' || str_starts_with($normalizedUri, '/public/')) {
            return '/public';
        }

        $publicSegmentPosition = strpos($normalizedUri, '/public/');

        if ($publicSegmentPosition === false) {
            return str_ends_with($normalizedUri, '/public')
                ? rtrim($normalizedUri, '/')
                : '';
        }

        return rtrim(substr($normalizedUri, 0, $publicSegmentPosition + 7), '/');
    }
}
