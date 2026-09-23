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
 * Job de validation offline des demandes de retrait
 * 
 * Valide les coordonnées bancaires en mode hors-ligne
 * avant le traitement final
 */
class ValidateWithdrawalOffline implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Nombre de tentatives
     */
    public int $tries = 3;

    /**
     * Temps entre les tentatives (secondes)
     */
    public int $backoff = 60;

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
        $this->onQueue('withdrawal-validation');
    }

    /**
     * Execute the job.
     */
    public function handle(WithdrawalRequestService $service): void
    {
        Log::info('ValidateWithdrawalOffline: Début de la validation', [
            'withdrawal_id' => $this->withdrawalRequest->id,
            'attempt' => $this->attempts(),
        ]);

        // Vérifier si la demande est toujours en attente
        if (!$this->withdrawalRequest->isPending()) {
            Log::info('ValidateWithdrawalOffline: Demande déjà traitée', [
                'withdrawal_id' => $this->withdrawalRequest->id,
                'status' => $this->withdrawalRequest->status,
            ]);
            return;
        }

        // Effectuer la validation
        $result = $service->validateOffline($this->withdrawalRequest);

        if ($result['success']) {
            Log::info('ValidateWithdrawalOffline: Validation réussie', [
                'withdrawal_id' => $this->withdrawalRequest->id,
                'valid' => $result['valid'],
            ]);

            // Si validation échouée, rejeter automatiquement
            if (!$result['valid']) {
                $this->handleValidationFailure($result['message']);
            }
        } else {
            Log::warning('ValidateWithdrawalOffline: Erreur de validation', [
                'withdrawal_id' => $this->withdrawalRequest->id,
                'error' => $result['message'],
            ]);

            throw new \Exception($result['message']);
        }
    }

    /**
     * Gérer l'échec de validation
     */
    protected function handleValidationFailure(string $reason): void
    {
        Log::warning('ValidateWithdrawalOffline: Validation échouée, rejection', [
            'withdrawal_id' => $this->withdrawalRequest->id,
            'reason' => $reason,
        ]);

        // Mettre à jour les métadonnées
        $metadata = $this->withdrawalRequest->metadata ?? [];
        $metadata['offline_validation_failed'] = true;
        $metadata['offline_validation_error'] = $reason;
        $metadata['auto_rejected'] = true;

        $this->withdrawalRequest->update(['metadata' => $metadata]);

        // Rejeter automatiquement la demande
        $this->withdrawalRequest->reject(
            null, // Système automatique
            "Validation automatique échouée: {$reason}"
        );

        // Rembourser le montant
        $wallet = $this->withdrawalRequest->wallet;
        if ($wallet) {
            $wallet->credit(
                $this->withdrawalRequest->amount,
                "Remboursement après échec validation #{$this->withdrawalRequest->id}",
                [
                    'withdrawal_id' => $this->withdrawalRequest->id,
                    'type' => 'validation_failure_refund',
                ]
            );
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ValidateWithdrawalOffline: Échec définitif', [
            'withdrawal_id' => $this->withdrawalRequest->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Marquer comme nécessitant une attention manuelle
        $metadata = $this->withdrawalRequest->metadata ?? [];
        $metadata['validation_requires_manual_review'] = true;
        $metadata['validation_error'] = $exception->getMessage();

        $this->withdrawalRequest->update(['metadata' => $metadata]);
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(2);
    }
}
