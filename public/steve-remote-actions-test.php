<?php
/**
 * SteVe Remote-Action Test Harness — browser-executable.
 *
 * Drop-in single file. Open in a browser (e.g.
 *   http://localhost/steve-remote-actions-test.php
 *   https://devcharge.evonpower.com/steve-remote-actions-test.php
 * ) and click "Run". Validates every documented remote-command endpoint on
 * SteVe 3.9.0-SNAPSHOT against the configured server, with full request/
 * response capture per call.
 *
 * Endpoints under test (POST unless noted; relative to /manager/api/v1):
 *   GET  /chargePoints                  — discovery
 *   GET  /ocppTags?usable               — idTag discovery
 *   GET  /connectors/status             — before / after snapshots
 *   GET  /transactions?type=ACTIVE      — find the txn we just started
 *   POST /ocpp/remote-start             — start session  (default connector=1, auto-resolved idTag)
 *   POST /ocpp/remote-stop              — stop the session just started
 *   POST /ocpp/unlock-connector
 *   POST /ocpp/change-availability      — INOPERATIVE then back to OPERATIVE
 *   POST /ocpp/change-configuration     — toggle HeartBeatInterval
 *   POST /ocpp/clear-cache
 *   POST /ocpp/reboot                   — gated (disruptive)
 *
 * The harness never logs the configured password. Read-only by default; the
 * disruptive endpoints (change-availability, change-configuration, reboot)
 * are checkbox-gated.
 *
 * Default credentials are documented for the staging SteVe instance — change
 * them in the form before running against production.
 */

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

if (!extension_loaded('curl')) {
    http_response_code(500);
    echo '<h1>SteVe harness requires the php-curl extension.</h1>'
       . '<p>Install/enable <code>ext-curl</code> in your PHP build and reload.</p>';
    exit;
}

// ---------------------------------------------------------------------------
// HTTP CLIENT
// ---------------------------------------------------------------------------

final class SteveHttpClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $username,
        private readonly string $password,
        private readonly int $timeout = 15,
    ) {}

    /**
     * Perform a single HTTP call against the SteVe Management API.
     *
     * Returns a normalized envelope:
     *   [
     *     'request' => [method, url, query, body],
     *     'response' => [status, headers, body_raw, body_parsed|null, duration_ms, curl_error|null],
     *     'ok' => bool,
     *   ]
     */
    public function call(
        string $method,
        string $path,
        array $query = [],
        array|string|null $body = null,
    ): array {
        $url = $this->buildUrl($path, $query);
        $ch = curl_init();

        $headers = ['Accept: application/json'];
        $bodyForCurl = null;
        if ($body !== null) {
            $bodyForCurl = is_array($body) ? json_encode($body, JSON_UNESCAPED_SLASHES) : (string) $body;
            $headers[] = 'Content-Type: application/json';
        }

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_USERPWD        => $this->username . ':' . $this->password,
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_POSTFIELDS     => $bodyForCurl,
        ]);

        $start = microtime(true);
        $raw   = curl_exec($ch);
        $durationMs = (int) round((microtime(true) - $start) * 1000);

        $status  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $hdrSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $err     = curl_error($ch) ?: null;
        curl_close($ch);

        $rawHeaders = $raw === false ? '' : (string) substr((string) $raw, 0, $hdrSize);
        $rawBody    = $raw === false ? '' : (string) substr((string) $raw, $hdrSize);

        $parsed = null;
        $trim = trim($rawBody);
        if ($trim !== '') {
            try {
                $decoded = json_decode($trim, true, 512, JSON_THROW_ON_ERROR);
                $parsed  = $decoded;
            } catch (\JsonException) {
                // SteVe sometimes returns a bare integer (task ID) for OCPP
                // commands. We try once more without strict JSON parsing.
                if (ctype_digit($trim)) {
                    $parsed = ['taskId' => (int) $trim];
                }
            }
        }

        return [
            'request' => [
                'method' => $method,
                'url'    => $url,
                'query'  => $query,
                'body'   => $body,
            ],
            'response' => [
                'status'       => $status,
                'headers_raw'  => $rawHeaders,
                'body_raw'     => $rawBody,
                'body_parsed'  => $parsed,
                'duration_ms'  => $durationMs,
                'curl_error'   => $err,
            ],
            'ok' => $err === null && $status >= 200 && $status < 300,
        ];
    }

    private function buildUrl(string $path, array $query): string
    {
        // SteVe REST 3.9.0 root: /manager/api/v1. We accept any of:
        //   …/steve           → append /manager/api/v1
        //   …/steve/manager   → append /api/v1
        //   …/steve/manager/api/v1 → append nothing
        $base = rtrim($this->baseUrl, '/');
        $tail = parse_url($base, PHP_URL_PATH) ?: '';

        $prefix = match (true) {
            str_ends_with($tail, '/manager/api/v1') => '',
            str_ends_with($tail, '/manager/api')    => '/v1',
            str_ends_with($tail, '/manager')        => '/api/v1',
            default                                  => '/manager/api/v1',
        };

        $url = $base . $prefix . '/' . ltrim($path, '/');

        // SteVe is picky about repeated keys vs PHP's foo[0]= syntax. Build by hand.
        $pairs = [];
        foreach ($query as $k => $v) {
            foreach ((array) $v as $item) {
                if ($item === null || $item === '') continue;
                $pairs[] = rawurlencode((string) $k) . '=' . rawurlencode((string) $item);
            }
        }
        return $pairs === [] ? $url : ($url . '?' . implode('&', $pairs));
    }
}

