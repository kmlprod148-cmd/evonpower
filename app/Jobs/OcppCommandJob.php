<?php

namespace App\Jobs;

use App\Events\OcppCommandFailed;
use App\Events\OcppCommandSucceeded;
use App\Exceptions\OcppCommandException;
use App\Models\ChargePointCommand;
use App\Services\SteveApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class OcppCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    public $tries = 3;

    /**
     * @var int
     */
    public $backoff = 10;

    /**
     * @var string
     */
    protected string $commandName;

    /**
     * @var string
     */
    protected string $chargeBoxId;

    /**
     * @var array
     */
    protected array $params;

    /**
     * @var int
     */
    protected int $triggeredByUserId;

    /**
     * @var ChargePointCommand
     */
    protected ChargePointCommand $commandRecord;

    /**
     * Create a new job instance.
     *
     * @param string $commandName
     * @param string $chargeBoxId
     * @param array $params
     * @param int $triggeredByUserId
     */
    public function __construct(string $commandName, string $chargeBoxId, array $params, int $triggeredByUserId)
    {
        $this->commandName = $commandName;
        $this->chargeBoxId = $chargeBoxId;
        $this->params = $params;
        $this->triggeredByUserId = $triggeredByUserId;

        // Persist the command record immediately as pending
        $this->commandRecord = ChargePointCommand::create([
            'charge_box_id' => $this->chargeBoxId,
            'command' => $this->commandName,
            'params' => $this->params,
            'status' => 'pending',
            'triggered_by' => $this->triggeredByUserId,
        ]);
    }

    /**
     * Execute the job.
     *
     * @param SteveApiService $service
     * @return void
     */
    public function handle(SteveApiService $service): void
    {
        try {
            Log::channel('ocpp')->info("Executing OcppCommandJob", [
                'command' => $this->commandName,
                'chargeBoxId' => $this->chargeBoxId,
                'params' => $this->params,
            ]);

            $methodMap = [
                'remote-start' => 'remoteStart',
                'remote-stop' => 'remoteStop',
                'reset' => 'resetStation',
                'availability' => 'changeAvailability',
                'unlock' => 'unlockConnector',
                'diagnostics' => 'getDiagnostics',
                'set-profile' => 'setChargingProfile',
                'clear-profile' => 'clearChargingProfile',
                'reserve' => 'reserveNow',
                'cancel-reserve' => 'cancelReservation',
            ];

            $method = $methodMap[$this->commandName] ?? null;

            if (!$method || !method_exists($service, $method)) {
                throw new OcppCommandException("Unsupported command: {$this->commandName}");
            }

            // Map params to method arguments if necessary, or pass as array if service is modified
            // For now, let's assume we use reflection or a simpler switch if params vary
            $result = $this->invokeServiceMethod($service, $method);

            $this->commandRecord->update([
                'status' => 'accepted',
                'response' => $result,
            ]);

            event(new OcppCommandSucceeded($this->commandRecord));

        } catch (OcppCommandException $e) {
            if ($e->isRetryable() && $this->attempts() < $this->tries) {
                Log::channel('ocpp')->warning("Job retryable, releasing back to queue", ['error' => $e->getMessage()]);
                $this->release($this->backoff);
                return;
            }

            $this->failJob($e->getMessage());
        } catch (Throwable $e) {
            $this->failJob($e->getMessage());
        }
    }

    /**
     * Dynamically invoke the service method based on command name.
     *
     * @param SteveApiService $service
     * @param string $method
     * @return array
     */
    protected function invokeServiceMethod(SteveApiService $service, string $method): array
    {
        return match ($method) {
            'remoteStart' => $service->remoteStart($this->chargeBoxId, $this->params['connectorId'], $this->params['idTag']),
            'remoteStop' => $service->remoteStop($this->chargeBoxId, $this->params['transactionId']),
            'resetStation' => $service->resetStation($this->chargeBoxId, $this->params['type'] ?? 'Soft'),
            'changeAvailability' => $service->changeAvailability($this->chargeBoxId, $this->params['connectorId'], $this->params['type']),
            'unlockConnector' => $service->unlockConnector($this->chargeBoxId, $this->params['connectorId']),
            'getDiagnostics' => $service->getDiagnostics($this->chargeBoxId, $this->params['uploadUrl']),
            'setChargingProfile' => $service->setChargingProfile($this->chargeBoxId, $this->params['connectorId'], $this->params['chargingProfile']),
            'clearChargingProfile' => $service->clearChargingProfile($this->chargeBoxId),
            'reserveNow' => $service->reserveNow($this->chargeBoxId, $this->params['connectorId'], $this->params['expiryDate'], $this->params['idTag'], $this->params['reservationId']),
            'cancelReservation' => $service->cancelReservation($this->chargeBoxId, $this->params['reservationId']),
            default => throw new OcppCommandException("Method mapping missing for {$method}"),
        };
    }

    /**
     * Mark command as failed and dispatch event.
     *
     * @param string $errorMessage
     * @return void
     */
    protected function failJob(string $errorMessage): void
    {
        Log::channel('ocpp')->error("OcppCommandJob Failed", [
            'command' => $this->commandName,
            'chargeBoxId' => $this->chargeBoxId,
            'error' => $errorMessage,
        ]);

        $this->commandRecord->update([
            'status' => 'failed',
            'response' => ['error' => $errorMessage],
        ]);

        event(new OcppCommandFailed($this->commandRecord, $errorMessage));
    }

    /**
     * Handle job failure.
     *
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        $this->failJob($exception->getMessage());
    }
}
