<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DisableOutputCompression
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Désactiver la compression de sortie pour éviter les erreurs ob_end_flush
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Désactiver la compression gzip
        if (function_exists('apache_setenv')) {
            apache_setenv('no-gzip', '1');
        }
        
        // Ajouter des headers pour désactiver la compression
        $response = $next($request);
        
        $response->headers->set('Content-Encoding', 'identity');
        $response->headers->set('Vary', 'Accept-Encoding');
        
        return $response;
    }
}
