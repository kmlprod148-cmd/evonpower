<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exécuter les migrations.
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            
            // Informations de base
            $table->string('name');
            $table->string('display_name');
            $table->string('slug')->unique();
            $table->string('domain')->nullable()->unique();
            $table->string('subdomain')->nullable()->unique();
            
            // Logos et images
            $table->string('logo')->nullable();
            $table->string('logo_dark')->nullable();
            $table->string('favicon')->nullable();
            $table->string('hero_image')->nullable();
            
            // Couleurs principales
            $table->string('primary_color', 7)->default('#10B981');
            $table->string('secondary_color', 7)->default('#047857');
            $table->string('accent_color', 7)->default('#F59E0B');
            $table->string('success_color', 7)->default('#10B981');
            $table->string('warning_color', 7)->default('#F59E0B');
            $table->string('error_color', 7)->default('#EF4444');
            $table->string('info_color', 7)->default('#3B82F6');
            
            // Couleurs de fond
            $table->string('background_primary', 7)->default('#FFFFFF');
            $table->string('background_secondary', 7)->default('#F9FAFB');
            $table->string('background_dark', 7)->default('#111827');
            
            // Couleurs de texte
            $table->string('text_primary', 7)->default('#111827');
            $table->string('text_secondary', 7)->default('#6B7280');
            $table->string('text_light', 7)->default('#9CA3AF');
            
            // Informations de contact
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->text('contact_address')->nullable();
            $table->string('support_email');
            
            // Réseaux sociaux
            $table->string('social_facebook')->nullable();
            $table->string('social_twitter')->nullable();
            $table->string('social_linkedin')->nullable();
            $table->string('social_instagram')->nullable();
            
            // Informations légales
            $table->string('company_name');
            $table->string('company_siret')->nullable();
            $table->string('company_vat')->nullable();
            $table->longText('legal_notice')->nullable();
            $table->longText('terms_of_service')->nullable();
            $table->longText('privacy_policy')->nullable();
            
            // Configuration de l'interface
            $table->enum('theme', ['light', 'dark', 'auto'])->default('light');
            $table->enum('language', ['fr', 'en', 'ar'])->default('fr');
            $table->string('timezone')->default('Europe/Paris');
            $table->string('currency', 3)->default('EUR');
            $table->string('currency_symbol', 5)->default('€');
            
            // Configuration des fonctionnalités
            $table->boolean('enable_qr_codes')->default(true);
            $table->boolean('enable_notifications')->default(true);
            $table->boolean('enable_analytics')->default(true);
            $table->boolean('enable_maintenance_mode')->default(false);
            
            // Configuration des paiements
            $table->enum('payment_gateway', ['stripe', 'paypal', 'cmi'])->default('stripe');
            $table->decimal('default_commission_rate', 5, 4)->default(0.15);
            $table->decimal('activation_fee', 8, 2)->default(0.00);
            
            // Configuration des bornes
            $table->decimal('default_charging_rate', 8, 2)->default(0.25);
            $table->decimal('max_charging_power', 5, 2)->default(22.00);
            $table->boolean('enable_smart_charging')->default(true);
            
            // Configuration de l'email
            $table->string('email_from_name')->default('EVON');
            $table->string('email_from_address')->default('noreply@evon.com');
            $table->string('email_signature')->default('EVON Team');
            
            // Configuration des notifications
            $table->json('notification_channels')->nullable();
            
            // Configuration de la sécurité
            $table->integer('session_lifetime')->default(120);
            $table->integer('max_login_attempts')->default(5);
            $table->integer('password_expiry_days')->default(90);
            
            // Configuration des métadonnées SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('meta_author')->nullable();
            $table->string('meta_image')->nullable();
            
            // Configuration des cookies
            $table->boolean('cookie_consent_required')->default(true);
            $table->string('cookie_policy_url')->nullable();
            $table->boolean('cookie_analytics')->default(true);
            $table->boolean('cookie_marketing')->default(false);
            
            // Configuration des intégrations
            $table->string('google_analytics_id')->nullable();
            $table->string('facebook_pixel_id')->nullable();
            $table->string('hotjar_id')->nullable();
            $table->string('intercom_id')->nullable();
            
            // Configuration des webhooks
            $table->string('webhook_url')->nullable();
            $table->string('webhook_secret')->nullable();
            
            // Configuration des rapports
            $table->enum('report_frequency', ['daily', 'weekly', 'monthly'])->default('weekly');
            $table->integer('report_retention_days')->default(365);
            $table->boolean('enable_auto_reports')->default(true);
            
            // Configuration des sauvegardes
            $table->enum('backup_frequency', ['hourly', 'daily', 'weekly'])->default('daily');
            $table->integer('backup_retention_days')->default(30);
            $table->enum('backup_storage', ['local', 's3', 'ftp'])->default('local');
            
            // Statut et abonnement
            $table->boolean('is_active')->default(true);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('subscription_ends_at')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Index
            $table->index(['domain', 'subdomain']);
            $table->index('is_active');
            $table->index('slug');
        });
    }

    /**
     * Annuler les migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
