<?php

namespace App\Console\Commands;

use App\Models\ChargingPoint;
use App\Services\ChargingPointSteveSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncChargingPointsWithSteve extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'charging-points:sync-steve
                            {--direction=export : Direction de sync (export=local->steve, import=steve->local, both=bidirectionnel)}
                            {--limit=100 : Nombre maximum de bornes à synchroniser}
                            {--id= : ID spécifique d\'une borne à synchroniser}
                            {--force : Forcer la synchronisation même si déjà synchronisé}
                            {--update-existing : Mettre à jour les bornes existantes lors de l\'import}
                            {--dry-run : Afficher ce qui serait fait sans exécuter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchroniser les bornes de recharge avec l\'API Steve';

    /**
     * Execute the console command.
     */
    public function handle(ChargingPointSteveSyncService $syncService)
    {
        $this->info('🔄 Synchronisation des bornes de recharge avec Steve API');
        $this->newLine();

        try {
            $direction = $this->option('direction');
            $limit = (int) $this->option('limit');
            $specificId = $this->option('id');
            $force = $this->option('force');
            $updateExisting = $this->option('update-existing');
            $dryRun = $this->option('dry-run');

            if ($dryRun) {
                $this->warn('⚠️  MODE DRY-RUN - Aucune modification ne sera effectuée');
                $this->newLine();
            }

            // Synchronisation d'une borne spécifique
            if ($specificId) {
                return $this->syncSpecificChargingPoint($syncService, $specificId, $direction, $dryRun);
            }

            // Synchronisation selon la direction
            switch ($direction) {
                case 'export':
                    return $this->exportToSteve($syncService, $limit, $dryRun);
                
                case 'import':
                    return $this->importFromSteve($syncService, $updateExisting, $dryRun);
                
                case 'both':
                    $exportResult = $this->exportToSteve($syncService, $limit, $dryRun);
                    $this->newLine();
                    $importResult = $this->importFromSteve($syncService, $updateExisting, $dryRun);
                    return ($exportResult === self::SUCCESS && $importResult === self::SUCCESS) 
                        ? self::SUCCESS 
                        : self::FAILURE;
                
                default:
                    $this->error('❌ Direction invalide. Utilisez: export, import, ou both');
                    return self::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('❌ Exception: ' . $e->getMessage());
            Log::error('SyncChargingPointsWithSteve: Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return self::FAILURE;
        }
    }

    /**
     * Exporter les bornes locales vers Steve
     */
    protected function exportToSteve(ChargingPointSteveSyncService $syncService, int $limit, bool $dryRun): int
    {
        $this->info('📤 Export: Local → Steve API');
        $this->newLine();

        if ($dryRun) {
            // Mode dry-run
            $chargingPoints = ChargingPoint::whereNull('steve_charging_point_id')
                ->orWhere('steve_sync_status', 'failed')
                ->orWhereNull('steve_sync_status')
                ->limit($limit)
                ->get();

            $this->info("Bornes qui seraient synchronisées: " . $chargingPoints->count());
            
            $tableData = [];
            foreach ($chargingPoints as $cp) {
                $tableData[] = [
                    $cp->id,
                    $cp->name,
                    $cp->city ?? 'N/A',
                    $cp->steve_sync_status ?? 'not_synced'
                ];
            }

            $this->table(['ID', 'Nom', 'Ville', 'Statut'], $tableData);

            return self::SUCCESS;
        }

        // Vraie synchronisation
        $result = $syncService->syncAllToSteve($limit);

        if (!$result['success']) {
            $this->error('❌ Échec de la synchronisation');
            $this->error($result['error'] ?? 'Erreur inconnue');
            return self::FAILURE;
        }

        $stats = $result['results'];
        $this->info("✅ Synchronisation terminée:");
        $this->line("   - Total: {$stats['total']}");
        $this->line("   - Succès: {$stats['success']}");
        $this->line("   - Échecs: {$stats['failed']}");
        $this->line("   - Ignorées: {$stats['skipped']}");

        if ($stats['success'] > 0) {
            $this->newLine();
            $this->info('📋 Détails des bornes synchronisées:');
            $tableData = [];
            foreach ($stats['details'] as $detail) {
                if ($detail['result']['success'] && $detail['result']['synced']) {
                    $tableData[] = [
                        $detail['charging_point_id'],
                        $detail['name'],
                        '✅ Synchronisée'
                    ];
                }
            }
            if (count($tableData) > 0) {
                $this->table(['ID', 'Nom', 'Résultat'], $tableData);
            }
        }

        if ($stats['failed'] > 0) {
            $this->newLine();
            $this->error('⚠️  Échecs de synchronisation:');
            $tableData = [];
            foreach ($stats['details'] as $detail) {
                if (!$detail['result']['success']) {
                    $tableData[] = [
                        $detail['charging_point_id'],
                        $detail['name'],
                        $detail['result']['error'] ?? 'Unknown'
                    ];
                }
            }
            if (count($tableData) > 0) {
                $this->table(['ID', 'Nom', 'Erreur'], $tableData);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Importer les bornes depuis Steve vers local
     */
    protected function importFromSteve(ChargingPointSteveSyncService $syncService, bool $updateExisting, bool $dryRun): int
    {
        $this->info('📥 Import: Steve API → Local');
        $this->newLine();

        if ($dryRun) {
            $this->warn('Mode dry-run pas encore implémenté pour l\'import');
            return self::SUCCESS;
        }

        $result = $syncService->importFromSteve($updateExisting);

        if (!$result['success']) {
            $this->error('❌ Échec de l\'import');
            $this->error($result['error'] ?? 'Erreur inconnue');
            return self::FAILURE;
        }

        $stats = $result['results'];
        $this->info("✅ Import terminé:");
        $this->line("   - Total sur Steve: {$stats['total']}");
        $this->line("   - Créées localement: {$stats['created']}");
        $this->line("   - Mises à jour: {$stats['updated']}");
        $this->line("   - Ignorées: {$stats['skipped']}");
        $this->line("   - Échecs: {$stats['failed']}");

        return self::SUCCESS;
    }

    /**
     * Synchroniser une borne spécifique
     */
    protected function syncSpecificChargingPoint(
        ChargingPointSteveSyncService $syncService, 
        int $id, 
        string $direction,
        bool $dryRun
    ): int {
        $chargingPoint = ChargingPoint::find($id);

        if (!$chargingPoint) {
            $this->error("❌ Borne #{$id} introuvable");
            return self::FAILURE;
        }

        $this->info("🔍 Synchronisation de la borne: {$chargingPoint->name} (ID: {$id})");
        $this->newLine();

        if ($dryRun) {
            $status = $syncService->checkSyncStatus($chargingPoint);
            
            $this->table(
                ['Propriété', 'Valeur'],
                [
                    ['Steve ID', $status['steve_charging_point_id'] ?? 'N/A'],
                    ['Steve PK', $status['steve_charge_box_pk'] ?? 'N/A'],
                    ['Statut sync', $status['sync_status'] ?? 'N/A'],
                    ['Dernière sync', $status['last_synced_at'] ?? 'Jamais'],
                    ['Existe sur Steve', $status['exists_on_steve'] ? 'Oui' : 'Non'],
                    ['Peut synchroniser', $status['can_sync'] ? 'Oui' : 'Non']
                ]
            );

            return self::SUCCESS;
        }

        // Synchronisation réelle
        switch ($direction) {
            case 'export':
                if (!empty($chargingPoint->steve_charging_point_id)) {
                    $result = $syncService->syncUpdateToSteve($chargingPoint);
                } else {
                    $result = $syncService->syncCreateToSteve($chargingPoint, true);
                }
                break;
            
            default:
                $this->error("❌ Direction '{$direction}' non supportée pour une borne spécifique");
                return self::FAILURE;
        }

        if ($result['success']) {
            $this->info('✅ Synchronisation réussie');
            $this->line($result['message'] ?? 'Borne synchronisée');
        } else {
            $this->error('❌ Échec de la synchronisation');
            $this->error($result['message'] ?? $result['error'] ?? 'Erreur inconnue');
        }

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }
}
