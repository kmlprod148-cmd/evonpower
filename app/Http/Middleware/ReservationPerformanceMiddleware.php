<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\ReservationPerformanceOptimizer;
use Illuminate\Support\Facades\Log;

/**
 * Middleware pour optimiser les performances des réservations
 */
class ReservationPerformanceMiddleware
{
    protected $optimizer;

    public function __construct(ReservationPerformanceOptimizer $optimizer)
    {
        $this->optimizer = $optimizer;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        // Optimiser les requêtes si nécessaire
        if ($this->shouldOptimize($request)) {
            $this->optimizer->optimizeDatabaseQueries();
        }

        $response = $next($request);

        // Ajouter des headers de performance
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        $response->headers->set('X-Execution-Time', $executionTime . 'ms');
        $response->headers->set('X-Memory-Usage', $this->formatBytes(memory_get_usage(true)));
        $response->headers->set('X-Peak-Memory', $this->formatBytes(memory_get_peak_usage(true)));

        // Log des performances si nécessaire
        if ($executionTime > 1000) { // Plus de 1 seconde
            Log::warning('Requête lente détectée', [
                'url' => $request->url(),
                'method' => $request->method(),
                'execution_time' => $executionTime,
                'memory_usage' => memory_get_usage(true)
            ]);
        }

        return $response;
    }

    /**
     * Détermine si l'optimisation est nécessaire
     */
    private function shouldOptimize(Request $request): bool
    {
        $optimizeRoutes = [
            'reservations.enhanced.index',
            'reservations.enhanced.show',
            'reservations.index',
            'transactions.index'
        ];

        return in_array($request->route()?->getName(), $optimizeRoutes);
    }

    /**
     * Formate les bytes en unités lisibles
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
