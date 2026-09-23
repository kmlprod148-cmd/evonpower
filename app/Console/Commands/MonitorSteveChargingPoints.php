<?php

namespace App\Console\Commands;

use App\Models\ChargingPoint;
use App\Services\ChargingPointStatusService;
use App\Services\SteveService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorSteveChargingPoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:steve-charging-points 
                            {--limit=50 : Nombre maximum de bornes à vérifier}
                            {--batch=10 : Taille du batch pour traiter les bornes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Met à jour le statut des bornes de recharge depuis l\'API Steve';

    /**
     * Execute the console command.
     */
    public function handle(SteveService $steve, ChargingPointStatusService $statusService)
    {
        $this->info('Démarrage du monitoring des bornes Steve...');
        
        $limit = (int) $this->option('limit');
        $batchSize = (int) $this->option('batch');

        $points = ChargingPoint::whereNotNull('steve_charging_point_id')
            ->limit($limit)
            ->get();

        if ($points->isEmpty()) {
            $this->warn('Aucune borne avec steve_charging_point_id trouvée.');
            return 0;
        }

        $this->info("Traitement de {$points->count()} borne(s)...");

        $bar = $this->output->createProgressBar($points->count());
        $bar->start();

        $updated = 0;
        $failed = 0;

        foreach ($points->chunk($batchSize) as $chunk) {
            foreach ($chunk as $p) {
                try {
                    $newStatus = null;
                    $statusResult = $statusService->getStatusFromConnectors($p, false);
                    if ($statusResult['success'] ?? false) {
                        $newStatus = $statusResult['status'];
                    }

                    if ($newStatus === null) {
                        $res = $steve->getConnectorStatus($p->steve_charging_point_id);
                        $online = false;

                        if ($res['ok'] && is_array($res['body'])) {
                            $online = $this->deriveOnlineFromConnectorBody($res['body']);
                        } else {
                            // Fallback: get charging point details
                            $detail = $steve->getChargingPoint($p->steve_charging_point_id);
                            if ($detail['ok'] && is_array($detail['body'])) {
                                $online = $this->deriveOnlineFromChargingPointBody($detail['body']);
                            } else {
                            // Si l'API échoue, garder le statut actuel
                                $bar->advance();
                                $failed++;
                                continue;
                            }
                        }

                    $newStatus = $online ? 'online' : 'offline';
                    }
                    
                    // Ne mettre à jour que si le statut a changé
                    if ($p->status !== $newStatus) {
                        $p->status = $newStatus;
                        $p->status_updated_at = now();
                        $p->saveQuietly();
                        $updated++;
                    } else {
                        // Mettre à jour juste le timestamp
                        $p->status_updated_at = now();
                        $p->saveQuietly();
                    }
                } catch (\Throwable $e) {
                    Log::error("MonitorSteveChargingPoints: Erreur pour CP {$p->id}: {$e->getMessage()}");
                    $failed++;
                }

                $bar->advance();
            }

            // Petite pause entre les batches pour éviter de surcharger l'API
            if ($batchSize < $points->count()) {
                usleep(500000); // 0.5 secondes
            }
        }

        $bar->finish();
        $this->newLine();

        $this->info("Terminé: {$updated} mise(s) à jour, {$failed} erreur(s)");
        
        return 0;
    }

    /**
     * Derive online status from connector response body
     */
    protected function deriveOnlineFromConnectorBody(array $body): bool
    {
        if (isset($body['connectors']) && is_array($body['connectors'])) {
            foreach ($body['connectors'] as $c) {
                $s = strtoupper($c['status'] ?? '');
                if (in_array($s, ['AVAILABLE', 'CHARGING', 'IN_USE', 'OCCUPIED', 'ACTIVE', 'ONLINE'])) {
                    return true;
                }
            }
        }
        
        if (isset($body['status'])) {
            $s = strtoupper($body['status']);
            if (in_array($s, ['ONLINE', 'CONNECTED', 'ACTIVE', 'AVAILABLE'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Derive online status from charging point details response body
     */
    protected function deriveOnlineFromChargingPointBody($body): bool
    {
        if (!is_array($body)) {
            return false;
        }

        $s = strtoupper($body['status'] ?? ($body['availability'] ?? ''));
        if (in_array($s, ['ONLINE', 'CONNECTED', 'ACTIVE', 'AVAILABLE'])) {
            return true;
        }
        
        if (!empty($body['lastSeen']) || !empty($body['last_seen'])) {
            try {
                $lastSeen = \Carbon\Carbon::parse($body['lastSeen'] ?? $body['last_seen']);
                if ($lastSeen->gt(now()->subMinutes(5))) {
                    return true;
                }
            } catch (\Exception $e) {
                // Ignore parse errors
            }
        }

        if (isset($body['connected']) && $body['connected'] === true) {
            return true;
        }

        return false;
    }
}
