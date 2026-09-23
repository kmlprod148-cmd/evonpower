<?php

namespace App\Jobs;

use App\Models\ChargingSession;
use App\Services\OcppOperationsService;
use App\Services\StevePostpaidPaymentService;
use App\Services\SteVeHttpClientService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * @deprecated Use {@see ReconcileChargingSessionsJob} instead. This class
 *             survives as a thin alias so existing schedules / queue payloads
 *             pinned to the old class name keep working. Slice C generalised
 *             the postpaid-only reconciliation into a flow that covers both
 *             prepaid and postpaid sessions plus operator-initiated stops.
 */
class SyncSteVePostpaidTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120;
    public $tries = 1;

    public function __construct(
        protected int $limit = 20,
        protected int $maxAgeMinutes = 60,
    ) {}

    /**
     * Delegate to the generic reconciler. Service-locator dispatch keeps the
     * old constructor signature usable from cron / queue payloads.
     */
    public function handle(
        SteVeHttpClientService $steve,
        OcppOperationsService $ocppOps,
        StevePostpaidPaymentService $postpaidService,
    ): void {
        (new ReconcileChargingSessionsJob($this->limit, $this->maxAgeMinutes))
            ->handle($steve, $ocppOps, $postpaidService);
    }

}
