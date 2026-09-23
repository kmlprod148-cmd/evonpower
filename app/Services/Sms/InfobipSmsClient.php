<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InfobipSmsClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $sender,
        private readonly int $timeout,
        private readonly string $driver,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            baseUrl: (string) config('services.infobip.base_url', ''),
            apiKey:  (string) config('services.infobip.api_key', ''),
            sender:  (string) config('services.infobip.sender', 'EVON'),
            timeout: (int)    config('services.infobip.timeout', 8),
            driver:  (string) config('services.infobip.driver', 'log'),
        );
    }

    /**
     * Send a plain SMS. Returns true when the upstream accepts the request.
     * In `log` driver mode it writes the payload to the log and returns true.
     */
    public function send(string $toE164, string $text): bool
    {
        if ($this->driver !== 'infobip' || $this->baseUrl === '' || $this->apiKey === '') {
            Log::info('[SMS:log] outbound message', [
                'to'   => $toE164,
                'text' => $text,
            ]);
            return true;
        }

        $response = Http::withHeaders([
                'Authorization' => 'App ' . $this->apiKey,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ])
            ->timeout($this->timeout)
            ->post(rtrim($this->baseUrl, '/') . '/sms/2/text/advanced', [
                'messages' => [[
                    'from'         => $this->sender,
                    'destinations' => [['to' => ltrim($toE164, '+')]],
                    'text'         => $text,
                ]],
            ]);

        if ($response->successful()) {
            return true;
        }

        Log::error('[SMS:infobip] send failed', [
            'to'     => $toE164,
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);
        return false;
    }
}
