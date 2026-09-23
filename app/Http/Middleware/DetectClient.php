<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Client;
use Illuminate\Support\Facades\Cache;

class DetectClient
{
    private static ?Client $currentClient = null;

    public function handle(Request $request, Closure $next)
    {
        // Détecter le client basé sur le domaine
        $this->detectClient($request);
        
        return $next($request);
    }

    /**
     * Détecter le client basé sur le domaine de la requête
     */
    private function detectClient(Request $request): void
    {
        $host = $request->getHost();
        
        // Essayer de trouver un client par domaine
        $client = Cache::remember("client_domain_{$host}", 3600, function () use ($host) {
            return \App\Models\Client::where('domain', $host)
                        ->orWhere('subdomain', $host)
                        ->active()
                        ->first();
        });

        // Si aucun client trouvé, utiliser le client par défaut
        if (!$client) {
            $client = Cache::remember('default_client', 3600, function () {
                return \App\Models\Client::active()->first();
            });
        }

        self::$currentClient = $client;
    }

    /**
     * Obtenir le client actuel
     */
    public static function getCurrentClient(): ?Client
    {
        return self::$currentClient;
    }

    /**
     * Obtenir l'ID du client actuel
     */
    public static function getCurrentClientId(): ?int
    {
        return self::$currentClient?->id;
    }

    /**
     * Vérifier si un client est actuellement détecté
     */
    public static function hasCurrentClient(): bool
    {
        return self::$currentClient !== null;
    }

    /**
     * Obtenir la configuration de marque complète
     */
    public static function getCurrentClientBrandConfig(): array
    {
        return self::$currentClient?->getBrandConfig() ?? self::getDefaultBrandConfig();
    }

    /**
     * Obtenir une valeur spécifique de la configuration de marque
     */
    public static function getCurrentClientBrandValue(string $key, $default = null)
    {
        $config = self::getCurrentClientBrandConfig();
        return $config[$key] ?? $default;
    }

    /**
     * Obtenir le nom du client actuel
     */
    public static function getCurrentClientName(): string
    {
        return self::$currentClient?->name ?? 'EVON POWER';
    }

    /**
     * Obtenir le nom d'affichage du client actuel
     */
    public static function getCurrentClientDisplayName(): string
    {
        return self::$currentClient?->display_name ?? 'EVON POWER';
    }

    /**
     * Obtenir le logo du client actuel
     */
    public static function getCurrentClientLogo(): string
    {
        return self::$currentClient?->logo_url ?? asset('images/logo.png');
    }

    /**
     * Obtenir la couleur primaire du client actuel
     */
    public static function getCurrentClientPrimaryColor(): string
    {
        return self::$currentClient?->primary_color ?? '#3B82F6';
    }

    /**
     * Obtenir la couleur secondaire du client actuel
     */
    public static function getCurrentClientSecondaryColor(): string
    {
        return self::$currentClient?->secondary_color ?? '#1E40AF';
    }

    /**
     * Obtenir la langue du client actuel
     */
    public static function getCurrentClientLanguage(): string
    {
        return self::$currentClient?->language ?? 'fr';
    }

    /**
     * Obtenir la devise du client actuel
     */
    public static function getCurrentClientCurrency(): string
    {
        return self::$currentClient?->currency ?? 'EUR';
    }

    /**
     * Obtenir le symbole de la devise du client actuel
     */
    public static function getCurrentClientCurrencySymbol(): string
    {
        return self::$currentClient?->currency_symbol ?? 'DH';
    }

    /**
     * Configuration de marque par défaut
     */
    private static function getDefaultBrandConfig(): array
    {
        return [
            'name' => 'EVON POWER',
            'display_name' => 'EVON POWER',
            'logo' => asset('images/logo.png'),
            'logo_dark' => asset('images/logo.png'),
            'favicon' => asset('images/favicon.ico'),
            'hero_image' => asset('images/hero-image.jpg'),
            'primary_color' => '#1E40AF',
            'secondary_color' => '#3B82F6',
            'accent_color' => '#F59E0B',
            'success_color' => '#10B981',
            'warning_color' => '#F59E0B',
            'error_color' => '#EF4444',
            'info_color' => '#3B82F6',
            'background_primary' => '#FFFFFF',
            'background_secondary' => '#F8FAFC',
            'background_dark' => '#0F172A',
            'text_primary' => '#0F172A',
            'text_secondary' => '#64748B',
            'text_light' => '#94A3B8',
            'contact_email' => 'contact@evonpower.com',
            'contact_phone' => '+212 5XX XXX XXX',
            'contact_address' => 'Maroc',
            'support_email' => 'support@evonpower.com',
            'language' => 'fr',
            'timezone' => 'Africa/Casablanca',
            'currency' => 'EUR',
            'currency_symbol' => '€',
        ];
    }
}