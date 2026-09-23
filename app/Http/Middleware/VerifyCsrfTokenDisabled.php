<?php
namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Http\Request;
use Closure;

class VerifyCsrfToken extends Middleware
{
    protected $except = [
        'api/*',
        'webhook/*',
        'payment/cmi/callback',
        'payment/cmi/return',
    ];
    
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next)
    {
        // Désactiver temporairement le CSRF si la variable d'environnement est définie
        if (env('DISABLE_CSRF', false)) {
            return $next($request);
        }
        
        // Sinon, utiliser le comportement normal
        return parent::handle($request, $next);
    }
}