<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AdminSetting;
use App\Services\PaymentKeysService;

class CmiStatus extends Command
{
    protected $signature = 'cmi:status';
    protected $description = 'Vérifier l\'état de CMI';

    public function handle()
    {
        $this->info('=== État de CMI ===');
        $this->newLine();
        
        // Vérifier l'état d'activation
        $setting = AdminSetting::where('category', 'external_apis')
            ->where('key', 'cmi_enabled')
            ->first();
        
        $isEnabled = $setting && ($setting->value === '1' || $setting->value === 'true');
        
        if ($isEnabled) {
            $this->info('✅ CMI est ACTIVÉ');
        } else {
            $this->warn('❌ CMI est DÉSACTIVÉ');
        }
        
        $this->newLine();
        
        // Vérifier les clés
        $paymentKeysService = new PaymentKeysService();
        $activeKeys = $paymentKeysService->getActiveCmiKeys();
        
        $this->info('Configuration des clés :');
        $this->line('  Environnement : ' . ($activeKeys['environment'] ?? 'non défini'));
        $this->line('  Client ID : ' . (!empty($activeKeys['clientid']) ? '✅ Configuré' : '❌ NON CONFIGURÉ'));
        $this->line('  Store Key : ' . (!empty($activeKeys['storekey']) ? '✅ Configuré' : '❌ NON CONFIGURÉ'));
        $this->line('  URL Callback : ' . (!empty($activeKeys['callback_url']) ? '✅ Configurée' : '❌ NON CONFIGURÉE'));
        $this->line('  API URL : ' . ($activeKeys['api_url'] ?? 'non défini'));
        
        $this->newLine();
        
        if ($isEnabled) {
            $missing = [];
            if (empty($activeKeys['storekey'])) {
                $missing[] = 'Clé API (Store Key)';
            }
            if (empty($activeKeys['clientid'])) {
                $missing[] = 'ID Marchand (Client ID)';
            }
            if (empty($activeKeys['callback_url'])) {
                $missing[] = 'URL de Callback';
            }
            
            if (!empty($missing)) {
                $this->warn('⚠️  CMI est activé mais les clés suivantes manquent :');
                foreach ($missing as $key) {
                    $this->line("  - {$key}");
                }
                $this->newLine();
                $this->info('Les paiements CMI échoueront jusqu\'à ce que les clés soient configurées.');
                $this->info('Pour configurer : Interface admin > Paramètres > API CMI');
            } else {
                $this->info('✅ Toutes les clés sont configurées. CMI est prêt à être utilisé.');
            }
        } else {
            $this->info('Pour activer CMI : php artisan cmi:enable');
        }
        
        return 0;
    }
}

