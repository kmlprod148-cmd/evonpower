<?php

namespace App\Traits;

use App\Models\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToClient
{
    /**
     * Boot du trait.
     */
    protected static function bootBelongsToClient()
    {
        // Ajouter automatiquement le client_id lors de la création
        static::creating(function ($model) {
            if (!$model->client_id && app()->has('current_client')) {
                $model->client_id = app('current_client')->id;
            }
        });

        // Scoper automatiquement les requêtes par client
        static::addGlobalScope('client', function (Builder $builder) {
            if (app()->has('current_client')) {
                $builder->where('client_id', app('current_client')->id);
            }
        });
    }

    /**
     * Relation avec le client.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope pour filtrer par client.
     */
    public function scopeForClient(Builder $query, Client $client): Builder
    {
        return $query->where('client_id', $client->id);
    }

    /**
     * Scope pour filtrer par client_id.
     */
    public function scopeForClientId(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Vérifier si le modèle appartient au client spécifié.
     */
    public function belongsToClient(Client $client): bool
    {
        return $this->client_id === $client->id;
    }

    /**
     * Vérifier si le modèle appartient au client actuel.
     */
    public function belongsToCurrentClient(): bool
    {
        if (!app()->has('current_client')) {
            return false;
        }
        return $this->client_id === app('current_client')->id;
    }

    /**
     * Obtenir le client du modèle.
     */
    public function getClient(): ?Client
    {
        return $this->client;
    }

    /**
     * Définir le client du modèle.
     */
    public function setClient(Client $client): void
    {
        $this->client_id = $client->id;
        $this->client = $client;
    }

    /**
     * Obtenir l'ID du client.
     */
    public function getClientId(): ?int
    {
        return $this->client_id;
    }

    /**
     * Définir l'ID du client.
     */
    public function setClientId(int $clientId): void
    {
        $this->client_id = $clientId;
    }

    /**
     * Vérifier si le modèle a un client.
     */
    public function hasClient(): bool
    {
        return !is_null($this->client_id);
    }

    /**
     * Supprimer la relation avec le client.
     */
    public function removeClient(): void
    {
        $this->client_id = null;
        $this->client = null;
    }

    /**
     * Obtenir la configuration de marque du client.
     */
    public function getClientBrandConfig(): array
    {
        return $this->client?->getBrandConfig() ?? [];
    }

    /**
     * Obtenir une valeur spécifique de la configuration de marque du client.
     */
    public function getClientBrandValue(string $key, $default = null)
    {
        $config = $this->getClientBrandConfig();
        return $config[$key] ?? $default;
    }

    /**
     * Obtenir le nom du client.
     */
    public function getClientName(): string
    {
        return $this->client?->name ?? 'Client inconnu';
    }

    /**
     * Obtenir le nom d'affichage du client.
     */
    public function getClientDisplayName(): string
    {
        return $this->client?->display_name ?? $this->getClientName();
    }

    /**
     * Obtenir le logo du client.
     */
    public function getClientLogo(): string
    {
        return $this->client?->logo_url ?? asset('images/default-logo.png');
    }

    /**
     * Obtenir la couleur primaire du client.
     */
    public function getClientPrimaryColor(): string
    {
        return $this->client?->primary_color ?? '#10B981';
    }

    /**
     * Obtenir la couleur secondaire du client.
     */
    public function getClientSecondaryColor(): string
    {
        return $this->client?->secondary_color ?? '#047857';
    }

    /**
     * Obtenir la langue du client.
     */
    public function getClientLanguage(): string
    {
        return $this->client?->language ?? 'fr';
    }

    /**
     * Obtenir la devise du client.
     */
    public function getClientCurrency(): string
    {
        return $this->client?->currency ?? 'EUR';
    }

    /**
     * Obtenir le symbole de la devise du client.
     */
    public function getClientCurrencySymbol(): string
    {
        return $this->client?->currency_symbol ?? '€';
    }

    /**
     * Obtenir le taux de commission par défaut du client.
     */
    public function getClientCommissionRate(): float
    {
        return $this->client?->default_commission_rate ?? 0.15;
    }

    /**
     * Obtenir les frais d'activation du client.
     */
    public function getClientActivationFee(): float
    {
        return $this->client?->activation_fee ?? 0.0;
    }

    /**
     * Obtenir le taux de recharge par défaut du client.
     */
    public function getClientChargingRate(): float
    {
        return $this->client?->default_charging_rate ?? 0.25;
    }

    /**
     * Obtenir la puissance maximale de recharge du client.
     */
    public function getClientMaxChargingPower(): float
    {
        return $this->client?->max_charging_power ?? 22.0;
    }

    /**
     * Vérifier si le client a activé les codes QR.
     */
    public function clientHasQrCodesEnabled(): bool
    {
        return $this->client?->enable_qr_codes ?? true;
    }

    /**
     * Vérifier si le client a activé les notifications.
     */
    public function clientHasNotificationsEnabled(): bool
    {
        return $this->client?->enable_notifications ?? true;
    }

    /**
     * Vérifier si le client a activé l'analytics.
     */
    public function clientHasAnalyticsEnabled(): bool
    {
        return $this->client?->enable_analytics ?? true;
    }

    /**
     * Vérifier si le client est en mode maintenance.
     */
    public function clientIsInMaintenanceMode(): bool
    {
        return $this->client?->enable_maintenance_mode ?? false;
    }

    /**
     * Obtenir la passerelle de paiement du client.
     */
    public function getClientPaymentGateway(): string
    {
        return $this->client?->payment_gateway ?? 'stripe';
    }

    /**
     * Obtenir l'email de contact du client.
     */
    public function getClientContactEmail(): string
    {
        return $this->client?->contact_email ?? 'contact@evon.com';
    }

    /**
     * Obtenir l'email de support du client.
     */
    public function getClientSupportEmail(): string
    {
        return $this->client?->support_email ?? 'support@evon.com';
    }

    /**
     * Obtenir le nom de l'entreprise du client.
     */
    public function getClientCompanyName(): string
    {
        return $this->client?->company_name ?? 'EVON SAS';
    }

    /**
     * Obtenir le SIRET de l'entreprise du client.
     */
    public function getClientCompanySiret(): string
    {
        return $this->client?->company_siret ?? '';
    }

    /**
     * Obtenir la TVA de l'entreprise du client.
     */
    public function getClientCompanyVat(): string
    {
        return $this->client?->company_vat ?? '';
    }

    /**
     * Obtenir les mentions légales du client.
     */
    public function getClientLegalNotice(): string
    {
        return $this->client?->legal_notice ?? '';
    }

    /**
     * Obtenir les conditions de service du client.
     */
    public function getClientTermsOfService(): string
    {
        return $this->client?->terms_of_service ?? '';
    }

    /**
     * Obtenir la politique de confidentialité du client.
     */
    public function getClientPrivacyPolicy(): string
    {
        return $this->client?->privacy_policy ?? '';
    }
}
