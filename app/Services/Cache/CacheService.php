<?php

namespace App\Services\Cache;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Récupère une valeur du cache
     */
    public function get(string $key, $default = null)
    {
        return Cache::get($key, $default);
    }

    /**
     * Stocke une valeur dans le cache
     */
    public function put(string $key, $value, $ttl = null)
    {
        return Cache::put($key, $value, $ttl);
    }

    /**
     * Supprime une valeur du cache
     */
    public function forget(string $key)
    {
        return Cache::forget($key);
    }

    /**
     * Vide tout le cache
     */
    public function flush()
    {
        return Cache::flush();
    }

    /**
     * Vérifie si une clé existe dans le cache
     */
    public function has(string $key)
    {
        return Cache::has($key);
    }

    /**
     * Récupère une valeur du cache ou exécute une closure et stocke le résultat
     */
    public function remember(string $key, $ttl, callable $callback)
    {
        return Cache::remember($key, $ttl, $callback);
    }
}