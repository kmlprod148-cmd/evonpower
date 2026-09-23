<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CreateBrand extends Command
{
    /**
     * Le nom et la signature de la commande console.
     *
     * @var string
     */
    protected $signature = 'brand:create 
                            {name : Le nom de la marque}
                            {--domain= : Le domaine principal}
                            {--subdomain= : Le sous-domaine}
                            {--primary-color=#10B981 : La couleur primaire}
                            {--secondary-color=#047857 : La couleur secondaire}
                            {--language=fr : La langue par défaut (fr, en, ar)}
                            {--currency=EUR : La devise par défaut}
                            {--commission-rate=0.15 : Le taux de commission par défaut}
                            {--charging-rate=0.25 : Le taux de recharge par défaut}
                            {--payment-gateway=stripe : La passerelle de paiement}
                            {--logo= : Le chemin vers le logo}
                            {--favicon= : Le chemin vers le favicon}
                            {--create-env : Créer un fichier .env personnalisé}
                            {--create-storage : Créer la structure de stockage}';

    /**
     * La description de la commande console.
     *
     * @var string
     */
    protected $description = 'Créer une nouvelle marque avec sa configuration personnalisée';

    /**
     * Exécuter la commande console.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $domain = $this->option('domain');
        $subdomain = $this->option('subdomain');
        
        $this->info("Création de la marque : {$name}");
        
        // Validation des paramètres
        if (!$domain && !$subdomain) {
            $this->error('Vous devez spécifier soit un domaine soit un sous-domaine.');
            return 1;
        }
        
        // Vérifier si la marque existe déjà
        if (Client::where('name', $name)->exists()) {
            $this->error("Une marque avec le nom '{$name}' existe déjà.");
            return 1;
        }
        
        if ($domain && Client::where('domain', $domain)->exists()) {
            $this->error("Une marque avec le domaine '{$domain}' existe déjà.");
            return 1;
        }
        
        if ($subdomain && Client::where('subdomain', $subdomain)->exists()) {
            $this->error("Une marque avec le sous-domaine '{$subdomain}' existe déjà.");
            return 1;
        }
        
        try {
            // Créer le client
            $client = Client::create([
                'name' => $name,
                'display_name' => $name,
                'slug' => Str::slug($name),
                'domain' => $domain,
                'subdomain' => $subdomain,
                
                // Couleurs
                'primary_color' => $this->option('primary-color'),
                'secondary_color' => $this->option('secondary-color'),
                'accent_color' => '#F59E0B',
                'success_color' => '#10B981',
                'warning_color' => '#F59E0B',
                'error_color' => '#EF4444',
                'info_color' => '#3B82F6',
                
                // Configuration
                'language' => $this->option('language'),
                'currency' => $this->option('currency'),
                'default_commission_rate' => $this->option('commission-rate'),
                'default_charging_rate' => $this->option('charging-rate'),
                'payment_gateway' => $this->option('payment-gateway'),
                
                // Informations de contact
                'contact_email' => "contact@{$name}.com",
                'support_email' => "support@{$name}.com",
                'company_name' => "{$name} SAS",
                
                // Autres paramètres par défaut
                'theme' => 'light',
                'timezone' => 'Europe/Paris',
                'currency_symbol' => $this->getCurrencySymbol($this->option('currency')),
                'enable_qr_codes' => true,
                'enable_notifications' => true,
                'enable_analytics' => true,
                'enable_maintenance_mode' => false,
                'enable_smart_charging' => true,
                'max_charging_power' => 22.00,
                'activation_fee' => 0.00,
                'email_from_name' => $name,
                'email_from_address' => "noreply@{$name}.com",
                'email_signature' => "L'équipe {$name}",
                'notification_channels' => [
                    'mail' => true,
                    'sms' => false,
                    'push' => false,
                ],
                'session_lifetime' => 120,
                'max_login_attempts' => 5,
                'password_expiry_days' => 90,
                'meta_title' => "{$name} - Solutions de recharge intelligentes",
                'meta_description' => "Découvrez nos solutions de recharge électrique innovantes",
                'meta_keywords' => 'recharge électrique, bornes, voiture électrique',
                'meta_author' => $name,
                'cookie_consent_required' => true,
                'cookie_analytics' => true,
                'cookie_marketing' => false,
                'report_frequency' => 'weekly',
                'report_retention_days' => 365,
                'enable_auto_reports' => true,
                'backup_frequency' => 'daily',
                'backup_retention_days' => 30,
                'backup_storage' => 'local',
                'is_active' => true,
                'trial_ends_at' => now()->addDays(30),
                'subscription_ends_at' => null,
            ]);
            
            $this->info("✅ Client créé avec succès (ID: {$client->id})");
            
            // Créer la structure de stockage
            if ($this->option('create-storage')) {
                $this->createStorageStructure($client);
            }
            
            // Copier les logos si spécifiés
            if ($this->option('logo')) {
                $this->copyLogo($client, $this->option('logo'));
            }
            
            if ($this->option('favicon')) {
                $this->copyFavicon($client, $this->option('favicon'));
            }
            
            // Créer le fichier .env personnalisé
            if ($this->option('create-env')) {
                $this->createEnvFile($client);
            }
            
            // Afficher les informations de la marque
            $this->displayBrandInfo($client);
            
            $this->info("🎉 Marque '{$name}' créée avec succès !");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Erreur lors de la création de la marque : " . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Obtenir le symbole de la devise.
     */
    protected function getCurrencySymbol(string $currency): string
    {
        $symbols = [
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'CHF' => 'CHF',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥',
            'CNY' => '¥',
            'INR' => '₹',
            'BRL' => 'R$',
        ];
        
        return $symbols[$currency] ?? $currency;
    }
}
