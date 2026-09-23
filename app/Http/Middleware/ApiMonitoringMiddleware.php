<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ApiMonitoringService;
use Illuminate\Support\Facades\Log;

class ApiMonitoringMiddleware
{
    protected $monitoringService;

    public function __construct(ApiMonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        // Vérifier si c'est une requête API à monitorer
        if (!$this->shouldMonitor($request)) {
            return $next($request);
        }

        try {
            // Exécuter la requête
            $response = $next($request);
            
            $endTime = microtime(true);
            $responseTimeMs = round(($endTime - $startTime) * 1000);
            
            // Logger la requête
            $this->logRequest($request, $response, $responseTimeMs);
            
            return $response;
            
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $responseTimeMs = round(($endTime - $startTime) * 1000);
            
            // Logger l'erreur
            $this->logError($request, $e, $responseTimeMs);
            
            throw $e;
        }
    }

    /**
     * Déterminer si la requête doit être monitorée
     */
    protected function shouldMonitor(Request $request): bool
    {
        // Monitorer seulement les routes API
        if (!$request->is('api/*')) {
            return false;
        }

        // Exclure certaines routes de monitoring
        $excludedRoutes = [
            'api/monitoring/*',
            'api/health/*'
        ];

        foreach ($excludedRoutes as $pattern) {
            if ($request->is($pattern)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Logger une requête réussie
     */
    protected function logRequest(Request $request, $response, int $responseTimeMs): void
    {
        try {
            $apiName = $this->determineApiName($request);
            $endpoint = $this->getEndpoint($request);
            
            $this->monitoringService->logFromRequest(
                $request,
                $apiName,
                $endpoint,
                $response->getStatusCode(),
                $this->getResponseBody($response),
                $responseTimeMs,
                $response->isSuccessful(),
                null,
                $this->getResponseHeaders($response)
            );
            
        } catch (\Exception $e) {
            Log::error('ApiMonitoringMiddleware: Failed to log request', [
                'error' => $e->getMessage(),
                'url' => $request->url()
            ]);
        }
    }

    /**
     * Logger une erreur
     */
    protected function logError(Request $request, \Exception $e, int $responseTimeMs): void
    {
        try {
            $apiName = $this->determineApiName($request);
            $endpoint = $this->getEndpoint($request);
            
            $this->monitoringService->logFromRequest(
                $request,
                $apiName,
                $endpoint,
                500,
                null,
                $responseTimeMs,
                false,
                $e->getMessage(),
                null
            );
            
        } catch (\Exception $logError) {
            Log::error('ApiMonitoringMiddleware: Failed to log error', [
                'original_error' => $e->getMessage(),
                'log_error' => $logError->getMessage(),
                'url' => $request->url()
            ]);
        }
    }

    /**
     * Déterminer le nom de l'API
     */
    protected function determineApiName(Request $request): string
    {
        $path = $request->path();
        
        if (str_contains($path, 'steve')) {
            return 'steve';
        }
        
        if (str_contains($path, 'evon')) {
            return 'evon';
        }
        
        // Par défaut, considérer comme API Evon
        return 'evon';
    }

    /**
     * Obtenir l'endpoint de la requête
     */
    protected function getEndpoint(Request $request): string
    {
        return $request->path();
    }

    /**
     * Obtenir le corps de la réponse
     */
    protected function getResponseBody($response): ?string
    {
        try {
            $content = $response->getContent();
            
            // Limiter la taille du contenu
            if (strlen($content) > 10000) {
                return substr($content, 0, 10000) . '... [TRUNCATED]';
            }
            
            return $content;
            
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtenir les en-têtes de la réponse
     */
    protected function getResponseHeaders($response): ?array
    {
        try {
            return $response->headers->all();
        } catch (\Exception $e) {
            return null;
        }
    }
}
