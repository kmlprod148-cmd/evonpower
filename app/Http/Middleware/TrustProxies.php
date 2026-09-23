<?php
namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*';

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_HOST | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO;

    /**
     * Handle an incoming request.
     * 
     * CRITICAL FIX pour mobile : Ce middleware DOIT être actif en production
     * car les appareils mobiles passent souvent par des proxies/load balancers
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        try {
            // Toujours activer la gestion des proxies (CRITIQUE pour mobile)
            return parent::handle($request, $next);
        } catch (\Exception $e) {
            // Log the error but don't break the request
            \Log::warning('TrustProxies middleware error: ' . $e->getMessage(), [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            // En cas d'erreur, continuer sans gestion des proxies
            return $next($request);
        } catch (\Throwable $e) {
            // Capturer aussi les erreurs fatales
            \Log::error('TrustProxies middleware fatal error: ' . $e->getMessage(), [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            return $next($request);
        }
    }
}