<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Cookies non chiffrés pour compatibilité mobile
        'locale',  // Cookie de langue (fallback pour mobile)
        'timezone', // Fuseau horaire utilisateur
        'currency', // Devise préférée
        // Note: La session reste chiffrée pour la sécurité
        // Ces cookies sont des fallbacks non sensibles pour améliorer l'UX mobile
    ];
}
