<?php

namespace App\Console\Commands;

use App\Models\ChargingSession;
use App\Services\PostpaidChargingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateAllPostpaidSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'postpaid:update-all-sessions 
                            {--limit=50 : Nombre maximum de sessions à traiter}
                            {--force : Forcer la mise à jour même si récente}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Met à jour la consommation de toutes les sessions postpayées en cours';

    protected $postpaidService;

    /**
     * Create a new command instance.
     */
    public function __construct(PostpaidChargingService $postpaidService)
    {
        parent::__construct();
        $this->postpaidService = $postpaidService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        $force = $this->option('force');

        $this->info('🔄 Mise à jour des sessions postpayées en cours...');

        // Récupérer toutes les sessions postpayées en cours
        $query = ChargingSession::where('mode', 'postpaid')
            ->where('status', 'in_progress')
            ->orderBy('started_at', 'asc');

        if (!$force) {
            // Ne mettre à jour que les sessions qui n'ont pas été mises à jour récemment (plus de 30 secondes)
            $query->where(function ($q) {
                $q->whereNull('updated_at')
                  ->orWhere('updated_at', '<', now()->subSeconds(30));
            });
        }

        $sessions = $query->limit($limit)->get();

        if ($sessions->isEmpty()) {
            $this->info('✅ Aucune session postpayée en cours à mettre à jour');
            return 0;
        }

        $this->info("📊 {$sessions->count()} session(s) trouvée(s)");

        $bar = $this->output->createProgressBar($sessions->count());
        $bar->start();

        $updated = 0;
        $failed = 0;

        foreach ($sessions as $session) {
            try {
                $result = $this->postpaidService->updateRealTimeConsumption($session);

                if ($result['success']) {
                    $updated++;
                } else {
                    $failed++;
                    $this->warn("\n⚠️  Échec pour session {$session->session_id}: " . ($result['message'] ?? 'Erreur inconnue'));
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error('Erreur lors de la mise à jour de session postpayée', [
                    'session_id' => $session->session_id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("\n❌ Erreur pour session {$session->session_id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Mise à jour terminée:");
        $this->info("   - Sessions mises à jour: {$updated}");
        if ($failed > 0) {
            $this->warn("   - Sessions en échec: {$failed}");
        }

        return 0;
    }
}

