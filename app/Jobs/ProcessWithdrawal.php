<?php

namespace App\Jobs;

use App\Models\WithdrawalRequest;
use App\Services\WithdrawalRequestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job de traitement d'une demande de retrait
 */
class ProcessWithdrawal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives
     */
    public int $tries = 3;

    /**
     * Temps entre les tentatives (secondes)
     */
    public int $backoff = 300; // 5 minutes

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public WithdrawalRequest $withdrawalRequest
    ) {
        $this->onQueue('withdrawals');
    }

    /**
     * Execute the job.
     */
    public function handle(WithdrawalRequestService $service): void
    {
        Log::info('ProcessWithdrawal: Début du traitement', [
            'withdrawal_id' => $this->withdrawalRequest->id,
            'status' => $this->withdrawalRequest->status,
            'attempt' => $this->attempts(),
        ]);

        // Vérifier si la demande peut toujours être traitée
        if (!$this->withdrawalRequest->canProcess()) {
            Log::warning('ProcessWithdrawal: Demande ne peut pas être traitée', [
                'withdrawal_id' => $this->withdrawalRequest->id,
                'status' => $this->withdrawalRequest->status,
            ]);
            return;
        }

        try {
            $result = $service->process($this->withdrawalRequest);

            if ($result['success']) {
                Log::info('ProcessWithdrawal: Succès', [
                    'withdrawal_id' => $this->withdrawalRequest->id,
                    'external_id' => $result['external_id'] ?? null,
                ]);
            } else {
                Log::warning('ProcessWithdrawal: Échec, nouvelle tentative prévue', [
                    'withdrawal_id' => $this->withdrawalRequest->id,
                    'error' => $result['error'] ?? 'Erreur inconnue',
                ]);

                // Relancer si prévu
                if (isset($result['retry_scheduled'])) {
                    throw new \Exception($result['error'] ?? 'Erreur de traitement');
                }
            }

        } catch (\Exception $e) {
            Log::error('ProcessWithdrawal: Exception', [
                'withdrawal_id' => $this->withdrawalRequest->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessWithdrawal: Échec définitif', [
            'withdrawal_id' => $this->withdrawalRequest->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Marquer comme échoué si toutes les tentatives épuisées
        if ($this->attempts() >= $this->tries) {
            $this->withdrawalRequest->markAsFailed(
                null,
                "Échec après {$this->attempts()} tentatives: {$exception->getMessage()}"
            );

            // Rembourser automatiquement
            $wallet = $this->withdrawalRequest->wallet;
            if ($wallet) {
                $wallet->credit(
                    $this->withdrawalRequest->amount,
                    "Remboursement automatique après échec #{$this->withdrawalRequest->id}",
                    [
                        'withdrawal_id' => $this->withdrawalRequest->id,
                        'type' => 'auto_refund_after_failure',
                    ]
                );
            }
        }
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(24);
    }
}
