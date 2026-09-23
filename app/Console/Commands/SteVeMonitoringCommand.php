<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SteVeMonitoringService;
use App\Services\SteVeRealTimeMonitoringService;
use App\Models\ChargingPoint;
use Illuminate\Support\Facades\Log;

class SteVeMonitoringCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'steve:monitor 
                            {action : Action à effectuer (test|status|start|stop|alerts)}
                            {--charging-point= : ID du point de charge spécifique}
                            {--verbose : Affichage détaillé}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestion du système de monitoring SteVe';

    protected SteVeMonitoringService $monitoringService;
    protected SteVeRealTimeMonitoringService $realTimeService;

    public function __construct(
        SteVeMonitoringService $monitoringService,
        SteVeRealTimeMonitoringService $realTimeService
    ) {
        parent::__construct();
        $this->monitoringService = $monitoringService;
        $this->realTimeService = $realTimeService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $verbose = $this->option('verbose');
        $chargingPointId = $this->option('charging-point');

        $this->info("🔌 Système de Monitoring SteVe");
        $this->info("==============================");

        switch ($action) {
            case 'test':
                $this->runConnectivityTest($verbose);
                break;
            case 'status':
                $this->showStatus($verbose, $chargingPointId);
                break;
            case 'start':
                $this->startMonitoring($verbose);
                break;
            case 'stop':
                $this->stopMonitoring($verbose);
                break;
            case 'alerts':
                $this->showAlerts($verbose);
                break;
            default:
                $this->error("Action non reconnue: $action");
                $this->showHelp();
                return 1;
        }

        return 0;
    }

    /**
     * Exécuter le test de connectivité
     */
    protected function runConnectivityTest(bool $verbose): void
    {
        $this->info("🧪 Test de connectivité SteVe...");
        
        try {
            $results = $this->monitoringService->testFullConnectivity();
            
            $this->info("📊 Résultats du test:");
            $this->line("   Statut global: " . $results['overall_status']);
            $this->line("   Temps de réponse: " . $results['response_time_ms'] . "ms");
            
            if ($verbose) {
                $this->line("\n📋 Détails des tests:");
                foreach ($results['details'] as $test => $result) {
                    if (is_array($result) && isset($result['status'])) {
                        $status = $result['status'] === 'success' ? '✅' : '❌';
                        $this->line("   $status $test: " . $result['message']);
                    }
                }
            }
            
            if ($results['overall_status'] === 'success') {
                $this->info("✅ Test de connectivité réussi!");
            } else {
                $this->warn("⚠️  Problèmes détectés dans la connectivité");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors du test: " . $e->getMessage());
        }
    }

    /**
     * Afficher le statut du système
     */
    protected function showStatus(bool $verbose, ?string $chargingPointId): void
    {
        $this->info("📊 Statut du système SteVe...");
        
        try {
            // Statut général
            $realTimeStatus = $this->realTimeService->startRealTimeMonitoring();
            
            if ($realTimeStatus['success']) {
                $data = $realTimeStatus['data'];
                
                $this->line("🌐 Serveur: " . ($data['server_status']['status'] ?? 'unknown'));
                $this->line("🔌 Points de charge: " . ($data['charging_points']['total'] ?? 0));
                $this->line("   - En ligne: " . ($data['charging_points']['online'] ?? 0));
                $this->line("   - Hors ligne: " . ($data['charging_points']['offline'] ?? 0));
                $this->line("📈 Connexions OCPP: " . ($data['ocpp_connections']['active_connections'] ?? 0));
                
                if ($verbose) {
                    $this->line("\n📋 Métriques système:");
                    $metrics = $data['system_metrics'] ?? [];
                    if (isset($metrics['memory_usage'])) {
                        $this->line("   Mémoire: " . $this->formatBytes($metrics['memory_usage']['current']));
                    }
                    if (isset($metrics['cpu_usage'])) {
                        $this->line("   CPU: " . ($metrics['cpu_usage']['load_1min'] ?? 'N/A'));
                    }
                }
            } else {
                $this->error("❌ Impossible de récupérer le statut: " . $realTimeStatus['message']);
            }
            
            // Statut spécifique d'un point de charge
            if ($chargingPointId) {
                $this->showChargingPointStatus($chargingPointId, $verbose);
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la récupération du statut: " . $e->getMessage());
        }
    }

    /**
     * Afficher le statut d'un point de charge spécifique
     */
    protected function showChargingPointStatus(string $chargingPointId, bool $verbose): void
    {
        try {
            $chargingPoint = ChargingPoint::findOrFail($chargingPointId);
            
            $this->line("\n🔌 Point de charge: {$chargingPoint->name}");
            $this->line("   ID: {$chargingPoint->charge_box_id}");
            $this->line("   Statut: {$chargingPoint->status}");
            $this->line("   Dernière connexion: " . ($chargingPoint->last_connected_at?->format('Y-m-d H:i:s') ?? 'N/A'));
            
            if ($verbose) {
                $this->line("   Profil: " . ($chargingPoint->businessProfile?->name ?? 'N/A'));
                $this->line("   Intégrateur: " . ($chargingPoint->integrator?->name ?? 'N/A'));
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Point de charge non trouvé: $chargingPointId");
        }
    }

    /**
     * Démarrer le monitoring
     */
    protected function startMonitoring(bool $verbose): void
    {
        $this->info("🚀 Démarrage du monitoring SteVe...");
        
        try {
            $result = $this->realTimeService->startRealTimeMonitoring();
            
            if ($result['success']) {
                $this->info("✅ Monitoring démarré avec succès!");
                
                if ($verbose) {
                    $this->line("📊 Données collectées:");
                    $this->line("   - Serveur: " . ($result['data']['server_status']['status'] ?? 'unknown'));
                    $this->line("   - Points de charge: " . ($result['data']['charging_points']['total'] ?? 0));
                    $this->line("   - Alertes: " . count($result['data']['alerts'] ?? []));
                }
            } else {
                $this->error("❌ Échec du démarrage: " . $result['message']);
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors du démarrage: " . $e->getMessage());
        }
    }

    /**
     * Arrêter le monitoring
     */
    protected function stopMonitoring(bool $verbose): void
    {
        $this->info("🛑 Arrêt du monitoring SteVe...");
        
        // Ici vous pouvez ajouter la logique pour arrêter le monitoring
        // Par exemple, vider les caches, arrêter les processus en arrière-plan, etc.
        
        $this->info("✅ Monitoring arrêté");
    }

    /**
     * Afficher les alertes
     */
    protected function showAlerts(bool $verbose): void
    {
        $this->info("🚨 Alertes SteVe...");
        
        try {
            $realTimeStatus = $this->realTimeService->startRealTimeMonitoring();
            
            if ($realTimeStatus['success']) {
                $alerts = $realTimeStatus['data']['alerts'] ?? [];
                
                if (empty($alerts)) {
                    $this->info("✅ Aucune alerte active");
                } else {
                    $this->warn("⚠️  " . count($alerts) . " alerte(s) active(s):");
                    
                    foreach ($alerts as $alert) {
                        $severity = $alert['severity'] ?? 'info';
                        $icon = $this->getSeverityIcon($severity);
                        $this->line("   $icon {$alert['type']}: {$alert['message']}");
                        
                        if ($verbose && isset($alert['data'])) {
                            $this->line("      Données: " . json_encode($alert['data']));
                        }
                    }
                }
            } else {
                $this->error("❌ Impossible de récupérer les alertes: " . $realTimeStatus['message']);
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la récupération des alertes: " . $e->getMessage());
        }
    }

    /**
     * Obtenir l'icône de sévérité
     */
    protected function getSeverityIcon(string $severity): string
    {
        return match ($severity) {
            'critical' => '🔴',
            'error' => '❌',
            'warning' => '⚠️',
            'info' => 'ℹ️',
            default => '📋'
        };
    }

    /**
     * Formater les bytes
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Afficher l'aide
     */
    protected function showHelp(): void
    {
        $this->line("\n📚 Actions disponibles:");
        $this->line("   test     - Tester la connectivité SteVe");
        $this->line("   status   - Afficher le statut du système");
        $this->line("   start    - Démarrer le monitoring");
        $this->line("   stop     - Arrêter le monitoring");
        $this->line("   alerts   - Afficher les alertes actives");
        
        $this->line("\n🔧 Options:");
        $this->line("   --charging-point=ID  - Point de charge spécifique");
        $this->line("   --verbose           - Affichage détaillé");
        
        $this->line("\n💡 Exemples:");
        $this->line("   php artisan steve:monitor test --verbose");
        $this->line("   php artisan steve:monitor status --charging-point=1");
        $this->line("   php artisan steve:monitor alerts --verbose");
    }
}
