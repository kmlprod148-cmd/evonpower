<?php

namespace App\Console\Commands;

use App\Models\ChargingSession;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupOrphanedPostpaidSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'postpaid:cleanup-orphaned 
                            {--hours=24 : Nombre d\'heures après lesquelles une session est considérée comme orpheline}
                            {--dry-run : Mode simulation sans modification}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nettoie les sessions postpayées orphelines (en cours depuis trop longtemps)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $hours = (int) $this->option('hours');
        $dryRun = $this->option('dry-run');

        $this->info("🧹 Nettoyage des sessions postpayées orphelines (plus de {$hours} heures)...");

        if ($dryRun) {
            $this->warn('⚠️  Mode simulation activé - aucune modification ne sera effectuée');
        }

        $cutoffTime = now()->subHours($hours);

        // Trouver les sessions postpayées en cours depuis trop longtemps
        $orphanedSessions = ChargingSession::where('mode', 'postpaid')
            ->where('status', 'in_progress')
            ->where('started_at', '<', $cutoffTime)
            ->get();

        if ($orphanedSessions->isEmpty()) {
            $this->info('✅ Aucune session orpheline trouvée');
            return 0;
        }

        $this->info("📊 {$orphanedSessions->count()} session(s) orpheline(s) trouvée(s)");

        $bar = $this->output->createProgressBar($orphanedSessions->count());
        $bar->start();

        $processed = 0;
        $errors = 0;

        foreach ($orphanedSessions as $session) {
            try {
                if ($dryRun) {
                    $this->line("\n📋 Session à traiter: {$session->session_id} (démarrée: {$session->started_at})");
                    $processed++;
                } else {
                    // Marquer la session comme terminée avec erreur
                    $session->update([
                        'status' => 'completed',
                        'ended_at' => now(),
                        'stop_reason' => 'orphaned_cleanup',
                        'error_code' => 'session_timeout',
                        'payment_status' => 'failed',
                    ]);

                    Log::warning('Session postpayée orpheline nettoyée', [
                        'session_id' => $session->session_id,
                        'started_at' => $session->started_at,
                        'hours_running' => $session->started_at->diffInHours(now()),
                    ]);

                    $processed++;
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('Erreur lors du nettoyage de session orpheline', [
                    'session_id' => $session->session_id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("\n❌ Erreur pour session {$session->session_id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->info("📋 Simulation terminée: {$processed} session(s) seraient traitées");
        } else {
            $this->info("✅ Nettoyage terminé:");
            $this->info("   - Sessions traitées: {$processed}");
            if ($errors > 0) {
                $this->warn("   - Erreurs: {$errors}");
            }
        }

        return 0;
    }
}

