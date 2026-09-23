<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AdminSetting;

class DisableCmiPayment extends Command
{
    protected $signature = 'cmi:disable';
    protected $description = 'Désactiver les paiements CMI';

    public function handle()
    {
        $this->info('=== Désactivation de CMI ===');
        $this->newLine();
        
        // Désactiver CMI
        $setting = AdminSetting::firstOrNew([
            'category' => 'external_apis',
            'key' => 'cmi_enabled'
        ]);
        
        $setting->value = '0';
        $setting->type = 'boolean';
        $setting->description = 'Activer ou désactiver les paiements CMI';
        $setting->is_active = true;
        $setting->save();
        
        $this->info('✅ CMI désactivé avec succès');
        $this->newLine();
        $this->info('Les paiements CMI sont maintenant désactivés.');
        $this->info('Pour réactiver : php artisan cmi:enable');
        
        return 0;
    }
}

