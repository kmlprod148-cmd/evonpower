<?php

namespace App\Services\SteVe;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client HTTP SteVe "source of truth".
 *
 * - Base URL: config('steve.api_url') (racine du serveur, ex: http://host:8180)
 * - Auth: Basic Auth (config('steve.username'/'steve.password'))
 * - Endpoints: ceux exposés par Swagger (ex: /api/v1/ocpp/remote-start)
 */
class SteVeClient
{
    public function baseUrl(): string
    {
        return rtrim((string) config('steve.api_url', ''), '/');
    }

    public function username(): string
    {
        return (string) config('steve.username', '');
    }

    public function password(): string
    {
        return (string) config('steve.password', '');
    }

    public function timeoutSeconds(): int
    {
        return (int) config('steve.timeout', 30);
    }

    /**
     * POST JSON avec querystring optionnelle.
     * Retourne une structure stable pour que les services puissent interpréter.
     */
    public function postJson(string $path, array $payload = [], array $query = []): array
    {
        $url = $this->buildUrl($path);
        if (!empty($query)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        try {
            $client = Http::timeout($this->timeoutSeconds())
                ->acceptJson()
                ->asJson();

            if ($this->username() !== '' || $this->password() !== '') {
                $client = $client->withBasicAuth($this->username(), $this->password());
            }

            Log::debug('SteVeClient: POST', [
                'url' => $url,
                'query_keys' => array_keys($query),
                'payload_keys' => array_keys($payload),
                'has_basic_auth' => $this->username() !== '' && $this->password() !== '',
                'timeout_s' => $this->timeoutSeconds(),
            ]);

            /** @var Response $res */
            $res = $client->post($url, $payload);

            return $this->normalizeResponse($url, $res);
        } catch (\Throwable $e) {
            Log::warning('SteVeClient: POST exception', [
                'url' => $url,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => null,
                'url' => $url,
                'json' => null,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * GET JSON avec querystring.
     */
    public function getJson(string $path, array $query = []): array
    {
        $url = $this->buildUrl($path);
        if (!empty($query)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        try {
            $client = Http::timeout($this->timeoutSeconds())
                ->acceptJson();

            if ($this->username() !== '' || $this->password() !== '') {
                $client = $client->withBasicAuth($this->username(), $this->password());
            }

            Log::debug('SteVeClient: GET', [
                'url' => $url,
                'query_keys' => array_keys($query),
                'has_basic_auth' => $this->username() !== '' && $this->password() !== '',
                'timeout_s' => $this->timeoutSeconds(),
            ]);

            /** @var Response $res */
            $res = $client->get($url);

            return $this->normalizeResponse($url, $res);
        } catch (\Throwable $e) {
            Log::warning('SteVeClient: GET exception', [
                'url' => $url,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => null,
                'url' => $url,
                'json' => null,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * POST sans body, avec querystring (utile pour /api/v1/ocpp/remote-stop?chargeBoxId=...).
     */
    public function postQuery(string $path, array $query): array
    {
        $url = $this->buildUrl($path);
        if (!empty($query)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        try {
            $client = Http::timeout($this->timeoutSeconds())
                ->acceptJson()
                ->withHeaders(['Content-Type' => 'application/json']);

            if ($this->username() !== '' || $this->password() !== '') {
                $client = $client->withBasicAuth($this->username(), $this->password());
            }

            Log::debug('SteVeClient: POST query', [
                'url' => $url,
                'query_keys' => array_keys($query),
                'has_basic_auth' => $this->username() !== '' && $this->password() !== '',
                'timeout_s' => $this->timeoutSeconds(),
            ]);

            /** @var Response $res */
            $res = $client->post($url, []);

            return $this->normalizeResponse($url, $res);
        } catch (\Throwable $e) {
            Log::warning('SteVeClient: POST query exception', [
                'url' => $url,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'status' => null,
                'url' => $url,
                'json' => null,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function buildUrl(string $path): string
    {
        $base = $this->baseUrl();
        $path = '/' . ltrim($path, '/');
        return $base . $path;
    }

    protected function normalizeResponse(string $url, Response $res): array
    {
        $json = null;
        try {
            $json = $res->json();
        } catch (\Throwable) {
            $json = null;
        }

        $out = [
            'ok' => $res->successful(),
            'status' => $res->status(),
            'url' => $url,
            'json' => $json,
            'body' => $res->body(),
            'error' => null,
        ];

        if (!$out['ok']) {
            $msg = null;
            if (is_array($json)) {
                $msg = $json['message'] ?? $json['error'] ?? null;
            }
            $out['error'] = $msg ?: ("HTTP " . $res->status());

            Log::warning('SteVeClient: HTTP error', [
                'url' => $url,
                'status' => $res->status(),
                'message' => $out['error'],
            ]);
        }

        return $out;
    }
}


