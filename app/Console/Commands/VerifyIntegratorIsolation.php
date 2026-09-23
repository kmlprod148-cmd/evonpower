<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\IntegratorIsolationService;
use App\Models\User;

class VerifyIntegratorIsolation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integrator:verify-isolation 
                            {--integrator= : ID de l\'intégrateur spécifique à vérifier}
                            {--enforce : Forcer l\'isolation si des problèmes sont détectés}
                            {--global : Vérifier l\'isolation de tous les intégrateurs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifier l\'isolation des intégrateurs pour s\'assurer qu\'ils ne peuvent pas accéder aux données d\'autres intégrateurs';

    protected IntegratorIsolationService $isolationService;

    public function __construct(IntegratorIsolationService $isolationService)
    {
        parent::__construct();
        $this->isolationService = $isolationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔒 Vérification de l\'isolation des intégrateurs...');
        $this->newLine();

        if ($this->option('global')) {
            $this->verifyGlobalIsolation();
        } elseif ($this->option('integrator')) {
            $this->verifySpecificIntegrator();
        } else {
            $this->error('Veuillez spécifier --integrator=ID ou --global');
            return 1;
        }

        return 0;
    }

    /**
     * Vérifier l'isolation globale
     */
    private function verifyGlobalIsolation()
    {
        $this->info('🌍 Vérification globale de l\'isolation...');
        
        $results = $this->isolationService->verifyGlobalIsolation();
        
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Total intégrateurs', $results['total_integrators']],
                ['Intégrateurs isolés', $results['isolated_integrators']],
                ['Intégrateurs non isolés', $results['non_isolated_integrators']],
                ['Total erreurs', $results['total_errors']],
            ]
        );

        if ($results['non_isolated_integrators'] > 0) {
            $this->error("❌ {$results['non_isolated_integrators']} intégrateurs ne sont pas correctement isolés !");
            
            foreach ($results['results'] as $result) {
                if (!$result['is_isolated']) {
                    $this->warn("Intégrateur {$result['integrator_name']} (ID: {$result['integrator_id']}):");
                    foreach ($result['errors'] as $error) {
                        $this->line("  - {$error}");
                    }
                }
            }

            if ($this->option('enforce')) {
                $this->enforceIsolation($results['results']);
            }
        } else {
            $this->info('✅ Tous les intégrateurs sont correctement isolés !');
        }
    }

    /**
     * Vérifier un intégrateur spécifique
     */
    private function verifySpecificIntegrator()
    {
        $integratorId = $this->option('integrator');
        $integrator = User::find($integratorId);

        if (!$integrator) {
            $this->error("Intégrateur avec l'ID {$integratorId} non trouvé.");
            return;
        }

        if (!$integrator->hasRole('integrator')) {
            $this->error("L'utilisateur {$integrator->name} n'est pas un intégrateur.");
            return;
        }

        $this->info("🔍 Vérification de l'intégrateur: {$integrator->name} (ID: {$integratorId})");
        
        $verification = $this->isolationService->verifyIntegratorIsolation($integrator);
        
        if ($verification['is_isolated']) {
            $this->info('✅ L\'intégrateur est correctement isolé !');
        } else {
            $this->error('❌ L\'intégrateur n\'est pas correctement isolé !');
            $this->warn('Erreurs détectées:');
            foreach ($verification['errors'] as $error) {
                $this->line("  - {$error}");
            }

            if ($this->option('enforce')) {
                $this->enforceSpecificIntegrator($integrator);
            }
        }
    }

    /**
     * Forcer l'isolation pour tous les intégrateurs problématiques
     */
    private function enforceIsolation(array $results)
    {
        $this->warn('🔧 Application de l\'isolation forcée...');
        
        foreach ($results as $result) {
            if (!$result['is_isolated']) {
                $integrator = User::find($result['integrator_id']);
                if ($integrator) {
                    $this->enforceSpecificIntegrator($integrator);
                }
            }
        }
    }

    /**
     * Forcer l'isolation pour un intégrateur spécifique
     */
    private function enforceSpecificIntegrator(User $integrator)
    {
        $this->info("🔧 Application de l'isolation forcée pour {$integrator->name}...");
        
        $result = $this->isolationService->enforceIntegratorIsolation($integrator);
        
        if ($result['success']) {
            $this->info("✅ Isolation forcée réussie pour {$integrator->name}");
            if (!empty($result['actions'])) {
                $this->line('Actions effectuées:');
                foreach ($result['actions'] as $action) {
                    $this->line("  - {$action}");
                }
            }
        } else {
            $this->error("❌ Échec de l'isolation forcée: {$result['message']}");
        }
    }
}