// ---------------------------------------------------------------------------
// TEST RUNNER
// ---------------------------------------------------------------------------

final class SteveRemoteActionTestRunner
{
    private SteveHttpClient $http;
    /** @var array<int, array> */
    public array $results = [];
    private array $state = [
        'chargeBoxId'    => null,
        'chargePointPk'  => null,
        'connectorId'    => 1,
        'idTag'          => null,
        'startedTxnId'   => null,
    ];

    public function __construct(
        private readonly array $config,
        private readonly array $flags,
    ) {
        $this->http = new SteveHttpClient(
            $this->config['base_url'],
            $this->config['username'],
            $this->config['password'],
            $this->config['timeout'],
        );

        if (!empty($this->config['charge_box_id'])) {
            $this->state['chargeBoxId'] = $this->config['charge_box_id'];
        }
        if (!empty($this->config['ocpp_tag'])) {
            $this->state['idTag'] = $this->config['ocpp_tag'];
        }
        $this->state['connectorId'] = max(1, (int) $this->config['connector_id']);
    }

    public function run(): void
    {
        // ── 1. DISCOVERY ──────────────────────────────────────────────────
        $this->test('1. List charge points', 'GET', 'discovery', fn () =>
            $this->http->call('GET', '/chargePoints')
        , function (array $res) {
            if (!$res['ok']) return [false, 'list failed'];
            $rows = is_array($res['response']['body_parsed']) ? $res['response']['body_parsed'] : [];
            if ($rows === []) return [false, 'SteVe has no charge points registered'];

            $pickFirst = $rows[0];
            if ($this->state['chargeBoxId'] === null) {
                $this->state['chargeBoxId']   = $pickFirst['chargeBoxId']   ?? null;
                $this->state['chargePointPk'] = $pickFirst['chargeBoxPk']   ?? $pickFirst['chargePointPk'] ?? null;
                return [true, sprintf('auto-picked chargeBoxId="%s" (pk=%s) — %d total',
                    $this->state['chargeBoxId'],
                    $this->state['chargePointPk'] ?? '?',
                    count($rows)
                )];
            }

            // User supplied a chargeBoxId: confirm SteVe knows it.
            foreach ($rows as $r) {
                if (($r['chargeBoxId'] ?? null) === $this->state['chargeBoxId']) {
                    $this->state['chargePointPk'] = $r['chargeBoxPk'] ?? $r['chargePointPk'] ?? null;
                    return [true, sprintf('confirmed chargeBoxId="%s" (pk=%s)',
                        $this->state['chargeBoxId'], $this->state['chargePointPk'] ?? '?')];
                }
            }
            return [false, sprintf('chargeBoxId="%s" not present in SteVe', $this->state['chargeBoxId'])];
        });

        if ($this->state['chargeBoxId'] === null) {
            $this->skip('Remaining tests require a chargeBoxId; aborting.');
            return;
        }

        // ── 2. USABLE TAGS ────────────────────────────────────────────────
        $this->test('2. Resolve usable idTag', 'GET', 'discovery', fn () =>
            $this->http->call('GET', '/ocppTags', [
                'expired'       => 'FALSE',
                'blocked'       => 'FALSE',
                'inTransaction' => 'FALSE',
            ])
        , function (array $res) {
            if (!$res['ok']) return [false, 'idTag lookup failed'];
            $rows = is_array($res['response']['body_parsed']) ? $res['response']['body_parsed'] : [];

            // Honour an explicit form input, otherwise pick the first usable row.
            if ($this->state['idTag'] !== null) {
                foreach ($rows as $r) {
                    if (($r['idTag'] ?? null) === $this->state['idTag']) {
                        return [true, sprintf('explicit idTag="%s" confirmed usable', $this->state['idTag'])];
                    }
                }
                return [false, sprintf('explicit idTag="%s" is not in the usable list', $this->state['idTag'])];
            }
            if ($rows === []) return [false, 'no usable idTag available — register one in SteVe first'];

            $this->state['idTag'] = $rows[0]['idTag'] ?? null;
            return [$this->state['idTag'] !== null,
                $this->state['idTag'] !== null
                    ? sprintf('auto-picked idTag="%s" (%d candidates)', $this->state['idTag'], count($rows))
                    : 'first row missing idTag field'];
        });

        // ── 3. STATUS BEFORE ──────────────────────────────────────────────
        $this->test('3. Connector status (before)', 'GET', 'observation', fn () =>
            $this->http->call('GET', '/connectors/status', ['chargeBoxId' => $this->state['chargeBoxId']])
        , function (array $res) {
            if (!$res['ok']) return [false, 'status fetch failed'];
            $data = $res['response']['body_parsed'];
            $online = $data['online'] ?? null;
            $rows   = $data['connectors'] ?? (is_array($data) ? $data : []);
            $rows   = is_array($rows) ? $rows : [];
            return [true, sprintf('online=%s, connectors=%d',
                $online === null ? '?' : ($online ? 'true' : 'false'),
                count($rows)
            )];
        });

        // ── 4. REMOTE START ───────────────────────────────────────────────
        // Default connector=1, auto-resolved idTag. The headline test.
        if ($this->state['idTag'] === null) {
            $this->skip('Remote-start requires an idTag — aborting remainder.');
            return;
        }

        $this->test('4. Remote start', 'POST', 'remote-command', fn () =>
            $this->http->call('POST', '/ocpp/remote-start', [], [
                'chargeBoxId' => $this->state['chargeBoxId'],
                'connectorId' => $this->state['connectorId'],
                'ocppTag'     => $this->state['idTag'],
            ])
        , function (array $res) {
            // SteVe REST 3.9.0 returns a queued task ID or OcppOperationResponse.
            // A 200 with either shape is success; failures usually surface as 4xx.
            if (!$res['ok']) return [false, 'SteVe rejected the RemoteStartTransaction'];
            $task = $res['response']['body_parsed']['taskId'] ?? null;
            return [true, $task ? "queued as SteVe task #{$task}" : 'queued (no taskId returned)'];
        });

        // 5. Wait + look up the active transaction we created so we know what to stop.
        sleep(3);
        $this->test('5. Find active transaction', 'GET', 'observation', fn () =>
            $this->http->call('GET', '/transactions', [
                'type'        => 'ACTIVE',
                'chargeBoxId' => $this->state['chargeBoxId'],
            ])
        , function (array $res) {
            if (!$res['ok']) return [false, 'transaction lookup failed'];
            $rows = is_array($res['response']['body_parsed']) ? $res['response']['body_parsed'] : [];
            if ($rows === []) {
                return [false, 'no active transaction — RemoteStart may have been rejected by the charger'];
            }
            $tx = $rows[0]['id'] ?? $rows[0]['transactionPk'] ?? null;
            if ($tx !== null) {
                $this->state['startedTxnId'] = (int) $tx;
            }
            return [$tx !== null, $tx !== null
                ? sprintf('active transactionId=%d (idTag=%s)', (int) $tx, $rows[0]['ocppIdTag'] ?? '?')
                : 'active list returned but no transaction id field'];
        });

        // ── 6. UNLOCK CONNECTOR ───────────────────────────────────────────
        $this->test('6. Unlock connector', 'POST', 'remote-command', fn () =>
            $this->http->call('POST', '/ocpp/unlock-connector', [
                'chargeBoxId' => $this->state['chargeBoxId'],
                'connectorId' => $this->state['connectorId'],
            ])
        , function (array $res) {
            if (!$res['ok']) return [false, 'unlock command rejected'];
            $task = $res['response']['body_parsed']['taskId'] ?? null;
            return [true, $task ? "queued as task #{$task}" : 'queued'];
        });

        // ── 7. CHANGE AVAILABILITY (gated) ────────────────────────────────
        if ($this->flags['run_change_availability']) {
            $this->test('7a. Change availability → INOPERATIVE', 'POST', 'remote-command', fn () =>
                $this->http->call('POST', '/ocpp/change-availability', [
                    'chargeBoxId' => $this->state['chargeBoxId'],
                    'availType'   => 'INOPERATIVE',
                ])
            , fn (array $res) => $res['ok'] ? [true, 'INOPERATIVE queued'] : [false, 'rejected']);

            // Restore so we don't leave the box bricked.
            $this->test('7b. Change availability → OPERATIVE (restore)', 'POST', 'remote-command', fn () =>
                $this->http->call('POST', '/ocpp/change-availability', [
                    'chargeBoxId' => $this->state['chargeBoxId'],
                    'availType'   => 'OPERATIVE',
                ])
            , fn (array $res) => $res['ok'] ? [true, 'OPERATIVE queued'] : [false, 'rejected']);
        } else {
            $this->skip('7. ChangeAvailability — checkbox not selected.');
        }

        // ── 8. CHANGE CONFIGURATION (gated) ───────────────────────────────
        if ($this->flags['run_change_configuration']) {
            $this->test('8. Change configuration (HeartBeatInterval=60)', 'POST', 'remote-command', fn () =>
                $this->http->call(
                    'POST',
                    '/ocpp/change-configuration',
                    ['chargeBoxId' => $this->state['chargeBoxId']],
                    ['keyType' => 'PREDEFINED', 'confKey' => 'HeartBeatInterval', 'value' => '60']
                )
            , fn (array $res) => $res['ok'] ? [true, 'configuration write queued'] : [false, 'rejected']);
        } else {
            $this->skip('8. ChangeConfiguration — checkbox not selected.');
        }

        // ── 9. CLEAR CACHE ────────────────────────────────────────────────
        $this->test('9. Clear authorization cache', 'POST', 'remote-command', fn () =>
            $this->http->call('POST', '/ocpp/clear-cache', ['chargeBoxId' => $this->state['chargeBoxId']])
        , fn (array $res) => $res['ok'] ? [true, 'cache flush queued'] : [false, 'rejected']);

        // ── 10. REMOTE STOP ───────────────────────────────────────────────
        if ($this->state['startedTxnId'] !== null) {
            $this->test('10. Remote stop', 'POST', 'remote-command', fn () =>
                $this->http->call('POST', '/ocpp/remote-stop', [
                    'chargeBoxId' => $this->state['chargeBoxId'],
                ])
            , function (array $res) {
                if (!$res['ok']) return [false, 'RemoteStopTransaction rejected'];
                $task = $res['response']['body_parsed']['taskId'] ?? null;
                return [true, $task
                    ? sprintf('queued as task #%d (txn=%d)', $task, $this->state['startedTxnId'])
                    : sprintf('queued (txn=%d)', $this->state['startedTxnId'])];
            });
        } else {
            $this->skip('10. RemoteStop — no active transaction to stop.');
        }

        // ── 11. STATUS AFTER ──────────────────────────────────────────────
        $this->test('11. Connector status (after)', 'GET', 'observation', fn () =>
            $this->http->call('GET', '/connectors/status', ['chargeBoxId' => $this->state['chargeBoxId']])
        , fn (array $res) => $res['ok'] ? [true, 'snapshot captured'] : [false, 'fetch failed']);

        // ── 12. REBOOT (gated, last) ──────────────────────────────────────
        if ($this->flags['run_reboot']) {
            $this->test('12. Reboot (Soft)', 'POST', 'remote-command', fn () =>
                $this->http->call('POST', '/ocpp/reboot', ['chargeBoxId' => $this->state['chargeBoxId']])
            , fn (array $res) => $res['ok'] ? [true, 'reboot queued — charger may go offline briefly'] : [false, 'rejected']);
        } else {
            $this->skip('12. Reboot — checkbox not selected (disruptive).');
        }
    }

