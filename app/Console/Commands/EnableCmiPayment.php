<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AdminSetting;
use App\Services\PaymentKeysService;

class EnableCmiPayment extends Command
{
    protected $signature = 'cmi:enable {--force : Forcer l\'activation même si les clés ne sont pas configurées}';
    protected $description = 'Activer les paiements CMI';

    public function handle()
    {
        $force = $this->option('force');
        
        $this->info('=== Activation de CMI ===');
        $this->newLine();
        
        // Vérifier les clés
        $paymentKeysService = new PaymentKeysService();
        $activeKeys = $paymentKeysService->getActiveCmiKeys();
        
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
        
        if (!empty($missing) && !$force) {
            $this->error('❌ Impossible d\'activer CMI : Clés manquantes');
            $this->newLine();
            $this->line('Clés manquantes :');
            foreach ($missing as $key) {
                $this->line("  - {$key}");
            }
            $this->newLine();
            $this->warn('Pour activer CMI malgré les clés manquantes, utilisez :');
            $this->line('  php artisan cmi:enable --force');
            $this->newLine();
            $this->info('Pour configurer les clés :');
            $this->line('  Interface admin > Paramètres > API CMI');
            return 1;
        }
        
        if (!empty($missing) && $force) {
            $this->warn('⚠️  Activation forcée malgré les clés manquantes :');
            foreach ($missing as $key) {
                $this->line("  - {$key}");
            }
            $this->newLine();
        }
        
        // Activer CMI
        $setting = AdminSetting::firstOrNew([
            'category' => 'external_apis',
            'key' => 'cmi_enabled'
        ]);
        
        $setting->value = '1';
        $setting->type = 'boolean';
        $setting->description = 'Activer ou désactiver les paiements CMI';
        $setting->is_active = true;
        $setting->save();
        
        $this->info('✅ CMI activé avec succès');
        $this->newLine();
        
        if (!empty($missing)) {
            $this->warn('⚠️  ATTENTION : Les clés suivantes ne sont pas configurées :');
            foreach ($missing as $key) {
                $this->line("  - {$key}");
            }
            $this->newLine();
            $this->info('Les paiements CMI échoueront jusqu\'à ce que les clés soient configurées.');
        }
        
        return 0;
    }
}

