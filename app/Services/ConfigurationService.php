<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class ConfigurationService
{
    /**
     * Get configuration value
     */
    public function get($key, $default = null)
    {
        return Config::get($key, $default);
    }

    /**
     * Set configuration value
     */
    public function set($key, $value)
    {
        Config::set($key, $value);
    }

    /**
     * Get cached configuration
     */
    public function getCached($key, $default = null, $ttl = 3600)
    {
        return Cache::remember("config.{$key}", $ttl, function () use ($key, $default) {
            return $this->get($key, $default);
        });
    }

    /**
     * Clear configuration cache
     */
    public function clearCache($key = null)
    {
        if ($key) {
            Cache::forget("config.{$key}");
        } else {
            Cache::flush();
        }
    }

    /**
     * Get application settings
     */
    public function getAppSettings()
    {
        return [
            'app_name' => $this->get('app.name'),
            'app_env' => $this->get('app.env'),
            'app_debug' => $this->get('app.debug'),
            'app_url' => $this->get('app.url'),
        ];
    }

    /**
     * Get database configuration
     */
    public function getDatabaseConfig()
    {
        return [
            'default' => $this->get('database.default'),
            'connections' => $this->get('database.connections'),
        ];
    }

    /**
     * Get payment configuration
     */
    public function getPaymentConfig()
    {
        return [
            'cmi' => $this->get('payment.cmi'),
            'stripe' => $this->get('payment.stripe'),
        ];
    }
}