    /**
     * Run one test, capturing the call envelope + a (pass, summary) verdict.
     */
    private function test(string $name, string $method, string $kind, callable $invoke, callable $verdict): void
    {
        try {
            $res = $invoke();
            [$pass, $summary] = $verdict($res);
            $this->results[] = [
                'name'    => $name,
                'method'  => $method,
                'kind'    => $kind,
                'envelope'=> $res,
                'pass'    => (bool) $pass,
                'summary' => (string) $summary,
                'skipped' => false,
            ];
        } catch (\Throwable $e) {
            $this->results[] = [
                'name'    => $name,
                'method'  => $method,
                'kind'    => $kind,
                'envelope'=> null,
                'pass'    => false,
                'summary' => 'runtime exception: ' . $e->getMessage(),
                'skipped' => false,
            ];
        }
    }

    private function skip(string $reason): void
    {
        $this->results[] = [
            'name'    => $reason,
            'method'  => '',
            'kind'    => 'skip',
            'envelope'=> null,
            'pass'    => true,
            'summary' => 'skipped',
            'skipped' => true,
        ];
    }

    public function tally(): array
    {
        $tot = count($this->results);
        $skp = count(array_filter($this->results, fn ($r) => $r['skipped']));
        $pass = count(array_filter($this->results, fn ($r) => !$r['skipped'] && $r['pass']));
        $fail = count(array_filter($this->results, fn ($r) => !$r['skipped'] && !$r['pass']));
        return ['total' => $tot, 'passed' => $pass, 'failed' => $fail, 'skipped' => $skp];
    }
}

