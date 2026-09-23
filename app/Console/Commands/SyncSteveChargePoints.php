<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\SteveService;
use App\Models\ChargingPoint;

class SyncSteveChargePoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'steve:sync-charge-points';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize charge points from the Steve API into the local database';

    /**
     * Execute the console command.
     */
    public function handle(SteveService $steve)
    {
        $this->info('🔄 Synchronisation des points de charge depuis l\'API Steve...');

        // Étape 1: Récupérer les points de charge depuis l'API
        $chargePoints = $steve->getChargePoints();

        if (!$chargePoints || !is_array($chargePoints)) {
            $this->error('❌ Échec de la récupération des points de charge depuis l\'API Steve.');
            return Command::FAILURE;
        }

        $this->info('✅ ' . count($chargePoints) . ' point(s) de charge récupéré(s).');

        // Étape 2: Parcourir et synchroniser
        $created = 0;
        $updated = 0;
        $errors = 0;

        DB::beginTransaction();
        try {
            foreach ($chargePoints as $cp) {
                try {
                    // Les champs disponibles dans la réponse de l'API Steve
                    // Exemple: ["id" => 1, "chargeBoxId" => "CP-001", "status" => "Available", "endpointAddress" => "192.168.1.10"]
                    $data = [
                        'steve_charging_point_id' => $cp['id'] ?? $cp['chargePointId'] ?? null,
                        'name' => $cp['chargeBoxId'] ?? $cp['name'] ?? null,
                        'ip_address' => $cp['endpointAddress'] ?? $cp['ipAddress'] ?? null,
                        'status' => $this->mapStatus($cp['status'] ?? 'Unknown'),
                        'notes' => $cp['notes'] ?? null,
                    ];

                    // Ignorer si pas d'ID Steve
                    if (empty($data['steve_charging_point_id'])) {
                        $this->warn('⚠️  Point de charge ignoré (pas d\'ID Steve): ' . json_encode($cp));
                        $errors++;
                        continue;
                    }

                    // Mettre à jour ou créer
                    $chargingPoint = ChargingPoint::updateOrCreate(
                        ['steve_charging_point_id' => $data['steve_charging_point_id']],
                        $data
                    );

                    if ($chargingPoint->wasRecentlyCreated) {
                        $created++;
                        $this->line("  ✓ Créé: {$data['name']} (ID Steve: {$data['steve_charging_point_id']})");
                    } else {
                        $updated++;
                        $this->line("  ↻ Mis à jour: {$data['name']} (ID Steve: {$data['steve_charging_point_id']})");
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error("  ✗ Erreur pour le point de charge: " . $e->getMessage());
                    Log::error('Erreur lors de la synchronisation d\'un point de charge', [
                        'charge_point_data' => $cp,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('❌ Erreur lors de la synchronisation: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info("✅ Synchronisation terminée ! Créés: {$created}, Mis à jour: {$updated}, Erreurs: {$errors}");
        return Command::SUCCESS;
    }

    /**
     * Convertir les statuts Steve vers notre système local.
     */
    protected function mapStatus(string $status): string
    {
        $status = strtolower(trim($status));
        return match ($status) {
            'available', 'charging', 'occupied', 'ready', 'online' => 'online',
            'unavailable', 'faulted', 'disconnected', 'offline' => 'offline',
            'maintenance' => 'maintenance',
            'error', 'faulted' => 'error',
            'reserved' => 'reserved',
            default => 'unknown',
        };
    }
}
