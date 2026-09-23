<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'display_name',
        'slug',
        'domain',
        'subdomain',
        'logo',
        'logo_dark',
        'favicon',
        'hero_image',
        'primary_color',
        'secondary_color',
        'accent_color',
        'success_color',
        'warning_color',
        'error_color',
        'info_color',
        'background_primary',
        'background_secondary',
        'background_dark',
        'text_primary',
        'text_secondary',
        'text_light',
        'contact_email',
        'contact_phone',
        'contact_address',
        'support_email',
        'social_facebook',
        'social_twitter',
        'social_linkedin',
        'social_instagram',
        'company_name',
        'company_siret',
        'company_vat',
        'legal_notice',
        'terms_of_service',
        'privacy_policy',
        'theme',
        'language',
        'timezone',
        'currency',
        'currency_symbol',
        'enable_qr_codes',
        'enable_notifications',
        'enable_analytics',
        'enable_maintenance_mode',
        'payment_gateway',
        'default_commission_rate',
        'activation_fee',
        'default_charging_rate',
        'max_charging_power',
        'enable_smart_charging',
        'email_from_name',
        'email_from_address',
        'email_signature',
        'notification_channels',
        'session_lifetime',
        'max_login_attempts',
        'password_expiry_days',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'meta_author',
        'meta_image',
        'cookie_consent_required',
        'cookie_policy_url',
        'cookie_analytics',
        'cookie_marketing',
        'google_analytics_id',
        'facebook_pixel_id',
        'hotjar_id',
        'intercom_id',
        'webhook_url',
        'webhook_secret',
        'report_frequency',
        'report_retention_days',
        'enable_auto_reports',
        'backup_frequency',
        'backup_retention_days',
        'backup_storage',
        'is_active',
        'trial_ends_at',
        'subscription_ends_at',
    ];

    /**
     * Les attributs qui doivent être castés.
     *
     * @var array
     */
    protected $casts = [
        'notification_channels' => 'array',
        'enable_qr_codes' => 'boolean',
        'enable_notifications' => 'boolean',
        'enable_analytics' => 'boolean',
        'enable_maintenance_mode' => 'boolean',
        'enable_smart_charging' => 'boolean',
        'cookie_consent_required' => 'boolean',
        'cookie_analytics' => 'boolean',
        'cookie_marketing' => 'boolean',
        'enable_auto_reports' => 'boolean',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
        'default_commission_rate' => 'decimal:4',
        'activation_fee' => 'decimal:2',
        'default_charging_rate' => 'decimal:2',
        'max_charging_power' => 'decimal:2',
        'session_lifetime' => 'integer',
        'max_login_attempts' => 'integer',
        'password_expiry_days' => 'integer',
        'report_retention_days' => 'integer',
        'backup_retention_days' => 'integer',
    ];

    /**
     * Les attributs qui doivent être cachés lors de la sérialisation.
     *
     * @var array
     */
    protected $hidden = [
        'webhook_secret',
    ];

    /**
     * Obtenir l'URL complète du logo.
     */
    public function getLogoUrlAttribute()
    {
        if ($this->logo && Storage::disk('public')->exists("brands/{$this->id}/{$this->logo}")) {
            return Storage::disk('public')->url("brands/{$this->id}/{$this->logo}");
        }
        
        return asset('images/default-logo.png');
    }

    /**
     * Obtenir l'URL complète du logo sombre.
     */
    public function getLogoDarkUrlAttribute()
    {
        if ($this->logo_dark && Storage::disk('public')->exists("brands/{$this->id}/{$this->logo_dark}")) {
            return Storage::disk('public')->url("brands/{$this->id}/{$this->logo_dark}");
        }
        
        return $this->logo_url;
    }

    /**
     * Obtenir l'URL complète du favicon.
     */
    public function getFaviconUrlAttribute()
    {
        if ($this->favicon && Storage::disk('public')->exists("brands/{$this->id}/{$this->favicon}")) {
            $faviconUrl = Storage::disk('public')->url("brands/{$this->id}/{$this->favicon}");
            // Vérifier si le fichier existe et n'est pas vide
            $faviconPath = Storage::disk('public')->path("brands/{$this->id}/{$this->favicon}");
            if (file_exists($faviconPath) && filesize($faviconPath) > 0) {
                return $faviconUrl;
            }
        }
        
        // Fallback vers SVG si ICO n'existe pas ou est vide
        $icoPath = public_path('images/default-favicon.ico');
        if (file_exists($icoPath) && filesize($icoPath) > 0) {
            return asset('images/default-favicon.ico');
        }
        
        return asset('images/default-favicon.svg');
    }

    /**
     * Obtenir l'URL complète de l'image héro.
     */
    public function getHeroImageUrlAttribute()
    {
        if ($this->hero_image && Storage::disk('public')->exists("brands/{$this->id}/{$this->hero_image}")) {
            return Storage::disk('public')->url("brands/{$this->id}/{$this->hero_image}");
        }
        
        return asset('images/default-hero.jpg');
    }

    /**
     * Obtenir l'URL complète de l'image meta.
     */
    public function getMetaImageUrlAttribute()
    {
        if ($this->meta_image && Storage::disk('public')->exists("brands/{$this->id}/{$this->meta_image}")) {
            return Storage::disk('public')->url("brands/{$this->id}/{$this->meta_image}");
        }
        
        return asset('images/default-meta.jpg');
    }

    /**
     * Vérifier si le client est en période d'essai.
     */
    public function onTrial()
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Vérifier si le client a un abonnement actif.
     */
    public function hasActiveSubscription()
    {
        return !$this->subscription_ends_at || $this->subscription_ends_at->isFuture();
    }

    /**
     * Obtenir la configuration de marque complète.
     */
    public function getBrandConfig()
    {
        return [
            'name' => $this->name,
            'display_name' => $this->display_name,
            'logo' => $this->logo_url,
            'logo_dark' => $this->logo_dark_url,
            'favicon' => $this->favicon_url,
            'hero_image' => $this->hero_image_url,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'accent_color' => $this->accent_color,
            'success_color' => $this->success_color,
            'warning_color' => $this->warning_color,
            'error_color' => $this->error_color,
            'info_color' => $this->info_color,
            'background_primary' => $this->background_primary,
            'background_secondary' => $this->background_secondary,
            'background_dark' => $this->background_dark,
            'text_primary' => $this->text_primary,
            'text_secondary' => $this->text_secondary,
            'text_light' => $this->text_light,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_address,
            'contact_address' => $this->contact_address,
            'support_email' => $this->support_email,
            'social_facebook' => $this->social_facebook,
            'social_twitter' => $this->social_twitter,
            'social_linkedin' => $this->social_linkedin,
            'social_instagram' => $this->social_instagram,
            'company_name' => $this->company_name,
            'company_siret' => $this->company_siret,
            'company_vat' => $this->company_vat,
            'legal_notice' => $this->legal_notice,
            'terms_of_service' => $this->terms_of_service,
            'privacy_policy' => $this->privacy_policy,
            'theme' => $this->theme,
            'language' => $this->language,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'currency_symbol' => $this->currency_symbol,
            'enable_qr_codes' => $this->enable_qr_codes,
            'enable_notifications' => $this->enable_notifications,
            'enable_analytics' => $this->enable_analytics,
            'enable_maintenance_mode' => $this->enable_maintenance_mode,
            'payment_gateway' => $this->payment_gateway,
            'default_commission_rate' => $this->default_commission_rate,
            'activation_fee' => $this->activation_fee,
            'default_charging_rate' => $this->default_charging_rate,
            'max_charging_power' => $this->max_charging_power,
            'enable_smart_charging' => $this->enable_smart_charging,
            'email_from_name' => $this->email_from_name,
            'email_from_address' => $this->email_from_address,
            'email_signature' => $this->email_signature,
            'notification_channels' => $this->notification_channels,
            'session_lifetime' => $this->session_lifetime,
            'max_login_attempts' => $this->max_login_attempts,
            'password_expiry_days' => $this->password_expiry_days,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'meta_author' => $this->meta_author,
            'meta_image' => $this->meta_image_url,
            'cookie_consent_required' => $this->cookie_consent_required,
            'cookie_policy_url' => $this->cookie_policy_url,
            'cookie_analytics' => $this->cookie_analytics,
            'cookie_marketing' => $this->cookie_marketing,
            'google_analytics_id' => $this->google_analytics_id,
            'facebook_pixel_id' => $this->facebook_pixel_id,
            'hotjar_id' => $this->hotjar_id,
            'intercom_id' => $this->intercom_id,
            'webhook_url' => $this->webhook_url,
            'report_frequency' => $this->report_frequency,
            'report_retention_days' => $this->report_retention_days,
            'enable_auto_reports' => $this->enable_auto_reports,
            'backup_frequency' => $this->backup_frequency,
            'backup_retention_days' => $this->backup_retention_days,
            'backup_storage' => $this->backup_storage,
        ];
    }

    /**
     * Relations avec les autres modèles.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function chargingStations()
    {
        return $this->hasMany(ChargingStation::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function businessProfiles()
    {
        return $this->hasMany(BusinessProfile::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Scope pour les clients actifs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les clients par domaine.
     */
    public function scopeByDomain($query, $domain)
    {
        return $query->where('domain', $domain)
                    ->orWhere('subdomain', $domain);
    }

    /**
     * Boot du modèle.
     */
    protected static function boot()
    {
        parent::boot();

        // Créer le dossier de stockage lors de la création
        static::created(function ($client) {
            Storage::disk('public')->makeDirectory("brands/{$client->id}");
        });

        // Supprimer le dossier de stockage lors de la suppression
        static::deleted(function ($client) {
            Storage::disk('public')->deleteDirectory("brands/{$client->id}");
        });
    }
}