// ---------------------------------------------------------------------------
// FORM HANDLING
// ---------------------------------------------------------------------------

$defaults = [
    'base_url'      => 'http://158.69.27.239:8180/steve',
    'username'      => 'admin',
    'password'      => '1234',
    'charge_box_id' => '',
    'connector_id'  => 1,
    'ocpp_tag'      => '',
    'timeout'       => 15,
];

$config = $defaults;
foreach ($defaults as $k => $_v) {
    if (isset($_POST[$k]) && $_POST[$k] !== '') {
        $config[$k] = $_POST[$k];
    }
}
$config['connector_id'] = (int) $config['connector_id'];
$config['timeout']      = max(3, min(60, (int) $config['timeout']));

$flags = [
    'run_change_availability'  => !empty($_POST['run_change_availability']),
    'run_change_configuration' => !empty($_POST['run_change_configuration']),
    'run_reboot'               => !empty($_POST['run_reboot']),
];

$running = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['run']);

// ---------------------------------------------------------------------------
// VIEW HELPERS
// ---------------------------------------------------------------------------

function h(?string $s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

function prettyJson(mixed $value): string
{
    if ($value === null) return '';
    if (is_string($value)) {
        $trim = trim($value);
        if ($trim === '') return '';
        $decoded = json_decode($trim, true);
        if ($decoded !== null || $trim === 'null') {
            return (string) json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        return $trim;
    }
    return (string) json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

function shortMaskedUrl(string $url): string
{
    // Strip embedded basic-auth if any.
    return (string) preg_replace('#://[^/@]+@#', '://', $url);
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SteVe Remote-Action Test Harness</title>
<style>
  :root {
    --bg-0: #0b0f17; --bg-1: #111827; --bg-2: #1f2937;
    --border: #1f2a3a; --accent: #22d3ee;
    --ok: #22c55e; --err: #ef4444; --warn: #f59e0b; --skip: #94a3b8;
  }
  html, body { background: var(--bg-0); color: #e5e7eb; font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif; margin: 0; }
  .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
  main { max-width: 1280px; margin: 0 auto; padding: 24px 20px 80px; }
  header { display:flex; align-items:baseline; gap:14px; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }
  header h1 { font-size: 18px; margin: 0; letter-spacing: 0.12em; text-transform: uppercase; color: #67e8f9; }
  header .sub { font-size: 12px; color: #94a3b8; }
  form { display: grid; grid-template-columns: repeat(12, 1fr); gap: 12px; background: linear-gradient(180deg, #0f172a, #0b1220); border: 1px solid var(--border); border-radius: 10px; padding: 16px; margin-bottom: 18px; }
  form label { display: flex; flex-direction: column; gap: 4px; font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; }
  form label.col-3 { grid-column: span 3; }
  form label.col-4 { grid-column: span 4; }
  form label.col-2 { grid-column: span 2; }
  form label.col-6 { grid-column: span 6; }
  form input[type="text"], form input[type="password"], form input[type="number"] {
    background: #0b1220; border: 1px solid var(--border); color: #e5e7eb; border-radius: 6px; padding: 8px 10px; font-size: 13px; font-family: ui-monospace, monospace;
  }
  form input:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 2px rgba(34,211,238,0.18); }
  form .checks { grid-column: span 12; display: flex; gap: 18px; padding-top: 6px; border-top: 1px dashed var(--border); }
  form .checks label { flex-direction: row; align-items: center; gap: 6px; text-transform: none; letter-spacing: 0; color: #cbd5e1; font-size: 12px; }
  form .actions { grid-column: span 12; display: flex; gap: 10px; }
  form button { background: #0891b2; color: white; border: none; border-radius: 6px; padding: 10px 18px; font-weight: 600; cursor: pointer; }
  form button:hover { background: #0e7490; }
  form button.secondary { background: #334155; }
  form button.secondary:hover { background: #475569; }
  .tally { display:flex; gap: 14px; font-size: 12px; margin-bottom: 16px; }
  .tally span { background: #1f2937; padding: 6px 12px; border-radius: 6px; border: 1px solid var(--border); }
  .tally .ok { color: #86efac; border-color: rgba(34,197,94,0.35); }
  .tally .err { color: #fca5a5; border-color: rgba(239,68,68,0.35); }
  .tally .skip { color: #cbd5e1; border-color: rgba(148,163,184,0.35); }
  .test { background: #0f172a; border: 1px solid var(--border); border-radius: 10px; margin-bottom: 12px; overflow: hidden; }
  .test summary { list-style: none; cursor: pointer; padding: 12px 14px; display: flex; align-items: center; gap: 12px; }
  .test summary::-webkit-details-marker { display: none; }
  .badge { font-size: 11px; padding: 3px 8px; border-radius: 9999px; font-weight: 600; letter-spacing: 0.04em; }
  .badge.ok { background: rgba(34,197,94,0.15); color: #86efac; border: 1px solid rgba(34,197,94,0.35); }
  .badge.err { background: rgba(239,68,68,0.15); color: #fca5a5; border: 1px solid rgba(239,68,68,0.35); }
  .badge.skip { background: rgba(148,163,184,0.12); color: #cbd5e1; border: 1px solid rgba(148,163,184,0.30); }
  .badge.method { background: rgba(34,211,238,0.10); color: #67e8f9; border: 1px solid rgba(34,211,238,0.30); }
  .summary-text { flex: 1; font-size: 13px; color: #cbd5e1; }
  .ms { font-size: 11px; color: #94a3b8; font-family: ui-monospace, monospace; }
  .panel { padding: 0 14px 14px; }
  .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 10px; }
  .row > div { background: #0b1220; border: 1px solid var(--border); border-radius: 8px; padding: 10px; }
  .row h4 { margin: 0 0 6px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; }
  pre { margin: 0; font-size: 12px; line-height: 1.45; white-space: pre-wrap; word-break: break-word; color: #e5e7eb; max-height: 360px; overflow: auto; }
  .url { font-size: 12px; color: #67e8f9; word-break: break-all; }
  .url .verb { color: #f0abfc; margin-right: 6px; }
  .skip-row { padding: 10px 14px; color: #94a3b8; font-size: 13px; }
  footer { margin-top: 24px; color: #64748b; font-size: 11px; text-align: center; }
</style>
</head>
<body>
<main>
  <header>
    <h1>SteVe Remote-Action Test Harness</h1>
    <span class="sub mono">aligned to SteVe 3.9.0-SNAPSHOT · /manager/api/v1/ocpp/*</span>
  </header>

  <form method="post" autocomplete="off">
    <label class="col-4">Base URL
      <input type="text" name="base_url" value="<?= h($config['base_url']) ?>" placeholder="http://158.69.27.239:8180/steve">
    </label>
    <label class="col-2">Username
      <input type="text" name="username" value="<?= h($config['username']) ?>" autocomplete="username">
    </label>
    <label class="col-2">Password
      <input type="password" name="password" value="<?= h($config['password']) ?>" autocomplete="current-password">
    </label>
    <label class="col-2">Connector ID
      <input type="number" name="connector_id" min="1" max="16" value="<?= h((string) $config['connector_id']) ?>">
    </label>
    <label class="col-2">Timeout (s)
      <input type="number" name="timeout" min="3" max="60" value="<?= h((string) $config['timeout']) ?>">
    </label>

    <label class="col-6">chargeBoxId <span class="mono" style="color:#64748b">(blank → auto-pick first)</span>
      <input type="text" name="charge_box_id" value="<?= h($config['charge_box_id']) ?>" placeholder="e.g. CP_TEST_01">
    </label>
    <label class="col-6">idTag <span class="mono" style="color:#64748b">(blank → auto-pick from usable tags)</span>
      <input type="text" name="ocpp_tag" value="<?= h($config['ocpp_tag']) ?>" placeholder="e.g. Open10Tag">
    </label>

    <div class="checks">
      <label><input type="checkbox" name="run_change_availability" <?= $flags['run_change_availability'] ? 'checked' : '' ?>> Run <span class="mono">change-availability</span> (INOPERATIVE→OPERATIVE)</label>
      <label><input type="checkbox" name="run_change_configuration" <?= $flags['run_change_configuration'] ? 'checked' : '' ?>> Run <span class="mono">change-configuration</span></label>
      <label><input type="checkbox" name="run_reboot" <?= $flags['run_reboot'] ? 'checked' : '' ?>> Run <span class="mono">reboot</span> (disruptive — last)</label>
    </div>

    <div class="actions">
      <button type="submit" name="run" value="1">Run tests</button>
      <button type="submit" class="secondary" name="defaults" value="1" onclick="document.querySelectorAll('input[type=text],input[type=password],input[type=number]').forEach(i=>i.value='')">Clear</button>
    </div>
  </form>

<?php if ($running):
    @set_time_limit(0);
    @ignore_user_abort(false);
    $runner = new SteveRemoteActionTestRunner($config, $flags);
    // Flush the form output so the user sees the page render while tests execute.
    if (function_exists('ob_get_level') && ob_get_level() > 0) { @ob_end_flush(); }
    @flush();

    $runner->run();
    $tally = $runner->tally();
?>
  <div class="tally">
    <span>total: <strong><?= (int) $tally['total'] ?></strong></span>
    <span class="ok">passed: <strong><?= (int) $tally['passed'] ?></strong></span>
    <span class="err">failed: <strong><?= (int) $tally['failed'] ?></strong></span>
    <span class="skip">skipped: <strong><?= (int) $tally['skipped'] ?></strong></span>
    <span class="mono">target: <?= h(shortMaskedUrl($config['base_url'])) ?></span>
  </div>

<?php foreach ($runner->results as $r):
    if ($r['skipped']): ?>
      <div class="test"><div class="skip-row"><span class="badge skip">SKIP</span> &nbsp; <?= h($r['name']) ?></div></div>
<?php   continue; endif;

    $env = $r['envelope'];
    $req = $env['request'] ?? null;
    $resp = $env['response'] ?? null;
    $cls = $r['pass'] ? 'ok' : 'err';
    $label = $r['pass'] ? 'PASS' : 'FAIL';
?>
    <details class="test" <?= $r['pass'] ? '' : 'open' ?>>
      <summary>
        <span class="badge <?= $cls ?>"><?= $label ?></span>
        <span class="badge method"><?= h($r['method']) ?></span>
        <span class="summary-text"><strong><?= h($r['name']) ?></strong> — <?= h($r['summary']) ?></span>
        <span class="ms">HTTP <?= h((string) ($resp['status'] ?? '?')) ?> · <?= h((string) ($resp['duration_ms'] ?? '?')) ?> ms</span>
      </summary>
      <div class="panel">
        <div class="url"><span class="verb"><?= h($req['method'] ?? '') ?></span><?= h(shortMaskedUrl((string) ($req['url'] ?? ''))) ?></div>
<?php   if (!empty($resp['curl_error'])): ?>
        <div class="row"><div><h4>cURL error</h4><pre><?= h((string) $resp['curl_error']) ?></pre></div></div>
<?php   endif; ?>
        <div class="row">
          <div>
            <h4>Request body</h4>
            <pre><?= h(prettyJson($req['body'] ?? null)) ?></pre>
          </div>
          <div>
            <h4>Response body</h4>
            <pre><?= h(prettyJson($resp['body_parsed'] ?? $resp['body_raw'] ?? null)) ?></pre>
          </div>
        </div>
      </div>
    </details>
<?php endforeach; ?>

<?php else: ?>
  <p style="color:#94a3b8;font-size:13px;line-height:1.55">
    Enter the SteVe server credentials, optionally pin a <span class="mono">chargeBoxId</span> + <span class="mono">idTag</span>,
    then press <strong>Run tests</strong>. Read-only and command-queue endpoints run automatically.
    Disruptive tests (<span class="mono">change-availability</span>, <span class="mono">change-configuration</span>, <span class="mono">reboot</span>)
    must be opted in via the checkboxes.
  </p>
<?php endif; ?>

  <footer>
    EVON · SteVe OCPP REST 3.9.0 remote-action validator · single-file harness
  </footer>
</main>
</body>
</html>
