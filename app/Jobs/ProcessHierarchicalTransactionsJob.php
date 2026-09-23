<?php

namespace App\Jobs;

use App\Services\ChargingSessionCompletionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessHierarchicalTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $chargingPointId;
    protected $sessionId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $chargingPointId = null, int $sessionId = null)
    {
        $this->chargingPointId = $chargingPointId;
        $this->sessionId = $sessionId;
    }

    /**
     * Execute the job.
     */
    public function handle(ChargingSessionCompletionService $completionService): void
    {
        try {
            if ($this->sessionId) {
                // Traiter une session spécifique
                $session = \App\Models\ChargingSession::find($this->sessionId);
                
                if ($session) {
                    $result = $completionService->processSessionCompletion($session);
                    
                    Log::info('Job de transaction hiérarchique - Session spécifique', [
                        'session_id' => $this->sessionId,
                        'result' => $result
                    ]);
                }
            } else {
                // Traiter toutes les sessions en attente
                $result = $completionService->processAllPendingSessions();
                
                Log::info('Job de transaction hiérarchique - Toutes les sessions', [
                    'processed' => $result['processed'],
                    'failed' => $result['failed'],
                    'errors' => $result['errors']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erreur dans le job de transaction hiérarchique', [
                'charging_point_id' => $this->chargingPointId,
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Échec du job de transaction hiérarchique', [
            'charging_point_id' => $this->chargingPointId,
            'session_id' => $this->sessionId,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
